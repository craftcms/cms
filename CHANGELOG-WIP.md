# Release Notes for Craft CMS 6.0 (WIP)

## Development
- Reference tags now support fallback values when no attribute is specified. ([#17688](https://github.com/craftcms/cms/pull/17688))
- Deprecated support for categories, global sets, and tags. ([#18009](https://github.com/craftcms/cms/pull/18009))

## Extensibility
- Added `CraftCms\Cms\Support\Arr`.
- Added `CraftCms\Cms\Support\Str`.
- `craft\services\Elements::stopCollectingCacheInfo()` no longer sets the returned duration to the `cacheDuration` config setting if a duration wasn’t explicitly declared. ([#16796](https://github.com/craftcms/cms/pull/16796))
- Deprecated `craft\helpers\ArrayHelper`. `CraftCms\Cms\Support\Arr` should be used instead.
- Deprecated `craft\helpers\ConfigHelper`. `CraftCms\Cms\Support\Config` should be used instead.
- Deprecated `craft\helpers\Diff`. `CraftCms\Cms\Support\Diff` should be used instead.
- Deprecated `craft\helpers\Html`. `CraftCms\Cms\Support\Html` should be used instead.
- Deprecated `craft\helpers\StringHelper`. `CraftCms\Cms\Support\Str` should be used instead.
- Deprecated `Craft::$app->getConfig()->getGeneral()`. `CraftCms\Cms\Config\GeneralConfig` should be used instead. This can be used through dependency injection or through `app(CraftCms\Cms\Config\GeneralConfig::class)`.
- Deprecated `craft.app.config.general` in Twig. `app.config.craft.general` should be used instead.
- Deprecated `craft\helpers\App::env()`, `CraftCms\Cms\Support\Env::get()` should be used instead.
- Deprecated `craft\helpers\Json`. `CraftCms\Cms\Support\Json` should be used instead.
- Deprecated `craft\services\Composer`. `CraftCms\Cms\Support\Composer` should be used instead.
- Deprecated `craft\enums\Color`. `CraftCms\Cms\Support\Enums\Color` should be used instead.
- Deprecated `craft\enums\AttributeStatus`. `CraftCms\Cms\Element\Enums\AttributeStatus` should be used instead.
- Deprecated `craft\enums\CmsEdition`. `CraftCms\Cms\Edition` should be used instead.
- Deprecated `craft\enums\ElementIndexViewMode`. `CraftCms\Cms\Field\Enums\ElementIndexViewMode` should be used instead.
- Deprecated `craft\enums\LicenseKeyStatus`. `CraftCms\Cms\Support\Enums\LicenseKeyStatus` should be used instead.
- Deprecated `craft\enums\MenuItemType`. `CraftCms\Cms\Element\Enums\MenuItemType` should be used instead.
- Deprecated `craft\enums\PropagationMethod`. `CraftCms\Cms\Element\Enums\PropagationMethod` should be used instead.
- Deprecated `craft\enums\TimePeriod`. `CraftCms\Cms\Support\Enums\TimePeriod` should be used instead.
- Deprecated `craft\services\Gc`. `CraftCms\Cms\GarbageCollection\GarbageCollection` should be used instead.
- Deprecated `craft\services\Api`. `CraftCms\Cms\Support\Api` should be used instead.
- Deprecated `craft\helpers\Api`. `CraftCms\Cms\Support\Api` should be used instead.
- Deprecated `craft\helpers\App`. The following classes/methods should be used instead:
  - #### General helpers
  - `App:devMode()` -> `app()->hasDebugModeEnabled()`
  - `App:parseBooleanEnv()` --> `\CraftCms\Cms\Support\Env::parseBoolean()`
  - `App:normalizeValue()` --> `\CraftCms\Cms\normalizeValue()`
  - `App:maxPowerCaptain()` --> `\CraftCms\Cms\maxPowerCaptain()`
  - `App:silence()` --> `\CraftCms\Cms\silence()`
  - `App:backtrace()` --> `\CraftCms\Cms\backtraceAsString()`
  - #### Env
  - `App:env()` --> `\CraftCms\Cms\Support\Env::get()`
  - `App:parseEnv()` --> `\CraftCms\Cms\Support\Env::parse()`
  - #### PHP
  - `App:phpVersion()` --> `\CraftCms\Cms\Support\PHP::version()`
  - `App:extensionVersion()` --> `\CraftCms\Cms\Support\PHP::extensionVersion()`
  - `App:phpConfigValueAsBool()` --> `\CraftCms\Cms\Support\PHP::configValueAsBool()`
  - `App:phpConfigValueInBytes()` --> `\CraftCms\Cms\Support\PHP::configValueInBytes()`
  - `App:phpSizeToBytes()` --> `\CraftCms\Cms\Support\PHP::sizeToBytes()`
  - `App:phpConfigValueAsPaths()` --> `\CraftCms\Cms\Support\PHP::configValueAsPaths()`
  - `App:normalizePhpPaths()` --> `\CraftCms\Cms\Support\PHP::normalizePaths()`
  - `App:isPathAllowed()` --> `\CraftCms\Cms\Support\PHP::isPathAllowed()`
  - `App:phpExecutable()` --> `\CraftCms\Cms\Support\PHP::executable()`
  - `App:testIniSet()` --> `\CraftCms\Cms\Support\PHP::testIniSet()`
  - `App:checkForValidIconv()` --> `\CraftCms\Cms\Support\PHP::checkForValidIconv()`
  - `App:supportsIdn()` --> `\CraftCms\Cms\Support\PHP::supportsIdn()`
  - #### License
  - `App:licenseKey()` --> `app(\CraftCms\Cms\License\License::class)->key()`
  - `App:licensingIssues()` --> `app(\CraftCms\Cms\License\License::class)->issues()`
  - `App:licenseShunCookieName()` --> `app(\CraftCms\Cms\License\License::class)->shunCookieName()`
  - `App:licensingIssuesHash()` --> `app(\CraftCms\Cms\License\License::class)->issuesHash()`
  -
- Deprecated `Craft::createGuzzleClient()`. `CraftCms\Cms\Support\Facades\Http::create()` should be used instead.

### Deprecator
- Added `CraftCms\Cms\Support\Facades\Deprecator`.
- Added `CraftCms\Cms\Deprecator\Commands\ClearDeprecations`.
- Removed `craft\console\controllers\ClearDeprecationsController.php`.
- Deprecated `craft\services\Deprecator`. `CraftCms\Cms\Deprecator\Deprecator` should be used instead.
- Deprecated `craft\models\DeprecationError`. `CraftCms\Cms\Deprecator\Models\DeprecationError` should be used instead.
- Deprecated `craft\errors\DeprecationException`. `CraftCms\Cms\Deprecator\Exceptions\DeprecationException` should be used instead.

### Console commands
- Added `php craft twig:cache` - Precompile Twig views
- Added `php craft twig:clear` - Clear precompiled Twig views
- `craft\console\controllers\EnvController` has been removed in favor of the classes below:
  - `CraftCms\Cms\Console\Commands\Env\EnvRemoveCommand` => `php craft env:remove`
  - `CraftCms\Cms\Console\Commands\Env\EnvSetCommand` => `php craft env:set`
  - `CraftCms\Cms\Console\Commands\Env\EnvShowCommand` => `php craft env:show`
- `craft\console\controllers\IndexAssetsController` has been removed in favor of the classes below:
  - `CraftCms\Cms\Asset\Commands\CleanupAssetIndexesCommand` => `php craft index-assets:cleanup`
  - `CraftCms\Cms\Asset\Commands\IndexAllAssetsCommand` => `php craft index-assets:all`
  - `CraftCms\Cms\Asset\Commands\IndexOneAssetCommand` => `php craft index-assets:one`

### Mutex

Craft's Mutex classes have been deprecated. [Laravel's atomic locking](https://laravel.com/docs/12.x/cache#atomic-locks) should be used instead.

- Deprecated `craft\mutex\Mutex`
- Deprecated `craft\mutex\MutexTrait`
- Deprecated `Craft::$app->getMutex()`

### Components
- Deprecated `craft\base\ComponentInterface`. `CraftCms\Cms\Component\Contracts\ComponentInterface` should be used instead.
- Deprecated `craft\base\ConfigurableComponentInterface`. `CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface` should be used instead.
- Deprecated `craft\base\SavableComponentInterface`. `CraftCms\Cms\Component\Contracts\SavableComponentInterface` should be used instead.

### Dashboard & Widgets

#### Controllers
- Removed `craft\controllers\DashboardController`. The following controllers now implement this functionality:
  - `CraftCms\Cms\Http\Controllers\Dashboard\DashboardController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\Widgets\CraftSupportController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\Widgets\FeedController`

#### Deprecations
- Deprecated `Craft::$app->getDashboard()`. `app(\CraftCms\Cms\Dashboard\Dashboard::class)` should be used instead.
- Deprecated `craft\services\Dashboard`. `CraftCms\Cms\Dashboard\Dashboard` should be used instead.
- Deprecated `craft\base\Widget`. `CraftCms\Cms\Dashboard\Widgets\Widget` should be used instead.
- Deprecated `craft\base\WidgetInterface`. `CraftCms\Cms\Dashboard\Contracts\WidgetInterface` should be used instead.
- Deprecated `craft\base\WidgetTrait`.
- Deprecated `craft\widgets\CraftSupport`. `CraftCms\Cms\Dashboard\Widgets\CraftSupport` should be used instead.
- Deprecated `craft\widgets\Feed`. `CraftCms\Cms\Dashboard\Widgets\Feed` should be used instead.
- Deprecated `craft\widgets\MissingWidget`. `CraftCms\Cms\Dashboard\Widgets\MissingWidget` should be used instead.
- Deprecated `craft\widgets\MyDrafts`. `CraftCms\Cms\Dashboard\Widgets\MyDrafts` should be used instead.
- Deprecated `craft\widgets\NewUsers`. `CraftCms\Cms\Dashboard\Widgets\NewUsers` should be used instead.
- Deprecated `craft\widgets\QuickPost`. `CraftCms\Cms\Dashboard\Widgets\QuickPost` should be used instead.
- Deprecated `craft\widgets\RecentEntries`. `CraftCms\Cms\Dashboard\Widgets\RecentEntries` should be used instead.
- Deprecated `craft\widgets\Updates`. `CraftCms\Cms\Dashboard\Widgets\Updates` should be used instead.
- Deprecated `craft\records\Widget`. `CraftCms\Cms\Dashboard\Models\Widget` should be used instead.

#### Events

- Deprecated `craft\services\Dashboard::EVENT_REGISTER_WIDGET_TYPES`. `CraftCms\Cms\Dashboard\Events\RegisterWidgetTypes` should be used instead.
- Deprecated `craft\events\WidgetEvent` in favor of the following new events:
  - `craft\services\Dashboard::EVENT_BEFORE_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaving`
  - `craft\services\Dashboard::EVENT_AFTER_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaved`
  - `craft\services\Dashboard::EVENT_BEFORE_DELETE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetDeleting`
  - `craft\services\Dashboard::EVENT_AFTER_DELETE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetDeleted`

## Assets

- Deprecated `\craft\records\Asset`. `\CraftCms\Cms\Asset\Models\Asset` should be used instead.
- Deprecated `\craft\records\AssetIndexData`. `\CraftCms\Cms\Asset\Models\AssetIndexData` should be used instead.
- Deprecated `\craft\records\AssetIndexingSession`. `\CraftCms\Cms\Asset\Models\AssetIndexingSession` should be used instead.
- Deprecated `\craft\records\Volume`. `\CraftCms\Cms\Asset\Models\Volume` should be used instead.
- Deprecated `\craft\records\VolumeFolder`. `\CraftCms\Cms\Asset\Models\VolumeFolder` should be used instead.

## Auth

- Refactored the authentication system to use Laravel’s authentication system.
- Deprecated `craft\services\Auth`. `CraftCms\Cms\Auth\Auth` should be used instead.
- Deprecated `craft\web\User`. `auth('craft')->user()` or `CraftCms\Cms\User\Elements\User` methods should be used instead.
- Deprecated `craft\events\AuthenticateUserEvent`. `CraftCms\Cms\Auth\Events\Authenticating` should be used instead.
- Deprecated `\craft\records\Authenticator`. `\CraftCms\Cms\Auth\Models\Authenticator` should be used instead.
- Deprecated `\craft\records\RecoveryCodes`. `\CraftCms\Cms\Auth\Models\RecoveryCodes` should be used instead.
- Deprecated `\craft\records\SsoIdentity`. `\CraftCms\Cms\Auth\Models\SsoIdentity` should be used instead.
- Deprecated `\craft\records\WebAuthn`. `\CraftCms\Cms\Auth\Models\WebAuthn` should be used instead.
- Deprecated `craft\behaviors\SessionBehavior::authorize`. `CraftCms\Cms\Auth\SessionAuth::authorize` should be used instead.
- Deprecated `craft\behaviors\SessionBehavior::deauthorize`. `CraftCms\Cms\Auth\SessionAuth::deauthorize` should be used instead.
- Deprecated `craft\behaviors\SessionBehavior::checkAuthorization`. `CraftCms\Cms\Auth\SessionAuth::checkAuthorization` should be used instead.
- Deprecated `GeneralConfig::elevatedSessionDuration()`. The `auth.password_timeout` config value should be used instead. To disable password confirmation (elevated sessions), you now set this value to `-1` instead of `0`.
    - Elevated sessions now work through [Laravel's password confirmation](https://laravel.com/docs/12.x/authentication#password-confirmation) system.

### Passkeys

- Added `CraftCms\Cms\Auth\Passkeys\Passkeys`.
- Deprecated `craft\services\Auth` passkey methods. The following should be used instead:
  - `Auth::hasPasskeys()` -> `app(Passkeys::class)->hasPasskeys()`
  - `Auth::getPasskeys()` -> `app(Passkeys::class)->getPasskeys()`
  - `Auth::getPasskeyCreationOptions()` -> `app(Passkeys::class)->getPasskeyCreationOptions()`
  - `Auth::verifyPasskeyCreationResponse()` -> `app(Passkeys::class)->verifyPasskeyCreationResponse()`
  - `Auth::getPasskeyRequestOptions()` -> `app(Passkeys::class)->getPasskeyRequestOptions()`
  - `Auth::verifyPasskey()` -> `app(Passkeys::class)->verifyPasskey()`
  - `Auth::deletePasskey()` -> `app(Passkeys::class)->deletePasskey()`
- Deprecated `craft\auth\passkeys\CredentialRepository`. `CraftCms\Cms\Auth\Passkeys\CredentialRepository` should be used instead.
- Deprecated `craft\auth\passkeys\WebauthnServer`. `CraftCms\Cms\Auth\Passkeys\WebauthnServer` should be used instead.

## Drafts

- Deprecated `craft\services\Drafts`. `CraftCms\Cms\Element\Drafts` should be used instead.
- Deprecated `craft\events\DraftEvent`. One of the events extending `CraftCms\Cms\Element\Events\DraftEvent` should be used instead.
- Deprecated `craft\behaviors\DraftBehavior`. `CraftCms\Cms\Element\Concerns\Draftable` should be used instead.

## Elements

- Deprecated `\craft\records\ContentBlock`. `\CraftCms\Cms\Element\Models\ContentBlock` should be used instead.
- Deprecated `\craft\records\Draft`. `\CraftCms\Cms\Element\Models\Draft` should be used instead.
- Deprecated `\craft\records\Element`. `\CraftCms\Cms\Element\Models\Element` should be used instead.
- Deprecated `\craft\records\Element_SiteSettings`. `\CraftCms\Cms\Element\Models\ElementSiteSettings` should be used instead.
- Deprecated `\craft\records\Revision`. `\CraftCms\Cms\Element\Models\Revision` should be used instead.

## ElementSources

- Deprecated `craft\services\ElementSources`. `CraftCms\Cms\Element\ElementSources` should be used instead.
- Deprecated `craft\events\DefineSourceSortOptionsEvent`. `CraftCms\Cms\Element\Events\DefineSourceSortOptions` should be used instead.
- Deprecated `craft\events\DefineSourceTableAttributesEvent`. `CraftCms\Cms\Element\Events\DefineSourceTableAttributes` should be used instead.

## Element Queries

- Deprecated `\craft\elements\db\AddressQuery`. `\CraftCms\Cms\Database\Queries\AddressQuery` should be used instead.
- Deprecated `\craft\elements\db\AssetQuery` `\CraftCms\Cms\Database\Queries\AssetQuery` should be used instead.
- Deprecated `\craft\elements\db\ContentBlockQuery` `\CraftCms\Cms\Database\Queries\ContentBlockQuery` should be used instead.
- Deprecated `\craft\elements\db\ElementQuery` `\CraftCms\Cms\Database\Queries\ElementQuery` should be used instead.
- Deprecated `\craft\elements\db\ElementQueryInterface`
- Deprecated `\craft\elements\db\EntryQuery` `\CraftCms\Cms\Database\Queries\EntryQuery` should be used instead.
- Deprecated `\craft\elements\db\UserQuery` `\CraftCms\Cms\Database\Queries\UserQuery` should be used instead.

## Entries & Entry Types

- Deprecated `craft\services\Entries`. `CraftCms\Cms\Entry\Entries` and `CraftCms\Cms\Entry\EntryTypes` should be used instead.
- Deprecated `craft\models\EntryType`. `CraftCms\Cms\Entry\Data\EntryType` should be used instead.
- Deprecated `craft\records\EntryType`. `CraftCms\Cms\Entry\Models\EntryType` should be used instead.
- Deprecated `craft\records\Entry`. `CraftCms\Cms\Entry\Models\Entry` should be used instead.
- Deprecated `craft\events\EntryTypeEvent`. One of these should be used instead:
  - `craft\services\Entries::EVENT_BEFORE_DELETE_ENTRY_TYPE` => `CraftCms\Cms\Section\Events\DeletingEntryType`
  - `craft\services\Entries::EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE` => `CraftCms\Cms\Entry\Events\ApplyingEntryTypeDelete`
  - `craft\services\Entries::EVENT_AFTER_DELETE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\EntryTypeDeleted`
  - `craft\services\Entries::EVENT_BEFORE_SAVE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\SavingEntryType`
  - `craft\services\Entries::EVENT_AFTER_SAVE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\EntryTypeSaved`
- Removed `craft\controllers\EntryTypesController` in favor of `CraftCms\Cms\Http\Controllers\EntryTypesController`
- Removed `craft\console\controllers\EntryTypesController` in favor of:
  - `CraftCms\Cms\Entry\Commands\MergeEntryTypesCommand`

## GQL

- Deprecated `\craft\records\GqlSchema`. `\CraftCms\Cms\Gql\Models\GqlSchema` should be used instead.
- Deprecated `\craft\records\GqlToken`. `\CraftCms\Cms\Gql\Models\GqlToken` should be used instead.

## HTTP

- Removed the header-setting logic in `yii2-adapter\legacy\web\Application`. The new `\CraftCms\Cms\Http\Middleware\SetHeaders` middleware handles this functionality.
- Removed the licensing issues screen logic in `yii2-adapter\legacy\web\Application`. The new `\CraftCms\Cms\Http\Middleware\EnforceLicenses` middleware handles this functionality.
- Deprecated `craft\controllers\AppController::actionLicensingIssues()`. `CraftCms\Cms\Http\Middleware\EnforceLicenses` should be used instead.

## Migrations

Craft and Yii's migrations have been removed in favor of [Laravel migrations](https://laravel.com/docs/12.x/migrations).

The `php craft fields:merge` and `php craft entry-types:merge` commands will now generate Laravel migrations.

- Deprecated `craft\db\Migration`. `CraftCms\Cms\Database\Migration` should be used instead.
- Deprecated `craft\db\MigrationManager`
- Removed `craft\helpers\MigrationHelper` as it was deprecated since 4.0.0.
- Removed `craft\console\controllers\InstallController` in favor of:
  - `CraftCms\Cms\Console\Commands\InstallCommand`
  - `CraftCms\Cms\Console\Commands\InstallCheckCommand`
- Removed `craft\console\controllers\MigrateController` in favor of:
  - `CraftCms\Cms\Database\Commands\MigrateCommand` 
- Removed `craft\console\controllers\UpController` in favor of:
  - `CraftCms\Cms\Console\Commands\UpCommand` 

## Plugins

### Added
- The base `CraftCms\Cms\Plugin\Plugin` class is now a [Laravel ServiceProvider](https://laravel.com/docs/12.x/providers) which provides a new way to register components for your plugins.

### Deprecations

- Deprecated `craft\services\Plugins`. `CraftCms\Cms\Plugin\Plugins` should be used instead.
- Deprecated `craft\base\Plugin`. `CraftCms\Cms\Plugin\Plugin` should be used instead.
- Deprecated `craft\base\PluginTrait`.
- Deprecated `craft\base\PluginInterface`. `CraftCms\Cms\Plugin\Contracts\PluginInterface` should be used instead.
- Deprecated `craft\errors\InvalidPluginException`. `CraftCms\Cms\Plugin\Exceptions\InvalidPluginException` should be used instead.

### Controllers
- Removed `craft\controllers\PluginsController`. Use `CraftCms\Cms\Http\Controllers\PluginsController` instead.

### Commands
- Removed `craft\console\controllers\PluginController` in favor of:
  - `CraftCms\Cms\Plugin\Commands\DisableCommand` -> `php craft plugin:disable`
  - `CraftCms\Cms\Plugin\Commands\EnableCommand` -> `php craft plugin:enable`
  - `CraftCms\Cms\Plugin\Commands\InstallCommand` -> `php craft plugin:install`
  - `CraftCms\Cms\Plugin\Commands\UninstallCommand` -> `php craft plugin:uninstall`
  - `CraftCms\Cms\Plugin\Commands\ListCommand` -> `php craft plugin:list`

### Events
- Deprecated `craft\events\PluginEvent` in favor of the following new events:
  - `craft\base\Plugin::EVENT_BEFORE_SAVE_SETTINGS` => `CraftCms\Cms\Component\Events\ComponentEvent`
  - `craft\base\Plugin::EVENT_AFTER_SAVE_SETTINGS` => `CraftCms\Cms\Component\Events\ComponentEvent`
  - `craft\services\Plugins::EVENT_BEFORE_DISABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\DisablingPlugin`;
  - `craft\services\Plugins::EVENT_BEFORE_ENABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\EnablingPlugin`;
  - `craft\services\Plugins::EVENT_BEFORE_INSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\InstallingPlugin`;
  - `craft\services\Plugins::EVENT_BEFORE_LOAD_PLUGINS` => `CraftCms\Cms\Plugin\Events\LoadingPlugins`;
  - `craft\services\Plugins::EVENT_BEFORE_SAVE_PLUGIN_SETTINGS` => `CraftCms\Cms\Plugin\Events\SavingPluginSettings`;
  - `craft\services\Plugins::EVENT_BEFORE_UNINSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\UninstallingPlugin`;
  - `craft\services\Plugins::EVENT_AFTER_DISABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginDisabled`;
  - `craft\services\Plugins::EVENT_AFTER_ENABLE_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginEnabled`;
  - `craft\services\Plugins::EVENT_AFTER_INSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginInstalled`;
  - `craft\services\Plugins::EVENT_AFTER_LOAD_PLUGINS` => `CraftCms\Cms\Plugin\Events\PluginsLoaded`;
  - `craft\services\Plugins::EVENT_AFTER_SAVE_PLUGIN_SETTINGS` => `CraftCms\Cms\Plugin\Events\PluginSettingsSaved`;
  - `craft\services\Plugins::EVENT_AFTER_UNINSTALL_PLUGIN` => `CraftCms\Cms\Plugin\Events\PluginUninstalled`;

## Request

- Added `Request::isPreview()` macro for detecting preview requests via `x-craft-preview` or `x-craft-live-preview` parameters.

## Updates

The `craft\services\Updates` internal service has been removed. `CraftCms\Cms\Updates\Updates` should be used instead.

Moved the following controllers:
- `craft\controllers\ConfigSyncController` => `CraftCms\Cms\Http\Controllers\ConfigSyncController`
- `craft\controllers\InstallController` => `CraftCms\Cms\Http\Controllers\InstallController`
- `craft\controllers\MigrateController` => `CraftCms\Cms\Http\Controllers\MigrateController`
- `craft\controllers\PluginStoreController` => `CraftCms\Cms\Http\Controllers\PluginStore\PluginStoreController`
- `craft\controllers\PluginStore\InstallController` => `CraftCms\Cms\Http\Controllers\PluginStore\InstallController`
- `craft\controllers\PluginStore\RemoveController` => `CraftCms\Cms\Http\Controllers\PluginStore\RemoveController`
- `craft\controllers\UpdaterController` => `CraftCms\Cms\Http\Controllers\Updates\UpdaterController`
- `craft\controllers\UpdatesController` => `CraftCms\Cms\Http\Controllers\Updates\UpdatesController`
- `craft\console\controllers\UpdateController` in favor of these commands:
  - `CraftCms\Cms\Updates\Commands\UpdateCommand`
  - `CraftCms\Cms\Updates\Commands\ComposerInstallCommand`
  - `CraftCms\Cms\Updates\Commands\InfoCommand`

#### Deprecations & removals
- Deprecated `craft\helpers\Install`. `CraftCms\Cms\Site\Concerns\SiteDefaults` should be used instead.
- Deprecated `craft\helpers\Update`. The only method was `checkPhpConstraint` which is now available on `CraftCms\Cms\Support\PHP::checkConstraint()`
- Removed `craft\events\UpdateReleaseEvent` in favor of `CraftCms\Cms\Updates\Events\CriticalUpdateReleasedEvent`
- Removed `craft\models\Update`. `CraftCms\Cms\Updates\Data\Update` should be used instead.
- Removed `craft\models\UpdateRelease`. `CraftCms\Cms\Updates\Data\UpdateRelease` should be used instead.
- Removed `craft\models\Updates`. `CraftCms\Cms\Updates\Data\Updates` should be used instead.

### Users

- Removed `craft\console\controllers\UsersController` in favor of the following commands (signatures are the same):
  - `CraftCms\Cms\User\Commands\ActivationUrlCommand`
  - `CraftCms\Cms\User\Commands\CreateCommand`
  - `CraftCms\Cms\User\Commands\DeleteCommand`
  - `CraftCms\Cms\User\Commands\ImpersonateCommand`
  - `CraftCms\Cms\User\Commands\ListAdminsCommand`
  - `CraftCms\Cms\User\Commands\LogoutAllCommand`
  - `CraftCms\Cms\User\Commands\PasswordResetUrlCommand`
  - `CraftCms\Cms\User\Commands\Remove2faCommand`
  - `CraftCms\Cms\User\Commands\SetPasswordCommand`
  - `CraftCms\Cms\User\Commands\UnlockCommand`

## Project Config

- Deprecated `craft\services\ProjectConfig`. `CraftCms\Cms\ProjectConfig\ProjectConfig` should be used instead.
- Removed `craft\controllers\ProjectConfigController` in favor of `CraftCms\Cms\Http\Controllers\Utilities\ProjectConfigController`
- Removed `craft\console\controllers\PcController` & `craft\console\controllers\ProjectConfigController` in favor of the following commands:
  - `CraftCms\Cms\ProjectConfig\Commands\ApplyCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\DiffCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\ExportCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\GetCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\RebuildCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\RemoveCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\SetCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\TouchCommand`
  - `CraftCms\Cms\ProjectConfig\Commands\WriteCommand`
  - All commands can be called using either `php craft project-config` or `php craft pc`
- Deprecated `craft\events\ConfigEvent` in favor of the following events:
  - `CraftCms\Cms\ProjectConfig\Events\AddingItem`
  - `CraftCms\Cms\ProjectConfig\Events\ItemAdded`
  - `CraftCms\Cms\ProjectConfig\Events\UpdatingItem`
  - `CraftCms\Cms\ProjectConfig\Events\ItemUpdated`
  - `CraftCms\Cms\ProjectConfig\Events\RemovingItem`
  - `CraftCms\Cms\ProjectConfig\Events\ItemRemoved`
- Deprecated `craft\services\ProjectConfig::EVENT_AFTER_APPLY_CHANGES`
  - Added `CraftCms\Cms\ProjectConfig\Events\ChangesApplied`
- Deprecated `craft\services\ProjectConfig::EVENT_AFTER_WRITE_YAML_FILES`
- Added `CraftCms\Cms\ProjectConfig\Events\YamlFilesWritten`
- Deprecated `craft\services\ProjectConfig::EVENT_REBUILD`
  - Added `CraftCms\Cms\ProjectConfig\Events\RebuildConfig`
- Removed `craft\errors\BusyResourceException` in favor of `CraftCms\Cms\ProjectConfig\Exceptions\BusyResourceException`
- Removed `craft\errors\StaleResourceException` in favor of `CraftCms\Cms\ProjectConfig\Exceptions\StaleResourceException`
- Added `CraftCms\Cms\ProjectConfig\Exceptions\ReadonlyException`
- Removed `craft\models\ProjectConfigData` in favor of `CraftCms\Cms\ProjectConfig\Data\ProjectConfigData`
- Removed `craft\models\ReadOnlyProjectConfigData` in favor of `CraftCms\Cms\ProjectConfig\Data\ReadOnlyProjectConfigData`
- Deprecated `craft\helpers\ProjectConfig`. `CraftCms\Cms\ProjectConfig\ProjectConfigHelper` should be used instead.

## Revisions

- Deprecated `craft\services\Revisions`. `CraftCms\Cms\Element\Revisions` should be used instead.
- Deprecated `craft\events\RevisionEvent`. One of the events extending `CraftCms\Cms\Element\Events\RevisionEvent` should be used instead.
- Deprecated `craft\behaviors\RevisionBehavior`. `CraftCms\Cms\Element\Concerns\Revisionable` should be used instead.

## Routes

- Deprecated `craft\services\Routes`. `CraftCms\Cms\Route\Routes` should be used instead.
- Using routes in `config/routes.php` is no longer supported. Register routes using [Laravel's routing](https://laravel.com/docs/12.x/routing) instead.

## Sections

- Deprecated the section related methods in `craft\services\Entries`. `CraftCms\Cms\Section\Sections` should be used instead.
- Deprecated `craft\models\Section`. `CraftCms\Cms\Section\Data\Section` should be used instead.
- Deprecated `craft\records\Section`. `CraftCms\Cms\Section\Models\Section` should be used instead.
- Deprecated `craft\models\Section_SiteSettings`. `CraftCms\Cms\Section\Data\SectionSiteSettings` should be used instead.
- Deprecated `craft\records\Section_SiteSettings`. `CraftCms\Cms\Section\Models\SectionSiteSettings` should be used instead.
- Deprecated `craft\events\SectionEvent`. One of these should be used instead:
  - `craft\services\Entries::EVENT_BEFORE_DELETE_SECTION` => `CraftCms\Cms\Section\Events\DeletingSection`
  - `craft\services\Entries::EVENT_BEFORE_APPLY_SECTION_DELETE` => `CraftCms\Cms\Section\Events\ApplyingSectionDelete`
  - `craft\services\Entries::EVENT_AFTER_DELETE_SECTION` => `CraftCms\Cms\Section\Events\SectionDeleted`
  - `craft\services\Entries::EVENT_BEFORE_SAVE_SECTION` => `CraftCms\Cms\Section\Events\SavingSection`
  - `craft\services\Entries::EVENT_AFTER_SAVE_SECTION` => `CraftCms\Cms\Section\Events\SectionSaved`
- Removed `craft\controllers\SectionsController` in favor of `CraftCms\Cms\Http\Controllers\SectionsController`
- Removed `craft\console\controllers\SectionsController` in favor of:
  - `CraftCms\Cms\Section\Commands\CreateCommand`
  - `CraftCms\Cms\Section\Commands\DeleteCommand`
- Added `CraftCms\Cms\Section\Enums\DefaultPlacement`
- Added `CraftCms\Cms\Section\Enums\SectionType`

## Sites

- Deprecated `craft\services\Sites`. `CraftCms\Cms\Site\Sites` should be used instead.
- Deprecated `craft\models\Site`. `CraftCms\Cms\Site\Data\Site` should be used instead.
- Deprecated `craft\models\SiteGroup`. `CraftCms\Cms\Site\Data\SiteGroup` should be used instead.
- Deprecated `craft\records\Site`. `CraftCms\Cms\Site\Models\Site` should be used instead.
- Deprecated `craft\records\SiteGroup`. `CraftCms\Cms\Site\Models\SiteGroup` should be used instead.
- Deprecated `craft\events\SiteEvent`. One of `CraftCms\Cms\Site\Events\*` should be used instead.
- Deprecated `craft\events\DeleteSiteEvent`. One of `CraftCms\Cms\Site\Events\DeletingSite` or `CraftCms\Cms\Site\Events\SiteDeleted` should be used instead.
- Deprecated `craft\events\ReorderSitesEvent`. One of `CraftCms\Cms\Site\Events\ReorderingSites` or `CraftCms\Cms\Site\Events\SitesReordered` should be used instead.
- Deprecated `craft\events\SiteGroupEvent`. One of `CraftCms\Cms\Site\Events\*` should be used instead.
- Deprecated `craft\errors\SiteNotFoundException`. `CraftCms\Cms\Site\Exceptions\SiteNotFoundException` should be used instead.
- Deprecated `craft\errors\SiteGroupNotFoundException`.

- Removed `craft\controllers\SitesController` in favor of:
  - `CraftCms\Cms\Http\Controllers\Settings\SitesController` 
  - `CraftCms\Cms\Http\Controllers\Settings\SiteGroupsController` 

## Structures

- Deprecated `craft\services\Structures`. `CraftCms\Cms\Structure\Structures` should be used instead.
- Deprecated `craft\models\Structure`. `CraftCms\Cms\Structure\Data\Structure` should be used instead.
- Deprecated `craft\records\Structure`. `CraftCms\Cms\Structure\Models\Structure` should be used instead.
- Deprecated `craft\records\StructureElement`. `CraftCms\Cms\Structure\Models\StructureElement` should be used instead.
- Replaced `craft\controllers\StructuresController`. `CraftCms\Cms\Http\Controllers\StructuresController`.
- Replaced structure related commands in `craft\console\controllers\RepairController` with:
  - `\CraftCms\Cms\Structure\Commands\RepairCategoryGroupStructureCommand`
  - `\CraftCms\Cms\Structure\Commands\RepairSectionStructureCommand`

## System Messages

- Deprecated `craft\services\SystemMessages`. `CraftCms\Cms\SystemMessage\SystemMessages` should be used instead.
- Deprecated `craft\models\SystemMessage` and `craft\records\SystemMessage`. `CraftCms\Cms\SystemMessage\Models\SystemMessage` should be used instead.
- Replaced `craft\controllers\SystemMessagesController` with `CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController`

## Tokens

- Deprecated `craft\services\Tokens`. `CraftCms\Cms\RouteToken\RouteTokens` should be used instead.
- Deprecated `craft\records\Token`. `CraftCms\Cms\RouteToken\Models\RouteToken` should be used instead.

## Translations

- Deprecated `craft\i18n\FormatConverter`. `CraftCms\Cms\Translation\FormatConverter` should be used instead.
- Deprecated `craft\i18n\Formatter`. `CraftCms\Cms\Translation\Formatter` should be used instead.
- Deprecated `craft\i18n\I18N`. `CraftCms\Cms\Translation\I18N` should be used instead.
- Deprecated `craft\i18n\Locale`. `CraftCms\Cms\Translation\Locale` should be used instead.
- Deprecated `craft\i18n\MessageFormatter`.
- Deprecated `craft\i18n\PhpMessageSource`.
- Deprecated `craft\i18n\Translation`. `CraftCms\Cms\Support\Facades\I18N` should be used instead.
- Deprecated `Craft::t`. `CraftCms\Cms\t` should be used instead.

## Users

- `CraftCms\Cms\User\Elements\User` now implements `Illuminate\Contracts\Auth\Authenticatable` and `Illuminate\Contracts\Auth\Access\Authorizable`.
- Removed `craft\controllers\UsersController` in favor of:
  - `CraftCms\Cms\Http\Controllers\Users\ActivateController`.
  - `CraftCms\Cms\Http\Controllers\Users\PasswordController`.
  - `CraftCms\Cms\Http\Controllers\Users\SaveUserController`.
- Removed `\craft\controllers\UserSettingsController` in favor of:
  - `CraftCms\Cms\Http\Controllers\Settings\UserGroupsController`
  - `CraftCms\Cms\Http\Controllers\Settings\UserSettingsController`
- Deprecated `UserGroupEvent` in favor of:
  - `CraftCms\Cms\User\Events\SavingUserGroup`
  - `CraftCms\Cms\User\Events\UserGroupSaved`
  - `CraftCms\Cms\User\Events\ApplyingUserGroupDelete`
  - `CraftCms\Cms\User\Events\DeletingUserGroup`
  - `CraftCms\Cms\User\Events\UserGroupDeleted`
- Deprecated `\craft\exceptions\UserGroupNotFoundException`.
- Deprecated `\craft\services\UserGroups`. `CraftCms\Cms\User\UserGroups` should be used instead.
- Deprecated `\craft\models\UserGroup`. `CraftCms\Cms\User\Data\UserGroup` should be used instead.
- Deprecated `\craft\records\User`. `\CraftCms\Cms\User\Models\User` should be used instead.
- Deprecated `\craft\records\UserGroup`. `\CraftCms\Cms\User\Models\UserGroup` should be used instead.
- Deprecated `\craft\records\UserPermission`. `\CraftCms\Cms\User\Models\UserPermission` should be used instead.
- Deprecated `craft\services\UserPermissions`. `CraftCms\Cms\User\UserPermissions` should be used instead.
- Deprecated `craft.app.userPermissions`. `craft.userPermissions` should be used instead.
- Deprecated `craft\events\DefineEditUserScreensEvent`. `CraftCms\Cms\User\Events\DefineEditUserScreens` should be used instead.
