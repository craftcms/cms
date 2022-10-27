<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use Throwable;
use yii\console\ExitCode;

/**
 * Manages plugins.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class PluginController extends Controller
{
    /**
     * @var bool Whether the plugin uninstallation should be forced.
     * @since 3.6.14
     */
    public bool $force = false;

    /**
     * @var bool Whether install/uninstall/enable/disable should be run on all plugins.
     * @since 4.4.0
     */
    public bool $all = false;

    /**
     * @inheritdoc
     */
    public $defaultAction = 'list';

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);

        switch ($actionID) {
            case 'install':
            case 'enable':
            case 'disable':
                $options[] = 'all';
            case 'uninstall':
                $options[] = 'force';
                $options[] = 'all';
                break;
        }

        return $options;
    }

    /**
     * @inheritdoc
     */
    public function beforeAction($action): bool
    {
        Console::ensureProjectConfigFileExists();
        return parent::beforeAction($action);
    }

    /**
     * Lists all plugins.
     *
     * @return int
     * @since 3.7.31
     */
    public function actionList(): int
    {
        $pluginInfo = Craft::$app->getPlugins()->getAllPluginInfo();
        ArrayHelper::multisort($pluginInfo, ['isEnabled', 'isInstalled'], [SORT_DESC, SORT_DESC]);
        $tableData = [];

        foreach ($pluginInfo as $handle => $info) {
            $row = [
                $info['name'],
                $handle,
                $info['packageName'],
                $info['version'],
                $this->_boolToString($info['isInstalled']),
                $this->_boolToString($info['isEnabled']),
            ];

            if ($info['isEnabled']) {
                $color = Console::FG_GREEN;
            } elseif ($info['isInstalled']) {
                $color = Console::FG_YELLOW;
            } else {
                $color = Console::FG_GREY;
            }

            $tableData[] = array_map(function($value) use ($color) {
                return [$value, 'format' => [$color]];
            }, $row);
        }

        $this->stdout(PHP_EOL);
        $this->table(['Name', 'Handle', 'Package Name', 'Version', 'Installed', 'Enabled'], $tableData);
        $this->stdout(PHP_EOL);

        return ExitCode::OK;
    }

    /**
     * Installs a plugin.
     *
     * @param string|null $handle The plugin handle.
     * @return int
     */
    public function actionInstall(?string $handle = null): int
    {
        // if we're supposed to install all plugins
        if ($this->all === true) {
            // get all plugins info
            $pluginInfo = Craft::$app->getPlugins()->getAllPluginInfo();

            // get handles for the ones that are not installed
            $handles = array_filter($pluginInfo, function(array $info) {
                return !$info['isInstalled'];
            });

            // if there aren't any uninstalled plugins to install - message and exit
            if (empty($handles)) {
                $this->stdout('There aren’t any uninstalled plugins present.' . PHP_EOL);
                return ExitCode::OK;
            }

            // install them one by one
            foreach ($handles as $pluginHandle => $info) {
                $this->_installPluginByHandle($pluginHandle);
            }
        } else {
            // otherwise do what we were doing before - install plugin by handle
            $this->_installPluginByHandle($handle);
        }

        return ExitCode::OK;
    }

    /**
     * Uninstalls a plugin.
     *
     * @param string|null $handle The plugin handle.
     * @return int
     */
    public function actionUninstall(?string $handle = null): int
    {
        // if we're supposed to uninstall all plugins
        if ($this->all === true) {
            // get all plugins info
            $pluginInfo = Craft::$app->getPlugins()->getAllPluginInfo();

            // get handles for the ones that are installed and enabled
            $handles = array_filter($pluginInfo, function(array $info) {
                return $info['isInstalled'] && $info['isEnabled'];
            });

            // if there aren't any installed & enabled plugins to uninstall - message and exit
            if (empty($handles)) {
                $this->stdout('There aren’t any installed and enabled plugins.' . PHP_EOL);
                return ExitCode::OK;
            }

            // uninstall them one by one
            foreach ($handles as $pluginHandle => $info) {
                $this->_uninstallPluginByHandle($pluginHandle);
            }
        } else {
            // otherwise do what we were doing before - uninstall plugin by handle
            $this->_uninstallPluginByHandle($handle);
        }

        return ExitCode::OK;
    }

    /**
     * Enables a plugin.
     *
     * @param string|null $handle The plugin handle.
     * @return int
     */
    public function actionEnable(?string $handle = null): int
    {
        // if we're supposed to enable all plugins
        if ($this->all === true) {
            // get all plugins info
            $pluginInfo = Craft::$app->getPlugins()->getAllPluginInfo();

            // get handles for the ones that are installed and disabled
            $handles = array_filter($pluginInfo, function(array $info) {
                return $info['isInstalled'] && !$info['isEnabled'];
            });

            // if there aren't any installed & disabled plugins to enable - message and exit
            if (empty($handles)) {
                $this->stdout('There aren’t any disabled plugins.' . PHP_EOL);
                return ExitCode::OK;
            }

            // enable them one by one
            foreach ($handles as $pluginHandle => $info) {
                $this->_enablePluginByHandle($pluginHandle);
            }
        } else {
            // otherwise do what we were doing before - enable plugin by handle
            $this->_enablePluginByHandle($handle);
        }

        return ExitCode::OK;
    }

    /**
     * Disables a plugin.
     *
     * @param string|null $handle The plugin handle.
     * @return int
     */
    public function actionDisable(?string $handle = null): int
    {
        // if we're supposed to disable all plugins
        if ($this->all === true) {
            // get all plugins info
            $pluginInfo = Craft::$app->getPlugins()->getAllPluginInfo();

            // get handles for the ones that are installed and enabled
            $handles = array_filter($pluginInfo, function(array $info) {
                return $info['isInstalled'] && $info['isEnabled'];
            });

            // if there aren't any installed & enabled plugins to disable - message and exit
            if (empty($handles)) {
                $this->stdout('There aren’t any enabled plugins.' . PHP_EOL);
                return ExitCode::OK;
            }

            // disable them one by one
            foreach ($handles as $pluginHandle => $info) {
                $this->_disablePluginByHandle($pluginHandle);
            }
        } else {
            // otherwise do what we were doing before - disable plugin by handle
            $this->_disablePluginByHandle($handle);
        }

        return ExitCode::OK;
    }

    /**
     * Process installing plugin by handle
     *
     * @param null|string $handle
     * @return int
     */
    private function _installPluginByHandle(?string $handle = null): int
    {
        if ($handle === null) {
            $handle = $this->_pluginPrompt(
                'The following uninstalled plugins are present:',
                'There aren’t any uninstalled plugins present.',
                'Choose a plugin handle to install:',
                function(array $info) {
                    return !$info['isInstalled'];
                }
            );
            if (is_int($handle)) {
                return $handle;
            }
        }

        $this->stdout("*** installing $handle" . PHP_EOL, Console::FG_YELLOW);
        $start = microtime(true);

        try {
            $success = Craft::$app->getPlugins()->installPlugin($handle);
        } catch (Throwable $e) {
            $success = false;
        } finally {
            if (!$success) {
                $this->stderr("*** failed to install $handle" . (isset($e) ? ": {$e->getMessage()}" : '.') . PHP_EOL . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $time = sprintf('%.3f', microtime(true) - $start);
        $this->stdout("*** installed $handle successfully (time: {$time}s)" . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Process uninstalling plugin by handle
     *
     * @param string $handle
     * @return int
     */
    private function _uninstallPluginByHandle(?string $handle = null): int
    {
        if ($handle === null) {
            $handle = $this->_pluginPrompt(
                'The following plugins plugins are installed and enabled:',
                'There aren’t any installed and enabled plugins.',
                'Choose a plugin handle to uninstall:',
                function(array $info) {
                    return $info['isInstalled'] && $info['isEnabled'];
                }
            );
            if (is_int($handle)) {
                return $handle;
            }
        }

        $this->stdout("*** uninstalling $handle" . PHP_EOL, Console::FG_YELLOW);
        $start = microtime(true);

        try {
            $success = Craft::$app->plugins->uninstallPlugin($handle, $this->force);
        } catch (Throwable $e) {
            $success = false;
        } finally {
            if (!$success) {
                $this->stderr("*** failed to uninstall $handle" . (isset($e) ? ": {$e->getMessage()}" : '.') . PHP_EOL, Console::FG_RED);
                if (!$this->force) {
                    $this->stderr('Try again with --force.' . PHP_EOL);
                }
                $this->stderr(PHP_EOL);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $time = sprintf('%.3f', microtime(true) - $start);
        $this->stdout("*** uninstalled $handle successfully (time: {$time}s)" . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Process enabling plugin by handle
     *
     * @param string $handle
     * @return int
     */
    private function _enablePluginByHandle(?string $handle = null): int
    {
        if ($handle === null) {
            $handle = $this->_pluginPrompt(
                'The following plugins are disabled:',
                'There aren’t any disabled plugins.',
                'Choose a plugin handle to enable:',
                function(array $info) {
                    return $info['isInstalled'] && !$info['isEnabled'];
                }
            );
            if (is_int($handle)) {
                return $handle;
            }
        }

        $this->stdout("*** enabling $handle" . PHP_EOL, Console::FG_YELLOW);
        $start = microtime(true);

        try {
            $success = Craft::$app->plugins->enablePlugin($handle);
        } catch (Throwable $e) {
            $success = false;
        } finally {
            if (!$success) {
                $this->stderr("*** failed to enable $handle" . (isset($e) ? ": {$e->getMessage()}" : '.') . PHP_EOL . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $time = sprintf('%.3f', microtime(true) - $start);
        $this->stdout("*** enabled $handle successfully (time: {$time}s)" . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * Process disabling plugin by handle
     *
     * @param string|null $handle
     * @return int
     */
    private function _disablePluginByHandle(?string $handle = null): int
    {
        if ($handle === null) {
            $handle = $this->_pluginPrompt(
                'The following plugins are enabled:',
                'There aren’t any enabled plugins.',
                'Choose a plugin handle to disable:',
                function(array $info) {
                    return $info['isInstalled'] && $info['isEnabled'];
                }
            );
            if (is_int($handle)) {
                return $handle;
            }
        }

        $this->stdout("*** disabling $handle" . PHP_EOL, Console::FG_YELLOW);
        $start = microtime(true);

        try {
            $success = Craft::$app->plugins->disablePlugin($handle);
        } catch (Throwable $e) {
            $success = false;
        } finally {
            if (!$success) {
                $this->stderr("*** failed to disable $handle" . (isset($e) ? ": {$e->getMessage()}" : '.') . PHP_EOL . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }
        }

        $time = sprintf('%.3f', microtime(true) - $start);
        $this->stdout("*** disabled $handle successfully (time: {$time}s)" . PHP_EOL . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }

    /**
     * @param string $tableMessage
     * @param string $noPlugins
     * @param string $prompt
     * @param callable|null $filterCallback
     * @return string|int
     */
    private function _pluginPrompt(string $tableMessage, string $noPlugins, string $prompt, ?callable $filterCallback): int|string
    {
        if (!$this->interactive) {
            $this->stderr('A plugin handle must be specified.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $pluginInfo = Craft::$app->getPlugins()->getAllPluginInfo();
        if ($filterCallback) {
            $pluginInfo = array_filter($pluginInfo, $filterCallback);
        }

        foreach ($pluginInfo as $handle => $info) {
            $uninstalledPluginInfo[$handle] = [
                [$handle, 'format' => [Console::FG_YELLOW]],
                $info['name'],
                $info['version'],
            ];
        }

        if (empty($uninstalledPluginInfo)) {
            $this->stdout($noPlugins . PHP_EOL);
            return ExitCode::OK;
        }

        $this->stdout($tableMessage . PHP_EOL . PHP_EOL);
        $this->table(['Handle', 'Name', 'Version'], $uninstalledPluginInfo);
        $this->stdout(PHP_EOL);

        return $this->prompt($prompt, [
            'validator' => function(string $input) use ($uninstalledPluginInfo) {
                return isset($uninstalledPluginInfo[$input]);
            },
        ]);
    }

    /**
     * @param bool $value
     * @return string
     */
    private function _boolToString(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }
}
