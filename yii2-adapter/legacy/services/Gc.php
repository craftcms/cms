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
use craft\console\Application as ConsoleApplication;
use craft\db\Connection;
use craft\db\TableSchema;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\ContentBlock;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\Tag;
use craft\elements\User;
use craft\errors\FsException;
use craft\helpers\Console;
use craft\records\Volume;
use craft\records\VolumeFolder;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tpetry\QueryExpressions\Function\Conditional\Coalesce;
use Tpetry\QueryExpressions\Language\Alias;
use yii\base\Component;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\db\Exception as DbException;
use yii\di\Instance;

/**
 * Garbage Collection service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getGc()|`Craft::$app->getGc()`]].
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
     * @var int The number of items that should be deleted in a single batch.
     */
    private const CHUNK_SIZE = 10000;

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
     * @var bool Whether CLI output should be muted.
     * @since 5.4.9
     */
    public bool $silent = false;

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
        $this->_generalConfig = app(GeneralConfig::class);
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
        $this->_deleteStaleBulkOpData();

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

        $this->deleteOrphanedFieldLayouts(Asset::class, Table::VOLUMES);
        $this->deleteOrphanedFieldLayouts(Category::class, Table::CATEGORYGROUPS);
        $this->deleteOrphanedFieldLayouts(Entry::class, Table::ENTRYTYPES);
        $this->deleteOrphanedFieldLayouts(GlobalSet::class, Table::GLOBALSETS);
        $this->deleteOrphanedFieldLayouts(Tag::class, Table::TAGGROUPS);

        $this->_deleteUnsupportedSiteEntries();
        $this->deleteOrphanedNestedElements(Address::class, Table::ADDRESSES);
        $this->deleteOrphanedNestedElements(ContentBlock::class, Table::CONTENTBLOCKS);
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
        $this->_deleteOrphanedFkRows();
        $this->_deletePointlessChangeData();

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

        $volumeIds = $this
            ->hardDeleteQuery(DB::table(Table::VOLUMES))
            ->pluck('id');

        $folders = DB::table(Table::VOLUMEFOLDERS)
            ->whereIn('volumeId', $volumeIds)
            ->select('id', 'path')
            ->get()
            ->all();

        usort($folders, fn($a, $b) => substr_count($a['path'], '/') < substr_count($b['path'], '/'));

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
            $this->hardDeleteQuery(DB::table(Table::ELEMENTS))
                ->whereIn('type', $normalElementTypes)
                ->delete();
        }

        if (empty($nestedElementTypes)) {
            $this->_stdout("done\n", Console::FG_GREEN);
            return;
        }

        // first get nested elements which are not nested (owned) and that don't have any revisions
        $ids1 = $this->hardDeleteQuery(DB::table(Table::ELEMENTS, 'e'), 'e')
            ->leftJoin(new Alias(Table::REVISIONS, 'r'), 'r.canonicalId','e.id')
            ->leftJoin(new Alias(Table::ELEMENTS_OWNERS, 'eo'), 'eo.elementId', '=', new Coalesce(['e.canonicalId', 'e.id']))
            ->whereIn('e.type', $nestedElementTypes)
            ->whereNull('r.id')
            ->whereNull('eo.elementId')
            ->pluck('e.id');

        // then get any nested elements that don't have any revisions, including nested ones
        $ids2 = $this->hardDeleteQuery(DB::table(Table::ELEMENTS, 'e'), 'e')
            ->leftJoin(new Alias(Table::REVISIONS, 'r'), 'r.canonicalId', '=', new Coalesce(['e.canonicalId', 'e.id']))
            ->whereIn('type', $nestedElementTypes)
            ->whereNull('r.id')
            ->pluck('e.id');

        $ids1
            ->merge($ids2)
            ->unique()
            ->chunk(self::CHUNK_SIZE)->each(function($idsChunk) {
                DB::table(Table::ELEMENTS)
                    ->whereIn('id', $idsChunk)
                    ->delete();
            });

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

        $tables = Arr::wrap($tables);

        foreach ($tables as $table) {
            $this->_stdout("    > deleting trashed rows in the `$table` table ... ");
            $this->hardDeleteQuery(DB::table($table))->delete();
            $this->_stdout("done\n", Console::FG_GREEN);
        }
    }

    /**
     * Deletes elements that are missing data in the given element extension table.
     *
     * @param class-string<ElementInterface> $elementType The element type
     * @param string $table The extension table name
     * @param string $fk The column name that contains the foreign key to `elements.id`
     *
     * @since 3.6.6
     */
    public function deletePartialElements(string $elementType, string $table, string $fk): void
    {
        $this->_stdout(sprintf('    > deleting partial %s data ... ', $elementType::lowerDisplayName()));

        DB::table(Table::ELEMENTS, 'e')
            ->leftJoin(new Alias($table, 't'), "t.$fk", 'e.id')
            ->where('e.type', $elementType)
            ->whereNull("t.$fk")
            ->pluck('e.id')
            ->chunk(self::CHUNK_SIZE)
            ->each(function($idsChunk) {
                DB::table(Table::ELEMENTS)
                    ->whereIn('id', $idsChunk)
                    ->delete();
            });

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

        $emptyFolderIds = DB::table(Table::VOLUMEFOLDERS, 'folders')
            ->leftJoin(new Alias(Table::ASSETS, 'assets'), 'assets.folderId', 'folders.id')
            ->whereNull(['folders.volumeId', 'assets.id'])
            ->whereNotNull(['folders.parentId', 'folders.path'])
            ->pluck('folders.id');

        if ($emptyFolderIds->isNotEmpty()) {
            Craft::$app->getAssets()->deleteFoldersByIds($emptyFolderIds->all());
        }

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
        DB::table(Table::SESSIONS)
            ->where('dateUpdated', '<', now()->subSeconds($this->_generalConfig->purgeStaleUserSessionDuration))
            ->delete();
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes any feature announcement rows that have gone stale.
     */
    private function _deleteStaleAnnouncements(): void
    {
        $this->_stdout('    > deleting stale feature announcements ... ');
        DB::table(Table::ANNOUNCEMENTS)
            ->where('dateRead', '<', now()->subDays(7))
            ->delete();
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes any stale element activity logs.
     */
    private function _deleteStaleElementActivity(): void
    {
        $this->_stdout('    > deleting stale element activity records ... ');
        DB::table(Table::ELEMENTACTIVITY)
            ->where('timestamp', '<', now()->subMinute())
            ->delete();
        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes any stale bulk operation data.
     */
    private function _deleteStaleBulkOpData(): void
    {
        $this->_stdout('    > deleting stale bulk operation data ... ');
        foreach ([Table::BULKOPEVENTS, Table::ELEMENTS_BULKOPS] as $table) {
            DB::table($table)
                ->where('timestamp', '<', now()->subWeeks(2))
                ->delete();
        }
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

        $siteIds = Sites::getAllSiteIds(true);
        $deleteIds = Collection::make();

        // get sections that are not enabled for given site
        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            $sectionSettings = $section->getSiteSettings();
            foreach ($siteIds as $siteId) {
                if (!isset($sectionSettings[$siteId])) {
                    $ids = DB::table(Table::ELEMENTS_SITES, 'es')
                        ->leftJoin(new Alias(Table::ENTRIES, 'en'), 'en.id', 'es.elementId')
                        ->where('en.sectionId', $section->id)
                        ->where('es.siteId', $siteId)
                        ->pluck('es.id');

                    $deleteIds->merge($ids);
                }
            }
        }

        $deleteIds->chunk(self::CHUNK_SIZE)->each(function($idsChunk) {
            DB::table(Table::ELEMENTS_SITES)
                ->whereIn('id', $idsChunk)
                ->delete();
        });

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes elements which have a `fieldId` value, but it’s set to an invalid field ID,
     * or they're missing a row in the `elements_owners` table.
     *
     * @param class-string<ElementInterface> $elementType The element type
     * @param string $table The extension table name
     * @param string $fieldFk The column name that contains the foreign key to `fields.id`
     *
     * @since 5.4.2
     */
    public function deleteOrphanedNestedElements(string $elementType, string $table, string $fieldFk = 'fieldId'): void
    {
        $this->_stdout(sprintf('    > deleting orphaned nested %s ... ', $elementType::pluralLowerDisplayName()));

        $ids1 = DB::table(Table::ELEMENTS, 'el')
            ->join(new Alias($table, 't'), 't.id', 'el.id')
            ->leftJoin(new Alias(Table::ELEMENTS_OWNERS, 'eo'), 'eo.elementId', 'el.id')
            ->whereNotNull("t.$fieldFk")
            ->whereNull('eo.elementId')
            ->pluck('el.id');

        $ids2 = DB::table(Table::ELEMENTS, 'el')
            ->join(new Alias($table, 't'), 't.id', 'el.id')
            ->leftJoin(new Alias(Table::FIELDS, 'f'), 'f.id', "t.$fieldFk")
            ->whereNotNull("t.$fieldFk")
            ->whereNull('f.id')
            ->pluck('el.id');

        $ids = $ids1->merge($ids2)->unique();

        if ($ids->isNotEmpty()) {
            DB::table(Table::ELEMENTS)
                ->whereIn('id', $ids)
                ->delete();
        }

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    /**
     * Deletes any orphaned rows in the `drafts` and `revisions` tables.
     */
    private function _deleteOrphanedDraftsAndRevisions(): void
    {
        $this->_stdout('    > deleting orphaned drafts and revisions ... ');

        foreach ([
                     'draftId' => Table::DRAFTS,
                     'revisionId' => Table::REVISIONS,
                 ] as $fk => $table) {
            DB::table($table, 't')
                ->leftJoin(new Alias(Table::ELEMENTS, 'e'), "e.$fk", "t.id")
                ->whereNull('e.id')
                ->pluck('t.id')
                ->chunk(self::CHUNK_SIZE)
                ->each(function($idsChunk) use ($table) {
                    DB::table($table)
                        ->whereIn('id', $idsChunk)
                        ->delete();
                });
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

        DB::table(Table::RELATIONS, 'r')
            ->leftJoin(new Alias(Table::ELEMENTS, 'e'), 'e.id', 'r.targetId')
            ->whereNull('e.id')
            ->pluck('r.id')
            ->chunk(self::CHUNK_SIZE)
            ->each(function($idsChunk) {
                DB::table(Table::RELATIONS)
                    ->whereIn('id', $idsChunk)
                    ->delete();
            });

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    private function _deleteOrphanedStructureElements(): void
    {
        $this->_stdout('    > deleting orphaned structure elements ... ');

        DB::table(Table::STRUCTUREELEMENTS, 'se')
            ->leftJoin(new Alias(Table::ELEMENTS, 'e'), 'e.id', 'se.elementId')
            ->whereNotNull('se.elementId')
            ->whereNull('e.id')
            ->pluck('se.id')
            ->chunk(self::CHUNK_SIZE)
            ->each(function($idsChunk) {
                DB::table(Table::STRUCTUREELEMENTS)
                    ->whereIn('id', $idsChunk)
                    ->delete();
            });

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    private function _deleteOrphanedFkRows(): void
    {
        $this->_stdout('    > deleting orphaned foreign key rows ... ');

        // Disable FK checks
        try {
            Schema::disableForeignKeyConstraints();
            $disabledFkChecks = true;
        } catch (DbException|QueryException) {
            // the DB user probably didn't have permission
            // see https://github.com/craftcms/cms/issues/15063#issuecomment-2194059768
            $disabledFkChecks = false;
        }

        $isMysql = $this->db->getIsMysql();
        foreach ($this->db->getSchema()->getTableSchemas() as $table) {
            /** @var TableSchema $table */
            $extendedFkInfo = $table->getExtendedForeignKeys();
            $counter = 0;
            foreach ($table->foreignKeys as $fk) {
                if ($extendedFkInfo[$counter]['deleteType'] === 'CASCADE') {
                    $fk = array_merge($fk);
                    $refTable = array_shift($fk);

                    foreach ($fk as $fkColumn => $pkColumn) {
                        if ($isMysql) {
                            $sql = <<<SQL
DELETE t.* FROM $table->name t
LEFT JOIN $refTable t2 ON t2.$pkColumn = t.$fkColumn
WHERE t.$fkColumn IS NOT NULL
AND t2.$pkColumn IS NULL
SQL;
                        } else {
                            $sql = <<<SQL
DELETE FROM $table->name t
WHERE t."$fkColumn" IS NOT NULL
AND NOT EXISTS (
    SELECT * FROM $refTable
    WHERE "$pkColumn" = t."$fkColumn"
)
SQL;
                        }

                        $this->db->createCommand($sql)->execute();
                    }
                }

                $counter++;
            }
        }

        // Re-enable FK checks
        if ($disabledFkChecks) {
            Schema::enableForeignKeyConstraints();
        }

        $this->_stdout("done\n", Console::FG_GREEN);
    }

    private function _deletePointlessChangeData(): void
    {
        $db = Craft::$app->getDb();
        $schema = $db->getSchema();

        foreach ([
                     Table::CHANGEDATTRIBUTES,
                     Table::CHANGEDFIELDS,
                 ] as $table) {
            $this->_stdout(sprintf('    > deleting pointless rows in the %s table ... ',
                $schema->getRawTableName($table)));

            // fetch any rows in the table for canonical elements that don't have any drafts
            DB::table($table, 't')
                ->join(new Alias(Table::ELEMENTS, 'e'), 'e.id', 't.elementId')
                ->leftJoin(new Alias(Table::ELEMENTS, 'd'), function(JoinClause $join) {
                    $join->whereColumn('d.canonicalId', 'e.id')
                        ->whereNotNull('d.draftId');
                })
                ->whereNull('e.canonicalId')
                ->whereNull('d.id')
                ->groupBy('t.elementId')
                ->select('t.elementId')
                ->cursor()
                ->chunk(100)
                ->each(function($chunk) use ($table) {
                    DB::table($table)
                        ->whereIn('elementId', $chunk->pluck('elementId'))
                        ->delete();
                });

            $this->_stdout("done\n", Console::FG_GREEN);
        }
    }

    /**
     * Deletes field layouts that are no longer used.
     *
     * @param class-string<ElementInterface> $elementType The element type
     * @param string $table The  table name that contains a foreign key to `fieldlayouts.id`
     * @param string $fk The column name that contains the foreign key to `fieldlayouts.id`
     *
     * @since 5.5.0
     */
    public function deleteOrphanedFieldLayouts(string $elementType, string $table, string $fk = 'fieldLayoutId'): void
    {
        $this->_stdout(sprintf('    > deleting orphaned %s field layouts ... ', $elementType::lowerDisplayName()));

        DB::table(Table::FIELDLAYOUTS, 'fl')
            ->leftJoin(new Alias($table, 't'), "t.$fk", 'fl.id')
            ->where('fl.type', $elementType)
            ->whereNull("t.$fk")
            ->pluck('fl.id')
            ->chunk(self::CHUNK_SIZE)
            ->each(function($idsChunk) {
                DB::table(Table::FIELDLAYOUTS)
                    ->whereIn('id', $idsChunk)
                    ->delete();
            });

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

        $structureIds = $this->hardDeleteQuery(DB::table($structuresTable, 's'), 's')
            ->leftJoin(new Alias($structureElementsTable, 'se'), 's.id', 'se.structureId')
            ->leftJoin(new Alias($elementsTable, 'e'), 'e.id', 'se.elementId')
            ->leftJoin(new Alias($revisionsTable, 'r'), 'r.canonicalId', '=', new Coalesce(['e.canonicalId', 'e.id']))
            ->whereNotNull('se.elementId')
            ->whereNull('r.canonicalId')
            ->distinct()
            ->pluck('s.id');

        if ($structureIds->isEmpty()) {
            return;
        }

        // and now perform the actual deletion based on those IDs
        $this->hardDeleteQuery(DB::table($structuresTable, 's'), 's')
            ->whereIn('s.id', $structureIds)
            ->delete();
    }

    private function _gcCache(): void
    {
        // @todo Not needed for Laravel?

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

    private function hardDeleteQuery(Builder $query, ?string $tableAlias = null): Builder
    {
        $tableAlias = $tableAlias ? "$tableAlias." : '';

        $query->whereNotNull("{$tableAlias}dateDeleted");

        if ($this->deleteAllTrashed) {
            return $query;
        }

        $query->where(
            "{$tableAlias}dateDeleted",
            '<',
            now()->subSeconds($this->_generalConfig->softDeleteDuration),
        );

        return $query;
    }

    private function _stdout(string $string, ...$format): void
    {
        if (!$this->silent && Craft::$app instanceof ConsoleApplication) {
            Console::stdout($string, ...$format);
        }
    }
}
