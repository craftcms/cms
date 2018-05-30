<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\log;

use Craft;
use craft\helpers\ArrayHelper;
use yii\helpers\VarDumper;

/**
 * Class FileTarget
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FileTarget extends \yii\log\FileTarget
{
    /**
     * @inheritdoc
     */
    protected function getContextMessage()
    {
        $context = ArrayHelper::filter($GLOBALS, $this->logVars);
        $result = [];
        $security = Craft::$app->getSecurity();

        foreach ($context as $key => $value) {
            $value = $security->redactIfSensitive($key, $value);
            $result[] = "\${$key} = ".VarDumper::dumpAsString($value);
        }

        return implode("\n\n", $result);
    }
}
