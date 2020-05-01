<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * Class Html
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
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
     * Generates a hidden CSRF input tag.
     *
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     * @return string The generated hidden input tag
     * @since 3.3.0
     */
    public static function csrfInput(array $options = []): string
    {
        $request = Craft::$app->getRequest();
        return static::hiddenInput($request->csrfParam, $request->getCsrfToken(), $options);
    }

    /**
     * Generates a hidden `action` input tag.
     *
     * @param string $route The action route
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     * @return string The generated hidden input tag
     * @since 3.3.0
     */
    public static function actionInput(string $route, array $options = []): string
    {
        return static::hiddenInput('action', $route, $options);
    }

    /**
     * Generates a hidden `redirect` input tag.
     *
     * @param string $url The URL to redirect to
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     * @return string The generated hidden input tag
     * @throws Exception if the validation key could not be written
     * @throws InvalidConfigException when HMAC generation fails
     * @since 3.3.0
     */
    public static function redirectInput(string $url, array $options = []): string
    {
        return static::hiddenInput('redirect', Craft::$app->getSecurity()->hashData($url), $options);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function a($text, $url = null, $options = [])
    {
        if ($url !== null) {
            // Use UrlHelper::url() instead of Url::to()
            $options['href'] = UrlHelper::url($url);
        }

        return static::tag('a', $text, $options);
    }

    /**
     * Appends HTML to the end of the given tag.
     *
     * @param string $tag The HTML tag that `$html` should be appended to
     * @param string $html The HTML to append to `$tag`.
     * @param string|null $ifExists What to do if `$tag` already contains a child of the same type as the element
     * defined by `$html`. Set to `'keep'` if no action should be taken, or `'replace'` if it should be replaced
     * by `$tag`.
     * @return string The modified HTML
     * @since 3.3.0
     */
    public static function appendToTag(string $tag, string $html, string $ifExists = null): string
    {
        return self::_addToTagInternal($tag, $html, 'htmlEnd', $ifExists);
    }

    /**
     * Prepends HTML to the beginning of given tag.
     *
     * @param string $tag The HTML tag that `$html` should be prepended to
     * @param string $html The HTML to prepend to `$tag`.
     * @param string|null $ifExists What to do if `$tag` already contains a child of the same type as the element
     * defined by `$html`. Set to `'keep'` if no action should be taken, or `'replace'` if it should be replaced
     * by `$tag`.
     * @return string The modified HTML
     * @since 3.3.0
     */
    public static function prependToTag(string $tag, string $html, string $ifExists = null): string
    {
        return self::_addToTagInternal($tag, $html, 'htmlStart', $ifExists);
    }

    /**
     * Parses an HTML tag and returns info about it and its children.
     *
     * @param string $tag The HTML tag
     * @param int $offset The offset to start looking for a tag
     * @return array An array containing `type`, `attributes`, `children`, `start`, `end`, `htmlStart`, and `htmlEnd`
     * properties. Nested text nodes will be represented as arrays within `children` with `type` set to `'text'`, and a
     * `value` key containing the text value.
     * @throws InvalidArgumentException if `$tag` doesn't contain a valid HTML tag
     * @since 3.3.0
     */
    public static function parseTag(string $tag, int $offset = 0): array
    {
        list($type, $start) = self::_findTag($tag, $offset);
        $attributes = static::parseTagAttributes($tag, $start, $attrStart, $attrEnd);
        $end = strpos($tag, '>', $attrEnd) + 1;
        $isVoid = $tag[$end - 2] === '/' || isset(static::$voidElements[$type]);
        $children = [];

        // If this is a void element, we're done here
        if ($isVoid) {
            $htmlStart = $htmlEnd = null;
        } else {
            // Otherwise look for nested tags
            $htmlStart = $cursor = $end;

            do {
                try {
                    $subtag = static::parseTag($tag, $cursor);
                    // Did we skip some text to get there?
                    if ($subtag['start'] > $cursor) {
                        $children[] = [
                            'type' => 'text',
                            'value' => substr($tag, $cursor, $subtag['start'] - $cursor),
                        ];
                    }
                    $children[] = $subtag;
                    $cursor = $subtag['end'];
                } catch (InvalidArgumentException $e) {
                    // We must have just reached the end
                    break;
                }
            } while (true);

            // Find the closing tag
            if (($htmlEnd = stripos($tag, "</{$type}>", $cursor)) === false) {
                throw new InvalidArgumentException("Could not find a </{$type}> tag in string: {$tag}");
            }

            $end = $htmlEnd + strlen($type) + 3;

            if ($htmlEnd > $cursor) {
                $children[] = [
                    'type' => 'text',
                    'value' => substr($tag, $cursor, $htmlEnd - $cursor),
                ];
            }
        }

        return compact('type', 'attributes', 'children', 'start', 'htmlStart', 'htmlEnd', 'end');
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
        $oldAttributes = static::parseTagAttributes($tag, 0, $start, $end, true);
        $attributes = ArrayHelper::merge($oldAttributes, $attributes);

        // Ensure we don't have any duplicate classes
        if (isset($attributes['class']) && is_array($attributes['class'])) {
            $attributes['class'] = array_unique($attributes['class']);
        }

        return substr($tag, 0, $start) .
            static::renderTagAttributes($attributes) .
            substr($tag, $end);
    }

    /**
     * Parses an HTML tag to find its attributes.
     *
     * @param string $tag The HTML tag to parse
     * @param int $offset The offset to start looking for a tag
     * @param int|null $start The start position of the first attribute in the given tag
     * @param int|null $end The end position of the last attribute in the given tag
     * @param bool $decode Whether the attributes should be HTML decoded in the process
     * @return array The parsed HTML tag attributes
     * @throws InvalidArgumentException if `$tag` doesn't contain a valid HTML tag
     * @since 3.3.0
     */
    public static function parseTagAttributes(string $tag, int $offset = 0, int &$start = null, int &$end = null, bool $decode = false): array
    {
        list($type, $tagStart) = self::_findTag($tag, $offset);
        $start = $tagStart + strlen($type) + 1;
        $anchor = $start;
        $attributes = [];

        do {
            if (!preg_match('/\s*([^=\/> ]+)/A', $tag, $match, 0, $anchor)) {
                // Did we just reach the end of the tag?
                if (preg_match('/(\s*)\/?>/A', $tag, $match, 0, $anchor)) {
                    $end = $anchor;
                    break;
                }
                // Otherwise this is a malformed tag
                throw new InvalidArgumentException('Malformed HTML tag in string: ' . $tag);
            }

            $name = $match[1];
            $anchor += strlen($match[0]);

            // Does the tag have a value?
            if (preg_match('/=(?:(["\'])(.*?)\1|([^ >]+))/A', $tag, $match, 0, $anchor)) {
                $value = $match[3] ?? $match[2];
                $anchor += strlen($match[0]);
            } else {
                $value = true;
            }

            $attributes[$name] = $value;
        } while (true);

        $attributes = static::normalizeTagAttributes($attributes);

        if ($decode) {
            foreach ($attributes as &$value) {
                if (is_string($value)) {
                    $value = static::decode($value);
                }
            }
        }

        return $attributes;
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

    /**
     * Finds the first tag defined in some HTML that isn't a comment or DTD.
     *
     * @param string $html
     * @param int $offset
     * @return array The tag type and starting position
     * @throws
     */
    private static function _findTag(string $html, int $offset = 0): array
    {
        // Find the first HTML tag that isn't a DTD or a comment
        if (!preg_match('/<(\/?\w+)/', $html, $match, PREG_OFFSET_CAPTURE, $offset) || $match[1][0][0] === '/') {
            throw new InvalidArgumentException('Could not find an HTML tag in string: ' . $html);
        }

        return [strtolower($match[1][0]), $match[0][1]];
    }

    /**
     * Appends or prepends HTML to the beginning of a string.
     *
     * @param string $tag
     * @param string $html
     * @param string $position
     * @param string|null $ifExists
     * @return string
     */
    private static function _addToTagInternal(string $tag, string $html, string $position, string $ifExists = null): string
    {
        $info = static::parseTag($tag);

        // Make sure it's not a void tag
        if (!isset($info['htmlStart'])) {
            throw new InvalidArgumentException("<{$info['type']}> can't have children.");
        }

        if ($ifExists) {
            // See if we have a child of the same type
            list($type) = self::_findTag($html);
            $child = ArrayHelper::firstWhere($info['children'], 'type', $type, true);

            if ($child) {
                switch ($ifExists) {
                    case 'keep':
                        return $tag;
                    case 'replace':
                        return substr($tag, 0, $child['start']) .
                            $html .
                            substr($tag, $child['end']);
                    default:
                        throw new InvalidArgumentException('Invalid $ifExists value: ' . $ifExists);
                }
            }
        }

        return substr($tag, 0, $info[$position]) .
            $html .
            substr($tag, $info[$position]);
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
