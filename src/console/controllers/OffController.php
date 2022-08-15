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
 * Takes the system offline.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.7
 */
class OffController extends BaseSystemStatusController
{
    /**
     * @var int|null Number of seconds the `Retry-After` HTTP header should be set to for 503 responses.
     *
     * The `retryDuration` config setting can be used to configure a *system-wide* `Retry-After` header.
     *
     * ::: warning
     * The `isSystemLive` config setting takes precedence over the `system.live` project config value,
     * so if `config/general.php` sets `isSystemLive` to `true` or `false` these `on`/`off` commands error out.
     * :::
     *
     * **Example**
     *
     * Running the following takes the system offline and returns 503 responses until it’s switched on again:
     *
     * ```
     * $ php craft off --retry=60
     * The system is now offline.
     * The retry duration is now set to 60.
     * ```
     */
    public ?int $retry = null;

    /**
     * @inheritdoc
     */
    public function options($actionID): array
    {
        $options = parent::options($actionID);
        $options[] = 'retry';
        return $options;
    }

    /**
     * Disables `system.live` project config value—bypassing any `allowAdminChanges` config setting
     * restrictions—meant for temporary use during the deployment process.
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

        if (!Craft::$app->getIsLive()) {
            $this->stdout('The system is already offline.' . PHP_EOL, Console::FG_GREEN);
            return ExitCode::OK;
        }

        try {
            $this->set('system.live', false);
        } catch (Throwable $e) {
            $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->stdout('The system is now offline.' . PHP_EOL, Console::FG_GREEN);

        if (isset($this->retry)) {
            try {
                $this->set('system.retryDuration', (int)$this->retry ?: null);
            } catch (Throwable $e) {
                $this->stderr($e->getMessage() . PHP_EOL, Console::FG_RED);
                return ExitCode::UNSPECIFIED_ERROR;
            }

            $this->stdout(($this->retry ? "The retry duration is now set to $this->retry." : 'The retry duration has been removed.') . PHP_EOL, Console::FG_GREEN);
        }

        return ExitCode::OK;
    }
}
