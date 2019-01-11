<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\db\Query;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use DateInterval;
use yii\base\Component;

/**
 * Garbage collection service.
 * An instance of the GC service is globally accessible in Craft via [[\craft\base\ApplicationTrait::getGc()|`Craft::$app->gc`]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
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
     * rather than just the ones that were deleted long enough ago to be ready for hard-deletion
     * per the [[\craft\config\GeneralConfig::softDeleteDuration]] config setting.
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

        Craft::$app->getUsers()->purgeExpiredPendingUsers();
        $this->_deleteStaleSessions();

        $this->hardDelete([
            '{{%elements}}',
            '{{%fieldlayouts}}',
            '{{%sites}}',
        ]);

        $this->hardDeleteWithFieldLayouts([
            '{{%volumes}}',
        ]);

        // Fire a 'run' event
        if ($this->hasEventHandlers(self::EVENT_RUN)) {
            $this->trigger(self::EVENT_RUN);
        }
    }

    /**
     * Hard-deletes any rows in the given table(s), that are due for it.
     *
     * @param string|string[] $tables The table(s) to delete rows from. They must have a `dateDeleted` column.
     */
    public function hardDelete($tables)
    {
        if (!$this->shouldHardDelete()) {
            return;
        }

        if (!is_array($tables)) {
            $tables = [$tables];
        }

        $db = Craft::$app->getDb();
        $condition = $this->hardDeleteCondition();

        foreach ($tables as $table) {
            $db->createCommand()
                ->delete($table, $condition)
                ->execute();
        }
    }

    /**
     * Hard-deletes any rows in the given table(s) that are due for it, along with their field layouts.
     *
     * @param string|string[] $tables The table(s) to delete rows from. They must have `id`, `fieldLayoutId`, and
     * `dateDeleted` columns.
     */
    public function hardDeleteWithFieldLayouts($tables)
    {
        if (!$this->shouldHardDelete()) {
            return;
        }

        if (!is_array($tables)) {
            $tables = [$tables];
        }

        $condition = $this->hardDeleteCondition();
        $fields = Craft::$app->getFields();
        $db = Craft::$app->getDb();

        foreach ($tables as $table) {
            $results = (new Query())
                ->select(['id', 'fieldLayoutId'])
                ->from([$table])
                ->where($condition)
                ->all();

            if (!empty($results)) {
                $fields->deleteLayoutById(ArrayHelper::getColumn($results, 'fieldLayoutId'));
                $db->createCommand()
                    ->delete($table, [
                        'id' => ArrayHelper::getColumn($results, 'id'),
                    ])
                    ->execute();
            }
        }
    }

    /**
     * Returns whether anything should be hard-deleted.
     *
     * @return bool
     */
    public function shouldHardDelete(): bool
    {
        return $this->deleteAllTrashed || Craft::$app->getConfig()->getGeneral()->softDeleteDuration;
    }

    /**
     * Returns the condition that should be used to find table rows that are due to be hard-deleted.
     *
     * @return array
     */
    public function hardDeleteCondition(): array
    {
        $condition = ['not', ['dateDeleted' => null]];

        if (!$this->deleteAllTrashed) {
            $expire = DateTimeHelper::currentUTCDateTime();
            $interval = DateTimeHelper::secondsToInterval(Craft::$app->getConfig()->getGeneral()->softDeleteDuration);
            $pastTime = $expire->sub($interval);
            $condition = [
                'and',
                $condition,
                ['<', 'dateDeleted', Db::prepareDateForDb($pastTime)],
            ];
        }

        return $condition;
    }

    /**
     * Deletes any session rows that have gone stale.
     */
    private function _deleteStaleSessions()
    {
        $interval = new DateInterval('P3M');
        $expire = DateTimeHelper::currentUTCDateTime();
        $pastTime = $expire->sub($interval);

        Craft::$app->getDb()->createCommand()
            ->delete('{{%sessions}}', ['<', 'dateUpdated', Db::prepareDateForDb($pastTime)])
            ->execute();
    }
}
