<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use yii\base\InvalidArgumentException;

/**
 * Class Json
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Json extends \yii\helpers\Json
{
    // Public Methods
    // =========================================================================

    /**
     * Decodes the given JSON string into a PHP data structure, only if the string is valid JSON.
     *
     * @param mixed $str The string to be decoded, if it's valid JSON.
     * @param bool $asArray Whether to return objects in terms of associative arrays.
     * @return mixed The PHP data, or the given string if it wasn’t valid JSON.
     */
    public static function decodeIfJson($str, bool $asArray = true)
    {
        try {
            return static::decode($str, $asArray);
        } catch (InvalidArgumentException $e) {
            // Wasn't JSON
            return $str;
        }
    }
}
