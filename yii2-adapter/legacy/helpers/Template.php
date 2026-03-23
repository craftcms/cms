<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\ElementInterface;
use craft\db\Paginator;
use CraftCms\Cms\Shared\BaseModel;
use CraftCms\Cms\Support\Facades\Entries;
use CraftCms\Cms\Support\Facades\HtmlStack;
use CraftCms\Cms\Support\Facades\Twig;
use CraftCms\Cms\Twig\TwigExceptionMapper;
use CraftCms\Cms\Twig\Variables\Paginate;
use CraftCms\Cms\View\Enums\Position;
use Illuminate\Support\Facades\Auth;
use Stringable;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Extension\CoreExtension;
use Twig\Extension\SandboxExtension;
use Twig\Markup;
use Twig\Source;
use Twig\Template as TwigTemplate;
use Twig\TemplateWrapper;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\base\UnknownMethodException;
use yii\base\UnknownPropertyException;
use yii\db\Query;
use yii\db\QueryInterface;

/**
 * Class Template
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Template
{
    public const PROFILE_TYPE_TEMPLATE = 'template';
    public const PROFILE_TYPE_BLOCK = 'block';
    public const PROFILE_TYPE_MACRO = 'macro';

    public const PROFILE_STAGE_BEGIN = 'begin';
    public const PROFILE_STAGE_END = 'end';

    /**
     * @var bool Whether to enable profiling for this request
     * @see _shouldProfile()
     */
    private static bool $_shouldProfile;

    /**
     * @var array Counters for template elements being profiled
     * @see beginProfile()
     * @see endProfile()
     */
    private static array $_profileCounters;

    /**
     * @var array Dynamically-defined fallback variables
     * @see fallbackExists()
     * @see fallback()
     */
    private static array $_fallbacks = [];

    /**
     * Returns whether a fallback variable has been defined.
     *
     * @param string $name
     * @return bool
     * @since 4.4.0
     * @deprecated in 5.9.15
     */
    public static function fallbackExists(string $name): bool
    {
        return isset(self::$_fallbacks[$name]);
    }

    /**
     * Provides dynamically-defined fallback variable’s value.
     *
     * @param string $name
     * @throws UnknownPropertyException if `$name` isn’t defined as a fallback variable.
     * @since 4.4.0
     * @deprecated in 5.9.15
     */
    public static function fallback(string $name): mixed
    {
        if (!static::fallbackExists($name)) {
            throw new UnknownPropertyException("$name is not defined as a fallback template variable.");
        }
        return self::$_fallbacks[$name];
    }

    /**
     * Resolves a template variable from the context, falling back to preloaded singles.
     *
     * Used by {@see \CraftCms\Cms\Twig\Nodes\FallbackNameExpression} to consolidate
     * variable resolution with fallback support into a single runtime call.
     *
     * @param string $name The variable name
     * @param array $context The Twig template context
     * @param bool $strict Whether strict variables mode is enabled
     * @param int $lineno The template line number (for error reporting)
     * @param Source|null $source The template source (for error reporting)
     * @since 6.0.0
     */
    public static function resolveVariable(string $name, array $context, bool $strict, int $lineno = -1, ?Source $source = null): mixed
    {
        if (isset($context[$name]) || array_key_exists($name, $context)) {
            return $context[$name];
        }

        if (static::fallbackExists($name)) {
            return static::fallback($name);
        }

        if ($strict) {
            throw new RuntimeError("Variable \"$name\" does not exist.", $lineno, $source);
        }

        return null;
    }

    /**
     * Checks whether a template variable exists in the context or as a fallback.
     *
     * @param string $name The variable name
     * @param array $context The Twig template context
     * @since 6.0.0
     */
    public static function variableExists(string $name, array $context): bool
    {
        return array_key_exists($name, $context) || static::fallbackExists($name);
    }

    /**
     * Returns the attribute value for a given array/object.
     *
     * @param Environment $env
     * @param Source $source
     * @param mixed $object The object or array from where to get the item
     * @param mixed $item The item to get from the array or object
     * @param array $arguments An array of arguments to pass if the item is an object method
     * @param string $type The type of attribute (@see [[TwigTemplate]] constants)
     * @param bool $isDefinedTest Whether this is only a defined check
     * @param bool $ignoreStrictCheck Whether to ignore the strict attribute check or not
     * @param bool $sandboxed Whether sandboxing is enabled
     * @param int $lineno The template line where the attribute was called
     * @return mixed The attribute value, or a Boolean when $isDefinedTest is true, or null when the attribute is not set and $ignoreStrictCheck is true
     * @throws RuntimeError if the attribute does not exist and Twig is running in strict mode and $isDefinedTest is false
     * @internal
     */
    public static function attribute(
        Environment $env,
        Source $source,
        mixed $object,
        mixed $item,
        array $arguments = [],
        string $type = TwigTemplate::ANY_CALL,
        bool $isDefinedTest = false,
        bool $ignoreStrictCheck = false,
        bool $sandboxed = false,
        int $lineno = -1,
    ): mixed {
        // Include this element in any active caches
        if ($object instanceof ElementInterface) {
            Craft::$app->getElements()->collectCacheInfoForElement($object);
        }

        if (
            $type !== TwigTemplate::METHOD_CALL &&
            $object instanceof BaseObject &&
            $object->canGetProperty($item)
        ) {
            if ($isDefinedTest) {
                return true;
            }
            if ($sandboxed) {
                $env->getExtension(SandboxExtension::class)->checkPropertyAllowed($object, $item, $lineno, $source);
            }
            return $object->$item;
        }

        if (
            $type !== TwigTemplate::METHOD_CALL &&
            $object instanceof BaseModel &&
            $object->hasAttribute($item)
        ) {
            if ($isDefinedTest) {
                return true;
            }
            if ($sandboxed) {
                $env->getExtension(SandboxExtension::class)->checkPropertyAllowed($object, $item, $lineno, $source);
            }
            return $object->$item;
        }

        // Convert any \Twig\Markup arguments back to strings (unless the class *extends* \Twig\Markup)
        foreach ($arguments as $key => $value) {
            if (is_object($value) && get_class($value) === Markup::class) {
                $arguments[$key] = (string)$value;
            }
        }

        try {
            // workaround for https://github.com/twigphp/Twig/issues/4701
            if ($type !== TwigTemplate::METHOD_CALL && $item instanceof Stringable) {
                $item = (string) $item;
            }

            return CoreExtension::getAttribute(
                $env,
                $source,
                $object,
                $item,
                $arguments,
                $type,
                $isDefinedTest,
                $ignoreStrictCheck,
                $sandboxed,
                $lineno,
            );
        } catch (UnknownMethodException $e) {
            // Copy twig_get_attribute()'s BadMethodCallException handling
            if ($ignoreStrictCheck || !$env->isStrictVariables()) {
                return null;
            }
            throw new RuntimeError($e->getMessage(), -1, $source);
        }
    }

    /**
     * Paginates a query.
     *
     * @param QueryInterface $query
     * @return array
     * @deprecated in 3.6.0. Use [[paginateQuery()]] instead.
     */
    public static function paginateCriteria(QueryInterface $query): array
    {
        return static::paginateQuery($query);
    }

    /**
     * Paginates a query.
     *
     * @param QueryInterface $query
     * @return array
     * @since 3.6.0
     */
    public static function paginateQuery(QueryInterface $query): array
    {
        /** @var Query $query */
        $paginator = new Paginator((clone $query)->limit(null), [
            'currentPage' => Craft::$app->getRequest()->getPageNum(),
            'pageSize' => $query->limit ?: 100,
        ]);

        return [
            Paginate::create($paginator),
            $paginator->getPageResults(),
        ];
    }

    /**
     * Returns a string wrapped in a \Twig\Markup object
     *
     * @param string $value
     * @return Markup
     */
    public static function raw(string $value): Markup
    {
        return new Markup($value, Craft::$app->charset);
    }

    /**
     * Begins profiling a template element.
     *
     * @param string $type The type of template element being profiled ('template', 'block', or 'macro')
     * @param string $name The name of the template element
     * @since 3.3.0
     */
    public static function beginProfile(string $type, string $name): void
    {
        if (!self::_shouldProfile()) {
            return;
        }

        if (!isset(self::$_profileCounters[$type][$name])) {
            $count = self::$_profileCounters[$type][$name] = 1;
        } else {
            $count = ++self::$_profileCounters[$type][$name];
        }

        Craft::beginProfile(self::_profileToken($type, $name, $count), 'Twig template');
    }

    /**
     * Finishes profiling a template element.
     *
     * @param string $type The type of template element being profiled ('template', 'block', or 'macro')
     * @param string $name The name of the template element
     * @since 3.3.0
     */
    public static function endProfile(string $type, string $name): void
    {
        if (!self::_shouldProfile()) {
            return;
        }

        $count = self::$_profileCounters[$type][$name]--;
        Craft::endProfile(self::_profileToken($type, $name, $count), 'Twig template');
    }

    /**
     * Returns whether to profile the given template element.
     *
     * @return bool Whether to profile it
     */
    private static function _shouldProfile(): bool
    {
        if (isset(self::$_shouldProfile)) {
            return self::$_shouldProfile;
        }

        if (app()->hasDebugModeEnabled()) {
            return self::$_shouldProfile = true;
        }

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return self::$_shouldProfile = $user->admin && $user->getPreference('profileTemplates');
    }

    /**
     * Returns the token name that should be used for a template profile.
     *
     * @param string $type
     * @param string $name
     * @param int $count
     * @return string
     */
    private static function _profileToken(string $type, string $name, int $count): string
    {
        return "render $type: $name" . ($count === 1 ? '' : " ($count)");
    }

    /**
     * Registers a CSS file or a CSS code block.
     *
     * @param string $css the CSS file URL, or the content of the CSS code block to be registered
     * @param array $options the HTML attributes for the `<link>`/`<style>` tag.
     * @param string|null $key the key that identifies the CSS code block. If null, it will use
     * `$css` as the key. If two CSS code blocks are registered with the same key, the latter
     * will overwrite the former.
     * @throws InvalidConfigException
     * @since 3.5.6
     */
    public static function css(string $css, array $options = [], ?string $key = null): void
    {
        // Is this a CSS file?
        if (preg_match('/^[^\r\n]+\.css(\.gz)?$/i', $css) || UrlHelper::isAbsoluteUrl($css)) {
            HtmlStack::cssFile($css, $options, $key);
        } else {
            HtmlStack::css($css, $options, $key);
        }
    }

    public static function html(string $html, int|Position $position = Position::BodyEnd): void
    {
        if (is_int($position)) {
            $position = Position::from($position);
        }

        HtmlStack::html($html, $position);
    }

    /**
     * Registers a JS file or a JS code block.
     *
     * @param string $js the JS file URL, or the content of the JS code block to be registered
     * @param array $options the HTML attributes for the `<script>` tag.
     * @param string|null $key the key that identifies the JS code block. If null, it will use
     * $css as the key. If two JS code blocks are registered with the same key, the latter
     * will overwrite the former.
     * @throws InvalidConfigException
     * @since 3.5.6
     */
    public static function js(string $js, array $options = [], ?string $key = null): void
    {
        // Is this a JS file?
        if (preg_match('/^[^\r\n]+\.js(\.gz)?$/i', $js) || UrlHelper::isAbsoluteUrl($js)) {
            HtmlStack::jsFile($js, $options, $key);
        } else {
            $position = Position::tryFrom($options['position']) ?? Position::BodyEnd;
            HtmlStack::js($js, $position, $key);
        }
    }

    public static function script(string $script, int|Position $position = Position::BodyEnd): void
    {
        if (is_int($position)) {
            $position = Position::from($position);
        }

        HtmlStack::script($script, $position);
    }

    /**
     * Attempts to resolve a compiled template file path and line number to its source template path and line number.
     *
     * @param string $path The compiled template path
     * @param int|null $line The line number from the compiled template
     *
     * @return array|false The resolved template path and line number, or `false` if the path couldn’t be determined.
     * If a template path could be determined but not the template line number, the line number will be null.
     * @since 4.1.5
     * @deprecated 6.0.0 use {@see TwigExceptionMapper::resolveTemplatePathAndLine()} instead.
     */
    public static function resolveTemplatePathAndLine(string $path, ?int $line)
    {
        return app(TwigExceptionMapper::class)->resolveTemplatePathAndLine($path, $line);
    }

    /**
     * Filters the template from a context array.
     *
     * Used by the `dump()` function and `dd` tags.
     *
     * @param array $context
     * @return array
     * @since 4.4.0
     */
    public static function contextWithoutTemplate(array $context): array
    {
        // Template check copied from twig_var_dump()
        return array_filter($context, fn($value) => !$value instanceof TwigTemplate && !$value instanceof TemplateWrapper);
    }

    /**
     * Preloads Single section entries as fallback values for [[fallbackValue()]]
     *
     * @param string[] $handles
     * @since 4.4.0
     */
    public static function preloadSingles(array $handles, ?array &$context = null): void
    {
        // Ignore handles that are defined Twig globals
        $globals = Twig::get()->getGlobals();
        $handles = array_diff($handles, array_keys($globals));

        if (!empty($handles)) {
            $singles = Entries::getSingleEntriesByHandle($handles);
            self::$_fallbacks += $singles;
            if ($context !== null) {
                $context += $singles;
            }
        }
    }
}
