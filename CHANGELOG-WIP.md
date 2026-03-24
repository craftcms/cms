# Release Notes for Craft CMS 6.0 (WIP)

## Development
- Reference tags now support fallback values when no attribute is specified. ([#17688](https://github.com/craftcms/cms/pull/17688))
- Deprecated support for categories, global sets, and tags. ([#18009](https://github.com/craftcms/cms/pull/18009))

## Extensibility
- Added `CraftCms\Cms\Support\Arr`.
- Added `CraftCms\Cms\Support\DateTimeHelper`.
- Added `CraftCms\Cms\Support\File`.
- Added `CraftCms\Cms\Support\Facades\Path`.
- Added `CraftCms\Cms\Support\Facades\Markdown`.
- Added `CraftCms\Cms\Support\Path`.
- Added `CraftCms\Cms\Support\Str`.
- Added `CraftCms\Cms\Support\URL`.
- Added `CraftCms\Cms\action_url()`, `CraftCms\Cms\cp_url()`, and `CraftCms\Cms\site_url()` helper functions.
- `craft\services\Elements::stopCollectingCacheInfo()` no longer sets the returned duration to the `cacheDuration` config setting if a duration wasn’t explicitly declared. ([#16796](https://github.com/craftcms/cms/pull/16796))
- Deprecated `craft\helpers\ArrayHelper`. `CraftCms\Cms\Support\Arr` should be used instead.
- Deprecated `craft\helpers\ConfigHelper`. `CraftCms\Cms\Support\Config` should be used instead.
- Deprecated `craft\helpers\DateTimeHelper`. `CraftCms\Cms\Support\DateTimeHelper` should be used instead.
- Deprecated `craft\helpers\Diff`. `CraftCms\Cms\Support\Diff` should be used instead.
- Deprecated `craft\helpers\Html`. `CraftCms\Cms\Support\Html` should be used instead.
- Deprecated `craft\helpers\HtmlPurifier`. `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers` should be used for HTML sanitization, and `CraftCms\Cms\Support\Str` should be used for UTF-8 cleanup instead.
- Deprecated `craft\helpers\HtmlPurifier::process()`. `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers::sanitize()` should be used instead.
- Deprecated `craft\helpers\HtmlPurifier::cleanUtf8()`.
- Deprecated `craft\helpers\HtmlPurifier::convertToUtf8()`. `CraftCms\Cms\Support\Str::convertToUtf8()` should be used instead.
- Deprecated `craft\helpers\HtmlPurifier::configure()`. `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers::defaults()` or a custom sanitizer registration should be used instead.
- Deprecated `config/craft/htmlpurifier/*.json` sanitizer config files. Sanitizers should be registered on `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers` instead.
- Deprecated `craft\services\Path`. `CraftCms\Cms\Support\Path` should be used instead.
- Deprecated `craft\helpers\SessionHelper`. `Illuminate\Support\Facades\Session` should be used instead.
- Deprecated `craft\helpers\Sequence`. `CraftCms\Cms\Support\Sequence` should be used instead.
- Deprecated `craft\helpers\StringHelper`. `CraftCms\Cms\Support\Str` should be used instead.
- Deprecated `Craft::$app->getConfig()->getGeneral()`. `CraftCms\Cms\Config\GeneralConfig` should be used instead. This can be used through dependency injection or through `app(CraftCms\Cms\Config\GeneralConfig::class)`.
- Deprecated `craft.app.config.general` in Twig. `app.config.craft.general` should be used instead.
- Deprecated `craft\helpers\App::env()`, `CraftCms\Cms\Support\Env::get()` should be used instead.
- Deprecated `craft\markdown\Markdown`, `craft\markdown\GithubMarkdown`, `craft\markdown\MarkdownExtra`, and `craft\markdown\PreEncodedMarkdown`. `CraftCms\Cms\Support\Facades\Markdown` should be used instead.
- Deprecated `craft\helpers\DateRange`. `CraftCms\Cms\Shared\Enums\DateRangeType` and `CraftCms\Cms\Shared\Enums\DateRangePeriod` should be used instead.
- Deprecated `craft\helpers\Cp`. One of the following classes should be used instead:
  - `CraftCms\Cms\Cp\Alerts`
  - `CraftCms\Cms\Cp\FormFields`
  - `CraftCms\Cms\Cp\Html\ContentHtml`
  - `CraftCms\Cms\Cp\Html\ElementHtml`
  - `CraftCms\Cms\Cp\Html\ElementIndexHtml`
  - `CraftCms\Cms\Cp\Html\MenuHtml`
  - `CraftCms\Cms\Cp\Html\PreviewHtml`
  - `CraftCms\Cms\Cp\Html\StatusHtml`
  - `CraftCms\Cms\Cp\Icons`
  - `CraftCms\Cms\Cp\RequestedSite`
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
- Deprecated `craft\nameparsing\CustomLanguage`. `CraftCms\Cms\Shared\Nameparser\CustomLanguage` should be used instead.
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
- Deprecated `craft\helpers\FileHelper`. `CraftCms\Cms\Support\File` should be used instead.
- Deprecated `craft\helpers\UrlHelper`. `CraftCms\Cms\Support\URL` should be used instead.

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
- `craft\console\controllers\BaseSystemStatusController`, `craft\console\controllers\OnController`, and `craft\console\controllers\OffController` have been removed in favor of the classes below:
  - `CraftCms\Cms\Console\Commands\System\OnCommand` => `php craft on`
  - `CraftCms\Cms\Console\Commands\System\OffCommand` => `php craft off`
- `craft\console\controllers\ElementsController` has been removed in favor of the classes below:
  - `CraftCms\Cms\Element\Commands\DeleteCommand` => `php craft elements:delete`
  - `CraftCms\Cms\Element\Commands\DeleteAllOfTypeCommand` => `php craft elements:delete-all-of-type`
  - `CraftCms\Cms\Element\Commands\RestoreCommand` => `php craft elements:restore`
- `craft\console\controllers\UpdateStatusesController` has been removed in favor of the class below:
  - `CraftCms\Cms\Entry\Commands\UpdateStatusesCommand` => `php craft update-statuses`
- `craft\console\controllers\utils\FixElementUidsController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\FixElementUidsCommand` => `php craft utils:fix-element-uids`
- `craft\console\controllers\utils\FixFieldLayoutUidsController` has been removed in favor of the class below:
  - `CraftCms\Cms\Console\Commands\Utils\FixFieldLayoutUidsCommand` => `php craft utils:fix-field-layout-uids`

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

## Address

- Added `CraftCms\Cms\Support\Facades\Addresses`.

## Assets

- Added `CraftCms\Cms\Asset\AssetsHelper`.
- Added `CraftCms\Cms\Support\Facades\Assets`.
- Added `CraftCms\Cms\Support\Facades\AssetIndexer` facade.
- Added `CraftCms\Cms\Support\Facades\Folders`.
- Deprecated `craft\helpers\Assets`. `CraftCms\Cms\Asset\AssetsHelper` should be used instead.
- Deprecated `craft\services\Assets`. `CraftCms\Cms\Asset\Assets` and `CraftCms\Cms\Asset\Folders` should be used instead.
- Deprecated `\craft\records\Asset`. `\CraftCms\Cms\Asset\Models\Asset` should be used instead.
- Deprecated `\craft\records\AssetIndexData`. `\CraftCms\Cms\Asset\Models\AssetIndexData` should be used instead.
- Deprecated `\craft\records\AssetIndexingSession`. `\CraftCms\Cms\Asset\Models\AssetIndexingSession` should be used instead.
- Deprecated `\craft\records\Volume`. `\CraftCms\Cms\Asset\Models\Volume` should be used instead.
- Deprecated `\craft\records\VolumeFolder`. `\CraftCms\Cms\Asset\Models\VolumeFolder` should be used instead.
- Deprecated `\craft\controllers\AssetIndexesController`. `\CraftCms\Cms\Http\Controllers\Utilities\AssetIndexesController` should be used instead.
- Deprecated `craft\services\AssetIndexer`. `CraftCms\Cms\Asset\AssetIndexer` should be used instead.
- Deprecated `craft\models\AssetIndexData`. `CraftCms\Cms\Asset\Data\AssetIndexEntry` should be used instead.
- Deprecated `craft\models\AssetIndexingSession`. `CraftCms\Cms\Asset\Data\IndexingSession` should be used instead.
- Deprecated `craft\errors\AssetException`. `CraftCms\Cms\Asset\Exceptions\AssetException` should be used instead.
- Deprecated `craft\errors\AssetDisallowedExtensionException`. `CraftCms\Cms\Asset\Exceptions\AssetDisallowedExtensionException` should be used instead.
- Deprecated `craft\errors\AssetNotIndexableException`. `CraftCms\Cms\Asset\Exceptions\AssetNotIndexableException` should be used instead.
- Deprecated `craft\errors\FileException`. `CraftCms\Cms\Asset\Exceptions\FileException` should be used instead.
- Deprecated `craft\errors\ImageException`. `CraftCms\Cms\Asset\Exceptions\ImageException` should be used instead.
- Deprecated `craft\errors\ImageTransformException`. `CraftCms\Cms\Asset\Exceptions\ImageTransformException` should be used instead.
- Deprecated `craft\errors\MissingAssetException`. `CraftCms\Cms\Asset\Exceptions\MissingAssetException` should be used instead.
- Deprecated `craft\errors\MissingVolumeFolderException`. `CraftCms\Cms\Asset\Exceptions\MissingVolumeFolderException` should be used instead.
- Deprecated `craft\errors\VolumeException`. `CraftCms\Cms\Asset\Exceptions\VolumeException` should be used instead.

### Events

- Added `CraftCms\Cms\Asset\Events\RegisterFileKinds`.
- Added `CraftCms\Cms\Asset\Events\SetAssetFilename`.
- Deprecated `craft\events\SetAssetFilenameEvent`. `CraftCms\Cms\Asset\Events\SetAssetFilename` should be used instead.
- Deprecated `craft\events\RegisterAssetFileKindsEvent`. `CraftCms\Cms\Asset\Events\RegisterFileKinds` should be used instead.
- Deprecated `craft\events\ReplaceAssetEvent` in favor of the following new events:
  - `craft\services\Assets::EVENT_BEFORE_REPLACE_ASSET` => `CraftCms\Cms\Asset\Events\BeforeReplaceAsset`
  - `craft\services\Assets::EVENT_AFTER_REPLACE_ASSET` => `CraftCms\Cms\Asset\Events\AfterReplaceAsset`
- Deprecated `craft\events\DefineAssetThumbUrlEvent`. `CraftCms\Cms\Asset\Events\DefineThumbUrl` should be used instead.
- Deprecated `craft\events\AssetPreviewEvent`. `CraftCms\Cms\Asset\Events\RegisterPreviewHandler` should be used instead.

## Auth

- Refactored the authentication system to use Laravel's authentication system.
- Added `CraftCms\Cms\Auth\Events\SettingPassword`.
- Added `CraftCms\Cms\User\Notifications\ResetPasswordNotification`.
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
- Deprecated `craft\services\Users::isVerificationCodeValidForUser()`. `Password::broker('craft')->tokenExists($user, $code)` should be used instead.
- Deprecated `GeneralConfig::elevatedSessionDuration()`. The `auth.password_timeout` config value should be used instead. To disable password confirmation (elevated sessions), you now set this value to `-1` instead of `0`.
    - Elevated sessions now work through [Laravel's password confirmation](https://laravel.com/docs/12.x/authentication#password-confirmation) system.
- Removed `craft\controllers\AuthController`. The following controllers now implement this functionality:
    - `CraftCms\Cms\Http\Controllers\Users\AuthMethodController`
    - `CraftCms\Cms\Http\Controllers\Users\PasskeysController`
    - `CraftCms\Cms\Http\Controllers\Users\RecoveryCodesController`
- Removed `verificationCode` and `verificationCodeIssuedDate` columns on the `users` table in favor of the `password_reset_tokens` table.

### Authorization

Craft 6 now uses [Laravel's authorization system](https://laravel.com/docs/12.x/authorization) for element authorization checks.

#### Added

- Added `CraftCms\Cms\Auth\Events\AuthorizingElement` event for customizing element authorization.
- Added `CraftCms\Cms\Element\Policies\ElementPolicy` base policy for element authorization.
- Added element-specific authorization policies:
  - `CraftCms\Cms\Address\Policies\AddressPolicy`
  - `CraftCms\Cms\Asset\Policies\AssetPolicy`
  - `CraftCms\Cms\Entry\Policies\EntryPolicy`
  - `CraftCms\Cms\User\Policies\UserPolicy`
  - `CraftCms\Cms\Field\Policies\ContentBlockPolicy`

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

## Conditions

### Added

- Added `CraftCms\Cms\Support\Facades\Conditions`.

### Deprecations

#### Service

- Deprecated `craft\services\Conditions`. `CraftCms\Cms\Condition\Conditions` should be used instead.

#### Base Conditions

- Deprecated `craft\base\conditions\ConditionInterface`. `CraftCms\Cms\Condition\Contracts\ConditionInterface` should be used instead.
- Deprecated `craft\base\conditions\ConditionRuleInterface`. `CraftCms\Cms\Condition\Contracts\ConditionRuleInterface` should be used instead.
- Deprecated `craft\base\conditions\BaseCondition`. `CraftCms\Cms\Condition\BaseCondition` should be used instead.
- Deprecated `craft\base\conditions\BaseConditionRule`. `CraftCms\Cms\Condition\BaseConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseTextConditionRule`. `CraftCms\Cms\Condition\BaseTextConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseNumberConditionRule`. `CraftCms\Cms\Condition\BaseNumberConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseSelectConditionRule`. `CraftCms\Cms\Condition\BaseSelectConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseMultiSelectConditionRule`. `CraftCms\Cms\Condition\BaseMultiSelectConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseLightswitchConditionRule`. `CraftCms\Cms\Condition\BaseLightswitchConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseDateRangeConditionRule`. `CraftCms\Cms\Condition\BaseDateRangeConditionRule` should be used instead.
- Deprecated `craft\base\conditions\BaseElementSelectConditionRule`. `CraftCms\Cms\Condition\BaseElementSelectConditionRule` should be used instead.

#### Elements

- Deprecated `craft\elements\conditions\ElementCondition`. `CraftCms\Cms\Element\Conditions\ElementCondition` should be used instead.
- Deprecated `craft\elements\conditions\ElementConditionInterface`. `CraftCms\Cms\Element\Conditions\Contracts\ElementConditionInterface` should be used instead.
- Deprecated `craft\elements\conditions\ElementConditionRuleInterface`. `CraftCms\Cms\Element\Conditions\Contracts\ElementConditionRuleInterface` should be used instead.
- Deprecated `craft\elements\conditions\HintableConditionRuleTrait`. `CraftCms\Cms\Element\Conditions\HintableConditionRuleTrait` should be used instead.
- Deprecated `craft\elements\conditions\TitleConditionRule`. `CraftCms\Cms\Element\Conditions\TitleConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\SlugConditionRule`. `CraftCms\Cms\Element\Conditions\SlugConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\UriConditionRule`. `CraftCms\Cms\Element\Conditions\UriConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\IdConditionRule`. `CraftCms\Cms\Element\Conditions\IdConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\StatusConditionRule`. `CraftCms\Cms\Element\Conditions\StatusConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\HasUrlConditionRule`. `CraftCms\Cms\Element\Conditions\HasUrlConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\HasDescendantsRule`. `CraftCms\Cms\Element\Conditions\HasDescendantsRule` should be used instead.
- Deprecated `craft\elements\conditions\LevelConditionRule`. `CraftCms\Cms\Element\Conditions\LevelConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\DateCreatedConditionRule`. `CraftCms\Cms\Element\Conditions\DateCreatedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\DateUpdatedConditionRule`. `CraftCms\Cms\Element\Conditions\DateUpdatedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\SiteConditionRule`. `CraftCms\Cms\Element\Conditions\SiteConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\SiteGroupConditionRule`. `CraftCms\Cms\Element\Conditions\SiteGroupConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\LanguageConditionRule`. `CraftCms\Cms\Element\Conditions\LanguageConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\RelatedToConditionRule`. `CraftCms\Cms\Element\Conditions\RelatedToConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\NotRelatedToConditionRule`. `CraftCms\Cms\Element\Conditions\NotRelatedToConditionRule` should be used instead.

#### Entries

- Deprecated `craft\elements\conditions\entries\EntryCondition`. `CraftCms\Cms\Entry\Conditions\EntryCondition` should be used instead.
- Deprecated `craft\elements\conditions\entries\PostDateConditionRule`. `CraftCms\Cms\Entry\Conditions\PostDateConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\ExpiryDateConditionRule`. `CraftCms\Cms\Entry\Conditions\ExpiryDateConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\SectionConditionRule`. `CraftCms\Cms\Entry\Conditions\SectionConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\TypeConditionRule`. `CraftCms\Cms\Entry\Conditions\TypeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\AuthorConditionRule`. `CraftCms\Cms\Entry\Conditions\AuthorConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\AuthorGroupConditionRule`. `CraftCms\Cms\Entry\Conditions\AuthorGroupConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\ViewableConditionRule`. `CraftCms\Cms\Entry\Conditions\ViewableConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\SavableConditionRule`. `CraftCms\Cms\Entry\Conditions\SavableConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\entries\FieldConditionRule`. `CraftCms\Cms\Entry\Conditions\FieldConditionRule` should be used instead.

#### Users

- Deprecated `craft\elements\conditions\users\UserCondition`. `CraftCms\Cms\User\Conditions\UserCondition` should be used instead.
- Deprecated `craft\elements\conditions\users\UsernameConditionRule`. `CraftCms\Cms\User\Conditions\UsernameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\EmailConditionRule`. `CraftCms\Cms\User\Conditions\EmailConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\FirstNameConditionRule`. `CraftCms\Cms\User\Conditions\FirstNameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\LastNameConditionRule`. `CraftCms\Cms\User\Conditions\LastNameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\GroupConditionRule`. `CraftCms\Cms\User\Conditions\GroupConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\AdminConditionRule`. `CraftCms\Cms\User\Conditions\AdminConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\CredentialedConditionRule`. `CraftCms\Cms\User\Conditions\CredentialedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\LastLoginDateConditionRule`. `CraftCms\Cms\User\Conditions\LastLoginDateConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\users\AffiliatedSiteConditionRule`. `CraftCms\Cms\User\Conditions\AffiliatedSiteConditionRule` should be used instead.

#### Assets

- Deprecated `craft\elements\conditions\assets\AssetCondition`. `CraftCms\Cms\Asset\Conditions\AssetCondition` should be used instead.
- Deprecated `craft\elements\conditions\assets\VolumeConditionRule`. `CraftCms\Cms\Asset\Conditions\VolumeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\FilenameConditionRule`. `CraftCms\Cms\Asset\Conditions\FilenameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\FileTypeConditionRule`. `CraftCms\Cms\Asset\Conditions\FileTypeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\FileSizeConditionRule`. `CraftCms\Cms\Asset\Conditions\FileSizeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\HeightConditionRule`. `CraftCms\Cms\Asset\Conditions\HeightConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\WidthConditionRule`. `CraftCms\Cms\Asset\Conditions\WidthConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\DateModifiedConditionRule`. `CraftCms\Cms\Asset\Conditions\DateModifiedConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\HasAltConditionRule`. `CraftCms\Cms\Asset\Conditions\HasAltConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\UploaderConditionRule`. `CraftCms\Cms\Asset\Conditions\UploaderConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\SavableConditionRule`. `CraftCms\Cms\Asset\Conditions\SavableConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\assets\ViewableConditionRule`. `CraftCms\Cms\Asset\Conditions\ViewableConditionRule` should be used instead.

#### Addresses

- Deprecated `craft\elements\conditions\addresses\AddressCondition`. `CraftCms\Cms\Address\Conditions\AddressCondition` should be used instead.
- Deprecated `craft\elements\conditions\addresses\FullNameConditionRule`. `CraftCms\Cms\Address\Conditions\FullNameConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\OrganizationConditionRule`. `CraftCms\Cms\Address\Conditions\OrganizationConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\OrganizationTaxIdConditionRule`. `CraftCms\Cms\Address\Conditions\OrganizationTaxIdConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\LocalityConditionRule`. `CraftCms\Cms\Address\Conditions\LocalityConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\DependentLocalityConditionRule`. `CraftCms\Cms\Address\Conditions\DependentLocalityConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\PostalCodeConditionRule`. `CraftCms\Cms\Address\Conditions\PostalCodeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\SortingCodeConditionRule`. `CraftCms\Cms\Address\Conditions\SortingCodeConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\CountryConditionRule`. `CraftCms\Cms\Address\Conditions\CountryConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AdministrativeAreaConditionRule`. `CraftCms\Cms\Address\Conditions\AdministrativeAreaConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AddressLine1ConditionRule`. `CraftCms\Cms\Address\Conditions\AddressLine1ConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AddressLine2ConditionRule`. `CraftCms\Cms\Address\Conditions\AddressLine2ConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\AddressLine3ConditionRule`. `CraftCms\Cms\Address\Conditions\AddressLine3ConditionRule` should be used instead.
- Deprecated `craft\elements\conditions\addresses\FieldConditionRule`. `CraftCms\Cms\Address\Conditions\FieldConditionRule` should be used instead.

#### Fields

- Deprecated `craft\fields\conditions\FieldConditionRuleInterface`. `CraftCms\Cms\Field\Conditions\Contracts\FieldConditionRuleInterface` should be used instead.
- Deprecated `craft\fields\conditions\FieldConditionRuleTrait`. `CraftCms\Cms\Field\Conditions\FieldConditionRuleTrait` should be used instead.
- Deprecated `craft\fields\conditions\GeneratedFieldConditionRule`. `CraftCms\Cms\Field\Conditions\GeneratedFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\TextFieldConditionRule`. `CraftCms\Cms\Field\Conditions\TextFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\NumberFieldConditionRule`. `CraftCms\Cms\Field\Conditions\NumberFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\MoneyFieldConditionRule`. `CraftCms\Cms\Field\Conditions\MoneyFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\OptionsFieldConditionRule`. `CraftCms\Cms\Field\Conditions\OptionsFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\RelationalFieldConditionRule`. `CraftCms\Cms\Field\Conditions\RelationalFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\LightswitchFieldConditionRule`. `CraftCms\Cms\Field\Conditions\LightswitchFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\EmptyFieldConditionRule`. `CraftCms\Cms\Field\Conditions\EmptyFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\DateFieldConditionRule`. `CraftCms\Cms\Field\Conditions\DateFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\CountryFieldConditionRule`. `CraftCms\Cms\Field\Conditions\CountryFieldConditionRule` should be used instead.
- Deprecated `craft\fields\conditions\LinkFieldConditionRule`. `CraftCms\Cms\Field\Conditions\LinkFieldConditionRule` should be used instead.

#### Events

- Deprecated `craft\events\RegisterConditionRulesEvent`. `CraftCms\Cms\Condition\Events\RegisterConditionRules` should be used instead.

## Drafts

- Deprecated `craft\services\Drafts`. `CraftCms\Cms\Element\Drafts` should be used instead.
- Deprecated `craft\events\DraftEvent`. One of the events extending `CraftCms\Cms\Element\Events\DraftEvent` should be used instead.
- Deprecated `craft\behaviors\DraftBehavior`. `CraftCms\Cms\Element\Concerns\Draftable` should be used instead.

## Elements

- Deprecated `craft\errors\InvalidTypeException`. `CraftCms\Cms\Element\Exceptions\InvalidTypeException` should be used instead.
- Deprecated `craft\errors\UnsupportedSiteException`. `CraftCms\Cms\Element\Exceptions\UnsupportedSiteException` should be used instead.

### Validation

Craft 6 introduces a new validation system that uses Laravel's Validator instead of Yii2's model validation.

#### Added

- Added `CraftCms\Cms\Validation\Contracts\Validatable` interface for classes that support Laravel-style validation.
- Added `CraftCms\Cms\Validation\Contracts\ValidatableWithRuleset` interface for classes that use a `Ruleset` class to define validation rules.
- Added `CraftCms\Cms\Validation\Ruleset` abstract class for defining validation rules, messages, and preparation logic.
- Added `CraftCms\Cms\Validation\Attributes\Ruleset` PHP attribute for specifying a component's ruleset class.
- Added `CraftCms\Cms\Validation\Concerns\Validates` trait for simple validation support.
- Added `CraftCms\Cms\Validation\Concerns\ValidatesWithRuleset` trait for ruleset-based validation.
- Added `CraftCms\Cms\Validation\Concerns\HasScenarios` trait for scenario-based validation filtering.
- Added `CraftCms\Cms\Validation\Concerns\InteractsWithValidator` trait providing common validator interactions.
- Added `CraftCms\Cms\Element\Validation\ElementRules` abstract class for defining element-specific validation rules.
- Added `CraftCms\Cms\Element\Validation\Events\DefineValidationRules` event for plugins to modify element validation rules.
- Added `CraftCms\Cms\Element\Validation\Rules\ElementUriRule` for validating element URIs.
- Added element-specific ruleset classes:
  - `CraftCms\Cms\Address\Validation\AddressRules`
  - `CraftCms\Cms\Asset\Validation\AssetRules`
  - `CraftCms\Cms\Entry\Validation\EntryRules`
  - `CraftCms\Cms\User\Validation\UserRules`
  - `CraftCms\Cms\Field\Elements\ContentBlockRules`
- Added `CraftCms\Cms\Asset\Validation\Rules\AssetLocationRule` for validating asset locations.
- Added `CraftCms\Cms\User\Validation\Rules\UserPasswordRule` for validating user passwords.
- Added `CraftCms\Cms\User\Validation\Rules\UsernameRule` for validating usernames.
- Added `CraftCms\Cms\Validation\Rules\UniqueCaseInsensitiveRule` for case-insensitive unique validation.
- Added `CraftCms\Cms\Validation\Rules\DisallowMb4` for disallowing 4-byte UTF-8 characters.
- Added `CraftCms\Cms\Validation\Rules\MoneyRule` for validating money values.

#### Changed

- `FieldInterface::getElementValidationRules()` has been replaced by `FieldInterface::getElementRules()` which returns rules in Laravel's validation format.
- Added `FieldInterface::prepareForElementValidation()` for preparing field values before validation.
- Validation rules are now defined as Laravel-style arrays (e.g., `['required', 'string', 'max:255']`).

#### Deprecations

- Deprecated `craft\base\Model::hasErrors()`. Use `->errors()->has($attribute)` or `->errors()->isNotEmpty()` instead.
- Deprecated `craft\base\Model::getErrors()`. Use `->errors()->get($attribute)` or `->errors()->getMessages()` instead.
- Deprecated `craft\base\Model::addErrors()`. Use `->errors()->add($attribute, $message)` instead.
- Deprecated `craft\base\Model::clearErrors()`. Use `->errors()->forget()` instead.
- Deprecated `CraftCms\Cms\Component\Concerns\ValidatableComponent`. Use `CraftCms\Cms\Validation\Concerns\Validates` instead.
- Deprecated `CraftCms\Cms\Component\Contracts\ValidatableComponentInterface`. Use `CraftCms\Cms\Validation\Contracts\Validatable` instead.
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

- Deprecated `\craft\elements\db\AddressQuery`. `\CraftCms\Cms\Element\Queries\AddressQuery` should be used instead.
- Deprecated `\craft\elements\db\AssetQuery` `\CraftCms\Cms\Element\Queries\AssetQuery` should be used instead.
- Deprecated `\craft\elements\db\ContentBlockQuery` `\CraftCms\Cms\Element\Queries\ContentBlockQuery` should be used instead.
- Deprecated `\craft\elements\db\ElementQuery` `\CraftCms\Cms\Element\Queries\ElementQuery` should be used instead.
- Deprecated `\craft\elements\db\ElementQueryInterface`
- Deprecated `\craft\elements\db\EntryQuery` `\CraftCms\Cms\Element\Queries\EntryQuery` should be used instead.
- Deprecated `\craft\elements\db\UserQuery` `\CraftCms\Cms\Element\Queries\UserQuery` should be used instead.

## Entries & Entry Types

- Deprecated `craft\services\Entries`. `CraftCms\Cms\Entry\Entries` and `CraftCms\Cms\Entry\EntryTypes` should be used instead.
- Deprecated `craft\models\EntryType`. `CraftCms\Cms\Entry\Data\EntryType` should be used instead.
- Deprecated `craft\records\EntryType`. `CraftCms\Cms\Entry\Models\EntryType` should be used instead.
- Deprecated `craft\records\Entry`. `CraftCms\Cms\Entry\Models\Entry` should be used instead.
- Deprecated `craft\errors\EntryTypeNotFoundException`. `CraftCms\Cms\Entry\Exceptions\EntryTypeNotFoundException` should be used instead.
- Deprecated `craft\events\EntryTypeEvent`. One of these should be used instead:
  - `craft\services\Entries::EVENT_BEFORE_DELETE_ENTRY_TYPE` => `CraftCms\Cms\Section\Events\DeletingEntryType`
  - `craft\services\Entries::EVENT_BEFORE_APPLY_ENTRY_TYPE_DELETE` => `CraftCms\Cms\Entry\Events\ApplyingEntryTypeDelete`
  - `craft\services\Entries::EVENT_AFTER_DELETE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\EntryTypeDeleted`
  - `craft\services\Entries::EVENT_BEFORE_SAVE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\SavingEntryType`
  - `craft\services\Entries::EVENT_AFTER_SAVE_ENTRY_TYPE` => `CraftCms\Cms\Entry\Events\EntryTypeSaved`
- Removed `craft\controllers\EntriesController`. The following controllers now implement this functionality:
    - `CraftCms\Cms\Http\Controllers\Entries\CreateEntryController`
    - `CraftCms\Cms\Http\Controllers\Entries\EntriesIndexController`
    - `CraftCms\Cms\Http\Controllers\Entries\MoveEntryToSectionController`
    - `CraftCms\Cms\Http\Controllers\Entries\StoreEntryController`
- Removed `craft\controllers\EntryTypesController` in favor of `CraftCms\Cms\Http\Controllers\EntryTypesController`
- Removed `craft\console\controllers\EntryTypesController` in favor of:
  - `CraftCms\Cms\Entry\Commands\MergeEntryTypesCommand`

## Component

- Added `CraftCms\Cms\Component\Component` base class, replacing Yii2's `BaseObject`/`Component` with config hydration, magic getters/setters, and `Arrayable` support.
- Added `CraftCms\Cms\Component\Exceptions\InvalidCallException`, replacing `yii\base\InvalidCallException`.
- Added `CraftCms\Cms\Component\Exceptions\UnknownPropertyException`, replacing `yii\base\UnknownPropertyException`.

## Field Layouts

### Added

- Added `CraftCms\Cms\FieldLayout\FieldLayoutForm`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutFormTab`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutFormElement`.
- Added `CraftCms\Cms\FieldLayout\FieldLayoutServiceProvider`.
- Added `CraftCms\Cms\FieldLayout\Concerns\HasFieldLayout` trait.

### Deprecations
- Deprecated `craft\models\FieldLayout`. `CraftCms\Cms\FieldLayout\FieldLayout` should be used instead.
- Deprecated `craft\models\FieldLayoutTab`. `CraftCms\Cms\FieldLayout\FieldLayoutTab` should be used instead.
- Deprecated `craft\base\FieldLayoutComponent`. `CraftCms\Cms\FieldLayout\FieldLayoutComponent` should be used instead.
- Deprecated `craft\base\FieldLayoutElement`. `CraftCms\Cms\FieldLayout\FieldLayoutElement` should be used instead.
- Deprecated `craft\base\FieldLayoutProviderInterface`. `CraftCms\Cms\FieldLayout\Contracts\FieldLayoutProviderInterface` should be used instead.
- Deprecated `craft\records\FieldLayout`. `CraftCms\Cms\FieldLayout\Models\FieldLayout` should be used instead.
- Deprecated `craft\fieldlayoutelements\BaseField`. `CraftCms\Cms\FieldLayout\LayoutElements\BaseField` should be used instead.
- Deprecated `craft\fieldlayoutelements\BaseNativeField`. `CraftCms\Cms\FieldLayout\LayoutElements\BaseNativeField` should be used instead.
- Deprecated `craft\fieldlayoutelements\BaseUiElement`. `CraftCms\Cms\FieldLayout\LayoutElements\BaseUiElement` should be used instead.
- Deprecated `craft\fieldlayoutelements\CustomField`. `CraftCms\Cms\FieldLayout\LayoutElements\CustomField` should be used instead.
- Deprecated `craft\fieldlayoutelements\Heading`. `CraftCms\Cms\FieldLayout\LayoutElements\Heading` should be used instead.
- Deprecated `craft\fieldlayoutelements\HorizontalRule`. `CraftCms\Cms\FieldLayout\LayoutElements\HorizontalRule` should be used instead.
- Deprecated `craft\fieldlayoutelements\Html`. `CraftCms\Cms\FieldLayout\LayoutElements\Html` should be used instead.
- Deprecated `craft\fieldlayoutelements\LineBreak`. `CraftCms\Cms\FieldLayout\LayoutElements\LineBreak` should be used instead.
- Deprecated `craft\fieldlayoutelements\Markdown`. `CraftCms\Cms\FieldLayout\LayoutElements\Markdown` should be used instead.
- Deprecated `craft\fieldlayoutelements\Template`. `CraftCms\Cms\FieldLayout\LayoutElements\Template` should be used instead.
- Deprecated `craft\fieldlayoutelements\TextField`. `CraftCms\Cms\FieldLayout\LayoutElements\TextField` should be used instead.
- Deprecated `craft\fieldlayoutelements\TextareaField`. `CraftCms\Cms\FieldLayout\LayoutElements\TextareaField` should be used instead.
- Deprecated `craft\fieldlayoutelements\Tip`. `CraftCms\Cms\FieldLayout\LayoutElements\Tip` should be used instead.
- Deprecated `craft\fieldlayoutelements\TitleField`. `CraftCms\Cms\FieldLayout\LayoutElements\TitleField` should be used instead.
- Deprecated `craft\fieldlayoutelements\FullNameField`. `CraftCms\Cms\FieldLayout\LayoutElements\FullNameField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\AddressField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\AddressField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\CountryCodeField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\CountryCodeField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\LabelField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\LabelField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\LatLongField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\LatLongField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\OrganizationField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\OrganizationField` should be used instead.
- Deprecated `craft\fieldlayoutelements\addresses\OrganizationTaxIdField`. `CraftCms\Cms\FieldLayout\LayoutElements\addresses\OrganizationTaxIdField` should be used instead.
- Deprecated `craft\fieldlayoutelements\assets\AssetTitleField`. `CraftCms\Cms\FieldLayout\LayoutElements\assets\AssetTitleField` should be used instead.
- Deprecated `craft\fieldlayoutelements\assets\AltField`. `CraftCms\Cms\FieldLayout\LayoutElements\assets\AltField` should be used instead.
- Deprecated `craft\fieldlayoutelements\entries\EntryTitleField`. `CraftCms\Cms\FieldLayout\LayoutElements\entries\EntryTitleField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\UsernameField`. `CraftCms\Cms\FieldLayout\LayoutElements\users\UsernameField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\FullNameField`. `CraftCms\Cms\FieldLayout\LayoutElements\users\FullNameField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\EmailField`. `CraftCms\Cms\FieldLayout\LayoutElements\users\EmailField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\AffiliatedSiteField`. `CraftCms\Cms\FieldLayout\LayoutElements\users\AffiliatedSiteField` should be used instead.
- Deprecated `craft\fieldlayoutelements\users\PhotoField`. `CraftCms\Cms\FieldLayout\LayoutElements\users\PhotoField` should be used instead.
- Deprecated `craft\events\CreateFieldLayoutFormEvent`. `CraftCms\Cms\FieldLayout\Events\CreateFieldLayoutForm` should be used instead.
- Deprecated `craft\events\DefineFieldLayoutCustomFieldsEvent`. `CraftCms\Cms\FieldLayout\Events\DefineCustomFields` should be used instead.
- Deprecated `craft\events\DefineFieldLayoutElementsEvent`. `CraftCms\Cms\FieldLayout\Events\DefineUIElements` should be used instead.
- Deprecated `craft\events\DefineFieldLayoutFieldsEvent`. `CraftCms\Cms\FieldLayout\Events\DefineNativeFields` should be used instead.
- Deprecated `craft\events\DefineShowFieldLayoutComponentInFormEvent`. `CraftCms\Cms\FieldLayout\Events\DefineShowInForm` should be used instead.
- Deprecated `craft\events\DefineFieldActionsEvent`. `CraftCms\Cms\FieldLayout\Events\DefineActionMenuItems` should be used instead.

## Fields

- Removed `craft\controllers\FieldsController` in favor of `CraftCms\Cms\Http\Controllers\FieldsController`.
- Deprecated `craft\errors\InvalidFieldException`. `CraftCms\Cms\Field\Exceptions\InvalidFieldException` should be used instead.
- Deprecated `craft\fields\data\ColorData`. `CraftCms\Cms\Field\Data\ColorData` should be used instead.
- Deprecated `craft\fields\data\IconData`. `CraftCms\Cms\Field\Data\IconData` should be used instead.
- Deprecated `craft\fields\data\JsonData`. `CraftCms\Cms\Field\Data\JsonData` should be used instead.
- Deprecated `craft\fields\data\LinkData`. `CraftCms\Cms\Field\Data\LinkData` should be used instead.
- Deprecated `craft\fields\data\MultiOptionsFieldData`. `CraftCms\Cms\Field\Data\MultiOptionsFieldData` should be used instead.
- Deprecated `craft\fields\data\OptionData`. `CraftCms\Cms\Field\Data\OptionData` should be used instead.
- Deprecated `craft\fields\data\SingleOptionFieldData`. `CraftCms\Cms\Field\Data\SingleOptionFieldData` should be used instead.
- Deprecated `craft\fields\linktypes\Asset`. `CraftCms\Cms\Field\LinkTypes\Asset` should be used instead.
- Deprecated `craft\fields\linktypes\BaseElementLinkType`. `CraftCms\Cms\Field\LinkTypes\BaseElementLinkType` should be used instead.
- Deprecated `craft\fields\linktypes\BaseLinkType`. `CraftCms\Cms\Field\LinkTypes\BaseLinkType` should be used instead.
- Deprecated `craft\fields\linktypes\BaseTextLinkType`. `CraftCms\Cms\Field\LinkTypes\BaseTextLinkType` should be used instead.
- Deprecated `craft\fields\linktypes\Category`. `CraftCms\Cms\Field\LinkTypes\Category` should be used instead.
- Deprecated `craft\fields\linktypes\Email`. `CraftCms\Cms\Field\LinkTypes\Email` should be used instead.
- Deprecated `craft\fields\linktypes\Entry`. `CraftCms\Cms\Field\LinkTypes\Entry` should be used instead.
- Deprecated `craft\fields\linktypes\Phone`. `CraftCms\Cms\Field\LinkTypes\Phone` should be used instead.
- Deprecated `craft\fields\linktypes\Sms`. `CraftCms\Cms\Field\LinkTypes\Sms` should be used instead.
- Deprecated `craft\fields\linktypes\Url`. `CraftCms\Cms\Field\LinkTypes\Url` should be used instead.
- Deprecated `craft\fields\Addresses`. `CraftCms\Cms\Field\Addresses` should be used instead.
- Deprecated `craft\fields\Assets`. `CraftCms\Cms\Field\Assets` should be used instead.
- Deprecated `craft\fields\BaseOptionsField`. `CraftCms\Cms\Field\BaseOptionsField` should be used instead.
- Deprecated `craft\fields\BaseRelationField`. `CraftCms\Cms\Field\BaseRelationField` should be used instead.
- Deprecated `craft\fields\ButtonGroup`. `CraftCms\Cms\Field\ButtonGroup` should be used instead.
- Deprecated `craft\fields\Categories`. `CraftCms\Cms\Field\Categories` should be used instead.
- Deprecated `craft\fields\Checkboxes`. `CraftCms\Cms\Field\Checkboxes` should be used instead.
- Deprecated `craft\fields\Color`. `CraftCms\Cms\Field\Color` should be used instead.
- Deprecated `craft\fields\ContentBlock`. `CraftCms\Cms\Field\ContentBlock` should be used instead.
- Deprecated `craft\fields\Country`. `CraftCms\Cms\Field\Country` should be used instead.
- Deprecated `craft\fields\Date`. `CraftCms\Cms\Field\Date` should be used instead.
- Deprecated `craft\fields\Dropdown`. `CraftCms\Cms\Field\Dropdown` should be used instead.
- Deprecated `craft\fields\Email`. `CraftCms\Cms\Field\Email` should be used instead.
- Deprecated `craft\fields\Entries`. `CraftCms\Cms\Field\Entries` should be used instead.
- Deprecated `craft\fields\Icon`. `CraftCms\Cms\Field\Icon` should be used instead.
- Deprecated `craft\fields\Json`. `CraftCms\Cms\Field\Json` should be used instead.
- Deprecated `craft\fields\Lightswitch`. `CraftCms\Cms\Field\Lightswitch` should be used instead.
- Deprecated `craft\fields\Link`. `CraftCms\Cms\Field\Link` should be used instead.
- Deprecated `craft\fields\Matrix`. `CraftCms\Cms\Field\Matrix` should be used instead.
- Deprecated `craft\fields\MissingField`. `CraftCms\Cms\Field\MissingField` should be used instead.
- Deprecated `craft\fields\Money`. `CraftCms\Cms\Field\Money` should be used instead.
- Deprecated `craft\fields\MultiSelect`. `CraftCms\Cms\Field\MultiSelect` should be used instead.
- Deprecated `craft\fields\Number`. `CraftCms\Cms\Field\Number` should be used instead.
- Deprecated `craft\fields\PlainText`. `CraftCms\Cms\Field\PlainText` should be used instead.
- Deprecated `craft\fields\RadioButtons`. `CraftCms\Cms\Field\RadioButtons` should be used instead.
- Deprecated `craft\fields\Range`. `CraftCms\Cms\Field\Range` should be used instead.
- Deprecated `craft\fields\Table`. `CraftCms\Cms\Field\Table` should be used instead.
- Deprecated `craft\fields\Tags`. `CraftCms\Cms\Field\Tags` should be used instead.
- Deprecated `craft\fields\Time`. `CraftCms\Cms\Field\Time` should be used instead.
- Deprecated `craft\fields\Url`. `CraftCms\Cms\Field\Url` should be used instead.
- Deprecated `craft\fields\Users`. `CraftCms\Cms\Field\Users` should be used instead.
- Deprecated `craft\services\Fields`. `CraftCms\Cms\Field\Fields` should be used instead.

## Filesystems

- Deprecated `craft\errors\InvalidSubpathException`. `CraftCms\Cms\Filesystem\Exceptions\InvalidSubpathException` should be used instead.

## GQL

- Deprecated `\craft\records\GqlSchema`. `\CraftCms\Cms\Gql\Models\GqlSchema` should be used instead.
- Deprecated `\craft\records\GqlToken`. `\CraftCms\Cms\Gql\Models\GqlToken` should be used instead.

## HTTP

- Deprecated `craft\filters\BasicHttpAuthLogin`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\BasicHttpAuthStatic`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\BasicHttpAuthTrait`. Use the `auth.basic` middleware instead. (see https://laravel.com/docs/12.x/authentication#http-basic-authentication)
- Deprecated `craft\filters\Cors`. Use Laravel's CORS settings instead. (see https://laravel.com/docs/12.x/routing#cors)
- Deprecated `craft\filters\Headers`. Use Laravel middleware instead. (see https://laravel.com/docs/middleware)
- Deprecated `craft\filters\ConditionalFilterTrait`.
- Deprecated `craft\filters\SiteFilterTrait`.
- Deprecated `craft\filters\UtilityAccess`.
- Deprecated `craft\controllers\AppController::actionLicensingIssues()`. `CraftCms\Cms\Http\Middleware\EnforceLicenses` should be used instead.
- Removed `craft\controllers\AppController::actionIconPickerOptions()`. Use `CraftCms\Cms\Http\Controllers\IconController::pickerOptions()` instead.
- Removed the header-setting logic in `yii2-adapter\legacy\web\Application`. The new `\CraftCms\Cms\Http\Middleware\SetHeaders` middleware handles this functionality.
- Removed the licensing issues screen logic in `yii2-adapter\legacy\web\Application`. The new `\CraftCms\Cms\Http\Middleware\EnforceLicenses` middleware handles this functionality.
- Removed `craft\controllers\AppController::actionTryEdition()` and `actionSwitchToLicensedEdition()` in favor of `CraftCms\Cms\Http\Controllers\EditionController`.

## Mail

- Added `CraftCms\Cms\Email\Commands\SendTestMailCommand`.
- Added `CraftCms\Cms\Email\Mailables\CraftMailable`, a base mailable class that automatically applies project config email settings (from, replyTo, mailer) with site-specific overrides.
- Added `CraftCms\Cms\SystemMessage\Mailables\SystemMessageMailable`.
- Deprecated `Craft::$app->getMailer()`. Laravel mailers/drivers and `CraftCms\Cms\SystemMessage\SystemMessages::mailable()` should be used instead.
- Deprecated `craft\mail\Mailer`. Laravel mailers/drivers and `CraftCms\Cms\SystemMessage\SystemMessages::mailable()` should be used instead.
- Deprecated `craft\helpers\MailerHelper`. Laravel mail configuration and drivers should be used instead.
- Deprecated `craft\config\GeneralConfig::$testToEmailAddress` and `craft\config\GeneralConfig::testToEmailAddress()`. `Illuminate\Support\Facades\Mail::alwaysTo()` should be used instead.
- Deprecated `craft\mail\Mailer::$template`, `craft\mail\Mailer::$siteOverrides`, `craft\models\MailSettings::$template`, and `craft\models\MailSettings::$siteOverrides`. Laravel mailable views and environment-specific Laravel mailers should be used instead.
- Removed legacy `projectConfig.email` mail settings and mail transport adapter configuration in favor of Laravel's `mail` config and drivers.

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
- Deprecated `craft\errors\InvalidLicenseKeyException`. `CraftCms\Cms\Plugin\Exceptions\InvalidLicenseKeyException` should be used instead.

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
- Added `Request::isCpRequest()`, `Request::isSiteRequest()`, `Request::isActionRequest()`, `Request::actionSegments()`, `Request::actionSegmentsToRoute()`, `Request::pageNumber()`, `Request::duplicateWithUri()`, `Request::getToken()`, and `Request::getSigned()` macros.

## Security

- Added `CraftCms\Cms\Support\Security`.
- Added `CraftCms\Cms\Support\Facades\Security`.
- Added `CraftCms\Cms\Http\Middleware\AddLogContext`.
- Deprecated `Craft::$app->getSecurity()` in favor of Laravel's Hash and Crypt facades, or `CraftCms\Cms\Support\Facades\Security`.
- Deprecated `GeneralConfig::$blowfishHashCost` in favor of Laravel's hashing.bcrypt.rounds config or the BCRYPT_ROUNDS environment variable.

## Updates

The `craft\services\Updates` internal service has been removed. `CraftCms\Cms\Update\Updates` should be used instead.

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
  - `CraftCms\Cms\Update\Commands\UpdateCommand`
  - `CraftCms\Cms\Update\Commands\ComposerInstallCommand`
  - `CraftCms\Cms\Update\Commands\InfoCommand`

#### Deprecations & removals
- Deprecated `craft\helpers\Install`. `CraftCms\Cms\Site\Concerns\SiteDefaults` should be used instead.
- Deprecated `craft\helpers\Update`. The only method was `checkPhpConstraint` which is now available on `CraftCms\Cms\Support\PHP::checkConstraint()`
- Removed `craft\events\UpdateReleaseEvent` in favor of `CraftCms\Cms\Update\Events\CriticalUpdateReleasedEvent`
- Removed `craft\models\Update`. `CraftCms\Cms\Update\Data\Update` should be used instead.
- Removed `craft\models\UpdateRelease`. `CraftCms\Cms\Update\Data\UpdateRelease` should be used instead.
- Removed `craft\models\Updates`. `CraftCms\Cms\Update\Data\Updates` should be used instead.

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

## Search

- Added `CraftCms\Cms\Support\Facades\Search`.
- Deprecated `craft\services\Search`. `CraftCms\Cms\Search\Search` should be used instead.
- Deprecated `Craft::$app->getSearch()`. `CraftCms\Cms\Support\Facades\Search` or `app(CraftCms\Cms\Search\Search::class)` should be used instead.
- Deprecated `craft\search\SearchQuery`. `CraftCms\Cms\Search\SearchQuery` should be used instead.
- Deprecated `craft\search\SearchQueryTerm`. `CraftCms\Cms\Search\SearchQueryTerm` should be used instead.
- Deprecated `craft\search\SearchQueryTermGroup`. `CraftCms\Cms\Search\SearchQueryTermGroup` should be used instead.
- Deprecated `craft\events\SearchEvent` in favor of the following new events:
  - `craft\services\Search::EVENT_BEFORE_SEARCH` => `CraftCms\Cms\Search\Events\BeforeSearch`
  - `craft\services\Search::EVENT_AFTER_SEARCH` => `CraftCms\Cms\Search\Events\AfterSearch`
  - `craft\services\Search::EVENT_BEFORE_SCORE_RESULTS` => `CraftCms\Cms\Search\Events\BeforeScoreResults`
- Deprecated `craft\events\IndexKeywordsEvent`. `CraftCms\Cms\Search\Events\BeforeIndexKeywords` should be used instead.

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
- Deprecated `craft\errors\SectionNotFoundException`. `CraftCms\Cms\Section\Exceptions\SectionNotFoundException` should be used instead.

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

## Twig

- Added `CraftCms\Cms\Twig\Twig` service for managing Twig environments, replacing the Twig management logic previously in `craft\web\View`.
- Added `CraftCms\Cms\Twig\TemplateRenderer` for rendering templates, replacing the rendering logic previously in `craft\web\View`.
- Added `CraftCms\Cms\Twig\PageLifecycle` for managing the page rendering lifecycle (head/body placeholder replacement), replacing the page lifecycle logic previously in `craft\web\View`.
- Added `CraftCms\Cms\Support\Facades\Twig` facade, resolving to `CraftCms\Cms\Twig\TemplateRenderer`.
- Added `CraftCms\Cms\Twig\Environment`, moved from `craft\web\twig\Environment`.
- Added `CraftCms\Cms\Twig\TemplateResolver`.
- Added `CraftCms\Cms\Twig\TemplateLoader`.
- Added `CraftCms\Cms\Twig\Exceptions\TemplateLoaderException`.
- Added helper functions in the `CraftCms\Cms` namespace: `template()`, `sandboxedTemplate()`, `pageTemplate()`, `renderString()`, `renderSandboxedString()`, `renderObjectTemplate()`, `renderSandboxedObjectTemplate()`.
- Added `sanitize` Twig filter for sanitizing HTML with `CraftCms\Cms\Support\HtmlSanitizer\HtmlSanitizers`.
- Deprecated `craft\web\View::getTwig()`. `CraftCms\Cms\Twig\Twig::get()` should be used instead.
- Deprecated `craft\web\View::setTwig()`. `CraftCms\Cms\Twig\Twig::set()` should be used instead.
- Deprecated `craft\web\View::createTwig()`. `CraftCms\Cms\Twig\Twig::create()` should be used instead.
- Deprecated `craft\web\View::registerCpTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::registerSiteTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::registerTwigExtension()`. `CraftCms\Cms\Twig\Twig::registerExtension()` should be used instead.
- Deprecated `craft\web\View::renderTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::renderTemplate()` or the `template()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::renderSandboxedTemplate()` or the `sandboxedTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderPageTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::renderPageTemplate()` or the `pageTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderString()`. `CraftCms\Cms\Twig\TemplateRenderer::renderString()` or the `renderString()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedString()`. `CraftCms\Cms\Twig\TemplateRenderer::renderSandboxedString()` or the `renderSandboxedString()` helper should be used instead.
- Deprecated `craft\web\View::renderObjectTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::renderObjectTemplate()` or the `renderObjectTemplate()` helper should be used instead.
- Deprecated `craft\web\View::renderSandboxedObjectTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::renderSandboxedObjectTemplate()` or the `renderSandboxedObjectTemplate()` helper should be used instead.
- Deprecated `craft\web\View::normalizeObjectTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::normalizeObjectTemplate()` should be used instead.
- Deprecated `craft\web\View::getIsRenderingTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::isRenderingTemplate` should be used instead.
- Deprecated `craft\web\View::getIsRenderingPageTemplate()`. `CraftCms\Cms\Twig\TemplateRenderer::isRenderingPageTemplate` should be used instead.
- Deprecated `craft\web\twig\Environment`. `CraftCms\Cms\Twig\Environment` should be used instead.
- Deprecated `craft\web\View::EVENT_AFTER_CREATE_TWIG`. `CraftCms\Cms\Twig\Events\TwigCreated` should be used instead.
- Deprecated `craft\web\View::doesTemplateExist()`. `CraftCms\Cms\Twig\TemplateResolver::doesTemplateExist()` should be used instead.
- Deprecated `craft\web\View::resolveTemplate()`. `CraftCms\Cms\Twig\TemplateResolver::resolveTemplate()` should be used instead.
- Deprecated `craft\web\twig\TemplateLoader`. `CraftCms\Cms\Twig\TemplateLoader` should be used instead.
- Deprecated `craft\web\twig\TemplateLoaderException`. `CraftCms\Cms\Twig\Exceptions\TemplateLoaderException` should be used instead.

### Events

- Added `CraftCms\Cms\Twig\Events\TwigCreated`, dispatched when a Twig environment is created.
- Added `CraftCms\Cms\Twig\Events\RenderingTemplate`, dispatched before a template is rendered. Supports cancellation via `ValidatableEvent`.
- Added `CraftCms\Cms\Twig\Events\TemplateRendered`, dispatched after a template is rendered. Has a mutable `output` property.
- Added `CraftCms\Cms\Twig\Events\RenderingPageTemplate`, dispatched before a page template is rendered. Supports cancellation via `ValidatableEvent`.
- Added `CraftCms\Cms\Twig\Events\PageTemplateRendered`, dispatched after a page template is rendered. Has a mutable `output` property.
- Added `CraftCms\Cms\Twig\Events\BeginPage`, dispatched when page rendering begins.
- Added `CraftCms\Cms\Twig\Events\EndPage`, dispatched when page rendering ends. Has nullable `headHtml`, `bodyBeginHtml`, and `bodyEndHtml` properties for overriding `HtmlStack` output.
- Deprecated `craft\web\View::EVENT_BEFORE_RENDER_TEMPLATE`. `CraftCms\Cms\Twig\Events\RenderingTemplate` should be used instead.
- Deprecated `craft\web\View::EVENT_AFTER_RENDER_TEMPLATE`. `CraftCms\Cms\Twig\Events\TemplateRendered` should be used instead.
- Deprecated `craft\web\View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE`. `CraftCms\Cms\Twig\Events\RenderingPageTemplate` should be used instead.
- Deprecated `craft\web\View::EVENT_AFTER_RENDER_PAGE_TEMPLATE`. `CraftCms\Cms\Twig\Events\PageTemplateRendered` should be used instead.

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

- `CraftCms\Cms\User\Elements\User` now implements `Illuminate\Contracts\Auth\Authenticatable`, `Illuminate\Contracts\Auth\Access\Authorizable`, `Illuminate\Contracts\Auth\CanResetPassword`, and `Illuminate\Contracts\Auth\MustVerifyEmail`.
- Added `CraftCms\Cms\User\Notifications\VerifyEmailNotification`.
- `Users::purgeExpiredPendingUsers()` now joins the `password_reset_tokens` table to find expired pending users.
- Removed `verificationCode` and `verificationCodeIssuedDate` columns on the `users` table in favor of the `password_reset_tokens` table.
- Deprecated `craft\services\Users::isVerificationCodeValidForUser()`. `Password::broker('craft')->tokenExists($user, $code)` should be used instead.
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

## View

- Added `CraftCms\Cms\View\TwigEngine`.
- Added `CraftCms\Cms\View\HtmlStack`.
- Added `CraftCms\Cms\Support\Facades\HtmlStack`.
- Added `CraftCms\Cms\View\Enums\Position` enum.
- Added `CraftCms\Cms\View\InputNamespace`.
- Added `CraftCms\Cms\Support\Facades\InputNamespace`.
- Added `CraftCms\Cms\View\TemplateHooks`.
- Added `CraftCms\Cms\Support\Facades\TemplateHooks`.
- Added `CraftCms\Cms\View\DeltaRegistry`.
- Added `CraftCms\Cms\Support\Facades\DeltaRegistry`.
- Added `CraftCms\Cms\View\TemplateMode` enum.
- Added `CraftCms\Cms\View\Events\RegisterCpTemplateRoots`.
- Added `CraftCms\Cms\View\Events\RegisterSiteTemplateRoots`.
- Added `CraftCms\Cms\View\TemplateCaches`.
- Added `CraftCms\Cms\View\CacheCollectors\DependencyCollector`.
- Added `CraftCms\Cms\View\CacheCollectors\ResourceCollector`.
- Added `CraftCms\Cms\View\Contracts\CacheCollectorInterface`.
- Added `CraftCms\Cms\View\Data\TemplateCacheContext`.
- Added `CraftCms\Cms\View\Events\RegisterTemplateCacheCollectors`.
- Deprecated `craft\services\TemplateCaches`. `CraftCms\Cms\View\TemplateCaches` should be used instead.
- Deprecated `craft\web\View::registerJs()`. `CraftCms\Cms\View\HtmlStack::js()` should be used instead.
- Deprecated `craft\web\View::registerJsWithVars()`. `CraftCms\Cms\View\HtmlStack::jsWithVars()` should be used instead.
- Deprecated `craft\web\View::registerJsFile()`. `CraftCms\Cms\View\HtmlStack::jsFile()` should be used instead.
- Deprecated `craft\web\View::registerCss()`. `CraftCms\Cms\View\HtmlStack::css()` should be used instead.
- Deprecated `craft\web\View::registerCssFile()`. `CraftCms\Cms\View\HtmlStack::cssFile()` should be used instead.
- Deprecated `craft\web\View::registerScript()`. `CraftCms\Cms\View\HtmlStack::script()` should be used instead.
- Deprecated `craft\web\View::registerScriptWithVars()`. `CraftCms\Cms\View\HtmlStack::scriptWithVars()` should be used instead.
- Deprecated `craft\web\View::registerHtml()`. `CraftCms\Cms\View\HtmlStack::html()` should be used instead.
- Deprecated `craft\web\View::registerMetaTag()`. `CraftCms\Cms\View\HtmlStack::metaTag()` should be used instead.
- Deprecated `craft\web\View::registerLinkTag()`. `CraftCms\Cms\View\HtmlStack::linkTag()` should be used instead.
- Deprecated `craft\web\View::registerTranslations()`. `CraftCms\Cms\View\HtmlStack::translations()` should be used instead.
- Deprecated `craft\web\View::registerJsImport()`. `CraftCms\Cms\View\HtmlStack::jsImport()` should be used instead.
- Deprecated `craft\web\View::registerIcons()`. `CraftCms\Cms\View\HtmlStack::icons()` should be used instead.
- Deprecated `craft\web\View::startJsBuffer()`. `CraftCms\Cms\View\HtmlStack::startJsBuffer()` should be used instead.
- Deprecated `craft\web\View::clearJsBuffer()`. `CraftCms\Cms\View\HtmlStack::clearJsBuffer()` should be used instead.
- Deprecated `craft\web\View::startScriptBuffer()`. `CraftCms\Cms\View\HtmlStack::startScriptBuffer()` should be used instead.
- Deprecated `craft\web\View::clearScriptBuffer()`. `CraftCms\Cms\View\HtmlStack::clearScriptBuffer()` should be used instead.
- Deprecated `craft\web\View::startCssBuffer()`. `CraftCms\Cms\View\HtmlStack::startCssBuffer()` should be used instead.
- Deprecated `craft\web\View::clearCssBuffer()`. `CraftCms\Cms\View\HtmlStack::clearCssBuffer()` should be used instead.
- Deprecated `craft\web\View::startCssFileBuffer()`. `CraftCms\Cms\View\HtmlStack::startCssFileBuffer()` should be used instead.
- Deprecated `craft\web\View::clearCssFileBuffer()`. `CraftCms\Cms\View\HtmlStack::clearCssFileBuffer()` should be used instead.
- Deprecated `craft\web\View::startJsFileBuffer()`. `CraftCms\Cms\View\HtmlStack::startJsFileBuffer()` should be used instead.
- Deprecated `craft\web\View::clearJsFileBuffer()`. `CraftCms\Cms\View\HtmlStack::clearJsFileBuffer()` should be used instead.
- Deprecated `craft\web\View::startHtmlBuffer()`. `CraftCms\Cms\View\HtmlStack::startHtmlBuffer()` should be used instead.
- Deprecated `craft\web\View::clearHtmlBuffer()`. `CraftCms\Cms\View\HtmlStack::clearHtmlBuffer()` should be used instead.
- Deprecated `craft\web\View::startMetaTagBuffer()`. `CraftCms\Cms\View\HtmlStack::startMetaTagBuffer()` should be used instead.
- Deprecated `craft\web\View::clearMetaTagBuffer()`. `CraftCms\Cms\View\HtmlStack::clearMetaTagBuffer()` should be used instead.
- Deprecated `craft\web\View::startJsImportBuffer()`. `CraftCms\Cms\View\HtmlStack::startJsImportBuffer()` should be used instead.
- Deprecated `craft\web\View::clearJsImportBuffer()`. `CraftCms\Cms\View\HtmlStack::clearJsImportBuffer()` should be used instead.
- Deprecated `craft\web\View::getNamespace()`. `CraftCms\Cms\View\InputNamespace::get()` should be used instead.
- Deprecated `craft\web\View::setNamespace()`. `CraftCms\Cms\View\InputNamespace::set()` should be used instead.
- Deprecated `craft\web\View::namespaceInputs()`. `CraftCms\Cms\View\InputNamespace::namespaceInputs()` should be used instead.
- Deprecated `craft\web\View::namespaceInputName()`. `CraftCms\Cms\View\InputNamespace::namespaceInputName()` should be used instead.
- Deprecated `craft\web\View::namespaceInputId()`. `CraftCms\Cms\View\InputNamespace::namespaceInputId()` should be used instead.
- Deprecated `craft\web\View::TEMPLATE_MODE_CP`. `CraftCms\Cms\View\TemplateMode::Cp` should be used instead.
- Deprecated `craft\web\View::TEMPLATE_MODE_SITE`. `CraftCms\Cms\View\TemplateMode::Site` should be used instead.
- Deprecated `craft\web\View::getTemplateMode()`. `CraftCms\Cms\View\TemplateMode::get()` should be used instead.
- Deprecated `craft\web\View::setTemplateMode()`. `CraftCms\Cms\View\TemplateMode::set()` should be used instead.
- Deprecated `craft\web\View::getTemplatesPath()`. `CraftCms\Cms\View\TemplateMode::templatesPath()` should be used instead.
- Deprecated `craft\web\View::getCpTemplateRoots()`. `CraftCms\Cms\View\TemplateMode::templateRoots()` should be used instead.
- Deprecated `craft\web\View::getSiteTemplateRoots()`. `CraftCms\Cms\View\TemplateMode::templateRoots()` should be used instead.
- Deprecated `craft\web\View::EVENT_REGISTER_CP_TEMPLATE_ROOTS`. `CraftCms\Cms\View\Events\RegisterCpTemplateRoots` should be used instead.
- Deprecated `craft\web\View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS`. `CraftCms\Cms\View\Events\RegisterSiteTemplateRoots` should be used instead.
- Deprecated `craft\web\View::registerDeltaName()`. `CraftCms\Cms\View\DeltaRegistry::registerName()` should be used instead.
- Deprecated `craft\web\View::getDeltaNames()`. `CraftCms\Cms\View\DeltaRegistry::getNames()` should be used instead.
- Deprecated `craft\web\View::getModifiedDeltaNames()`. `CraftCms\Cms\View\DeltaRegistry::getModifiedNames()` should be used instead.
- Deprecated `craft\web\View::setInitialDeltaValue()`. `CraftCms\Cms\View\DeltaRegistry::setInitialValue()` should be used instead.
- Deprecated `craft\web\View::getInitialDeltaValues()`. `CraftCms\Cms\View\DeltaRegistry::getInitialValues()` should be used instead.
- Deprecated `craft\web\View::getIsDeltaRegistrationActive()`. `CraftCms\Cms\View\DeltaRegistry::isActive()` should be used instead.
- Deprecated `craft\web\View::setIsDeltaRegistrationActive()`. `CraftCms\Cms\View\DeltaRegistry::setActive()` should be used instead.
- Deprecated `craft\web\View::hook()`. `CraftCms\Cms\View\TemplateHooks::register()` should be used instead.
- Deprecated `craft\web\View::invokeHook()`. `CraftCms\Cms\View\TemplateHooks::invoke()` should be used instead.
