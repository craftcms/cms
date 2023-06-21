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
            $res = $this->run('migrate/all', ['noContent' => true]);
            if ($res !== ExitCode::OK) {
                $this->stderr("\nAborting remaining tasks.\n", Console::FG_YELLOW);
                return $res;
            }
            $this->stdout("\n");

            // Project Config
            if ($pendingChanges) {
                $res = $this->run('project-config/apply');
                if ($res !== ExitCode::OK) {
                    return $res;
                }
                $this->stdout("\n");
            }

            // Content migration
            $res = $this->run('migrate/up', ['track' => MigrationManager::TRACK_CONTENT]);
            if ($res !== ExitCode::OK) {
                return $res;
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
