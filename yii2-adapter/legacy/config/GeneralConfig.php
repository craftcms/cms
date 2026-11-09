<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\config;

use craft\services\Config;
use CraftCms\Cms\Image\CraftAssetTransformDriver;
use CraftCms\Cms\Support\Config as ConfigHelper;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Deprecator;
use DateInterval;
use Deprecated;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config as ConfigFacade;
use Illuminate\Support\Facades\Context;
use InvalidArgumentException;
use ReflectionProperty;
use RuntimeException;
use yii\base\InvalidConfigException;
use yii\validators\IpValidator;

use function CraftCms\Cms\t;

/**
 * General config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Config\GeneralConfig]] should be used instead.
 */
class GeneralConfig extends \CraftCms\Cms\Config\GeneralConfig
{
    protected static array $renamedSettings = [
        'activateAccountFailurePath' => 'invalidUserTokenPath',
        'allowAutoUpdates' => 'allowUpdates',
        'backupDbOnUpdate' => 'backupOnUpdate',
        'defaultFilePermissions' => 'defaultFileMode',
        'defaultFolderPermissions' => 'defaultDirMode',
        'enableGraphQlCaching' => 'enableGraphqlCaching',
        'environmentVariables' => 'aliases',
        'isSystemOn' => 'isSystemLive',
        'restoreDbOnUpdateFailure' => 'restoreOnUpdateFailure',
        'tempAssetUploadFs' => 'tempAssetUploadDisk',
        'validationKey' => 'securityKey',
    ];

    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    /**
     * @var array<string, mixed> Middleware settings applied by the fluent methods, which a cached config applies again
     * when it’s restored
     */
    private array $middlewareSettings = [];

    /**
     * @var bool Whether [[holdSideEffects()]] is running
     */
    private static bool $holdingSideEffects = false;

    /**
     * @var list<callable> Changes to Laravel’s state that this config’s settings made while [[holdSideEffects()]] ran.
     * A clone keeps them, as it keeps the settings that made them.
     */
    private array $heldSideEffects = [];

    /**
     * @var bool|null Whether the site is currently live. If set to `true` or `false`, it will take precedence over the System Status setting
     * in Settings → General.
     *
     * ::: code
     * ```php Static Config
     * ->isSystemLive(true)
     * ```
     * ```shell Environment Override
     * CRAFT_IS_SYSTEM_LIVE=true
     * ```
     * :::
     *
     * @group System
     * @deprecated in 6.0.0. Use Laravel maintenance mode instead.
     */
    public ?bool $isSystemLive = null;

    /**
     * @var bool|null Whether image transforms should be generated before page load. `null` leaves it up to the asset transformer.
     *
     * ::: code
     * ```php Static Config
     * ->generateTransformsBeforePageLoad(true)
     * ```
     * ```shell Environment Override
     * CRAFT_GENERATE_TRANSFORMS_BEFORE_PAGE_LOAD=true
     * ```
     * :::
     *
     * @group Image Handling
     * @deprecated in 6.0.0. Configure immediate generation on the Craft asset transformer instead.
     */
    public ?bool $generateTransformsBeforePageLoad = null {
        set {
            $this->whenBooting(fn() => $value === null
                ? Context::forgetHidden(CraftAssetTransformDriver::IMMEDIATE_TRANSFORMS_CONTEXT)
                : Context::addHidden(CraftAssetTransformDriver::IMMEDIATE_TRANSFORMS_CONTEXT, $value));

            $this->generateTransformsBeforePageLoad = $value;
        }
    }

    /**
     * @var string|array|null|false Configures Craft to send all system emails to either a single email address or an array of email addresses
     * for testing purposes.
     *
     * By default, the recipient name(s) will be “Test Recipient”, but you can customize that by setting the value with the format
     * `['me@domain.tld' => 'Name']`.
     *
     * ::: code
     * ```php Static Config
     * ->testToEmailAddress('me@domain.tld')
     * ```
     * ```shell Environment Override
     * CRAFT_TEST_TO_EMAIL_ADDRESS=me@domain.tld
     * ```
     * :::
     *
     * @group System
     * @deprecated in 6.0.0. Configure `Illuminate\Support\Facades\Mail::alwaysTo()` instead.
     */
    public string|array|null|false $testToEmailAddress = null;

    /**
     * @var bool Whether [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) should be used for Live Preview.
     *
     * Using iFrame Resizer makes it possible for Craft to retain the preview’s scroll position between page loads, for cross-origin web pages.
     *
     * It works by setting the height of the iframe to match the height of the inner web page, and the iframe’s container will be scrolled rather
     * than the iframe document itself. This can lead to some unexpected CSS issues, however, because the previewed viewport height will be taller
     * than the visible portion of the iframe.
     *
     * If you have a [decoupled front end](https://craftcms.com/docs/5.x/reference/element-types/entries.html#previewing-decoupled-front-ends), you will need to include
     * [iframeResizer.contentWindow.min.js](https://raw.github.com/davidjbradshaw/iframe-resizer/master/js/iframeResizer.contentWindow.min.js) on your
     * page as well for this to work. You can conditionally include it for only Live Preview requests by checking if the requested URL contains a
     * `x-craft-live-preview` query string parameter.
     *
     * ::: tip
     * You can customize the behavior of iFrame Resizer via the <config5:previewIframeResizerOptions> config setting.
     * :::
     *
     * ::: code
     * ```php Static Config
     * ->useIframeResizer(true)
     * ```
     * ```shell Environment Override
     * CRAFT_USE_IFRAME_RESIZER=true
     * ```
     * :::
     *
     * @group System
     * @since 3.5.5
     * @deprecated in 6.0.0. It no longer has any effect. Live Preview no longer uses iFrame Resizer.
     */
    public bool $useIframeResizer = false;

    /**
     * @var array Custom [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) that should be used for preview iframes.
     *
     * ```php Static Config
     * ->previewIframeResizerOptions([
     *     'autoResize' => false,
     * ])
     * ```
     *
     * @group System
     * @since 3.5.0
     * @deprecated in 6.0.0. It no longer has any effect. Live Preview no longer uses iFrame Resizer.
     */
    public array $previewIframeResizerOptions = [];

    /**
     * @var string The domain that cookies generated by Craft should be created for. If blank, it will be left up to the browser to determine
     * which domain to use (almost always the current). If you want the cookies to work for all subdomains, for example, you could
     * set this to `'.my-project.tld'`.
     *
     * ::: code
     * ```php Static Config
     * ->defaultCookieDomain('.my-project.tld')
     * ```
     * ```shell Environment Override
     * CRAFT_DEFAULT_COOKIE_DOMAIN=.my-project.tld
     * ```
     * :::
     *
     * @group Environment
     * @deprecated in 6.0.0. Configure `session.domain` or set the `SESSION_DOMAIN` environment variable instead.
     */
    public string $defaultCookieDomain = '';

    /**
     * @var mixed The amount of time a user stays logged if “Remember Me” is checked on the login page.
     *
     * Set to `0` to disable the “Remember Me” feature altogether.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * ->rememberedUserSessionDuration(0)
     * ```
     * ```shell Environment Override
     * CRAFT_REMEMBERED_USER_SESSION_DURATION=0
     * ```
     * :::
     *
     * @group Session
     * @defaultAlt 14 days
     * @deprecated in 6.0.0. Configure `auth.guards.craft.remember` instead.
     */
    public mixed $rememberedUserSessionDuration = 1209600;

    /**
     * @var mixed The amount of time a user verification code can be used before expiring.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * // 1 hour
     * ->verificationCodeDuration(3600)
     * ```
     * ```shell Environment Override
     * # 1 hour
     * CRAFT_VERIFICATION_CODE_DURATION=3600
     * ```
     * :::
     *
     * @group Security
     * @defaultAlt 1 day
     * @deprecated in 6.0.0. Configure `auth.passwords.craft.expire` instead.
     */
    public mixed $verificationCodeDuration = 86400;

    /**
     * @var string The path to the root directory that should store published control panel resources.
     *
     * ::: code
     * ```php Static Config
     * ->resourceBasePath('@webroot/craft-resources')
     * ```
     * ```shell Environment Override
     * CRAFT_RESOURCE_BASE_PATH=@webroot/craft-resources
     * ```
     * :::
     *
     * @group Environment
     */
    public string $resourceBasePath = '@webroot/cpresources';

    /**
     * @var string The URL to the root directory where control panel resources are published.
     *
     * ::: code
     * ```php Static Config
     * ->resourceBaseUrl('@web/craft-resources')
     * ```
     * ```shell Environment Override
     * CRAFT_RESOURCE_BASE_URL=@web/craft-resources
     * ```
     * :::
     *
     * @group Environment
     */
    public string $resourceBaseUrl = '@web/cpresources';

    /**
     * {@inheritdoc}
     */
    protected ?string $filename = Config::CATEGORY_GENERAL;

    /**
     * @var string[]|null|false The Ajax origins that should be allowed to access the GraphQL API, if enabled.
     *
     * If this is set to an array, then `graphql/api` requests will only include the current request’s [[\yii\web\Request::getOrigin()|origin]]
     * in the `Access-Control-Allow-Origin` response header if it’s listed here.
     *
     * If this is set to `false`, then the `Access-Control-Allow-Origin` response header will never be sent.
     *
     * ::: code
     * ```php Static Config
     * ->allowedGraphqlOrigins(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ALLOW_GRAPHQL_ORIGINS=false
     * ```
     * :::
     *
     * @group GraphQL
     *
     * @since 3.5.0
     * @deprecated in 4.11.0. [[\craft\filters\Cors]] should be used instead.
     * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-cors
     */
    public array|null|false $allowedGraphqlOrigins = null;

    /**
     * @var bool Whether drafts should be saved automatically as they are edited.
     *
     * Note that drafts *will* be autosaved while Live Preview is open, regardless of this setting.
     *
     * ::: code
     *  ```php Static Config
     *  ->autosaveDrafts(false)
     *  ```
     * ```shell Environment Override
     * CRAFT_AUTOSAVE_DRAFTS=false
     * ```
     * :::
     *
     * @group System
     *
     * @since 3.5.6
     * @deprecated in 4.0.0
     */
    public bool $autosaveDrafts = true;

    /**
     * @var int The higher the cost value, the longer it takes to generate a password hash and to verify against it.
     *
     * Therefore, higher cost slows down a brute-force attack.
     *
     * For best protection against brute force attacks, set it to the highest value that is tolerable on production servers.
     *
     * The time taken to compute the hash doubles for every increment by one for this value.
     *
     * For example, if the hash takes 1 second to compute when the value is 14 then the compute time varies as
     * 2^(value - 14) seconds.
     *
     * ::: code
     * ```php Static Config
     * ->blowfishHashCost(15)
     * ```
     * ```shell Environment Override
     * CRAFT_BLOWFISH_HASH_COST=15
     * ```
     * :::
     *
     * @group Security
     *
     * @deprecated in 6.0.0. Configure `hashing.bcrypt.rounds` or set the `BCRYPT_ROUNDS` environment variable instead.
     */
    public int $blowfishHashCost = 13;

    /**
     * @var bool Whether the `@transform` directive should be disabled for the GraphQL API.
     *
     * ::: code
     * ```php Static Config
     * ->disableGraphqlTransformDirective(true)
     * ```
     * ```shell Environment Override
     * CRAFT_DISABLE_GRAPHQL_TRANSFORM_DIRECTIVE=true
     * ```
     * :::
     *
     * @group GraphQL
     *
     * @since 3.6.0
     * @deprecated in 5.9.0. It no longer has any effect. Choose whether each GraphQL schema includes the `@transform` directive instead.
     */
    public bool $disableGraphqlTransformDirective = false;

    /**
     * @var string|null The `Permissions-Policy` header that should be sent for site responses.
     *
     * ::: code
     * ```php Static Config
     * ->permissionsPolicyHeader('Permissions-Policy: geolocation=(self)')
     * ```
     * ```shell Environment Override
     * CRAFT_PERMISSIONS_POLICY_HEADER=Permissions-Policy: geolocation=(self)
     * ```
     * :::
     *
     * @group System
     *
     * @since 3.6.14
     * @deprecated in 4.11.0. [[\craft\filters\Headers]] should be used instead.
     */
    public ?string $permissionsPolicyHeader = null;

    /**
     * @var string The name of the PHP session cookie.
     *
     * ::: code
     * ```php Static Config
     * ->phpSessionName(null)
     * ```
     * ```shell Environment Override
     * CRAFT_PHP_SESSION_NAME=
     * ```
     * :::
     *
     * @see https://php.net/manual/en/function.session-name.php
     *
     * @group Session
     *
     * @deprecated in 6.0.0. Configure `session.cookie` or set the `SESSION_COOKIE` environment variable instead.
     */
    public string $phpSessionName = 'CraftSessionId';

    /**
     * @var mixed The amount of time to wait before Craft purges stale user sessions from the sessions table in the database.
     *
     * Set to `0` to disable this feature.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * // 1 week
     * ->purgeStaleUserSessionDuration(604800)
     * ```
     * ```shell Environment Override
     * # 1 week
     * CRAFT_PURGE_STALE_USER_SESSION_DURATION=604800
     * ```
     * :::
     *
     * @group Garbage Collection
     *
     * @defaultAlt 90 days
     *
     * @since 3.3.0
     * @deprecated in 6.0.0. It no longer has any effect. Stale sessions are cleaned up by Laravel’s session lottery.
     */
    public mixed $purgeStaleUserSessionDuration = 7776000;

    /**
     * @var bool Whether Craft should require a matching user agent string when restoring a user session from a cookie.
     *
     * ::: code
     * ```php Static Config
     * ->requireMatchingUserAgentForSession(false)
     * ```
     * ```shell Environment Override
     * CRAFT_REQUIRE_MATCHING_USER_AGENT_FOR_SESSION=false
     * ```
     * :::
     *
     * @group Session
     *
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    public bool $requireMatchingUserAgentForSession = true;

    /**
     * @var bool Whether Craft should require the existence of a user agent string and IP address when creating a new user session.
     *
     * ::: code
     * ```php Static Config
     * ->requireUserAgentAndIpForSession(false)
     * ```
     * ```shell Environment Override
     * CRAFT_REQUIRE_USER_AGENT_AND_IP_FOR_SESSION=false
     * ```
     * :::
     *
     * @group Session
     *
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    public bool $requireUserAgentAndIpForSession = true;

    /**
     * @var mixed The amount of time before a user will get logged out due to inactivity.
     *
     * Set to `0` if you want users to stay logged in as long as their browser is open rather than a predetermined amount of time.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ::: code
     * ```php Static Config
     * // 3 hours
     * ->userSessionDuration(10800)
     * ```
     * ```shell Environment Override
     * # 3 hours
     * CRAFT_USER_SESSION_DURATION=10800
     * ```
     * :::
     *
     * @group Session
     *
     * @defaultAlt 1 hour
     *
     * @deprecated in 6.0.0. Configure `session.lifetime` or set the `SESSION_LIFETIME` environment variable instead.
     */
    public mixed $userSessionDuration = 3600;

    /**
     * @var string The prefix that should be prepended to HTTP error status codes when determining the path to look for an error’s template.
     *
     * If set to `'_'` your site’s 404 template would live at `templates/_404.twig`, for example.
     *
     * ::: code
     * ```php Static Config
     * ->errorTemplatePrefix('_')
     * ```
     * ```shell Environment Override
     * CRAFT_ERROR_TEMPLATE_PREFIX=_
     * ```
     * :::
     *
     * @group System
     *
     * @deprecated in 6.0.0. Put error templates in an `errors/` folder within the site templates instead, such as `errors/404.twig` or `errors/_404.twig`.
     */
    public string $errorTemplatePrefix = '';

    /**
     * @var string|null The query string param that Craft will check when determining the request’s path.
     *
     * This can be set to `null` if your web server is capable of directing traffic to `index.php` without a query string param.
     * If you’re using Apache, that means you’ll need to change the `RewriteRule` line in your `.htaccess` file to:
     *
     * ```
     * RewriteRule (.+) index.php [QSA,L]
     * ```
     *
     * ::: code
     * ```php Static Config
     * ->pathParam(null)
     * ```
     * ```shell Environment Override
     * CRAFT_PATH_PARAM=
     * ```
     * :::
     *
     * @group Routing
     *
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    public ?string $pathParam = null;

    /**
     * @var bool Whether generated URLs should omit `index.php` (e.g. `http://my-project.tld/path` instead of `http://my-project.tld/index.php/path`)
     *
     * This can only be possible if your server is configured to redirect would-be 404s to `index.php`, for example, with the redirect found
     * in the `.htaccess` file that came with Craft:
     *
     * ```
     * RewriteEngine On
     * RewriteCond %{REQUEST_FILENAME} !-f
     * RewriteCond %{REQUEST_FILENAME} !-d
     * RewriteRule (.+) /index.php?p=$1 [QSA,L]
     * ```
     *
     * ::: code
     * ```php Static Config
     * ->omitScriptNameInUrls(true)
     * ```
     * ```shell Environment Override
     * CRAFT_OMIT_SCRIPT_NAME_IN_URLS=true
     * ```
     * :::
     *
     * ::: tip
     * Even when this is set to `true`, the script name could still be included in some action URLs.
     * If you want to ensure that `index.php` is fully omitted from **all** generated URLs, set the <config5:pathParam>
     * config setting to `null`.
     * :::
     *
     * @group Routing
     *
     * @deprecated in 6.0.0. It no longer has any effect. Script names are always omitted from URLs.
     */
    public bool $omitScriptNameInUrls = true;

    /**
     * @var bool Whether Craft should specify the path using `PATH_INFO` or as a query string parameter when generating URLs.
     *
     * This setting only takes effect if <config5:omitScriptNameInUrls> is set to `false`.
     *
     * ::: code
     * ```php Static Config
     * ->usePathInfo(true)
     * ```
     * ```shell Environment Override
     * CRAFT_USE_PATH_INFO=true
     * ```
     * :::
     *
     * @group Routing
     *
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    public bool $usePathInfo = false;

    /**
     * @var bool|null Whether to grab an exclusive lock on a file when writing to it by using the `LOCK_EX` flag.
     *
     * Some file systems, such as NFS, do not support exclusive file locking.
     *
     * If `null`, Craft will try to detect if the underlying file system supports exclusive file locking and cache the results.
     *
     * ::: code
     * ```php Static Config
     * ->useFileLocks(false)
     * ```
     * ```shell Environment Override
     * CRAFT_USE_FILE_LOCKS=false
     * ```
     * :::
     *
     * @see https://php.net/manual/en/function.file-put-contents.php
     * @group System
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    public ?bool $useFileLocks = null;

    /**
     * @var bool Whether front-end web requests should support basic HTTP authentication.
     *
     * ::: code
     * ```php Static Config
     * ->enableBasicHttpAuth(true)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_BASIC_HTTP_AUTH=true
     * ```
     * :::
     *
     * @group Security
     * @since 3.5.0
     * @deprecated in 6.0.0. It no longer has any effect. Use Laravel’s `auth.basic` middleware instead.
     */
    public bool $enableBasicHttpAuth = false;

    /**
     * @var bool Whether to enable CSRF protection via hidden form inputs for all forms submitted via Craft.
     *
     * ::: code
     * ```php Static Config
     * ->enableCsrfProtection(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_CSRF_PROTECTION=false
     * ```
     * :::
     *
     * @see csrfTokenName
     * @see enableCsrfCookie
     *
     * @group Security
     * @deprecated in 6.0.0. [Exclude routes from CSRF protection](https://laravel.com/docs/13.x/csrf#csrf-excluding-uris) instead.
     */
    public bool $enableCsrfProtection = true;

    /**
     * @var bool Whether to use a cookie to persist the CSRF token if <config5:enableCsrfProtection> is enabled. If false, the CSRF token will be
     *           stored in session under the `csrfTokenName` config setting name. Note that while storing CSRF tokens in session increases security,
     *           it requires starting a session for every page that a CSRF token is needed, which may degrade site performance.
     *
     * ::: code
     * ```php Static Config
     * ->enableCsrfCookie(false)
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_CSRF_COOKIE=false
     * ```
     * :::
     *
     * @see enableCsrfProtection
     *
     * @group Security
     * @deprecated in 6.0.0. It no longer has any effect. A cookie is always used to persist the CSRF token.
     */
    public bool $enableCsrfCookie = true;

    /**
     * @var string The name of CSRF token used for CSRF validation if <config5:enableCsrfProtection> is set to `true`.
     *
     * ::: code
     * ```php Static Config
     * ->csrfTokenName('MY_CSRF')
     * ```
     * ```shell Environment Override
     * CRAFT_CSRF_TOKEN_NAME=MY_CSRF
     * ```
     * :::
     *
     * @group Security
     *
     * @see enableCsrfProtection
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    public string $csrfTokenName = '_token';

    /**
     * @var bool Whether user-defined Twig templates should be sandboxed.
     *
     * ::: code
     * ```php Static Config
     * ->enableTwigSandbox()
     * ```
     * ```shell Environment Override
     * CRAFT_ENABLE_TWIG_SANDBOX=true
     * ```
     * :::
     *
     * @see enableTwigSandbox()
     *
     * @group Security
     * @deprecated in 6.0.0. It no longer has any effect. The Twig sandbox is always enabled for untrusted templates.
     */
    public bool $enableTwigSandbox = true;

    /**
     * {@inheritdoc}
     *
     * @throws InvalidConfigException
     */
    public function init(): void
    {
        // (Re-)normalize everything.
        // Even if they were already set via the fluent methods, \Craft may not have been autoloaded yet,
        // so some values would still be in need of normalization, e.g. defaultCpLanguage/extraAppLocales.
        $this
            // file extensions
            ->allowedFileExtensions($this->allowedFileExtensions)
            ->extraAllowedFileExtensions($this->extraAllowedFileExtensions)
            // durations
            ->cacheDuration($this->cacheDuration)
            ->cooldownDuration($this->cooldownDuration)
            ->defaultTokenDuration($this->defaultTokenDuration)
            ->invalidLoginWindowDuration($this->invalidLoginWindowDuration)
            ->previewTokenDuration($this->previewTokenDuration ?? $this->defaultTokenDuration);

        // legacy-only durations
        $this->rememberedUserSessionDuration($this->rememberedUserSessionDuration);
        $this->verificationCodeDuration($this->verificationCodeDuration);

        $this
            // durations
            ->purgeStaleUserSessionDuration($this->purgeStaleUserSessionDuration)
            ->purgePendingUsersDuration($this->purgePendingUsersDuration)
            ->purgeUnsavedDraftsDuration($this->purgeUnsavedDraftsDuration)
            ->rememberUsernameDuration($this->rememberUsernameDuration)
            ->softDeleteDuration($this->softDeleteDuration)
            // locales
            ->defaultCpLanguage($this->defaultCpLanguage)
            ->extraAppLocales($this->extraAppLocales)
            // misc
            ->maxUploadFileSize($this->maxUploadFileSize)
            ->disabledPlugins($this->disabledPlugins);
    }

    /**
     * Whether the site is currently live. If set to `true` or `false`, it will take precedence over the System Status setting
     * in Settings → General.
     *
     * ```php
     * ->isSystemLive(true)
     * ```
     *
     * @group System
     * @see $isSystemLive
     * @since 4.2.0
     * @deprecated in 6.0.0. Use Laravel maintenance mode instead.
     */
    #[Deprecated(message: 'in 6.0.0. Use Laravel maintenance mode instead.')]
    public function isSystemLive(?bool $value): self
    {
        $this->deprecate('isSystemLive', 'Use Laravel maintenance mode instead.');

        $this->isSystemLive = $value;

        return $this;
    }

    /**
     * The Ajax origins that should be allowed to access the GraphQL API, if enabled.
     *
     * If this is set to an array, then `graphql/api` requests will only include the current request’s [[\yii\web\Request::getOrigin()|origin]]
     * in the `Access-Control-Allow-Origin` response header if it’s listed here.
     *
     * If this is set to `false`, then the `Access-Control-Allow-Origin` response header will never be sent.
     *
     * ```php
     * ->allowedGraphqlOrigins(false)
     * ```
     *
     * @group GraphQL
     *
     * @see $allowedGraphqlOrigins
     * @since 4.2.0
     * @see https://www.yiiframework.com/doc/api/2.0/yii-filters-cors
     */
    #[Deprecated(message: 'in 4.11.0. [[\craft\filters\Cors]] should be used instead.')]
    public function allowedGraphqlOrigins(array|null|false $value): self
    {
        $this->deprecate('allowedGraphqlOrigins', 'It no longer has any effect.');

        $this->allowedGraphqlOrigins = $value;

        return $this;
    }

    /**
     * The higher the cost value, the longer it takes to generate a password hash and to verify against it.
     *
     * Therefore, higher cost slows down a brute-force attack.
     *
     * For best protection against brute force attacks, set it to the highest value that is tolerable on production servers.
     *
     * The time taken to compute the hash doubles for every increment by one for this value.
     *
     * For example, if the hash takes 1 second to compute when the value is 14 then the compute time varies as
     * 2^(value - 14) seconds.
     *
     * ```php
     * ->blowfishHashCost(15)
     * ```
     *
     * @group Security
     *
     * @see $blowfishHashCost
     * @since 4.2.0
     * @deprecated in 6.0.0. Configure `hashing.bcrypt.rounds` or set the `BCRYPT_ROUNDS` environment variable instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `hashing.bcrypt.rounds` or set the `BCRYPT_ROUNDS` environment variable instead.')]
    public function blowfishHashCost(int $value): self
    {
        $this->deprecate('blowfishHashCost', 'Configure `hashing.bcrypt.rounds` or set the `BCRYPT_ROUNDS` environment variable instead.');

        $this->whenBooting(fn() => ConfigFacade::set('hashing.bcrypt.rounds', $value));

        $this->blowfishHashCost = $value;

        return $this;
    }

    /**
     * The name of CSRF token used for CSRF validation if <config5:enableCsrfProtection> is set to `true`.
     *
     * ```php
     * ->csrfTokenName('MY_CSRF')
     * ```
     *
     * @group Security
     *
     * @see $csrfTokenName
     * @see enableCsrfProtection
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect.')]
    public function csrfTokenName(string $value): self
    {
        $this->deprecate('csrfTokenName', 'It no longer has any effect.');

        $this->csrfTokenName = $value;

        return $this;
    }

    /**
     * Whether the system should run in [Dev Mode](https://craftcms.com/support/dev-mode).
     *
     * ```php
     * ->devMode(true)
     * ```
     *
     * @group System
     *
     * @see $devMode
     * @since 4.2.0
     * @deprecated in 6.0.0. Configure `app.debug` or set the `APP_DEBUG` environment variable instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `app.debug` or set the `APP_DEBUG` environment variable instead.')]
    public function devMode(bool $value = true): self
    {
        $this->deprecate('devMode', 'Configure `app.debug` or set the `APP_DEBUG` environment variable instead.');

        $this->whenBooting(fn() => ConfigFacade::set('app.debug', $value));

        $this->devMode = $value;

        return $this;
    }

    /**
     * Whether image transforms should be generated before page load.
     *
     * ```php
     * ->generateTransformsBeforePageLoad(true)
     * ```
     *
     * @group Image Handling
     * @see $generateTransformsBeforePageLoad
     * @since 4.2.0
     * @deprecated in 6.0.0. Configure immediate generation on the Craft asset transformer instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure immediate generation on the Craft asset transformer instead.')]
    public function generateTransformsBeforePageLoad(?bool $value = true): self
    {
        $this->deprecate('generateTransformsBeforePageLoad', 'Configure immediate generation on the Craft asset transformer instead.');

        $this->generateTransformsBeforePageLoad = $value;

        return $this;
    }

    /**
     * The domain that cookies generated by Craft should be created for. If blank, it will be left up to the browser to determine
     * which domain to use (almost always the current). If you want the cookies to work for all subdomains, for example, you could
     * set this to `'.my-project.tld'`.
     *
     * ```php
     * ->defaultCookieDomain('.my-project.tld')
     * ```
     *
     * @group Environment
     * @see $defaultCookieDomain
     * @since 4.2.0
     * @deprecated in 6.0.0. Configure `session.domain` or set the `SESSION_DOMAIN` environment variable instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `session.domain` or set the `SESSION_DOMAIN` environment variable instead.')]
    public function defaultCookieDomain(string $value): self
    {
        $this->deprecate('defaultCookieDomain', 'Configure `session.domain` or set the `SESSION_DOMAIN` environment variable instead.');

        $this->whenBooting(fn() => ConfigFacade::set('session.domain', $value));

        $this->defaultCookieDomain = $value;

        return $this;
    }

    /**
     * The amount of time a user stays logged if “Remember Me” is checked on the login page.
     *
     * Set to `0` to disable the “Remember Me” feature altogether.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * ```php
     * ->rememberedUserSessionDuration(0)
     * ```
     *
     * @group Session
     * @defaultAlt 14 days
     * @see $rememberedUserSessionDuration
     * @see getRememberedUserSessionDuration()
     * @since 4.2.0
     * @deprecated in 6.0.0. Configure `auth.guards.craft.remember` instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `auth.guards.craft.remember` instead.')]
    public function rememberedUserSessionDuration(mixed $value): self
    {
        $this->deprecate('rememberedUserSessionDuration', 'Configure `auth.guards.craft.remember` instead.');

        try {
            $interval = DateTimeHelper::toDateInterval($value);
        } catch (InvalidArgumentException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        $this->rememberedUserSessionDuration = $interval ? ConfigHelper::durationInSeconds($interval) : 0;

        $this->whenBooting(fn() => ConfigFacade::set('auth.guards.craft.remember', floor($this->rememberedUserSessionDuration / 60)));

        return $this;
    }

    public function getRememberedUserSessionDuration(): ?DateInterval
    {
        return $this->rememberedUserSessionDuration > 0
            ? DateTimeHelper::toDateInterval($this->rememberedUserSessionDuration)
            : null;
    }

    /**
     * The amount of time a user verification code can be used before expiring.
     *
     * See [[ConfigHelper::durationInSeconds()]] for a list of supported value types.
     *
     * ```php
     * // 1 hour
     * ->verificationCodeDuration(3600)
     * ```
     *
     * @group Security
     * @defaultAlt 1 day
     * @see $verificationCodeDuration
     * @since 4.2.0
     * @deprecated in 6.0.0. Configure `auth.passwords.craft.expire` instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `auth.passwords.craft.expire` instead.')]
    public function verificationCodeDuration(mixed $value): self
    {
        $this->deprecate('verificationCodeDuration', 'Configure `auth.passwords.craft.expire` instead.');

        $this->verificationCodeDuration = ConfigHelper::durationInSeconds($value);

        $this->whenBooting(fn() => ConfigFacade::set('auth.passwords.craft.expire', floor($this->verificationCodeDuration / 60)));

        return $this;
    }

    /**
     * Whether the `@transform` directive should be disabled for the GraphQL API.
     *
     * ```php
     * ->disableGraphqlTransformDirective(true)
     * ```
     *
     * @group GraphQL
     *
     * @see $disableGraphqlTransformDirective
     * @deprecated in 5.9.0. It no longer has any effect. Choose whether each GraphQL schema includes the `@transform` directive instead.
     */
    #[Deprecated(message: 'in 5.9.0. It no longer has any effect. Choose whether each GraphQL schema includes the `@transform` directive instead.')]
    public function disableGraphqlTransformDirective(bool $value = true): self
    {
        $this->deprecate('disableGraphqlTransformDirective', 'It no longer has any effect. Choose whether each GraphQL schema includes the `@transform` directive instead.');

        $this->disableGraphqlTransformDirective = $value;

        return $this;
    }

    /**
     * Whether to use a cookie to persist the CSRF token if <config5:enableCsrfProtection> is enabled. If false, the CSRF token will be
     * stored in session under the `csrfTokenName` config setting name. Note that while storing CSRF tokens in session increases security,
     * it requires starting a session for every page that a CSRF token is needed, which may degrade site performance.
     *
     * ```php
     * ->enableCsrfCookie(false)
     * ```
     *
     * @group Security
     *
     * @see $enableCsrfCookie
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect. A cookie is always used to persist the CSRF token.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect. A cookie is always used to persist the CSRF token.')]
    public function enableCsrfCookie(bool $value = true): self
    {
        $this->deprecate('enableCsrfCookie', 'It no longer has any effect. A cookie is always used to persist the CSRF token.');

        $this->enableCsrfCookie = $value;

        return $this;
    }

    /**
     * Whether to enable CSRF protection via hidden form inputs for all forms submitted via Craft.
     *
     * ```php
     * ->enableCsrfProtection(false)
     * ```
     *
     * @group Security
     *
     * @see $enableCsrfProtection
     * @since 4.2.0
     * @deprecated in 6.0.0. [Exclude routes from CSRF protection](https://laravel.com/docs/13.x/csrf#csrf-excluding-uris) instead.
     */
    #[Deprecated(message: 'in 6.0.0. [Exclude routes from CSRF protection](https://laravel.com/docs/13.x/csrf#csrf-excluding-uris) instead.')]
    public function enableCsrfProtection(bool $value = true): self
    {
        $this->deprecate('enableCsrfProtection', '[Exclude routes from CSRF protection](https://laravel.com/docs/13.x/csrf#csrf-excluding-uris) instead.');

        $this->enableCsrfProtection = $value;

        $this->applyMiddlewareSetting('enableCsrfProtection', $value);

        return $this;
    }

    /**
     * The amount of time a user’s elevated session will last, which is required for some sensitive actions (e.g. user group/permission assignment).
     *
     * Set to `0` to disable elevated session support.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * ->elevatedSessionDuration(0)
     * ```
     *
     * @group Security
     *
     * @defaultAlt 5 minutes
     *
     * @see $elevatedSessionDuration
     * @deprecated in 6.0.0. Configure `auth.password_timeout` instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `auth.password_timeout` instead.')]
    public function elevatedSessionDuration(mixed $value): self
    {
        $this->deprecate('elevatedSessionDuration', 'Configure `auth.password_timeout` instead.');

        $this->elevatedSessionDuration = ConfigHelper::durationInSeconds($value);

        $this->whenBooting(fn() => ConfigFacade::set('auth.password_timeout', $this->elevatedSessionDuration === 0 ? -1 : $this->elevatedSessionDuration));

        return $this;
    }

    /**
     * Whether front-end web requests should support basic HTTP authentication.
     *
     * ```php
     * ->enableBasicHttpAuth(true)
     * ```
     *
     * @group Security
     * @see $enableBasicHttpAuth
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect. Use Laravel’s `auth.basic` middleware instead.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect. Use Laravel’s `auth.basic` middleware instead.')]
    public function enableBasicHttpAuth(bool $value = true): self
    {
        $this->deprecate('enableBasicHttpAuth', 'It no longer has any effect. Use Laravel’s `auth.basic` middleware instead.');

        $this->enableBasicHttpAuth = $value;

        return $this;
    }

    /**
     * Whether user-defined Twig templates should be sandboxed.
     *
     * ```php
     * ->enableTwigSandbox()
     * ```
     *
     * @group Security
     *
     * @see $enableTwigSandbox
     * @deprecated in 6.0.0. It no longer has any effect. The Twig sandbox is always enabled for untrusted templates.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect. The Twig sandbox is always enabled for untrusted templates.')]
    public function enableTwigSandbox(bool $value = true): self
    {
        $this->deprecate('enableTwigSandbox', 'It no longer has any effect. The Twig sandbox is always enabled for untrusted templates.');

        $this->enableTwigSandbox = $value;

        return $this;
    }

    /**
     * Whether generated URLs should omit `index.php` (e.g. `http://my-project.tld/path` instead of `http://my-project.tld/index.php/path`)
     *
     * This can only be possible if your server is configured to redirect would-be 404s to `index.php`, for example, with the redirect found
     * in the `.htaccess` file that came with Craft:
     *
     * ```
     * RewriteEngine On
     * RewriteCond %{REQUEST_FILENAME} !-f
     * RewriteCond %{REQUEST_FILENAME} !-d
     * RewriteRule (.+) /index.php?p=$1 [QSA,L]
     * ```
     *
     * ```php
     * ->omitScriptNameInUrls(true)
     * ```
     *
     * @group Routing
     *
     * @see $omitScriptNameInUrls
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect. Script names are always omitted from URLs.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect. Script names are always omitted from URLs.')]
    public function omitScriptNameInUrls(bool $value = true): self
    {
        $this->deprecate('omitScriptNameInUrls', 'It no longer has any effect. Script names are always omitted from URLs.');

        $this->omitScriptNameInUrls = $value;

        return $this;
    }

    /**
     * The query string param that Craft will check when determining the request’s path.
     *
     * This can be set to `null` if your web server is capable of directing traffic to `index.php` without a query string param.
     * If you’re using Apache, that means you’ll need to change the `RewriteRule` line in your `.htaccess` file to:
     *
     * ```
     * RewriteRule (.+) index.php [QSA,L]
     * ```
     *
     * ```php
     * ->pathParam(null)
     * ```
     *
     * @group Routing
     *
     * @see $pathParam
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect.')]
    public function pathParam(?string $value): self
    {
        $this->deprecate('pathParam', 'It no longer has any effect.');

        $this->pathParam = $value;

        return $this;
    }

    /**
     * The `Permissions-Policy` header that should be sent for web responses.
     *
     * ```php
     * ->permissionsPolicyHeader('Permissions-Policy: geolocation=(self)')
     * ```
     *
     * @group System
     *
     * @see $permissionsPolicyHeader
     * @since 4.2.0
     */
    #[Deprecated(message: 'in 4.11.0. [[\craft\filters\Headers]] should be used instead.')]
    public function permissionsPolicyHeader(?string $value): self
    {
        $this->deprecate('permissionsPolicyHeader', 'It no longer has any effect.');

        $this->permissionsPolicyHeader = $value;

        return $this;
    }

    /**
     * The name of the PHP session cookie.
     *
     * ```php
     * ->phpSessionName(null)
     * ```
     *
     * @group Session
     *
     * @see $phpSessionName
     * @see https://php.net/manual/en/function.session-name.php
     * @since 4.2.0
     * @deprecated in 6.0.0. Configure `session.cookie` or set the `SESSION_COOKIE` environment variable instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `session.cookie` or set the `SESSION_COOKIE` environment variable instead.')]
    public function phpSessionName(string $value): self
    {
        $this->deprecate('phpSessionName', 'Configure `session.cookie` or set the `SESSION_COOKIE` environment variable instead.');

        $this->whenBooting(fn() => ConfigFacade::set('session.cookie', $value));

        $this->phpSessionName = $value;

        return $this;
    }

    /**
     * The amount of time to wait before Craft purges stale user sessions from the sessions table in the database.
     *
     * Set to `0` to disable this feature.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * // 1 week
     * ->purgeStaleUserSessionDuration(604800)
     * ```
     *
     * @group Garbage Collection
     *
     * @defaultAlt 90 days
     *
     * @see $purgeStaleUserSessionDuration
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect. Stale sessions are cleaned up by Laravel’s session lottery.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect. Stale sessions are cleaned up by Laravel’s session lottery.')]
    public function purgeStaleUserSessionDuration(mixed $value): self
    {
        $this->deprecate('purgeStaleUserSessionDuration', 'It no longer has any effect. Stale sessions are cleaned up by Laravel’s session lottery.');

        $this->purgeStaleUserSessionDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * Whether Craft should require a matching user agent string when restoring a user session from a cookie.
     *
     * ```php
     * ->requireMatchingUserAgentForSession(false)
     * ```
     *
     * @group Session
     *
     * @see $requireMatchingUserAgentForSession
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect.')]
    public function requireMatchingUserAgentForSession(bool $value = true): self
    {
        $this->deprecate('requireMatchingUserAgentForSession', 'It no longer has any effect.');

        $this->requireMatchingUserAgentForSession = $value;

        return $this;
    }

    /**
     * Whether Craft should require the existence of a user agent string and IP address when creating a new user session.
     *
     * ```php
     * ->requireUserAgentAndIpForSession(false)
     * ```
     *
     * @group Session
     *
     * @see $requireUserAgentAndIpForSession
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect.')]
    public function requireUserAgentAndIpForSession(bool $value = true): self
    {
        $this->deprecate('requireUserAgentAndIpForSession', 'It no longer has any effect.');

        $this->requireUserAgentAndIpForSession = $value;

        return $this;
    }

    /**
     * The [SameSite](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite) value that should be set on Craft cookies, if any.
     *
     * This can be set to `'None'`, `'Lax'`, `'Strict'`, or `null`.
     *
     * ```php
     * ->sameSiteCookieValue('Strict')
     * ```
     *
     * @group System
     *
     * @phpstan-param 'None'|'Lax'|'Strict'|null $value
     *
     * @see $sameSiteCookieValue
     * @since 4.2.0
     * @deprecated in 6.0.0. Configure `session.same_site` or set the `SESSION_SAME_SITE` environment variable instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `session.same_site` or set the `SESSION_SAME_SITE` environment variable instead.')]
    public function sameSiteCookieValue(?string $value): self
    {
        $this->deprecate('sameSiteCookieValue', 'Configure `session.same_site` or set the `SESSION_SAME_SITE` environment variable instead.');

        $this->sameSiteCookieValue = $value;

        return $this;
    }

    /**
     * Lists of headers that are, by default, subject to the trusted host configuration.
     *
     * See [[\yii\web\Request::secureHeaders]] for more details.
     *
     * If not set, the default [[\yii\web\Request::secureHeaders]] value will be used.
     *
     * ```php
     * ->secureHeaders([
     *     'X-Forwarded-For',
     *     'X-Forwarded-Host',
     *     'X-Forwarded-Proto',
     *     'X-Rewrite-Url',
     *     'X-Original-Host',
     *     'CF-Connecting-IP',
     * ])
     * ```
     *
     * @group Security
     *
     * @see $secureHeaders
     * @since 4.2.0
     * @deprecated in 6.0.0. [Configure trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies) instead.
     */
    #[Deprecated(message: 'in 6.0.0. [Configure trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies) instead.')]
    public function secureHeaders(?array $value): self
    {
        $this->deprecate('secureHeaders', '[Configure trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies) instead.');

        $this->secureHeaders = $value;

        if (!$value) {
            $this->forgetMiddlewareSetting('secureHeaders');

            return $this;
        }

        $headerMap = [
            'X-Forwarded-For' => Request::HEADER_X_FORWARDED_FOR,
            'X-Forwarded-Host' => Request::HEADER_X_FORWARDED_HOST,
            'X-Forwarded-Port' => Request::HEADER_X_FORWARDED_PORT,
            'X-Forwarded-Proto' => Request::HEADER_X_FORWARDED_PROTO,
            'X-Forwarded-Aws-ELB' => Request::HEADER_X_FORWARDED_AWS_ELB,
            'X-Forwarded-Traefik' => Request::HEADER_X_FORWARDED_TRAEFIK,
        ];

        $bitmask = 0;
        foreach ($value as $header) {
            if (isset($headerMap[$header])) {
                $bitmask |= $headerMap[$header];
            }
        }

        $this->applyMiddlewareSetting('secureHeaders', $bitmask);

        return $this;
    }

    /**
     * List of headers to check for determining whether the connection is made via HTTPS.
     *
     * See [[\yii\web\Request::secureProtocolHeaders]] for more details.
     *
     * If not set, the default [[\yii\web\Request::secureProtocolHeaders]] value will be used.
     *
     * ```php
     * ->secureProtocolHeaders([
     *     'X-Forwarded-Proto' => [
     *         'https',
     *     ],
     *     'Front-End-Https' => [
     *         'on',
     *     ],
     *     'CF-Visitor' => [
     *         '{\"scheme\":\"https\"}',
     *     ],
     * ])
     * ```
     *
     * @group Security
     *
     * @see $secureProtocolHeaders
     * @since 4.2.0
     * @deprecated in 6.0.0. [Configure trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies) instead.
     */
    #[Deprecated(message: 'in 6.0.0. [Configure trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies) instead.')]
    public function secureProtocolHeaders(?array $value): self
    {
        $this->deprecate('secureProtocolHeaders', '[Configure trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies) instead.');

        $this->secureProtocolHeaders = $value;

        return $this;
    }

    /**
     * A private, random, cryptographically-secure key that is used for hashing and encrypting data in [[\craft\services\Security]].
     *
     * ::: warning
     * **Do not** share this key publicly. If exposed, it could lead to a compromised system.
     * :::
     *
     * In the event that the key is compromised, a new secure key can be generated with the command:
     *
     * ```sh
     * php craft setup/security-key
     * ```
     *
     * Note that if the key changes, any data that is encrypted with it (e.g. user session cookies) will be inaccessible.
     *
     * ```php
     * ->securityKey('2cf24dba5...')
     * ```
     *
     * @group Security
     *
     * @see $securityKey
     * @see https://craftcms.com/knowledge-base/securing-craft
     * @since 4.2.0
     * @deprecated in 6.0.0. Configure `app.key` or set the `APP_KEY` environment variable instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `app.key` or set the `APP_KEY` environment variable instead.')]
    public function securityKey(string $value): self
    {
        $this->deprecate('securityKey', 'Configure `app.key` or set the `APP_KEY` environment variable instead.');

        $this->whenBooting(fn() => ConfigFacade::set('app.key', $value));

        $this->securityKey = $value;

        return $this;
    }

    /**
     * The configuration for trusted security-related headers.
     *
     * See [[\yii\web\Request::trustedHosts]] for more details.
     *
     * By default, all hosts are trusted.
     *
     * ```php
     * ->trustedHosts(['trusted-one.foo', 'trusted-two.foo'])
     * ```
     *
     * @group Security
     *
     * @see $trustedHosts
     * @since 4.2.0
     * @deprecated in 6.0.0. [Configure trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies) instead.
     */
    #[Deprecated(message: 'in 6.0.0. [Configure trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies) instead.')]
    public function trustedHosts(?array $value): self
    {
        $this->deprecate('trustedHosts', '[Configure trusted proxies](https://laravel.com/docs/13.x/requests#configuring-trusted-proxies) instead.');

        $this->trustedHosts = $value;

        if (!empty($value)) {
            $this->applyMiddlewareSetting('trustedHosts', self::trustedProxies($value));
        } else {
            $this->forgetMiddlewareSetting('trustedHosts');
        }

        return $this;
    }

    /**
     * Converts trusted hosts to the proxies Laravel trusts.
     *
     * Network aliases such as `any` and `private` expand the way Yii’s IP validator expands them. Laravel trusts the
     * same headers from every proxy, so a host’s own list of headers to trust is ignored.
     *
     * @param array<int|string, string|string[]> $hosts
     * @return string[]
     */
    private static function trustedProxies(array $hosts): array
    {
        $networks = get_class_vars(IpValidator::class)['networks'];
        $proxies = [];

        foreach ($hosts as $host => $headers) {
            $host = is_array($headers) ? $host : $headers;
            array_push($proxies, ...(isset($networks[$host]) ? self::trustedProxies($networks[$host]) : [$host]));
        }

        return array_values(array_unique($proxies));
    }

    /**
     * The path to the root directory that should store published control panel resources.
     *
     * ```php
     * ->resourceBasePath('@webroot/craft-resources')
     * ```
     *
     * @group Environment
     *
     * @see $resourceBasePath
     */
    public function resourceBasePath(string $value): self
    {
        $this->resourceBasePath = $value;

        return $this;
    }

    /**
     * The URL to the root directory where control panel resources are published.
     *
     * ```php
     * ->resourceBaseUrl('@web/craft-resources')
     * ```
     *
     * @group Environment
     *
     * @see $resourceBaseUrl
     */
    public function resourceBaseUrl(string $value): self
    {
        $this->resourceBaseUrl = $value;

        return $this;
    }

    /**
     * Whether to grab an exclusive lock on a file when writing to it by using the `LOCK_EX` flag.
     *
     * Some file systems, such as NFS, do not support exclusive file locking.
     *
     * If `null`, Craft will try to detect if the underlying file system supports exclusive file locking and cache the results.
     *
     * ```php
     * ->useFileLocks(false)
     * ```
     *
     * @group System
     *
     * @see $useFileLocks
     * @see https://php.net/manual/en/function.file-put-contents.php
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect.')]
    public function useFileLocks(?bool $value): self
    {
        $this->deprecate('useFileLocks', 'It no longer has any effect.');

        return $this;
    }

    /**
     * Whether Craft should specify the path using `PATH_INFO` or as a query string parameter when generating URLs.
     *
     * This setting only takes effect if <config5:omitScriptNameInUrls> is set to `false`.
     *
     * ```php
     * ->usePathInfo(true)
     * ```
     *
     * @group Routing
     *
     * @see $usePathInfo
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect.')]
    public function usePathInfo(bool $value = true): self
    {
        $this->deprecate('usePathInfo', 'It no longer has any effect.');

        $this->usePathInfo = $value;

        return $this;
    }

    /**
     * Whether Craft will set the “secure” flag when saving cookies when using `Craft::cookieConfig()` to create a cookie.
     *
     * Valid values are `true`, `false`, and `'auto'`. Defaults to `'auto'`, which will set the secure flag if the page you’re currently accessing
     * is over `https://`. `true` will always set the flag, regardless of protocol and `false` will never automatically set the flag.
     *
     * ```php
     * ->useSecureCookies(true)
     * ```
     *
     * @group Security
     *
     * @see $useSecureCookies
     * @since 4.2.0
     * @deprecated in 6.0.0. Configure `session.secure` or set the `SESSION_SECURE_COOKIE` environment variable instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `session.secure` or set the `SESSION_SECURE_COOKIE` environment variable instead.')]
    public function useSecureCookies(string|bool $value): self
    {
        $this->deprecate('useSecureCookies', 'Configure `session.secure` or set the `SESSION_SECURE_COOKIE` environment variable instead.');

        $this->whenBooting(fn() => ConfigFacade::set('session.secure', $value === 'auto' ? null : $value));

        $this->useSecureCookies = $value;

        return $this;
    }

    /**
     * The handle of the filesystem that should be used for storing temporary asset uploads. A local temp folder will
     * be used by default.
     *
     * ```php
     * ->tempAssetUploadFs('$TEMP_ASSET_UPLOADS_FS')
     * ```
     *
     * @group Assets
     * @see $tempAssetUploadFs
     * @since 5.0.0
     * @deprecated in 6.0.0. [[tempAssetUploadDisk()]] should be used instead.
     */
    #[Deprecated(message: 'in 6.0.0. [[tempAssetUploadDisk()]] should be used instead.')]
    public function tempAssetUploadFs(?string $value): self
    {
        $this->tempAssetUploadDisk($value);

        return $this;
    }

    /**
     * The amount of time before a user will get logged out due to inactivity.
     *
     * Set to `0` if you want users to stay logged in as long as their browser is open rather than a predetermined amount of time.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * ```php
     * // 3 hours
     * ->userSessionDuration(10800)
     * ```
     *
     * @group Session
     *
     * @defaultAlt 1 hour
     *
     * @see $userSessionDuration
     * @deprecated in 6.0.0. Configure `session.lifetime` or set the `SESSION_LIFETIME` environment variable instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `session.lifetime` or set the `SESSION_LIFETIME` environment variable instead.')]
    public function userSessionDuration(mixed $value): self
    {
        $this->deprecate('userSessionDuration', 'Configure `session.lifetime` or set the `SESSION_LIFETIME` environment variable instead.');

        $this->userSessionDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * Configures Craft to send all system emails to either a single email address or an array of email addresses
     * for testing purposes.
     *
     * @see $testToEmailAddress
     * @deprecated in 6.0.0. Configure `Illuminate\Support\Facades\Mail::alwaysTo()` instead.
     */
    #[Deprecated(message: 'in 6.0.0. Configure `Illuminate\\Support\\Facades\\Mail::alwaysTo()` instead.')]
    public function testToEmailAddress(string|array|null|false $value): self
    {
        $this->deprecate('testToEmailAddress', 'Configure `Illuminate\\Support\\Facades\\Mail::alwaysTo()` instead.');

        $this->testToEmailAddress = $value;

        return $this;
    }

    /**
     * The prefix that should be prepended to HTTP error status codes when determining the path to look for an error’s template.
     *
     * If set to `'_'` your site’s 404 template would live at `templates/_404.twig`, for example.
     *
     * ```php
     * ->errorTemplatePrefix('_')
     * ```
     *
     * @group System
     *
     * @see $errorTemplatePrefix
     * @deprecated in 6.0.0. Put error templates in an `errors/` folder within the site templates instead, such as `errors/404.twig` or `errors/_404.twig`.
     */
    #[Deprecated(message: 'in 6.0.0. Put error templates in an `errors/` folder within the site templates instead, such as `errors/404.twig` or `errors/_404.twig`.')]
    public function errorTemplatePrefix(string $value): self
    {
        $this->deprecate('errorTemplatePrefix', 'Put error templates in an `errors/` folder within the site templates instead, such as `errors/404.twig` or `errors/_404.twig`.');

        $this->errorTemplatePrefix = $value;

        return $this;
    }

    /**
     * Whether [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) should be used for Live Preview.
     *
     * Using iFrame Resizer makes it possible for Craft to retain the preview’s scroll position between page loads, for cross-origin web pages.
     *
     * It works by setting the height of the iframe to match the height of the inner web page, and the iframe’s container will be scrolled rather
     * than the iframe document itself. This can lead to some unexpected CSS issues, however, because the previewed viewport height will be taller
     * than the visible portion of the iframe.
     *
     * If you have a [decoupled front end](https://craftcms.com/docs/5.x/reference/element-types/entries.html#previewing-decoupled-front-ends), you will need to include
     * [iframeResizer.contentWindow.min.js](https://raw.github.com/davidjbradshaw/iframe-resizer/master/js/iframeResizer.contentWindow.min.js) on your
     * page as well for this to work. You can conditionally include it for only Live Preview requests by checking if the requested URL contains a
     * `x-craft-live-preview` query string parameter.
     *
     * ::: tip
     * You can customize the behavior of iFrame Resizer via the <config5:previewIframeResizerOptions> config setting.
     * :::
     *
     * ```php
     * ->useIframeResizer(true)
     * ```
     *
     * @group System
     * @see $useIframeResizer
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect. Live Preview no longer uses iFrame Resizer.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect. Live Preview no longer uses iFrame Resizer.')]
    public function useIframeResizer(bool $value = true): self
    {
        $this->deprecate('useIframeResizer', 'It no longer has any effect. Live Preview no longer uses iFrame Resizer.');

        $this->useIframeResizer = $value;

        return $this;
    }

    /**
     * Custom [iFrame Resizer options](http://davidjbradshaw.github.io/iframe-resizer/#options) that should be used for preview iframes.
     *
     * ```php
     * ->previewIframeResizerOptions([
     *     'autoResize' => false,
     * ])
     * ```
     *
     * @group System
     * @see $previewIframeResizerOptions
     * @since 4.2.0
     * @deprecated in 6.0.0. It no longer has any effect. Live Preview no longer uses iFrame Resizer.
     */
    #[Deprecated(message: 'in 6.0.0. It no longer has any effect. Live Preview no longer uses iFrame Resizer.')]
    public function previewIframeResizerOptions(array $value): self
    {
        $this->deprecate('previewIframeResizerOptions', 'It no longer has any effect. Live Preview no longer uses iFrame Resizer.');

        $this->previewIframeResizerOptions = $value;

        return $this;
    }

    /**
     * Returns the normalized test email addresses.
     */
    public function getTestToEmailAddress(): array
    {
        $to = [];
        if ($this->testToEmailAddress) {
            foreach ((array) $this->testToEmailAddress as $key => $value) {
                if (is_numeric($key)) {
                    $to[$value] = t('Test Recipient');
                } else {
                    $to[$key] = $value;
                }
            }
        }

        return $to;
    }

    /**
     * Restores a cached config, and applies the middleware settings its fluent methods applied again, as Laravel’s
     * middleware doesn’t keep them between requests.
     *
     * @param array<string, mixed> $stateData
     */
    public static function __set_state(array $stateData): static
    {
        $config = parent::__set_state($stateData);

        // The parent can’t restore this private property on subclasses
        $config->middlewareSettings = $stateData['middlewareSettings'] ?? [];
        $config->applyMiddlewareSettings();

        return $config;
    }

    /**
     * Applies the middleware settings this config’s fluent methods applied again.
     */
    private function applyMiddlewareSettings(): void
    {
        foreach ($this->middlewareSettings as $setting => $value) {
            $this->applyMiddlewareSetting($setting, $value);
        }
    }

    /**
     * Marks when settings can start changing Laravel’s state immediately.
     *
     * @internal
     */
    public static function registerBootingCallback(Application $app): void
    {
        $app->booting(fn() => $app->instance(self::class . '::booting', true));
    }

    /**
     * Runs a callback, and holds back the changes to Laravel’s state that settings make meanwhile on the configs they
     * were made on, so only those of a config that’s kept are applied, with [[applyHeldSideEffects()]].
     *
     * @template T
     * @param callable(): T $callback
     * @return T
     * @internal
     */
    public static function holdSideEffects(callable $callback): mixed
    {
        $holding = self::$holdingSideEffects;
        self::$holdingSideEffects = true;

        try {
            return $callback();
        } finally {
            self::$holdingSideEffects = $holding;
        }
    }

    /**
     * Applies the changes to Laravel’s state that this config’s settings made while [[holdSideEffects()]] ran,
     * including those made before it was cloned.
     *
     * @internal
     */
    public function applyHeldSideEffects(): void
    {
        $sideEffects = $this->heldSideEffects;
        $this->heldSideEffects = [];

        foreach ($sideEffects as $callback) {
            self::runWhenBooting($callback);
        }
    }

    /**
     * Runs a callback that changes Laravel’s state, such as its config.
     *
     * Settings applied while config files load and providers register are deferred until the application starts
     * booting, so they run in order after Laravel’s config and facades are ready. Settings applied after that, such as
     * `CRAFT_` environment overrides, run immediately.
     */
    private function whenBooting(callable $callback): void
    {
        if (self::$holdingSideEffects) {
            $this->heldSideEffects[] = $callback;

            return;
        }

        self::runWhenBooting($callback);
    }

    private static function runWhenBooting(callable $callback): void
    {
        $app = app();

        if ($app->bound(self::class . '::booting')) {
            $callback();
        } else {
            $app->booting($callback);
        }
    }

    /**
     * Applies a setting to Laravel’s middleware, and remembers it so a cached config can apply it again.
     */
    private function applyMiddlewareSetting(string $setting, mixed $value): void
    {
        $this->middlewareSettings[$setting] = $value;

        $this->whenBooting(function() use ($setting, $value): void {
            if ($setting === 'trustedHosts') {
                TrustProxies::at($value);
            } elseif ($setting === 'secureHeaders') {
                TrustProxies::withHeaders($value);
            } elseif ($value === false) {
                PreventRequestForgery::except(['*']);
            } else {
                // Undo a previous `enableCsrfProtection(false)`, which PreventRequestForgery has no method for
                $neverVerify = new ReflectionProperty(PreventRequestForgery::class, 'neverVerify');
                $neverVerify->setValue(null, array_values(array_diff($neverVerify->getValue(), ['*'])));
            }
        });
    }

    /**
     * Undoes the trusted proxies or headers this config applied earlier, unless something else has replaced them since,
     * such as an app’s own `trustProxies()` call, and stops a cached config from applying them again.
     */
    private function forgetMiddlewareSetting(string $setting): void
    {
        if (!array_key_exists($setting, $this->middlewareSettings)) {
            return;
        }

        $value = $this->middlewareSettings[$setting];
        unset($this->middlewareSettings[$setting]);

        $this->whenBooting(function() use ($setting, $value): void {
            $property = new ReflectionProperty(TrustProxies::class, $setting === 'trustedHosts' ? 'alwaysTrustProxies' : 'alwaysTrustHeaders');

            if ($property->getValue() === $value) {
                $property->setValue(null, null);
            }
        });
    }

    /**
     * Logs a deprecation warning for a config setting that was set.
     */
    private function deprecate(string $setting, string $guidance): void
    {
        app()->booted(fn() => Deprecator::log(
            "generalConfig.$setting",
            "The `$setting` config setting is deprecated. $guidance",
        ));
    }
}
