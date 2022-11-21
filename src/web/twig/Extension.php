<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\twig;

use CommerceGuys\Addressing\Formatter\FormatterInterface;
use Countable;
use Craft;
use craft\base\ElementInterface;
use craft\base\MissingComponentInterface;
use craft\base\PluginInterface;
use craft\elements\Address;
use craft\elements\Asset;
use craft\elements\User;
use craft\errors\AssetException;
use craft\helpers\App;
use craft\helpers\ArrayHelper;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Gql;
use craft\helpers\Html;
use craft\helpers\HtmlPurifier;
use craft\helpers\Json;
use craft\helpers\MoneyHelper;
use craft\helpers\Sequence;
use craft\helpers\StringHelper;
use craft\helpers\Template as TemplateHelper;
use craft\helpers\UrlHelper;
use craft\i18n\Locale;
use craft\web\twig\nodevisitors\EventTagAdder;
use craft\web\twig\nodevisitors\EventTagFinder;
use craft\web\twig\nodevisitors\GetAttrAdjuster;
use craft\web\twig\nodevisitors\Profiler;
use craft\web\twig\tokenparsers\CacheTokenParser;
use craft\web\twig\tokenparsers\DdTokenParser;
use craft\web\twig\tokenparsers\DeprecatedTokenParser;
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
use craft\web\twig\tokenparsers\RequireGuestTokenParser;
use craft\web\twig\tokenparsers\RequireLoginTokenParser;
use craft\web\twig\tokenparsers\RequirePermissionTokenParser;
use craft\web\twig\tokenparsers\SwitchTokenParser;
use craft\web\twig\tokenparsers\TagTokenParser;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Collection;
use IteratorAggregate;
use Money\Money;
use Throwable;
use Traversable;
use Twig\Environment as TwigEnvironment;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\db\Exception;
use yii\db\Expression;
use yii\db\QueryInterface;
use yii\helpers\Markdown;
use function twig_date_converter;
use function twig_date_format_filter;

/**
 * Class Extension
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class Extension extends AbstractExtension implements GlobalsInterface
{
    /**
     * @var View|null
     */
    protected ?View $view = null;

    /**
     * @var TwigEnvironment|null
     */
    protected ?TwigEnvironment $environment = null;

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
    public function getNodeVisitors(): array
    {
        return [
            new Profiler(),
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
            new DeprecatedTokenParser(),
            new DdTokenParser(),
            new ExitTokenParser(),
            new HeaderTokenParser(),
            new HookTokenParser(),
            new RegisterResourceTokenParser('css', TemplateHelper::class . '::css', [
                'allowTagPair' => true,
                'allowOptions' => true,
            ]),
            new RegisterResourceTokenParser('html', 'Craft::$app->getView()->registerHtml', [
                'allowTagPair' => true,
                'allowPosition' => true,
            ]),
            new RegisterResourceTokenParser('js', TemplateHelper::class . '::js', [
                'allowTagPair' => true,
                'allowPosition' => true,
                'allowRuntimePosition' => true,
                'allowOptions' => true,
            ]),
            new RegisterResourceTokenParser('script', 'Craft::$app->getView()->registerScript', [
                'allowTagPair' => true,
                'allowPosition' => true,
                'allowOptions' => true,
                'defaultPosition' => View::POS_END,
            ]),
            new NamespaceTokenParser(),
            new NavTokenParser(),
            new PaginateTokenParser(),
            new RedirectTokenParser(),
            new RequireAdminTokenParser(),
            new RequireEditionTokenParser(),
            new RequireLoginTokenParser(),
            new RequireGuestTokenParser(),
            new RequirePermissionTokenParser(),
            new SwitchTokenParser(),
            new TagTokenParser(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getFilters(): array
    {
        $security = Craft::$app->getSecurity();

        return [
            new TwigFilter('address', [$this, 'addressFilter'], ['is_safe' => ['html']]),
            new TwigFilter('append', [$this, 'appendFilter'], ['is_safe' => ['html']]),
            new TwigFilter('ascii', [StringHelper::class, 'toAscii']),
            new TwigFilter('atom', [$this, 'atomFilter'], ['needs_environment' => true]),
            new TwigFilter('attr', [$this, 'attrFilter'], ['is_safe' => ['html']]),
            new TwigFilter('boolean', 'boolval'),
            new TwigFilter('camel', [$this, 'camelFilter']),
            new TwigFilter('column', [ArrayHelper::class, 'getColumn']),
            new TwigFilter('contains', [ArrayHelper::class, 'contains']),
            new TwigFilter('currency', [$this, 'currencyFilter']),
            new TwigFilter('date', [$this, 'dateFilter'], ['needs_environment' => true]),
            new TwigFilter('datetime', [$this, 'datetimeFilter'], ['needs_environment' => true]),
            new TwigFilter('diff', 'array_diff'),
            new TwigFilter('duration', [DateTimeHelper::class, 'humanDuration']),
            new TwigFilter('encenc', [$this, 'encencFilter']),
            new TwigFilter('explodeClass', [Html::class, 'explodeClass']),
            new TwigFilter('explodeStyle', [Html::class, 'explodeStyle']),
            new TwigFilter('filesize', [$this, 'filesizeFilter']),
            new TwigFilter('filter', [$this, 'filterFilter'], ['needs_environment' => true]),
            new TwigFilter('filterByValue', [ArrayHelper::class, 'where'], ['deprecated' => '3.5.0', 'alternative' => 'where']),
            new TwigFilter('group', [$this, 'groupFilter']),
            new TwigFilter('hash', [$security, 'hashData']),
            new TwigFilter('httpdate', [$this, 'httpdateFilter'], ['needs_environment' => true]),
            new TwigFilter('id', [Html::class, 'id']),
            new TwigFilter('index', [ArrayHelper::class, 'index']),
            new TwigFilter('indexOf', [$this, 'indexOfFilter']),
            new TwigFilter('integer', 'intval'),
            new TwigFilter('intersect', 'array_intersect'),
            new TwigFilter('float', 'floatval'),
            new TwigFilter('json_encode', [$this, 'jsonEncodeFilter']),
            new TwigFilter('json_decode', [Json::class, 'decode']),
            new TwigFilter('kebab', [$this, 'kebabFilter']),
            new TwigFilter('length', [$this, 'lengthFilter'], ['needs_environment' => true]),
            new TwigFilter('lcfirst', [$this, 'lcfirstFilter']),
            new TwigFilter('literal', [$this, 'literalFilter']),
            new TwigFilter('markdown', [$this, 'markdownFilter'], ['is_safe' => ['html']]),
            new TwigFilter('md', [$this, 'markdownFilter'], ['is_safe' => ['html']]),
            new TwigFilter('merge', [$this, 'mergeFilter']),
            new TwigFilter('money', [$this, 'moneyFilter']),
            new TwigFilter('multisort', [$this, 'multisortFilter']),
            new TwigFilter('namespace', [$this->view, 'namespaceInputs'], ['is_safe' => ['html']]),
            new TwigFilter('namespaceAttributes', [Html::class, 'namespaceAttributes'], ['is_safe' => ['html']]),
            new TwigFilter('ns', [$this->view, 'namespaceInputs'], ['is_safe' => ['html']]),
            new TwigFilter('namespaceInputName', [$this->view, 'namespaceInputName']),
            new TwigFilter('namespaceInputId', [$this->view, 'namespaceInputId']),
            new TwigFilter('number', [$this, 'numberFilter']),
            new TwigFilter('parseAttr', [$this, 'parseAttrFilter']),
            new TwigFilter('parseRefs', [$this, 'parseRefsFilter'], ['is_safe' => ['html']]),
            new TwigFilter('pascal', [$this, 'pascalFilter']),
            new TwigFilter('percentage', [$this, 'percentageFilter']),
            new TwigFilter('prepend', [$this, 'prependFilter'], ['is_safe' => ['html']]),
            new TwigFilter('purify', [$this, 'purifyFilter'], ['is_safe' => ['html']]),
            new TwigFilter('push', [$this, 'pushFilter']),
            new TwigFilter('removeClass', [$this, 'removeClassFilter'], ['is_safe' => ['html']]),
            new TwigFilter('replace', [$this, 'replaceFilter']),
            new TwigFilter('rss', [$this, 'rssFilter'], ['needs_environment' => true]),
            new TwigFilter('snake', [$this, 'snakeFilter']),
            new TwigFilter('sort', [$this, 'sortFilter'], ['needs_environment' => true]),
            new TwigFilter('string', 'strval'),
            new TwigFilter('time', [$this, 'timeFilter'], ['needs_environment' => true]),
            new TwigFilter('timestamp', [$this, 'timestampFilter']),
            new TwigFilter('translate', [$this, 'translateFilter']),
            new TwigFilter('truncate', [$this, 'truncateFilter']),
            new TwigFilter('t', [$this, 'translateFilter']),
            new TwigFilter('ucfirst', [$this, 'ucfirstFilter']),
            new TwigFilter('ucwords', [$this, 'ucwordsFilter'], ['needs_environment' => true]),
            new TwigFilter('unique', 'array_unique'),
            new TwigFilter('unshift', [$this, 'unshiftFilter']),
            new TwigFilter('values', 'array_values'),
            new TwigFilter('where', [ArrayHelper::class, 'where']),
            new TwigFilter('widont', [$this, 'widontFilter'], ['is_safe' => ['html']]),
            new TwigFilter('without', [$this, 'withoutFilter']),
            new TwigFilter('withoutKey', [$this, 'withoutKeyFilter']),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getTests(): array
    {
        return [
            new TwigTest('array', function($obj): bool {
                return is_array($obj);
            }),
            new TwigTest('boolean', function($obj): bool {
                return is_bool($obj);
            }),
            new TwigTest('callable', function($obj): bool {
                return is_callable($obj);
            }),
            new TwigTest('countable', function($obj): bool {
                if (!function_exists('is_countable')) {
                    return is_array($obj) || $obj instanceof Countable;
                }
                return is_countable($obj);
            }),
            new TwigTest('float', function($obj): bool {
                return is_float($obj);
            }),
            new TwigTest('instance of', function($obj, $class) {
                return $obj instanceof $class;
            }),
            new TwigTest('integer', function($obj): bool {
                return is_int($obj);
            }),
            new TwigTest('missing', function($obj) {
                return $obj instanceof MissingComponentInterface;
            }),
            new TwigTest('numeric', function($obj): bool {
                return is_numeric($obj);
            }),
            new TwigTest('object', function($obj): bool {
                return is_object($obj);
            }),
            new TwigTest('resource', function($obj): bool {
                return is_resource($obj);
            }),
            new TwigTest('scalar', function($obj): bool {
                return is_scalar($obj);
            }),
            new TwigTest('string', function($obj): bool {
                return is_string($obj);
            }),
        ];
    }

    /**
     * @param Address|null $address
     * @param array $options
     * @param FormatterInterface|null $formatter
     * @return string
     * @since 4.0.0
     */
    public function addressFilter(?Address $address, array $options = [], FormatterInterface $formatter = null): string
    {
        if ($address === null) {
            return '';
        }

        return Craft::$app->getAddresses()->formatAddress($address, $options, $formatter);
    }

    /**
     * Outputs a value from a Money object.
     *
     * @param Money|null $money
     * @param string|null $formatLocale
     * @return string|null
     */
    public function moneyFilter(?Money $money, ?string $formatLocale = null): ?string
    {
        if ($money === null) {
            return null;
        }

        return MoneyHelper::toString($money, $formatLocale);
    }

    /**
     * Translates the given message.
     *
     * @param mixed $message The message to be translated.
     * @param string|array|null $category the message category.
     * @param array|string|null $params The parameters that will be used to replace the corresponding placeholders in the message.
     * @param string|null $language The language code (e.g. `en-US`, `en`). If this is null, the current
     * [[\yii\base\Application::language|application language]] will be used.
     * @return string the translated message.
     */
    public function translateFilter(mixed $message, mixed $category = null, mixed $params = null, ?string $language = null): string
    {
        // The front end site doesn't need to specify the category
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        if (is_array($category)) {
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $language = $params;
            /** @noinspection CallableParameterUseCaseInTypeContextInspection */
            $params = $category;
            $category = 'site';
        } elseif ($category === null) {
            $category = 'site';
        }

        if ($params === null) {
            $params = [];
        }

        try {
            return Craft::t($category, (string)$message, $params, $language);
        } catch (InvalidConfigException) {
            return $message;
        }
    }

    /**
     * Truncates the string to a given length, while ensuring that it does not split words.
     *
     * @param string $string The string to truncate
     * @param int $length The maximum number of characters for the truncated string
     * @param string $suffix The string that should be appended to `$string`, if it must be truncated
     * @param bool $splitSingleWord Whether to split up `$string` if it only contains one word
     * @return string The truncated string
     * @since 3.5.10
     */
    public function truncateFilter(string $string, int $length, string $suffix = '…', bool $splitSingleWord = true): string
    {
        // Override default behavior where the substring would be returned in this case
        if ($string === '' || $length <= 0) {
            return $string;
        }

        return StringHelper::safeTruncate($string, $length, $suffix, $splitSingleWord);
    }

    /**
     * Uppercases the first character of a multibyte string.
     *
     * @param mixed $string The multibyte string.
     * @return string The string with the first character converted to upercase.
     */
    public function ucfirstFilter(mixed $string): string
    {
        return StringHelper::upperCaseFirst((string)$string);
    }

    /**
     * Uppercases the first character of each word in a string.
     *
     * @param TwigEnvironment $env
     * @param string $string
     * @return string
     */
    public function ucwordsFilter(TwigEnvironment $env, string $string): string
    {
        Craft::$app->getDeprecator()->log('ucwords', 'The `|ucwords` filter has been deprecated. Use `|title` instead.');
        $charset = $env->getCharset();
        if ($charset) {
            return mb_convert_case($string, MB_CASE_TITLE, $charset);
        }
        return ucwords(strtolower($string));
    }

    /**
     * Lowercases the first character of a multibyte string.
     *
     * @param mixed $string The multibyte string.
     * @return string The string with the first character converted to lowercase.
     */
    public function lcfirstFilter(mixed $string): string
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
    public function kebabFilter(mixed $string, string $glue = '-', bool $lower = true, bool $removePunctuation = true): string
    {
        return StringHelper::toKebabCase((string)$string, $glue, $lower, $removePunctuation);
    }

    /**
     * camelCases a string.
     *
     * @param mixed $string The string
     * @return string
     */
    public function camelFilter(mixed $string): string
    {
        return StringHelper::toCamelCase((string)$string);
    }

    /**
     * PascalCases a string.
     *
     * @param mixed $string The string
     * @return string
     */
    public function pascalFilter(mixed $string): string
    {
        return StringHelper::toPascalCase((string)$string);
    }

    /**
     * snake_cases a string.
     *
     * @param mixed $string The string
     * @return string
     */
    public function snakeFilter(mixed $string): string
    {
        return StringHelper::toSnakeCase((string)$string);
    }

    /**
     * Sorts an array.
     *
     * @param TwigEnvironment $env
     * @param iterable $array
     * @param string|callable|null $arrow
     * @return array
     * @throws RuntimeError
     * @since 4.3.2
     */
    public function sortFilter(TwigEnvironment $env, iterable $array, string|callable|null $arrow = null): array
    {
        if (is_string($arrow) && strtolower($arrow) === 'system') {
            throw new RuntimeError('The sort filter doesn\'t support sorting by system().');
        }

        return twig_sort_filter($env, $array, $arrow);
    }


    /**
     * Formats the value as a currency number.
     *
     * @param mixed $value
     * @param string|null $currency
     * @param array $options
     * @param array $textOptions
     * @param bool $stripZeros
     * @return string
     * @since 3.6.0
     */
    public function currencyFilter(mixed $value, ?string $currency = null, array $options = [], array $textOptions = [], bool $stripZeros = false): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Craft::$app->getFormatter()->asCurrency($value, $currency, $options, $textOptions, $stripZeros);
        } catch (InvalidArgumentException) {
            return $value;
        }
    }

    /**
     * Formats the value in bytes as a size in human readable form for example `12 kB`.
     *
     * @param mixed $value
     * @param int|null $decimals
     * @param array $options
     * @param array $textOptions
     * @return string
     * @since 3.6.0
     */
    public function filesizeFilter(mixed $value, ?int $decimals = null, array $options = [], array $textOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Craft::$app->getFormatter()->asShortSize($value, $decimals, $options, $textOptions);
        } catch (InvalidArgumentException) {
            return $value;
        }
    }

    /**
     * Formats the value as a decimal number.
     *
     * @param mixed $value
     * @param int|null $decimals
     * @param array $options
     * @param array $textOptions
     * @return string
     * @since 3.6.0
     */
    public function numberFilter(mixed $value, ?int $decimals = null, array $options = [], array $textOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Craft::$app->getFormatter()->asDecimal($value, $decimals, $options, $textOptions);
        } catch (InvalidArgumentException) {
            return $value;
        }
    }

    /**
     * Formats the value as a percent number with "%" sign.
     *
     * @param mixed $value
     * @param int|null $decimals
     * @param array $options
     * @param array $textOptions
     * @return string
     * @since 3.6.0
     */
    public function percentageFilter(mixed $value, ?int $decimals = null, array $options = [], array $textOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Craft::$app->getFormatter()->asPercent($value, $decimals, $options, $textOptions);
        } catch (InvalidArgumentException) {
            return $value;
        }
    }

    /**
     * Formats the value as a human-readable timestamp.
     *
     * @param mixed $value
     * @param string|null $format
     * @param bool $withPreposition
     * @return string
     * @since 3.6.0
     */
    public function timestampFilter(mixed $value, ?string $format = null, bool $withPreposition = false): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return Craft::$app->getFormatter()->asTimestamp($value, $format, $withPreposition);
        } catch (InvalidArgumentException) {
            return $value;
        }
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
     * @return string|false The JSON encoded value.
     */
    public function jsonEncodeFilter(mixed $value, ?int $options = null, int $depth = 512): string|false
    {
        if ($options === null) {
            if (
                !Craft::$app->getRequest()->getIsConsoleRequest() &&
                in_array(Craft::$app->getResponse()->getContentType(), ['text/html', 'application/xhtml+xml'], true)
            ) {
                $options = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT;
            } else {
                $options = 0;
            }
        }

        return json_encode($value, $options, $depth);
    }

    /**
     * Inserts a non-breaking space between the last two words of a string.
     *
     * @param string $string
     * @return string
     * @since 3.7.0
     */
    public function widontFilter(string $string): string
    {
        return Html::widont($string);
    }

    /**
     * Returns an array without certain values.
     *
     * @param mixed $arr
     * @param mixed $exclude
     * @param bool $strict
     * @return array
     */
    public function withoutFilter(mixed $arr, mixed $exclude, bool $strict = false): array
    {
        $arr = (array)$arr;

        if (!is_array($exclude)) {
            $exclude = [$exclude];
        }

        foreach ($exclude as $value) {
            ArrayHelper::removeValue($arr, $value, $strict);
        }

        return $arr;
    }

    /**
     * Returns an array without a certain key.
     *
     * @param mixed $arr
     * @param string|string[] $key
     * @return array
     * @since 3.2.0
     */
    public function withoutKeyFilter(mixed $arr, array|string $key): array
    {
        $arr = (array)$arr;

        if (!is_array($key)) {
            $key = [$key];
        }

        foreach ($key as $k) {
            ArrayHelper::remove($arr, $k);
        }

        return $arr;
    }

    /**
     * Parses an HTML tag to find its attributes.
     *
     * @param string $tag The HTML tag to parse
     * @return array The parsed HTML tag attributes
     * @throws InvalidArgumentException if `$tag` doesn't contain a valid HTML tag
     * @since 3.4.0
     */
    public function parseAttrFilter(string $tag): array
    {
        try {
            return Html::parseTagAttributes($tag, 0, $start, $end, true);
        } catch (InvalidArgumentException $e) {
            Craft::warning($e->getMessage(), __METHOD__);
            return [];
        }
    }

    /**
     * Parses a string for reference tags.
     *
     * @param mixed $str
     * @param int|null $siteId
     * @return string
     */
    public function parseRefsFilter(mixed $str, ?int $siteId = null): string
    {
        return Craft::$app->getElements()->parseRefs((string)$str, $siteId);
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
    public function prependFilter(string $tag, string $html, ?string $ifExists = null): string
    {
        try {
            return Html::prependToTag($tag, $html, $ifExists);
        } catch (InvalidArgumentException $e) {
            Craft::warning($e->getMessage(), __METHOD__);
            return $tag;
        }
    }

    /**
     * Purifies the given HTML using HTML Purifier.
     *
     * @param string $html The HTML to be purified
     * @param string|array|null $config The HTML Purifier config. This can either be the name of a JSON file within
     * `config/htmlpurifier/` (sans `.json` extension) or a config array.
     * @return string The purified HTML
     * @since 3.4.0
     */
    public function purifyFilter(string $html, array|string|null $config = null): string
    {
        if (is_string($config)) {
            $path = Craft::$app->getPath()->getConfigPath() . DIRECTORY_SEPARATOR . 'htmlpurifier' .
                DIRECTORY_SEPARATOR . $config . '.json';
            $config = null;
            if (!is_file($path)) {
                Craft::warning("No HTML Purifier config found at $path.");
            } else {
                try {
                    $config = Json::decode(file_get_contents($path));
                } catch (InvalidArgumentException) {
                    Craft::warning("Invalid HTML Purifier config at $path.");
                }
            }
        }

        return HtmlPurifier::process($html, $config);
    }

    /**
     * Pushes one or more items onto the end of an array.
     *
     * @param array $array
     * @return array
     * @since 3.5.0
     */
    public function pushFilter(array $array): array
    {
        $args = func_get_args();
        array_shift($args);
        array_push($array, ...$args);
        return $array;
    }

    /**
     * Prepends one or more items to the beginning of an array.
     *
     * @param array $array
     * @return array
     * @since 3.5.0
     */
    public function unshiftFilter(array $array): array
    {
        $args = func_get_args();
        array_shift($args);
        array_unshift($array, ...$args);
        return $array;
    }

    /**
     * Removes a class (or classes) from the given HTML tag.
     *
     * @param string $tag The HTML tag to modify
     * @param string|string[] $class
     * @return string The modified HTML tag
     * @since 3.7.0
     */
    public function removeClassFilter(string $tag, array|string $class): string
    {
        try {
            $oldClasses = Html::parseTagAttributes($tag)['class'] ?? [];
            $newClasses = array_filter($oldClasses, function(string $oldClass) use ($class) {
                return is_string($class) ? $oldClass !== $class : !in_array($oldClass, $class, true);
            });

            $newTag = Html::modifyTagAttributes($tag, ['class' => false]);
            if (!empty($newClasses)) {
                $newTag = Html::modifyTagAttributes($newTag, ['class' => $newClasses]);
            }
            return $newTag;
        } catch (InvalidArgumentException $e) {
            Craft::warning($e->getMessage(), __METHOD__);
            return $tag;
        }
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
    public function replaceFilter(mixed $str, mixed $search, mixed $replace = null): mixed
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
     * @param string|null $locale The target locale the date should be formatted for. By default the current system locale will be used.
     * @return string
     */
    public function dateFilter(TwigEnvironment $env, mixed $date, ?string $format = null, mixed $timezone = null, ?string $locale = null): string
    {
        if ($date instanceof DateInterval) {
            return twig_date_format_filter($env, $date, $format, $timezone);
        }

        // Is this a custom PHP date format?
        if ($format !== null && !in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
            if (str_starts_with($format, 'icu:')) {
                $format = substr($format, 4);
            } else {
                $format = StringHelper::ensureLeft($format, 'php:');
            }
        }

        $date = twig_date_converter($env, $date, $timezone);
        $formatter = $locale ? (new Locale($locale))->getFormatter() : Craft::$app->getFormatter();
        $fmtTimeZone = $formatter->timeZone;
        $formatter->timeZone = $timezone !== null ? $date->getTimezone()->getName() : $formatter->timeZone;
        $formatted = $formatter->asDate(DateTime::createFromInterface($date), $format);
        $formatter->timeZone = $fmtTimeZone;
        return $formatted;
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
    public function appendFilter(string $tag, string $html, ?string $ifExists = null): string
    {
        try {
            return Html::appendToTag($tag, $html, $ifExists);
        } catch (InvalidArgumentException $e) {
            Craft::warning($e->getMessage(), __METHOD__);
            return $tag;
        }
    }

    /**
     * Converts a date to the Atom format.
     *
     * @param TwigEnvironment $env
     * @param DateTime|DateTimeInterface|string $date A date
     * @param DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     * @return string The formatted date
     */
    public function atomFilter(TwigEnvironment $env, mixed $date, mixed $timezone = null): string
    {
        return twig_date_format_filter($env, $date, DateTime::ATOM, $timezone);
    }

    /**
     * Modifies a HTML tag’s attributes, supporting the same attribute definitions as [[Html::renderTagAttributes()]].
     *
     * @param string $tag The HTML tag whose attributes should be modified.
     * @param array $attributes The attributes to be added to the tag.
     * @return string The modified HTML tag.
     * @since 3.3.0
     */
    public function attrFilter(string $tag, array $attributes): string
    {
        try {
            return Html::modifyTagAttributes($tag, $attributes);
        } catch (InvalidArgumentException $e) {
            Craft::warning($e->getMessage(), __METHOD__);
            return $tag;
        }
    }

    /**
     * Converts a date to the RSS format.
     *
     * @param TwigEnvironment $env
     * @param DateTime|DateTimeInterface|string $date A date
     * @param DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     * @return string The formatted date
     */
    public function rssFilter(TwigEnvironment $env, mixed $date, mixed $timezone = null): string
    {
        return twig_date_format_filter($env, $date, DateTime::RSS, $timezone);
    }

    /**
     * Formats the value as a time.
     *
     * @param TwigEnvironment $env
     * @param DateTimeInterface|string $date A date
     * @param string|null $format The target format, null to use the default
     * @param DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     * @param string|null $locale The target locale the date should be formatted for. By default the current systme locale will be used.
     * @return string
     */
    public function timeFilter(TwigEnvironment $env, mixed $date, ?string $format = null, mixed $timezone = null, ?string $locale = null): string
    {
        // Is this a custom PHP date format?
        if ($format !== null && !in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
            if (str_starts_with($format, 'icu:')) {
                $format = substr($format, 4);
            } else {
                $format = StringHelper::ensureLeft($format, 'php:');
            }
        }

        $date = twig_date_converter($env, $date, $timezone);
        $formatter = $locale ? (new Locale($locale))->getFormatter() : Craft::$app->getFormatter();
        $fmtTimeZone = $formatter->timeZone;
        $formatter->timeZone = $timezone !== null ? $date->getTimezone()->getName() : $formatter->timeZone;
        $formatted = $formatter->asTime(DateTime::createFromInterface($date), $format);
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
     * @return string
     */
    public function datetimeFilter(TwigEnvironment $env, mixed $date, ?string $format = null, mixed $timezone = null, ?string $locale = null): string
    {
        // Is this a custom PHP date format?
        if ($format !== null && !in_array($format, [Locale::LENGTH_SHORT, Locale::LENGTH_MEDIUM, Locale::LENGTH_LONG, Locale::LENGTH_FULL], true)) {
            if (str_starts_with($format, 'icu:')) {
                $format = substr($format, 4);
            } else {
                $format = StringHelper::ensureLeft($format, 'php:');
            }
        }

        $date = twig_date_converter($env, $date, $timezone);
        $formatter = $locale ? (new Locale($locale))->getFormatter() : Craft::$app->getFormatter();
        $fmtTimeZone = $formatter->timeZone;
        $formatter->timeZone = $timezone !== null ? $date->getTimezone()->getName() : $formatter->timeZone;
        $formatted = $formatter->asDatetime(DateTime::createFromInterface($date), $format);
        $formatter->timeZone = $fmtTimeZone;
        return $formatted;
    }

    /**
     * Encrypts and base64-encodes a string.
     *
     * @param mixed $str the string
     * @return string
     */
    public function encencFilter(mixed $str): string
    {
        return StringHelper::encenc((string)$str);
    }

    /**
     * Filters an array.
     *
     * @param TwigEnvironment $env
     * @param iterable $arr
     * @param callable|null $arrow
     * @return array
     */
    public function filterFilter(TwigEnvironment $env, iterable $arr, ?callable $arrow = null): array
    {
        /** @var array|Traversable $arr */
        if ($arrow === null) {
            if ($arr instanceof Traversable) {
                $arr = iterator_to_array($arr);
            }
            return array_filter($arr);
        }

        $filtered = twig_array_filter($env, $arr, $arrow);

        if (is_array($filtered)) {
            return $filtered;
        }

        return iterator_to_array($filtered);
    }

    /**
     * Groups an array by a the results of an arrow function, or value of a property.
     *
     * @param iterable $arr
     * @param callable|string $arrow The arrow function or property name that determines the group the item should be grouped in
     * @return array[] The grouped items
     * @throws RuntimeError if $arr is not of type array or Traversable
     */
    public function groupFilter(iterable $arr, callable|string $arrow): array
    {
        $groups = [];

        if (!is_string($arrow) && is_callable($arrow)) {
            foreach ($arr as $key => $item) {
                $groupKey = (string)$arrow($item, $key);
                $groups[$groupKey][] = $item;
            }
        } else {
            $template = '{' . $arrow . '}';
            $view = Craft::$app->getView();
            foreach ($arr as $item) {
                $groupKey = $view->renderObjectTemplate($template, $item);
                $groups[$groupKey][] = $item;
            }
        }

        return $groups;
    }


    /**
     * Converts a date to the HTTP format (used by HTTP headers such as `Expires`).
     *
     * @param TwigEnvironment $env
     * @param DateTime|DateTimeInterface|string $date A date
     * @param DateTimeZone|string|false|null $timezone The target timezone, null to use the default, false to leave unchanged
     * @return string The formatted date
     * @since 3.6.10
     */
    public function httpdateFilter(TwigEnvironment $env, mixed $date, mixed $timezone = null): string
    {
        return twig_date_format_filter($env, $date, DateTime::RFC7231, $timezone);
    }


    /**
     * Returns the index of an item in a string or array, or -1 if it cannot be found.
     *
     * @param mixed $haystack
     * @param mixed $needle
     * @return int
     */
    public function indexOfFilter(mixed $haystack, mixed $needle): int
    {
        if (is_string($haystack)) {
            $index = strpos($haystack, $needle);
        } elseif (is_array($haystack)) {
            $index = array_search($needle, $haystack, false);
        } elseif (is_object($haystack) && $haystack instanceof IteratorAggregate) {
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
     * Returns the length of an array, or the total result count of a query.
     *
     * @param TwigEnvironment $env
     * @param mixed $value A variable
     * @return int The length of the value
     * @since 4.2.0
     */
    public function lengthFilter(TwigEnvironment $env, mixed $value): int
    {
        if ($value instanceof QueryInterface) {
            return $value->count();
        }

        return twig_length_filter($env, $value);
    }

    /**
     * Escapes commas and asterisks in a string so they are not treated as special characters in
     * [[Db::parseParam()]].
     *
     * @param mixed $value The param value.
     * @return string The escaped param value.
     */
    public function literalFilter(mixed $value): string
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
     * @return string
     */
    public function markdownFilter(mixed $markdown, ?string $flavor = null, bool $inlineOnly = false): string
    {
        if ($inlineOnly) {
            return Markdown::processParagraph((string)$markdown, $flavor);
        }

        return Markdown::process((string)$markdown, $flavor);
    }

    /**
     * Merges an array with another one.
     *
     * @param iterable $arr1 An array
     * @param iterable $arr2 An array
     * @param bool $recursive Whether the arrays should be merged recursively using [[\yii\helpers\BaseArrayHelper::merge()]]
     * @return array The merged array
     * @since 3.4.0
     */
    public function mergeFilter(iterable $arr1, iterable $arr2, bool $recursive = false): array
    {
        if ($arr1 instanceof Traversable) {
            $arr1 = iterator_to_array($arr1);
        }

        if ($arr2 instanceof Traversable) {
            $arr2 = iterator_to_array($arr2);
        }

        if ($recursive) {
            return ArrayHelper::merge($arr1, $arr2);
        }

        return twig_array_merge($arr1, $arr2);
    }

    /**
     * Duplicates an array and sorts it with [[\craft\helpers\ArrayHelper::multisort()]].
     *
     * @param mixed $array the array to be sorted. The array will be modified after calling this method.
     * @param string|callable|array $key the key(s) to be sorted by. This refers to a key name of the sub-array
     * elements, a property name of the objects, or an anonymous function returning the values for comparison
     * purpose. The anonymous function signature should be: `function($item)`.
     * To sort by multiple keys, provide an array of keys here.
     * @param int|array $direction the sorting direction. It can be either `SORT_ASC` or `SORT_DESC`.
     * When sorting by multiple keys with different sorting directions, use an array of sorting directions.
     * @param int|array $sortFlag the PHP sort flag. Valid values include
     * `SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING`, `SORT_LOCALE_STRING`, `SORT_NATURAL` and `SORT_FLAG_CASE`.
     * Please refer to [PHP manual](https://php.net/manual/en/function.sort.php)
     * for more details. When sorting by multiple keys with different sort flags, use an array of sort flags.
     * @return array the sorted array
     * @throws InvalidArgumentException if the $direction or $sortFlag parameters do not have
     * correct number of elements as that of $key.
     */
    public function multisortFilter(mixed $array, mixed $key, int|array $direction = SORT_ASC, int|array $sortFlag = SORT_REGULAR): array
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
            new TwigFunction('actionUrl', [UrlHelper::class, 'actionUrl']),
            new TwigFunction('alias', [Craft::class, 'getAlias']),
            new TwigFunction('ceil', 'ceil'),
            new TwigFunction('className', 'get_class'),
            new TwigFunction('clone', [$this, 'cloneFunction']),
            new TwigFunction('collect', [$this, 'collectFunction']),
            new TwigFunction('combine', 'array_combine'),
            new TwigFunction('configure', [Craft::class, 'configure']),
            new TwigFunction('cpUrl', [UrlHelper::class, 'cpUrl']),
            new TwigFunction('create', [Craft::class, 'createObject']),
            new TwigFunction('dataUrl', [$this, 'dataUrlFunction']),
            new TwigFunction('date', [$this, 'dateFunction'], ['needs_environment' => true]),
            new TwigFunction('expression', [$this, 'expressionFunction']),
            new TwigFunction('floor', 'floor'),
            new TwigFunction('getenv', [App::class, 'env']),
            new TwigFunction('gql', [$this, 'gqlFunction']),
            new TwigFunction('parseEnv', [App::class, 'parseEnv']),
            new TwigFunction('parseBooleanEnv', [App::class, 'parseBooleanEnv']),
            new TwigFunction('plugin', [$this, 'pluginFunction']),
            new TwigFunction('raw', [TemplateHelper::class, 'raw']),
            new TwigFunction('renderObjectTemplate', [$this, 'renderObjectTemplate']),
            new TwigFunction('seq', [$this, 'seqFunction']),
            new TwigFunction('shuffle', [$this, 'shuffleFunction']),
            new TwigFunction('siteUrl', [UrlHelper::class, 'siteUrl']),
            new TwigFunction('url', [UrlHelper::class, 'url']),

            // Element authorization functions
            new TwigFunction('canCreateDrafts', fn(ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canCreateDrafts($element, $user)),
            new TwigFunction('canDelete', fn(ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canDelete($element, $user)),
            new TwigFunction('canDeleteForSite', fn(ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canDeleteForSite($element, $user)),
            new TwigFunction('canDuplicate', fn(ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canDuplicate($element, $user)),
            new TwigFunction('canSave', fn(ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canSave($element, $user)),
            new TwigFunction('canView', fn(ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canView($element, $user)),

            // HTML generation functions
            new TwigFunction('actionInput', [Html::class, 'actionInput'], ['is_safe' => ['html']]),
            new TwigFunction('attr', [Html::class, 'renderTagAttributes'], ['is_safe' => ['html']]),
            new TwigFunction('csrfInput', [Html::class, 'csrfInput'], ['is_safe' => ['html']]),
            new TwigFunction('failMessageInput', [Html::class, 'failMessageInput'], ['is_safe' => ['html']]),
            new TwigFunction('hiddenInput', [Html::class, 'hiddenInput'], ['is_safe' => ['html']]),
            new TwigFunction('input', [Html::class, 'input'], ['is_safe' => ['html']]),
            new TwigFunction('ol', [Html::class, 'ol'], ['is_safe' => ['html']]),
            new TwigFunction('redirectInput', [Html::class, 'redirectInput'], ['is_safe' => ['html']]),
            new TwigFunction('successMessageInput', [Html::class, 'successMessageInput'], ['is_safe' => ['html']]),
            new TwigFunction('svg', [$this, 'svgFunction'], ['is_safe' => ['html']]),
            new TwigFunction('tag', [$this, 'tagFunction'], ['is_safe' => ['html']]),
            new TwigFunction('ul', [Html::class, 'ul'], ['is_safe' => ['html']]),

            // DOM event functions
            new TwigFunction('head', [$this->view, 'head']),
            new TwigFunction('beginBody', [$this->view, 'beginBody']),
            new TwigFunction('endBody', [$this->view, 'endBody']),
        ];
    }

    /**
     * Returns a clone of the given variable.
     *
     * @param mixed $var
     * @return mixed
     */
    public function cloneFunction(mixed $var): mixed
    {
        return clone $var;
    }

    /**
     * Returns a new collection.
     *
     * @param mixed $var
     * @return Collection
     * @since 4.0.0
     */
    public function collectFunction(mixed $var): Collection
    {
        return Collection::make($var);
    }

    /**
     * Generates a base64-encoded [data URL](https://developer.mozilla.org/en-US/docs/Web/HTTP/Basics_of_HTTP/Data_URIs) for the given file path or asset.
     *
     * @param string|Asset $file A file path on an asset
     * @param string|null $mimeType The file’s MIME type. If `null` then it will be determined automatically.
     * @return string The data URL
     * @throws InvalidConfigException if `$file` is an invalid file path, or an asset with a missing/invalid volume ID
     * @throws AssetException if a stream could not be created for the asset
     * @since 3.5.13
     */
    public function dataUrlFunction(Asset|string $file, ?string $mimeType = null): string
    {
        if ($file instanceof Asset) {
            return $file->getDataUrl();
        }

        return Html::dataUrl(Craft::getAlias($file), $mimeType);
    }

    /**
     * Converts an input to a [[\DateTime]] instance.
     *
     * @param TwigEnvironment $env
     * @param DateTimeInterface|string|array|null $date A date, or null to use the current time
     * @param DateTimeZone|string|false|null $timezone The target timezone, `null` to use the default, `false` to leave unchanged
     * @return DateTimeInterface
     */
    public function dateFunction(TwigEnvironment $env, mixed $date = null, mixed $timezone = null): DateTimeInterface
    {
        // Support for date/time arrays
        if (is_array($date)) {
            $date = DateTimeHelper::toDateTime($date, false, false);
            if ($date === false) {
                throw new InvalidArgumentException('Invalid date passed to date() function');
            }
        }

        return twig_date_converter($env, $date, $timezone);
    }

    /**
     * @param mixed $expression
     * @param array $params
     * @param array $config
     * @return Expression
     * @since 3.1.0
     */
    public function expressionFunction(mixed $expression, array $params = [], array $config = []): Expression
    {
        return new Expression($expression, $params, $config);
    }

    /**
     * Executes a GraphQL query against the full schema.
     *
     * @param string $query The GraphQL query
     * @param array|null $variables Query variables
     * @param string|null $operationName The operation name
     * @return array The query result
     * @since 3.3.12
     */
    public function gqlFunction(string $query, ?array $variables = null, ?string $operationName = null): array
    {
        $schema = Gql::createFullAccessSchema();
        return Craft::$app->getGql()->executeQuery($schema, $query, $variables, $operationName);
    }

    /**
     * Returns a plugin instance by its handle.
     *
     * @param string $handle The plugin handle
     * @return PluginInterface|null The plugin, or `null` if it’s not installed
     * @since 3.1.0
     */
    public function pluginFunction(string $handle): ?PluginInterface
    {
        return Craft::$app->getPlugins()->getPlugin($handle);
    }

    /**
     * Returns the next number in a given sequence, or the current number in the sequence.
     *
     * @param string $name The sequence name.
     * @param int|null $length The minimum string length that should be returned. (Numbers that are too short will be left-padded with `0`s.)
     * @param bool $next Whether the next number in the sequence should be returned (and the sequence should be incremented).
     * If set to `false`, the current number in the sequence will be returned instead.
     * @return int|string
     * @throws Throwable if reasons
     * @throws Exception
     * @since 3.0.31
     */
    public function seqFunction(string $name, ?int $length = null, bool $next = true): int|string
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
    public function renderObjectTemplate(string $template, mixed $object): string
    {
        return Craft::$app->getView()->renderObjectTemplate($template, $object);
    }

    /**
     * Shuffles an array.
     *
     * @param iterable $arr
     * @return array
     */
    public function shuffleFunction(iterable $arr): array
    {
        /** @var array|Traversable $arr */
        if ($arr instanceof Traversable) {
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
     * (This argument is deprecated. The `|attr` filter should be used instead.)
     * @return string
     */
    public function svgFunction(Asset|string $svg, ?bool $sanitize = null, ?bool $namespace = null, ?string $class = null): string
    {
        $svg = Html::svg($svg, $sanitize, $namespace);

        if ($class !== null) {
            Craft::$app->getDeprecator()->log('svg()-class', 'The `class` argument of the `svg()` Twig function has been deprecated. The `|attr` filter should be used instead.');
            try {
                $svg = Html::modifyTagAttributes($svg, [
                    'class' => $class,
                ]);
            } catch (InvalidArgumentException $e) {
                Craft::warning('Unable to add a class to the SVG: ' . $e->getMessage(), __METHOD__);
            }
        }

        return $svg;
    }

    /**
     * Generates a complete HTML tag.
     *
     * @param string $type the tag type ('p', 'div', etc.)
     * @param array $attributes the HTML tag attributes in terms of name-value pairs.
     * If `text` is supplied, the value will be HTML-encoded and included as the contents of the tag.
     * If 'html' is supplied, the value will be included as the contents of the tag, without getting encoded.
     * @return string
     * @since 3.3.0
     */
    public function tagFunction(string $type, array $attributes = []): string
    {
        $html = ArrayHelper::remove($attributes, 'html', '');
        $text = ArrayHelper::remove($attributes, 'text');

        if ($text !== null) {
            $html = Html::encode($text);
        }

        return Html::tag($type, $html, $attributes);
    }

    /**
     * Registers global variables.
     */
    public function getGlobals(): array
    {
        $isInstalled = Craft::$app->getIsInstalled();
        $generalConfig = Craft::$app->getConfig()->getGeneral();
        $setPasswordRequestPath = $generalConfig->getSetPasswordRequestPath();

        if ($isInstalled && !Craft::$app->getUpdates()->getIsCraftUpdatePending()) {
            /** @noinspection PhpUnhandledExceptionInspection */
            $currentSite = Craft::$app->getSites()->getCurrentSite();

            $currentUser = Craft::$app->getUser()->getIdentity();
            $siteName = Craft::t('site', $currentSite->getName());
            $siteUrl = $currentSite->getBaseUrl();
            $systemName = Craft::$app->getSystemName();
        } else {
            $currentSite = $currentUser = $siteName = $siteUrl = $systemName = null;
        }

        return [
            'craft' => new CraftVariable(),
            'currentSite' => $currentSite,
            'currentUser' => $currentUser,
            'siteName' => $siteName,
            'siteUrl' => $siteUrl,
            'systemName' => $systemName,
            'view' => $this->view,

            'devMode' => App::devMode(),
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
            'setPasswordUrl' => $setPasswordRequestPath !== null ? UrlHelper::siteUrl($setPasswordRequestPath) : null,
            'now' => DateTimeHelper::now(),
            'today' => DateTimeHelper::today(),
            'tomorrow' => DateTimeHelper::tomorrow(),
            'yesterday' => DateTimeHelper::yesterday(),
        ];
    }
}
