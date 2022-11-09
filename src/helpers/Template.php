<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\base\ElementInterface;
use craft\base\ExpirableElementInterface;
use craft\db\Paginator;
use craft\web\twig\variables\Paginate;
use craft\web\View;
use Twig\Environment;
use Twig\Error\RuntimeError;
use Twig\Markup;
use Twig\Source;
use Twig\Template as TwigTemplate;
use yii\base\BaseObject;
use yii\base\InvalidConfigException;
use yii\base\UnknownMethodException;
use yii\db\Query;
use yii\db\QueryInterface;
use function twig_get_attribute;

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
     * @return mixed The attribute value, or a Boolean when $isDefinedTest is true, or null when the attribute is not set and $ignoreStrictCheck is true
     * @throws RuntimeError if the attribute does not exist and Twig is running in strict mode and $isDefinedTest is false
     * @internal
     */
    public static function attribute(Environment $env, Source $source, mixed $object, mixed $item, array $arguments = [], string $type = TwigTemplate::ANY_CALL, bool $isDefinedTest = false, bool $ignoreStrictCheck = false): mixed
    {
        // Include this element in any active caches
        if ($object instanceof ElementInterface) {
            $elementsService = Craft::$app->getElements();
            if ($elementsService->getIsCollectingCacheInfo()) {
                $class = get_class($object);
                $elementsService->collectCacheTags([
                    'element',
                    "element::$class",
                    "element::$class::$object->id",
                ]);

                // If the element is expirable, register its expiry date
                if (
                    $object instanceof ExpirableElementInterface &&
                    ($expiryDate = $object->getExpiryDate()) !== null
                ) {
                    $elementsService->setCacheExpiryDate($expiryDate);
                }
            }
        }

        if (
            $type !== TwigTemplate::METHOD_CALL &&
            $object instanceof BaseObject &&
            $object->canGetProperty($item)
        ) {
            return $isDefinedTest ? true : $object->$item;
        }

        // Convert any \Twig\Markup arguments back to strings (unless the class *extends* \Twig\Markup)
        foreach ($arguments as $key => $value) {
            if (is_object($value) && get_class($value) === Markup::class) {
                $arguments[$key] = (string)$value;
            }
        }

        try {
            return twig_get_attribute($env, $source, $object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
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

        if (App::devMode()) {
            return self::$_shouldProfile = true;
        }

        $user = Craft::$app->getUser()->getIdentity();

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
        if (preg_match('/^[^\r\n]+\.css$/i', $css) || UrlHelper::isAbsoluteUrl($css)) {
            Craft::$app->getView()->registerCssFile($css, $options, $key);
        } else {
            Craft::$app->getView()->registerCss($css, $options, $key);
        }
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
        if (preg_match('/^[^\r\n]+\.js$/i', $js) || UrlHelper::isAbsoluteUrl($js)) {
            Craft::$app->getView()->registerJsFile($js, $options, $key);
        } else {
            $position = $options['position'] ?? View::POS_READY;
            Craft::$app->getView()->registerJs($js, $position, $key);
        }
    }

    /**
     * Attempts to resolve a compiled template file path and line number to its source template path and line number.
     *
     * @param string $path The compiled template path
     * @param int|null $line The line number from the compiled template
     * @return array|false The resolved template path and line number, or `false` if the path couldn’t be determined.
     * If a template path could be determined but not the template line number, the line number will be null.
     * @since 4.1.5
     */
    public static function resolveTemplatePathAndLine(string $path, ?int $line)
    {
        if (!str_contains($path, 'compiled_templates')) {
            return false;
        }

        $contents = file_get_contents($path);

        if (!preg_match('/^class (\w+)/m', $contents, $match)) {
            return false;
        }

        $class = $match[1];
        if (!class_exists($class, false) || !is_subclass_of($class, TwigTemplate::class)) {
            return false;
        }

        /** @var TwigTemplate $template */
        $template = new $class(Craft::$app->getView()->getTwig());
        $src = $template->getSourceContext();
        $templatePath = $src->getPath() ?: null;
        $templateLine = null;

        if ($line !== null) {
            foreach ($template->getDebugInfo() as $codeLine => $thisTemplateLine) {
                if ($codeLine <= $line) {
                    $templateLine = $thisTemplateLine;
                    break;
                }
            }
        }

        return [$templatePath, $templateLine];
    }
}
