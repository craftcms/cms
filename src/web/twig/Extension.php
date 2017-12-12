<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use Craft;
use craft\base\MissingComponentInterface;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\helpers\Template as TemplateHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Locale;
use craft\web\twig\nodevisitors\EventTagAdder;
use craft\web\twig\nodevisitors\EventTagFinder;
use craft\web\twig\nodevisitors\GetAttrAdjuster;
use craft\web\twig\tokenparsers\CacheTokenParser;
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
use yii\base\InvalidConfigException;
use yii\helpers\Markdown;

/**
 * Class Extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class Extension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    // Properties
    // =========================================================================

    /**
     * @var View|null
     */
    protected $view;

    /**
     * @var Environment|null
     */
    protected $environment;

    // Public Methods
    // =========================================================================

    /**
     * Constructor
     *
     * @param View        $view
     * @param Environment $environment
     */
    public function __construct(View $view, Environment $environment)
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
     * Returns the token parser instances to add to the existing list.
     *
     * @return array An array of Twig_TokenParserInterface or Twig_TokenParserBrokerInterface instances
     */
    public function getTokenParsers(): array
    {
        return [
            new CacheTokenParser(),
            new ExitTokenParser(),
            new HeaderTokenParser(),
            new HookTokenParser(),
            new RegisterResourceTokenParser('css', 'registerCss', true, false, false, true),
            new RegisterResourceTokenParser('js', 'registerJs', true, true, true, false),
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
            new RegisterResourceTokenParser('includeCss', 'registerCss', true, false, false, true, '{% css %}'),
            new RegisterResourceTokenParser('includeHiResCss', 'registerHiResCss', true, false, false, true, '{% css %}'),
            new RegisterResourceTokenParser('includeCssFile', 'registerCssFile', false, false, false, true, '{% do view.registerCssFile("/url/to/file.css") %}'),
            new RegisterResourceTokenParser('includeJs', 'registerJs', true, true, true, false, '{% js %}'),
            new RegisterResourceTokenParser('includeJsFile', 'registerJsFile', false, true, false, true, '{% do view.registerJsFile("/url/to/file.js") %}'),

            new RegisterResourceTokenParser('includecss', 'registerCss', true, false, false, true, '{% css %}'),
            new RegisterResourceTokenParser('includehirescss', 'registerHiResCss', true, false, false, true, '{% css %}'),
            new RegisterResourceTokenParser('includecssfile', 'registerCssFile', false, false, false, true, '{% do view.registerCssFile("/url/to/file.css") %}'),
            new RegisterResourceTokenParser('includejs', 'registerJs', true, true, true, false, '{% js %}'),
            new RegisterResourceTokenParser('includejsfile', 'registerJsFile', false, true, false, true, '{% do view.registerJsFile("/url/to/file.js") %}'),
        ];
    }

    /**
     * Returns a list of filters to add to the existing list.
     *
     * @return \Twig_SimpleFilter[] An array of filters
     */
    public function getFilters(): array
    {
        $formatter = Craft::$app->getFormatter();
        $security = Craft::$app->getSecurity();

        return [
            new \Twig_SimpleFilter('atom', [$this, 'atomFilter'], ['needs_environment' => true]),
            new \Twig_SimpleFilter('camel', [$this, 'camelFilter']),
            new \Twig_SimpleFilter('column', [ArrayHelper::class, 'getColumn']),
            new \Twig_SimpleFilter('currency', [$formatter, 'asCurrency']),
            new \Twig_SimpleFilter('date', [$this, 'dateFilter'], ['needs_environment' => true]),
            new \Twig_SimpleFilter('datetime', [$this, 'datetimeFilter'], ['needs_environment' => true]),
            new \Twig_SimpleFilter('datetime', [$formatter, 'asDatetime']),
            new \Twig_SimpleFilter('duration', [DateTimeHelper::class, 'humanDurationFromInterval']),
            new \Twig_SimpleFilter('filesize', [$formatter, 'asShortSize']),
            new \Twig_SimpleFilter('filter', 'array_filter'),
            new \Twig_SimpleFilter('filterByValue', [ArrayHelper::class, 'filterByValue']),
            new \Twig_SimpleFilter('group', [$this, 'groupFilter']),
            new \Twig_SimpleFilter('hash', [$security, 'hashData']),
            new \Twig_SimpleFilter('id', [$this->view, 'formatInputId']),
            new \Twig_SimpleFilter('index', [ArrayHelper::class, 'index']),
            new \Twig_SimpleFilter('indexOf', [$this, 'indexOfFilter']),
            new \Twig_SimpleFilter('intersect', 'array_intersect'),
            new \Twig_SimpleFilter('json_encode', [$this, 'jsonEncodeFilter']),
            new \Twig_SimpleFilter('kebab', [$this, 'kebabFilter']),
            new \Twig_SimpleFilter('lcfirst', [$this, 'lcfirstFilter']),
            new \Twig_SimpleFilter('literal', [$this, 'literalFilter']),
            new \Twig_SimpleFilter('markdown', [$this, 'markdownFilter']),
            new \Twig_SimpleFilter('md', [$this, 'markdownFilter']),
            new \Twig_SimpleFilter('namespace', [$this->view, 'namespaceInputs']),
            new \Twig_SimpleFilter('ns', [$this->view, 'namespaceInputs']),
            new \Twig_SimpleFilter('namespaceInputName', [$this->view, 'namespaceInputName']),
            new \Twig_SimpleFilter('namespaceInputId', [$this->view, 'namespaceInputId']),
            new \Twig_SimpleFilter('number', [$formatter, 'asDecimal']),
            new \Twig_SimpleFilter('parseRefs', [$this, 'parseRefsFilter']),
            new \Twig_SimpleFilter('pascal', [$this, 'pascalFilter']),
            new \Twig_SimpleFilter('percentage', [$formatter, 'asPercent']),
            new \Twig_SimpleFilter('replace', [$this, 'replaceFilter']),
            new \Twig_SimpleFilter('rss', [$this, 'rssFilter'], ['needs_environment' => true]),
            new \Twig_SimpleFilter('snake', [$this, 'snakeFilter']),
            new \Twig_SimpleFilter('time', [$this, 'timeFilter'], ['needs_environment' => true]),
            new \Twig_SimpleFilter('timestamp', [$formatter, 'asTimestamp']),
            new \Twig_SimpleFilter('translate', [$this, 'translateFilter']),
            new \Twig_SimpleFilter('t', [$this, 'translateFilter']),
            new \Twig_SimpleFilter('ucfirst', [$this, 'ucfirstFilter']),
            new \Twig_SimpleFilter('ucwords', 'ucwords'),
            new \Twig_SimpleFilter('unique', 'array_unique'),
            new \Twig_SimpleFilter('values', 'array_values'),
            new \Twig_SimpleFilter('without', [$this, 'withoutFilter']),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new \Twig_SimpleTest('instance of', function($obj, $class) {
                return $obj instanceof $class;
            }),
            new \Twig_SimpleTest('missing', function($obj) {
                return $obj instanceof MissingComponentInterface;
            }),
        ];
    }

    /**
     * Translates the given message.
     *
     * @param mixed       $message  The message to be translated.
     * @param string|null $category the message category.
     * @param array|null  $params   The parameters that will be used to replace the corresponding placeholders in the message.
     * @param string|null $language The language code (e.g. `en-US`, `en`). If this is null, the current
     *                              [[\yii\base\Application::language|application language]] will be used.
     *
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
     * @param string $string The multibyte string.
     *
     * @return string The string with the first character converted to upercase.
     */
    public function ucfirstFilter(string $string): string
    {
        return StringHelper::upperCaseFirst($string);
    }

    /**
     * Lowercases the first character of a multibyte string.
     *
     * @param string $string The multibyte string.
     *
     * @return string The string with the first character converted to lowercase.
     */
    public function lcfirstFilter(string $string): string
    {
        return StringHelper::lowercaseFirst($string);
    }

    /**
     * kebab-cases a string.
     *
     * @param string $string            The string
     * @param string $glue              The string used to glue the words together (default is a hyphen)
     * @param bool   $lower             Whether the string should be lowercased (default is true)
     * @param bool   $removePunctuation Whether punctuation marks should be removed (default is true)
     *
     * @return string The kebab-cased string
     */
    public function kebabFilter(string $string, string $glue = '-', bool $lower = true, bool $removePunctuation = true): string
    {
        return StringHelper::toKebabCase($string, $glue, $lower, $removePunctuation);
    }

    /**
     * camelCases a string.
     *
     * @param string $string The string
     *
     * @return string
     */
    public function camelFilter(string $string): string
    {
        return StringHelper::toCamelCase($string);
    }

    /**
     * PascalCases a string.
     *
     * @param string $string The string
     *
     * @return string
     */
    public function pascalFilter(string $string): string
    {
        return StringHelper::toPascalCase($string);
    }

    /**
     * snake_cases a string.
     *
     * @param string $string The string
     *
     * @return string
     */
    public function snakeFilter(string $string): string
    {
        return StringHelper::toSnakeCase($string);
    }


    /**
     * This method will JSON encode a variable. We're overriding Twig's default implementation to set some stricter
     * encoding options on text/html/xml requests.
     *
     * @param mixed    $value   The value to JSON encode.
     * @param int|null $options Either null or a bitmask consisting of JSON_HEX_QUOT, JSON_HEX_TAG, JSON_HEX_AMP,
     *                          JSON_HEX_APOS, JSON_NUMERIC_CHECK, JSON_PRETTY_PRINT, JSON_UNESCAPED_SLASHES,
     *                          JSON_FORCE_OBJECT
     * @param int      $depth   The maximum depth
     *
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
     * @param array $arr
     * @param mixed $exclude
     *
     * @return array
     */
    public function withoutFilter(array $arr, $exclude): array
    {
        $filteredArray = [];

        if (!is_array($exclude)) {
            $exclude = (array)$exclude;
        }

        foreach ($arr as $key => $value) {
            if (!in_array($value, $exclude, false)) {
                $filteredArray[$key] = $value;
            }
        }

        return $filteredArray;
    }

    /**
     * Parses a string for reference tags.
     *
     * @param string   $str
     * @param int|null $siteId
     *
     * @return \Twig_Markup
     */
    public function parseRefsFilter(string $str, int $siteId = null): \Twig_Markup
    {
        $str = Craft::$app->getElements()->parseRefs($str, $siteId);

        return TemplateHelper::raw($str);
    }

    /**
     * Replaces Twig's |replace filter, adding support for passing in separate
     * search and replace arrays.
     *
     * @param mixed $str
     * @param mixed $search
     * @param mixed $replace
     *
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
     * @param \Twig_Environment                     $env
     * @param DateTimeInterface|DateInterval|string $date      A date
     * @param string|null                           $format    The target format, null to use the default
     * @param DateTimeZone|string|false|null        $timezone  The target timezone, null to use the default, false to leave unchanged
     * @param bool                                  $translate Whether the formatted date string should be translated
     *
     * @return mixed|string
     */
    public function dateFilter(\Twig_Environment $env, $date, string $format = null, $timezone = null, bool $translate = true)
    {
        // Should we be using the app's formatter?
        if (!($date instanceof \DateInterval) && ($format === null || in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true))) {
            $date = \twig_date_converter($env, $date, $timezone);
            $value = Craft::$app->getFormatter()->asDate($date, $format);
        } else {
            $value = \twig_date_format_filter($env, $date, $format, $timezone);
        }

        if ($translate) {
            $value = DateTimeHelper::translateDate($value);
        }

        return $value;
    }

    /**
     * Converts a date to the Atom format.
     *
     * @param \Twig_Environment                 $env
     * @param DateTime|DateTimeInterface|string $date     A date
     * @param DateTimeZone|string|false|null    $timezone The target timezone, null to use the default, false to leave unchanged
     *
     * @return string The formatted date
     */
    public function atomFilter(\Twig_Environment $env, $date, $timezone = null): string
    {
        return \twig_date_format_filter($env, $date, \DateTime::ATOM, $timezone);
    }

    /**
     * Converts a date to the RSS format.
     *
     * @param \Twig_Environment                 $env
     * @param DateTime|DateTimeInterface|string $date     A date
     * @param DateTimeZone|string|false|null    $timezone The target timezone, null to use the default, false to leave unchanged
     *
     * @return string The formatted date
     */
    public function rssFilter(\Twig_Environment $env, $date, $timezone = null): string
    {
        return \twig_date_format_filter($env, $date, \DateTime::RSS, $timezone);
    }

    /**
     * Formats the value as a time.
     *
     * @param \Twig_Environment              $env
     * @param DateTimeInterface|string       $date      A date
     * @param string|null                    $format    The target format, null to use the default
     * @param DateTimeZone|string|false|null $timezone  The target timezone, null to use the default, false to leave unchanged
     * @param bool                           $translate Whether the formatted date string should be translated
     *
     * @return mixed|string
     */
    public function timeFilter(\Twig_Environment $env, $date, string $format = null, $timezone = null, bool $translate = true)
    {
        // Is this a custom PHP date format?
        if ($format !== null && !in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
            $format = StringHelper::ensureLeft($format, 'php:');
        }

        $date = \twig_date_converter($env, $date, $timezone);
        $value = Craft::$app->getFormatter()->asTime($date, $format);

        if ($translate) {
            $value = DateTimeHelper::translateDate($value);
        }

        return $value;
    }

    /**
     * Formats the value as a date+time.
     *
     * @param \Twig_Environment              $env
     * @param DateTimeInterface|string       $date      A date
     * @param string|null                    $format    The target format, null to use the default
     * @param DateTimeZone|string|false|null $timezone  The target timezone, null to use the default, false to leave unchanged
     * @param bool                           $translate Whether the formatted date string should be translated
     *
     * @return mixed|string
     */
    public function datetimeFilter(\Twig_Environment $env, $date, string $format = null, $timezone = null, bool $translate = true)
    {
        // Is this a custom PHP date format?
        if ($format !== null && !in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
            $format = StringHelper::ensureLeft($format, 'php:');
        }

        $date = \twig_date_converter($env, $date, $timezone);
        $value = Craft::$app->getFormatter()->asDatetime($date, $format);

        if ($translate) {
            $value = DateTimeHelper::translateDate($value);
        }

        return $value;
    }

    /**
     * Groups an array by a common property.
     *
     * @param array  $arr
     * @param string $item
     *
     * @return array
     */
    public function groupFilter(array $arr, string $item): array
    {
        $groups = [];

        $template = '{'.$item.'}';

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
     *
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
     * @param string $value The param value.
     *
     * @return string The escaped param value.
     */
    public function literalFilter(string $value): string
    {
        return Db::escapeParam($value);
    }

    /**
     * Parses text through Markdown.
     *
     * @param string      $markdown   The markdown text to parse
     * @param string|null $flavor     The markdown flavor to use. Can be 'original', 'gfm' (GitHub-Flavored Markdown),
     *                                'gfm-comment' (GFM with newlines converted to `<br>`s),
     *                                or 'extra' (Markdown Extra). Default is 'original'.
     * @param bool        $inlineOnly Whether to only parse inline elements, omitting any `<p>` tags.
     *
     * @return \Twig_Markup
     */
    public function markdownFilter(string $markdown, string $flavor = null, bool $inlineOnly = false): \Twig_Markup
    {
        if ($inlineOnly) {
            $html = Markdown::processParagraph($markdown, $flavor);
        } else {
            $html = Markdown::process($markdown, $flavor);
        }

        return TemplateHelper::raw($html);
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return \Twig_SimpleFunction[] An array of functions
     */
    public function getFunctions(): array
    {
        return [
            new \Twig_SimpleFunction('actionUrl', [UrlHelper::class, 'actionUrl']),
            new \Twig_SimpleFunction('cpUrl', [UrlHelper::class, 'cpUrl']),
            new \Twig_SimpleFunction('ceil', 'ceil'),
            new \Twig_SimpleFunction('className', 'get_class'),
            new \Twig_SimpleFunction('csrfInput', [$this, 'csrfInputFunction']),
            new \Twig_SimpleFunction('floor', 'floor'),
            new \Twig_SimpleFunction('redirectInput', [$this, 'redirectInputFunction']),
            new \Twig_SimpleFunction('renderObjectTemplate', [$this, 'renderObjectTemplate']),
            new \Twig_SimpleFunction('round', [$this, 'roundFunction']),
            new \Twig_SimpleFunction('shuffle', [$this, 'shuffleFunction']),
            new \Twig_SimpleFunction('siteUrl', [UrlHelper::class, 'siteUrl']),
            new \Twig_SimpleFunction('svg', [$this, 'svgFunction']),
            new \Twig_SimpleFunction('url', [UrlHelper::class, 'url']),
            // DOM event functions
            new \Twig_SimpleFunction('head', [$this->view, 'head']),
            new \Twig_SimpleFunction('beginBody', [$this->view, 'beginBody']),
            new \Twig_SimpleFunction('endBody', [$this->view, 'endBody']),
            // Deprecated functions
            new \Twig_SimpleFunction('getCsrfInput', [$this, 'getCsrfInput']),
            new \Twig_SimpleFunction('getHeadHtml', [$this, 'getHeadHtml']),
            new \Twig_SimpleFunction('getFootHtml', [$this, 'getFootHtml']),
        ];
    }

    /**
     * Returns a CSRF input wrapped in a \Twig_Markup object.
     *
     * @return \Twig_Markup|null
     */
    public function csrfInputFunction()
    {
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        if ($generalConfig->enableCsrfProtection === true) {
            return TemplateHelper::raw('<input type="hidden" name="'.$generalConfig->csrfTokenName.'" value="'.Craft::$app->getRequest()->getCsrfToken().'">');
        }

        return null;
    }

    /**
     * Returns a redirect input wrapped in a \Twig_Markup object.
     *
     * @param string $url The URL to redirect to.
     *
     * @return \Twig_Markup
     */
    public function redirectInputFunction(string $url): \Twig_Markup
    {
        return TemplateHelper::raw('<input type="hidden" name="redirect" value="'.Craft::$app->getSecurity()->hashData($url).'">');
    }

    /**
     * Rounds the given value.
     *
     * @param int|float $value
     * @param int       $precision
     * @param int       $mode
     *
     * @return int|float
     * @deprecated in 3.0. Use Twig's |round filter instead.
     */
    public function roundFunction($value, int $precision = 0, int $mode = PHP_ROUND_HALF_UP)
    {
        Craft::$app->getDeprecator()->log('round()', 'The round() function has been deprecated. Use Twig’s |round filter instead.');

        return round($value, $precision, $mode);
    }

    /**
     * @param string $template
     * @param mixed  $object
     *
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
     *
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
     * Returns the contents of a given SVG file
     *
     * @param string $svg      The SVG file path or contents
     * @param bool   $sanitize Whether the file should be sanitized first
     *
     * @return \Twig_Markup|string
     */
    public function svgFunction(string $svg, bool $sanitize = true)
    {
        // If we can't find an <svg> tag, it's probably a file path
        if (stripos($svg, '<svg') === false) {
            $svg = Craft::getAlias($svg);
            if (!is_file($svg) || !FileHelper::isSvg($svg)) {
                return '';
            }
            $svg = file_get_contents($svg);
        }

        // Sanitize?
        if ($sanitize) {
            $svg = (new Sanitizer())->sanitize($svg);
        }

        // Remove the XML declaration
        $svg = preg_replace('/<\?xml.*?\?>/', '', $svg);

        return TemplateHelper::raw($svg);
    }

    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return array An array of global variables
     */
    public function getGlobals(): array
    {
        $isInstalled = Craft::$app->getIsInstalled();
        $request = Craft::$app->getRequest();
        $generalConfig = Craft::$app->getConfig()->getGeneral();

        $globals = [
            'view' => $this->view,

            'SORT_ASC' => SORT_ASC,
            'SORT_DESC' => SORT_DESC,
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
            $globals['CraftPersonal'] = Craft::Personal;
            $globals['CraftClient'] = Craft::Client;
            $globals['CraftPro'] = Craft::Pro;
        }

        // Only set these things when Craft is installed and not being updated
        if ($isInstalled && !Craft::$app->getUpdates()->getIsCraftDbMigrationNeeded()) {
            $globals['systemName'] = Craft::$app->getInfo()->name;
            $site = Craft::$app->getSites()->currentSite;
            $globals['currentSite'] = $site;
            $globals['siteName'] = $site->name;
            $globals['siteUrl'] = $site->baseUrl;

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

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName(): string
    {
        return 'craft';
    }

    // Deprecated Methods
    // -------------------------------------------------------------------------

    /**
     * @deprecated in Craft 3.0. Use csrfInput() instead.
     * @return \Twig_Markup|null
     */
    public function getCsrfInput()
    {
        Craft::$app->getDeprecator()->log('getCsrfInput', 'getCsrfInput() has been deprecated. Use csrfInput() instead.');

        return $this->csrfInputFunction();
    }

    /**
     * @deprecated in Craft 3.0. Use head() instead.
     * @return \Twig_Markup
     */
    public function getHeadHtml(): \Twig_Markup
    {
        Craft::$app->getDeprecator()->log('getHeadHtml', 'getHeadHtml() has been deprecated. Use head() instead.');

        ob_start();
        ob_implicit_flush(false);
        $this->view->head();

        return TemplateHelper::raw(ob_get_clean());
    }

    /**
     * @deprecated in Craft 3.0. Use endBody() instead.
     * @return \Twig_Markup
     */
    public function getFootHtml(): \Twig_Markup
    {
        Craft::$app->getDeprecator()->log('getFootHtml', 'getFootHtml() has been deprecated. Use endBody() instead.');

        ob_start();
        ob_implicit_flush(false);
        $this->view->endBody();

        return TemplateHelper::raw(ob_get_clean());
    }
}
