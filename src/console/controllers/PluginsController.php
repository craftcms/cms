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
use yii\base\InvalidArgumentException;
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
        $pluginsService = Craft::$app->getPlugins();
        $plugins = $this->_assemblePlugins();

        // Single plugin display
        if ($pluginHandle) {
            $plugin = $pluginsService->getComposerPluginInfo($pluginHandle);
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

            $isInstalled = $pluginsService->isPluginInstalled($pluginHandle) ? 'Yes': 'No';
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
        $plugins = $this->_assemblePlugins();

        if (!$plugins) {
            $this->stdout('No plugins no manage'.PHP_EOL);
            return ExitCode::OK;
        }

        if ($actionablePlugins = $this->prompt(
            'Enter plugins seperated by comma. Enter nothing if you want a list of plugins and the option to select from that list.')
            ) {
            $actionablePlugins = StringHelper::split($actionablePlugins, ',');

            // Hmmm.
            if (!$actionablePlugins) {
                $this->stderr('Invalid plugin input. Please try again'.PHP_EOL);
                return ExitCode::OK;
            }

            foreach ($actionablePlugins as $actionablePlugin) {
                if (($result = $this->_processPluginAction($actionablePlugin)) !== 0) {
                    return $result;
                }
            }
        }

        // Show them a list...
        if (!$actionablePlugins) {

            $this->stdout('Which plugin(s) do you want to manage?'.PHP_EOL.PHP_EOL);

            /* @var Plugin $plugin */
            foreach ($plugins as $plugin) {
                $pluginName = $plugin->name;
                $handle = $plugin->getHandle();
                $version = $plugin->getVersion();

                $result = $this->select(
                    "$pluginName - $handle - $version",
                    ['Yes' => 'Yes', 'No' => 'No']
                );

                if ($result === 'Yes') {
                    if (($result = $this->_processPluginAction($handle)) !== 0) {
                        return $result;
                    }
                }
            }
        }

        $this->stdout('All actions processed successfully.'.PHP_EOL);
        return ExitCode::OK;
    }


    // Protected functions
    // =========================================================================

    /**
     * @param string $actionablePluginHandle
     * @return int
     */
    protected function _processPluginAction(string $actionablePluginHandle)
    {
        $pluginsService = Craft::$app->getPlugins();

        // Ensure we know what they want.
        $action = $this->select("What do you want to do to $actionablePluginHandle?".PHP_EOL, [
            'Uninstall' => 'Uninstall',
            'Install' => 'Install',
            'Disable' => 'Disable',
            'Enable' => 'Enable',
        ]);

        $isInstalled = $pluginsService->isPluginInstalled($actionablePluginHandle);

        // Hmmm.
        if (!$isInstalled && $action !== 'Install') {
            $this->stderr("$actionablePluginHandle is not installed. Cancelling.".PHP_EOL);
            return ExitCode::OK;
        }

        try {
            switch ($action) {
                case 'Uninstall':
                    $this->stdout("Uninstalling plugin: $actionablePluginHandle...".PHP_EOL);

                    if (!$pluginsService->uninstallPlugin($actionablePluginHandle)) {
                        return $this->_handleFailedPluginAction($actionablePluginHandle, $action);
                    }
                    break;
                case 'Install':
                    $edition = $this->prompt('Which edition must the plugin be installed?');

                    $this->stdout("Installing plugin: $actionablePluginHandle...".PHP_EOL);

                    if (!$pluginsService->installPlugin($actionablePluginHandle, $edition)) {
                        return $this->_handleFailedPluginAction($actionablePluginHandle, $action);
                    }
                    break;
                case 'Disable':
                    $this->stdout("Disabling plugin: $actionablePluginHandle...".PHP_EOL);

                    if (!$pluginsService->disablePlugin($actionablePluginHandle)) {
                        return $this->_handleFailedPluginAction($actionablePluginHandle, $action);
                    }
                    break;
                case 'Enable':
                    $this->stdout("Enabling plugin: $actionablePluginHandle...".PHP_EOL);

                    if (!$pluginsService->enablePlugin($actionablePluginHandle)) {
                        return $this->_handleFailedPluginAction($actionablePluginHandle, $action);
                    }
                    break;
            }
        } catch (\Throwable $exception) {
            return $this->_handleFailedPluginAction($actionablePluginHandle, $action, $exception->getMessage());
        }

        return ExitCode::OK;
    }

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
            $plugins[] = $plugin = $pluginsService->createPlugin($handle);
            
            if (!$plugin) {
                throw new InvalidArgumentException("Unable to create a plugin by handle: $handle");
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
