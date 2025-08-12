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
     * @var bool Skip backing up the database.
     * @since 4.5.8
     */
    public bool $noBackup = false;

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
            'noBackup',
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
            $projectConfig = Craft::$app->getProjectConfig();
            $pendingChanges = $projectConfig->areChangesPending(force: true);
            $writeYamlAutomatically = $projectConfig->writeYamlAutomatically;

            // Craft + plugin migrations
            $res = $this->run('migrate/all', [
                'noContent' => true,
                'noBackup' => $this->noBackup,
            ]);
            if ($res !== ExitCode::OK) {
                $this->stderr("\nAborting remaining tasks.\n", Console::FG_YELLOW);
                return $res;
            }
            $this->stdout("\n");

            // Save and reset the project config
            $projectConfig->saveModifiedConfigData();
            $projectConfig->reset();

            // Project Config
            if ($pendingChanges) {
                $res = $this->run('project-config/apply');
                if ($res !== ExitCode::OK) {
                    return $res;
                }
                $this->stdout("\n");

                $projectConfig->flush();
                $projectConfig->reset();
            }

            // Content migration
            $res = $this->run('migrate/up', [
                'track' => MigrationManager::TRACK_CONTENT,
            ]);
            if ($res !== ExitCode::OK) {
                return $res;
            }
            $this->stdout("\n");

            $this->stdout('Updating license info ... ');
            Craft::$app->getUpdates()->getUpdates(true);
            $this->stdout("done\n", Console::FG_GREEN);
        } catch (Throwable $e) {
            if (!$e instanceof OperationAbortedException) {
                throw $e;
            }
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($writeYamlAutomatically && !$projectConfig->readOnly) {
            $projectConfig->writeYamlFiles(true);
        }

        return ExitCode::OK;
    }
}
