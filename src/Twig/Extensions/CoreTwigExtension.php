<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use CommerceGuys\Addressing\Formatter\FormatterInterface;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Contracts\MissingComponentInterface;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\Gql\GqlHelper;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Gql;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Money as MoneyHelper;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Sequence;
use CraftCms\Cms\Support\Template as TemplateHelper;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Twig\Nodes\Expressions\Binaries\HasEveryBinary;
use CraftCms\Cms\Twig\Nodes\Expressions\Binaries\HasSomeBinary;
use CraftCms\Cms\Twig\NodeVisitors\EventTagAdder;
use CraftCms\Cms\Twig\NodeVisitors\EventTagFinder;
use CraftCms\Cms\Twig\NodeVisitors\GetAttrAdjuster;
use CraftCms\Cms\Twig\NodeVisitors\Profiler;
use CraftCms\Cms\Twig\TokenParsers\CacheTokenParser;
use CraftCms\Cms\Twig\TokenParsers\DdTokenParser;
use CraftCms\Cms\Twig\TokenParsers\DeprecatedTokenParser;
use CraftCms\Cms\Twig\TokenParsers\DumpTokenParser;
use CraftCms\Cms\Twig\TokenParsers\ExitTokenParser;
use CraftCms\Cms\Twig\TokenParsers\ExpiresTokenParser;
use CraftCms\Cms\Twig\TokenParsers\HeaderTokenParser;
use CraftCms\Cms\Twig\TokenParsers\HookTokenParser;
use CraftCms\Cms\Twig\TokenParsers\NamespaceTokenParser;
use CraftCms\Cms\Twig\TokenParsers\NavTokenParser;
use CraftCms\Cms\Twig\TokenParsers\PaginateTokenParser;
use CraftCms\Cms\Twig\TokenParsers\RedirectTokenParser;
use CraftCms\Cms\Twig\TokenParsers\RegisterResourceTokenParser;
use CraftCms\Cms\Twig\TokenParsers\RequireAdminTokenParser;
use CraftCms\Cms\Twig\TokenParsers\RequireEditionTokenParser;
use CraftCms\Cms\Twig\TokenParsers\RequireGuestTokenParser;
use CraftCms\Cms\Twig\TokenParsers\RequireLoginTokenParser;
use CraftCms\Cms\Twig\TokenParsers\RequirePermissionTokenParser;
use CraftCms\Cms\Twig\TokenParsers\SwitchTokenParser;
use CraftCms\Cms\Twig\TokenParsers\TagTokenParser;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\PageLifecycle;
use CraftCms\Cms\View\TemplateGlobals;
use DirectoryIterator;
use DOMDocument;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\FnStream;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Imagick;
use InvalidArgumentException;
use Money\Money;
use mysqli;
use Override;
use PDO;
use Reflector;
use SimpleXMLElement;
use SoapClient;
use Symfony\Component\Process\Process;
use Throwable;
use Twig\Environment as TwigEnvironment;
use Twig\ExpressionParser\Infix\BinaryOperatorExpressionParser;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\Extension\GlobalsInterface;
use Twig\Node\Expression\Filter\DefaultFilter;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use XMLReader;
use XSLTProcessor;
use yii\behaviors\AttributeTypecastBehavior;

use function CraftCms\Cms\craftAsset;
use function CraftCms\Cms\currentUser;
use function CraftCms\Cms\renderObjectTemplate;
use function CraftCms\Cms\t;

class CoreTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly PageLifecycle $pageLifecycle,
    ) {}

    public static function arraySome(TwigEnvironment $env, mixed $array, mixed $arrow, bool $isSandboxed = false): mixed
    {
        CoreExtension::checkArrow($isSandboxed, $arrow, 'has some', 'operator');

        return CoreExtension::arraySome($env, $array, $arrow, $isSandboxed);
    }

    public static function arrayEvery(TwigEnvironment $env, mixed $array, mixed $arrow, bool $isSandboxed = false): mixed
    {
        CoreExtension::checkArrow($isSandboxed, $arrow, 'has every', 'operator');

        return CoreExtension::arrayEvery($env, $array, $arrow, $isSandboxed);
    }

    #[Override]
    public function getNodeVisitors(): array
    {
        $eventTagAdder = new EventTagAdder($this->pageLifecycle);

        return [
            new Profiler,
            new GetAttrAdjuster,
            new EventTagFinder($eventTagAdder),
            $eventTagAdder,
        ];
    }

    #[Override]
    public function getTokenParsers(): array
    {
        return [
            new CacheTokenParser,
            new DeprecatedTokenParser,
            new DdTokenParser,
            new DumpTokenParser,
            new ExitTokenParser,
            new ExpiresTokenParser,
            new HeaderTokenParser,
            new HookTokenParser,
            new RegisterResourceTokenParser('css', TemplateHelper::class.'::css', [
                'allowTagPair' => true,
                'allowOptions' => true,
            ]),
            new RegisterResourceTokenParser('html', TemplateHelper::class.'::html', [
                'allowTagPair' => true,
                'allowPosition' => true,
            ]),
            new RegisterResourceTokenParser('js', TemplateHelper::class.'::js', [
                'allowTagPair' => true,
                'allowPosition' => true,
                'allowRuntimePosition' => true,
                'allowOptions' => true,
            ]),
            new RegisterResourceTokenParser('script', TemplateHelper::class.'::script', [
                'allowTagPair' => true,
                'allowPosition' => true,
                'allowOptions' => true,
                'defaultPosition' => Position::BodyEnd->value,
            ]),
            new NamespaceTokenParser,
            new NavTokenParser,
            new PaginateTokenParser,
            new RedirectTokenParser,
            new RequireAdminTokenParser,
            new RequireEditionTokenParser,
            new RequireLoginTokenParser,
            new RequireGuestTokenParser,
            new RequirePermissionTokenParser,
            new SwitchTokenParser,
            new TagTokenParser,
        ];
    }

    #[Override]
    public function getTests(): array
    {
        return [
            new TwigTest('array', fn ($obj): bool => is_array($obj)),
            new TwigTest('boolean', fn ($obj): bool => is_bool($obj)),
            new TwigTest('callable', fn ($obj): bool => is_callable($obj)),
            new TwigTest('countable', fn ($obj): bool => is_countable($obj)),
            new TwigTest('empty', function ($obj): bool {
                if ($obj instanceof Component) {
                    // assume the IteratorAggregate implementation was not intentional
                    return false;
                }

                return CoreExtension::testEmpty($obj);
            }),
            new TwigTest('float', fn ($obj): bool => is_float($obj)),
            new TwigTest('instance of', fn ($obj, $class) => $obj instanceof $class),
            new TwigTest('integer', fn ($obj): bool => is_int($obj)),
            new TwigTest('missing', fn ($obj) => $obj instanceof MissingComponentInterface),
            new TwigTest('numeric', fn ($obj): bool => is_numeric($obj)),
            new TwigTest('object', fn ($obj): bool => is_object($obj)),
            new TwigTest('resource', fn ($obj): bool => is_resource($obj)),
            new TwigTest('scalar', fn ($obj): bool => is_scalar($obj)),
            new TwigTest('string', fn ($obj): bool => is_string($obj)),
        ];
    }

    /** @return list<BinaryOperatorExpressionParser> */
    #[Override]
    public function getExpressionParsers(): array
    {
        return [
            new BinaryOperatorExpressionParser(HasSomeBinary::class, 'has some', 20),
            new BinaryOperatorExpressionParser(HasEveryBinary::class, 'has every', 20),
        ];
    }

    public function getGlobals(): array
    {
        $globals = app(TemplateGlobals::class)->resolve();

        return array_merge($globals, [
            // Twig-only: convenience constants (PHP devs access these directly in Blade)
            'SORT_ASC' => SORT_ASC,
            'SORT_DESC' => SORT_DESC,
            'SORT_REGULAR' => SORT_REGULAR,
            'SORT_NUMERIC' => SORT_NUMERIC,
            'SORT_STRING' => SORT_STRING,
            'SORT_LOCALE_STRING' => SORT_LOCALE_STRING,
            'SORT_NATURAL' => SORT_NATURAL,
            'SORT_FLAG_CASE' => SORT_FLAG_CASE,
            'PHP_INT_MAX' => PHP_INT_MAX,
            // Twig-only: asset injection positions
            'POS_HEAD' => Position::Head->value,
            'POS_BEGIN' => Position::BodyBegin->value,
            'POS_END' => Position::BodyEnd->value,
            'POS_READY' => Position::Ready->value,
            'POS_LOAD' => Position::Load->value,
        ]);
    }

    #[Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('address', $this->addressFilter(...), ['is_safe' => ['html']]),
            new TwigFilter('base64_decode', 'base64_decode'),
            new TwigFilter('base64_encode', 'base64_encode'),
            new TwigFilter('boolean', 'boolval'),
            new TwigFilter('currency', $this->currencyFilter(...)),
            new TwigFilter('default', $this->defaultFilter(...), ['node_class' => DefaultFilter::class]),
            new TwigFilter('filesize', $this->filesizeFilter(...)),
            new TwigFilter('float', 'floatval'),
            new TwigFilter('integer', 'intval'),
            new TwigFilter('json_encode', $this->jsonEncodeFilter(...)),
            new TwigFilter('json_decode', Json::decode(...)),
            new TwigFilter('length', $this->lengthFilter(...), ['needs_environment' => true]),
            new TwigFilter('literal', $this->literalFilter(...)),
            new TwigFilter('money', $this->moneyFilter(...)),
            new TwigFilter('number', $this->numberFilter(...)),
            new TwigFilter('percentage', $this->percentageFilter(...)),
            new TwigFilter('string', 'strval'),
            new TwigFilter('translate', $this->translateFilter(...)),
            new TwigFilter('t', $this->translateFilter(...)),
        ];
    }

    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('actionUrl', Url::actionUrl(...)),
            new TwigFunction('alias', Aliases::get(...)),
            new TwigFunction('asset', asset(...)),
            new TwigFunction('ceil', 'ceil'),
            new TwigFunction('className', 'get_class'),
            new TwigFunction('clone', $this->cloneFunction(...)),
            new TwigFunction('configure', Typecast::configure(...)),
            new TwigFunction('cpUrl', Url::cpUrl(...)),
            new TwigFunction('craftAsset', craftAsset(...)),
            new TwigFunction('create', $this->createFunction(...)),
            new TwigFunction('dump', $this->dumpFunction(...), ['is_safe' => ['html'], 'needs_context' => true, 'is_variadic' => true]),
            new TwigFunction('dd', dd(...)),
            new TwigFunction('encodeUrl', Url::encodeUrl(...)),
            new TwigFunction('entryType', $this->entryTypeFunction(...)),
            new TwigFunction('expression', $this->expressionFunction(...)),
            new TwigFunction('fieldValueSql', $this->fieldValueSqlFunction(...)),
            new TwigFunction('floor', 'floor'),
            new TwigFunction('getenv', Env::get(...)),
            new TwigFunction('gql', $this->gqlFunction(...)),
            new TwigFunction('parseEnv', Env::parse(...)),
            new TwigFunction('parseBooleanEnv', Env::parseBoolean(...)),
            new TwigFunction('plugin', $this->pluginFunction(...)),
            new TwigFunction('raw', TemplateHelper::raw(...)),
            new TwigFunction('renderObjectTemplate', $this->renderObjectTemplate(...)),
            new TwigFunction('seq', $this->seqFunction(...)),
            new TwigFunction('siteUrl', Url::siteUrl(...)),
            new TwigFunction('url', Url::url(...)),

            new TwigFunction('addresses', fn (array $config = []) => new AddressQuery($config)),
            new TwigFunction('assets', fn (array $config = []) => new AssetQuery($config)),
            new TwigFunction('contentBlocks', fn (array $config = []) => new ContentBlockQuery($config)),
            new TwigFunction('elements', fn (string $elementType = Element::class, array $config = []) => new ElementQuery($elementType, $config)),
            new TwigFunction('entries', fn (array $config = []) => new EntryQuery($config)),
            new TwigFunction('users', fn (array $config = []) => new UserQuery($config)),

            new TwigFunction('canCreateDrafts', fn (ElementInterface $element, ?User $user = null) => ($user ?? currentUser())?->can('createDrafts', $element)),
            new TwigFunction('canDelete', fn (ElementInterface $element, ?User $user = null) => ($user ?? currentUser())?->can('delete', $element)),
            new TwigFunction('canDeleteForSite', fn (ElementInterface $element, ?User $user = null) => ($user ?? currentUser())?->can('deleteForSite', $element)),
            new TwigFunction('canDuplicate', fn (ElementInterface $element, ?User $user = null) => ($user ?? currentUser())?->can('duplicate', $element)),
            new TwigFunction('canSave', fn (ElementInterface $element, ?User $user = null) => ($user ?? currentUser())?->can('save', $element)),
            new TwigFunction('canView', fn (ElementInterface $element, ?User $user = null) => ($user ?? currentUser())?->can('view', $element)),

            new TwigFunction('head', $this->pageLifecycle->head(...)),
            new TwigFunction('beginBody', $this->pageLifecycle->beginBody(...)),
            new TwigFunction('endBody', $this->pageLifecycle->endBody(...)),
        ];
    }

    /** @param array<string, mixed> $options */
    public function addressFilter(?Address $address, array $options = [], ?FormatterInterface $formatter = null): string
    {
        if ($address === null) {
            return '';
        }

        return app(Addresses::class)->formatAddress($address, $options, $formatter);
    }

    public function moneyFilter(?Money $money, ?string $locale = null): ?string
    {
        if ($money === null) {
            return null;
        }

        return MoneyHelper::toString($money, $locale);
    }

    public function translateFilter(mixed $message, mixed $category = null, mixed $params = null, ?string $language = null): string
    {
        if (is_array($category)) {
            $language = $params;
            $params = $category;
            $category = 'site';
        } elseif ($category === null) {
            $category = 'site';
        }

        if ($params === null) {
            $params = [];
        }

        return t((string) $message, $params, $category, $language);
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $textOptions
     */
    public function currencyFilter(mixed $value, ?string $currency = null, array $options = [], array $textOptions = [], bool $stripZeros = false): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return I18N::getFormatter()->asCurrency($value, $currency, $stripZeros);
        } catch (Throwable) {
            return $value;
        }
    }

    /**
     * Returns the passed-in value if it’s not empty; otherwise, the provided default value.
     */
    public static function defaultFilter(mixed $value, mixed $default = ''): mixed
    {
        if ($value instanceof Component) {
            // assume the IteratorAggregate implementation was not intentional
            return $value;
        }

        if (CoreExtension::testEmpty($value)) {
            return $default;
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $textOptions
     */
    public function filesizeFilter(mixed $value, ?int $decimals = null, array $options = [], array $textOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return I18N::getFormatter()->asShortSize($value, $decimals);
        } catch (Throwable) {
            return $value;
        }
    }

    /**
     * @param  array<int, int>  $options
     * @param  array<string, mixed>  $textOptions
     */
    public function numberFilter(mixed $value, ?int $decimals = null, array $options = [], array $textOptions = [], ?string $locale = null): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $formatter = $locale
            ? I18N::getLocaleById($locale)->getFormatter()
            : I18N::getFormatter();

        try {
            if (! $formatter->willBeMisrepresented($value)) {
                return $formatter->asDecimal($value, $decimals, $options);
            }
        } catch (Throwable) {
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $textOptions
     */
    public function percentageFilter(mixed $value, ?int $decimals = null, array $options = [], array $textOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return I18N::getFormatter()->asPercent($value, $decimals);
        } catch (Throwable) {
            return $value;
        }
    }

    public function jsonEncodeFilter(mixed $value, ?int $options = null, int $depth = 512): string|false
    {
        if ($options === null) {
            if (
                ! app()->runningInConsole() &&
                ! request()->wantsJson()
            ) {
                $options = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT;
            } else {
                $options = 0;
            }
        }

        return Json::encode($value, $options, $depth);
    }

    public function lengthFilter(TwigEnvironment $env, mixed $value): int
    {
        if ($value instanceof Builder) {
            return $value->count();
        }

        return CoreExtension::length($env->getCharset(), $value);
    }

    public function literalFilter(mixed $value): string
    {
        return Query::escapeParam((string) $value);
    }

    public function cloneFunction(mixed $var): mixed
    {
        return clone $var;
    }

    /**
     * @param  string|array<string, mixed>  $type
     * @param  array<string, mixed>  $params
     */
    public function createFunction(string|array $type, array $params = []): object
    {
        if (is_array($type) && isset($type['__class']) && isset($type['class'])) {
            throw new InvalidArgumentException('`__class` and `class` cannot both be specified.');
        }

        $class = is_string($type) ? $type : ($type['__class'] ?? $type['class'] ?? null);

        if (! $class) {
            throw new InvalidArgumentException('No class specified for create().');
        }

        foreach ([
            /** @phpstan-ignore-next-line */
            AttributeTypecastBehavior::class,
            DirectoryIterator::class,
            DOMDocument::class,
            XMLReader::class,
            XSLTProcessor::class,
            SoapClient::class,
            GuzzleClient::class,
            PDO::class,
            mysqli::class,
            Imagick::class,
            Process::class,
            FnStream::class,
            SimpleXMLElement::class,
            Reflector::class,
        ] as $blockedClass) {
            if (is_a($class, $blockedClass, true)) {
                throw new InvalidArgumentException(sprintf('create() cannot be used to create instances of %s.', $class));
            }
        }

        if (str_starts_with(Str::lower(ltrim($class, '\\')), 'spl')) {
            throw new InvalidArgumentException(sprintf('create() cannot be used to create instances of %s.', $class));
        }

        if (str_ends_with(Str::lower(rtrim($class, '\\')), 'iterator')) {
            throw new InvalidArgumentException(sprintf('create() cannot be used to create instances of %s.', $class));
        }

        $object = app()->make($class, $params);

        if (! is_object($object)) {
            throw new InvalidArgumentException("Unable to create an instance of $class.");
        }

        if (! is_array($type)) {
            return $object;
        }

        unset($type['__class'], $type['class']);

        return Typecast::configure($object, $type);
    }

    /** @param array<string, mixed> $context */
    public function dumpFunction(array $context, mixed ...$vars): string
    {
        if (! $vars) {
            $vars = [TemplateHelper::contextWithoutTemplate($context)];
        }

        $output = '';

        foreach ($vars as $var) {
            ob_start();
            dump($var);
            $output .= str_replace('<code>', '<code style="display:block;">', ob_get_clean());
        }

        return $output;
    }

    public function entryTypeFunction(string $handle): EntryType
    {
        $entryType = EntryTypes::getEntryTypeByHandle($handle);

        if ($entryType === null) {
            throw new InvalidArgumentException("Invalid entry type handle: $handle");
        }

        return $entryType;
    }

    public function expressionFunction(mixed $expression): Expression
    {
        return new \Illuminate\Database\Query\Expression($expression);
    }

    public function fieldValueSqlFunction(FieldLayoutProviderInterface $provider, string $fieldHandle, ?string $key = null): ?string
    {
        $valueSql = $provider->getFieldLayout()->getFieldByHandle($fieldHandle)->getValueSql($key);

        if ($valueSql instanceof Expression) {
            return $valueSql->getValue(DB::getQueryGrammar());
        }

        return $valueSql;
    }

    /**
     * @param  array<array-key, mixed>|null  $variables
     * @return array<array-key, mixed>
     */
    public function gqlFunction(string $query, ?array $variables = null, ?string $operationName = null): array
    {
        $schema = GqlHelper::createFullAccessSchema();

        return Gql::executeQuery($schema, $query, $variables, $operationName);
    }

    public function pluginFunction(string $handle): ?PluginInterface
    {
        return app(Plugins::class)->getPlugin($handle);
    }

    public function seqFunction(string $name, ?int $length = null, bool $next = true): int|string
    {
        if ($next) {
            return Sequence::next($name, $length);
        }

        return Sequence::current($name, $length);
    }

    public function renderObjectTemplate(string $template, mixed $object): string
    {
        return renderObjectTemplate($template, $object);
    }
}
