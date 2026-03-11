# OAuth

Craft 6 includes a Laravel-centric OAuth login system built on [Laravel Socialite](https://github.com/laravel/socialite).

OAuth providers are configured in `GeneralConfig`, use Craft web routes for redirects and callbacks, and integrate with Craft's existing login and access checks while bypassing the 2FA prompt.

## Requirements

- OAuth is available in Craft Pro and Enterprise.
- Each provider must be configured in `config/craft/general.php` or equivalent config.
- The provider `driver` must be either:
  - a registered Socialite driver name, or
  - a Socialite-compatible provider class name

## Basic Configuration

Configure providers with `GeneralConfig::oauthProviders()`:

```php
<?php

use CraftCms\Cms\Config\GeneralConfig;

return GeneralConfig::create()
    ->oauthProviders([
        'github',
        'google' => [
            'driver' => 'google',
            'clientId' => env('GOOGLE_CLIENT_ID'),
            'clientSecret' => env('GOOGLE_CLIENT_SECRET'),
        ],
    ]);
```

Provider configs are keyed by handle. The handle is used in Craft's OAuth routes and in the identity link stored in `sso_identities`.

If a provider only needs a driver, you can use the shorthand string form:

```php
'github',
```

When you use a named Socialite driver such as `github` or `google`, Craft merges the provider config with any existing `services.{driver}` config before resolving the driver. Values defined in the Craft OAuth provider take precedence.

## Provider Options

Each provider supports the following keys:

- `driver` required. A registered Socialite driver name, or a Socialite-compatible provider class name.
- `clientId` optional for named drivers when already defined in Laravel's `services` config. Required for provider classes.
- `clientSecret` optional for named drivers when already defined in Laravel's `services` config. Required for provider classes.
- `name` optional. Human-friendly provider name.
- `label` optional. Button label. Defaults to `Sign in with {name}`.
- `scopes` optional. Array of scopes passed to Socialite.
- `with` optional. Array of extra request parameters passed to the provider.
- `stateless` optional. Set to `true` to bypass Socialite state validation.
- `groups` optional. Array of user group IDs, UIDs, or handles to assign to newly-created users.
- `createsUsers` optional. Defaults to `null`, which inherits Craft's public registration setting.
- `activatesUsers` optional. Defaults to `false`.
- `identityResolver` optional. Class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthIdentity`.
- `userResolver` optional. Class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUser`.
- `userPopulator` optional. Class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\PopulatesOAuthUser`.
- `groupResolver` optional. Class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUserGroups`.
- `buttonRenderer` optional. Class implementing `\CraftCms\Cms\Auth\OAuth\Contracts\RendersOAuthButton`.

Example with more options:

```php
<?php

use App\Auth\OAuth\GitHubButtonRenderer;
use App\Auth\OAuth\GitHubUserPopulator;
use CraftCms\Cms\Config\GeneralConfig;

return GeneralConfig::create()
    ->oauthProviders([
        'github' => [
            'driver' => 'github',
            'clientId' => env('GITHUB_CLIENT_ID'),
            'clientSecret' => env('GITHUB_CLIENT_SECRET'),
            'label' => 'Continue with GitHub',
            'scopes' => ['read:user', 'user:email'],
            'groups' => ['members', 'editors'],
            'createsUsers' => true,
            'activatesUsers' => true,
            'userPopulator' => GitHubUserPopulator::class,
            'buttonRenderer' => GitHubButtonRenderer::class,
        ],
    ]);
```

## Routes and Callback URLs

Craft provides a stable callback endpoint and two redirect entrypoints:

- Site redirect: `oauth/{provider}/redirect`
- CP redirect: `{cpTrigger}/oauth/{provider}/redirect`
- Callback: `oauth/{provider}/callback`

The callback URL is generated from the site's normal web URL:

```php
siteUrl('oauth/google/callback')
```

Register that callback URL with your OAuth provider.

The redirect endpoint can be entered from either the site or control panel. Craft uses separate redirect routes so the callback can stay stable while still preserving the correct CP vs site auth context.

When the login starts from the control panel, Craft appends `?context=cp` to the callback URL so the callback can apply CP auth checks without any session-based context tracking.

## Default Behavior

If you do not override anything, Craft handles OAuth users like this:

### Identity

The default identity resolver uses `SocialiteUser::getId()`. If the provider does not return an ID, authentication fails.

### User Linking

The default user resolver tries, in order:

1. An existing link in `sso_identities`
2. The `ResolvingOAuthUserLink` event
3. `Users::getUserByUsernameOrEmail()` using the provider email

If none of those produce a user, the resolver returns `null`. The controller will only create a new user record afterward if `createsUsers` allows it.

If the provider does not return an email, there is no default username/email fallback match.

### User Population

The default user populator:

- sets `email` from the provider email when present
- sets `fullName` from the provider name when present
- sets `username` if it is blank:
  - to the email if `useEmailAsUsername` is enabled
  - otherwise to the provider nickname
  - otherwise to the provider email
  - otherwise to `{providerHandle}_{identity}`

### Activation

If `activatesUsers` is `true`:

- newly-created users are saved active
- matched inactive users are activated after save

If `activatesUsers` is `false`, Craft leaves account state alone. If you need custom activation rules, set `activatesUsers` accordingly and handle the rest in a custom `userPopulator`.

### User Creation

OAuth will create a new user according to `createsUsers`:

- `null` or omitted: inherit the public registration setting
- `true`: always allow provider-driven user creation
- `false`: never allow provider-driven user creation

If no existing user can be matched and creation is not allowed by that policy, the OAuth login is rejected without creating an account.

### Group Assignment

Configured `groups` are normalized from IDs, UIDs, or handles to group IDs.

Static `groups` and the `groupResolver` only apply to newly-created users. Existing users' groups are not changed by default.

If a configured group cannot be resolved, the provider is treated as invalid, its login button is omitted, and an error is logged.

### Login Completion

After Craft resolves and saves the user, it:

- links the identity in `sso_identities`
- runs the normal auth/access checks for the current context
- completes the login directly without entering the 2FA prompt

## Customization Hooks

### Custom Identity Resolver

Use `identityResolver` to change how an external identity is derived.

```php
<?php

namespace App\Auth\OAuth;

use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthIdentity;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use Laravel\Socialite\Contracts\User as SocialiteUser;

final class CustomIdentityResolver implements ResolvesOAuthIdentity
{
    public function handle(ProviderDefinition $provider, SocialiteUser $socialiteUser): string
    {
        return (string) $socialiteUser->getEmail();
    }
}
```

### Custom User Resolver

Use `userResolver` to take full control over how a Craft user is matched from OAuth data.

If you only need to augment the default matching sequence, listen for `\CraftCms\Cms\Auth\OAuth\Events\ResolvingOAuthUserLink` and set `$event->user`.

### Custom User Populator

Use `userPopulator` to control how the `User` element is populated before it is saved.

The configured populator replaces the default one. If you want the default behavior plus extra changes, compose or call into your own equivalent logic explicitly.

### Custom Group Resolver

Use `groupResolver` to return group references for newly-created users.

`ResolvesOAuthUserGroups::handle()` must return an array of group IDs, UIDs, or handles.

### Custom Button Renderer

Use `buttonRenderer` to control how a provider button is rendered on the login page.

Renderers receive `\CraftCms\Cms\Auth\OAuth\Data\ButtonData`, which contains:

- `provider`
- `isCpRequest`
- `url`
- `label`

Renderers must return `Illuminate\Support\HtmlString`.

```php
<?php

namespace App\Auth\OAuth;

use CraftCms\Cms\Auth\OAuth\Contracts\RendersOAuthButton;
use CraftCms\Cms\Auth\OAuth\Data\ButtonData;
use Illuminate\Support\HtmlString;

final class BrandButtonRenderer implements RendersOAuthButton
{
    public function handle(ButtonData $button): HtmlString
    {
        return new HtmlString(sprintf(
            '<a class="btn oauth-btn oauth-btn--%s" href="%s">%s</a>',
            e($button->provider->handle),
            e($button->url),
            e($button->label),
        ));
    }
}
```

## Rendering Buttons in Templates

Craft automatically renders configured OAuth buttons on the login page.

You can also render them yourself in Twig:

```twig
{% for button in craft.oauth.getLoginButtons() %}
  {{ button }}
{% endfor %}
```

`getLoginButtons()` returns `HtmlString` instances, so Twig will treat them as safe HTML.

## Using Custom Socialite Drivers

If you use a custom Socialite driver name, it must already be registered with Socialite before Craft tries to build the provider.

If you do not want to rely on a named driver, you can set `driver` to a Socialite-compatible provider class name instead.

## Troubleshooting

- If a provider button does not appear, check the logs first. Invalid provider config is logged and the provider is omitted.
- Make sure the callback URL registered with the OAuth provider exactly matches Craft's callback URL.
- If you expect an existing user match and one is not found, verify that the provider is returning an email or provide a custom `userResolver`.
- If your provider does not support state or your infrastructure breaks stateful callbacks, set `stateless` to `true`.
