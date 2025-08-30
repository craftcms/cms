<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use craft\elements\Asset;
use CraftCms\Cms\Support\Exceptions\InvalidHtmlTagException;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * Class Html
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Html} instead.
 */
class Html extends \yii\helpers\Html
{
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
        return \CraftCms\Cms\Support\Html::encodeParams($html, $variables);
    }

    /**
     * Converts spaces into `%20` entities.
     *
     * @param string $str
     * @return string
     * @since 4.0.4
     */
    public static function encodeSpaces(string $str): string
    {
        return \CraftCms\Cms\Support\Html::encodeSpaces($str);
    }

    /**
     * Disables any form inputs in the given HTML.
     *
     * @param callable|string|null $html
     * @return string|null
     * @since 5.6.0
     */
    public static function disableInputs(callable|string|null $html): ?string
    {
        return \CraftCms\Cms\Support\Html::disableInputs($html);
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
        return \CraftCms\Cms\Support\Html::csrfInput($options);
    }

    /**
     * @inheritdoc
     */
    public static function beginForm($action = '', $method = 'post', $options = []): string
    {
        return \CraftCms\Cms\Support\Html::beginForm($action, $method, $options);
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
        return \CraftCms\Cms\Support\Html::actionInput($route, $options);
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
        return \CraftCms\Cms\Support\Html::redirectInput($url, $options);
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
        return \CraftCms\Cms\Support\Html::failMessageInput($message, $options);
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
        return \CraftCms\Cms\Support\Html::successMessageInput($message, $options);
    }

    /**
     * @inheritdoc
     */
    public static function tag($name, $content = '', $options = [])
    {
        return \CraftCms\Cms\Support\Html::tag($name, $content, $options);
    }

    /**
     * @inheritdoc
     */
    public static function beginTag($name, $options = [])
    {
        return \CraftCms\Cms\Support\Html::beginTag($name, $options);
    }

    /**
     * @inheritdoc
     * @since 3.3.0
     */
    public static function a($text, $url = null, $options = []): string
    {
        return \CraftCms\Cms\Support\Html::a($text, $url, $options);
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
        return \CraftCms\Cms\Support\Html::appendToTag($tag, $html, $ifExists);
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
        return \CraftCms\Cms\Support\Html::prependToTag($tag, $html, $ifExists);
    }

    /**
     * Parses an HTML tag and returns info about it and its children.
     *
     * @param string $tag The HTML tag
     * @param int $offset The offset to start looking for a tag
     * @return array An array containing `type`, `attributes`, `children`, `start`, `end`, `htmlStart`, and `htmlEnd`
     * properties. Nested text nodes will be represented as arrays within `children` with `type` set to `'text'`, and a
     * `value` key containing the text value.
     * @throws InvalidHtmlTagException if `$tag` doesn't contain a valid HTML tag
     * @since 3.3.0
     */
    public static function parseTag(string $tag, int $offset = 0): array
    {
        return \CraftCms\Cms\Support\Html::parseTag($tag, $offset);
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
        return \CraftCms\Cms\Support\Html::modifyTagAttributes($tag, $attributes);
    }

    /**
     * Parses an HTML tag to find its attributes.
     *
     * @param string $tag The HTML tag to parse
     * @param int $offset The offset to start looking for a tag
     * @param int|null $start The start position of the first attribute in the given tag
     * @param-out int $start
     * @param int|null $end The end position of the last attribute in the given tag
     * @param bool $decode Whether the attributes should be HTML decoded in the process
     * @return array The parsed HTML tag attributes
     * @throws InvalidHtmlTagException if `$tag` doesn't contain a valid HTML tag
     * @since 3.3.0
     */
    public static function parseTagAttributes(string $tag, int $offset = 0, ?int &$start = null, ?int &$end = null, bool $decode = false): array
    {
        return \CraftCms\Cms\Support\Html::parseTagAttributes($tag, $offset, $start, $end, $decode);
    }

    /**
     * Parses the next HTML tag attribute in a given string.
     *
     * @param string $html The HTML to parse
     * @param int $offset The offset to start looking for an attribute
     * @param int|null $start The start position of the attribute in the given HTML
     * @param int|null $end The end position of the attribute in the given HTML
     * @return array|null The name and value of the attribute, or `false` if no complete attribute was found
     * @throws InvalidArgumentException if `$html` doesn't begin with a valid HTML attribute
     * @since 3.7.0
     */
    public static function parseTagAttribute(string $html, int $offset = 0, ?int &$start = null, ?int &$end = null): ?array
    {
        try {
            return \CraftCms\Cms\Support\Html::parseTagAttribute($html, $offset, $start, $end);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
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
        return \CraftCms\Cms\Support\Html::normalizeTagAttributes($attributes);
    }

    /**
     * Explodes a `class` attribute into an array.
     *
     * @param mixed $value
     * @return string[]
     * @since 3.5.0
     */
    public static function explodeClass(mixed $value): array
    {
        return \CraftCms\Cms\Support\Html::explodeClass($value);
    }

    /**
     * Explodes a `style` attribute into an array of property/value pairs.
     *
     * @param mixed $value
     * @return string[]
     * @since 3.5.0
     */
    public static function explodeStyle(mixed $value): array
    {
        return \CraftCms\Cms\Support\Html::explodeStyle($value);
    }

    /**
     * Unwraps an IE conditional comment from the given HTML.
     *
     * @param string $content
     * @return array[] An array containing the HTML content, and the condition (if there is one).
     * @phpstan-return array{string,string|null}
     * @see wrapIntoCondition()
     * @since 4.0.0
     */
    public static function unwrapCondition(string $content): array
    {
        return \CraftCms\Cms\Support\Html::unwrapCondition($content);
    }

    /**
     * Unwraps a `<noscript>` tag from the given HTML.
     *
     * @param string $content
     * @return array[] An array containing the HTML content, and whether a `<noscript>` tag was found.
     * @phpstan-return array{string,bool}
     * @since 4.0.0
     */
    public static function unwrapNoscript(string $content): array
    {
        return \CraftCms\Cms\Support\Html::unwrapNoscript($content);
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
        return \CraftCms\Cms\Support\Html::id($id);
    }

    /**
     * Namespaces an input name.
     *
     * @param string $inputName The input name
     * @param string|null $namespace The namespace
     * @return string The namespaced input name
     * @since 3.5.0
     */
    public static function namespaceInputName(string $inputName, ?string $namespace): string
    {
        return \CraftCms\Cms\Support\Html::namespaceInputName($inputName, $namespace);
    }

    /**
     * Namespaces an ID.
     *
     * @param string $id The ID
     * @param string|null $namespace The namespace
     * @return string The namespaced ID
     * @since 3.5.0
     */
    public static function namespaceId(string $id, ?string $namespace): string
    {
        return \CraftCms\Cms\Support\Html::namespaceId($id, $namespace);
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
        return \CraftCms\Cms\Support\Html::namespaceHtml($html, $namespace, $withClasses);
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
     * @param string $namespace The namespace
     * @return string The HTML with namespaced input names
     * @see namespaceHtml()
     * @see namespaceAttributes()
     * @since 3.5.0
     */
    public static function namespaceInputs(string $html, string $namespace): string
    {
        return \CraftCms\Cms\Support\Html::namespaceInputs($html, $namespace);
    }

    /**
     * Prepends a namespace to `id` attributes, and any of the following things that reference those IDs:
     *
     * - `for`, `list`, `href`, `aria-labelledby`, `aria-describedby`, `aria-controls`, `data-target`, `data-reverse-target`, and `data-target-prefix` attributes
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
     * @see namespaceHtml()
     * @see namespaceInputs()
     * @since 3.5.0
     */
    public static function namespaceAttributes(string $html, string $namespace, bool $withClasses = false): string
    {
        return \CraftCms\Cms\Support\Html::namespaceAttributes($html, $namespace, $withClasses);
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
        return \CraftCms\Cms\Support\Html::sanitizeSvg($svg);
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
        try {
            return \CraftCms\Cms\Support\Html::dataUrl($file, $mimeType);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
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
        try {
            return \CraftCms\Cms\Support\Html::dataUrlFromString($contents, $mimeType);
        } catch (\InvalidArgumentException $e) {
            throw new InvalidArgumentException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Inserts a non-breaking space between the last two words of a string.
     *
     * @param string $string
     * @return string
     * @since 3.7.0
     */
    public static function widont(string $string): string
    {
        return \CraftCms\Cms\Support\Html::widont($string);
    }

    /**
     * Returns a visually-hidden input label.
     *
     * @param string $content
     * @param string|null $for
     * @param array $options
     * @return string
     * @since 4.0.0
     */
    public static function hiddenLabel(string $content, ?string $for = null, array $options = []): string
    {
        return \CraftCms\Cms\Support\Html::hiddenLabel($content, $for, $options);
    }

    /**
     * Encodes invalid (unclosed) HTML tags so they appear as plain text.
     *
     * @param string $html
     * @return string
     * @since 3.7.27
     */
    public static function encodeInvalidTags(string $html): string
    {
        return \CraftCms\Cms\Support\Html::encodeInvalidTags($html);
    }

    /**
     * Decodes any double-encoded entities.
     *
     * @param string $html
     * @return string
     * @since 5.8.3
     */
    public static function decodeDoubles(string $html): string
    {
        return \CraftCms\Cms\Support\Html::decodeDoubles($html);
    }

    /**
     * Returns the contents of a given SVG file.
     *
     * @param string|Asset $svg An SVG asset, a file path, or raw SVG markup
     * @param bool|null $sanitize Whether the SVG should be sanitized of potentially
     * malicious scripts. By default, the SVG will only be sanitized if an asset
     * or markup is passed in. (File paths are assumed to be safe.)
     * @param bool|null $namespace Whether class names and IDs within the SVG
     * should be namespaced to avoid conflicts with other elements in the DOM.
     * By default, the SVG will only be namespaced if an asset or markup is passed in.
     * @param bool $throwException Whether to throw an exception on error
     * @return string
     * @since 4.3.0
     */
    public static function svg(
        Asset|string $svg,
        ?bool $sanitize = null,
        ?bool $namespace = null,
        bool $throwException = false,
    ): string {
        return \CraftCms\Cms\Support\Html::svg($svg, $sanitize, $namespace, $throwException);
    }
}
