<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\db\MigrationManager;
use craft\errors\OperationAbortedException;
use craft\helpers\Console;
use Throwable;
use yii\console\ExitCode;

/**
 * Runs pending migrations and applies pending project config changes.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.13
 */
class UpController extends Controller
{
    /**
     * @var bool Whether to perform the action even if a mutex lock could not be acquired.
     */
    public bool $force = false;

    /**
     * @inheritdoc
     */
    public bool $isolated = true;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        return array_merge(parent::options($actionID), [
            'force',
        ]);
    }

    /**
     * Runs pending migrations and applies pending project config changes.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        try {
            $pendingChanges = Craft::$app->getProjectConfig()->areChangesPending();

            // Craft + plugin migrations
            if ($this->run('migrate/all', ['noContent' => true]) !== ExitCode::OK) {
                $this->stderr("\nAborting remaining tasks.\n", Console::FG_RED);
                throw new OperationAbortedException();
            }
            $this->stdout("\n");

            // Project Config
            if ($pendingChanges) {
                if ($this->run('project-config/apply') !== ExitCode::OK) {
                    throw new OperationAbortedException();
                }
                $this->stdout("\n");
            }

            // Content migrations
            if ($this->run('migrate/up', ['track' => MigrationManager::TRACK_CONTENT]) !== ExitCode::OK) {
                throw new OperationAbortedException();
            }
            $this->stdout("\n");
        } catch (Throwable $e) {
            if (!$e instanceof OperationAbortedException) {
                throw $e;
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        return ExitCode::OK;
    }
}
