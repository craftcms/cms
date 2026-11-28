<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

use CraftCms\Cms\Support\Attributes\EnvName;
use CraftCms\Cms\Support\Config as ConfigHelper;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

/**
 * Settings for authentication, rate limits, and tokens.
 *
 * @since 6.0.0
 */
class SecurityConfig extends BaseConfig
{
    /**
     * The Laravel authentication guard Craft should use.
     *
     * Set this to `craft` to use Craft’s dedicated guard and user provider. If this is `null`, Craft will continue using Laravel’s default guard.
     * Configure `authPasswordBroker` as well if Craft should use a separate password broker.
     *
     * @see getAuthGuard()
     */
    public ?string $authGuard = null;

    /**
     * The Laravel password broker Craft should use.
     *
     * If this is `null`, Craft will continue using Laravel’s default password broker. To isolate password resets, define a separate broker, provider,
     * and token table in `config/auth.php`, and set this to the broker name.
     *
     * @see getAuthPasswordBroker()
     */
    public ?string $authPasswordBroker = null;

    /**
     * The amount of time a user must wait before re-attempting to log in after their account is locked due to too many
     * failed login attempts.
     *
     * Set to `0` to keep the account locked indefinitely, requiring an admin to manually unlock the account.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * @defaultAlt 5 minutes
     */
    public mixed $cooldownDuration = 300;

    /**
     * The number of requests Craft will allow from a single IP address within {@see $defaultRateLimitDuration},
     * for actions that are rate-limited by default, before responding with a 429 error.
     *
     * This applies to the following, each of which is tracked separately:
     *
     * - login attempts
     * - two-factor authentication verifications
     * - password reset requests
     * - MCP discovery requests (`/.well-known/ai-catalog.json` and MCP Server Cards)
     *
     * Login attempts and two-factor authentication verifications are also limited per account, to five a minute.
     *
     * This can be set to `false` to disable the default rate limits.
     *
     * ::: tip
     * If Craft is running behind a load balancer or reverse proxy, make sure Laravel’s
     * [trusted proxies](https://laravel.com/docs/requests#configuring-trusted-proxies) are configured, so rate limits are
     * applied to each visitor’s IP rather than the proxy’s.
     * :::
     */
    public int|false $defaultRateLimit = 10;

    /**
     * The amount of time that {@see $defaultRateLimit} applies to.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * @defaultAlt 1 minute
     */
    public mixed $defaultRateLimitDuration = 60;

    /**
     * The default amount of time tokens can be used before expiring.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * @defaultAlt 1 day
     */
    public mixed $defaultTokenDuration = 86400;

    /**
     * Whether two-step verification features should be disabled.
     */
    #[EnvName('DISABLE_2FA')]
    public bool $disable2fa = false;

    /**
     * The amount of time to track invalid login attempts for a user, for determining if Craft should lock an account.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * @defaultAlt 1 hour
     */
    public mixed $invalidLoginWindowDuration = 3600;

    /**
     * The number of invalid login attempts Craft will allow within the specified duration before the account gets locked.
     */
    public int|false $maxInvalidLogins = 5;

    /**
     * When `true`, Craft will always return a successful response in the “forgot password” flow, making it difficult to enumerate users.
     *
     * When set to `false` and you go through the “forgot password” flow from the control panel login page, you’ll get distinct messages indicating
     * whether the username/email exists and whether an email was sent with further instructions. This can be helpful for the user attempting to
     * log in but allow for username/email enumeration based on the response.
     */
    public bool $preventUserEnumeration = true;

    /**
     * The amount of time content preview tokens can be used before expiring.
     *
     * Defaults to the `defaultTokenDuration` value.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     */
    public mixed $previewTokenDuration = null;

    /**
     * The amount of time Craft will remember a username and pre-populate it on the control panel’s Login page.
     *
     * Set to `0` to disable this feature altogether.
     *
     * See {@see ConfigHelper::durationInSeconds()} for a list of supported value types.
     *
     * @defaultAlt 1 year
     */
    public mixed $rememberUsernameDuration = 31536000;

    /**
     * Whether user IP addresses should be stored/logged by the system.
     */
    public bool $storeUserIps = false;

    public function authGuard(?string $value): self
    {
        $this->authGuard = $value;

        return $this;
    }

    public function authPasswordBroker(?string $value): self
    {
        $this->authPasswordBroker = $value;

        return $this;
    }

    public function cooldownDuration(mixed $value): self
    {
        $this->cooldownDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    public function defaultRateLimit(int|false $value): self
    {
        $this->defaultRateLimit = $value;

        return $this;
    }

    public function defaultRateLimitDuration(mixed $value): self
    {
        $this->defaultRateLimitDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    public function defaultTokenDuration(mixed $value): self
    {
        $this->defaultTokenDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    public function disable2fa(bool $value = true): self
    {
        $this->disable2fa = $value;

        return $this;
    }

    public function invalidLoginWindowDuration(mixed $value): self
    {
        $this->invalidLoginWindowDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    public function maxInvalidLogins(int|false $value): self
    {
        $this->maxInvalidLogins = $value;

        return $this;
    }

    public function preventUserEnumeration(bool $value = true): self
    {
        $this->preventUserEnumeration = $value;

        return $this;
    }

    public function previewTokenDuration(mixed $value): self
    {
        $this->previewTokenDuration = $value !== null ? ConfigHelper::durationInSeconds($value) : null;

        return $this;
    }

    public function rememberUsernameDuration(mixed $value): self
    {
        $this->rememberUsernameDuration = ConfigHelper::durationInSeconds($value);

        return $this;
    }

    public function storeUserIps(bool $value = true): self
    {
        $this->storeUserIps = $value;

        return $this;
    }

    /**
     * Returns the Laravel authentication guard Craft should use.
     *
     * @see authGuard
     */
    public function getAuthGuard(): string
    {
        return $this->authGuard ?? Auth::getDefaultDriver();
    }

    /**
     * Returns the Laravel password broker Craft should use.
     *
     * @see authPasswordBroker
     */
    public function getAuthPasswordBroker(): string
    {
        return $this->authPasswordBroker ?? Password::getDefaultDriver();
    }

    /**
     * Returns the session key used to store the password confirmation timestamp.
     */
    public function getPasswordConfirmationKey(): string
    {
        return $this->authGuard === null
            ? 'auth.password_confirmed_at'
            : sprintf('auth.%s.password_confirmed_at', $this->authGuard);
    }
}
