<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\helpers\Console;
use craft\console\Controller;
use yii\console\ExitCode;

/**
 * Allows you to manage garbage collection.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
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
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);
        $options[] = 'deleteAllTrashed';
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
        $gc->deleteAllTrashed = ($this->deleteAllTrashed || $this->confirm('Delete all trashed items?'));
        $this->stdout('Running garbage collection ... ', Console::FG_YELLOW);
        $gc->run(true);
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        $gc->deleteAllTrashed = $deleteAllTrashed;
        return ExitCode::OK;
    }
}
