<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\console\actions\make;

use craft\console\controllers\MakeController;
use craft\helpers\Json;
use yii\base\Action;

/**
 * Class PluginAction
 *
 * @property MakeController $controller
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
abstract class BaseAction extends Action
{
    protected string $basePath;

    protected function writeToFile(string $file, string $contents, array $options = []): void
    {
        // Ensure all files end in a blank line
        $this->controller->writeToFile("$this->basePath/$file", $contents, $options);
    }

    protected function writeJson(string $file, mixed $value): void
    {
        $json = Json::encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        $this->writeToFile($file, $json);
    }
}
