# Native Socialite SSO

Craft CMS 6 includes a native Socialite-based SSO layer that is separate from the legacy Yii SSO system.

## Configuration

Install Socialite in the Laravel app when you want to use SSO:

```bash
composer require laravel/socialite
```

Configure provider credentials in the app’s `config/services.php`:

```php
<?php

return [
    'google' => [
        'client_id' => env('GOOGLE_OAUTH_CLIENT_ID'),
        'client_secret' => env('GOOGLE_OAUTH_CLIENT_SECRET'),
    ],
];
```

Configure enabled providers in `config/craft/general.php` via `GeneralConfig::$socialiteProviders`:

```php
use CraftCms\Cms\Auth\OAuth\Provider;
use CraftCms\Cms\Config\GeneralConfig;

return GeneralConfig::create()
    ->oAuthProviders([
        new Provider('github')
            ->name('GitHub')
            ->scopes(['user'])
            ->activatesUsers(), // Whether to auto-activate new users - this is when you want the provider to be the source of truth
        ],
    ]);
```

Providers are keyed by handle. Each provider can define:

- `driver`
- `name`
- `scopes`
- `with`
- `clientId`
- `clientSecret`
- `redirectUrl`
- `stateless`
- `activatesUsers`
- `determineUniqueIdUsing`
- `findUserUsing`
- `populateUserUsing`
- `assignUserGroups`

You can also register provider definition classes directly:

```php
use App\Auth\MarketingProviderDefinition;

return GeneralConfig::create()
    ->socialiteProviders([
        MarketingProviderDefinition::class,
    ]);
```

The callback hooks receive `CraftCms\Cms\Auth\OAuth\Profile` objects:

```php
'findUserUsing' => fn (Profile $profile) => null,
'populateUserUsing' => fn (User $user, Profile $profile) => $user,
'assignUserGroups' => fn (array $groupIds, Profile $profile) => $groupIds,
```

Existing non-active users are not auto-activated unless `activatesUsers` is enabled for the provider.

## Routes

On Craft Pro and higher, with Socialite installed, Craft registers two public web routes:

- `/auth/socialite/redirect/{provider}`
- `/auth/socialite/callback/{provider}`

The login template uses these routes automatically through `craft.auth.getSocialiteProviders()`.

## Coexisting With `yii2-adapter`

The native Socialite layer and the legacy Yii SSO layer are separate systems that only share the `sso_identities` table.

If `yii2-adapter` is installed, legacy SSO providers continue to work independently. The adapter now throws if a legacy handle conflicts with a configured core Socialite handle or an already-registered Socialite driver key.
