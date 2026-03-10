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
    'marketing' => [
        'client_id' => env('MARKETING_OAUTH_CLIENT_ID'),
        'client_secret' => env('MARKETING_OAUTH_CLIENT_SECRET'),
    ],
];
```

Configure enabled providers in `config/craft/general.php` via `GeneralConfig::$socialiteProviders`:

```php
use CraftCms\Cms\Config\GeneralConfig;

return GeneralConfig::create()
    ->socialiteProviders([
        'marketing' => [
            'driver' => 'google',
            'name' => 'Marketing SSO',
            'clientId' => env('MARKETING_OAUTH_CLIENT_ID'),
            'clientSecret' => env('MARKETING_OAUTH_CLIENT_SECRET'),
            'scopes' => ['openid', 'email', 'profile'],
            'with' => ['prompt' => 'select_account'],
            'stateless' => false,
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
- `idpUniqueIdentifier`
- `findUser`
- `populateUser`
- `assignUserGroups`

You can also register provider definition classes directly:

```php
use App\Auth\MarketingProviderDefinition;

return GeneralConfig::create()
    ->socialiteProviders([
        MarketingProviderDefinition::class,
    ]);
```

The callback hooks receive `CraftCms\Cms\Auth\Socialite\SocialiteProfile` objects:

```php
'findUser' => fn (SocialiteProfile $profile) => null,
'populateUser' => fn (User $user, SocialiteProfile $profile) => $user,
'assignUserGroups' => fn (array $groupIds, SocialiteProfile $profile) => $groupIds,
```

## Routes

On Craft Pro and higher, with Socialite installed, Craft registers two public web routes:

- `/auth/socialite/redirect/{provider}`
- `/auth/socialite/callback/{provider}`

The login template uses these routes automatically through `craft.auth.getSocialiteProviders()`.

## Coexisting With `yii2-adapter`

The native Socialite layer and the legacy Yii SSO layer are separate systems that only share the `sso_identities` table.

If `yii2-adapter` is installed, legacy SSO providers continue to work independently. The adapter now throws if a legacy handle conflicts with a configured core Socialite handle or an already-registered Socialite driver key.
