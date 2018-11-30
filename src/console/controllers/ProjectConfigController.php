<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\helpers\Console;
use yii\console\Controller;
use yii\console\ExitCode;

/**
 * Manages the project config.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class ProjectConfigController extends Controller
{
    /**
     * Syncs the project config.
     *
     * @return int
     */
    public function actionSync(): int
    {
        if (!Craft::$app->getConfig()->getGeneral()->useProjectConfigFile) {
            $this->stdout('Craft is not configured to use project.yaml. Please enable the \'useProjectConfigFile\' config setting in config/general.php.' . PHP_EOL, Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $this->stdout('Applying changes from project.yaml... ', Console::FG_YELLOW);
        try {
            Craft::$app->getProjectConfig()->applyYamlChanges();
        } catch (\Throwable $e) {
            $this->stderr('error: ' . $e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('done' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
