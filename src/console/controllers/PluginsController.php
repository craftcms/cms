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
     * @return int
     */
    public function manage() : int
    {
        $pluginsService = Craft::$app->getPlugins();

        if (($actionablePlugins = $this->prompt(
            'Enter plugins seperated by comma. Enter "No" if you want a list of plugins and the option to select from that list.'.PHP_EOL)
            ) !== 'No') {
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

            $plugins = $pluginsService->getAllPlugins();

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
            $isInstalled = Craft::$app->getPlugins()->isPluginInstalled($actionablePlugin);

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
                        if (!Craft::$app->getPlugins()->uninstallPlugin($actionablePlugin)) {
                            return $this->_handleFailedPluginAction($actionablePlugin, $action);
                        }
                        break;
                    case 'Install':
                        $edition = $this->prompt('Which edition must the plugin be installed?');
                        if (!Craft::$app->getPlugins()->installPlugin($actionablePlugin, $edition)) {
                            return $this->_handleFailedPluginAction($actionablePlugin, $action);
                        }
                        break;
                    case 'Disable':
                        if (!Craft::$app->getPlugins()->disablePlugin($actionablePlugin)) {
                            return $this->_handleFailedPluginAction($actionablePlugin, $action);
                        }
                        break;
                    case 'Enable':
                        if (!Craft::$app->getPlugins()->enablePlugin($actionablePlugin)) {
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
