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
use craft\i18n\Locale;
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

/**
 * Class Template
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Template
{
    const PROFILE_TYPE_TEMPLATE = 'template';
    const PROFILE_TYPE_BLOCK = 'block';
    const PROFILE_TYPE_MACRO = 'macro';

    const PROFILE_STAGE_BEGIN = 'begin';
    const PROFILE_STAGE_END = 'end';

    /**
     * @var bool Whether to enable profiling for this request
     * @see _shouldProfile()
     */
    private static $_shouldProfile;

    /**
     * @var array Counters for template elements being profiled
     * @see beginProfile()
     * @see endProfile()
     */
    private static $_profileCounters;

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
    public static function attribute(Environment $env, Source $source, $object, $item, array $arguments = [], string $type = TwigTemplate::ANY_CALL, bool $isDefinedTest = false, bool $ignoreStrictCheck = false)
    {
        // Include this element in any active caches
        if ($object instanceof ElementInterface) {
            $elementsService = Craft::$app->getElements();
            if ($elementsService->getIsCollectingCacheTags()) {
                $class = get_class($object);
                $elementsService->collectCacheTags([
                    'element',
                    "element::$class",
                    "element::$class::$object->id",
                ]);
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

        // Add deprecated support for the old DateTime methods
        if ($object instanceof \DateTime && ($value = self::_dateTimeAttribute($object, $item, $type)) !== false) {
            return $value;
        }

        try {
            return \twig_get_attribute($env, $source, $object, $item, $arguments, $type, $isDefinedTest, $ignoreStrictCheck);
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
        $paginatorQuery = clone $query;
        $paginator = new Paginator($paginatorQuery->limit(null), [
            'currentPage' => Craft::$app->getRequest()->getPageNum(),
            'pageSize' => $query->limit ?: 100,
        ]);

        return [
            Paginate::create($paginator),
            $paginator->getPageResults()
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
    public static function beginProfile(string $type, string $name)
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
    public static function endProfile(string $type, string $name)
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
        if (self::$_shouldProfile !== null) {
            return self::$_shouldProfile;
        }

        if (YII_DEBUG) {
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
        return "render {$type}: {$name}" . ($count === 1 ? '' : " ({$count})");
    }

    /**
     * Adds (deprecated) support for the old Craft\DateTime methods.
     *
     * @param \DateTime $object
     * @param string $item
     * @param string $type
     * @return string|false
     */
    private static function _dateTimeAttribute(\DateTime $object, string $item, string $type)
    {
        switch ($item) {
            case 'atom':
                $format = \DateTime::ATOM;
                $filter = 'atom';
                break;
            case 'cookie':
                $format = \DateTime::COOKIE;
                break;
            case 'iso8601':
                $format = \DateTime::ISO8601;
                break;
            case 'rfc822':
                $format = \DateTime::RFC822;
                break;
            case 'rfc850':
                $format = \DateTime::RFC850;
                break;
            case 'rfc1036':
                $format = \DateTime::RFC1036;
                break;
            case 'rfc1123':
                $format = \DateTime::RFC1123;
                break;
            case 'rfc2822':
                $format = \DateTime::RFC2822;
                break;
            case 'rfc3339':
                $format = \DateTime::RFC3339;
                break;
            case 'rss':
                $format = \DateTime::RSS;
                $filter = 'rss';
                break;
            case 'w3c':
                $format = \DateTime::W3C;
                break;
            case 'w3cDate':
                $format = 'Y-m-d';
                break;
            case 'mySqlDateTime':
                $format = 'Y-m-d H:i:s';
                break;
            case 'localeDate':
                $value = Craft::$app->getFormatter()->asDate($object, Locale::LENGTH_SHORT);
                $filter = 'date(\'short\')';
                break;
            case 'localeTime':
                $value = Craft::$app->getFormatter()->asTime($object, Locale::LENGTH_SHORT);
                $filter = 'time(\'short\')';
                break;
            case 'year':
                $format = 'Y';
                break;
            case 'month':
                $format = 'n';
                break;
            case 'day':
                $format = 'j';
                break;
            case 'nice':
                $value = Craft::$app->getFormatter()->asDatetime($object, Locale::LENGTH_SHORT);
                $filter = 'datetime(\'short\')';
                break;
            case 'uiTimestamp':
                $value = Craft::$app->getFormatter()->asTimestamp($object, Locale::LENGTH_SHORT);
                $filter = 'timestamp(\'short\')';
                break;
            default:
                return false;
        }

        if (isset($format)) {
            if (!isset($value)) {
                $value = $object->format($format);
            }
            if (!isset($filter)) {
                $filter = 'date(\'' . addslashes($format) . '\')';
            }
        }

        $key = "DateTime::{$item}()";
        /** @noinspection PhpUndefinedVariableInspection */
        $message = "`DateTime::{$item}" . ($type === TwigTemplate::METHOD_CALL ? '()' : '') . "` is deprecated. Use the `|{$filter}` filter instead.";

        if ($item === 'iso8601') {
            $message = rtrim($message, '.') . ', or consider using the `|atom` filter, which will give you an actual ISO-8601 string (unlike the old `.iso8601()` method).';
        }

        Craft::$app->getDeprecator()->log($key, $message);
        /** @noinspection PhpUndefinedVariableInspection */
        return $value;
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
    public static function css(string $css, array $options = [], ?string $key = null)
    {
        // Is this a CSS file?
        if (preg_match('/^[^\r\n]+\.css$/i', $css)) {
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
    public static function js(string $js, array $options = [], ?string $key = null)
    {
        // Is this a JS file?
        if (preg_match('/^[^\r\n]+\.js$/i', $js)) {
            Craft::$app->getView()->registerJsFile($js, $options, $key);
        } else {
            $position = $options['position'] ?? View::POS_READY;
            Craft::$app->getView()->registerJs($js, $position, $key);
        }
    }
}
