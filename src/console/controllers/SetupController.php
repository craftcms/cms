<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\console\controllers;

use Craft;
use craft\helpers\Console;
use craft\helpers\FileHelper;
use yii\base\Exception;
use yii\console\Controller;

/**
 * Craft CMS setup installer.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SetupController extends Controller
{
    // Public Methods
    // =========================================================================

    /**
     * Generates a new security key and saves it in the .env file.
     *
     * @param string $name The name of the environment variable to set
     */
    public function actionSecurityKey($name = 'CRAFT_SECURITY_KEY')
    {
        $path = Craft::getAlias('@root/.env');
        if (!file_exists($path)) {
            if ($this->confirm("A .env file doesn't exist at {$path}. Would you like to create one?")) {
                FileHelper::writeToFile($path, "{$name}=".PHP_EOL);
                $this->stdout("{$path} created. Note you still need to set up PHP dotenv for its values to take effect.".PHP_EOL, Console::FG_YELLOW);
            } else {
                $this->stdout('Action aborted.'.PHP_EOL, Console::FG_YELLOW);
                return;
            }
        }

        $contents = file_get_contents($path);
        $config = Craft::$app->getConfig()->getGeneral();
        $qName = preg_quote($name, '/');
        $key = Craft::$app->getSecurity()->generateRandomString();
        $contents = preg_replace("/^(\s*){$qName}=.*/m", "\$1{$name}=\"{$key}\"", $contents, -1, $count);
        if ($count === 0) {
            if ($this->confirm("{$name} could not be found in {$path}. Would you like to add it?")) {
                $contents = rtrim($contents);
                $contents = ($contents ? $contents.PHP_EOL.PHP_EOL : '')."{$name}=\"{$key}\"".PHP_EOL;
            } else {
                $this->stdout('Action aborted.'.PHP_EOL, Console::FG_YELLOW);
                return;
            }
        }

        FileHelper::writeToFile($path, $contents);
        $config->securityKey = $key;
        $this->stdout("New key saved to {$path}: {$key}".PHP_EOL, Console::FG_YELLOW);
    }
}
