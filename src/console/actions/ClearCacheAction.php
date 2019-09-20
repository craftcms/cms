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
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * @inheritdoc
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.37
 */
class ClearCacheAction extends Action
{
    // Properties
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

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     * @return int
     */
    public function run(): int
    {
        $label = Console::ansiFormat(Craft::t('app', 'Clearing cache:'), [Console::FG_GREEN]);
        $name = Console::ansiFormat($this->label, [Console::FG_YELLOW]);
        Console::output("{$label} {$name}");

        if (is_string($this->action)) {
            try {
                FileHelper::clearDirectory($this->action);
            } catch (InvalidArgumentException $e) {
                // the directory doesn't exist
            } catch (\Throwable $e) {
                $error = "Could not clear the directory {$this->label}: " . $e->getMessage();
                Console::error(Console::ansiFormat($error, [Console::FG_RED]));
                Craft::warning($error, __METHOD__);
            }
        } else if (isset($this->params)) {
            try {
                call_user_func_array($this->action, $this->params);
            } catch (\Throwable $e) {
                $error = "Error clearing cache {$this->label}: " . $e->getMessage();
                Console::error(Console::ansiFormat($error, [Console::FG_RED]));
                Craft::warning($error, __METHOD__);
            }
        } else {
            try {
                $action = $this->action;
                $action();
            } catch (\Throwable $e) {
                $error = "Error clearing cache {$this->label}: " . $e->getMessage();
                Console::error(Console::ansiFormat($error, [Console::FG_RED]));
                Craft::warning($error, __METHOD__);
            }
        }

        return ExitCode::OK;
    }
}
