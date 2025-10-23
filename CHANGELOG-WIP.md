# Release Notes for Craft CMS 6.0 (WIP)

### Development
- Reference tags now support fallback values when no attribute is specified. ([#17688](https://github.com/craftcms/cms/pull/17688))

### Extensibility
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

### Plugins

#### Added
- The base `CraftCms\Cms\Plugin\Plugin` class is now a [Laravel ServiceProvider](https://laravel.com/docs/12.x/providers) which provides a new way to register components for your plugins.

#### Deprecations

- Deprecated `craft\services\Plugins`. `CraftCms\Cms\Plugin\Plugins` should be used instead.
- Deprecated `craft\base\Plugin`. `CraftCms\Cms\Plugin\Plugin` should be used instead.
- Deprecated `craft\base\PluginTrait`.
- Deprecated `craft\base\PluginInterface`. `CraftCms\Cms\Plugin\Contracts\PluginInterface` should be used instead.
- Deprecated `craft\errors\InvalidPluginException`. `CraftCms\Cms\Plugin\Exceptions\InvalidPluginException` should be used instead.

#### Controllers
- Removed `craft\controllers\PluginsController`. Use `CraftCms\Cms\Http\Controllers\PluginsController` instead.

#### Commands
- Removed `craft\console\controllers\PluginController` in favor of:
  - `CraftCms\Cms\Plugin\Commands\DisableCommand` -> `php craft plugin:disable` 
  - `CraftCms\Cms\Plugin\Commands\EnableCommand` -> `php craft plugin:enable` 
  - `CraftCms\Cms\Plugin\Commands\InstallCommand` -> `php craft plugin:install` 
  - `CraftCms\Cms\Plugin\Commands\UninstallCommand` -> `php craft plugin:uninstall` 
  - `CraftCms\Cms\Plugin\Commands\ListCommand` -> `php craft plugin:list` 
 
#### Events
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

### Migrations

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

### (Plugin) Updates

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

## System Messages

- Deprecated `craft\services\SystemMessages`. `CraftCms\Cms\SystemMessage\SystemMessages` should be used instead.
- Deprecated `craft\models\SystemMessage` and `craft\records\SystemMessage`. `CraftCms\Cms\SystemMessage\Models\SystemMessage` should be used instead.
- Replaced `craft\controllers\SystemMessagesController` with `CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController`

## Translations

- Deprecated `craft\i18n\FormatConverter`. `CraftCms\Cms\Translation\FormatConverter` should be used instead.
- Deprecated `craft\i18n\Formatter`. `CraftCms\Cms\Translation\Formatter` should be used instead.
- Deprecated `craft\i18n\I18N`. `CraftCms\Cms\Translation\I18N` should be used instead.
- Deprecated `craft\i18n\Locale`. `CraftCms\Cms\Translation\Locale` should be used instead.
- Deprecated `craft\i18n\MessageFormatter`.
- Deprecated `craft\i18n\PhpMessageSource`.
- Deprecated `craft\i18n\Translation`. `CraftCms\Cms\Support\Facades\I18N` should be used instead.
- Deprecated `Craft::t`. `CraftCms\Cms\t` should be used instead.
