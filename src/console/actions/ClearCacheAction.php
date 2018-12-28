<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\actions;

use Craft;
use craft\helpers\FileHelper;

use yii\base\Action;
use yii\base\InvalidArgumentException;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ClearCacheAction extends Action
{

    // Public Properties
    // =========================================================================

    /**
     * @var string|callable
     */
    public $action;

    /**
     * @var string
     */
    public $label;

    /**
     * @var array
     */
    public $params;

    /**
     * @var Controller
     */
    public $controller;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function run()
    {
        $label = $this->controller->ansiFormat(Craft::t('app', 'Clearing cache:'), Console::FG_GREEN);
        $name = $this->controller->ansiFormat($this->label, Console::FG_YELLOW);
        echo "$label $name\n";
        if (is_string($this->action)) {
            try {
                FileHelper::clearDirectory($this->action);
            } catch (InvalidArgumentException $e) {
                // the directory doesn't exist
            } catch (\Throwable $e) {
                $error = "Could not clear the directory {$this->label}: ".$e->getMessage();
                $this->controller->stderr($error.PHP_EOL, Console::FG_RED);
                Craft::warning($error, __METHOD__);
            }
        } elseif (isset($this->params)) {
            try {
                call_user_func_array($this->action, $this->params);
            } catch (\Throwable $e) {
                $error = "Error clearing cache {$this->label}: ".$e->getMessage();
                $this->controller->stderr($error.PHP_EOL, Console::FG_RED);
                Craft::warning($error, __METHOD__);
            }
        } else {
            try {
                $action = $this->action;
                $action();
            } catch (\Throwable $e) {
                $error = "Error clearing cache {$this->label}: ".$e->getMessage();
                $this->controller->stderr($error.PHP_EOL, Console::FG_RED);
                Craft::warning($error, __METHOD__);
            }
        }
    }
}
