<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\events\ConfigEvent;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use craft\helpers\ProjectConfig;
use craft\services\ProjectConfig as ProjectConfigService;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Throwable;
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
    public bool $force = false;

    /**
     * @var bool Whether to reduce the command output.
     * @since 4.4.0
     */
    public bool $quiet = false;

    /**
     * @var bool Whether to treat the loaded project config as the source of truth, instead of the YAML files.
     * @since 3.5.13
     */
    public bool $invert = false;

    /**
     * @var bool Whether to pull values from the project config YAML files instead of the loaded config.
     * @since 4.1.0
     */
    public bool $external = false;

    /**
     * @var string|null A message describing the changes.
     * @see \craft\services\ProjectConfig::set()
     * @since 4.1.0
     */
    public ?string $message = null;

    /**
     * @var bool Whether the `dateModified` value should be updated
     * @see \craft\services\ProjectConfig::set()
     * @since 4.1.0
     */
    public bool $updateTimestamp = false;

    /**
     * @var bool Whether to overwrite an existing export file, if a specific file path is given.
     * @since 4.2.1
     */
    public bool $overwrite = false;

    /**
     * @var int Counter of the total paths that have been processed.
     */
    private int $_pathCount = 0;

    /**
     * @var array The config paths that are currently being processed.
     */
    private array $_processingPaths;

    /**
     * @var array The config paths that have finished being processed.
     */
    private array $_completedPaths = [];

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'apply':
            case 'sync':
                $options[] = 'force';
                $options[] = 'quiet';
                break;
            case 'diff':
                $options[] = 'invert';
                break;
            case 'get':
                $options[] = 'external';
                break;
            case 'set':
                $options[] = 'message';
                $options[] = 'updateTimestamp';
                $options[] = 'force';
                break;
            case 'export':
                $options[] = 'external';
                $options[] = 'overwrite';
                break;
        }

        return $options;
    }

    /**
     * Outputs a project config value.
     *
     * Example:
     * ```
     * php craft project-config/get system.edition
     * ```
     *
     * @param string $path The config item path
     * @return int
     * @since 4.1.0
     */
    public function actionGet(string $path): int
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $value = $projectConfig->get($path, $this->external);
        $this->stdout(Yaml::dump($value));
        $this->stdout(PHP_EOL);
        return ExitCode::OK;
    }

    /**
     * Sets a project config value.
     *
     * Example:
     * ```
     * php craft project-config/set system.edition pro
     * ```
     *
     * @param string $path The config item path
     * @param string $value The config item value as a valid YAML string
     * @return int
     * @since 4.1.0
     */
    public function actionSet(string $path, string $value): int
    {
        try {
            $parsedValue = Yaml::parse($value);
        } catch (ParseException $e) {
            $this->stderr('Input value must be valid YAML.' . PHP_EOL, Console::FG_RED);
            return ExitCode::USAGE;
        }

        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->set(
            $path,
            $parsedValue,
            $this->message,
            $this->updateTimestamp,
            $this->force,
        );

        $value = $projectConfig->get($path);
        $dumpedValue = Yaml::dump($value);
        $multiline = str_contains($dumpedValue, PHP_EOL);

        $this->stdout('Project config path ');
        $this->stdout($path, Console::FG_CYAN);
        $this->stdout(' has been ');
        if ($value === null) {
            $this->stdout('removed', Console::FG_BLUE);
        } else {
            $this->stdout('set to' . ($multiline ? ':' . PHP_EOL : ' '));
            $this->stdout($dumpedValue, Console::FG_BLUE);
        }
        $this->stdout(($multiline ? '' : '.') . PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Removes a project config value.
     *
     * Example:
     * ```
     * php craft project-config/set system.edition pro
     * ```
     *
     * @param string $path The config item path
     * @return int
     * @since 4.1.0
     */
    public function actionRemove(string $path): int
    {
        return $this->runAction('set', [$path, 'null']);
    }

    /**
     * Outputs a diff of the pending project config YAML changes.
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

        if ($updatesService->getIsCraftUpdatePending() || $updatesService->getIsPluginUpdatePending()) {
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
        if (!$projectConfig->getDoesExternalConfigExist()) {
            $this->stdout("No project config files found. Generating them from internal config ... ", Console::FG_YELLOW);
            $projectConfig->regenerateExternalConfig();
        } else {
            // Any plugins need to be installed/uninstalled?
            $loadedConfigPlugins = array_keys($projectConfig->get(ProjectConfigService::PATH_PLUGINS) ?? []);
            $yamlPlugins = array_keys($projectConfig->get(ProjectConfigService::PATH_PLUGINS, true) ?? []);

            if (!$this->_installPlugins(array_diff($yamlPlugins, $loadedConfigPlugins))) {
                $this->stdout('Aborting config apply process' . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->_uninstallPlugins(array_diff($loadedConfigPlugins, $yamlPlugins));

            $this->stdout('Applying changes from your project config files ...');

            try {
                $forceUpdate = $projectConfig->forceUpdate;
                $projectConfig->forceUpdate = $this->force;

                if (!$this->quiet) {
                    $this->_processingPaths = [];
                    $projectConfig->on(ProjectConfigService::EVENT_ADD_ITEM, [$this, 'onStartProcessingItem'], ['label' => 'adding'], false);
                    $projectConfig->on(ProjectConfigService::EVENT_ADD_ITEM, [$this, 'onFinishProcessingItem'], ['label' => 'adding'], true);
                    $projectConfig->on(ProjectConfigService::EVENT_REMOVE_ITEM, [$this, 'onStartProcessingItem'], ['label' => 'removing'], false);
                    $projectConfig->on(ProjectConfigService::EVENT_REMOVE_ITEM, [$this, 'onFinishProcessingItem'], ['label' => 'removing'], true);
                    $projectConfig->on(ProjectConfigService::EVENT_UPDATE_ITEM, [$this, 'onStartProcessingItem'], ['label' => 'updating'], false);
                    $projectConfig->on(ProjectConfigService::EVENT_UPDATE_ITEM, [$this, 'onFinishProcessingItem'], ['label' => 'updating'], true);
                }

                $projectConfig->applyExternalChanges();

                $projectConfig->forceUpdate = $forceUpdate;
            } catch (Throwable $e) {
                $this->stderr("\nerror: " . $e->getMessage() . PHP_EOL, Console::FG_RED);
                Craft::$app->getErrorHandler()->logException($e);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $this->stdout("\nFinished applying changes\n", Console::FG_GREEN);

        $projectConfig->off(ProjectConfigService::EVENT_ADD_ITEM, [$this, 'onStartProcessingItem']);
        $projectConfig->off(ProjectConfigService::EVENT_ADD_ITEM, [$this, 'onFinishProcessingItem']);
        $projectConfig->off(ProjectConfigService::EVENT_REMOVE_ITEM, [$this, 'onStartProcessingItem']);
        $projectConfig->off(ProjectConfigService::EVENT_REMOVE_ITEM, [$this, 'onFinishProcessingItem']);
        $projectConfig->off(ProjectConfigService::EVENT_UPDATE_ITEM, [$this, 'onStartProcessingItem']);
        $projectConfig->off(ProjectConfigService::EVENT_UPDATE_ITEM, [$this, 'onFinishProcessingItem']);

        return ExitCode::OK;
    }

    /**
     * Called when a project config item has started getting processed.
     *
     * @param ConfigEvent $event
     * @since 3.6.10
     */
    public function onStartProcessingItem(ConfigEvent $event): void
    {
        if (isset($this->_processingPaths[$event->path]) || isset($this->_completedPaths[$event->path])) {
            return;
        }

        $this->stdout("\n");

        // Are we in the middle of processing another path(s)?
        $otherPaths = count($this->_processingPaths);
        if ($otherPaths !== 0) {
            $this->stdout(str_repeat('  ', $otherPaths));
        }

        $this->stdout("- {$event->data['label']} ");
        $this->stdout($event->path, Console::FG_CYAN);
        $this->stdout(' ... ');

        $this->_processingPaths[$event->path] = ++$this->_pathCount;
    }

    /**
     * Called when a project config item has finished getting processed.
     *
     * @param ConfigEvent $event
     * @since 3.6.10
     */
    public function onFinishProcessingItem(ConfigEvent $event): void
    {
        if (!isset($this->_processingPaths[$event->path])) {
            return;
        }

        // Have any other paths been processed since this one started?
        if ($this->_processingPaths[$event->path] !== $this->_pathCount) {
            $this->stdout("\n" . str_repeat('  ', count($this->_processingPaths) - 1) . '  ');
        }

        $this->stdout('done', Console::FG_GREEN);

        unset($this->_processingPaths[$event->path]);
        $this->_completedPaths[$event->path] = true;
    }

    /**
     * DEPRECATED. Use `project-config/apply` instead.
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
     * Writes out the currently-loaded project config as YAML files to the `config/project/` folder, discarding any pending YAML changes.
     *
     * @return int
     * @since 3.5.13
     */
    public function actionWrite(): int
    {
        $this->stdout('Writing out project config files ... ');
        Craft::$app->getProjectConfig()->regenerateExternalConfig();
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

        if ($projectConfig->writeYamlAutomatically && !$projectConfig->getDoesExternalConfigExist()) {
            $this->stdout("No project config files found. Generating them from internal config ... ", Console::FG_YELLOW);
            $projectConfig->regenerateExternalConfig();
        }

        $this->stdout('Rebuilding the project config from the current state ... ', Console::FG_YELLOW);

        try {
            $projectConfig->rebuild();
        } catch (Throwable $e) {
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
     * Exports the entire project config to a single file.
     *
     * @param string|null $path The path the project config should be exported to.
     * Can be any of the following:
     *
     * - A full file path
     * - A folder path (export will be saved in there with a dynamically-generated name)
     * - A filename (export will be saved in the working directory with the given name)
     * - Blank (export will be saved in the working directly with a dynamically-generated name)
     *
     * @since 4.2.1
     */
    public function actionExport(?string $path = null): int
    {
        if ($path !== null) {
            // Prefix with the working directory if a relative path or no path is given
            if (str_starts_with($path, '.') || !str_contains(FileHelper::normalizePath($path, '/'), '/')) {
                $path = getcwd() . DIRECTORY_SEPARATOR . $path;
            }

            $path = FileHelper::normalizePath($path);
        } else {
            $path = getcwd();
        }

        if (is_dir($path)) {
            $i = 0;
            do {
                $testPath = $path . DIRECTORY_SEPARATOR . 'project-config-' . ($this->external ? 'external' : 'internal') . '--' . date('Y-m-d') . ($i ? "--$i" : '') . '.yaml';
                $i++;
            } while (file_exists($testPath));
            $path = $testPath;
        } elseif (is_file($path)) {
            if (!$this->overwrite) {
                if (!$this->interactive) {
                    $this->stderr("$path already exists. Retry with the --overwrite flag to overwrite it." . PHP_EOL, Console::FG_RED);
                    return ExitCode::UNSPECIFIED_ERROR;
                }
                if (!$this->confirm("$path already exists. Overwrite?")) {
                    $this->stdout('Aborting' . PHP_EOL);
                    return ExitCode::OK;
                }
            }
            unlink($path);
        }

        $this->stdout('Exporting the ' . ($this->external ? 'external' : 'loaded') . ' project config data ... ');

        $config = Craft::$app->getProjectConfig()->get(null, $this->external);
        $content = Yaml::dump(ProjectConfig::cleanupConfig($config), 20, 2);
        FileHelper::writeToFile($path, $content);

        $this->stdout("done\n", Console::FG_GREEN);
        $size = Craft::$app->getFormatter()->asShortSize(filesize($path));
        $this->stdout('Exported to: ');
        $this->stdout($path, Console::FG_CYAN);
        $this->stdout(" ($size)\n");
        return ExitCode::OK;
    }

    /**
     * Uninstalls plugins.
     *
     * @param string[] $handles
     */
    private function _uninstallPlugins(array $handles): void
    {
        $pluginsService = Craft::$app->getPlugins();

        foreach ($handles as $handle) {
            $this->stdout('Uninstalling plugin ', Console::FG_YELLOW);
            $this->stdout("\"$handle\"", Console::FG_CYAN);
            $this->stdout(' ... ', Console::FG_YELLOW);

            ob_start();
            $pluginsService->uninstallPlugin($handle, true);
            ob_end_clean();

            $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
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
            $this->stdout("\"$handle\"", Console::FG_CYAN);
            $this->stdout(' ... ', Console::FG_YELLOW);

            ob_start();

            try {
                $pluginsService->installPlugin($handle);
                ob_end_clean();
                $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
            } catch (Throwable $e) {
                ob_end_clean();
                $this->stdout('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
                Craft::$app->getErrorHandler()->logException($e);
                return false;
            }
        }

        return true;
    }
}
