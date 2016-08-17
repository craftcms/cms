<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\helpers;

use yii\base\InvalidParamException;

/**
 * Class Json
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Json extends \yii\helpers\Json
{
    // Public Methods
    // =========================================================================

    /**
     * Decodes the given JSON string into a PHP data structure, only if the string is valid JSON.
     *
     * @param string  $str     The string to be decoded, if it's valid JSON.
     * @param boolean $asArray Whether to return objects in terms of associative arrays.
     *
     * @return mixed The PHP data, or the given string if it wasn’t valid JSON.
     */
    public static function decodeIfJson($str, $asArray = true)
    {
        try {
            return static::decode($str, $asArray);
        } catch (InvalidParamException $e) {
            // Wasn't JSON
            return $str;
        }
    }

    /**
     * Sets JSON helpers on the response.
     *
     * @return void
     */
    public static function sendJsonHeaders()
    {
        static::setJsonContentTypeHeader();
        Header::setNoCache();
    }

    /**
     * Sets the Content-Type header to 'application/json'.
     *
     * @return void
     */
    public static function setJsonContentTypeHeader()
    {
        Header::setContentTypeByExtension('json');
    }

    /**
     * Removes single-line, multi-line, //, /*, comments from JSON
     * (since comments technically product invalid JSON).
     *
     * @param string $json
     *
     * @return string
     */
    public static function removeComments($json)
    {
        // Remove any comments from the JSON.
        // Adapted from http://stackoverflow.com/a/31907095/684
        $pattern = '/(?:(?:\/\*(?:[^*]|(?:\*+[^*\/]))*\*+\/)|(?:(?<!\:|\\\|\'|\")\/\/.*))/';

        $json = preg_replace($pattern, '', $json);
        $json = trim($json, PHP_EOL);

        return $json;
    }
}
