<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\db\Table;
use craft\helpers\Console;
use craft\helpers\Db;
use craft\helpers\ProjectConfig;
use craft\services\Plugins;
use yii\console\ExitCode;

/**
 * Manages the Project Config.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class ProjectConfigController extends Controller
{
    /**
     * @var bool Whether every entry change should be force-applied.
     */
    public $force = false;

    /**
     * @var bool Whether to treat the loaded project config as the source of truth, instead of the YAML files.
     * @since 3.5.13
     */
    public $invert = false;

    /**
     * @inheritdoc
     */
    public function options($actionID)
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'apply':
            case 'sync':
                $options[] = 'force';
                break;
            case 'diff':
                $options[] = 'reverse';
        }

        return $options;
    }

    /**
     * See a diff of the pending project config YAML changes.
     *
     * @return int
     * @since 3.5.6
     */
    public function actionDiff(): int
    {
        $diff = ProjectConfig::diff($this->invert);

        if ($diff === '') {
            $this->stdout('No pending project config YAML changes.' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        if (!$this->isColorEnabled()) {
            $this->stdout($diff . PHP_EOL . PHP_EOL);
            return ExitCode::OK;
        }

        foreach (explode("\n", $diff) as $line) {
            $firstChar = $line[0] ?? '';
            switch ($firstChar) {
                case '-':
                    $this->stdout($line . PHP_EOL, Console::FG_RED);
                    break;
                case '+':
                    $this->stdout($line . PHP_EOL, Console::FG_GREEN);
                    break;
                default:
                    $this->stdout($line . PHP_EOL);
                    break;
            }
        }

        $this->stdout(PHP_EOL);
        return ExitCode::OK;
    }

    /**
     * Applies project config file changes.
     *
     * @return int
     */
    public function actionApply(): int
    {
        $updatesService = Craft::$app->getUpdates();

        if ($updatesService->getIsCraftDbMigrationNeeded() || $updatesService->getIsPluginDbUpdateNeeded()) {
            $this->stderr('Craft has pending migrations. Please run `craft migrate/all` first.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $projectConfig = Craft::$app->getProjectConfig();

        $issues = [];
        if (!$projectConfig->getAreConfigSchemaVersionsCompatible($issues)) {
            $this->stderr("Your project config files were created for different versions of Craft and/or plugins than what’s currently installed." . PHP_EOL . PHP_EOL, Console::FG_YELLOW);

            foreach ($issues as $issue) {
                $this->stderr($issue['cause'], Console::FG_RED);
                $this->stderr(' is installed with schema version of ', Console::FG_YELLOW);
                $this->stderr($issue['existing'], Console::FG_RED);
                $this->stderr(' while ', Console::FG_YELLOW);
                $this->stderr($issue['incoming'], Console::FG_RED);
                $this->stderr(' was expected.' . PHP_EOL, Console::FG_YELLOW);
            }

            $this->stderr(PHP_EOL . 'Try running `composer install` from your terminal to resolve.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        // Do we need to create a new config file?
        if (!$projectConfig->getDoesYamlExist()) {
            $this->stdout("No project config files found. Generating them from internal config ... ", Console::FG_YELLOW);
            $projectConfig->regenerateYamlFromConfig();
        } else {
            // Any plugins need to be installed/uninstalled?
            $loadedConfigPlugins = array_keys($projectConfig->get(Plugins::CONFIG_PLUGINS_KEY) ?? []);
            $yamlPlugins = array_keys($projectConfig->get(Plugins::CONFIG_PLUGINS_KEY, true) ?? []);
            $this->_uninstallPlugins(array_diff($loadedConfigPlugins, $yamlPlugins));

            if (!$this->_installPlugins(array_diff($yamlPlugins, $loadedConfigPlugins))) {
                $this->stdout('Aborting config apply process' . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout("Applying changes from your project config files ... ", Console::FG_YELLOW);
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
     * Alias for `apply`.
     *
     * @return int
     * @deprecated in 3.5.0. Use [[actionApply()]] instead.
     */
    public function actionSync(): int
    {
        $this->stderr('project-config/sync has been renamed to project-config/apply. Running that instead...' . PHP_EOL, Console::FG_RED);
        return $this->runAction('apply');
    }

    /**
     * Writes out the current project config as YAML files to the `config/project/` folder, discarding any pending YAML changes.
     *
     * @return int
     * @since 3.5.13
     */
    public function actionWrite(): int
    {
        $this->stdout('Writing out project config files ... ');
        Craft::$app->getProjectConfig()->regenerateYamlFromConfig();
        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Rebuilds the project config.
     *
     * @return int
     * @since 3.1.20
     */
    public function actionRebuild(): int
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if ($projectConfig->writeYamlAutomatically && !$projectConfig->getDoesYamlExist()) {
            $this->stdout("No project config files found. Generating them from internal config ... ", Console::FG_YELLOW);
            $projectConfig->regenerateYamlFromConfig();
        }

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
     * Updates the `dateModified` value in `config/project/project.yaml`, attempting to resolve a Git conflict for it.
     *
     * @return int
     */
    public function actionTouch(): int
    {
        $time = time();
        ProjectConfig::touch($time);
        $this->stdout("The dateModified value in project.yaml is now set to $time." . PHP_EOL, Console::FG_GREEN);
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
                Db::delete(Table::PLUGINS, [
                    'handle' => $handle,
                ]);
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
}
