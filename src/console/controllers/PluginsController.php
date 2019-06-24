<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\base\Plugin;
use craft\console\Controller;
use craft\errors\InvalidPluginException;
use craft\helpers\ArrayHelper;
use craft\helpers\Console;
use craft\helpers\StringHelper;
use craft\services\Plugins;
use yii\console\ExitCode;

/**
 * The PluginsController allows management of plugins through the CLI.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class PluginsController extends Controller
{
    // Public functions
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $defaultAction = 'view';

    /**
     * @param string|null $pluginHandle
     * @return int
     * @throws InvalidPluginException
     */
    public function actionView(string $pluginHandle = null) : int
    {
        $plugins = $this->_assemblePlugins();

        // Single plugin display
        if ($pluginHandle) {
            $plugin = Craft::$app->getPlugins()->getComposerPluginInfo($pluginHandle);
            if (!$plugin) {
                $this->stderr('No plugin exists by that handle.'.PHP_EOL);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout('Plugin with name: '.$plugin['name'].''.PHP_EOL.PHP_EOL);

            foreach ($plugin as $propName => $value) {
                if (is_array($value)) {
                    $value = implode(', ', $value);
                }

                $this->stdout("$propName: $value" . PHP_EOL);
            }

            $isInstalled = Craft::$app->getPlugins()->isPluginInstalled($pluginHandle) ? 'Yes': 'No';
            $this->stdout("Is installed: $isInstalled".PHP_EOL);
            $this->stdout(PHP_EOL);

            return ExitCode::OK;
        }

        $this->stdout('We are able to detect the following plugins: '.PHP_EOL);

        if (!$plugins) {
            $this->stdout('No plugins detected'.PHP_EOL);
            return ExitCode::OK;
        }

        /* @var Plugin $plugin */
        foreach ($plugins as $plugin) {
            $pluginName = $plugin->name;
            $handle = $plugin->getHandle();
            $version = $plugin->getVersion();

            $result = $this->stdout(
                "$pluginName - $handle - $version" . PHP_EOL
            );

            if ($result) {
                $actionablePlugins[] = $handle;
            }
        }

        return ExitCode::OK;
    }

    /**
     * Manage plugins and perform various Craft related actions on them including:
     * Uninstalling them
     * Installing them
     * Disabling them
     * Enabling them
     *
     * @return int
     * @throws InvalidPluginException
     */
    public function actionManage() : int
    {
        $pluginsService = Craft::$app->getPlugins();

        if ($actionablePlugins = $this->prompt(
            'Enter plugins seperated by comma. Enter nothing if you want a list of plugins and the option to select from that list.')
            ) {
            $actionablePlugins = StringHelper::split($actionablePlugins, ',');

            // Hmmm.
            if (!$actionablePlugins) {
                $this->stderr('Invalid plugin input. Please try again'.PHP_EOL);
                return ExitCode::OK;
            }
        }

        // Show them a list...
        if (!$actionablePlugins) {
            $actionablePlugins = [];
            $this->prompt('Which plugin(s) do you want to manage?');

            $plugins = $this->_assemblePlugins();

            /* @var Plugin $plugin */
            foreach ($plugins as $plugin) {
                $pluginName = $plugin->name;
                $handle = $plugin->getHandle();
                $version = $plugin->getVersion();

                $result = $this->select(
                    "$pluginName - $handle - $version" . PHP_EOL,
                    ['Yes' => 'Yes', 'No' => 'No']
                );

                if ($result) {
                    $actionablePlugins[] = $handle;
                }
            }
        }

        foreach ($actionablePlugins as $actionablePlugin) {
            $isInstalled = $pluginsService->isPluginInstalled($actionablePlugin);

            // Hmmm.
            if (!$isInstalled) {
                $this->stderr("$actionablePlugin is not installed. Cancelling.");
                return ExitCode::OK;
            }

            // Ensure we know what they want.
            $action = $this->select("What do you want to do to $actionablePlugin?", [
                'Uninstall' => 'Uninstall',
                'Install' => 'Install',
                'Disable' => 'Disable',
                'Enable' => 'Enable',
            ]);

            try {
                switch ($action) {
                    case 'Uninstall':
                        if (!$pluginsService->uninstallPlugin($actionablePlugin)) {
                            return $this->_handleFailedPluginAction($actionablePlugin, $action);
                        }
                        break;
                    case 'Install':
                        $edition = $this->prompt('Which edition must the plugin be installed?');
                        if (!$pluginsService->installPlugin($actionablePlugin, $edition)) {
                            return $this->_handleFailedPluginAction($actionablePlugin, $action);
                        }
                        break;
                    case 'Disable':
                        if (!$pluginsService->disablePlugin($actionablePlugin)) {
                            return $this->_handleFailedPluginAction($actionablePlugin, $action);
                        }
                        break;
                    case 'Enable':
                        if (!$pluginsService->enablePlugin($actionablePlugin)) {
                            return $this->_handleFailedPluginAction($actionablePlugin, $action);
                        }
                        break;
                }
            } catch (\Throwable $exception) {
                return $this->_handleFailedPluginAction($actionablePlugin, $action, $exception->getMessage());
            }
        }

        $this->prompt('All actions processed successfully.');
        return ExitCode::OK;
    }


    // Protected functions
    // =========================================================================

    /**
     * @return array
     * @throws \craft\errors\InvalidPluginException
     */
    protected function _assemblePlugins() : array
    {
        $pluginsService = Craft::$app->getPlugins();
        $plugins = [];
        $composerInfo = Craft::$app->getPlugins()->getComposerPluginInfo();

        foreach ($composerInfo as $handle => $composerPlugin) {
            if ($pluginsService->isPluginInstalled($handle)) {
                $plugins[] = $pluginsService->getPlugin($handle);
            } else {
                $plugins[] = $pluginsService->createPlugin($handle);
            }
        }

        return $plugins;
    }

    /**
     * @param $plugin
     * @param string $action
     * @return int
     */
    protected function _handleFailedPluginAction($plugin, string $action, string $exceptionMessage = null) : int
    {
        $this->stderr("We were unable to $action $plugin" . PHP_EOL);
        if ($exceptionMessage) {
            $this->stderr("Additionally an exception was thrown: $exceptionMessage");
        }

        return ExitCode::OK;
    }
}
