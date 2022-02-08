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
 * Clears all deprecation warnings.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.7.31
 */
class ClearDeprecationsController extends Controller
{
    /**
     * Clears all deprecation warnings.
     *
     * @return int
     */
    public function actionIndex(): int
    {
        $this->stdout('Clearing all deprecation logs ... ');
        Craft::$app->deprecator->deleteAllLogs();
        $this->stdout("done\n", Console::FG_GREEN);
        return ExitCode::OK;
    }
}
