<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\config;

use craft\base\LegacyEventConstants;
use craft\services\Config;
use CraftCms\Cms\Support\Config as ConfigHelper;
use CraftCms\Cms\Support\Facades\Deprecator;
use Deprecated;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config as ConfigFacade;
use yii\base\InvalidConfigException;

use function CraftCms\Cms\t;

/**
 * General config class
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Config\GeneralConfig]] should be used instead.
 */
class GeneralConfig extends \CraftCms\Cms\Config\GeneralConfig
{
    use LegacyEventConstants;

    /**
     * @var string|array|null|false Configures Craft to send all system emails to either a single email address or an array of email addresses
     *                              for testing purposes.
     *
     * By default, the recipient name(s) will be “Test Recipient”, but you can customize that by setting the value with the format
     * `['me@domain.tld' => 'Name']`.
     *
     * @deprecated in 6.0.0. Configure `Illuminate\Support\Facades\Mail::alwaysTo()` instead.
     */
    public string|array|null|false $testToEmailAddress = null;

    /**
     * @inheritdoc
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
     * @deprecated 6.0.0. Set hashing.bcrypt.rounds or BCRYPT_ROUNDS environment variable instead.
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
     * ::: tip
     * As of Craft 5.9.0, the `@transform` directive can be optionally included for each GraphQL schema,
     * unless this setting is set to `true`.
     * :::
     *
     * @group GraphQL
     *
     * @since 3.6.0
     * @deprecated in 5.9.0
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
     * @deprecated 6.0.0 configure sessions using Laravel's session config instead.
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
     * @deprecated 6.0.0 configure sessions using Laravel's session config instead.
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
     * @deprecated 6.0.0
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
     * @deprecated 6.0.0
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
     * @deprecated 6.0.0
     */
    public mixed $userSessionDuration = 3600;

    /**
     * @inheritdoc
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

        $this
            // legacy-only durations
            ->purgeStaleUserSessionDuration($this->purgeStaleUserSessionDuration)
            ->purgePendingUsersDuration($this->purgePendingUsersDuration)
            ->purgeUnsavedDraftsDuration($this->purgeUnsavedDraftsDuration)
            ->rememberUsernameDuration($this->rememberUsernameDuration)
            ->rememberedUserSessionDuration($this->rememberedUserSessionDuration)
            ->softDeleteDuration($this->softDeleteDuration)
            ->verificationCodeDuration($this->verificationCodeDuration)
            // locales
            ->defaultCpLanguage($this->defaultCpLanguage)
            ->extraAppLocales($this->extraAppLocales)
            // misc
            ->maxUploadFileSize($this->maxUploadFileSize)
            ->disabledPlugins($this->disabledPlugins)
        ;
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
     */
    #[Deprecated(message: 'in 6.0.0. Set hashing.bcrypt.rounds or BCRYPT_ROUNDS environment variable instead.')]
    public function blowfishHashCost(int $value): self
    {
        app()->booting(function() use ($value) {
            ConfigFacade::set('hashing.bcrypt.rounds', $value);
            Deprecator::log('generalConfig.blowfishHashCost', 'blowfishHashCost is deprecated. Set hashing.bcrypt.rounds or BCRYPT_ROUNDS instead.');
        });

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
     */
    #[Deprecated(message: 'in 6.0.0. Calling csrfTokenName() is deprecated. The token is always named XSRF-TOKEN.')]
    public function csrfTokenName(string $value): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.csrfTokenName', 'Calling csrfTokenName() is deprecated. The token is always named XSRF-TOKEN.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. Set `app.debug` or `APP_DEBUG` environment variable instead.')]
    public function devMode(bool $value = true): self
    {
        app()->booting(function() use ($value) {
            Deprecator::log('generalConfig.devMode', 'devMode is deprecated. Set `app.debug` or `APP_DEBUG` environment variable instead.');
            ConfigFacade::set('app.debug', $value);
        });

        $this->devMode = $value;

        return $this;
    }
    /**
     * Whether the `@transform` directive should be disabled for the GraphQL API.
     *
     * ```php
     * ->disableGraphqlTransformDirective(true)
     * ```
     *
     * ::: tip
     * As of Craft 5.9.0, the `@transform` directive can be optionally included for each GraphQL schema,
     * unless this setting is set to `true`.
     * :::
     *
     * @group GraphQL
     *
     * @see $disableGraphqlTransformDirective
     */
    public function disableGraphqlTransformDirective(bool $value = true): self
    {
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
     */
    #[Deprecated(message: 'in 6.0.0. A cookie will always be used to persist the CSRF token.')]
    public function enableCsrfCookie(bool $value = true): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.enableCsrfCookie', 'A cookie will always be used to persist the CSRF token.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. [Configure excluded routes instead](https://laravel.com/docs/13.x/csrf#csrf-excluding-uris)')]
    public function enableCsrfProtection(bool $value = true): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.enableCsrfProtection', 'Configure excluded routes instead.'));

        $this->enableCsrfProtection = $value;

        if ($value === false) {
            PreventRequestForgery::except(['*']);
        }

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
     */
    #[Deprecated(message: 'use the `auth.password_timeout` config setting instead.', since: '6.0.0')]
    public function elevatedSessionDuration(mixed $value): self
    {
        $this->elevatedSessionDuration = ConfigHelper::durationInSeconds($value);

        app()->booting(function() {
            Deprecator::log('generalConfig.elevatedSessionDuration', 'Use the `auth.password_timeout` config setting instead.');
            ConfigFacade::set('auth.password_timeout', $this->elevatedSessionDuration === 0 ? -1 : $this->elevatedSessionDuration);
        });

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
     */
    #[Deprecated(message: 'in 6.0.0. Sandbox is always enabled.')]
    public function enableTwigSandbox(bool $value = true): self
    {
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
     */
    #[Deprecated(message: 'in 6.0.0. Script name is now always omitted.')]
    public function omitScriptNameInUrls(bool $value = true): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.omitScriptNameInUrls', 'Calling omitScriptNameInUrls() is deprecated. Script name is now always omitted.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. This method no longer does anything.')]
    public function pathParam(?string $value): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.pathParam', 'Calling pathParam() is deprecated.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. Configure `session.cookie` or set `SESSION_COOKIE` environment variable.')]
    public function phpSessionName(string $value): self
    {
        $this->phpSessionName = $value;

        app()->booting(function() use ($value) {
            Deprecator::log('generalConfig.phpSessionName', 'Calling phpSessionName() is deprecated. Configure `session.cookie` or set `SESSION_COOKIE` environment variable.');
            ConfigFacade::set('session.cookie', $value);
        });

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
     */
    #[Deprecated(message: 'in 6.0.0. This method no longer does anything, sessions are cleaned up on a lottery basis when needed.')]
    public function purgeStaleUserSessionDuration(mixed $value): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.purgeStaleUserSessionDuration', 'Calling purgeStaleUserSessionDuration() is deprecated. Sessions are cleaned up on a lottery basis when needed.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. This method no longer configures anything.')]
    public function requireMatchingUserAgentForSession(bool $value = true): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.requireMatchingUserAgentForSession', 'Calling requireMatchingUserAgentForSession() is deprecated.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. This method no longer configures anything.')]
    public function requireUserAgentAndIpForSession(bool $value = true): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.requireUserAgentAndIpForSession', 'Calling requireUserAgentAndIpForSession() is deprecated.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. Configure `cookie.same_site` or set `SESSION_SAME_SITE` environment variable instead.')]
    public function sameSiteCookieValue(?string $value): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.sameSiteCookieValue', 'Calling sameSiteCookieValue() is deprecated. Configure `cookie.same_site` or set `SESSION_SAME_SITE` environment variable instead.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. [Configure trusted proxies instead](https://laravel.com/docs/12.x/requests#configuring-trusted-proxies).')]
    public function secureHeaders(?array $value): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.secureHeaders', 'Calling secureHeaders() is deprecated. [Configure trusted proxies instead](https://laravel.com/docs/12.x/requests#configuring-trusted-proxies)'));

        $this->secureHeaders = $value;

        if (!$value) {
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

        TrustProxies::withHeaders($bitmask);

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
     */
    #[Deprecated(message: 'in 6.0.0. This method no longer configures anything.')]
    public function secureProtocolHeaders(?array $value): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.secureProtocolHeaders', 'Calling secureProtocolHeaders() is deprecated.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. Configure `app.key` or set `APP_KEY` in your environment instead.')]
    public function securityKey(string $value): self
    {
        $this->securityKey = $value;

        app()->booting(function() use ($value) {
            Deprecator::log('generalConfig.securityKey', 'Calling securityKey() is deprecated.');
            ConfigFacade::set('app.key', $value);
        });

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
     */
    #[Deprecated(message: 'in 6.0.0. [Configure trusted proxies instead](https://laravel.com/docs/12.x/requests#configuring-trusted-proxies).')]
    public function trustedHosts(array $value): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.trustedHosts', 'Calling secureProtocolHeaders() is deprecated. [Configure trusted proxies instead](https://laravel.com/docs/12.x/requests#configuring-trusted-proxies).'));

        $this->trustedHosts = $value;

        if (!empty($value)) {
            TrustProxies::at($value);
        }

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
     * @deprecated 6.0.0
     */
    public function useFileLocks(?bool $value): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.useFileLocks', 'Calling useFileLocks() is deprecated.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. This setting no longer has any effect.')]
    public function usePathInfo(bool $value = true): self
    {
        app()->booting(fn() => Deprecator::log('generalConfig.usePathInfo', 'Calling usePathInfo() is deprecated. This setting no longer has any effect.'));

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
     */
    #[Deprecated(message: 'in 6.0.0. Configure `session.secure` or set `SESSION_SECURE_COOKIE` in your environment instead.')]
    public function useSecureCookies(string|bool $value): self
    {
        app()->booting(function() use ($value) {
            Deprecator::log('generalConfig.useSecureCookies', 'Calling useSecureCookies() is deprecated. Configure `session.secure` or set `SESSION_SECURE_COOKIE` in your environment instead.');
            ConfigFacade::set('session.secure', $value === 'auto' ? null : $value);
        });

        $this->useSecureCookies = $value;

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
     */
    #[Deprecated(message: "configure sessions using Laravel's session config instead.", since: '6.0.0')]
    public function userSessionDuration(mixed $value): self
    {
        $this->userSessionDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    /**
     * Configures Craft to send all system emails to either a single email address or an array of email addresses
     * for testing purposes.
     *
     * @deprecated in 6.0.0. Configure `Illuminate\Support\Facades\Mail::alwaysTo()` instead.
     *
     * @see $testToEmailAddress
     */
    #[Deprecated(message: 'in 6.0.0. Configure `Illuminate\\Support\\Facades\\Mail::alwaysTo()` instead.')]
    public function testToEmailAddress(string|array|null|false $value): self
    {
        app()->booted(fn() => Deprecator::log(
            'generalConfig.testToEmailAddress',
            '`craft\\config\\GeneralConfig::$testToEmailAddress` and `craft\\config\\GeneralConfig::testToEmailAddress()` are deprecated. Configure `Illuminate\\Support\\Facades\\Mail::alwaysTo()` in your application bootstrap or service provider instead.',
        ));

        $this->testToEmailAddress = $value;

        return $this;
    }

    /**
     * Returns the normalized test email addresses.
     */
    public function getTestToEmailAddress(): array
    {
        $to = [];
        if ($this->testToEmailAddress) {
            foreach ((array)$this->testToEmailAddress as $key => $value) {
                if (is_numeric($key)) {
                    $to[$value] = t('Test Recipient');
                } else {
                    $to[$key] = $value;
                }
            }
        }

        return $to;
    }
}
