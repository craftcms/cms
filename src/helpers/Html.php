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
 * Class Html
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Html extends \yii\helpers\Html
{
    /**
     * @var string[]
     * @see _sortedDataAttributes()
     */
    private static $_sortedDataAttributes;

    /**
     * Will take an HTML string and an associative array of key=>value pairs, HTML encode the values and swap them back
     * into the original string using the keys as tokens.
     *
     * @param string $html The HTML string.
     * @param array $variables An associative array of key => value pairs to be applied to the HTML string using `strtr`.
     * @return string The HTML string with the encoded variable values swapped in.
     */
    public static function encodeParams(string $html, array $variables = []): string
    {
        // Normalize the param keys
        $normalizedVariables = [];

        if (is_array($variables)) {
            foreach ($variables as $key => $value) {
                $key = '{' . trim($key, '{}') . '}';
                $normalizedVariables[$key] = static::encode($value);
            }

            $html = strtr($html, $normalizedVariables);
        }

        return $html;
    }

    /**
     * Modifies a HTML tag’s attributes, supporting the same attribute definitions as [[renderTagAttributes()]].
     *
     * @param string $tag The HTML tag whose attributes should be modified.
     * @param array $attributes The attributes to be added to the tag.
     * @return string The modified HTML tag.
     * @throws InvalidArgumentException if `$tag` doesn't contain a valid HTML tag
     * @since 3.3.0
     */
    public static function modifyTagAttributes(string $tag, array $attributes): string
    {
        // Normalize the attributes & merge with the old attributes
        $attributes = static::normalizeTagAttributes($attributes);
        $oldAttributes = static::parseTagAttributes($tag, $attrStart, $attrEnd);
        $attributes = ArrayHelper::merge($oldAttributes, $attributes);

        // Ensure we don't have any duplicate classes
        if (isset($attributes['class']) && is_array($attributes['class'])) {
            $attributes['class'] = array_unique($attributes['class']);
        }

        return substr($tag, 0, $attrStart) .
            static::renderTagAttributes($attributes) .
            substr($tag, $attrEnd);
    }

    /**
     * Parses an HTML tag to find its attributes.
     *
     * @param string $tag The HTML tag to parse
     * @param int|null $attrStart The start position of the first attribute in the given tag
     * @param int|null $attrEnd The end position of the last attribute in the given tag
     * @return array The parsed HTML tags
     * @throws InvalidArgumentException if `$tag` doesn't contain a valid HTML tag
     * @since 3.3.0
     */
    public static function parseTagAttributes(string $tag, int &$attrStart = null, int &$attrEnd = null): array
    {
        // Find the first HTML tag that isn't a DTD or a comment
        if (!preg_match('/<\w+/', $tag, $match, PREG_OFFSET_CAPTURE)) {
            throw new InvalidArgumentException('Could not find an HTML tag in string: ' . $tag);
        }

        $attrStart = $match[0][1] + strlen($match[0][0]);
        $anchor = $attrStart;
        $attributes = [];



        do {
            if (!preg_match('/\s*([^=\/> ]+)/A', $tag, $match, 0, $anchor)) {
                // Did we just reach the end of the tag?
                if (preg_match('/(\s*)\/?>/A', $tag, $match, 0, $anchor)) {
                    $attrEnd = $anchor;
                    break;
                }
                // Otherwise this is a malformed tag
                throw new InvalidArgumentException('Malformed HTML tag in string: ' . $tag);
            }

            $name = $match[1];
            $anchor += strlen($match[0]);

            // Does the tag have a value?
            if (preg_match('/=(?:(["\'])(.*?)\1|([^ >]+))/A', $tag, $match, 0, $anchor)) {
                $value = $match[2] ?: $match[3];
                $anchor += strlen($match[0]);
            } else {
                $value = true;
            }

            $attributes[$name] = $value;
        } while (true);

        return static::normalizeTagAttributes($attributes);
    }

    /**
     * Normalizes attributes.
     *
     * @param array $attributes
     * @return array
     * @since 3.3.0
     */
    public static function normalizeTagAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $name => $value) {
            if (!is_bool($value) && !is_array($value)) {
                if ($name === 'class') {
                    $normalized[$name] = ArrayHelper::filterEmptyStringsFromArray(explode(' ', $value));
                    continue;
                }

                if ($name === 'style') {
                    $styles = ArrayHelper::filterEmptyStringsFromArray(preg_split('/\s*;\s*/', $value));
                    foreach ($styles as $style) {
                        list($n, $v) = array_pad(preg_split('/\s*:\s*/', $style, 2), 2, '');
                        $normalized[$name][$n] = $v;
                    }
                    continue;
                }

                // See if it's a data attribute
                foreach (self::_sortedDataAttributes() as $dataAttribute) {
                    if (strpos($name, $dataAttribute . '-') === 0) {
                        $n = substr($name, strlen($dataAttribute) + 1);
                        $normalized[$dataAttribute][$n] = $value;
                        continue 2;
                    }
                }
            }

            $normalized[$name] = $value;
        }

        return $normalized;
    }

    private static function _sortedDataAttributes(): array
    {
        if (self::$_sortedDataAttributes === null) {
            self::$_sortedDataAttributes = array_merge(static::$dataAttributes);
            usort(self::$_sortedDataAttributes, function(string $a, string $b): int {
                return strlen($b) - strlen($a);
            });
        }
        return self::$_sortedDataAttributes;
    }
}
