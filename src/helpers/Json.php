<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use yii\base\InvalidArgumentException;

/**
 * Class Json
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Json extends \yii\helpers\Json
{
    /**
     * Returns whether a string value looks like a JSON object or array.
     *
     * @param string $str
     * @return bool
     * @since 3.5.0
     */
    public static function isJsonObject(string $str): bool
    {
        return (bool)preg_match('/^(?:\{.*\}|\[.*\])$/s', $str);
    }

    /**
     * @inheritdoc
     * @param int $options The encoding options. `JSON_UNESCAPED_UNICODE` is used by default.
     */
    public static function encode($value, $options = JSON_UNESCAPED_UNICODE)
    {
        return parent::encode($value, $options);
    }

    /**
     * Decodes the given JSON string into a PHP data structure, only if the string is valid JSON.
     *
     * @param mixed $str The string to be decoded, if it's valid JSON.
     * @param bool $asArray Whether to return objects in terms of associative arrays.
     * @return mixed The PHP data, or the given string if it wasn’t valid JSON.
     */
    public static function decodeIfJson(mixed $str, bool $asArray = true): mixed
    {
        try {
            return static::decode($str, $asArray);
        } catch (InvalidArgumentException) {
            // Wasn't JSON
            return $str;
        }
    }

    /**
     * Decodes JSON from a given file path.
     *
     * @param string $file the file path
     * @param bool $asArray whether to return objects in terms of associative arrays
     * @return mixed The JSON-decoded file contents
     * @throws InvalidArgumentException if the file doesn’t exist or there was a problem JSON-decoding it
     * @since 4.3.5
     */
    public static function decodeFromFile(string $file, bool $asArray = true): mixed
    {
        $file = Craft::getAlias($file);

        if (!file_exists($file)) {
            throw new InvalidArgumentException("`$file` doesn’t exist.");
        }

        if (is_dir($file)) {
            throw new InvalidArgumentException("`$file` is a directory.");
        }

        try {
            return static::decode(file_get_contents($file), $asArray);
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException("`$file` doesn’t contain valid JSON.");
        }
    }
}
