<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace yii\helpers;

/**
 * @inheritdoc
 */
class ArrayHelper extends BaseArrayHelper
{
    public static function recursiveSort(array &$array, $sorter = null)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                static::recursiveSort($value, $sorter);
            }
        }
        unset($value);

        if ($sorter === null) {
            if (static::isIndexed($array)) {
                // leave it alone for now, until https://github.com/yiisoft/yii2/issues/20191 is fixed
                return $array;
            }
            $sorter = 'ksort';
        }

        call_user_func_array($sorter, [&$array]);

        return $array;
    }
}
