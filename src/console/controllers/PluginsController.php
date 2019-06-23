<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\Console;
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

    public function actionUninstall(string $param = null)
    {
        if ($param === 'all') {
            if (!$this->stdout('Are you sure? This will uninstall ALL plugins and their associated data?')) {
                $this->stdout('Cancelling');
                return ExitCode::OK;
            }

            $this->stdout('Uninstalling all plugins'.PHP_EOL);

            foreach (Craft::$app->getPlugins()->getAllPlugins() as $plugin) {
                Craft::$app->getPlugins()->uninstallPlugin($plugin->getHandle());
            }

            $this->stdout('Uninstalled all plugins');
        }

        $plugin = Craft::$app->getPlugins()->getPlugin($param);

        if (!$plugin) {
            $this->stdout('No plugin');
            return ExitCode::OK;
        }

        Craft::$app->getPlugins()->uninstallPlugin($plugin);

        $this->stdout('Plugin uninstalled');
        return ExitCode::OK;
    }
}
