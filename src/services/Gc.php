<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\GlobalSet;
use craft\elements\MatrixBlock;
use craft\elements\Tag;
use craft\elements\User;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use yii\base\Component;

/**
 * Garbage collection service.
 * An instance of the GC service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getGc()|`Craft::$app->gc`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class Gc extends Component
{
    /**
     * @event Event The event that is triggered when running garbage collection.
     */
    const EVENT_RUN = 'run';

    /**
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * on a request. Defaults to 10, meaning 0.001% chance.
     *
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all unless forced.
     */
    public $probability = 10;

    /**
     * @var bool whether [[hardDelete()]] should delete *all* soft-deleted rows,
     * rather than just the ones that were deleted long enough ago to be ready
     * for hard-deletion per the <config3:softDeleteDuration> config setting.
     */
    public $deleteAllTrashed = false;

    /**
     * Possibly runs garbage collection.
     *
     * @param bool $force Whether garbage collection should be forced. If left as `false`, then
     * garbage collection will only run if a random condition passes, factoring in [[probability]].
     */
    public function run(bool $force = false)
    {
        if (!$force && mt_rand(0, 1000000) >= $this->probability) {
            return;
        }

        Craft::$app->getDrafts()->purgeUnsavedDrafts();
        Craft::$app->getUsers()->purgeExpiredPendingUsers();
        $this->_deleteStaleSessions();

        $this->hardDelete([
            Table::ELEMENTS, // elements should always go first
            Table::CATEGORYGROUPS,
            Table::ENTRYTYPES,
            Table::FIELDGROUPS,
            Table::SECTIONS,
            Table::TAGGROUPS,
            Table::VOLUMES,
        ]);

        $this->deletePartialElements(Asset::class, Table::ASSETS, 'id');
        $this->deletePartialElements(Category::class, Table::CATEGORIES, 'id');
        $this->deletePartialElements(Entry::class, Table::ENTRIES, 'id');
        $this->deletePartialElements(GlobalSet::class, Table::GLOBALSETS, 'id');
        $this->deletePartialElements(MatrixBlock::class, Table::MATRIXBLOCKS, 'id');
        $this->deletePartialElements(Tag::class, Table::TAGS, 'id');
        $this->deletePartialElements(User::class, Table::USERS, 'id');

        $this->deletePartialElements(Asset::class, Table::CONTENT, 'elementId');
        $this->deletePartialElements(Category::class, Table::CONTENT, 'elementId');
        $this->deletePartialElements(Entry::class, Table::CONTENT, 'elementId');
        $this->deletePartialElements(GlobalSet::class, Table::CONTENT, 'elementId');
        $this->deletePartialElements(Tag::class, Table::CONTENT, 'elementId');
        $this->deletePartialElements(User::class, Table::CONTENT, 'elementId');

        $this->_deleteOrphanedDraftsAndRevisions();
        Craft::$app->getSearch()->deleteOrphanedIndexes();

        // Fire a 'run' event
        if ($this->hasEventHandlers(self::EVENT_RUN)) {
            $this->trigger(self::EVENT_RUN);
        }

        $this->hardDelete([
            Table::STRUCTURES,
            Table::FIELDLAYOUTS,
            Table::SITES,
        ]);
    }

    /**
     * Hard-deletes any rows in the given table(s), that are due for it.
     *
     * @param string|string[] $tables The table(s) to delete rows from. They must have a `dateDeleted` column.
     */
    public function hardDelete($tables)
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        if (!$generalConfig->softDeleteDuration && !$this->deleteAllTrashed) {
            return;
        }

        $condition = ['not', ['dateDeleted' => null]];

        if (!$this->deleteAllTrashed) {
            $expire = DateTimeHelper::currentUTCDateTime();
            $interval = DateTimeHelper::secondsToInterval($generalConfig->softDeleteDuration);
            $pastTime = $expire->sub($interval);
            $condition = [
                'and',
                $condition,
                ['<', 'dateDeleted', Db::prepareDateForDb($pastTime)],
            ];
        }

        if (!is_array($tables)) {
            $tables = [$tables];
        }

        foreach ($tables as $table) {
            Db::delete($table, $condition);
        }
    }

    /**
     * Deletes elements that are missing data in the given element extension table.
     *
     * @param string $elementType The element type
     * @param string $table The extension table name
     * @param string $fk The column name that contains the foreign key to `elements.id`
     * @return void
     * @since 3.6.6
     */
    public function deletePartialElements(string $elementType, string $table, string $fk): void
    {
        $db = Craft::$app->getDb();
        $elementsTable = Table::ELEMENTS;

        if ($db->getIsMysql()) {
            $sql = <<<SQL
DELETE [[e]].* FROM $elementsTable [[e]]
LEFT JOIN $table [[t]] ON [[t.$fk]] = [[e.id]]
WHERE
  [[e.type]] = :type AND
  [[t.$fk]] IS NULL
SQL;
        } else {
            $sql = <<<SQL
DELETE FROM $elementsTable
USING $elementsTable [[e]]
LEFT JOIN $table [[t]] ON [[t.$fk]] = [[e.id]]
WHERE
  $elementsTable.[[id]] = [[e.id]] AND
  [[e.type]] = :type AND
  [[t.$fk]] IS NULL
SQL;
        }

        $db->createCommand($sql, ['type' => $elementType])->execute();
    }

    /**
     * Deletes any session rows that have gone stale.
     */
    private function _deleteStaleSessions()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->purgeStaleUserSessionDuration === 0) {
            return;
        }

        $interval = DateTimeHelper::secondsToInterval($generalConfig->purgeStaleUserSessionDuration);
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

        Db::delete(Table::SESSIONS, ['<', 'dateUpdated', Db::prepareDateForDb($pastTime)]);
    }

    /**
     * Deletes any orphaned rows in the `drafts` and `revisions` tables.
     *
     * @return void
     */
    private function _deleteOrphanedDraftsAndRevisions(): void
    {
        $db = Craft::$app->getDb();
        $elementsTable = Table::ELEMENTS;

        foreach (['draftId' => Table::DRAFTS, 'revisionId' => Table::REVISIONS] as $fk => $table) {
            if ($db->getIsMysql()) {
                $sql = <<<SQL
DELETE [[t]].* FROM $table [[t]]
LEFT JOIN $elementsTable [[e]] ON [[e.$fk]] = [[t.id]]
WHERE [[e.id]] IS NULL
SQL;
            } else {
                $sql = <<<SQL
DELETE FROM $table
USING $table [[t]]
LEFT JOIN $elementsTable [[e]] ON [[e.$fk]] = [[t.id]]
WHERE
  $table.[[id]] = [[t.id]] AND
  [[e.id]] IS NULL
SQL;
            }

            $db->createCommand($sql)->execute();
        }
    }
}
