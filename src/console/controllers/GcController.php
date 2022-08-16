<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Console;
use craft\helpers\Db;
use yii\console\ExitCode;

/**
 * Allows you to manage garbage collection.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class GcController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'run';

    /**
     * @var bool Whether all soft-deleted items should be deleted, rather than just
     * the ones that were deleted long enough ago to be ready for hard-deletion
     * per the `softDeleteDuration` config setting.
     */
    public $deleteAllTrashed = false;

    /**
     * @var bool Whether old database tables should be emptied out.
     *
     * @since 3.6.11
     */
    public $emptyDeprecatedTables = false;

    /**
     * @var string[]
     */
    private $_deprecatedTables = [
        Table::TEMPLATECACHEELEMENTS,
        Table::TEMPLATECACHEQUERIES,
        Table::TEMPLATECACHES,
        Table::ENTRYDRAFTS,
        Table::ENTRYVERSIONS,
        '{{%entrydrafterrors}}',
        '{{%entryversionerrors}}',
    ];

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'deleteAllTrashed';
        $options[] = 'emptyDeprecatedTables';
        return $options;
    }

    /**
     * Runs garbage collection.
     *
     * @return int
     */
    public function actionRun(): int
    {
        $gc = Craft::$app->getGc();
        $deleteAllTrashed = $gc->deleteAllTrashed;
        $gc->deleteAllTrashed = $this->deleteAllTrashed || ($this->interactive && $this->confirm('Delete all trashed items?'));
        $this->stdout("Running garbage collection ...\n");
        $gc->run(true);
        $this->stdout('Finished running garbage collection.' . PHP_EOL, Console::FG_GREEN);
        $gc->deleteAllTrashed = $deleteAllTrashed;

        if ($this->_emptyDeprecatedTables()) {
            $db = Craft::$app->getDb();
            foreach ($this->_deprecatedTables as $table) {
                if ($db->tableExists($table)) {
                    $rows = (new Query())->from($table)->count('*', $db);
                    if ($rows) {
                        $this->stdout('Emptying ');
                        $this->stdout(trim($table, '{}%'), Console::FG_CYAN);
                        $this->stdout(" ($rows rows) ... ");
                        Db::delete($table);
                        $this->stdout("done\n", Console::FG_GREEN);
                    }
                }
            }
        }

        return ExitCode::OK;
    }

    /**
     * Returns whether deprecated tables should be emptied out.
     *
     * @return bool
     */
    private function _emptyDeprecatedTables(): bool
    {
        if ($this->emptyDeprecatedTables) {
            return true;
        }

        if (!$this->interactive) {
            return false;
        }

        $db = Craft::$app->getDb();
        $tablesWithRows = [];

        foreach ($this->_deprecatedTables as $table) {
            if ($db->tableExists($table) && (new Query())->from($table)->exists($db)) {
                $tablesWithRows[] = $table;
            }
        }

        if (empty($tablesWithRows)) {
            return false;
        }

        return $this->confirm("The following tables have data in them that isn’t needed anymore:\n" .
            implode("\n", array_map(function(string $table): string {
                return ' - ' . trim($table, '{}%');
            }, $tablesWithRows)) .
            "\nWould you like to clear them out?");
    }
}
