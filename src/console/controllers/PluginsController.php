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
    // Properties
    // =========================================================================

    /**
     * @inheritdoc
     */
    public $defaultAction = 'view';

    // Public Methods
    // =========================================================================

    /**
     * View basic information about all plugins or all information about one plugin.
     *
     * @param string|null $pluginHandle
     * @return int
     * @throws InvalidPluginException
     */
    public function actionView() : int
    {
        $plugins = $this->_assemblePlugins();

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
     * Allows you to perform bulk a bulk action on a specified set of plugins.
     *
     * @return int
     */
    public function actionBulk()
    {
        $action = $this->select('What bulk actions do you want to perform?' . PHP_EOL, [
            'Uninstall' => 'This will Uninstall this plugin',
            'Install' => 'This will Install this plugin',
            'Disable' => 'This will Disable this plugin',
            'Enable' => 'This will Enable this plugin'
        ]);

        $actionablePlugins = $this->prompt(
            'Enter the applicable plugin handles seperated by a comma.'
        );

        if (!$actionablePlugins) {
            $this->stderr('No plugins entered Please try again'.PHP_EOL);
            return ExitCode::OK;
        }

        $actionablePlugins = StringHelper::split($actionablePlugins, ',');

        // Hmmm.
        if (!$actionablePlugins) {
            $this->stderr('Invalid plugin input. Please try again'.PHP_EOL);
            return ExitCode::OK;
        }

        foreach ($actionablePlugins as $actionablePlugin) {
            $composerInfo = Craft::$app->getPlugins()->getComposerPluginInfo($actionablePlugin);
            if (!$composerInfo) {
                $this->stderr("Invalid plugin handle: $actionablePlugin".PHP_EOL);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            if (($result = $this->_processPluginAction($actionablePlugin, $action)) !== 0) {
                return $result;
            }
        }

        $this->stdout('Bulk actions performed.'.PHP_EOL);
        return ExitCode::OK;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @param string $actionablePluginHandle
     * @return int
     */
    protected function _processPluginAction(string $actionablePluginHandle, string $action)
    {
        $pluginsService = Craft::$app->getPlugins();

        $this->stdout(PHP_EOL);

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
                    $edition = $this->prompt("Which edition must $actionablePluginHandle be installed with?");

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

        $this->stdout(PHP_EOL);
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
            $this->stderr("Additionally an exception was thrown: $exceptionMessage".PHP_EOL);
        }

        return ExitCode::OK;
    }
}
