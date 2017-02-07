<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\log;

use Craft;
use craft\helpers\ArrayHelper;
use yii\helpers\VarDumper;

/**
 * Class FileTarget
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
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
        foreach ($context as $key => $value) {
            $value = $this->_redactSensitiveData($key, $value);
            $result[] = "\${$key} = ".VarDumper::dumpAsString($value);
        }

        return implode("\n\n", $result);
    }

    /**
     * Searches through the global variables to see if there's anything that should be redacted.
     *
     * @param string|array $value
     *
     * @return string|array
     */
    private function _redactSensitiveData($name, $value)
    {
        if (is_array($value)) {
            foreach ($value as $n => $v) {
                $value[$n] = $this->_redactSensitiveData($n, $value[$n]);
            }
        } else if (is_string($value)) {
            $value = Craft::$app->getSecurity()->redactIfSensitive($name, $value);
        }

        return $value;
    }
}
