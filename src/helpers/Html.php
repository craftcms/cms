<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\image\SvgAllowedAttributes;
use enshrined\svgSanitize\Sanitizer;
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
     * Generates a hidden `failMessage` input tag.
     *
     * @param string $message The flash message to shown on failure
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     * @return string The generated hidden input tag
     * @throws Exception if the validation key could not be written
     * @throws InvalidConfigException when HMAC generation fails
     * @since 3.6.6
     */
    public static function failMessageInput(string $message, array $options = []): string
    {
        return static::hiddenInput('failMessage', Craft::$app->getSecurity()->hashData($message), $options);
    }

    /**
     * Generates a hidden `successMessage` input tag.
     *
     * @param string $message The flash message to shown on success
     * @param array $options The tag options in terms of name-value pairs. These will be rendered as
     * the attributes of the resulting tag. The values will be HTML-encoded using [[encode()]].
     * If a value is null, the corresponding attribute will not be rendered.
     * See [[renderTagAttributes()]] for details on how attributes are being rendered.
     * @return string The generated hidden input tag
     * @throws Exception if the validation key could not be written
     * @throws InvalidConfigException when HMAC generation fails
     * @since 3.6.6
     */
    public static function successMessageInput(string $message, array $options = []): string
    {
        return static::hiddenInput('successMessage', Craft::$app->getSecurity()->hashData($message), $options);
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
    public static function appendToTag(string $tag, string $html, ?string $ifExists = null): string
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
    public static function prependToTag(string $tag, string $html, ?string $ifExists = null): string
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
        [$type, $start] = self::_findTag($tag, $offset);
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
        [$type, $tagStart] = self::_findTag($tag, $offset);
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
            if ($value === false || $value === null) {
                $normalized[$name] = false;
                continue;
            }

            switch ($name) {
                case 'class':
                    $normalized[$name] = static::explodeClass($value);
                    break;
                case 'style':
                    $normalized[$name] = static::explodeStyle($value);
                    break;
                default:
                    // See if it's a data attribute
                    foreach (self::_sortedDataAttributes() as $dataAttribute) {
                        if (strpos($name, $dataAttribute . '-') === 0) {
                            $n = substr($name, strlen($dataAttribute) + 1);
                            $normalized[$dataAttribute][$n] = $value;
                            break 2;
                        }
                    }
                    $normalized[$name] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Explodes a `class` attribute into an array.
     *
     * @param string|string[]|bool|null $value
     * @return string[]
     * @since 3.5.0
     */
    public static function explodeClass($value): array
    {
        if ($value === null || is_bool($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            return ArrayHelper::filterEmptyStringsFromArray(explode(' ', $value));
        }
        throw new InvalidArgumentException('Invalid class value');
    }

    /**
     * Explodes a `style` attribute into an array of property/value pairs.
     *
     * @param string|string[]|bool|null $value
     * @return string[]
     * @since 3.5.0
     */
    public static function explodeStyle($value): array
    {
        if ($value === null || is_bool($value)) {
            return [];
        }
        if (is_array($value)) {
            return $value;
        }
        if (is_string($value)) {
            $styles = ArrayHelper::filterEmptyStringsFromArray(preg_split('/\s*;\s*/', $value));
            $normalized = [];
            foreach ($styles as $style) {
                [$n, $v] = array_pad(preg_split('/\s*:\s*/', $style, 2), 2, '');
                $normalized[$n] = $v;
            }
            return $normalized;
        }
        throw new InvalidArgumentException('Invalid style value');
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
        if (!preg_match('/<(\/?[\w\-]+)/', $html, $match, PREG_OFFSET_CAPTURE, $offset) || $match[1][0][0] === '/') {
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
    private static function _addToTagInternal(string $tag, string $html, string $position, ?string $ifExists = null): string
    {
        $info = static::parseTag($tag);

        // Make sure it's not a void tag
        if (!isset($info['htmlStart'])) {
            throw new InvalidArgumentException("<{$info['type']}> can't have children.");
        }

        if ($ifExists) {
            // See if we have a child of the same type
            [$type] = self::_findTag($html);
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

    /**
     * Normalizes an element ID into only alphanumeric characters, underscores, and dashes, or generates one at random.
     *
     * @param string $id
     * @return string
     * @since 3.5.0
     */
    public static function id(string $id = ''): string
    {
        $id = trim(preg_replace('/[^\w]+/', '-', $id), '-');
        return $id ?: StringHelper::randomString(10);
    }

    /**
     * Namespaces an input name.
     *
     * @param string $inputName The input name
     * @param string|null $namespace The namespace
     * @return string The namespaced input name
     * @since 3.5.0
     */
    public static function namespaceInputName(string $inputName, string $namespace): string
    {
        return preg_replace('/([^\'"\[\]]+)([^\'"]*)/', $namespace . '[$1]$2', $inputName);
    }

    /**
     * Namespaces an ID.
     *
     * @param string $id The ID
     * @param string|null $namespace The namespace
     * @return string The namespaced ID
     * @since 3.5.0
     */
    public static function namespaceId(string $id, string $namespace): string
    {
        return static::id("$namespace-$id");
    }

    /**
     * Namespaces input names and other HTML attributes, as well as CSS selectors.
     *
     * This is a shortcut for calling [[namespaceInputs()]] and [[namespaceAttributes()]].
     *
     * @param string $html The HTML code
     * @param string $namespace The namespace
     * @param bool $withClasses Whether class names should be namespaced as well (affects both `class` attributes and class name CSS selectors)
     * @return string The HTML with namespaced attributes
     * @since 3.5.0
     */
    public static function namespaceHtml(string $html, string $namespace, bool $withClasses = false): string
    {
        $markers = self::_escapeTextareas($html);
        self::_namespaceInputs($html, $namespace);
        self::_namespaceAttributes($html, $namespace, $withClasses);
        return self::_restoreTextareas($html, $markers);
    }

    /**
     * Renames HTML input names so they belong to a namespace.
     *
     * This method will go through the passed-in HTML code looking for `name` attributes, and namespace their values.
     *
     * For example, this:
     *
     * ```html
     * <input type="text" name="title">
     * <textarea name="fields[body]"></textarea>
     * ```
     *
     * would become this, if it were namespaced with `foo`:
     *
     * ```html
     * <input type="text" name="foo[title]">
     * <textarea name="foo[fields][body]"></textarea>
     * ```
     *
     * @param string $html The HTML code
     * @param string|null $namespace The namespace
     * @return string The HTML with namespaced input names
     * @since 3.5.0
     * @see namespaceHtml()
     * @see namespaceAttributes()
     */
    public static function namespaceInputs(string $html, string $namespace): string
    {
        $markers = self::_escapeTextareas($html);
        static::_namespaceInputs($html, $namespace);
        return self::_restoreTextareas($html, $markers);
    }

    /**
     * @param string $html
     * @param string $namespace
     */
    private static function _namespaceInputs(string &$html, string $namespace)
    {
        $html = preg_replace('/(?<![\w\-])(name=(\'|"))([^\'"\[\]]+)([^\'"]*)\2/i', '$1' . $namespace . '[$3]$4$2', $html);
    }

    /**
     * Prepends a namespace to `id` attributes, and any of the following things that reference those IDs:
     *
     * - `for`, `list`, `href`, `aria-labelledby`, `aria-describedby`, `data-target`, `data-reverse-target`, and `data-target-prefix` attributes
     * - ID selectors within `<style>` tags
     *
     * For example, this:
     *
     * ```html
     * <style>#summary { font-size: larger }</style>
     * <p id="summary">...</p>
     * ```
     *
     * would become this, if it were namespaced with `foo`:
     *
     * ```html
     * <style>#foo-summary { font-size: larger }</style>
     * <p id="foo-summary">...</p>
     * ```
     *
     * @param string $html The HTML code
     * @param string $namespace The namespace
     * @param bool $withClasses Whether class names should be namespaced as well (affects both `class` attributes and class name CSS selectors)
     * @return string The HTML with namespaced attributes
     * @since 3.5.0
     * @see namespaceHtml()
     * @see namespaceInputs()
     */
    public static function namespaceAttributes(string $html, string $namespace, bool $withClasses = false): string
    {
        $markers = self::_escapeTextareas($html);
        self::_namespaceAttributes($html, $namespace, $withClasses);
        return self::_restoreTextareas($html, $markers);
    }

    /**
     * @param string $html
     * @param string $namespace
     * @param bool $withClasses
     */
    private static function _namespaceAttributes(string &$html, string $namespace, bool $withClasses)
    {
        // normalize the namespace
        $namespace = static::id($namespace);

        // Namespace & capture the ID attributes
        $ids = [];
        $html = preg_replace_callback('/(?<=\sid=)(\'|")([^\'"\s]*)\1/i', function($match) use ($namespace, &$ids): string {
            $ids[] = $match[2];
            return $match[1] . $namespace . '-' . $match[2] . $match[1];
        }, $html);
        $ids = array_flip($ids);

        // normal HTML attributes
        $html = preg_replace_callback(
            "/(?<=\\s)((for|list|xlink:href|href|aria\\-labelledby|aria\\-describedby|data\\-target|data\\-reverse\\-target|data\\-target\\-prefix)=('|\")#?)([^\.'\"\s]*)\\3/i",
            function(array $match) use ($namespace, $ids): string {
                if ($match[2] === 'data-target-prefix' || isset($ids[$match[4]])) {
                    return $match[1] . $namespace . '-' . $match[4] . $match[3];
                }
                return $match[0];
            }, $html);

        // ID references in url() calls
        $html = preg_replace_callback(
            "/(?<=url\\(#)[^'\"\s\)]*(?=\\))/i",
            function(array $match) use ($namespace, $ids): string {
                if (isset($ids[$match[0]])) {
                    return $namespace . '-' . $match[0];
                }
                return $match[0];
            }, $html);

        // class attributes
        if ($withClasses) {
            $html = preg_replace_callback('/(?<![\w\-])\bclass=(\'|")([^\'"]+)\\1/i', function($match) use ($namespace) {
                $newClasses = [];
                foreach (preg_split('/\s+/', $match[2]) as $class) {
                    $newClasses[] = "$namespace-$class";
                }
                return 'class=' . $match[1] . implode(' ', $newClasses) . $match[1];
            }, $html);
        }

        // CSS selectors
        $html = preg_replace_callback(
            '/(<style\b[^>]*>)(.*?)(<\/style>)/is',
            function(array $match) use ($namespace, $withClasses, $ids) {
                $html = preg_replace_callback(
                    "/(?<![\w'\"])#([^'\"\s]*)(?=[,\\s\\{])/",
                    function(array $match) use ($namespace, $ids): string {
                        if (isset($ids[$match[1]])) {
                            return '#' . $namespace . '-' . $match[1];
                        }
                        return $match[0];
                    }, $match[2]);
                if ($withClasses) {
                    $html = preg_replace("/(?<![\\w'\"])\\.([\\w\\-]+)(?=[,\\s\\{])/", ".$namespace-$1", $match[2]);
                }
                return $match[1] . $html . $match[3];
            }, $html);
    }

    /**
     * Replaces textareas with markers
     *
     * @param string $html
     * @return array
     */
    private static function _escapeTextareas(string &$html): array
    {
        $markers = [];
        $html = preg_replace_callback('/(<textarea\b[^>]*>)(.*?)(<\/textarea>)/is', function(array $matches) use (&$markers) {
            $marker = '{marker:' . StringHelper::randomString() . '}';
            $markers[$marker] = $matches[2];
            return $matches[1] . $marker . $matches[3];
        }, $html);
        return $markers;
    }

    /**
     * Replaces markers with textareas.
     *
     * @param string $html
     * @param array $markers
     * @return string
     */
    private static function _restoreTextareas(string $html, array &$markers): string
    {
        return str_replace(array_keys($markers), array_values($markers), $html);
    }

    /**
     * Sanitizes an SVG.
     *
     * @param string $svg
     * @return string
     * @since 3.5.0
     */
    public static function sanitizeSvg(string $svg): string
    {
        $sanitizer = new Sanitizer();
        $sanitizer->setAllowedAttrs(new SvgAllowedAttributes());
        $svg = $sanitizer->sanitize($svg);
        // Remove comments, title & desc
        $svg = preg_replace('/<!--.*?-->\s*/s', '', $svg);
        $svg = preg_replace('/<title>.*?<\/title>\s*/is', '', $svg);
        $svg = preg_replace('/<desc>.*?<\/desc>\s*/is', '', $svg);
        return $svg;
    }

    /**
     * Generates a base64-encoded [data URL](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) for the given file path.
     *
     * @param string $file The file path
     * @param string|null $mimeType The file’s MIME type. If `null` then it will be determined automatically.
     * @return string The data URL
     * @throws InvalidArgumentException if `$file` is an invalid file path
     * @since 3.5.13
     */
    public static function dataUrl(string $file, ?string $mimeType = null): string
    {
        if (!is_file($file)) {
            throw new InvalidArgumentException("Invalid file path: $file");
        }

        if ($mimeType === null) {
            try {
                $mimeType = FileHelper::getMimeType($file);
            } catch (\Throwable $e) {
                Craft::warning("Unable to determine the MIME type for $file: " . $e->getMessage());
                Craft::$app->getErrorHandler()->logException($e);
            }
        }

        return static::dataUrlFromString(file_get_contents($file), $mimeType);
    }

    /**
     * Generates a base64-encoded [data URL](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) based on the given file contents and MIME type.
     *
     * @param string $contents The file path
     * @param string|null $mimeType The file’s MIME type. If `null` then it will be determined automatically.
     * @return string The data URL
     * @throws InvalidArgumentException if `$file` is an invalid file path
     * @since 3.5.13
     */
    public static function dataUrlFromString(string $contents, ?string $mimeType = null): string
    {
        return 'data:' . ($mimeType ? "$mimeType;" : '') . 'base64,' . base64_encode($contents);
    }
}
