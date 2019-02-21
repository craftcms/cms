<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace yii\helpers;

use Craft;

/**
 * @inheritdoc
 */
class Inflector extends BaseInflector
{
    /**
     * @inheritdoc
     * @todo remove this once Yii 2.0.16.1 is released
     */
    public static function camel2words($name, $ucwords = true)
    {
        $label = mb_strtolower(trim(str_replace([
            '-',
            '_',
            '.',
        ], ' ', preg_replace('/(?<!\p{Lu})(\p{Lu})|(\p{Lu})(?=\p{Ll})/u', ' \0', $name))), self::encoding());

        return $ucwords ? StringHelper::mb_ucwords($label, self::encoding()) : $label;
    }

    /**
     * @return string
     */
    private static function encoding(): string
    {
        return isset(Craft::$app) ? Craft::$app->charset : 'UTF-8';
    }
}
