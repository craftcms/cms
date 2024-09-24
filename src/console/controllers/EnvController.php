<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\console\Controller;
use craft\helpers\App;
use craft\helpers\Console;
use yii\base\Exception;
use yii\console\ExitCode;

/**
 * Sets or removes environment variables in the `.env` file.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.3.0
 */
class EnvController extends Controller
{
    /**
     * @inheritdoc
     */
    public $defaultAction = 'show';

    /**
     * Displays the value of an environment variable, or sets its value if $name contains `=`.
     *
     *     php craft env CRAFT_DEV_MODE
     *     php craft env CRAFT_DEV_MODE=true
     *
     * @param string $name
     * @return int
     */
    public function actionShow(string $name): int
    {
        if (str_contains($name, '=')) {
            [$name, $value] = explode('=', $name, 2);
            return $this->runAction('set', [$name, $value]);
        }

        $value = App::env($name);
        $dump = Craft::dump($value, return: true);
        $this->stdout(trim($dump) . "\n");
        return ExitCode::OK;
    }

    /**
     * Sets an environment variable in the `.env` file.
     *
     *     php craft env/set CRAFT_DEV_MODE true
     *
     * @param string $name
     * @param string $value
     * @return int
     */
    public function actionSet(string $name, string $value = ''): int
    {
        if (str_contains($name, '=')) {
            [$name, $value] = explode('=', $name, 2);
        }

        try {
            Craft::$app->getConfig()->setDotEnvVar(trim($name), trim($value));
        } catch (Exception $e) {
            $this->stderr($e->getMessage() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $dump = Craft::dump(App::env($name), return: true);
        $this->stdout(sprintf("%s %s.\n", $this->markdownToAnsi("`$name` is now"), trim($dump)));
        return ExitCode::OK;
    }

    /**
     * Removes an environment variable from the `.env` file.
     *
     *     php craft env/remove CRAFT_DEV_MODE
     *
     * @param string $name
     * @return int
     */
    public function actionRemove(string $name): int
    {
        try {
            Craft::$app->getConfig()->setDotEnvVar($name, false);
        } catch (Exception $e) {
            $this->stderr($e->getMessage() . "\n", Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout($this->markdownToAnsi("`$name` has been removed.") . "\n");
        return ExitCode::OK;
    }
}
