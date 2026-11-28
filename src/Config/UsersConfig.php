<?php

declare(strict_types=1);

namespace CraftCms\Cms\Config;

/**
 * Settings for user accounts.
 *
 * @since 6.0.0
 */
class UsersConfig extends BaseConfig
{
    /**
     * Whether users should automatically be logged in after activating their account.
     */
    public bool $autoLoginAfterAccountActivation = false;

    /**
     * By default, Craft requires a front-end “password” field for public user registrations. Setting this to
     * `true` removes that requirement for the initial registration form. Instead, new users will set their password
     * once they’ve followed the link in their activation email.
     */
    public bool $deferPublicRegistrationPassword = false;

    /**
     * @var string[] Any additional last name prefixes that should be supported by the name parser.
     */
    public array $extraLastNamePrefixes = [];

    /**
     * @var string[] Any additional name salutations that should be supported by the name parser.
     */
    public array $extraNameSalutations = [];

    /**
     * @var string[] Any additional name suffixes that should be supported by the name parser.
     */
    public array $extraNameSuffixes = [];

    /**
     * @var array<int|string, array<string, mixed>|string> The OAuth providers that should be available for login.
     *
     * Each provider should be keyed by its handle, and may be configured as either a driver string shorthand
     * or an array with the following supported keys. Numeric entries are also allowed for simple driver-only providers.
     *
     * - `driver` *(required)*: A registered Socialite driver name, or a Socialite-compatible provider class name.
     * - `clientId`: The provider client ID. Required for provider classes, optional for named drivers if already defined in `config/services.php`.
     * - `clientSecret`: The provider client secret. Required for provider classes, optional for named drivers if already defined in `config/services.php`.
     * - `enabled`: Whether the provider should be available. Defaults to `true`.
     * - `name`: A human-friendly provider name.
     * - `label`: The rendered button label.
     * - `icon`: The control panel brand icon name.
     * - `scopes`: Additional Socialite scopes.
     * - `with`: Additional Socialite request parameters.
     * - `stateless`: Whether the provider should bypass Socialite state checks.
     * - `groups`: User group IDs, UIDs, or handles to assign to new users.
     * - `createsUsers`: Whether the provider may create new users when no existing account can be matched. Defaults to the public registration setting when `null` or omitted.
     * - `controlsAccess`: Whether the provider becomes the source of truth for whether users can access Craft. When `true`, anyone the provider lets sign in gets access: Craft saves new users as active and activates any matched user who isn't active, which also lifts suspensions and locks. Disabled and archived users still can't sign in. Defaults to `false`.
     * - `trustsEmail`: Whether the provider is trusted to verify email ownership, allowing first-time matches to existing users by email. Defaults to `false`.
     * - `identityResolver`: A custom identity resolver class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthIdentity`.
     * - `userResolver`: A custom linked-user resolver class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUser`.
     * - `userPopulator`: A custom user populator class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\PopulatesOAuthUser`.
     * - `groupResolver`: A custom initial user-group resolver class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUserGroups`, returning group IDs, UIDs, or handles.
     * - `buttonRenderer`: A custom login-button renderer class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\RendersOAuthButton`.
     */
    public array $oauthProviders = [];

    /**
     * Whether “First Name” and “Last Name” fields should be shown in place of “Full Name” fields.
     */
    public bool $showFirstAndLastNameFields = false;

    /**
     * Whether Craft should set users’ usernames to their email addresses, rather than let them set their username separately.
     *
     * If you enable this setting after user accounts already exist, run this terminal command to update existing usernames:
     *
     * ```bash
     * php artisan craft:utils:update-usernames
     * ```
     */
    public bool $useEmailAsUsername = false;

    public function autoLoginAfterAccountActivation(bool $value = true): self
    {
        $this->autoLoginAfterAccountActivation = $value;

        return $this;
    }

    public function deferPublicRegistrationPassword(bool $value = true): self
    {
        $this->deferPublicRegistrationPassword = $value;

        return $this;
    }

    /** @param  string[]  $value */
    public function extraLastNamePrefixes(array $value): self
    {
        $this->extraLastNamePrefixes = $value;

        return $this;
    }

    /** @param  string[]  $value */
    public function extraNameSalutations(array $value): self
    {
        $this->extraNameSalutations = $value;

        return $this;
    }

    /** @param  string[]  $value */
    public function extraNameSuffixes(array $value): self
    {
        $this->extraNameSuffixes = $value;

        return $this;
    }

    /** @param  array<int|string, array<string, mixed>|string>  $value */
    public function oauthProviders(array $value): self
    {
        $this->oauthProviders = $value;

        return $this;
    }

    public function showFirstAndLastNameFields(bool $value = true): self
    {
        $this->showFirstAndLastNameFields = $value;

        return $this;
    }

    public function useEmailAsUsername(bool $value = true): self
    {
        $this->useEmailAsUsername = $value;

        return $this;
    }
}
