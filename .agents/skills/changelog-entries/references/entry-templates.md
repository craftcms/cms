# Entry Templates

## Added

**New class or service:**
```
- Added `CraftCms\Cms\Namespace\ClassName`.
```

**New facade:**
```
- Added `CraftCms\Cms\Support\Facades\FacadeName`.
```

**New enum:**
```
- Added `CraftCms\Cms\Namespace\EnumName` enum.
```

**New event:**
```
- Added `CraftCms\Cms\Namespace\Events\EventName`.
```

**New event with description:**
```
- Added `CraftCms\Cms\Namespace\Events\EventName` event for customizing X behavior.
```

**New method or property:**
```
- Added `craft\helpers\ElementHelper::cleanseQueryCriteria()`.
- Added `craft\base\ElementTrait::$applyingDraft`. ([#18057](https://github.com/craftcms/cms/pull/18057))
```

**New method macro:**
```
- Added `Request::isPreview()` macro for detecting preview requests via `x-craft-preview` or `x-craft-live-preview` parameters.
```

**New console command:**
```
- Added `php craft twig:cache` - Precompile Twig views.
```

**New config setting:**
```
- Added the `enableTwigSandbox` config setting. ([#18208](https://github.com/craftcms/cms/pull/18208))
```

**New library:**
```
- Added the Illuminate Support library.
```

**Multiple related additions (with sub-list):**
```
- Added element-specific authorization policies:
  - `CraftCms\Cms\Entry\Policies\EntryPolicy`
  - `CraftCms\Cms\Asset\Policies\AssetPolicy`
```

## Fixed

**Bug fix:**
```
- Fixed a bug where something wasn't working properly. ([#12345](https://github.com/craftcms/cms/issues/12345))
- Fixed a bug where Matrix fields in Blocks view could lose their existing values when they became editable.
```

**Error fix:**
```
- Fixed an error that could occur when editing an element with a Table field. ([#18408](https://github.com/craftcms/cms/pull/18408))
- Fixed an error that occurred when creating a new element on multi-site installs. ([#18393](https://github.com/craftcms/cms/pull/18393))
```

**JavaScript fix:**
```
- Fixed a JavaScript error that occurred if a Matrix field's label was hidden. ([#18366](https://github.com/craftcms/cms/issues/18366))
- Fixed potential JavaScript errors that could occur if a disclosure menu's trigger was missing. ([#18358](https://github.com/craftcms/cms/issues/18358))
```

**Security fix:**
```
- Fixed a [high-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) RCE vulnerability. (GHSA-fp5j-j7j4-mcxc)
- Fixed a [moderate-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) SSTI vulnerability. (GHSA-qc86-q28f-ggww)
- Fixed [low-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) XSS vulnerabilities. (GHSA-4mgv-366x-qxvx)
```

**Styling fix:**
```
- Fixed a styling issue with slideouts within Live Preview. ([#18383](https://github.com/craftcms/cms/issues/18383))
```

## Deprecated

**Class replaced by new class:**
```
- Deprecated `craft\old\ClassName`. `CraftCms\Cms\New\ClassName` should be used instead.
```

**Method replaced by new method:**
```
- Deprecated `craft\old\Class::oldMethod()`. `CraftCms\Cms\New\Class::newMethod()` should be used instead.
```

**Property replaced:**
```
- Deprecated `GeneralConfig::$oldProp` in favor of Laravel's config.key config value.
```

**Constant replaced by enum case:**
```
- Deprecated `craft\web\View::TEMPLATE_MODE_CP`. `CraftCms\Cms\View\TemplateMode::Cp` should be used instead.
```

**Event constant replaced by event class:**
```
- Deprecated `craft\web\View::EVENT_REGISTER_CP_TEMPLATE_ROOTS`. `CraftCms\Cms\View\Events\RegisterCpTemplateRoots` should be used instead.
```

**Twig variable replaced:**
```
- Deprecated `craft.app.config.general` in Twig. `app.config.craft.general` should be used instead.
```

**Class replaced with extra context:**
```
- Deprecated `Craft::$app->getConfig()->getGeneral()`. `CraftCms\Cms\Config\GeneralConfig` should be used instead. This can be used through dependency injection or through `app(CraftCms\Cms\Config\GeneralConfig::class)`.
```

**Config setting deprecated:**
```
- The `disableGraphqlTransformDirective` config setting is now deprecated.
```

**Replaced by Laravel built-in (no direct Craft replacement):**
```
- Deprecated `craft\filters\BasicHttpAuthLogin`. Use the `auth.basic` middleware instead.
```

**Multiple methods with "in favor of" and sub-list:**
```
- Deprecated `craft\events\WidgetEvent` in favor of the following new events:
  - `craft\services\Dashboard::EVENT_BEFORE_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaving`
  - `craft\services\Dashboard::EVENT_AFTER_SAVE_WIDGET` => `CraftCms\Cms\Dashboard\Events\WidgetSaved`
```

**Multiple methods from same class (sub-list with arrow mapping):**
```
- Deprecated `craft\helpers\App`. The following classes/methods should be used instead:
  - `App:devMode()` --> `app()->hasDebugModeEnabled()`
  - `App:parseBooleanEnv()` --> `\CraftCms\Cms\Support\Env::parseBoolean()`
```

## Removed

**Class removed with replacement:**
```
- Removed `craft\old\Class`. `CraftCms\Cms\New\Class` should be used instead.
```

**Class removed with multiple replacements:**
```
- Removed `craft\controllers\DashboardController`. The following controllers now implement this functionality:
  - `CraftCms\Cms\Http\Controllers\Dashboard\DashboardController`
  - `CraftCms\Cms\Http\Controllers\Dashboard\WidgetsController`
```

**Removed because previously deprecated:**
```
- Removed `craft\helpers\MigrationHelper` as it was deprecated since 4.0.0.
```

**Event removed:**
```
- Removed `craft\events\UpdateReleaseEvent` in favor of `CraftCms\Cms\Updates\Events\CriticalUpdateReleasedEvent`.
```

**Database column removed:**
```
- Removed `verificationCode` and `verificationCodeIssuedDate` columns on the `users` table in favor of the `password_reset_tokens` table.
```

## Replaced

```
- Replaced `craft\controllers\StructuresController`. `CraftCms\Cms\Http\Controllers\StructuresController`.
- Replaced `craft\controllers\SystemMessagesController` with `CraftCms\Cms\Http\Controllers\Utilities\SystemMessagesController`.
```

## Improved

```
- Improved the performance of `craft\helpers\Typecast`. ([#18426](https://github.com/craftcms/cms/pull/18426))
- Improved the accessibility of user permission lists. ([#18290](https://github.com/craftcms/cms/pull/18290))
- Improved drag-n-drop performance. ([#18019](https://github.com/craftcms/cms/pull/18019))
```

## Updated

```
- Updated Yii to 2.0.54.
- Updated Twig to 3.21. ([#17603](https://github.com/craftcms/cms/discussions/17603))
- Updated Axios to 1.12.2. ([#17988](https://github.com/craftcms/cms/pull/17988))
```

## Behavioral Changes (no action verb prefix)

```
- `CraftCms\Cms\User\Elements\User` now implements `Illuminate\Contracts\Auth\Authenticatable`.
- `craft\services\Elements::stopCollectingCacheInfo()` no longer sets the returned duration to the `cacheDuration` config setting if a duration wasn't explicitly declared. ([#16796](https://github.com/craftcms/cms/pull/16796))
- Element indexes now show "Paste" buttons alongside bulk element action buttons. ([#18427](https://github.com/craftcms/cms/issues/18427))
- `slug` columns referenced in element queries' `select`, `where`, or `orderBy` expressions now explicitly resolve to `elements_sites.slug`. ([#18416](https://github.com/craftcms/cms/issues/18416))
- The `maxCachedCloudImageSize` config setting is now set to `0` by default. ([#17997](https://github.com/craftcms/cms/pull/17997))
```

## Feature Descriptions (no code reference)

```
- Nested entries' edit screens now have a "Field settings" action menu item.
- Legacy entry index URLs now redirect `content/<page-name>`.
- Bulk element actions are now available on element indexes for mobile devices.
- Revisions now keep track of which element attributes/fields were modified for the revision.
```
