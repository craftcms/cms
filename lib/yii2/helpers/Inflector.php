<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace yii\helpers;

use Craft;
use Yii;

/**
 * @inheritdoc
 */
class Inflector extends BaseInflector
{
    /**
     * @inheritdoc
     * @todo remove this once https://github.com/yiisoft/yii2/issues/18832 is resolved
     */
    public static function camel2words($name, $ucwords = true)
    {
        // Add a space before any uppercase letter preceded by a lowercase letter (xY => x Y)
        // and any uppercase letter preceded by an uppercase letter and followed by a lowercase letter (XYz => X Yz)
        $label = preg_replace('/(?<=\p{Ll})\p{Lu}|(?<=\p{L})\p{Lu}(?=\p{Ll})/u', ' \0', $name);

        $label = mb_strtolower(trim(str_replace(['-', '_', '.'], ' ', $label)), self::encoding());

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
