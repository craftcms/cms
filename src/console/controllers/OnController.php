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
 * Takes the system online
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.7
 */
class OnController extends Controller
{
    /**
     * Turns the system on.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        // If the isSystemLive config setting is set, then we can't control it from here
        if (is_bool($live = Craft::$app->getConfig()->getGeneral()->isSystemLive)) {
            $this->stderr('It\'s not possible to toggle the system status when the `isSystemLive` config setting is set.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (Craft::$app->getIsLive()) {
            $this->stdout('The system is already online.' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        // Allow changes to the project config even if it's supposed to be read only,
        // and prevent changes from getting written to YAML
        $projectConfig = Craft::$app->getProjectConfig();
        $projectConfig->readOnly = false;
        $projectConfig->writeYamlAutomatically = false;

        $projectConfig->set('system.live', true, null, false);

        $this->stdout('The system is now online.' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
