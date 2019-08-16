<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\db\Table;
use craft\helpers\Console;
use craft\services\Plugins;
use craft\console\Controller;
use yii\console\ExitCode;

/**
 * Manages the Project Config.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class ProjectConfigController extends Controller
{
    /**
     * @var bool Whether every entry change should be force-synced.
     */
    public $force = false;

    /**
     * Syncs the project config.
     *
     * @return int
     */
    public function actionSync(): int
    {
        if (!Craft::$app->getConfig()->getGeneral()->useProjectConfigFile) {
            $this->stdout('Craft is not configured to use project.yaml. Please enable the \'useProjectConfigFile\' config setting in config/general.php.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $updatesService = Craft::$app->getUpdates();

        if ($updatesService->getIsCraftDbMigrationNeeded() || $updatesService->getIsPluginDbUpdateNeeded()) {
            $this->stderr('Craft has pending migrations. Please run `craft migrate/all` first.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        $issues = [];
        if (!$projectConfig->getAreConfigSchemaVersionsCompatible($issues)) {
            $this->stderr('Your `project.yaml` file was created for different versions of Craft and/or plugins than what’s currently installed.' . PHP_EOL . PHP_EOL, Console::FG_YELLOW);

            foreach ($issues as $issue) {
                $this->stderr($issue['cause'], Console::FG_RED);
                $this->stderr(' is installed with schema version of ', Console::FG_YELLOW);
                $this->stderr($issue['existing'], Console::FG_RED);
                $this->stderr(' while ', Console::FG_YELLOW);
                $this->stderr($issue['incoming'], Console::FG_RED);
                $this->stderr(' was expected.' . PHP_EOL, Console::FG_YELLOW);
            }

            $this->stderr(PHP_EOL . 'Try running `composer install` from your terminal to resolve.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        // Do we need to create a new config file?
        if (!file_exists(Craft::$app->getPath()->getProjectConfigFilePath())) {
            $this->stdout('No project.yaml file found. Generating one from internal config ... ', Console::FG_YELLOW);
            $projectConfig->regenerateYamlFromConfig();
        } else {
            // Any plugins need to be installed/uninstalled?
            $loadedConfigPlugins = array_keys($projectConfig->get(Plugins::CONFIG_PLUGINS_KEY) ?? []);
            $yamlPlugins = array_keys($projectConfig->get(Plugins::CONFIG_PLUGINS_KEY, true) ?? []);
            $this->_uninstallPlugins(array_diff($loadedConfigPlugins, $yamlPlugins));

            if (!$this->_installPlugins(array_diff($yamlPlugins, $loadedConfigPlugins))) {
                $this->stdout('Aborting config sync' . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout('Applying changes from project.yaml ... ', Console::FG_YELLOW);
            try {
                $forceUpdate = $projectConfig->forceUpdate;
                $projectConfig->forceUpdate = $this->force;
                $projectConfig->applyYamlChanges();
                $projectConfig->forceUpdate = $forceUpdate;
            } catch (\Throwable $e) {
                $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                Craft::$app->getErrorHandler()->logException($e);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Rebuilds the project config.
     *
     * @return int
     */
    public function actionRebuild(): int
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $this->stdout('Rebuilding the project config from the current state ... ', Console::FG_YELLOW);

        try {
            $projectConfig->rebuild();
        } catch (\Throwable $e) {
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            Craft::$app->getErrorHandler()->logException($e);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Uninstalls plugins.
     *
     * @param string[] $handles
     */
    private function _uninstallPlugins(array $handles)
    {
        $pluginsService = Craft::$app->getPlugins();

        foreach ($handles as $handle) {
            $this->stdout('Uninstalling plugin ', Console::FG_YELLOW);
            $this->stdout("\"{$handle}\"", Console::FG_CYAN);
            $this->stdout(' ... ', Console::FG_YELLOW);

            ob_start();

            try {
                $pluginsService->uninstallPlugin($handle);
                ob_end_clean();
                $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
            } catch (\Throwable $e) {
                ob_end_clean();
                $this->stdout('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                Craft::$app->getErrorHandler()->logException($e);

                // Just remove the row
                Craft::$app->getDb()->createCommand()
                    ->delete(Table::PLUGINS, ['handle' => $handle])
                    ->execute();
            }
        }
    }

    /**
     * Installs plugins.
     *
     * @param string[] $handles
     * @return bool
     */
    private function _installPlugins(array $handles): bool
    {
        $pluginsService = Craft::$app->getPlugins();

        foreach ($handles as $handle) {
            $this->stdout('Installing plugin ', Console::FG_YELLOW);
            $this->stdout("\"{$handle}\"", Console::FG_CYAN);
            $this->stdout(' ... ', Console::FG_YELLOW);

            ob_start();

            try {
                $pluginsService->installPlugin($handle);
                ob_end_clean();
                $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
            } catch (\Throwable $e) {
                ob_end_clean();
                $this->stdout('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                Craft::$app->getErrorHandler()->logException($e);
                return false;
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        if ($actionID == 'sync') {
            $options[] = 'force';
        }

        return $options;
    }
}
