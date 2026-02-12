<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\controllers;

use Craft;
use craft\helpers\Console;
use Throwable;
use yii\console\ExitCode;

/**
 * Takes the system online.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.7
 */
class OnController extends BaseSystemStatusController
{
    /**
     * Turns the system on.
     *
     * Example:
     * ```
     * $ php craft on
     * The system is now online.
     * ```
     *
     * @return int
     */
    public function actionIndex(): int
    {
        // If the isSystemLive config setting is set, then we can’t control it from here
        if (is_bool(Craft::$app->getConfig()->getGeneral()->isSystemLive)) {
            $this->stderr('It\'s not possible to toggle the system status when the `isSystemLive` config setting is set.' . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if (Craft::$app->getIsLive()) {
            $this->stdout('The system is already online.' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        try {
            $this->set('system.live', true);
        } catch (Throwable $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('The system is now online.' . PHP_EOL, Console::FG_GREEN);
        return ExitCode::OK;
    }
}
