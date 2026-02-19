<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Extensions;

use CommerceGuys\Addressing\Formatter\FormatterInterface;
use Craft;
use craft\base\ElementInterface;
use craft\base\MissingComponentInterface;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use craft\helpers\Gql;
use craft\helpers\Template as TemplateHelper;
use craft\helpers\UrlHelper;
use craft\web\twig\variables\CraftVariable;
use craft\web\View;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Address\Addresses;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\AddressQuery;
use CraftCms\Cms\Element\Queries\AssetQuery;
use CraftCms\Cms\Element\Queries\ContentBlockQuery;
use CraftCms\Cms\Element\Queries\ElementQuery;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Element\Queries\UserQuery;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Money as MoneyHelper;
use CraftCms\Cms\Support\Sequence;
use CraftCms\Cms\Twig\Nodes\Expressions\Binaries\HasEveryBinary;
use CraftCms\Cms\Twig\Nodes\Expressions\Binaries\HasSomeBinary;
use CraftCms\Cms\Twig\NodeVisitors\EventTagAdder;
use CraftCms\Cms\Twig\NodeVisitors\EventTagFinder;
use CraftCms\Cms\Twig\NodeVisitors\GetAttrAdjuster;
use CraftCms\Cms\Twig\NodeVisitors\Profiler;
use CraftCms\Cms\Twig\PageLifecycle;
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
use CraftCms\Cms\Updates\Updates;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\ViewErrorBag;
use InvalidArgumentException;
use Money\Money;
use Override;
use Throwable;
use Twig\Environment as TwigEnvironment;
use Twig\ExpressionParser\Infix\BinaryOperatorExpressionParser;
use Twig\Extension\AbstractExtension;
use Twig\Extension\CoreExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;
use yii\base\BaseObject;
use yii\db\Expression;
use yii\db\QueryInterface;

use function CraftCms\Cms\renderObjectTemplate;
use function CraftCms\Cms\t;

final class CoreTwigExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly PageLifecycle $pageLifecycle,
    ) {}

    public static function arraySome(TwigEnvironment $env, mixed $array, mixed $arrow): mixed
    {
        CoreExtension::checkArrow($env, $arrow, 'has some', 'operator');

        return CoreExtension::arraySome($env, $array, $arrow);
    }

    public static function arrayEvery(TwigEnvironment $env, mixed $array, mixed $arrow): mixed
    {
        CoreExtension::checkArrow($env, $arrow, 'has every', 'operator');

        return CoreExtension::arrayEvery($env, $array, $arrow);
    }

    #[Override]
    public function getNodeVisitors(): array
    {
        return [
            new Profiler,
            new GetAttrAdjuster,
            new EventTagFinder,
            new EventTagAdder($this->pageLifecycle),
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
            new RegisterResourceTokenParser('html', AssetRegistry::class.'::html', [
                'allowTagPair' => true,
                'allowPosition' => true,
            ]),
            new RegisterResourceTokenParser('js', TemplateHelper::class.'::js', [
                'allowTagPair' => true,
                'allowPosition' => true,
                'allowRuntimePosition' => true,
                'allowOptions' => true,
            ]),
            new RegisterResourceTokenParser('script', AssetRegistry::class.'::script', [
                'allowTagPair' => true,
                'allowPosition' => true,
                'allowOptions' => true,
                'defaultPosition' => View::POS_END,
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
        $isInstalled = Cms::isInstalled();
        $generalConfig = Cms::config();
        $setPasswordRequestPath = $generalConfig->getSetPasswordRequestPath();
        $updates = app(Updates::class);

        if ($isInstalled && ! $updates->isCraftUpdatePending()) {
            $currentSite = Sites::getCurrentSite();
            $primarySite = Sites::getPrimarySite();

            $currentUser = Auth::user();
            $siteName = t($currentSite->getName(), category: 'site');
            $siteUrl = $currentSite->getBaseUrl();
            $systemName = Cms::systemName();
        } else {
            $currentSite = $primarySite = $currentUser = $siteName = $siteUrl = $systemName = null;
        }

        $variable = new CraftVariable;

        return [
            'app' => $variable,
            'craft' => $variable,
            'sessionErrors' => Session::get('errors') ?: new ViewErrorBag,
            'request' => app(Request::class),
            'pluginAssets' => app(Plugins::class)->getAssetsHtml(),
            'currentSite' => $currentSite,
            'currentUser' => $currentUser,
            'primarySite' => $primarySite,
            'siteName' => $siteName,
            'siteUrl' => $siteUrl,
            'systemName' => $systemName,
            'devMode' => app()->hasDebugModeEnabled(),
            'SORT_ASC' => SORT_ASC,
            'SORT_DESC' => SORT_DESC,
            'SORT_REGULAR' => SORT_REGULAR,
            'SORT_NUMERIC' => SORT_NUMERIC,
            'SORT_STRING' => SORT_STRING,
            'SORT_LOCALE_STRING' => SORT_LOCALE_STRING,
            'SORT_NATURAL' => SORT_NATURAL,
            'SORT_FLAG_CASE' => SORT_FLAG_CASE,
            'PHP_INT_MAX' => PHP_INT_MAX,
            'POS_HEAD' => View::POS_HEAD,
            'POS_BEGIN' => View::POS_BEGIN,
            'POS_END' => View::POS_END,
            'POS_READY' => View::POS_READY,
            'POS_LOAD' => View::POS_LOAD,
            'isInstalled' => $isInstalled,
            'isUpdateInfoCached' => $updates->isUpdateInfoCached(),
            'loginUrl' => UrlHelper::siteUrl($generalConfig->getLoginPath()),
            'logoutUrl' => UrlHelper::siteUrl($generalConfig->getLogoutPath()),
            'setPasswordUrl' => $setPasswordRequestPath !== null ? UrlHelper::siteUrl($setPasswordRequestPath) : null,
            'now' => DateTimeHelper::now(),
            'today' => DateTimeHelper::today(),
            'tomorrow' => DateTimeHelper::tomorrow(),
            'yesterday' => DateTimeHelper::yesterday(),
        ];
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
            new TwigFunction('app', $this->appFunction(...)),
            new TwigFunction('actionUrl', UrlHelper::actionUrl(...)),
            new TwigFunction('alias', Aliases::get(...)),
            new TwigFunction('ceil', 'ceil'),
            new TwigFunction('className', 'get_class'),
            new TwigFunction('clone', $this->cloneFunction(...)),
            new TwigFunction('configure', [Craft::class, 'configure']),
            new TwigFunction('cpUrl', UrlHelper::cpUrl(...)),
            new TwigFunction('create', $this->createFunction(...)),
            new TwigFunction('dump', $this->dumpFunction(...), ['is_safe' => ['html'], 'needs_context' => true, 'is_variadic' => true]),
            new TwigFunction('encodeUrl', UrlHelper::encodeUrl(...)),
            new TwigFunction('entryType', $this->entryTypeFunction(...)),
            new TwigFunction('expression', $this->expressionFunction(...)),
            new TwigFunction('fieldValueSql', $this->fieldValueSqlFunction(...)),
            new TwigFunction('floor', 'floor'),
            new TwigFunction('getenv', Env::get(...)),
            new TwigFunction('gql', $this->gqlFunction(...)),
            new TwigFunction('old', $this->oldFunction(...)),
            new TwigFunction('parseEnv', Env::parse(...)),
            new TwigFunction('parseBooleanEnv', Env::parseBoolean(...)),
            new TwigFunction('plugin', $this->pluginFunction(...)),
            new TwigFunction('raw', TemplateHelper::raw(...)),
            new TwigFunction('renderObjectTemplate', $this->renderObjectTemplate(...)),
            new TwigFunction('seq', $this->seqFunction(...)),
            new TwigFunction('session', $this->sessionFunction(...)),
            new TwigFunction('siteUrl', UrlHelper::siteUrl(...)),
            new TwigFunction('url', UrlHelper::url(...)),

            new TwigFunction('addresses', fn (array $config = []) => new AddressQuery($config)),
            new TwigFunction('assets', fn (array $config = []) => new AssetQuery($config)),
            new TwigFunction('contentBlocks', fn (array $config = []) => new ContentBlockQuery($config)),
            new TwigFunction('elements', fn (string $elementType = Element::class, array $config = []) => new ElementQuery($elementType, $config)),
            new TwigFunction('entries', fn (array $config = []) => new EntryQuery($config)),
            new TwigFunction('users', fn (array $config = []) => new UserQuery($config)),

            new TwigFunction('canCreateDrafts', fn (ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canCreateDrafts($element, $user)),
            new TwigFunction('canDelete', fn (ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canDelete($element, $user)),
            new TwigFunction('canDeleteForSite', fn (ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canDeleteForSite($element, $user)),
            new TwigFunction('canDuplicate', fn (ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canDuplicate($element, $user)),
            new TwigFunction('canSave', fn (ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canSave($element, $user)),
            new TwigFunction('canView', fn (ElementInterface $element, ?User $user = null) => Craft::$app->getElements()->canView($element, $user)),

            new TwigFunction('head', $this->pageLifecycle->head(...)),
            new TwigFunction('beginBody', $this->pageLifecycle->beginBody(...)),
            new TwigFunction('endBody', $this->pageLifecycle->endBody(...)),
        ];
    }

    public function addressFilter(?Address $address, array $options = [], ?FormatterInterface $formatter = null): string
    {
        if ($address === null) {
            return '';
        }

        return app(Addresses::class)->formatAddress($address, $options, $formatter);
    }

    public function moneyFilter(?Money $money, ?string $formatLocale = null): ?string
    {
        if ($money === null) {
            return null;
        }

        return MoneyHelper::toString($money, $formatLocale);
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

    public function numberFilter(mixed $value, ?int $decimals = null, array $options = [], array $textOptions = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            return I18N::getFormatter()->asDecimal($value, $decimals, $options);
        } catch (Throwable) {
            return $value;
        }
    }

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
                ! Craft::$app->getRequest()->getIsConsoleRequest() &&
                in_array(Craft::$app->getResponse()->getContentType(), ['text/html', 'application/xhtml+xml'], true)
            ) {
                $options = JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT;
            } else {
                $options = 0;
            }
        }

        if ($depth !== 512) {
            return json_encode($value, $options, $depth);
        }

        return Json::encode($value, $options);
    }

    public function lengthFilter(TwigEnvironment $env, mixed $value): int
    {
        if ($value instanceof QueryInterface) {
            return $value->count();
        }

        return CoreExtension::length($env->getCharset(), $value);
    }

    public function literalFilter(mixed $value): string
    {
        return Db::escapeParam((string) $value);
    }

    public function appFunction(?string $abstract = null): mixed
    {
        return app($abstract);
    }

    public function cloneFunction(mixed $var): mixed
    {
        return clone $var;
    }

    public function createFunction(string|array $type, array $params = []): object
    {
        $class = is_string($type) ? $type : ($type['__class'] ?? $type['class'] ?? null);
        if (
            ! is_subclass_of($class, BaseObject::class) &&
            ! str_starts_with($class, 'craft\\helpers\\') &&
            ! str_starts_with($class, '\\CraftCms\\Cms\\')
        ) {
            throw new InvalidArgumentException(sprintf('create() can only be used to create instances of %s.', BaseObject::class));
        }

        return Craft::createObject($type, $params);
    }

    public function dumpFunction(array $context, ...$vars): string
    {
        if (! $vars) {
            $vars = [TemplateHelper::contextWithoutTemplate($context)];
        }

        $output = '';

        foreach ($vars as $var) {
            ob_start();
            Craft::dump($var);
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

    public function expressionFunction(mixed $expression, array $params = [], array $config = []): Expression
    {
        return new Expression($expression, $params, $config);
    }

    public function fieldValueSqlFunction(FieldLayoutProviderInterface $provider, string $fieldHandle, ?string $key = null): ?string
    {
        $valueSql = $provider->getFieldLayout()->getFieldByHandle($fieldHandle)->getValueSql($key);

        if ($valueSql instanceof \Illuminate\Contracts\Database\Query\Expression) {
            return $valueSql->getValue(\Illuminate\Support\Facades\DB::getQueryGrammar());
        }

        return $valueSql;
    }

    public function gqlFunction(string $query, ?array $variables = null, ?string $operationName = null): array
    {
        $schema = Gql::createFullAccessSchema();

        return Craft::$app->getGql()->executeQuery($schema, $query, $variables, $operationName);
    }

    public function oldFunction(?string $key = null, mixed $default = null): mixed
    {
        return Session::getOldInput($key, $default);
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

    public function sessionFunction(array|string|null $key = null, mixed $default = null): mixed
    {
        return session($key, $default);
    }
}
