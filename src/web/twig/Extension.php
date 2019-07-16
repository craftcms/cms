<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use craft\base\MissingComponentInterface;
use craft\base\PluginInterface;
use craft\elements\Asset;
use craft\elements\db\ElementQuery;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\helpers\Sequence;
use craft\helpers\StringHelper;
use craft\helpers\Template as TemplateHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Locale;
use craft\web\twig\nodevisitors\EventTagAdder;
use craft\web\twig\nodevisitors\EventTagFinder;
use craft\web\twig\nodevisitors\GetAttrAdjuster;
use craft\web\twig\tokenparsers\CacheTokenParser;
use craft\web\twig\tokenparsers\DdTokenParser;
use craft\web\twig\tokenparsers\ExitTokenParser;
use craft\web\twig\tokenparsers\HeaderTokenParser;
use craft\web\twig\tokenparsers\HookTokenParser;
use craft\web\twig\tokenparsers\NamespaceTokenParser;
use craft\web\twig\tokenparsers\NavTokenParser;
use craft\web\twig\tokenparsers\PaginateTokenParser;
use craft\web\twig\tokenparsers\RedirectTokenParser;
use craft\web\twig\tokenparsers\RegisterResourceTokenParser;
use craft\web\twig\tokenparsers\RequireAdminTokenParser;
use craft\web\twig\tokenparsers\RequireEditionTokenParser;
use craft\web\twig\tokenparsers\RequireLoginTokenParser;
use craft\web\twig\tokenparsers\RequirePermissionTokenParser;
use craft\web\twig\tokenparsers\SwitchTokenParser;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use enshrined\svgSanitize\Sanitizer;
use Twig\Environment as TwigEnvironment;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Markup;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Expression;
use yii\helpers\Markdown;

/**
 * Class Extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class Extension extends AbstractExtension implements GlobalsInterface
{
    // Properties
    // =========================================================================

    /**
     * @var View|null
     */
    protected $view;

    /**
     * @var TwigEnvironment|null
     */
    protected $environment;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param View $view
     * @param TwigEnvironment $environment
     */
    public function __construct(View $view, TwigEnvironment $environment)
    {
        $this->view = $view;
        $this->environment = $environment;
    }

    /**
     * @inheritdoc
     */
    public function getNodeVisitors()
    {
        return [
            new GetAttrAdjuster(),
            new EventTagFinder(),
            new EventTagAdder(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getTokenParsers(): array
    {
        return [
            new CacheTokenParser(),
            new DdTokenParser(),
            new ExitTokenParser(),
            new HeaderTokenParser(),
            new HookTokenParser(),
            new RegisterResourceTokenParser('css', 'registerCss', [
                'allowTagPair' => true,
                'allowOptions' => true,
            ]),
            new RegisterResourceTokenParser('js', 'registerJs', [
                'allowTagPair' => true,
                'allowPosition' => true,
                'allowRuntimePosition' => true,
            ]),
            new NamespaceTokenParser(),
            new NavTokenParser(),
            new PaginateTokenParser(),
            new RedirectTokenParser(),
            new RequireAdminTokenParser(),
            new RequireEditionTokenParser(),
            new RequireLoginTokenParser(),
            new RequirePermissionTokenParser(),
            new SwitchTokenParser(),

            // Deprecated tags
            new RegisterResourceTokenParser('includeCss', 'registerCss', [
                'allowTagPair' => true,
                'allowOptions' => true,
                'newCode' => '{% css %}',
            ]),
            new RegisterResourceTokenParser('includeHiResCss', 'registerHiResCss', [
                'allowTagPair' => true,
                'allowOptions' => true,
                'newCode' => '{% css %}',
            ]),
            new RegisterResourceTokenParser('includeCssFile', 'registerCssFile', [
                'allowOptions' => true,
                'newCode' => '{% do view.registerCssFile("/url/to/file.css") %}',
            ]),
            new RegisterResourceTokenParser('includeJs', 'registerJs', [
                'allowTagPair' => true,
                'allowPosition' => true,
                'allowRuntimePosition' => true,
                'newCode' => '{% js %}',
            ]),
            new RegisterResourceTokenParser('includeJsFile', 'registerJsFile', [
                'allowPosition' => true,
                'allowOptions' => true,
                'newCode' => '{% do view.registerJsFile("/url/to/file.js") %}',
            ]),

            new RegisterResourceTokenParser('includecss', 'registerCss', [
                'allowTagPair' => true,
                'allowOptions' => true,
                'newCode' => '{% css %}',
            ]),
            new RegisterResourceTokenParser('includehirescss', 'registerHiResCss', [
                'allowTagPair' => true,
                'allowOptions' => true,
                'newCode' => '{% css %}',
            ]),
            new RegisterResourceTokenParser('includecssfile', 'registerCssFile', [
                'allowOptions' => true,
                'newCode' => '{% do view.registerCssFile("/url/to/file.css") %}',
            ]),
            new RegisterResourceTokenParser('includejs', 'registerJs', [
                'allowTagPair' => true,
                'allowPosition' => true,
                'allowRuntimePosition' => true,
                'newCode' => '{% js %}',
            ]),
            new RegisterResourceTokenParser('includejsfile', 'registerJsFile', [
                'allowPosition' => true,
                'allowOptions' => true,
                'newCode' => '{% do view.registerJsFile("/url/to/file.js") %}',
            ]),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        $formatter = Craft::$app->getFormatter();
        $security = Craft::$app->getSecurity();

        return [
            new TwigFilter('ascii', [StringHelper::class, 'toAscii']),
            new TwigFilter('atom', [$this, 'atomFilter'], ['needs_environment' => true]),
            new TwigFilter('camel', [$this, 'camelFilter']),
            new TwigFilter('column', [ArrayHelper::class, 'getColumn']),
            new TwigFilter('currency', [$formatter, 'asCurrency']),
            new TwigFilter('date', [$this, 'dateFilter'], ['needs_environment' => true]),
            new TwigFilter('datetime', [$this, 'datetimeFilter'], ['needs_environment' => true]),
            new TwigFilter('duration', [DateTimeHelper::class, 'humanDurationFromInterval']),
            new TwigFilter('encenc', [$this, 'encencFilter']),
            new TwigFilter('filesize', [$formatter, 'asShortSize']),
            new TwigFilter('filter', [$this, 'filterFilter']),
            new TwigFilter('filterByValue', [ArrayHelper::class, 'where']),
            new TwigFilter('group', [$this, 'groupFilter']),
            new TwigFilter('hash', [$security, 'hashData']),
            new TwigFilter('id', [$this->view, 'formatInputId']),
            new TwigFilter('index', [ArrayHelper::class, 'index']),
            new TwigFilter('indexOf', [$this, 'indexOfFilter']),
            new TwigFilter('intersect', 'array_intersect'),
            new TwigFilter('json_encode', [$this, 'jsonEncodeFilter']),
            new TwigFilter('json_decode', [Json::class, 'decode']),
            new TwigFilter('kebab', [$this, 'kebabFilter']),
            new TwigFilter('lcfirst', [$this, 'lcfirstFilter']),
            new TwigFilter('literal', [$this, 'literalFilter']),
            new TwigFilter('markdown', [$this, 'markdownFilter']),
            new TwigFilter('md', [$this, 'markdownFilter']),
            new TwigFilter('multisort', [$this, 'multisortFilter']),
            new TwigFilter('namespace', [$this->view, 'namespaceInputs']),
            new TwigFilter('ns', [$this->view, 'namespaceInputs']),
            new TwigFilter('namespaceInputName', [$this->view, 'namespaceInputName']),
            new TwigFilter('namespaceInputId', [$this->view, 'namespaceInputId']),
            new TwigFilter('number', [$formatter, 'asDecimal']),
            new TwigFilter('parseRefs', [$this, 'parseRefsFilter']),
            new TwigFilter('pascal', [$this, 'pascalFilter']),
            new TwigFilter('percentage', [$formatter, 'asPercent']),
            new TwigFilter('replace', [$this, 'replaceFilter']),
            new TwigFilter('rss', [$this, 'rssFilter'], ['needs_environment' => true]),
            new TwigFilter('snake', [$this, 'snakeFilter']),
            new TwigFilter('time', [$this, 'timeFilter'], ['needs_environment' => true]),
            new TwigFilter('timestamp', [$formatter, 'asTimestamp']),
            new TwigFilter('translate', [$this, 'translateFilter']),
            new TwigFilter('t', [$this, 'translateFilter']),
            new TwigFilter('ucfirst', [$this, 'ucfirstFilter']),
            new TwigFilter('ucwords', 'ucwords'),
            new TwigFilter('unique', 'array_unique'),
            new TwigFilter('values', 'array_values'),
            new TwigFilter('without', [$this, 'withoutFilter']),
            new TwigFilter('withoutKey', [$this, 'withoutKeyFilter']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new TwigTest('instance of', function($obj, $class) {
                return $obj instanceof $class;
            }),
            new TwigTest('missing', function($obj) {
                return $obj instanceof MissingComponentInterface;
            }),
        ];
    }

    /**
     * Translates the given message.
     *
     * @param mixed $message The message to be translated.
     * @param string|null $category the message category.
     * @param array|null $params The parameters that will be used to replace the corresponding placeholders in the message.
     * @param string|null $language The language code (e.g. `en-US`, `en`). If this is null, the current
     * [[\yii\base\Application::language|application language]] will be used.
     * @return string the translated message.
     */
    public function translateFilter($message, $category = null, $params = null, $language = null): string
    {
        // The front end site doesn't need to specify the category
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        if (is_array($category)) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $language = $params;
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $params = $category;
            $category = 'site';
        } else if ($category === null) {
            $category = 'site';
        }

        if ($params === null) {
            $params = [];
        }

        try {
            return Craft::t($category, (string)$message, $params, $language);
        } catch (InvalidConfigException $e) {
            return $message;
        }
    }

    /**
     * Uppercases the first character of a multibyte string.
     *
     * @param mixed $string The multibyte string.
     * @return string The string with the first character converted to upercase.
     */
    public function ucfirstFilter($string): string
    {
        return StringHelper::upperCaseFirst((string)$string);
    }

    /**
     * Lowercases the first character of a multibyte string.
     *
     * @param mixed $string The multibyte string.
     * @return string The string with the first character converted to lowercase.
     */
    public function lcfirstFilter($string): string
    {
        return StringHelper::lowercaseFirst((string)$string);
    }

    /**
     * kebab-cases a string.
     *
     * @param mixed $string The string
     * @param string $glue The string used to glue the words together (default is a hyphen)
     * @param bool $lower Whether the string should be lowercased (default is true)
     * @param bool $removePunctuation Whether punctuation marks should be removed (default is true)
     * @return string The kebab-cased string
     */
    public function kebabFilter($string, string $glue = '-', bool $lower = true, bool $removePunctuation = true): string
    {
        return StringHelper::toKebabCase((string)$string, $glue, $lower, $removePunctuation);
    }

    /**
     * camelCases a string.
     *
     * @param mixed $string The string
     * @return string
     */
    public function camelFilter($string): string
    {
        return StringHelper::toCamelCase((string)$string);
    }

    /**
     * PascalCases a string.
     *
     * @param mixed $string The string
     * @return string
     */
    public function pascalFilter($string): string
    {
        return StringHelper::toPascalCase((string)$string);
    }

    /**
     * snake_cases a string.
     *
     * @param mixed $string The string
     * @return string
     */
    public function snakeFilter($string): string
    {
        return StringHelper::toSnakeCase((string)$string);
    }


    /**
     * This method will JSON encode a variable. We're overriding Twig's default implementation to set some stricter
     * encoding options on text/html/xml requests.
     *
     * @param mixed $value The value to JSON encode.
     * @param int|null $options Either null or a bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP,
     * JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES,
     * JSON_FORCE_OBJECT
     * @param int $depth The maximum depth
     * @return mixed The JSON encoded value.
     */
    public function jsonEncodeFilter($value, int $options = null, int $depth = 512)
    {
        if ($options === null) {
            if (in_array(Craft::$app->getResponse()->getContentType(), ['text/html', 'application/xhtml+xml'], true)) {
                $options = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT;
            } else {
                $options = 0;
            }
        }

        return json_encode($value, $options, $depth);
    }

    /**
     * Returns an array without certain values.
     *
     * @param mixed $arr
     * @param mixed $exclude
     * @return array
     */
    public function withoutFilter($arr, $exclude): array
    {
        $arr = (array)$arr;

        if (!is_array($exclude)) {
            $exclude = [$exclude];
        }

        foreach ($exclude as $value) {
            ArrayHelper::removeValue($arr, $value);
        }

        return $arr;
    }

    /**
     * Returns an array without a certain key.
     *
     * @param mixed $arr
     * @param string $key
     * @return array
     */
    public function withoutKeyFilter($arr, string $key): array
    {
        $arr = (array)$arr;
        ArrayHelper::remove($arr, $key);
        return $arr;
    }

    /**
     * Parses a string for reference tags.
     *
     * @param mixed $str
     * @param int|null $siteId
     * @return Markup
     */
    public function parseRefsFilter($str, int $siteId = null): Markup
    {
        $str = Craft::$app->getElements()->parseRefs((string)$str, $siteId);

        return TemplateHelper::raw($str);
    }

    /**
     * Replaces Twig's |replace filter, adding support for passing in separate
     * search and replace arrays.
     *
     * @param mixed $str
     * @param mixed $search
     * @param mixed $replace
     * @return mixed
     */
    public function replaceFilter($str, $search, $replace = null)
    {
        // Are they using the standard Twig syntax?
        if (is_array($search) && $replace === null) {
            return strtr($str, $search);
        }

        // Is this a regular expression?
        if (preg_match('/^\/.+\/[a-zA-Z]*$/', $search)) {
            return preg_replace($search, $replace, $str);
        }

        // Otherwise use str_replace
        return str_replace($search, $replace, $str);
    }

    /**
     * Extending Twig's |date filter so we can run any translations on the output.
     *
     * @param TwigEnvironment $env
     * @param DateTimeInterface|DateInterval|string $date A date
     * @param string|null $format The target format, null to use the default
     * @param DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     * @param string|null $locale The target locale the date should be formatted for. By default the current systme locale will be used.
     * @return mixed|string
     */
    public function dateFilter(TwigEnvironment $env, $date, string $format = null, $timezone = null, string $locale = null)
    {
        if ($date instanceof \DateInterval) {
            return \twig_date_format_filter($env, $date, $format, $timezone);
        }

        // Is this a custom PHP date format?
        if ($format !== null && !in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
            if (strpos($format, 'icu:') === 0) {
                $format = substr($format, 4);
            } else {
                $format = StringHelper::ensureLeft($format, 'php:');
            }
        }

        $date = \twig_date_converter($env, $date, $timezone);
        $formatter = $locale ? (new Locale($locale))->getFormatter() : Craft::$app->getFormatter();
        $fmtTimeZone = $formatter->timeZone;
        $formatter->timeZone = $timezone !== null ? $date->getTimezone()->getName() : $formatter->timeZone;
        $formatted = $formatter->asDate($date, $format);
        $formatter->timeZone = $fmtTimeZone;
        return $formatted;
    }

    /**
     * Converts a date to the Atom format.
     *
     * @param TwigEnvironment $env
     * @param DateTime|DateTimeInterface|string $date A date
     * @param DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     * @return string The formatted date
     */
    public function atomFilter(TwigEnvironment $env, $date, $timezone = null): string
    {
        return \twig_date_format_filter($env, $date, \DateTime::ATOM, $timezone);
    }

    /**
     * Converts a date to the RSS format.
     *
     * @param TwigEnvironment $env
     * @param DateTime|DateTimeInterface|string $date A date
     * @param DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     * @return string The formatted date
     */
    public function rssFilter(TwigEnvironment $env, $date, $timezone = null): string
    {
        return \twig_date_format_filter($env, $date, \DateTime::RSS, $timezone);
    }

    /**
     * Formats the value as a time.
     *
     * @param TwigEnvironment $env
     * @param DateTimeInterface|string $date A date
     * @param string|null $format The target format, null to use the default
     * @param DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     * @param string|null $locale The target locale the date should be formatted for. By default the current systme locale will be used.
     * @return mixed|string
     */
    public function timeFilter(TwigEnvironment $env, $date, string $format = null, $timezone = null, string $locale = null)
    {
        // Is this a custom PHP date format?
        if ($format !== null && !in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
            if (strpos($format, 'icu:') === 0) {
                $format = substr($format, 4);
            } else {
                $format = StringHelper::ensureLeft($format, 'php:');
            }
        }

        $date = \twig_date_converter($env, $date, $timezone);
        $formatter = $locale ? (new Locale($locale))->getFormatter() : Craft::$app->getFormatter();
        $fmtTimeZone = $formatter->timeZone;
        $formatter->timeZone = $timezone !== null ? $date->getTimezone()->getName() : $formatter->timeZone;
        $formatted = $formatter->asTime($date, $format);
        $formatter->timeZone = $fmtTimeZone;
        return $formatted;
    }

    /**
     * Formats the value as a date+time.
     *
     * @param TwigEnvironment $env
     * @param DateTimeInterface|string $date A date
     * @param string|null $format The target format, null to use the default
     * @param DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     * @param string|null $locale The target locale the date should be formatted for. By default the current systme locale will be used.
     * @return mixed|string
     */
    public function datetimeFilter(TwigEnvironment $env, $date, string $format = null, $timezone = null, string $locale = null)
    {
        // Is this a custom PHP date format?
        if ($format !== null && !in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
            if (strpos($format, 'icu:') === 0) {
                $format = substr($format, 4);
            } else {
                $format = StringHelper::ensureLeft($format, 'php:');
            }
        }

        $date = \twig_date_converter($env, $date, $timezone);
        $formatter = $locale ? (new Locale($locale))->getFormatter() : Craft::$app->getFormatter();
        $fmtTimeZone = $formatter->timeZone;
        $formatter->timeZone = $timezone !== null ? $date->getTimezone()->getName() : $formatter->timeZone;
        $formatted = $formatter->asDatetime($date, $format);
        $formatter->timeZone = $fmtTimeZone;
        return $formatted;
    }

    /**
     * Encrypts and base64-encodes a string.
     *
     * @param mixed $str the string
     * @return string
     */
    public function encencFilter($str): string
    {
        return StringHelper::encenc((string)$str);
    }

    /**
     * Filters an array.
     *
     * @param array|\Traversable $arr
     * @param callable|null $arrow
     * @return array
     */
    public function filterFilter($arr, $arrow = null)
    {
        if ($arrow === null) {
            return array_filter($arr);
        }

        $filtered = twig_array_filter($arr, $arrow);

        if (is_array($filtered)) {
            return $filtered;
        }

        return iterator_to_array($filtered);
    }

    /**
     * Groups an array or element query's results by a common property.
     *
     * @param array|\Traversable $arr
     * @param string $item
     * @return array
     * @throws RuntimeError if $arr is not of type array or Traversable
     */
    public function groupFilter($arr, string $item): array
    {
        if ($arr instanceof ElementQuery) {
            Craft::$app->getDeprecator()->log('ElementQuery::getIterator()', 'Looping through element queries directly has been deprecated. Use the all() function to fetch the query results before looping over them.');
            $arr = $arr->all();
        }

        if (!is_array($arr) && !$arr instanceof \Traversable) {
            throw new RuntimeError('Values passed to the |group filter must be of type array or Traversable.');
        }

        $groups = [];

        $template = '{' . $item . '}';

        foreach ($arr as $key => $object) {
            $value = Craft::$app->getView()->renderObjectTemplate($template, $object);
            $groups[$value][] = $object;
        }

        return $groups;
    }

    /**
     * Returns the index of an item in a string or array, or -1 if it cannot be found.
     *
     * @param mixed $haystack
     * @param mixed $needle
     * @return int
     */
    public function indexOfFilter($haystack, $needle): int
    {
        if (is_string($haystack)) {
            $index = strpos($haystack, $needle);
        } else if (is_array($haystack)) {
            $index = array_search($needle, $haystack, false);
        } else if (is_object($haystack) && $haystack instanceof \IteratorAggregate) {
            $index = false;

            foreach ($haystack as $i => $item) {
                if ($item == $needle) {
                    $index = $i;
                    break;
                }
            }
        }

        /** @noinspection UnSafeIsSetOverArrayInspection - FP */
        if (isset($index) && $index !== false) {
            return $index;
        }

        return -1;
    }

    /**
     * Escapes commas and asterisks in a string so they are not treated as special characters in
     * [[Db::parseParam()]].
     *
     * @param mixed $value The param value.
     * @return string The escaped param value.
     */
    public function literalFilter($value): string
    {
        return Db::escapeParam((string)$value);
    }

    /**
     * Parses text through Markdown.
     *
     * @param mixed $markdown The markdown text to parse
     * @param string|null $flavor The markdown flavor to use. Can be 'original', 'gfm' (GitHub-Flavored Markdown),
     * 'gfm-comment' (GFM with newlines converted to `<br>`s),
     * or 'extra' (Markdown Extra). Default is 'original'.
     * @param bool $inlineOnly Whether to only parse inline elements, omitting any `<p>` tags.
     * @return Markup
     */
    public function markdownFilter($markdown, string $flavor = null, bool $inlineOnly = false): Markup
    {
        if ($inlineOnly) {
            $html = Markdown::processParagraph((string)$markdown, $flavor);
        } else {
            $html = Markdown::process((string)$markdown, $flavor);
        }

        return TemplateHelper::raw($html);
    }

    /**
     * Duplicates an array and sorts it with [[\craft\helpers\ArrayHelper::multisort()]].
     *
     * @param mixed $array the array to be sorted. The array will be modified after calling this method.
     * @param string|\Closure|array $key the key(s) to be sorted by. This refers to a key name of the sub-array
     * elements, a property name of the objects, or an anonymous function returning the values for comparison
     * purpose. The anonymous function signature should be: `function($item)`.
     * To sort by multiple keys, provide an array of keys here.
     * @param int|array $direction the sorting direction. It can be either `SORT_ASC` or `SORT_DESC`.
     * When sorting by multiple keys with different sorting directions, use an array of sorting directions.
     * @param int|array $sortFlag the PHP sort flag. Valid values include
     * `SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING`, `SORT_LOCALE_STRING`, `SORT_NATURAL` and `SORT_FLAG_CASE`.
     * Please refer to [PHP manual](http://php.net/manual/en/function.sort.php)
     * for more details. When sorting by multiple keys with different sort flags, use an array of sort flags.
     * @return array the sorted array
     * @throws InvalidArgumentException if the $direction or $sortFlag parameters do not have
     * correct number of elements as that of $key.
     */
    public function multisortFilter($array, $key, $direction = SORT_ASC, $sortFlag = SORT_REGULAR): array
    {
        // Prevent multisort() from modifying the original array
        $array = array_merge($array);
        ArrayHelper::multisort($array, $key, $direction, $sortFlag);
        return $array;
    }

    /**
     * @inheritdoc
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('alias', [Craft::class, 'getAlias']),
            new TwigFunction('actionInput', [$this, 'actionInputFunction']),
            new TwigFunction('actionUrl', [UrlHelper::class, 'actionUrl']),
            new TwigFunction('attr', [$this, 'attrFunction']),
            new TwigFunction('cpUrl', [UrlHelper::class, 'cpUrl']),
            new TwigFunction('ceil', 'ceil'),
            new TwigFunction('className', 'get_class'),
            new TwigFunction('clone', [$this, 'cloneFunction']),
            new TwigFunction('create', [Craft::class, 'createObject']),
            new TwigFunction('csrfInput', [$this, 'csrfInputFunction']),
            new TwigFunction('expression', [$this, 'expressionFunction']),
            new TwigFunction('floor', 'floor'),
            new TwigFunction('getenv', 'getenv'),
            new TwigFunction('parseEnv', [Craft::class, 'parseEnv']),
            new TwigFunction('plugin', [$this, 'pluginFunction']),
            new TwigFunction('redirectInput', [$this, 'redirectInputFunction']),
            new TwigFunction('renderObjectTemplate', [$this, 'renderObjectTemplate']),
            new TwigFunction('round', [$this, 'roundFunction']),
            new TwigFunction('seq', [$this, 'seqFunction']),
            new TwigFunction('shuffle', [$this, 'shuffleFunction']),
            new TwigFunction('siteUrl', [UrlHelper::class, 'siteUrl']),
            new TwigFunction('svg', [$this, 'svgFunction']),
            new TwigFunction('url', [UrlHelper::class, 'url']),
            // DOM event functions
            new TwigFunction('head', [$this->view, 'head']),
            new TwigFunction('beginBody', [$this->view, 'beginBody']),
            new TwigFunction('endBody', [$this->view, 'endBody']),
            // Deprecated functions
            new TwigFunction('getCsrfInput', [$this, 'getCsrfInput']),
            new TwigFunction('getHeadHtml', [$this, 'getHeadHtml']),
            new TwigFunction('getFootHtml', [$this, 'getFootHtml']),
        ];
    }

    /**
     * Renders HTML tag attributes with [[\craft\helpers\Html::renderTagAttributes()]]
     *
     * @param array $attributes
     * @return Markup
     */
    public function attrFunction(array $config): Markup
    {
        return TemplateHelper::raw(Html::renderTagAttributes($config));
    }

    /**
     * Returns a CSRF input wrapped in a \Twig\Markup object.
     *
     * @return Markup|null
     */
    public function csrfInputFunction()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->enableCsrfProtection === true) {
            $request = Craft::$app->getRequest();
            return TemplateHelper::raw('<input type="hidden" name="' . $request->csrfParam . '" value="' . $request->getCsrfToken() . '">');
        }

        return null;
    }

    /**
     * Returns a clone of the given variable.
     *
     * @param mixed $var
     * @return mixed
     */
    public function cloneFunction($var)
    {
        return clone $var;
    }

    /**
     * @param mixed $expression
     * @param mixed $params
     * @param mixed $config
     * @return Expression
     */
    public function expressionFunction($expression, $params = [], $config = []): Expression
    {
        return new Expression($expression, $params, $config);
    }

    /**
     * Returns a plugin instance by its handle.
     *
     * @param string $handle The plugin handle
     * @return PluginInterface|null The plugin, or `null` if it's not installed
     */
    public function pluginFunction(string $handle)
    {
        return Craft::$app->getPlugins()->getPlugin($handle);
    }

    /**
     * Returns a redirect input wrapped in a \Twig\Markup object.
     *
     * @param string $url The URL to redirect to.
     * @return Markup
     */
    public function redirectInputFunction(string $url): Markup
    {
        return TemplateHelper::raw('<input type="hidden" name="redirect" value="' . Craft::$app->getSecurity()->hashData($url) . '">');
    }

    /**
     * Returns an action input wrapped in a \Twig\Markup object, suitable for use in a front-end form.
     *
     * @param string $actionPath
     * @return Markup
     */
    public function actionInputFunction(string $actionPath): Markup
    {
        return TemplateHelper::raw('<input type="hidden" name="action" value="' . $actionPath . '">');
    }

    /**
     * Rounds the given value.
     *
     * @param int|float $value
     * @param int $precision
     * @param int $mode
     * @return int|float
     * @deprecated in 3.0. Use Twig's |round filter instead.
     */
    public function roundFunction($value, int $precision = 0, int $mode = PHP_ROUND_HALF_UP)
    {
        Craft::$app->getDeprecator()->log('round()', 'The round() function has been deprecated. Use Twig’s |round filter instead.');

        return round($value, $precision, $mode);
    }

    /**
     * Returns the next number in a given sequence, or the current number in the sequence.
     *
     * @param string $name The sequence name.
     * @param int|null $length The minimum string length that should be returned. (Numbers that are too short will be left-padded with `0`s.)
     * @param bool $next Whether the next number in the sequence should be returned (and the sequence should be incremented).
     * If set to `false`, the current number in the sequence will be returned instead.
     * @return integer|string
     * @throws \Throwable if reasons
     * @throws \yii\db\Exception
     */
    public function seqFunction(string $name, int $length = null, bool $next = true)
    {
        if ($next) {
            return Sequence::next($name, $length);
        }
        return Sequence::current($name, $length);
    }

    /**
     * @param string $template
     * @param mixed $object
     * @return string
     */
    public function renderObjectTemplate(string $template, $object): string
    {
        return Craft::$app->getView()->renderObjectTemplate($template, $object);
    }

    /**
     * Shuffles an array.
     *
     * @param mixed $arr
     * @return mixed
     */
    public function shuffleFunction($arr)
    {
        if ($arr instanceof \Traversable) {
            $arr = iterator_to_array($arr, false);
        } else {
            $arr = array_merge($arr);
        }

        shuffle($arr);

        return $arr;
    }

    /**
     * Returns the contents of a given SVG file.
     *
     * @param string|Asset $svg An SVG asset, a file path, or raw SVG markup
     * @param bool|null $sanitize Whether the SVG should be sanitized of potentially
     * malicious scripts. By default the SVG will only be sanitized if an asset
     * or markup is passed in. (File paths are assumed to be safe.)
     * @param bool|null $namespace Whether class names and IDs within the SVG
     * should be namespaced to avoid conflicts with other elements in the DOM.
     * By default the SVG will only be namespaced if an asset or markup is passed in.
     * @param string|null $class A CSS class name that should be added to the `<svg>` element.
     * @return Markup|string
     */
    public function svgFunction($svg, bool $sanitize = null, bool $namespace = null, string $class = null)
    {
        if ($svg instanceof Asset) {
            try {
                $svg = $svg->getContents();
            } catch (\Throwable $e) {
                Craft::error("Could not get the contents of {$svg->getPath()}: {$e->getMessage()}", __METHOD__);
                Craft::$app->getErrorHandler()->logException($e);
                return '';
            }
        } else if (stripos($svg, '<svg') === false) {
            // No <svg> tag, so it's probably a file path
            $svg = Craft::getAlias($svg);
            if (!is_file($svg) || !FileHelper::isSvg($svg)) {
                Craft::warning("Could not get the contents of {$svg}: The file doesn't exist", __METHOD__);
                return '';
            }
            $svg = file_get_contents($svg);

            // This came from a file path, so pretty good chance that the SVG can be trusted.
            $sanitize = $sanitize ?? false;
            $namespace = $namespace ?? false;
        }

        // Sanitize and namespace the SVG by default
        $sanitize = $sanitize ?? true;
        $namespace = $namespace ?? true;

        // Sanitize?
        if ($sanitize) {
            $svg = (new Sanitizer())->sanitize($svg);
            // Remove comments, title & desc
            $svg = preg_replace('/<!--.*?-->\s*/s', '', $svg);
            $svg = preg_replace('/<title>.*?<\/title>\s*/is', '', $svg);
            $svg = preg_replace('/<desc>.*?<\/desc>\s*/is', '', $svg);
        }

        // Remove the XML declaration
        $svg = preg_replace('/<\?xml.*?\?>/', '', $svg);

        // Namespace class names and IDs
        if (
            $namespace && (
                strpos($svg, 'id=') !== false || strpos($svg, 'class=') !== false)
        ) {
            $ns = StringHelper::randomStringWithChars('abcdefghijklmnopqrstuvwxyz', 10) . '-';
            $ids = [];
            $classes = [];
            $svg = preg_replace_callback('/\bid=([\'"])([^\'"]+)\\1/i', function($matches) use ($ns, &$ids) {
                $ids[] = $matches[2];
                return "id={$matches[1]}{$ns}{$matches[2]}{$matches[1]}";
            }, $svg);
            $svg = preg_replace_callback('/\bclass=([\'"])([^\'"]+)\\1/i', function($matches) use ($ns, &$classes) {
                $newClasses = [];
                foreach (preg_split('/\s+/', $matches[2]) as $c) {
                    $classes[] = $c;
                    $newClasses[] = $ns . $c;
                }
                return 'class=' . $matches[1] . implode(' ', $newClasses) . $matches[1];
            }, $svg);
            foreach ($ids as $id) {
                $quotedId = preg_quote($id, '/');
                $svg = preg_replace("/#{$quotedId}\b(?!\-)/", "#{$ns}{$id}", $svg);
            }
            foreach ($classes as $c) {
                $quotedClass = preg_quote($c, '/');
                $svg = preg_replace("/\.{$quotedClass}\b(?!\-)/", ".{$ns}{$c}", $svg);
            }
        }

        if ($class !== null) {
            $svg = preg_replace('/(<svg\b[^>]+\bclass=([\'"])[^\'"]+)(\\2)/i', "$1 {$class}$3", $svg, 1, $count);
            if ($count === 0) {
                $svg = preg_replace('/<svg\b/i', "$0 class=\"{$class}\"", $svg, 1);
            }
        }

        return TemplateHelper::raw($svg);
    }

    /**
     * @inheritdoc
     */
    public function getGlobals(): array
    {
        $isInstalled = Craft::$app->getIsInstalled();
        $request = Craft::$app->getRequest();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $globals = [
            'view' => $this->view,

            'devMode' => YII_DEBUG,
            'SORT_ASC' => SORT_ASC,
            'SORT_DESC' => SORT_DESC,
            'SORT_REGULAR' => SORT_REGULAR,
            'SORT_NUMERIC' => SORT_NUMERIC,
            'SORT_STRING' => SORT_STRING,
            'SORT_LOCALE_STRING' => SORT_LOCALE_STRING,
            'SORT_NATURAL' => SORT_NATURAL,
            'SORT_FLAG_CASE' => SORT_FLAG_CASE,
            'POS_HEAD' => View::POS_HEAD,
            'POS_BEGIN' => View::POS_BEGIN,
            'POS_END' => View::POS_END,
            'POS_READY' => View::POS_READY,
            'POS_LOAD' => View::POS_LOAD,

            'isInstalled' => $isInstalled,
            'loginUrl' => UrlHelper::siteUrl($generalConfig->getLoginPath()),
            'logoutUrl' => UrlHelper::siteUrl($generalConfig->getLogoutPath()),
            'now' => new DateTime(null, new \DateTimeZone(Craft::$app->getTimeZone()))
        ];

        $globals['craft'] = new CraftVariable();

        if ($isInstalled && !$request->getIsConsoleRequest() && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
            $globals['currentUser'] = Craft::$app->getUser()->getIdentity();
        } else {
            $globals['currentUser'] = null;
        }

        $templateMode = $this->view->getTemplateMode();

        // CP-only variables
        if ($templateMode === View::TEMPLATE_MODE_CP) {
            $globals['CraftEdition'] = Craft::$app->getEdition();
            $globals['CraftSolo'] = Craft::Solo;
            $globals['CraftPro'] = Craft::Pro;
        }

        // Only set these things when Craft is installed and not being updated
        if ($isInstalled && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
            $globals['systemName'] = Craft::$app->getSystemName();
            /** @noinspection PhpUnhandledExceptionInspection */
            $site = Craft::$app->getSites()->getCurrentSite();
            $globals['currentSite'] = $site;
            $globals['siteName'] = $site->name;
            $globals['siteUrl'] = $site->getBaseUrl();

            // Global sets (site templates only)
            if ($templateMode === View::TEMPLATE_MODE_SITE) {
                foreach (Craft::$app->getGlobals()->getAllSets() as $globalSet) {
                    $globals[$globalSet->handle] = $globalSet;
                }
            }
        } else {
            $globals['systemName'] = null;
            $globals['currentSite'] = null;
            $globals['siteName'] = null;
            $globals['siteUrl'] = null;
        }

        return $globals;
    }

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /**
     * @deprecated in Craft 3.0. Use csrfInput() instead.
     * @return Markup|null
     */
    public function getCsrfInput()
    {
        Craft::$app->getDeprecator()->log('getCsrfInput', 'getCsrfInput() has been deprecated. Use csrfInput() instead.');

        return $this->csrfInputFunction();
    }

    /**
     * @deprecated in Craft 3.0. Use head() instead.
     * @return Markup
     */
    public function getHeadHtml(): Markup
    {
        Craft::$app->getDeprecator()->log('getHeadHtml', 'getHeadHtml() has been deprecated. Use head() instead.');

        ob_start();
        ob_implicit_flush(false);
        $this->view->head();

        return TemplateHelper::raw(ob_get_clean());
    }

    /**
     * @deprecated in Craft 3.0. Use endBody() instead.
     * @return Markup
     */
    public function getFootHtml(): Markup
    {
        Craft::$app->getDeprecator()->log('getFootHtml', 'getFootHtml() has been deprecated. Use endBody() instead.');

        ob_start();
        ob_implicit_flush(false);
        $this->view->endBody();

        return TemplateHelper::raw(ob_get_clean());
    }
}
