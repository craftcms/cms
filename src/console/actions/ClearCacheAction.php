<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\actions;

use Craft;
use craft\console\controllers\ClearCachesController;
use craft\helpers\FileHelper;
use Throwable;
use yii\base\Action;
use yii\base\InvalidArgumentException;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * @inheritdoc
 * @property ClearCachesController $controller
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.37
 */
class ClearCacheAction extends Action
{
    /**
     * @var string|callable
     */
    public $action;

    /**
     * @var string
     */
    public string $label;

    /**
     * @var array|null
     */
    public ?array $params = null;

    /**
     * Clears the caches.
     *
     * @return int
     */
    public function run(): int
    {
        $this->controller->stdout(Craft::t('app', 'Clearing cache:') . ' ', Console::FG_GREEN);
        $this->controller->stdout($this->label . PHP_EOL, Console::FG_YELLOW);

        if (is_string($this->action)) {
            try {
                FileHelper::clearDirectory($this->action);
            } catch (InvalidArgumentException) {
                // the directory doesn't exist
            } catch (Throwable $e) {
                $error = "Could not clear the directory $this->label: " . $e->getMessage();
                $this->controller->stderr($error . PHP_EOL, Console::FG_RED);
                Craft::warning($error, __METHOD__);
            }
        } elseif (isset($this->params)) {
            try {
                call_user_func_array($this->action, $this->params);
            } catch (Throwable $e) {
                $error = "Error clearing cache $this->label: " . $e->getMessage();
                $this->controller->stderr($error . PHP_EOL, Console::FG_RED);
                Craft::warning($error, __METHOD__);
            }
        } else {
            try {
                $action = $this->action;
                $action();
            } catch (Throwable $e) {
                $error = "Error clearing cache $this->label: " . $e->getMessage();
                $this->controller->stderr($error . PHP_EOL, Console::FG_RED);
                Craft::warning($error, __METHOD__);
            }
        }

        return ExitCode::OK;
    }
}
