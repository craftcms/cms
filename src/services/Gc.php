<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\base\ElementInterface;
use craft\base\NestedElementInterface;
use craft\config\GeneralConfig;
use craft\console\Application as ConsoleApplication;
use craft\db\Connection;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\elements\User;
use craft\errors\FsException;
use craft\fs\Temp;
use craft\helpers\Console;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\records\Volume;
use craft\records\VolumeFolder;
use DateTime;
use ReflectionMethod;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\di\Instance;

/**
 * Garbage Collection service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getGc()|`Craft::$app->gc`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class Gc extends Component
{
    /**
     * @event Event The event that is triggered when running garbage collection.
     */
    public const EVENT_RUN = 'run';

    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * on a request. Defaults to 10, meaning 0.001% chance.
     *
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all unless forced.
     */
    public int $probability = 10;

    /**
     * @var bool whether [[hardDelete()]] should delete *all* soft-deleted rows,
     * rather than just the ones that were deleted long enough ago to be ready
     * for hard-deletion per the <config5:softDeleteDuration> config setting.
     */
    public bool $deleteAllTrashed = false;

    /**
     * @var Connection|array|string The database connection to use
     * @since 4.0.0
     */
    public string|array|Connection $db = 'db';

    /**
     * @var GeneralConfig
     */
    private GeneralConfig $_generalConfig;

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->db = Instance::ensure($this->db, Connection::class);
        $this->_generalConfig = Craft::$app->getConfig()->getGeneral();
        parent::init();
    }

    /**
     * Possibly runs garbage collection.
     *
     * @param bool $force Whether garbage collection should be forced. If left as `false`, then
     * garbage collection will only run if a random condition passes, factoring in [[probability]].
     */
    public function run(bool $force = false): void
    {
        if (!$force && mt_rand(0, 1000000) >= $this->probability) {
            return;
        }

        $this->_purgeUnsavedDrafts();
        $this->_purgePendingUsers();
        $this->_deleteStaleSessions();
        $this->_deleteStaleAnnouncements();
        $this->_deleteStaleElementActivity();
        $this->_deleteStaleBulkElementOps();

        // elements should always go first
        $this->hardDeleteElements();

        $this->hardDelete([
            Table::CATEGORYGROUPS,
            Table::ENTRYTYPES,
            Table::FIELDS,
            Table::SECTIONS,
            Table::TAGGROUPS,
        ]);

        $this->deletePartialElements(Address::class, Table::ADDRESSES, 'id');
        $this->deletePartialElements(Asset::class, Table::ASSETS, 'id');
        $this->deletePartialElements(Category::class, Table::CATEGORIES, 'id');
        $this->deletePartialElements(Entry::class, Table::ENTRIES, 'id');
        $this->deletePartialElements(GlobalSet::class, Table::GLOBALSETS, 'id');
        $this->deletePartialElements(Tag::class, Table::TAGS, 'id');
        $this->deletePartialElements(User::class, Table::USERS, 'id');

        $this->_deleteUnsupportedSiteEntries();
        $this->deleteOrphanedNestedElements(Address::class, Table::ADDRESSES);
        $this->deleteOrphanedNestedElements(Entry::class, Table::ENTRIES);

        // Fire a 'run' event
        // Note this should get fired *before* orphaned drafts & revisions are deleted
        // (see https://github.com/craftcms/cms/issues/14309)
        if ($this->hasEventHandlers(self::EVENT_RUN)) {
            $this->trigger(self::EVENT_RUN);
        }

        $this->_deleteOrphanedDraftsAndRevisions();
        $this->_deleteOrphanedSearchIndexes();
        $this->_deleteOrphanedRelations();
        $this->_deleteOrphanedStructureElements();

        $this->_hardDeleteStructures();

        $this->hardDelete([
            Table::FIELDLAYOUTS,
            Table::SITES,
        ]);

        $this->hardDeleteVolumes();
        $this->removeEmptyTempFolders();
        $this->_gcCache();

        // Invalidate all element caches so any hard-deleted elements don't look like they still exist
        Craft::$app->getElements()->invalidateAllCaches();
    }

    /**
     * Hard delete eligible volumes, deleting the folders one by one to avoid nested dependency errors.
     */
    public function hardDeleteVolumes(): void
    {
        if (!$this->_shouldHardDelete()) {
            return;
        }

        $this->_stdout("    > deleting trashed volumes and their folders ... ");
        $condition = $this->_hardDeleteCondition();

        $volumes = (new Query())->select(['id'])->from([Table::VOLUMES])->where($condition)->all();
        $volumeIds = [];

        foreach ($volumes as $volume) {
            $volumeIds[] = $volume['id'];
        }

        $folders = (new Query())->select(['id', 'path'])->from([Table::VOLUMEFOLDERS])->where(['volumeId' => $volumeIds])->all();
        usort($folders, function($a, $b) {
            return substr_count($a['path'], '/') < substr_count($b['path'], '/');
        });

        foreach ($folders as $folder) {
            VolumeFolder::deleteAll(['id' => $folder['id']]);
        }

        Volume::deleteAll(['id' => $volumeIds]);
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Hard-deletes eligible elements.
     *
     * Any soft-deleted nested elements which have revisions will be skipped, as their revisions may still be needed by the owner element.
     *
     * @since 4.0.0
     */
    public function hardDeleteElements(): void
    {
        if (!$this->_shouldHardDelete()) {
            return;
        }

        $normalElementTypes = [];
        $nestedElementTypes = [];

        foreach (Craft::$app->getElements()->getAllElementTypes() as $elementType) {
            if (is_subclass_of($elementType, NestedElementInterface::class)) {
                $nestedElementTypes[] = $elementType;
            } else {
                $normalElementTypes[] = $elementType;
            }
        }

        $this->_stdout('    > deleting trashed elements ... ');

        if ($normalElementTypes) {
            Db::delete(Table::ELEMENTS, [
                'and',
                $this->_hardDeleteCondition(),
                ['type' => $normalElementTypes],
            ]);
        }

        if (!empty($nestedElementTypes)) {
            // first get nested elements which are not nested (owned) and that don't have any revisions
            $ids1 = (new Query())
                ->select('e.id')
                ->from(['e' => Table::ELEMENTS])
                ->leftJoin(['r' => Table::REVISIONS], '[[r.canonicalId]] = [[e.id]]')
                ->leftJoin(['eo' => Table::ELEMENTS_OWNERS], '[[eo.elementId]] = COALESCE([[e.canonicalId]], [[e.id]])')
                ->where([
                    'and',
                    $this->_hardDeleteCondition('e'),
                    [
                        'e.type' => $nestedElementTypes,
                        'r.id' => null,
                        'eo.elementId' => null,
                    ],
                ])
                ->column();

            // then get any nested elements that don't have any revisions, including nested ones
            $ids2 = (new Query())
                ->select('e.id')
                ->from(['e' => Table::ELEMENTS])
                ->leftJoin(['r' => Table::REVISIONS], '[[r.canonicalId]] = COALESCE([[e.canonicalId]], [[e.id]])')
                ->where([
                    'and',
                    $this->_hardDeleteCondition('e'),
                    [
                        'e.type' => $nestedElementTypes,
                        'r.id' => null,
                    ],
                ])
                ->column();

            $ids = array_unique(array_merge($ids1, $ids2));

            if (!empty($ids)) {
                Db::delete(Table::ELEMENTS, ['id' => $ids]);
            }
        }

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Hard-deletes any rows in the given table(s), that are due for it.
     *
     * @param string|string[] $tables The table(s) to delete rows from. They must have a `dateDeleted` column.
     */
    public function hardDelete(array|string $tables): void
    {
        if (!$this->_shouldHardDelete()) {
            return;
        }

        $condition = $this->_hardDeleteCondition();

        if (!is_array($tables)) {
            $tables = [$tables];
        }

        foreach ($tables as $table) {
            $this->_stdout("    > deleting trashed rows in the `$table` table ... ");
            Db::delete($table, $condition);
            $this->_stdout("done\n", Console::FG_GREEN);
        }
    }

    /**
     * Deletes elements that are missing data in the given element extension table.
     *
     * @param string $elementType The element type
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param string $table The extension table name
     * @param string $fk The column name that contains the foreign key to `elements.id`
     * @since 3.6.6
     */
    public function deletePartialElements(string $elementType, string $table, string $fk): void
    {
        /** @var string|ElementInterface $elementType */
        $this->_stdout(sprintf('    > deleting partial %s data in the `%s` table ... ', $elementType::lowerDisplayName(), $table));

        $ids = (new Query())
            ->select('e.id')
            ->from(['e' => Table::ELEMENTS])
            ->leftJoin(['t' => $table], "[[t.$fk]] = [[e.id]]")
            ->where([
                'e.type' => $elementType,
                "t.$fk" => null,
            ])
            ->column();

        if (!empty($ids)) {
            Db::delete(Table::ELEMENTS, ['id' => $ids]);
        }

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    private function _purgeUnsavedDrafts()
    {
        if ($this->_generalConfig->purgeUnsavedDraftsDuration === 0) {
            return;
        }

        $this->_stdout('    > purging unsaved drafts that have gone stale ... ');
        Craft::$app->getDrafts()->purgeUnsavedDrafts();
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    private function _purgePendingUsers()
    {
        if ($this->_generalConfig->purgePendingUsersDuration === 0) {
            return;
        }

        $this->_stdout('    > purging pending users with stale activation codes ... ');
        Craft::$app->getUsers()->purgeExpiredPendingUsers();
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Find all temp upload folders with no assets in them and remove them.
     *
     * @throws FsException
     * @throws Exception
     * @throws InvalidConfigException
     * @since 4.0.0
     */
    public function removeEmptyTempFolders(): void
    {
        $this->_stdout('    > removing empty temp folders ... ');

        $emptyFolders = (new Query())
            ->select(['folders.id', 'folders.path'])
            ->from(['folders' => Table::VOLUMEFOLDERS])
            ->leftJoin(['assets' => Table::ASSETS], '[[assets.folderId]] = [[folders.id]]')
            ->where([
                'folders.volumeId' => null,
                'assets.id' => null,
            ])
            ->andWhere(['not', ['folders.parentId' => null]])
            ->andWhere(['not', ['folders.path' => null]])
            ->pairs();

        $fs = Craft::createObject(Temp::class);

        foreach ($emptyFolders as $emptyFolderPath) {
            if ($fs->directoryExists($emptyFolderPath)) {
                $fs->deleteDirectory($emptyFolderPath);
            }
        }

        VolumeFolder::deleteAll(['id' => array_keys($emptyFolders)]);
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Returns whether we should be hard-deleting soft-deleted objects.
     *
     * @return bool
     */
    private function _shouldHardDelete(): bool
    {
        return $this->_generalConfig->softDeleteDuration || $this->deleteAllTrashed;
    }

    /**
     * Deletes any session rows that have gone stale.
     */
    private function _deleteStaleSessions(): void
    {
        if ($this->_generalConfig->purgeStaleUserSessionDuration === 0) {
            return;
        }

        $this->_stdout('    > deleting stale user sessions ... ');
        $interval = DateTimeHelper::secondsToInterval($this->_generalConfig->purgeStaleUserSessionDuration);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);
        Db::delete(Table::SESSIONS, ['<', 'dateUpdated', Db::prepareDateForDb($pastTime)]);
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes any feature announcement rows that have gone stale.
     */
    private function _deleteStaleAnnouncements(): void
    {
        $this->_stdout('    > deleting stale feature announcements ... ');
        Db::delete(Table::ANNOUNCEMENTS, ['<', 'dateRead', Db::prepareDateForDb(new DateTime('7 days ago'))]);
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes any stale element activity logs.
     */
    private function _deleteStaleElementActivity(): void
    {
        $this->_stdout('    > deleting stale element activity records ... ');
        Db::delete(Table::ELEMENTACTIVITY, ['<', 'timestamp', Db::prepareDateForDb(new DateTime('1 minute ago'))]);
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes any stale bulk element operation records.
     */
    private function _deleteStaleBulkElementOps(): void
    {
        $this->_stdout('    > deleting stale bulk element operation records ... ');
        Db::delete(Table::ELEMENTS_BULKOPS, ['<', 'timestamp', Db::prepareDateForDb(new DateTime('2 weeks ago'))]);
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes entries for sites that aren’t enabled by their section.
     *
     * This can happen if you entrify a category group, disable one of the sites in the newly-created section’s
     * settings, then deploy those changes to another environment, apply project config changes, and re-run the
     * entrify command. (https://github.com/craftcms/cms/issues/13383)
     */
    private function _deleteUnsupportedSiteEntries(): void
    {
        $this->_stdout('    > deleting entries in unsupported sites ... ');

        $siteIds = Craft::$app->getSites()->getAllSiteIds(true);
        $deleteIds = [];

        // get sections that are not enabled for given site
        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            $sectionSettings = $section->getSiteSettings();
            foreach ($siteIds as $siteId) {
                if (!isset($sectionSettings[$siteId])) {
                    $ids = (new Query())
                        ->select('es.id')
                        ->from(['es' => Table::ELEMENTS_SITES])
                        ->leftJoin(['en' => Table::ENTRIES], '[[en.id]] = [[es.elementId]]')
                        ->where([
                            'en.sectionId' => $section->id,
                            'es.siteId' => $siteId,
                        ])
                        ->column();

                    $deleteIds = array_merge($deleteIds, $ids);
                }
            }
        }

        if (!empty($deleteIds)) {
            Db::delete(Table::ELEMENTS_SITES, ['id' => $deleteIds]);
        }

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes elements which have a `fieldId` value, but it’s set to an invalid field ID,
     * or they're missing a row in the `elements_owners` table.
     *
     * @param string $elementType The element type
     * @phpstan-param class-string<ElementInterface> $elementType
     * @param string $table The extension table name
     * @param string $fieldFk The column name that contains the foreign key to `fields.id`
     * @since 5.4.2
     */
    public function deleteOrphanedNestedElements(string $elementType, string $table, string $fieldFk = 'fieldId'): void
    {
        /** @var string|ElementInterface $elementType */
        $this->_stdout(sprintf('    > deleting orphaned nested %s ... ', $elementType::pluralLowerDisplayName()));

        $ids1 = (new Query())
            ->select('el.id')
            ->from(['el' => Table::ELEMENTS])
            ->innerJoin(['t' => $table], '[[t.id]] = [[el.id]]')
            ->leftJoin(['eo' => Table::ELEMENTS_OWNERS], '[[eo.elementId]] = [[el.id]]')
            ->where([
                'and',
                ['not', ["t.$fieldFk" => null]],
                ['eo.elementId' => null],
            ])
            ->column();

        $ids2 = (new Query())
            ->select('el.id')
            ->from(['el' => Table::ELEMENTS])
            ->innerJoin(['t' => $table], '[[t.id]] = [[el.id]]')
            ->leftJoin(['f' => Table::FIELDS], "[[f.id]] = [[t.$fieldFk]]")
            ->where([
                'and',
                ['not', ["t.$fieldFk" => null]],
                ['f.id' => null],
            ])
            ->column();

        $ids = array_unique(array_merge($ids1, $ids2));

        if (!empty($ids)) {
            Db::delete(Table::ELEMENTS, ['id' => $ids]);
        }

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes any orphaned rows in the `drafts` and `revisions` tables.
     */
    private function _deleteOrphanedDraftsAndRevisions(): void
    {
        $this->_stdout('    > deleting orphaned drafts and revisions ... ');

        foreach (['draftId' => Table::DRAFTS, 'revisionId' => Table::REVISIONS] as $fk => $table) {
            $ids = (new Query())
                ->select('t.id')
                ->from(['t' => $table])
                ->leftJoin(['e' => Table::ELEMENTS], "[[e.$fk]] = [[t.id]]")
                ->where(['e.id' => null])
                ->column();

            if (!empty($ids)) {
                Db::delete($table, ['id' => $ids]);
            }
        }

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    private function _deleteOrphanedSearchIndexes(): void
    {
        $this->_stdout('    > deleting orphaned search indexes ... ');
        Craft::$app->getSearch()->deleteOrphanedIndexes();
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    private function _deleteOrphanedRelations(): void
    {
        $this->_stdout('    > deleting orphaned relations ... ');

        $ids = (new Query())
            ->select('r.id')
            ->from(['r' => Table::RELATIONS])
            ->leftJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[r.targetId]]')
            ->where(['e.id' => null])
            ->column();

        if (!empty($ids)) {
            Db::delete(Table::RELATIONS, ['id' => $ids]);
        }

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    private function _deleteOrphanedStructureElements(): void
    {
        $this->_stdout('    > deleting orphaned structure elements ... ');

        $ids = (new Query())
            ->select('se.id')
            ->from(['se' => Table::STRUCTUREELEMENTS])
            ->leftJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[se.elementId]]')
            ->where([
                'and',
                ['not', ['se.elementId' => null]],
                ['e.id' => null],
            ])
            ->column();

        if (!empty($ids)) {
            Db::delete(Table::STRUCTUREELEMENTS, ['id' => $ids]);
        }

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Hard delete structures data
     * Any soft-deleted structure elements which have revisions will be skipped, as their revisions may still be needed by the owner element.
     *
     * @return void
     * @throws \yii\db\Exception
     */
    private function _hardDeleteStructures(): void
    {
        // get IDs of structures that can be deleted;
        // those are the ones for which the elements don't have any revisions
        $structuresTable = Table::STRUCTURES;
        $structureElementsTable = Table::STRUCTUREELEMENTS;
        $elementsTable = Table::ELEMENTS;
        $revisionsTable = Table::REVISIONS;

        $params = [];

        $structureIds = (new Query())
            ->select('[[s.id]]')
            ->distinct()
            ->from(['s' => $structuresTable])
            ->leftJoin(['se' => $structureElementsTable], '[[s.id]] = [[se.structureId]]')
            ->leftJoin(['e' => $elementsTable], '[[e.id]] = [[se.elementId]]')
            ->leftJoin(['r' => $revisionsTable], '[[r.canonicalId]] = coalesce([[e.canonicalId]],[[e.id]])')
            ->where([
                'and',
                ['not', ['se.elementId' => null]],
                $this->_hardDeleteCondition('s'),
                [
                    'r.canonicalId' => null,
                ],
            ])
            ->column();

        if (!empty($structureIds)) {
            $ids = implode(',', $structureIds);
            $conditionSql = $this->db->getQueryBuilder()->buildCondition($this->_hardDeleteCondition('s'), $params);

            // and now perform the actual deletion based on those IDs
            if ($this->db->getIsMysql()) {
                $sql = <<<SQL
DELETE [[s]].* FROM $structuresTable [[s]]
WHERE [[s.id]] IN ($ids)
AND $conditionSql
SQL;
            } else {
                $sql = <<<SQL
DELETE FROM $structuresTable
USING $structuresTable [[s]]
WHERE 
    $structuresTable.[[id]] = [[s.id]] AND 
    [[s.id]] IN ($ids) AND
    $conditionSql
SQL;
            }
            $this->db->createCommand($sql, $params)->execute();
        }
    }

    private function _gcCache(): void
    {
        $cache = Craft::$app->getCache();

        // gc() isn't always implemented, or defined by an interface,
        // so we have to be super defensive here :-/

        if (!method_exists($cache, 'gc')) {
            return;
        }

        $method = new ReflectionMethod($cache, 'gc');

        if (!$method->isPublic()) {
            return;
        }

        $requiredArgs = $method->getNumberOfRequiredParameters();
        $firstArg = $method->getParameters()[0] ?? null;
        $hasForceArg = $firstArg && $firstArg->getName() === 'force';

        if ($requiredArgs > 1 || ($requiredArgs === 1 && !$hasForceArg)) {
            return;
        }

        $this->_stdout('    > garbage-collecting data caches ... ');

        if ($hasForceArg) {
            $cache->gc(true);
        } else {
            $cache->gc();
        }

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * @param string|null $tableAlias
     * @return array
     */
    private function _hardDeleteCondition(?string $tableAlias = null): array
    {
        $tableAlias = $tableAlias ? "$tableAlias." : '';
        $condition = ['not', ["{$tableAlias}dateDeleted" => null]];

        if (!$this->deleteAllTrashed) {
            $expire = DateTimeHelper::currentUTCDateTime();
            $interval = DateTimeHelper::secondsToInterval($this->_generalConfig->softDeleteDuration);
            $pastTime = $expire->sub($interval);
            $condition = [
                'and',
                $condition,
                ['<', "{$tableAlias}dateDeleted", Db::prepareDateForDb($pastTime)],
            ];
        }

        return $condition;
    }

    private function _stdout(string $string, ...$format): void
    {
        if (Craft::$app instanceof ConsoleApplication) {
            Console::stdout($string, ...$format);
        }
    }
}
