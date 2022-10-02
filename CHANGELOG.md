# Release Notes for Craft CMS 4

## Unreleased

### Fixed
- Fixed an error that could occur when editing a draft of an element type that didn’t have change tracking enabled.
- Fixed an error that caused `ElementQuery` instances to throw a serialization exception. ([#11981](https://github.com/craftcms/cms/issues/11981))

## 4.2.5.1 - 2022-09-21

### Fixed
- Fixed a bug where the “New category” button could be missing from the Categories index page. ([#11977](https://github.com/craftcms/cms/issues/11977))
- Fixed a bug where `Craft::t()` and the `|t` Twig filter were modifying digit-dash-digit sequences. ([#11980](https://github.com/craftcms/cms/issues/11980))
- Fixed PHP errors that occurred if `webonyx/graphql-php` 14.11.17 was installed. ([#11979](https://github.com/craftcms/cms/issues/11979), [webonyx/graphql-php#1221](https://github.com/webonyx/graphql-php/issues/1221))
- Fixed a PHP error that could occur when working with a draft. ([#11976](https://github.com/craftcms/cms/issues/11976))

## 4.2.5 - 2022-09-20

### Added
- Added `craft\helpers\Image::targetDimensions()`.

### Changed
- Edit Asset pages now show the “View” button for all file types, not just images, PDFs, and text files. ([#11936](https://github.com/craftcms/cms/issues/11936))

### Removed
- Removed the Punycode PHP library. ([#11948](https://github.com/craftcms/cms/issues/11948))

### Fixed
- Fixed a bug where image transforms weren’t always getting applied properly to all animation frames. ([#11937](https://github.com/craftcms/cms/pull/11937))
- Fixed a bug where animated WebP images would lose their animation frames when transformed. ([#11937](https://github.com/craftcms/cms/pull/11937))
- Fixed a bug where image transform dimensions could be calculated incorrectly when `upscaleImages` was `false`. ([#11837](https://github.com/craftcms/cms/issues/11837))
- Fixed a bug where the `users/send-password-reset-email` action wasn’t passing errors back to the template. ([#11933](https://github.com/craftcms/cms/issues/11933))
- Fixed an error that occurred when setting a non-numeric `width` or `height` on an image transform. ([#11837](https://github.com/craftcms/cms/issues/11837))
- Fixed a bug where the database connection wasn’t being configured properly when fluent config methods and environment variable overrides were being used in combination. ([#11941](https://github.com/craftcms/cms/issues/11941))
- Fixed a bug where slideouts lost their shadows when focused.
- Fixed a bug where `relatedTo*` arguments weren’t supported by `children` fields in GraphQL. ([#11918](https://github.com/craftcms/cms/issues/11918))
- Fixed a bug where Image Editor and slideout action buttons were obstructed when the Debug Toolbar was enabled. ([#11965](https://github.com/craftcms/cms/issues/11965))
- Fixed an error that occurred when installing Craft when MySQL’s `sql_require_primary_key` setting was enabled. ([#11374](https://github.com/craftcms/cms/discussions/11374))
- Fixed a bug subfolders created by Assets fields could be reported as missing when updating asset indexes, even if they contained assets. ([#11949](https://github.com/craftcms/cms/issues/11949))
- Fixed a PHP error that could occur in a potential race condition when calling  `craft\helpers\FileHelper::clearDirectory()`.
- Fixed a bug where Structure section entries that were duplicated via the “Save as a new entry” action on a provisional draft weren’t being placed within the structure properly.
- Fixed a bug where element’s `searchScore` properties would be set to `null` when their original score was below 1, rather than rounding to 0 or 1. ([#11973](https://github.com/craftcms/cms/issues/11973))

## 4.2.4 - 2022-09-13

### Changed
- The “New entry” and “New category” buttons on the Entries and Categories index pages now support <kbd>Ctrl</kbd>/<kbd>Command</kbd>/middle-clicking to open the edit page in a new window. ([#11870](https://github.com/craftcms/cms/issues/11870))
- Control panel menus now automatically reposition themselves when the window is resized.
- Improved the performance of some element queries on MySQL. ([#11825](https://github.com/craftcms/cms/pull/11825))
- `resave/*` commands now have a `--touch` option. When passed, elements’ `dateUpdated` timestamps will be updated as they’re resaved. ([#11849](https://github.com/craftcms/cms/discussions/11849))
- Underscores within query param values that begin/end with `*` are now escaped, so they aren’t treated as wildcard characters by the `like` condition. ([#11898](https://github.com/craftcms/cms/issues/11898))
- `craft\services\Elements::resaveElements()` now has a `$touch` argument.

### Fixed
- Fixed an error that could occur when upgrading to Craft 4, if any Matrix blocks contained null `sortOrder` values. ([#11843](https://github.com/craftcms/cms/issues/11843))
- Fixed a bug where image transform dimensions could be calculated incorrectly when `upscaleImages` was `false`. ([#11837](https://github.com/craftcms/cms/issues/11837))
- Fixed an error that occurred when parsing an image transform string that was missing an interlace type. ([#11834](https://github.com/craftcms/cms/pull/11834))
- Fixed a bug where element caches weren’t being invalidated during garbage collection, so hard-deleted elements could appear to still exist.
- Fixed a bug where image transforms were always getting saved with `dateIndexed` set to `null`. ([#11863](https://github.com/craftcms/cms/pull/11863))
- Fixed an error that could occur when rendering front-end templates if there was a problem connecting to the database. ([#11855](https://github.com/craftcms/cms/issues/11855))
- Fixed a bug where Edit Asset pages were showing the “View” button for assets in volumes without public URLs. ([#11860](https://github.com/craftcms/cms/issues/11860))
- Fixed a bug where the Assets index page wasn’t handling failed uploads properly. ([#11866](https://github.com/craftcms/cms/issues/11866))
- Fixed a bug where it was possible to save an asset with a focal point outside its cropped area. ([#11875](https://github.com/craftcms/cms/issues/11875))
- Fixed a bug where element index filter HUDs were unresponsive if another one was already active for a different site/source. ([#11880](https://github.com/craftcms/cms/issues/11880))
- Fixed a bug where newly-created subfolders on the Assets index page could appear to have the wrong indentation.
- Fixed a UI bug where renaming a newly-created volume subfolder didn’t appear to have any effect.
- Fixed a bug where empty URL fields would be marked as changed, even when no change was made to them. ([#11908](https://github.com/craftcms/cms/issues/11908))
- Fixed a UI bug where autosuggest menus weren’t getting filtered when first opened for inputs with existing values. ([#11896](https://github.com/craftcms/cms/issues/11896))
- Fixed a bug where Entry Type condition rules weren’t working for conditions that were applied to a single element. ([#11914](https://github.com/craftcms/cms/issues/11914))
- Fixed a bug where Related To condition rules weren’t working for conditions that were applied to a single element, for cross-site relations. ([#11892](https://github.com/craftcms/cms/issues/11892))
- Fixed a bug where form action keyboard shortcuts weren’t available when a custom select menu was focused. ([#11919](https://github.com/craftcms/cms/issues/11919))
- Fixed a bug where transforming an animated GIF into a WebP file would only include the first frame. ([#11889](https://github.com/craftcms/cms/issues/11889))
- Fixed a bug where `craft\models\FieldLayout::createFromConfig()` was ignoring `id`, `uid`, `type`, and `reservedFieldHandles` keys, if set. ([#11929](https://github.com/craftcms/cms/issues/11929))

### Security
- Fixed XSS vulnerabilities.
- Password inputs no longer temporarily reveal the password when the <kbd>Alt</kbd> key is pressed. ([#11930](https://github.com/craftcms/cms/issues/11930))

## 4.2.3 - 2022-08-26

### Changed
- If a plugin’s license key is set to an empty environment variable, its trial license key will now be stored in `.env` rather than the project config. ([#11830](https://github.com/craftcms/cms/issues/11830))

### Fixed
- Fixed a PHP error that occurred when garbage collection was run on web requests. ([#11829](https://github.com/craftcms/cms/issues/11829))

## 4.2.2 - 2022-08-23

### Added
- Added the `utils/fix-field-layout-uids` command. ([#11746](https://github.com/craftcms/cms/issues/11746))

### Changed
- Improved the styling of Categories fields.
- The first field group is now automatically selected by default when creating a new custom field.
- Improved console output for the `gc` command.
- The `gc` command now runs garbage collection for data caches.
- Exception JSON responses now include `name` and `code` keys. ([#11799](https://github.com/craftcms/cms/discussions/11799))
- `elements/*` actions no longer include custom field values in the failure response data, improving performance. ([#11807](https://github.com/craftcms/cms/discussions/11807))

### Fixed
- Fixed a bug where keyboard focus wasn’t being maintained when changing the element type within a “Related To” condition rule.
- Fixed a bug where keyboard focus wasn’t being maintained when changing the country within an address’s “Administrative Area” condition rule.
- Fixed a bug where Date fields’ Timezone menus could be clipped. ([#11780](https://github.com/craftcms/cms/pull/11780))
- Fixed an error that could occur when saving an unpublished draft, if any custom validation errors were added to it after its draft status had been removed. ([#11407](https://github.com/craftcms/cms/issues/11407))
- Fixed a bug where custom validation errors would be shown twice for unpublished drafts, if they were added to it after its draft status had been removed. ([#11407](https://github.com/craftcms/cms/issues/11407))
- Fixed PHP warnings that would occur when passing `0` into `craft\helpers\DateTimeHelper::humanDuration()`. ([#11787](https://github.com/craftcms/cms/issues/11787))
- Fixed a bug where selected assets weren’t getting automatically replaced when an image was edited and “Save as a new asset” was chosen. ([#11805](https://github.com/craftcms/cms/issues/11805))
- Fixed a JavaScript error that occurred when editing a user via a slideout, if the user had any addresses. ([#11810](https://github.com/craftcms/cms/issues/11810))
- Fixed a bug where some invalid slideout submissions weren’t being handled properly. ([#11812](https://github.com/craftcms/cms/issues/11812))
- Fixed a bug where `craft\helpers\DateTimeHelper::toDateInterval()` was returning negative interval durations when integers were passed in. ([#11814](https://github.com/craftcms/cms/pull/11814))
- Fixed a bug where `iframeResizer.contentWindow.js` was getting loaded for all preview requests, not just Live Preview, and even when `useIframeResizer` was disabled. ([#11778](https://github.com/craftcms/cms/issues/11778))
- Fixed a bug where deleted relations and Matrix blocks could persist if the edit form was submitted before they had been fully animated away. ([#11789](https://github.com/craftcms/cms/issues/11789))
- Fixed a PHP error that could occur if `craft\services\Assets::getUserTemporaryUploadFolder()` was called when there was no logged-in user account. ([#11751](https://github.com/craftcms/cms/issues/11751))

## 4.2.1.1 - 2022-08-10

### Fixed
- Fixed a bug where saving an element with invalid field values could result in some field values being forgotten. ([#11756](https://github.com/craftcms/cms/issues/11756))
- Fixed a bug where it wasn’t always possible to serve asset bundles via `webpack-dev-server` over SSL. ([#11758](https://github.com/craftcms/cms/pull/11758))

## 4.2.1 - 2022-08-09

### Added
- Added the `project-config/export` command. ([#11733](https://github.com/craftcms/cms/pull/11733))
- Added `craft\config\GeneralConfig::getRememberedUserSessionDuration()`.
- Added `craft\helpers\DateTimeHelper::toDateInterval()`.

### Changed
- “Related To” condition rules are now available for conditions that are stored in the project config. ([#11740](https://github.com/craftcms/cms/issues/11740))
- Element index filters are now managed for each site and source, rather than just for each source. ([#11719](https://github.com/craftcms/cms/issues/11719))
- `craft\config\DbConfig::dsn()` now parses the DSN string and populates the other DSN-settable config properties.
- `craft\helpers\DateTimeHelper::humanDuration()` no longer returns the number of weeks, unless the number of days is divisible by 7. ([#11594](https://github.com/craftcms/cms/discussions/11594))
- `craft\helpers\DateTimeHelper::humanDuration()` now accepts date interval strings to be passed in.
- `craft\helpers\Db::url2config()` now returns `driver`, `server`, `port`, and `database` keys, when possible. ([#11735](https://github.com/craftcms/cms/issues/11735))
- `craft\services\Assets::getImagePreviewUrl()` now throws a `yii\base\NotSupportedException` if a preview URL could not be generated for the asset, rather than causing a PHP error.

### Deprecated
- Deprecated `craft\helpers\DateTimeHelper::secondsToInterval()`. `toDateInterval()` should be used instead.

### Fixed
- Fixed a bug where database connections would always use port `3306` by default if `craft\config\DbConfig` had been configured via fluent methods, even for PostgreSQL.
- Fixed a bug where system messages provided by Yii weren’t getting translated in some cases. ([#11712](https://github.com/craftcms/cms/issues/11712))
- Fixed a bug where the “Keep me signed in” checkbox label wasn’t always accurately representing the `rememberedUserSessionDuration` config setting. ([#11594](https://github.com/craftcms/cms/discussions/11594))
- Fixed a bug where the `Craft.cp.setSiteId()` JavaScript method wasn’t updating `Craft.siteId`, or the base URLs used by `Craft.getActionUrl()`, `Craft.getCpUrl()`, and `Craft.getUrl()`.
- Fixed an error that occurred when removing a Single section from the primary site, if it contained any Matrix blocks. ([#11669](https://github.com/craftcms/cms/issues/11669))
- Fixed a bug where Matrix sub-fields weren’t showing their validation errors when `autosaveDrafts` was `false`. ([#11731](https://github.com/craftcms/cms/issues/11731))
- Fixed a bug where saving an element with invalid field values could result in the invalid values being forgotten, rather than re-validated. ([#11731](https://github.com/craftcms/cms/issues/11731))
- Fixed a bug where the database connection would be misconfigured if the `url` connection setting was used. ([#11735](https://github.com/craftcms/cms/issues/11735))
- Fixed a bug where the element index filter HUDs weren’t always visually aligned with the search input. ([#11739](https://github.com/craftcms/cms/issues/11739))
- Fixed a bug where it wasn’t possible to preview or edit image assets if their filesystem and transform filesystem didn’t have public URLs. ([#11686](https://github.com/craftcms/cms/issues/11686), [#11687](https://github.com/craftcms/cms/issues/11687))
- Fixed a bug where not all project config changes would be applied if a site was deleted. ([#9567](https://github.com/craftcms/cms/issues/9567))
- Fixed a bug where `$` characters in database connection passwords weren’t being escaped property when backing up/restoring the database. ([#11750](https://github.com/craftcms/cms/issues/11750))

### Security
- Fixed XSS vulnerabilities.

## 4.2.0.2 - 2022-07-27

### Fixed
- Fixed a bug where `Garnish.uiShortcutManager` was getting double-instantiated, causing some keyboard shortcuts to be triggered multiple times.
- Fixed a JavaScript error that occurred when switching sites in the control panel. ([#11709](https://github.com/craftcms/cms/issues/11709))
- Fixed a bug where some config settings set via fluent setters weren’t getting normalized.
- Fixed a bug where the database connection DSN string wasn’t getting built properly when the connection settings were set via fluent setters.

## 4.2.0.1 - 2022-07-26

### Fixed
- Fixed an error that could occur when passing an object into `craft\helpers\ArrayHelper::removeValue()` or the `|without` filter.

## 4.2.0 - 2022-07-26

### Added
- The control panel is now translated into Ukrainian.
- Element conditions can now include condition rules for Matrix fields. ([#11620](https://github.com/craftcms/cms/issues/11620))
- Element conditions can now include condition rules for Money fields. ([#11560](https://github.com/craftcms/cms/issues/11560))
- Added the “Notification Duration” user accessibility preference. ([#11612](https://github.com/craftcms/cms/pull/11612))
- The `accessibilityDefaults` config setting now supports a `notificationDuration` key.
- Added `craft\behaviors\SessionBehavior::getSuccess()`.
- Added `craft\behaviors\SessionBehavior::setSuccess()`.
- Added `craft\config\BaseConfig`. ([#11591](https://github.com/craftcms/cms/pull/11591), [#11656](https://github.com/craftcms/cms/pull/11656))
- Added `craft\controllers\UsersController::EVENT_AFTER_FIND_LOGIN_USER`. ([#11645](https://github.com/craftcms/cms/pull/11645))
- Added `craft\controllers\UsersController::EVENT_BEFORE_FIND_LOGIN_USER`. ([#11645](https://github.com/craftcms/cms/pull/11645))
- Added `craft\events\DefineFieldLayoutCustomFieldsEvent`.
- Added `craft\events\FindLoginUserEvent`.
- Added `craft\events\IndexKeywordsEvent`.
- Added `craft\fields\conditions\EmptyFieldConditionRule`.
- Added `craft\helpers\DateTimeHelper::humanDuration()`.
- Added `craft\helpers\Template::resolveTemplatePathAndLine()`.
- Added `craft\models\FieldLayout::EVENT_DEFINE_CUSTOM_FIELDS`. ([#11634](https://github.com/craftcms/cms/discussions/11634))
- Added `craft\services\Config::getLoadingConfigFile()`.
- Added `craft\services\Elements::EVENT_INVALIDATE_CACHES`. ([#11617](https://github.com/craftcms/cms/pull/11617))
- Added `craft\services\Search::EVENT_BEFORE_INDEX_KEYWORDS`. ([#11575](https://github.com/craftcms/cms/discussions/11575))

### Changed
- Redesigned user notifications. ([#11612](https://github.com/craftcms/cms/pull/11612))
- Most element notifications now include a link to the element. ([#11612](https://github.com/craftcms/cms/pull/11612))
- Improved overall control panel accessibility. ([#11563](https://github.com/craftcms/cms/pull/11563), [#11543](https://github.com/craftcms/cms/pull/11543), [#11688](https://github.com/craftcms/cms/pull/11688), [#11699](https://github.com/craftcms/cms/pull/11699))
- Improved condition builder accessibility. ([#11588](https://github.com/craftcms/cms/pull/11588), [#11643](https://github.com/craftcms/cms/pull/11643))
- Improved Image Editor accessibility. ([#11496](https://github.com/craftcms/cms/pull/11496))
- The “Keep me signed in” checkbox label on the control panel’s login page now includes the remembered session duration, e.g. “Keep me signed in for 2 weeks”. ([#11594](https://github.com/craftcms/cms/discussions/11594))
- Dashboard widgets no longer show a confirmation dialog when deleted. Their delete notifications include an “Undo” button instead. ([#11573](https://github.com/craftcms/cms/discussions/11573))
- Element edit pages no longer jump down when the “Showing your unsaved changes” notice is added, unless there’s not enough content to require a scroll bar. ([#11586](https://github.com/craftcms/cms/discussions/11586))
- Matrix block previews now show selected option labels rather than their raw values. ([#11659](https://github.com/craftcms/cms/issues/11659))
- Improved the behavior of some console commands for non-interactive shells. ([#11650](https://github.com/craftcms/cms/issues/11650))
- The `utils/prune-revisions` console command now has a `--section` option. ([#8783](https://github.com/craftcms/cms/discussions/8783))
- Deprecation warnings’ stack traces now show source templates’ paths and line numbers.
- Exception JSON responses now include the previous exception details, recursively. ([#11694](https://github.com/craftcms/cms/discussions/11694))
- `config/general.php` and `config/db.php` can now return `craft\config\GeneralConfig`/`DbConfig` objects, which can be defined using new fluent setter methods. ([#11591](https://github.com/craftcms/cms/pull/11591), [#11656](https://github.com/craftcms/cms/pull/11656))
- The `|duration` Twig filter can now be used with an integer representing a number of seconds, and its `showSeconds` argument is no longer required. Seconds will be output if the duration is less than one minute by default.
- The `|length` Twig filter now checks if the variable is a query, and if so, returns its count. ([#11625](https://github.com/craftcms/cms/discussions/11625))
- The `|without` Twig filter no longer uses strict value comparisons by default. It has a new `$strict` argument that can be set to `true` to enforce strict comparisons if desired. ([#11695](https://github.com/craftcms/cms/issues/11695))
- `craft\base\conditions\BaseConditionRule::inputHtml()` is no longer abstract, and returns an empty string by default.
- `craft\behaviors\SessionBehavior::setError()` now has a `$settings` argument.
- `craft\behaviors\SessionBehavior::setNotice()` now has a `$settings` argument.
- `craft\db\Query` now implements the `ArrayAccess` and `IteratorAggregate` interfaces, so queries (including element queries) can be treated as arrays.
- `craft\helpers\ArrayHelper::removeValue()` no longer uses strict value comparisons by default. It has a new `$strict` argument that can be set to `true` to enforce strict comparisons if desired.
- `craft\web\Controller::asSuccess()` now has a `$notificationSettings` argument.
- `craft\web\Controller::setFailFlash()` now has a `$settings` argument.
- `craft\web\Controller::setSuccessFlash()` now has a `$settings` argument.

### Deprecated
- Deprecated `craft\helpers\DateTimeHelper::humanDurationFromInterval()`. `humanDuration()` should be used instead.
- Deprecated `craft\helpers\DateTimeHelper::secondsToHumanTimeDuration()`. `humanDuration()` should be used instead.

### Fixed
- Fixed a bug where new condition rules’ type selectors weren’t getting auto-focused.
- Fixed a bug where Quick Post widgets weren’t submitting custom field values.
- Fixed a bug where assets’ `getImg()` methods were returning `null` for assets in volumes without URLs, even if a transform was being used. ([#11614](https://github.com/craftcms/cms/issues/11614))
- Fixed a bug where sensitive data wasn’t getting redacted in the logs when Dev Mode was enabled. ([#11618](https://github.com/craftcms/cms/issues/11618))
- Fixed a SQL error that could occur on MySQL 5. ([#11596](https://github.com/craftcms/cms/issues/11596))
- Fixed an error that could occur when upgrading to Craft 4. ([#11644](https://github.com/craftcms/cms/issues/11644))
- Fixed a bug where the green color used in lightswitches was too dark. ([#11653](https://github.com/craftcms/cms/issues/11653))
- Fixed a bug where relational and Matrix fields were assuming their values hadn’t been eager-loaded on element save. ([#11667](https://github.com/craftcms/cms/issues/11667), [#11670](https://github.com/craftcms/cms/issues/11670))
- Fixed a bug where deprecation warnings for treating an element query as an array weren’t getting logged with an origin, if they involved Twig’s `|batch` filter. ([#11597](https://github.com/craftcms/cms/issues/11597))
- Fixed a bug where `{% js %}`, `{% script %}`, and `{% css %}` tags weren’t registering JavaScript and CSS code properly when used within a `{% cache %}` tag that contained an ungenerated image transform. ([#11602](https://github.com/craftcms/cms/issues/11602))
- Fixed a bug where the “User saved” notification was translated for the former language, when changing the current user’s language preference.
- Fixed a JavaScript error that occurred when removing a category from a Categories field, if any of its descendants were selected as well. ([#11641](https://github.com/craftcms/cms/issues/11641))
- Fixed a bug where links to config settings from **Settings** → **General** didn’t include the correct setting anchors. ([#11665](https://github.com/craftcms/cms/pull/11665))
- Fixed styling issues with Live Preview in Firefox.

### Security
- Fixed an information disclosure vulnerability.

## 4.1.4.1 - 2022-07-13

### Fixed
- Fixed a bug where `craft\services\Assets::getRootFolderByVolumeId()` wasn’t returning the root folder. ([#11593](https://github.com/craftcms/cms/issues/11593))

## 4.1.4 - 2022-07-12

### Added
- Added `craft\models\FieldLayout::getVisibleCustomFieldElements()`.

### Changed
- Relation fields now focus on the next related element’s “Remove” button when an element is removed. ([#11577](https://github.com/craftcms/cms/issues/11577))

### Deprecated
- Deprecated `craft\base\FieldTrait::$required`. `craft\fieldlayoutelements\BaseField::$required` should be used instead.

### Fixed
- Fixed a bug where assets’ native Alternative Text fields were getting mislabeled as translatable. ([#11576](https://github.com/craftcms/cms/issues/11576))
- Fixed a bug where fields nested within Neo fields could be incorrectly validated as required. ([#11574](https://github.com/craftcms/cms/issues/11574))
- Fixed a PHP error that occurred when editing a Date field with a Min Date setting set.
- Fixed a bug where date range inputs weren’t working on mobile. ([#11571](https://github.com/craftcms/cms/issues/11571))
- Fixed a bug where the “Craft Support” Dashboard widget wasn’t being labeled properly in the widget settings HUD and delete confirmation dialog. ([#11573](https://github.com/craftcms/cms/discussions/11573))
- Fixed a bug where the project config cache was getting invalidated on each public GraphQL API request.

## 4.1.3 - 2022-07-07

### Changed
- Address fields now show required indicators based on the configured validation rules. ([#11566](https://github.com/craftcms/cms/pull/11566))

### Fixed
- Fixed a JavaScript error that occurred on the Updates utility. ([#11567](https://github.com/craftcms/cms/issues/11567))
- Fixed a bug where Craft’s Composer commands could produce a malformed `composer.json` file. ([#11564](https://github.com/craftcms/cms/issues/11564))

## 4.1.2 - 2022-07-06

### Added
- Added `craft\helpers\ProjectConfig::ensureAllFilesystemsProcessed()`.

### Changed
- Relational field condition rules now have the “is related to” operator selected by default. ([#11550](https://github.com/craftcms/cms/discussions/11550))

### Fixed
- Fixed a bug where the Updates utility wasn’t checking for updates properly. ([#11552](https://github.com/craftcms/cms/issues/11552))
- Fixed an error that could occur when deploying a Craft 4 upgrade to another server. ([#11558](https://github.com/craftcms/cms/issues/11558))
- Fixed a bug where Assets fields were relocating assets when saving a revision.
- Fixed a bug where asset bundles weren’t getting registered on the front end. ([#11555](https://github.com/craftcms/cms/issues/11555))

## 4.1.1 - 2022-07-05

### Changed
- Improved the control panel tab design. ([#11524](https://github.com/craftcms/cms/pull/11524))
- Changed the order of relational field condition rule operators, so “is related to” is listed first. ([#11550](https://github.com/craftcms/cms/discussions/11550))
- Template caching is now supported for console requests, for `{% cache %}` tags that have the `globally` param. ([#11551](https://github.com/craftcms/cms/issues/11551))
- Updated Composer to 2.2.15. ([#11538](https://github.com/craftcms/cms/issues/11538))

### Fixed
- Fixed an error that could occur if any custom fields were missing their field group. ([#11522](https://github.com/craftcms/cms/discussions/11522))
- Fixed a bug where custom selects weren’t scrolling to the visually-focused option.
- Fixed errors that could occur if an element condition contained any rules for deleted custom fields. ([#11526](https://github.com/craftcms/cms/issues/11526))
- Fixed a bug where the “Deactivate users by default” user setting wasn’t working. ([#11519](https://github.com/craftcms/cms/issues/11519))
- Fixed a styling issue with the Edit Route modal. ([#11528](https://github.com/craftcms/cms/issues/11528))
- Fixed a bug where assets uploaded from Assets fields weren’t retaining their original filename. ([#11530](https://github.com/craftcms/cms/issues/11530))
- Fixed a bug where project config changes made at the end of the request lifecycle weren’t getting saved.
- Fixed a bug where toggling entries’ and categories’ site-specific statuses from element editor slideouts wasn’t working. ([#11547](https://github.com/craftcms/cms/issues/11547))
- Fixed a SQL error that occurred when running the `utils/prune-provisional-drafts` command. ([#11548](https://github.com/craftcms/cms/issues/11548))
- Fixed focus styling issues with the Edit Route modal.

## 4.1.0.2 - 2022-06-28

### Fixed
- Fixed a PHP error. ([#11518](https://github.com/craftcms/cms/issues/11518))

## 4.1.0.1 - 2022-06-28

### Fixed
- Fixed an infinite recursion bug. ([#11514](https://github.com/craftcms/cms/issues/11514))

## 4.1.0 - 2022-06-28

### Added
- Field layouts can now have “Line Break” UI elements. ([#11328](https://github.com/craftcms/cms/discussions/11328))
- Added the `db/drop-all-tables` command. ([#11288](https://github.com/craftcms/cms/pull/11288))
- Added the `elements/delete` command.
- Added the `elements/restore` command.
- Added the `project-config/get` command. ([#11341](https://github.com/craftcms/cms/pull/11341))
- Added the `project-config/remove` command. ([#11341](https://github.com/craftcms/cms/pull/11341))
- Added the `project-config/set` command. ([#11341](https://github.com/craftcms/cms/pull/11341))
- The `AdminTable` Vue component can now be included into other Vue apps, in addition to being used as a standalone app. ([#11107](https://github.com/craftcms/cms/pull/11107))
- Added a `one()` alias for `first()` to collections. ([#11134](https://github.com/craftcms/cms/discussions/11134))
- Added `craft\base\Element::EVENT_DEFINE_CACHE_TAGS`. ([#11171](https://github.com/craftcms/cms/discussions/11171))
- Added `craft\base\Element::cacheTags()`.
- Added `craft\base\FieldInterface::getLabelId()`.
- Added `craft\console\controllers\UsersController::$activate`.
- Added `craft\elements\conditions\ElementCondition::$sourceKey`.
- Added `craft\elements\db\ElementQuery::EVENT_AFTER_POPULATE_ELEMENTS`. ([#11262](https://github.com/craftcms/cms/discussions/11262))
- Added `craft\elements\db\ElementQuery::EVENT_DEFINE_CACHE_TAGS`. ([#11171](https://github.com/craftcms/cms/discussions/11171))
- Added `craft\events\PopulateElementsEvent`.
- Added `craft\fieldlayoutelements\BaseField::labelId()`.
- Added `craft\fieldlayoutelements\LineBreak`.
- Added `craft\helpers\DateTimeHelper::now()`.
- Added `craft\helpers\DateTimeHelper::pause()`. ([#11130](https://github.com/craftcms/cms/pull/11130))
- Added `craft\helpers\DateTimeHelper::resume()`. ([#11130](https://github.com/craftcms/cms/pull/11130))

### Changed
- Improved overall control panel accessibility. ([#11297](https://github.com/craftcms/cms/pull/11297), [#11296](https://github.com/craftcms/cms/pull/11296), [#11414](https://github.com/craftcms/cms/pull/11414), [#11452](https://github.com/craftcms/cms/pull/11452))
- Improved pagination UI accessibility. ([#11126](https://github.com/craftcms/cms/pull/11126))
- Improved element index accessibility. ([#11169](https://github.com/craftcms/cms/pull/11169), [#11200](https://github.com/craftcms/cms/pull/11200), [#11251](https://github.com/craftcms/cms/pull/11251))
- Improved Dashboard accessibility. ([#11217](https://github.com/craftcms/cms/pull/11217), [#11297](https://github.com/craftcms/cms/pull/11297))
- Improved address management accessibility. ([#11397](https://github.com/craftcms/cms/pull/11397))
- Improved Matrix field accessibility. ([#11306](https://github.com/craftcms/cms/pull/11306))
- Improved mobile support. ([#11323](https://github.com/craftcms/cms/pull/11323), [#11430](https://github.com/craftcms/cms/pull/11430))
- Improved keyboard support for custom selects. ([#11414](https://github.com/craftcms/cms/pull/11414))
- It’s now possible to remove all selected elements from relational fields by pressing <kbd>Backspace</kbd> or <kbd>Delete</kbd> while one of them is focused.
- Improved the UI of condition builders. ([#11386](https://github.com/craftcms/cms/pull/11386))
- Entry Type condition rules now allow multiple selections. ([#11124](https://github.com/craftcms/cms/pull/11124))
- Element index filters now only show condition rules for the custom fields that are used by the field layouts in the selected source, if a native source is selected. ([#11187](https://github.com/craftcms/cms/discussions/11187))
- Element index filters now only show condition rules for custom fields used by field layouts created for the target element type, if no native source is selected.
- Condition builders can now include multiple rules with the same label, as long as they’re in different groups.
- Asset indexes now have a “Location” table attribute option. ([#11450](https://github.com/craftcms/cms/discussions/11450))
- It’s now possible to sort entries by their section and type. ([#9192](https://github.com/craftcms/cms/discussions/9192), [#11335](https://github.com/craftcms/cms/discussions/11335))
- It’s now possible to sort assets by their file kind.
- Element editor metadata now lists elements’ IDs.
- Live Preview now always shows a “Refresh” button, regardless of whether the preview target has auto-refresh enabled. ([#11160](https://github.com/craftcms/cms/discussions/11160))
- Sites’ Language settings now display the locale IDs as option hints, rather than the languages’ native names. ([#11195](https://github.com/craftcms/cms/discussions/11195))
- Selectize options can now specify searchable `keywords` that won’t be visible in the UI.
- Selectize inputs will now include their options’ values as search keywords.
- Newly-created entries now get placeholder Post Date set on them, so they get sorted appropriately when querying for entries ordered by `postDate`. ([#11272](https://github.com/craftcms/cms/issues/11272))
- Element queries can now pass columns into the `orderBy` param in addition to `score` when searching. ([#11470](https://github.com/craftcms/cms/pull/11470), [#11457](https://github.com/craftcms/cms/discussions/11457))
- Field layout elements within field layout designers now support double-clicking to open their settings slideout. ([#11277](https://github.com/craftcms/cms/discussions/11277))
- The control panel’s JavaScript queue is now paused when the browser tab isn’t visible. ([#10632](https://github.com/craftcms/cms/issues/10632))
- The `db/restore` command now asks whether the database should be backed up, and whether all existing database tables should be dropped, prior to restoring the backup. ([#11288](https://github.com/craftcms/cms/pull/11288))
- The `users/create` command now asks whether the user should be activated when saved.
- The `maxBackups` config setting now impacts `.sql.zip` files in addition to `.sql` files. ([#11241](https://github.com/craftcms/cms/issues/11241))
- Deprecation messages are now consistently referred to as “deprecation warnings” in the control panel.
- Callback functions returned by elements’ `sortOptions()`/`defineSortOptions()` methods are now passed a `craft\db\Connection` object as a second argument.
- All element sources now have a “Set Status” action, even if the element type’s `defineActions()` method didn’t include one, if the element type’s `hasStatuses()` method returns `true`. ([#11383](https://github.com/craftcms/cms/discussions/11383))
- All element sources now have a “View” action, even if the element type’s `defineActions()` method didn’t include one, if the element type’s `hasUris()` method returns `true`. ([#11383](https://github.com/craftcms/cms/discussions/11383))
- All element sources now have “Edit” and “Delete” actions, even if the element type’s `defineActions()` method didn’t include them. ([#11383](https://github.com/craftcms/cms/discussions/11383))
- The “Set Status” and “Edit” element actions are now only available for elements whose `canSave()` method returned `true`.
- Assets fields now reject uploaded files which don’t pass their “Selectable Assets Condition” setting. ([#11433](https://github.com/craftcms/cms/issues/11433))
- It’s now possible to save new assets without setting their `filename` or `kind` attributes, as long as `newLocation` or `newFilename` is set. ([#11439](https://github.com/craftcms/cms/issues/11439))
- The `searchindex` table is now uses the InnoDB storage engine by default for MySQL installs. ([#11374](https://github.com/craftcms/cms/discussions/11374))
- The `_layouts/elementindex` control panel template now sets the page title based on the element’s `pluralDisplayName()` method by default. ([#11502](https://github.com/craftcms/cms/pull/11502))
- `craft\test\ActiveFixture::$data` is now populated with the active record instances, making them accessible to tests via `$this->tester->grabFixture('my-fixture', 'data-key')`. ([#11445](https://github.com/craftcms/cms/pull/11445))
- Address validation rules are now defined by `defineRules()`. ([#11471](https://github.com/craftcms/cms/pull/11471))
- `Garnish.DELETE_KEY` now refers to the actual <kbd>Delete</kbd> key code, and the <kbd>Backspace</kbd> key code is now referenced by `Garnish.BACKSPACE_KEY`.

### Deprecated
- Deprecated `craft\elements\actions\DeleteAssets`. `craft\elements\actions\Delete` should be used instead.

### Removed
- Removed `craft\elements\conditions\entries\EntryTypeCondition::$sectionUid`.
- Removed `craft\elements\conditions\entries\EntryTypeCondition::$entryTypeUid`.

## 4.0.6 - 2022-06-28

### Added
- Added `craft\fields\BaseOptionsField::encodeValue()`.

### Changed
- Improved the `install` command’s error output when invalid options were passed.
- `canonical` is now a reserved field handle. ([#11503](https://github.com/craftcms/cms/issues/11503))
- `craft\fields\BaseOptionsField::translatedOptions()` now has an `$encode` argument.

### Fixed
- Fixed a bug where self relations within relational fields were being forgotten. ([#11461](https://github.com/craftcms/cms/issues/11461))
- Fixed a bug where the `install` command required `--site-name`, `--site-url`, and `--language` options to be passed when project config YAML was already present. ([#11513](https://github.com/craftcms/cms/issues/11513))
- Fixed a bug where `Garnish.setFocusWithin()` wasn’t working if the first focusable element was a checkbox. ([#11498](https://github.com/craftcms/cms/discussions/11498))
- Fixed a bug where Matrix blocks could be saved in the wrong order.
- Fixed a bug where Checkboxes, Dropdown, Multi-select, and Radio Buttons fields could lose their values if their option values were set to integers. ([#11461](https://github.com/craftcms/cms/issues/11461))

## 4.0.5.2 - 2022-06-24

### Fixed
- Fixed a SQL error that could occur on MySQL 5. ([#11493](https://github.com/craftcms/cms/issues/11493))
- Fixed a bug where Craft’s Composer commands weren’t ensuring that `config.allow-plugins.yiisoft/yii2-composer` was `true` in `composer.json`. ([#11399](https://github.com/craftcms/cms/issues/11399))

## 4.0.5.1 - 2022-06-22

### Fixed
- Fixed a bug where not all changes to entries and categories created via the “Save and add another” action were propagating to other sites. ([#11476](https://github.com/craftcms/cms/issues/11476))
- Fixed a bug where it wasn’t possible to rename assets.
- Fixed a bug where a provisional draft could be created for an entry if its form was interacted with before the page had fully initialized. ([#11466](https://github.com/craftcms/cms/issues/11466))
- Fixed exact phrase searching on PostgreSQL. ([#11486](https://github.com/craftcms/cms/issues/11486))

## 4.0.5 - 2022-06-21

### Added
- Added `craft\helpers\Number::isIntOrFloat()`.

### Changed
- Categories now support change tracking.
- Improved performance when working with temp asset folders.
- Temp asset folders are no longer created until they’re actually needed. ([#11427](https://github.com/craftcms/cms/issues/11427))
- Element index queries are no longer cached if they contain a search term.
- Search inputs within field layout designers now prevent the containing form from being submitted when the <kbd>Return</kbd> key is pressed. ([#11415](https://github.com/craftcms/cms/discussions/11415))

### Deprecated
- Deprecated `craft\services\Categories::pruneDeletedField()`. ([#11054](https://github.com/craftcms/cms/discussions/11054))
- Deprecated `craft\services\Globals::pruneDeletedField()`. ([#11054](https://github.com/craftcms/cms/discussions/11054))
- Deprecated `craft\services\Sections::pruneDeletedField()`. ([#11054](https://github.com/craftcms/cms/discussions/11054))
- Deprecated `craft\services\Tags::pruneDeletedField()`. ([#11054](https://github.com/craftcms/cms/discussions/11054))
- Deprecated `craft\services\Users::pruneDeletedField()`. ([#11054](https://github.com/craftcms/cms/discussions/11054))
- Deprecated `craft\services\Volumes::pruneDeletedField()`. ([#11054](https://github.com/craftcms/cms/discussions/11054))

### Fixed
- Fixed an error that could occur when saving an element to a disabled site. ([#10499](https://github.com/craftcms/cms/issues/10499))
- Fixed a bug where newly-added condition rules’ types were still selectable for preexisting condition rules, when they shouldn’t have been.
- Fixed a bug where field layout designers were checking the wrong setting when determining whether to include UI elements (`customizableTabs` instead of `customizableUi`).
- Fixed a bug where the Asset Indexes utility was analyzing image transform directories and files. ([#11362](https://github.com/craftcms/cms/issues/11362), [#11384](https://github.com/craftcms/cms/pull/11384))
- Fixed a bug where focus was getting trapped within element editor slideouts’ sidebars even for wide viewports where there was enough room to display the sidebar side-by-side with other slideout content. ([#11358](https://github.com/craftcms/cms/pull/11358))
- Fixed a bug where users’ Formatting Locale preferences weren’t always being respected.
- Fixed a bug where address card menus would linger around after an address was deleted.
- Fixed a bug where the `index-assets` command could produce unexpected output. ([#11194](https://github.com/craftcms/cms/issues/11194)).
- Fixed a bug where video controls within asset preview modals were inaccessible via the keyboard. ([#11371](https://github.com/craftcms/cms/pull/11371))
- Fixed a bug where `transform` GraphQL directives weren’t working for Assets fields. ([#10299](https://github.com/craftcms/cms/discussions/10299))
- Fixed a PHP error that could occur when running the `help` command. ([#11423](https://github.com/craftcms/cms/issues/11423))
- Fixed a bug where `craft\helpers\App::env()` was converting some values to integers or floats unexpectedly. ([#11422](https://github.com/craftcms/cms/issues/11422))
- Fixed a bug where changes to existing Matrix blocks weren’t saving for element types that supported drafts but not change tracking. ([#11419](https://github.com/craftcms/cms/issues/11419))
- Fixed a bug where double-clicking on a related asset’s thumbnail could open the asset’s preview modal. ([#11424](https://github.com/craftcms/cms/issues/11424))
- Fixed a bug where the control panel wasn’t displaying file upload failure messages.
- Fixed a bug where `action` query params were taking precedence over `actionTrigger` URI matches, when handling action requests. ([#11435](https://github.com/craftcms/cms/issues/11435))
- Fixed a bug where image fields within Edit User pages and the Settings → General page weren’t resetting properly after an image was deleted. ([#11436](https://github.com/craftcms/cms/issues/11436))
- Fixed a bug where User Group condition rules set to the “is not one of” operator weren’t being applied to individual elements correctly. ([#11444](https://github.com/craftcms/cms/discussions/11444))
- Fixed a JavaScript error that occurred on element indexes for users that didn’t have permission to edit any sites.
- Fixed a bug where users without permission to create new entries in a section could duplicate existing entries. ([#11447](https://github.com/craftcms/cms/pull/11447))
- Fixed a bug where element selection condition rules weren’t working if an element ID was provided. ([#11451](https://github.com/craftcms/cms/pull/11451))
- Fixed a PHP error that occurred when executing a GraphQL query using a token that wasn’t set to a schema. ([#11453](https://github.com/craftcms/cms/issues/11453))
- Fixed a PHP error that could occur when unserializing a `craft\validator\DateTimeValidator`, `LanguageValidator`, `StringValidator`, or `TimeValidator` object. ([#11454](https://github.com/craftcms/cms/issues/11454))
- Fixed a bug where element types’ `actions()` methods were getting called for all `element-indexes/*` action requests.
- Fixed a bug where the `install` command would run non-interactively even if not all needed options were passed, resulting in an error after the database tables had been added. ([#11305](https://github.com/craftcms/cms/issues/11305))
- Fixed a viewport clipping bug on the control panel’s Login page. ([#11372](https://github.com/craftcms/cms/pull/11372))
- Fixed a bug where filtering an element query by a relational field using `:empty:`/`:notempty:` wasn’t factoring in the field’s “Which site should entries be related from?” setting properly.
- Fixed a bug where filtering an element query by a relational field using `:empty:`/`:notempty:` wasn’t factoring in the source elements’ site IDs, for fields set to manage relations on a per-site basis. ([#11418](https://github.com/craftcms/cms/issues/11418))
- Fixed a bug where the Temporary Uploads asset source wasn’t including subfolders.
- Fixed a bug where file upload progress bars weren’t always going away when an upload error occurred.
- Fixed a bug where Pashto was not being treated as an RTL langauge. ([#11428](https://github.com/craftcms/cms/issues/11428))
- Fixed a bug where the `upscaleImages` config setting wasn’t being respected for transforms where only a single image dimension was specified. ([#11398](https://github.com/craftcms/cms/issues/11398))
- Fixed an error that could occur when executing a GraphQL query, if a section didn’t have any entry types. ([#11273](https://github.com/craftcms/cms/issues/11273))
- Fixed an error that could occur when changing the primary site on installs with a large number of users. ([#11459](https://github.com/craftcms/cms/issues/11459))
- Fixed a bug where Assets fields within Vizy fields weren’t getting relocated from the user’s temp uploads folder. ([#11462](https://github.com/craftcms/cms/issues/11462))

### Security
- Environment-aware control panel fields no longer suggest environment variables that begin with `HTTP_`.
- The Sendmail mailer no longer validates if the Sendmail Command setting is set to an enivornment variable that begins with `HTTP_`.

## 4.0.4 - 2022-06-03

### Added
- Added support for querying for users with a `credentialed` status.
- Added `craft\elements\db\UserQuery::STATUS_CREDENTIALED`.
- Added `craft\errors\FieldNotFoundException`.
- Added `craft\helpers\Html::encodeSpaces()`.
- Added `craft\web\twig\variables\Cp::getRequestedSite()`. ([#11082](https://github.com/craftcms/cms/discussions/11082))

### Changed
- `temp` is now a reserved volume handle.
- Improved the performance of field layout designers. ([#11298](https://github.com/craftcms/cms/issues/11298))
- All control panel pages now have a `site--<siteHandle>` class name on the `<body>`, based on the currently-selected site. ([#11303](https://github.com/craftcms/cms/discussions/11303))
- Warnings are no longer logged when instantiating a field layout that references a deleted custom field. ([#11333](https://github.com/craftcms/cms/issues/11333))
- Read/write splitting is now disabled for all console requests.
- The `db/restore` command now prompts to clear data caches after the import is complete. ([#11327](https://github.com/craftcms/cms/issues/11327))
- Entry queries no longer factor in seconds when looking for currently-live entries, without excluding entries that were published in the past minute. ([#5389](https://github.com/craftcms/cms/issues/5389))
- `craft\elements\Asset::getUrl()` now encodes any spaces in the URL as `%20` entities.

### Fixed
- Fixed a bug where it wasn’t possible to disable all table columns for an element source. ([#11291](https://github.com/craftcms/cms/issues/11291))
- Fixed a bug where the Assets index page wasn’t allowing any bulk actions for assets in the temporary volume. ([#11293](https://github.com/craftcms/cms/issues/11293))
- Fixed a bug where PHP errors thrown while rendering a template weren’t being handled properly. ([#11108](https://github.com/craftcms/cms/issues/11108))
- Fixed a bug where site status labels were inconsistent on element edit pages. ([#11307](https://github.com/craftcms/cms/issues/11307))
- Fixed a bug where addresses’ County fields were mislablled. ([#11314](https://github.com/craftcms/cms/pull/11314))
- Fixed a bug where the control panel’s login form wasn’t handling errors properly. ([#11319](https://github.com/craftcms/cms/pull/11319))
- Fixed a bug where it wasn’t possible to use a `{% redirect %}` tag in an error template. ([#11336](https://github.com/craftcms/cms/issues/11336))
- Fixed an error that occurred when saving an entry via a GraphQL mutation. ([#11312](https://github.com/craftcms/cms/issues/11312))
- Fixed a bug where all web requests were getting no-cache headers. ([#11346](https://github.com/craftcms/cms/issues/11346))
- Fixed a bug where user caches weren’t getting invalidated when users were changed to a pending or inactive state.
- Fixed a bug where querying for users with an `active` status was returning suspended users. ([#11370](https://github.com/craftcms/cms/pull/11370))
- Fixed a bug where it wasn’t possible to drag assets within Assets fields by their thumbnails. ([#11364](https://github.com/craftcms/cms/issues/11364))
- Fixed a bug where asset thumbnails weren’t loading if their filename contained a space. ([#11350](https://github.com/craftcms/cms/issues/11350))
- Fixed a bug where `craft\services\AssetIndexer::indexFile()` wasn’t removing the filename from the file path when setting the directory on the listing. ([#11365](https://github.com/craftcms/cms/issues/11365))
- Fixed a bug where links within custom field instructions were getting mangled. ([#11377](https://github.com/craftcms/cms/issues/11377))
- Fixed a bug where project config paths that contained slashes weren’t getting handled properly. ([#10774](https://github.com/craftcms/cms/issues/10774))
- Fixed a bug where the Login page had a tab-focusable “Skip to content” button. ([#11375](https://github.com/craftcms/cms/issues/11375))

## 4.0.3 - 2022-05-20

### Added
- Added `craft\elements\db\ElementQuery::prepareSubquery()`.

### Changed
- Element edit pages now disable pointer events on the content container for 300 milliseconds after the “Showing your unsaved changes” notice is displayed. ([#11229](https://github.com/craftcms/cms/issues/11229))
- Users can now create drafts for entries they have permission to view, but not save. ([#11249](https://github.com/craftcms/cms/issues/11249))
- User Group condition rules are no longer available in element conditions when no user groups exist. ([#11252](https://github.com/craftcms/cms/issues/11252))
- Matrix blocks now have `data-type-name` attributes. ([#11286](https://github.com/craftcms/cms/pull/11286))
- Reversed the order of Lightswitch fields’ “ON Label” and “OFF Label” settings. ([#11259](https://github.com/craftcms/cms/issues/11259))
- `craft\services\Elements::duplicateElement()` now has a `$trackDuplication` argument.
- `craft\services\Matrix::duplicateBlocks()` now has a `$trackDuplications` argument.

### Fixed
- Fixed a bug where dynamically-defined image transforms weren’t respecting the `format` param, unless the `generateTransformsBeforePageLoad` config setting was enabled.
- Fixed a bug where Table fields with Min Rows and Max Rows set to `1` were still showing a delete button. ([#11211](https://github.com/craftcms/cms/issues/11211))
- Fixed an error that could occur when saving an Assets field that was restricted to a single location, at the root of a volume. ([#11212](https://github.com/craftcms/cms/issues/11212))
- Fixed an error that could occur after a queue job execution had finished. ([#11213](https://github.com/craftcms/cms/issues/11213))
- Fixed an error that could occur when saving an entry with Matrix blocks. ([#11155](https://github.com/craftcms/cms/issues/11155))
- Fixed an error that occurred when saving a GraphQL schema without a scope. ([#11240](https://github.com/craftcms/cms/issues/11240))
- Fixed an error that could occur when editing the public GraphQL schema, if a public token existed in the project config, but not the database. ([#11218](https://github.com/craftcms/cms/issues/11218))
- Fixed some bugs with inconsistent asset indexing on Windows. ([#11174](https://github.com/craftcms/cms/issues/11174)), ([#11219](https://github.com/craftcms/cms/issues/11219))
- Fixed a bug where custom fields weren’t available to be included as table attributes. ([#11222](https://github.com/craftcms/cms/issues/11222))
- Fixed a bug where Alternative Text wasn’t available to be included as a table attribute. ([#11222](https://github.com/craftcms/cms/issues/11222))`immediately`
- Fixed a JavaScript error that broke Matrix fields with Min Blocks and Max Blocks both set to `1`. ([#11233](https://github.com/craftcms/cms/issues/11233))
- Fixed a bug where request context logs could appear when nothing else was logged. ([#11141](https://github.com/craftcms/cms/issues/11141))
- Fixed a bug where stack traces could be erroneously filtered from logs.
- Fixed a bug where removing an element from a relational field within an element editor could cause the editor to create a provisional draft, even if the element type didn’t support drafts. ([#11242](https://github.com/craftcms/cms/issues/11242))
- Fixed a bug where draft editor pages had two identical “Save and continue editing” alternate form actions.
- Fixed a JavaScript warning that occurred when viewing an element edit page, if the user didn’t have permission to edit it.
- Fixed a bug where asset selector modals weren’t fully initializing for Assets fields, if they were targeting the user’s temp folder. ([#11254](https://github.com/craftcms/cms/issues/11254))
- Fixed an error that occurred when saving an entry via a GraphQL mutation. ([#11258](https://github.com/craftcms/cms/issues/11258))
- Fixed a bug where Matrix block types’ layout elements were getting new UUIDs assigned each time the Matrix field was edited. ([#11248](https://github.com/craftcms/cms/issues/11248))
- Fixed a bug where the web-based installation wizard was throwing an exception if a database connection couldn’t be established, and there was no `config/db.php` file. ([#11245](https://github.com/craftcms/cms/issues/11245))
- Fixed a bug where editable tables’ delete buttons’ `aria-label` attributes weren’t getting updated when table rows were reordered or deleted.
- Fixed a bug where editable tables’ delete buttons weren’t visually disabled when the minimum number of rows had been reached.
- Fixed a bug where all eager-loaded `srcset`-style transform sizes were relative to the first eager-loaded transform, rather than the prior one. ([#11209](https://github.com/craftcms/cms/issues/11209))
- Fixed a bug where eager-loaded `srcset`-style transform sizes didn’t reference the prior eager-loaded transform’s `format`, `interlace`, `mode`, `position`, or `quality` settings. ([#11264](https://github.com/craftcms/cms/issues/11264))
- Fixed a bug where the web-based installation wizard wouldn’t show the database connection screen if a connection could be established but no database was selected. ([#11245](https://github.com/craftcms/cms/issues/11245))
- Fixed an error that could occur when applying a multi-site draft with relational fields. ([#11220](https://github.com/craftcms/cms/issues/11220))
- Fixed a bug where Matrix blocks could be deleted from newly-created multi-site entries, if the edit page was reloaded. ([#10906](https://github.com/craftcms/cms/issues/10906))

## 4.0.2 - 2022-05-11

### Added
- Added `craft\events\LocateUploadedFilesEvent`.
- Added `craft\fields\Assets::EVENT_LOCATE_UPLOADED_FILES`. ([#11123](https://github.com/craftcms/cms/discussions/11123))

### Changed
- `elements/*` actions no longer include custom field values in the response data, improving performance.
- Garnish menu buttons are now aware of the `disabled` attribute. ([#11128](https://github.com/craftcms/cms/issues/11128))
- Improved save performance for multi-site elements ([#11113](https://github.com/craftcms/cms/issues/11113))
- Built-in Composer actions now ensure that `composer.json` allows the `craftcms/plugin-installer` Composer plugin.

### Fixed
- Fixed an error that occurred when searching for elements by a custom field. ([#11120](https://github.com/craftcms/cms/pull/11120))
- Fixed a bug where asset upload failures weren’t being handled properly. ([#11156](https://github.com/craftcms/cms/issues/11156))
- Fixed a bug where warning and error logs were being sent to both `stdout` and `stderr` when `CRAFT_STREAM_LOG` was enabled. ([#11189](https://github.com/craftcms/cms/issues/11189))
- Fixed an error that occurred when exporting elements with relational fields using the “Expanded” export type. ([#11127](https://github.com/craftcms/cms/issues/11127))
- Fixed a PHP deprecation warning that occurred if the `tablePrefix` database connection setting was `null`.
- Fixed a bug where category groups were being identified as “{name}” in user permission lists. ([#11132](https://github.com/craftcms/cms/issues/11132))
- Fixed a bug where Assets fields’ “Upload files” buttons weren’t wrapping when there wasn’t enough space to show them alongside “Add an asset”. ([#11133](https://github.com/craftcms/cms/issues/11133))
- Fixed a bug where `Craft.getUrl()` was appending empty query strings to URLs when they weren’t needed. ([#11159](https://github.com/craftcms/cms/issues/11159))
- Fixed a bug where addresses could validate the wrong set of required fields, if the validation rules were invoked before the country code was set. ([#11162](https://github.com/craftcms/cms/issues/11162))
- Fixed an error that could occur when viewing the Temporary Uploads volume, if the Temp Uploads Location asset setting was set to “In the local temp folder”. ([#11192](https://github.com/craftcms/cms/issues/11192))
- Fixed an error that could occur when deleting a volume, if its filesystem had been deleted.
- Fixed an error that could occur when rendering the thumbnail preview for an asset, if its file was missing. ([#11196](https://github.com/craftcms/cms/issues/11196))
- Fixed a bug where soft-deleted drafts’ search keywords weren’t getting re-indexed if they were restored.
- Fixed an error that occurred when transforming an SVG image without specifying a width or height. ([#11122](https://github.com/craftcms/cms/issues/11122))
- Fixed an error that occurred when saving a Number field with a non-numeric value. ([#11164](https://github.com/craftcms/cms/issues/11164))
- Fixed a bug where it wasn’t possible to drag an item to the top in admin tables. ([#10781](https://github.com/craftcms/cms/issues/10781))
- Fixed a bug where entries within Structure sections weren’t expandable if their only descendants were unpublished drafts.
- Fixed a bug where expanding a collapsed Structure section entry wouldn’t reveal its descendants, if the parent was a draft. ([#11186](https://github.com/craftcms/cms/issues/11186))
- Fixed a bug where element caches weren’t getting cleared for elements when they were propagated to a newly-created site.

## 4.0.1 - 2022-05-06

### Fixed
- Fixed a bug where Money field labels’ `for` attributes weren’t referencing the correct input ID. ([#11016](https://github.com/craftcms/cms/pull/11016))
- Fixed a bug where Money field inputs weren’t getting `aria-describedby` attributes. ([#11016](https://github.com/craftcms/cms/pull/11016))
- Fixed an error that occurred when loading an edit screen for an element type that didn’t have a field layout. ([#11110](https://github.com/craftcms/cms/pull/11110))
- Fixed a bug where condition rules that weren’t selectable (per `isSelectable()`) were still visible in the rule dropdown menu. ([#11104](https://github.com/craftcms/cms/pull/11104))
- Fixed a bug where element edit pages could reload themselves immediately after saving the element. ([#11084](https://github.com/craftcms/cms/issues/11084))
- Fixed a bug where tabs weren’t interactive after changing an entry’s type, if the new entry type didn’t have a tab of the same name as the previously-selected tab. ([#11093](https://github.com/craftcms/cms/issues/11093))
- Fixed a bug where Twig syntax errors weren’t being handled properly. ([#11108](https://github.com/craftcms/cms/issues/11108))
- Fixed an error that occurred when attempting to delete a global set. ([#11100](https://github.com/craftcms/cms/issues/11100))
- Fixed an error that could occur when applying a draft. ([#11083](https://github.com/craftcms/cms/issues/11083))
- Fixed a bug where element queries weren’t returning any results if an element attribute table wasn’t joined in, and the element query was set to an abstract element class. ([#11105](https://github.com/craftcms/cms/issues/11105))

## 4.0.0.1 - 2022-05-04

### Changed
- The `setup` command now writes the application ID to a `CRAFT_APP_ID` environment variable.
- The `setup` command now writes the security key to a `CRAFT_SECURITY_KEY` environment variable.

## 4.0.0 - 2022-05-04

### Added
- Entries’, categories’, and assets’ edit pages, and all element types via slideouts, now use a unified editing experience. ([#10467](https://github.com/craftcms/cms/pull/10467))
- Categories now support drafts. ([#10467](https://github.com/craftcms/cms/pull/10467))
- Element slideouts now support provisional drafts and autosaving, for element types that support them. ([#10467](https://github.com/craftcms/cms/pull/10467))
- Element indexes can now be filtered by element attributes and custom field values. ([#9192](https://github.com/craftcms/cms/discussions/9192), [#9450](https://github.com/craftcms/cms/discussions/9450), [#9462](https://github.com/craftcms/cms/discussions/9462), [#9483](https://github.com/craftcms/cms/discussions/9483))
- Admins can now create custom element sources from the Customize Sources modal. ([#8423](https://github.com/craftcms/cms/discussions/8423))
- It’s now possible to disable native element sources from the Customize Sources modal. ([#10676](https://github.com/craftcms/cms/discussions/10676))
- Field layout tabs, fields, and UI elements can now be conditionally shown based on properties of the current user and/or element being edited. ([#8099](https://github.com/craftcms/cms/discussions/8099), [#8154](https://github.com/craftcms/cms/discussions/8154))
- Assets, Entries, and Users fields have new condition settings that can be used to further limit which elements should be relatable, beyond the existing field settings. ([#10393](https://github.com/craftcms/cms/pull/10393))
- Assets, Entries, and Users fields have new “Min Relations” settings, and their former “Limit” settings have been renamed to “Max Relations”. ([#8621](https://github.com/craftcms/cms/discussions/8621))
- Added a dedicated “Full Name” field to users. “First Name” and “Last Name” are now parsed out from the full name automatically when a user is saved. ([#10405](https://github.com/craftcms/cms/discussions/10405))
- Added the “Inactive” user status, which can be used by users which can’t be signed into. ([#8963](https://github.com/craftcms/cms/discussions/8963))
- Added “Credentialed” and “Inactive” user sources.
- Added the “Deactivate…” user action for pending and active users.
- Users can now have an “Addresses” field. ([#10507](https://github.com/craftcms/cms/pull/10507))
- Added the concept of “filesystems”, which handle file operations, either locally or on a remote service like Amazon S3.
- It’s now possible to set sites’ Status settings to environment variables. ([#3005](https://github.com/craftcms/cms/issues/3005))
- Added the Money field type.
- Craft now provides a native “Alternative Text” (`alt`) field for assets. ([#10302](https://github.com/craftcms/cms/discussions/10302))
- Asset thumbnails in the control panel now have `alt` attributes, for assets with a filled-in Alternative Text value.
- Added the `index-assets/cleanup` command.
- Added the “Deactivate users by default” user registration setting, which replaces “Suspend users by default”. ([#5830](https://github.com/craftcms/cms/issues/5830))
- Element source settings are now stored in the project config. ([#8616](https://github.com/craftcms/cms/discussions/8616))
- Improved element index accessibility. ([#10629](https://github.com/craftcms/cms/pull/10629), [#10660](https://github.com/craftcms/cms/pull/10660))
- Improved Live Preview accessibility for screen readers. ([#10688](https://github.com/craftcms/cms/pull/10688))
- Slideouts, Live Preview, and Matrix blocks are no longer animated for browsers that have requested reduced motion. ([#10665](https://github.com/craftcms/cms/pull/10665))
- Added support for `JSON` columns. ([#9089](https://github.com/craftcms/cms/pull/9089))
- It’s now possible to edit images’ focal points from their preview modals. ([#8489](https://github.com/craftcms/cms/discussions/8489))
- Added support for Monolog and the PSR-3 logging interface. ([#10659](https://github.com/craftcms/cms/pull/10659))
- Added the `|address` Twig filter.
- Added the `|money` Twig filter.
- Added the `collect()` Twig function.
- Added the `assetUploaders`, `authors`, and `fullName` user query params.
- Added the `primaryOwner` and `primaryOwnerId` Matrix block query params.
- Added the `hasAlt` asset query param.
- Added the `button`, `submitButton`, `fs`, `fsField`, `volume`, and `volumeField` macros to the `_includes/forms` control panel template.
- Added the `buildId` general config. ([#10705](https://github.com/craftcms/cms/pull/10705))
- Added support for setting custom config settings from `config/custom.php`, which are accessible via `Craft::$app->config->custom`. ([#10012](https://github.com/craftcms/cms/issues/10012))
- Added the `addresses`, `address`, and `addressCount` GraphQL queries.
- Added the `hasAlt` argument to asset GraphQL queries.
- Added the `alt` field to assets queried via GraphQL.
- Added the `fullName`, `assetUploaders`, and `authors` arguments to user GraphQL queries.
- Added the `addresses` field to user GraphQL queries.
- GraphQL schemas now include settings that determine which sites elements can be queried from. ([#10610](https://github.com/craftcms/cms/issues/10610))
- Added the `assets/icon` action.
- Added the `assets/update-focal-point` action.
- Added the `categories/create` action.
- Added the `elements/apply-draft` action.
- Added the `elements/create` action.
- Added the `elements/delete-draft` action.
- Added the `elements/delete-for-site` action.
- Added the `elements/delete` action.
- Added the `elements/duplicate` action.
- Added the `elements/edit` action.
- Added the `elements/redirect` action.
- Added the `elements/revert` action.
- Added the `elements/save-draft` action.
- Added the `elements/save` action.
- Added the `users/delete-address` action.
- Added the `users/save-address` action.
- Added the `app/render-element` control panel controller action.
- Added the `element-indexes/element-table-html` control panel controller action.
- Added `craft\base\ApplicationTrait::getConditions()`.
- Added `craft\base\ApplicationTrait::getElementSources()`, which replaces `getElementIndexes()`.
- Added `craft\base\ApplicationTrait::getFs()`.
- Added `craft\base\ApplicationTrait::getImageTransforms()`, which replaces `getAssetTransforms()`.
- Added `craft\base\conditions\BaseCondition`.
- Added `craft\base\conditions\BaseConditionRule`.
- Added `craft\base\conditions\BaseDateRangeConditionRule`.
- Added `craft\base\conditions\BaseElementSelectConditionRule`.
- Added `craft\base\conditions\BaseLightswitchConditionRule`.
- Added `craft\base\conditions\BaseMultiSelectConditionRule`.
- Added `craft\base\conditions\BaseNumberConditionRule`.
- Added `craft\base\conditions\BaseSelectConditionRule`.
- Added `craft\base\conditions\BaseTextConditionRule`.
- Added `craft\base\conditions\ConditionInterface`.
- Added `craft\base\conditions\ConditionRuleInterface`.
- Added `craft\base\Element::EVENT_AUTHORIZE_CREATE_DRAFTS`.
- Added `craft\base\Element::EVENT_AUTHORIZE_DELETE_FOR_SITE`.
- Added `craft\base\Element::EVENT_AUTHORIZE_DELETE`.
- Added `craft\base\Element::EVENT_AUTHORIZE_DUPLICATE`.
- Added `craft\base\Element::EVENT_AUTHORIZE_SAVE`.
- Added `craft\base\Element::EVENT_AUTHORIZE_VIEW`.
- Added `craft\base\Element::EVENT_DEFINE_ADDITIONAL_BUTTONS`. ([#10420](https://github.com/craftcms/cms/discussions/10420))
- Added `craft\base\Element::getParentId()`.
- Added `craft\base\Element::hasNewParent()`.
- Added `craft\base\Element::notesFieldHtml()`.
- Added `craft\base\Element::setParentId()`.
- Added `craft\base\Element::statusFieldHtml()`.
- Added `craft\base\ElementInterface::canCreateDrafts()`.
- Added `craft\base\ElementInterface::canDelete()`.
- Added `craft\base\ElementInterface::canDeleteForSite()`.
- Added `craft\base\ElementInterface::canDuplicate()`.
- Added `craft\base\ElementInterface::canSave()`.
- Added `craft\base\ElementInterface::canView()`.
- Added `craft\base\ElementInterface::createAnother()`.
- Added `craft\base\ElementInterface::createCondition()`.
- Added `craft\base\ElementInterface::getAdditionalButtons()`.
- Added `craft\base\ElementInterface::getPostEditUrl()`.
- Added `craft\base\ElementInterface::getThumbAlt()`.
- Added `craft\base\ElementInterface::hasRevisions()`.
- Added `craft\base\ElementInterface::prepareEditScreen()`.
- Added `craft\base\FieldInterface::getElementConditionRuleType()`.
- Added `craft\base\FieldInterface::isRequirable()`.
- Added `craft\base\FieldLayoutComponent`.
- Added `craft\base\Fs`.
- Added `craft\base\FsInterface`.
- Added `craft\base\FsTrait`.
- Added `craft\base\Image::heartbeat()`.
- Added `craft\base\Image::setHeartbeatCallback()`.
- Added `craft\base\imagetransforms\EagerImageTransformerInterface`.
- Added `craft\base\imagetransforms\ImageEditorTransformerInterface`.
- Added `craft\base\imagetransforms\ImageTransformerInterface`.
- Added `craft\base\LocalFsInterface`.
- Added `craft\base\Model::defineBehaviors()`. ([#10691](https://github.com/craftcms/cms/pull/10691))
- Added `craft\base\ModelInterface`.
- Added `craft\base\NameTrait`.
- Added `craft\base\PluginInterface::config()`. ([#11039](https://github.com/craftcms/cms/pull/11039))
- Added `craft\behaviors\SessionBehavior::broadcastToJs()`.
- Added `craft\behaviors\SessionBehavior::getError()`.
- Added `craft\behaviors\SessionBehavior::getNotice()`.
- Added `craft\controllers\AddressesController`.
- Added `craft\controllers\AssetIndexesController`.
- Added `craft\controllers\ConditionsController`.
- Added `craft\controllers\ElementIndexesController::$condition`.
- Added `craft\controllers\FsController`.
- Added `craft\controllers\ImageTransformsController`.
- Added `craft\db\Migration::archiveTableIfExists()`. ([#10827](https://github.com/craftcms/cms/discussions/10827))
- Added `craft\db\Migration::dropAllForeignKeysToTable()`.
- Added `craft\db\Migration::dropForeignKeyIfExists()`.
- Added `craft\db\Migration::renameTable()`.
- Added `craft\db\Query::collect()`, which returns the query results as an `Illuminate\Support\Collection` object rather than an array. ([#8513](https://github.com/craftcms/cms/discussions/8513))
- Added `craft\db\Table::ADDRESSES`.
- Added `craft\db\Table::ASSETINDEXINGSESSIONS`.
- Added `craft\db\Table::IMAGETRANSFORMINDEX`.
- Added `craft\db\Table::IMAGETRANSFORMS`.
- Added `craft\db\Table::MATRIXBLOCKS_OWNERS`.
- Added `craft\debug\LogTarget`.
- Added `craft\debug\MailPanel`.
- Added `craft\elements\Address`.
- Added `craft\elements\Asset::$alt`.
- Added `craft\elements\Asset::EVENT_AFTER_GENERATE_TRANSFORM`.
- Added `craft\elements\Asset::EVENT_BEFORE_GENERATE_TRANSFORM`.
- Added `craft\elements\Asset::getFs()`.
- Added `craft\elements\Asset::setFilename()`.
- Added `craft\elements\conditions\addresses\AddressCondition`.
- Added `craft\elements\conditions\addresses\CountryConditionRule`.
- Added `craft\elements\conditions\assets\AssetCondition`.
- Added `craft\elements\conditions\assets\DateModifiedConditionRule`.
- Added `craft\elements\conditions\assets\FilenameConditionRule`.
- Added `craft\elements\conditions\assets\FileSizeConditionRule`.
- Added `craft\elements\conditions\assets\FileTypeConditionRule`.
- Added `craft\elements\conditions\assets\HasAltConditionRule`.
- Added `craft\elements\conditions\assets\HeightConditionRule`.
- Added `craft\elements\conditions\assets\UploaderConditionRule`.
- Added `craft\elements\conditions\assets\VolumeConditionRule`.
- Added `craft\elements\conditions\assets\WidthConditionRule`.
- Added `craft\elements\conditions\categories\CategoryCondition`.
- Added `craft\elements\conditions\categories\GroupConditionRule`.
- Added `craft\elements\conditions\DateCreatedConditionRule`.
- Added `craft\elements\conditions\DateUpdatedConditionRule`.
- Added `craft\elements\conditions\ElementCondition`.
- Added `craft\elements\conditions\ElementConditionInterface`.
- Added `craft\elements\conditions\ElementConditionRuleInterface`.
- Added `craft\elements\conditions\entries\AuthorConditionRule`.
- Added `craft\elements\conditions\entries\AuthorGroupConditionRule`.
- Added `craft\elements\conditions\entries\EntryCondition`.
- Added `craft\elements\conditions\entries\ExpiryDateConditionRule`.
- Added `craft\elements\conditions\entries\PostDateConditionRule`.
- Added `craft\elements\conditions\entries\SectionConditionRule`.
- Added `craft\elements\conditions\entries\TypeConditionRule`.
- Added `craft\elements\conditions\HasUrlConditionRule`.
- Added `craft\elements\conditions\IdConditionRule`.
- Added `craft\elements\conditions\LevelConditionRule`.
- Added `craft\elements\conditions\RelatedToConditionRule`.
- Added `craft\elements\conditions\SlugConditionRule`.
- Added `craft\elements\conditions\tags\GroupConditionRule`.
- Added `craft\elements\conditions\tags\TagCondition`.
- Added `craft\elements\conditions\TitleConditionRule`.
- Added `craft\elements\conditions\UriConditionRule`.
- Added `craft\elements\conditions\users\AdminConditionRule`.
- Added `craft\elements\conditions\users\CredentialedConditionRule`.
- Added `craft\elements\conditions\users\EmailConditionRule`.
- Added `craft\elements\conditions\users\FirstNameConditionRule`.
- Added `craft\elements\conditions\users\GroupConditionRule`.
- Added `craft\elements\conditions\users\LastLoginDateConditionRule`.
- Added `craft\elements\conditions\users\LastNameConditionRule`.
- Added `craft\elements\conditions\users\UserCondition`.
- Added `craft\elements\conditions\users\UsernameConditionRule`.
- Added `craft\elements\db\AddressQuery`.
- Added `craft\elements\MatrixBlock::$primaryOwnerId`.
- Added `craft\elements\MatrixBlock::$saveOwnership`.
- Added `craft\elements\User::$active`.
- Added `craft\elements\User::$fullName`.
- Added `craft\elements\User::canAssignUserGroups()`.
- Added `craft\elements\User::getAddresses()`.
- Added `craft\elements\User::getIsCredentialed()`.
- Added `craft\elements\User::STATUS_INACTIVE`.
- Added `craft\errors\FsException`.
- Added `craft\errors\FsObjectExistsException`.
- Added `craft\errors\FsObjectNotFoundException`.
- Added `craft\errors\ImageTransformException`.
- Added `craft\errors\InvalidFsException`.
- Added `craft\errors\MissingVolumeFolderException`.
- Added `craft\events\AuthorizationCheckEvent`.
- Added `craft\events\CreateElementCheckEvent`.
- Added `craft\events\DefineElementEditorHtmlEvent`.
- Added `craft\events\DefineElementInnerHtmlEvent`. ([#11035](https://github.com/craftcms/cms/pull/11035))
- Added `craft\events\DefineHtmlEvent::$static`.
- Added `craft\events\FsEvent`.
- Added `craft\events\GenerateTransformEvent::$asset`.
- Added `craft\events\GenerateTransformEvent::$transform`.
- Added `craft\events\GenerateTransformEvent::$url`.
- Added `craft\events\ImageTransformerOperationEvent`.
- Added `craft\events\ImageTransformEvent`.
- Added `craft\events\RegisterConditionRuleTypesEvent`.
- Added `craft\events\TransformImageEvent`.
- Added `craft\fieldlayoutelements\addresses\AddressField`.
- Added `craft\fieldlayoutelements\addresses\CountryCodeField`.
- Added `craft\fieldlayoutelements\addresses\LabelField`.
- Added `craft\fieldlayoutelements\addresses\LatLongField`.
- Added `craft\fieldlayoutelements\addresses\OrganizationField`.
- Added `craft\fieldlayoutelements\addresses\OrganizationTaxIdField`.
- Added `craft\fieldlayoutelements\assets\AltField`.
- Added `craft\fieldlayoutelements\BaseField::selectorLabel()`.
- Added `craft\fieldlayoutelements\FullNameField`.
- Added `craft\fieldlayoutelements\TextareaField`.
- Added `craft\fieldlayoutelements\users\AddressesField`.
- Added `craft\fields\Assets::$allowSubfolders`.
- Added `craft\fields\Assets::$restrictedDefaulUploadSubpath`.
- Added `craft\fields\BaseRelationField::createSelectionCondition()`.
- Added `craft\fields\BaseRelationField::getSelectionCondition()`.
- Added `craft\fields\BaseRelationField::setSelectionCondition()`.
- Added `craft\fields\conditions\DateFieldConditionRule`.
- Added `craft\fields\conditions\FieldConditionRuleInterface`.
- Added `craft\fields\conditions\FieldConditionRuleTrait`.
- Added `craft\fields\conditions\LightswitchFieldConditionRule`.
- Added `craft\fields\conditions\NumberFieldConditionRule`.
- Added `craft\fields\conditions\OptionsFieldConditionRule`.
- Added `craft\fields\conditions\RelationalFieldConditionRule`.
- Added `craft\fields\conditions\TextFieldConditionRule`.
- Added `craft\fields\Money`.
- Added `craft\fs\Local`.
- Added `craft\fs\MissingFs`.
- Added `craft\fs\Temp`.
- Added `craft\gql\arguments\elements\Address`.
- Added `craft\gql\base\SingularTypeInterface`.
- Added `craft\gql\interfaces\elements\Address`.
- Added `craft\gql\queries\Address`.
- Added `craft\gql\resolvers\elements\Address`.
- Added `craft\gql\TypeManager::registerFieldDefinitions()`.
- Added `craft\gql\types\elements\Address`.
- Added `craft\gql\types\generators\AddressType`.
- Added `craft\helpers\App::cliOption()`.
- Added `craft\helpers\App::devMode()`.
- Added `craft\helpers\App::envConfig()`. ([#10869](https://github.com/craftcms/cms/pull/10869))
- Added `craft\helpers\App::isStreamLog()`.
- Added `craft\helpers\App::normalizeValue()`.
- Added `craft\helpers\Assets::downloadFile()`.
- Added `craft\helpers\Assets::iconPath()`.
- Added `craft\helpers\Assets::iconUrl()`.
- Added `craft\helpers\Assets::revParams()`.
- Added `craft\helpers\Cp::addressCardHtml()`.
- Added `craft\helpers\Cp::addressCardsHtml()`.
- Added `craft\helpers\Cp::addressFieldsHtml()`.
- Added `craft\helpers\Cp::dateFieldHtml()`.
- Added `craft\helpers\Cp::dateHtml()`.
- Added `craft\helpers\Cp::elementSelectHtml()`.
- Added `craft\helpers\Cp::EVENT_DEFINE_ELEMENT_INNER_HTML`. ([#11035](https://github.com/craftcms/cms/pull/11035))
- Added `craft\helpers\Cp::fieldLayoutDesignerHtml()`.
- Added `craft\helpers\Cp::lightswitchHtml()`.
- Added `craft\helpers\Cp::multiSelectFieldHtml()`.
- Added `craft\helpers\Cp::multiSelectHtml()`.
- Added `craft\helpers\Cp::requestedSite()`.
- Added `craft\helpers\Cp::textareaHtml()`.
- Added `craft\helpers\Cp::textHtml()`.
- Added `craft\helpers\Cp::timeFieldHtml()`.
- Added `craft\helpers\Cp::timeHtml()`.
- Added `craft\helpers\Db::dropAllForeignKeysToTable()`.
- Added `craft\helpers\Db::dropForeignKeyIfExists()`.
- Added `craft\helpers\Db::dropIndexIfExists()`.
- Added `craft\helpers\Db::findForeignKey()`.
- Added `craft\helpers\Db::findIndex()`.
- Added `craft\helpers\Db::parseMoneyParam()`.
- Added `craft\helpers\Db::parseNumericParam()`.
- Added `craft\helpers\Db::prepareMoneyForDb()`.
- Added `craft\helpers\Db::renameTable()`.
- Added `craft\helpers\FileHelper::deleteFileAfterRequest()`.
- Added `craft\helpers\FileHelper::deleteQueuedFiles()`.
- Added `craft\helpers\Gql::getSchemaContainedEntryTypes)()`.
- Added `craft\helpers\Html::hiddenLabel()`.
- Added `craft\helpers\Html::unwrapCondition()`.
- Added `craft\helpers\Html::unwrapNoscript()`.
- Added `craft\helpers\ImageTransforms`.
- Added `craft\helpers\Money`.
- Added `craft\helpers\Number::isInt()`.
- Added `craft\helpers\Number::toIntOrFloat()`.
- Added `craft\helpers\ProjectConfig::encodeValueAsString()`.
- Added `craft\helpers\ProjectConfig::ensureAllSectionsProcessed()`.
- Added `craft\helpers\ProjectConfig::traverseDataArray()`.
- Added `craft\helpers\Typecast`. ([#10706](https://github.com/craftcms/cms/pull/10706))
- Added `craft\i18n\Translation`.
- Added `craft\imagetransforms\ImageTransformer`.
- Added `craft\log\ContextProcessor`.
- Added `craft\log\Dispatcher::getTargets()`.
- Added `craft\log\MessageProcessor`.
- Added `craft\log\MonologTarget`.
- Added `craft\models\AssetIndexingSession`.
- Added `craft\models\FieldLayout::getElementsByType()`.
- Added `craft\models\FieldLayout::getFirstElementByType()`.
- Added `craft\models\FieldLayout::getFirstVisibleElementByType()`.
- Added `craft\models\FieldLayout::getVisibleCustomFields()`.
- Added `craft\models\FieldLayout::getVisibleElementsByType()`.
- Added `craft\models\FieldLayoutElement::$uid`.
- Added `craft\models\FieldLayoutElement::getLayout()` and `setLayout()`.
- Added `craft\models\FieldLayoutForm::getVisibleElements()`.
- Added `craft\models\FieldLayoutFormTab::getTabId()`.
- Added `craft\models\FieldLayoutFormTab::getUid()`.
- Added `craft\models\FieldLayoutTab::getElements()` and `setElements()`.
- Added `craft\models\FsListing`.
- Added `craft\models\ImageTransform`.
- Added `craft\models\ImageTransformIndex`.
- Added `craft\models\ProjectConfigData`.
- Added `craft\models\ReadOnlyProjectConfigData`.
- Added `craft\models\Volume`.
- Added `craft\queue\jobs\Proxy`.
- Added `craft\queue\Queue::$proxyQueue`, which can be set to another queue configuration that all jobs should be sent to as proxies. ([#10999](https://github.com/craftcms/cms/pull/10999))
- Added `craft\records\Address`.
- Added `craft\records\AssetIndexingSession`.
- Added `craft\records\ImageTransform`.
- Added `craft\services\Addresses`.
- Added `craft\services\AssetIndexer::createIndexingSession()`.
- Added `craft\services\AssetIndexer::getExistingIndexingSessions()`.
- Added `craft\services\AssetIndexer::getIndexingSessionById()`.
- Added `craft\services\AssetIndexer::getMissingEntriesForSession()`.
- Added `craft\services\AssetIndexer::getSkippedItemsForSession()`.
- Added `craft\services\AssetIndexer::indexFileByListing()`.
- Added `craft\services\AssetIndexer::indexFolderByEntry()`.
- Added `craft\services\AssetIndexer::indexFolderByListing()`.
- Added `craft\services\AssetIndexer::processIndexSession()`.
- Added `craft\services\AssetIndexer::removeCliIndexingSessions()`.
- Added `craft\services\AssetIndexer::startIndexingSession()`.
- Added `craft\services\AssetIndexer::stopIndexingSession()`.
- Added `craft\services\Assets::getImagePreviewUrl()`.
- Added `craft\services\AssetTransforms::deleteTransformIndexDataByAssetIds()`.
- Added `craft\services\Conditions`.
- Added `craft\services\Config::CATEGORY_CUSTOM`.
- Added `craft\services\Config::getCustom()`.
- Added `craft\services\Drafts::removeDraftData()`.
- Added `craft\services\ElementSources`, which replaces `craft\services\ElementIndexes`.
- Added `craft\services\Fields::createLayout()`.
- Added `craft\services\Fs`.
- Added `craft\services\Gc::hardDeleteElements()`.
- Added `craft\services\Gc::removeEmptyTempFolders()`.
- Added `craft\services\Gql::prepareFieldDefinitions()`.
- Added `craft\services\ImageTransforms`.
- Added `craft\services\Matrix::createRevisionBlocks()`.
- Added `craft\services\Matrix::duplicateOwnership()`.
- Added `craft\services\ProjectConfig::ASSOC_KEY`.
- Added `craft\services\ProjectConfig::PATH_DATE_MODIFIED`.
- Added `craft\services\ProjectConfig::PATH_ELEMENT_SOURCES`.
- Added `craft\services\ProjectConfig::PATH_FS`.
- Added `craft\services\ProjectConfig::PATH_META_NAMES`.
- Added `craft\services\ProjectConfig::PATH_SCHEMA_VERSION`.
- Added `craft\services\ProjectConfig::PATH_SYSTEM`.
- Added `craft\services\ProjectConfig::rememberAppliedChanges()`.
- Added `craft\services\Users::deactivateUser()`.
- Added `craft\services\Users::ensureUserByEmail()`, which will return a user for the given email, creating one if it didn’t exist yet.
- Added `craft\services\Users::EVENT_AFTER_DEACTIVATE_USER`.
- Added `craft\services\Users::EVENT_BEFORE_DEACTIVATE_USER`.
- Added `craft\services\Users::removeCredentials()`.
- Added `craft\services\Volumes::getTemporaryVolume()`.
- Added `craft\services\Volumes::getUserPhotoVolume()`.
- Added `craft\validators\MoneyValidator`.
- Added `craft\web\assets\conditionbuilder\ConditionBuilderAsset`.
- Added `craft\web\assets\htmx\HtmxAsset`.
- Added `craft\web\assets\money\MoneyAsset`.
- Added `craft\web\Controller::asCpScreen()`.
- Added `craft\web\Controller::asFailure()`.
- Added `craft\web\Controller::asModelFailure()`.
- Added `craft\web\Controller::asModelSuccess()`.
- Added `craft\web\Controller::asSuccess()`.
- Added `craft\web\Controller::CpScreenResponseBehavior()`.
- Added `craft\web\Controller::CpScreenResponseFormatter()`.
- Added `craft\web\Controller::getPostedRedirectUrl()`.
- Added `craft\web\Controller::TemplateResponseBehavior()`.
- Added `craft\web\Controller::TemplateResponseFormatter()`.
- Added `craft\web\twig\Extension::addressFilter()`.
- Added `craft\web\twig\Extension::moneyFilter()`.
- Added `craft\web\twig\variables\Cp::fieldLayoutDesigner()`.
- Added `craft\web\twig\variables\Cp::getFsOptions()`.
- Added `craft\web\twig\variables\Cp::getVolumeOptions()`.
- Added `craft\web\View::clearCssFileBuffer()`.
- Added `craft\web\View::clearJsFileBuffer()`.
- Added `craft\web\View::startCssFileBuffer()`.
- Added `craft\web\View::startJsFileBuffer()`.
- Added the `Craft.appendBodyHtml()` JavaScript method, which replaces the now-deprecated `appendFootHtml()` method.
- Added the `Craft.CpScreenSlideout` JavaScript class, which can be used to create slideouts from actions that return `$this->asCpScreen()`.
- Added the `Craft.ElementEditor` JavaScript class.
- Added the `Craft.ElementEditorSlideout` JavaScript class.
- Added the `Craft.getPageUrl()` JavaScript method.
- Added the `Craft.getQueryParam()` JavaScript method.
- Added the `Craft.getQueryParams()` JavaScript method.
- Added the `Craft.namespaceId()` JavaScript method.
- Added the `Craft.namespaceInputName()` JavaScript method.
- Added the `Craft.Preview.refresh()` JavaScript method.
- Added the `Craft.Queue` JavaScript class.
- Added the `Craft.setElementAttributes()` JavaScript method.
- Added the `Craft.setPath()` JavaScript method.
- Added the `Craft.setQueryParam()` JavaScript method.
- Added the `Craft.setUrl()` JavaScript method.
- Added the `Craft.ui.createButton()` JavaScript method.
- Added the `Craft.ui.createSubmitButton()` JavaScript method.
- Added the `htmx.org` JavaScript library.
- Added the commerceguys/addressing package.
- Added the illuminate/collections package. ([#8475](https://github.com/craftcms/cms/discussions/8475))
- Added the moneyphp/money package.
- Added the symfony/var-dumper package.
- Added the theiconic/name-parser package.
- Added the yiisoft/yii2-symfonymailer package.

### Changed
- Craft now requires PHP 8.0.2 or later.
- Craft now requires MySQL 5.7.8 / MariaDB 10.2.7 / PostgreSQL 10.0 or later.
- Craft now requires the [Intl](https://php.net/manual/en/book.intl.php) and [BCMath](https://www.php.net/manual/en/book.bc.php) PHP extensions.
- Improved draft creation/application performance. ([#10577](https://github.com/craftcms/cms/pull/10577))
- Improved revision creation performance. ([#10589](https://github.com/craftcms/cms/pull/10577))
- The “What’s New” HUD now displays an icon and label above each announcement, identifying where it came from (Craft CMS or a plugin). ([#9747](https://github.com/craftcms/cms/discussions/9747))
- The control panel now keeps track of the currently-edited site on a per-tab basis by adding a `site` query string param to all control panel URLs. ([#8920](https://github.com/craftcms/cms/discussions/8920))
- Element index pages’ status and sort menu option selections are now coded into the page URL via `status` and `sort` query string params. ([#10669](https://github.com/craftcms/cms/discussions/10669))
- Users are no longer required to have a username or email.
- Users can now set their Formatting Locale to any known locale; not just the available Language options. ([#10519](https://github.com/craftcms/cms/pull/10519))
- Users’ Language and Formatting Locale settings now display locale names in the current language and their native languages. ([#10519](https://github.com/craftcms/cms/pull/10519))
- User queries now return all users by default, rather than only active users.
- Filtering users by `active`, `pending`, and `locked` statuses no longer excludes suspended users.
- `credentialed` and `inactive` are now reserved user group handles.
- Elements throughout the control panel are now automatically updated whenever they’re saved by another browser tab.
- Assets fields that are restricted to a single location can now be configured to allow selection within subfolders of that location. ([#9070](https://github.com/craftcms/cms/discussions/9070))
- When an image is saved as a new asset from the Image Editor via an Assets field, the Assets field will now automatically replace the selected asset with the new one. ([#8974](https://github.com/craftcms/cms/discussions/8974))
- `alt` is now a reserved field handle for volume field layouts.
- Volumes no longer have “types”, and their file operations are now delegated to a filesystem selected by an “Asset Filesystem” setting on the volume.
- Volumes now have “Transform Filesystem” and “Transform Subpath” settings, which can be used to choose where image transforms should be stored. (The volume’s Asset Filesystem will be used by default.)
- Asset thumbnails are now generated as image transforms.
- It’s now possible to create volumes directly from the User Settings page.
- Images that are not web-safe now are always converted to JPEGs when transforming, if no format was specified.
- Entry post dates are no longer set automatically until the entry is validated with the `live` scenario. ([#10093](https://github.com/craftcms/cms/pull/10093))
- Entry queries’ `authorGroup()` param method now accepts an array of `craft\models\UserGroup` objects.
- Element queries’ `revision` params can now be set to `null` to include normal and revision elements.
- Element queries can no longer be traversed or accessed like an array. Use a query execution method such as `all()`, `collect()`, or `one()` to fetch the results before working with them.
- Element queries’ `title` params no longer treat values with commas as arrays. ([#10891](https://github.com/craftcms/cms/issues/10891))
- User queries’ `firstName` and `lastName` params no longer treat values with commas as arrays. ([#10891](https://github.com/craftcms/cms/issues/10891))
- Relational fields now load elements in the current site rather than the primary site, if the source element isn’t localizable. ([#7048](https://github.com/craftcms/cms/issues/7048))
- Lightswitch fields can no longer be marked as required within field layouts. ([#10773](https://github.com/craftcms/cms/issues/10773))
- Built-in queue jobs are now always translated for the current user’s language. ([#9745](https://github.com/craftcms/cms/pull/9745))
- Path options passed to console commands (e.g. `--basePath`) now take precedence over their enivronment variable/PHP constant counterparts.
- Database backups are now named after the Craft version in the database, rather than the Composer-installed version. ([#9733](https://github.com/craftcms/cms/discussions/9733))
- Template autosuggestions now include their filename. ([#9744](https://github.com/craftcms/cms/pull/9744))
- Improved the look of loading spinners in the control panel. ([#9109](https://github.com/craftcms/cms/discussions/9109))
- The default `subLeft` and `subRight` search query term options are now only applied to terms that don’t include an asterisk at the beginning/end, e.g. `hello*`. ([#10613](https://github.com/craftcms/cms/discussions/10613))
- `{% cache %}` tags now store any external JavaScript or CSS files registered with `{% js %}` and `{% css %}` tags. ([#9987](https://github.com/craftcms/cms/discussions/9987))
- All control panel templates end in `.twig` now. ([#9743](https://github.com/craftcms/cms/pull/9743))
- 404 requests are no longer logged by default. ([#10659](https://github.com/craftcms/cms/pull/10659))
- Log entries are now single-line by default when Dev Mode is disabled. ([#10659](https://github.com/craftcms/cms/pull/10659))
- Log files are now rotated once every 24 hours. ([#10659](https://github.com/craftcms/cms/pull/10659))
- `CRAFT_STREAM_LOG` no longer logs _in addition to_ other log targets. ([#10659](https://github.com/craftcms/cms/pull/10659))
- The default log target no longer logs `debug` or `info` messages when Dev Mode is enabled. ([#10916](https://github.com/craftcms/cms/pull/10916))
- SQL query logs now use the `debug` log level, so they no longer get logged when Dev Mode is enabled. ([#10916](https://github.com/craftcms/cms/pull/10916))
- `yii\db\Connection::$enableLogging` and `$enableProfiling` are no longer enabled by default when Dev Mode is disabled. ([#10916](https://github.com/craftcms/cms/pull/10916))
- The `queue` log target no longer has special handling for Yii or `info` logs. ([#10916](https://github.com/craftcms/cms/pull/10916))
- A warning is now logged if an element query is executed before Craft is fully initialized. ([#11033](https://github.com/craftcms/cms/issues/11033))
- A warning is now logged if Twig is instantiated before Craft is fully initialized. ([#11033](https://github.com/craftcms/cms/issues/11033))
- Craft’s bootstrap script now attempts to create its configured system paths automatically. ([#10562](https://github.com/craftcms/cms/pull/10562))
- When using GraphQL to mutate entries, the `enabled` status is now affected on a per-site basis when specifying both the `enabled` and `siteId` parameters. ([#9771](https://github.com/craftcms/cms/issues/9771))
- The `forms/selectize` control panel template now supports `addOptionFn` and `addOptionLabel` params, which can be set to add new options to the list.
- Editable tables now support `allowAdd`, `allowDelete`, and `allowReorder` settings, replacing `staticRows`. ([#10163](https://github.com/craftcms/cms/pull/10163))
- Column definitions passed to the `_includes/forms/editableTable` control panel template can now specify a `width` key. ([#11062](https://github.com/craftcms/cms/pull/11062))
- The `limitField` macro in the `_components/fieldtypes/elementfieldsettings` control panel template has been renamed to `limitFields`.
- Renamed the `elements/get-categories-input-html` action to `categories/input-html`.
- Renamed the `elements/get-modal-body` action to `element-selector-modals/body`.
- The `entries/save-entry` action now returns a 400 HTTP status for JSON responses when the entry couldn’t be saved.
- The `users/save-user` action no longer includes a `unverifiedEmail` key in failure responses.
- The `users/set-password` action now returns a 400 HTTP status when an invalid token is passed, if there’s no URL to redirect to. ([#10592](https://github.com/craftcms/cms/discussions/10592))
- `install/*`, `setup/*`, `db/*`, and `help` actions no longer output a warning if Craft can’t connect to the database. ([#10851](https://github.com/craftcms/cms/pull/10851))
- `createFoldersInVolume:<uid>` user permissions have been renamed to `createFolders:<uid>`.
- `deleteFilesAndFoldersInVolume:<uid>` user permissions have been renamed to `deleteAssets:<uid>`.
- `deletePeerFilesInVolume:<uid>` user permissions have been renamed to `deletePeerAssets:<uid>`.
- `editCategories:<uid>` user permissions have been split into `viewCategories:<uid>`, `saveCategories:<uid>`, `deleteCategories:<uid>`, `viewPeerCategoryDrafts:<uid>`, `savePeerCategoryDrafts:<uid>`, and `deletePeerCategoryDrafts:<uid>`.
- `editEntries:<uid>` user permissions have been renamed to `viewEntries:<uid>`.
- `editImagesInVolume:<uid>` user permissions have been renamed to `editImages:<uid>`.
- `editPeerEntries:<uid>` user permissions have been renamed to `viewPeerEntries:<uid>`.
- `editPeerEntryDrafts:<uid>` user permissions have been split into `viewPeerEntryDrafts:<uid>` and `savePeerEntryDrafts:<uid>`.
- `editPeerFilesInVolume:<uid>` user permissions have been renamed to `savePeerAssets:<uid>`.
- `editPeerImagesInVolume:<uid>` user permissions have been renamed to `editPeerImages:<uid>`.
- `publishEntries:<uid>` user permissions have been renamed to `saveEntries:<uid>`, and no longer differentiate between enabled and disabled entries. (Users with `viewEntries:<uid>` permissions will still be able to create drafts.)
- `publishPeerEntries:<uid>` user permissions have been renamed to `savePeerEntries:<uid>`, and no longer differentiate between enabled and disabled entries. (Users with `viewPeerEntries:<uid>` permissions will still be able to create drafts.)
- `replaceFilesInVolume:<uid>` user permissions have been renamed to `replaceFiles:<uid>`.
- `replacePeerFilesInVolume:<uid>` user permissions have been renamed to `replacePeerFiles:<uid>`.
- `saveAssetInVolume:<uid>` user permissions have been renamed to `saveAssets:<uid>`.
- `viewPeerFilesInVolume:<uid>` user permissions have been renamed to `viewPeerAssets:<uid>`.
- `viewVolume:<uid>` user permissions have been renamed to `viewAssets:<uid>`.
- Elements’ `searchScore` GraphQL fields are now returned as integers.
- Element types must now override `craft\base\Element::isDeletable()` if its elements should be deletable from the index page.
- Element types’ `cpEditUrl()` methods no longer need to add a `site` param; one will be added automatically by `craft\base\Element::getCpEditUrl()`.
- Element types’ `defineActions()` methods’ `$source` arguments should no longer accept `null`.
- Element types’ `defineSources()` methods’ `$context` arguments should no longer accept `null`.
- Element types’ `getHtmlAttributes()` and `htmlAttributes()` methods must now return attribute arrays that are compatible with `craft\helpers\Html::renderTagAttributes()`.
- Element types’ `sources()` methods’ `$context` arguments should no longer accept `null`.
- Element types’ `tableAttributes()` and `defineTableAttributes()` methods should no longer return a generic attribute for defining the header column heading at the beginning of the returned array. The header column heading is now set to the element type’s display name, per its `displayName()` method.
- Block element types’ `getOwner()` methods can now return `null`.
- Control panel resource locations are now cached, so resource requests can be resolved when Craft isn’t installed yet, or a database connection can’t be established. ([#10642](https://github.com/craftcms/cms/pull/10642))
- Control panel resources are now served with cache headers, if the `buildId` config setting is set. ([#10705](https://github.com/craftcms/cms/pull/10705))
- Empty subfolders within the temporary upload volume are now removed during garbage collection. ([#10746](https://github.com/craftcms/cms/issues/10746))
- Most config settings can now be overridden via environment variables. ([#10573](https://github.com/craftcms/cms/pull/10573), [#10869](https://github.com/craftcms/cms/pull/10869))
- It’s now possible to configure the Debug Toolbar to store its data files on a filesystem, rather than within `storage/runtime/debug/`. ([#10825](https://github.com/craftcms/cms/pull/10825))
- `craft\base\AssetPreviewHandlerInterface::getPreviewHtml()` now accepts an optional array of variable to pass on to the template.
- `craft\base\Element::__get()` now clones custom field values before returning them. ([#8781](https://github.com/craftcms/cms/discussions/8781))
- `craft\base\Element::fieldLayoutFields()` now has a `visibleOnly` argument.
- `craft\base\Element::getFieldValue()` now returns eager-loaded element values for the field, when they exist. ([#10047](https://github.com/craftcms/cms/issues/10047))
- `craft\base\Element::metaFieldsHtml()` now has a `static` argument.
- `craft\base\Element::setFieldValue()` now unsets any previously-eager-loaded elements for the field. ([#11003](https://github.com/craftcms/cms/discussions/11003))
- `craft\base\Element::slugFieldHtml()` now has a `static` argument.
- `craft\base\ElementInterface::getEagerLoadedElements()` now returns an `Illuminate\Support\Collection` object instead of an array. ([#8513](https://github.com/craftcms/cms/discussions/8513))
- `craft\base\ElementInterface::getSidebarHtml()` now has a `static` argument.
- `craft\base\MemoizableArray` no longer extends `ArrayObject`, and now implements `IteratorAggregate` and `Countable` directly.
- `craft\base\Model::__construct()` and `setAttributes()` now automatically typecast values that map to properties with `int`, `float`, `int|float`, `string`, `bool`, `array`, or `DateTime` type declarations. ([#10706](https://github.com/craftcms/cms/pull/10706))
- `craft\base\Model::datetimeAttributes()` is now called from the constructor, instead of the `init()` method.
- `craft\base\Model::setAttributes()` now normalizes date attributes into `DateTime` objects.
- `craft\behaviors\FieldLayoutBehavior::getFields()` has been renamed to `getCustomFields()`.
- `craft\elements\Asset::getImg()` now sets the `alt` attribute to the native Alternative Text field value, if set.
- `craft\elements\Asset::getVolume()` now returns an instance of `craft\models\Volume`.
- `craft\elements\db\ElementQuery::ids()` no longer accepts an array of criteria params.
- `craft\events\DraftEvent::$source` has been renamed to `$canonical`.
- `craft\events\GetAssetThumbUrlEvent` has been renamed to `DefineAssetThumbUrlEvent`.
- `craft\events\GetAssetUrlEvent` has been renamed to `DefineAssetUrlEvent`.
- `craft\events\RevisionEvent::$source` has been renamed to `$canonical`.
- `craft\fieldlayoutelements\AssetTitleField` has been renamed to `craft\fieldlayoutelements\assets\AssetTitleField`.
- `craft\fieldlayoutelements\EntryTitleField` has been renamed to `craft\fieldlayoutelements\entries\EntryTitleField`.
- `craft\fieldlayoutelements\StandardField` has been renamed to `craft\fieldlayoutelements\BaseNativeField`.
- `craft\fieldlayoutelements\StandardTextField` has been renamed to `craft\fieldlayoutelements\TextField`.
- `craft\fields\Assets::$singleUploadLocationSource` has been renamed to `$restrictedLocationSource`.
- `craft\fields\Assets::$singleUploadLocationSubpath` has been renamed to `$restrictedLocationSubpath`.
- `craft\fields\Assets::$useSingleFolder` has been renamed to `$restrictLocation`.
- `craft\fields\BaseRelationField::$limit` has been renamed to `$maxRelations`.
- `craft\fields\BaseRelationField::elementType()` is now public.
- `craft\fields\BaseRelationField::inputSelectionCriteria()` has been renamed to `getInputSelectionCriteria()`, and is now public.
- `craft\fields\BaseRelationField::inputSources()` has been renamed to `getInputSources()`, and is now public.
- `craft\gql\directives\FormatDateTime::defaultTimezone()` has been renamed to `defaultTimeZone()`.
- `craft\gql\TypeManager::EVENT_DEFINE_GQL_TYPE_FIELDS` is now triggered when actually resolving fields for a GraphQL type, rather than when the type is first created. ([#9626](https://github.com/craftcms/cms/issues/9626))
- `craft\helpers\App::env()` now checks for a PHP constant as well, if the environment variable didn’t exist.
- `craft\helpers\App::env()` now returns `null` if a value couldn’t be found, rather than `false`.
- `craft\helpers\App::env()` now returns a boolean if the original value was `'true'` or `'false'`.
- `craft\helpers\App::env()` now returns an integer or float if the original value was numeric.
- `craft\helpers\ArrayHelper::getValue()` now supports keys in square bracket syntax, e.g. `foo[bar][baz]`.
- `craft\helpers\Assets::generateUrl()` no longer accepts a transform index for date modified comparisons. A `DateTime` object is expected instead.
- `craft\helpers\Assets::urlAppendix()` no longer accepts a transform index for date modified comparisons. A `DateTime` object is expected instead.
- `craft\helpers\Component::createComponent()` now automatically typecasts values that map to properties with `int`, `float`, `int|float`, `string`, `bool`, `array`, or `DateTime` type declarations. ([#10706](https://github.com/craftcms/cms/pull/10706))
- `craft\helpers\Cp::elementHtml()` now has an `$autoReload` argument.
- `craft\helpers\Db::batchInsert()`, `craft\helpers\Db::insert()`, `craft\db\Command::batchInsert()`, `craft\db\Command::insert()`, `craft\db\Migration::batchInsert()`, and `craft\db\Migration::insert()` no longer have `$includeAuditColumns` arguments, and now check if the table has `dateCreated`, `dateUpdated`, and/or `uid` columns before setting their values.
- `craft\helpers\Db::parseParam()` now validates that numeric values are passed if the `$columnType` is set to a numeric column type. ([#9142](https://github.com/craftcms/cms/issues/9142))
- `craft\helpers\Db::prepareDateForDb()` no longer has a `$stripSeconds` argument.
- `craft\helpers\Db::prepareValueForDb()` now has a `$columnType` argument.
- `craft\helpers\Db::truncateTable()` now returns `void` rather than `int`.
- `craft\helpers\Db::update()`, `craft\helpers\Db::upsert()`, `craft\db\Command::update()`, `craft\db\Command::upsert()`, `craft\db\Migration::update()`’ and `craft\db\Migration::upsert()`’ `$includeAuditColumns` arguments have been renamed to `$updateTimestamp`, and only affect the `dateCreated` column now. All upserts now check if the table has `dateCreated`, `dateUpdated`, and/or `uid` columns before setting their values.
- `craft\helpers\Db::upsert()`, `craft\db\Command::upsert()`, and `craft\db\Migration()` no longer merge the `$updateColumns` array into `$insertColumns`. The full array of `INSERT` column values should be passed to `$insertColumns` now.
- `craft\helpers\Gql::getUnionType()` no longer requires a resolver function to be passed, if the union contains only element GraphQL types.
- `craft\helpers\Html::beginForm()` not sets `accept-charset="UTF-8"` by default.
- `craft\helpers\Html` now supports defining `hx-*` and `data-hx-*` attributes via a `hx` and `data-hx` keys, similar to `aria` and `data`.
- `craft\helpers\i18n\Formatter::asPercent()` now chooses a default `$decimals` value based on the value given, if `null`.
- `craft\helpers\i18n\Formatter::asPercent()` now treats all empty values as `0`.
- `craft\helpers\MailerHelper::normalizeEmails()` now returns an empty array instead of `null`.
- `craft\helpers\MigrationHelper::dropAllIndexesOnTable()` no longer returns an array of the dropped indexes.
- `craft\helpers\Queue::push()` now has a `$queue` argument.
- `craft\models\FieldLayout::EVENT_DEFINE_STANDARD_FIELDS` has been renamed to `EVENT_DEFINE_NATIVE_FIELDS`.
- `craft\models\FieldLayout::getAvailableStandardFields()` has been renamed to `getAvailableNativeFields()`.
- `craft\models\FieldLayout::getFields()` has been renamed to `getCustomFields()`.
- `craft\queue\Queue::$channel` is now set automatically based on the queue’s application component ID.
- `craft\services\Announcements::push()` no longer accepts callables to be passed to the `$heading` and `$body` arguments. `craft\i18n\Translation::prep()` should be used to prepare the messages to be lazy-translated instead.
- `craft\services\AssetIndexer::storeIndexList()` now expects the first argument to be a generator that returns `craft\models\FsListing` objects.
- `craft\services\Assets::ensureFolderByFullPathAndVolume()` now returns a `craft\models\VolumeFolder` object rather than a folder ID.
- `craft\services\Assets::ensureTopFolder()` now returns a `craft\models\VolumeFolder` object rather than a folder ID.
- `craft\services\Assets::EVENT_GET_ASSET_THUMB_URL` has been renamed to `EVENT_DEFINE_THUMB_URL`.
- `craft\services\Assets::EVENT_GET_ASSET_URL` has been moved to `craft\elements\Asset::EVENT_DEFINE_URL`.
- `craft\services\AssetTransforms::CONFIG_TRANSFORM_KEY` has been moved to `craft\services\ProjectConfig::PATH_IMAGE_TRANSFORMS`.
- `craft\services\Categories::CONFIG_CATEGORYROUP_KEY` has been moved to `craft\services\ProjectConfig::PATH_CATEGORY_GROUPS`.
- `craft\services\Fields::CONFIG_FIELDGROUP_KEY` has been moved to `craft\services\ProjectConfig::PATH_FIELD_GROUPS`.
- `craft\services\Fields::CONFIG_FIELDS_KEY` has been moved to `craft\services\ProjectConfig::PATH_FIELDS`.
- `craft\services\Globals::CONFIG_GLOBALSETS_KEY` has been moved to `craft\services\ProjectConfig::PATH_GLOBAL_SETS`.
- `craft\services\Gql::CONFIG_GQL_KEY` has been moved to `craft\services\ProjectConfig::PATH_GRAPHQL`.
- `craft\services\Gql::CONFIG_GQL_PUBLIC_TOKEN_KEY` has been moved to `craft\services\ProjectConfig::PATH_GRAPHQL_PUBLIC_TOKEN`.
- `craft\services\Gql::CONFIG_GQL_SCHEMAS_KEY` has been moved to `craft\services\ProjectConfig::PATH_GRAPHQL_SCHEMAS`.
- `craft\services\Matrix::CONFIG_BLOCKTYPE_KEY` has been moved to `craft\services\ProjectConfig::PATH_MATRIX_BLOCK_TYPES`.
- `craft\services\Matrix::duplicateBlocks()` now has a `$deleteOtherBlocks` argument.
- `craft\services\Plugins::CONFIG_PLUGINS_KEY` has been moved to `craft\services\ProjectConfig::PATH_PLUGINS`.
- `craft\services\Plugins::doesPluginRequireDatabaseUpdate()` has been renamed to `isPluginUpdatePending()`.
- `craft\services\ProjectConfig::applyYamlChanges()` has been renamed to `applyExternalChanges()`.
- `craft\services\ProjectConfig::getDoesYamlExist()` has been renamed to `getDoesExternalConfigExist()`.
- `craft\services\ProjectConfig::getIsApplyingYamlChanges()` has been renamed to `getIsApplyingExternalChanges()`.
- `craft\services\ProjectConfig::set()` now returns `true` or `false` depending on whether the project config was modified.
- `craft\services\Revisions::createRevision()` now returns the ID of the revision, rather than the revision itself.
- `craft\services\Routes::CONFIG_ROUTES_KEY` has been moved to `craft\services\ProjectConfig::PATH_ROUTES`.
- `craft\services\Sections::CONFIG_ENTRYTYPES_KEY` has been moved to `craft\services\ProjectConfig::PATH_ENTRY_TYPES`.
- `craft\services\Sections::CONFIG_SECTIONS_KEY` has been moved to `craft\services\ProjectConfig::PATH_PATH_SECTIONS`.
- `craft\services\Sites::CONFIG_SITEGROUP_KEY` has been moved to `craft\services\ProjectConfig::PATH_SITE_GROUPS`.
- `craft\services\Sites::CONFIG_SITES_KEY` has been moved to `craft\services\ProjectConfig::PATH_SITES`.
- `craft\services\Tags::CONFIG_TAGGROUP_KEY` has been moved to `craft\services\ProjectConfig::PATH_TAG_GROUPS`.
- `craft\services\Updates::getIsCraftDbMigrationNeeded()` has been renamed to `getIsCraftUpdatePending()`.
- `craft\services\Updates::getIsPluginDbUpdateNeeded()` has been renamed to `getIsPluginUpdatePending()`.
- `craft\services\UserGroups::CONFIG_USERPGROUPS_KEY` has been moved to `craft\services\ProjectConfig::PATH_USER_GROUPS`.
- `craft\services\UserPermissions::getAllPermissions()` and `getAssignablePermissions()` now return permission groups as arrays with `heading` and `permission` sub-keys, fixing a bug where two groups with the same heading would conflict with each other. ([#7771](https://github.com/craftcms/cms/issues/7771))
- `craft\services\Users::CONFIG_USERLAYOUT_KEY` has been moved to `craft\services\ProjectConfig::PATH_USER_FIELD_LAYOUTS`.
- `craft\services\Users::CONFIG_USERS_KEY` has been moved to `craft\services\ProjectConfig::PATH_USERS`.
- `craft\services\Volumes::CONFIG_VOLUME_KEY` has been moved to `craft\services\ProjectConfig::PATH_VOLUMES`.
- `craft\test\fixtures\elements\BaseElementFixture` now validates elements with the `live` scenario if they are enabled, canonical, and not a provisional draft.
- `craft\test\TestSetup::getMockApp()` has been renamed to `getMockModule()`, and its `$appClass` argument has been renamed to `$moduleClass`.
- `craft\web\Request::getBodyParam()` now accepts nested param names in the `foo[bar][baz]` format.
- `craft\web\Request::getBodyParams()` and `getBodyParam()` now check for an `X-Craft-Namespace` header. If present, only params that begin with its value will be returned, excluding the namespace.
- `craft\web\View::renderString()` now has an `$escapeHtml` argument.
- `craft\web\View::setNamespace()`’ `$namespace` argument no longer has a default value of `null`.
- The `Craft.getUrl()` JavaScript method now removes duplicate query string params when passing in a param that’s already included in the base URL.
- The `Craft.getUrl()` JavaScript method now encodes any query string params passed to it.
- `Craft.broadcastChannel` has been split up into two broadcast channels: `Craft.broadcaster` and `Craft.messageReceiver`.
- `Craft.cp.$tabs` now returns a collection of the tabs’ `<a>` elements, as they no longer have wrapping `<li>` elements.
- Local volumes no longer use Flysystem.
- A selected volume for user photo storage if no longer displayed if no volume has been set.
- The user photo volume can now only be set to a volume that has a public transform filesystem configured.
- Craft now uses Symfony Mailer to send email. ([#10062](https://github.com/craftcms/cms/discussions/10062))
- Updated Twig to 3.3.
- Updated vue-autosuggest to 2.2.0.

### Deprecated
- Deprecated the `autosaveDrafts` config setting.
- Deprecated the `anyStatus` element query param. `status(null)` should be used instead.
- Deprecated the `immediately` argument for transforms created over GraphQL. It no longer has any effect.
- Deprecated `craft\base\ApplicationTrait::getInstalledSchemaVersion()`.
- Deprecated `craft\base\Model::datetimeAttributes()`. ([#10706](https://github.com/craftcms/cms/pull/10706))
- Deprecated `craft\elements\User::getFullName()`. `$fullName` should be used instead.
- Deprecated `craft\gql\TypeManager::flush()`. `craft\services\Gql::flushCaches()` should be used instead.
- Deprecated `craft\gql\TypeManager::prepareFieldDefinitions()`. `craft\services\Gql::prepareFieldDefinitions()` should be used instead.
- Deprecated `craft\helpers\ArrayHelper::append()`. `array_unshift()` should be used instead.
- Deprecated `craft\helpers\ArrayHelper::prepend()`. `array_push()` should be used instead.
- Deprecated `craft\helpers\MigrationHelper`.
- Deprecated `craft\i18n\I18N::getIsIntlLoaded()`.
- Deprecated `craft\services\Assets::getAssetUrl()`. `craft\elements\Asset::getUrl()` should be used instead.
- Deprecated `craft\services\Assets::getIconPath()`. `craft\helpers\Assets::iconPath()` should be used instead.
- Deprecated `craft\web\Controller::asErrorJson()`. `asFailure()` should be used instead.
- Deprecated the `assets/save-asset` action. `elements/save` should be used instead.
- Deprecated the `categories/save-category` action. `elements/save` should be used instead.
- Deprecated the `Craft.appendFootHtml()` JavaScript method. `appendBodyHtml()` should be used instead.

### Removed
- Removed the “Header Column Heading” element source setting.
- Removed support for setting custom config settings from `config/general.php`. `config/custom.php` should be used instead. ([#10012](https://github.com/craftcms/cms/issues/10012))
- Removed the `customAsciiCharMappings` config setting.
- Removed the `siteName` config setting. Environment-specific site names can be defined via environment variables.
- Removed the `siteUrl` config setting. Environment-specific site URLs can be defined via environment variables.
- Removed the `suppressTemplateErrors` config setting.
- Removed the `useCompressedJs` config setting.
- Removed the `useProjectConfigFile` config setting. Override `craft\services\ProjectConfig::$writeYamlAutomatically` to opt into [manual YAML file generation](https://craftcms.com/docs/4.x/project-config.html#manual-yaml-file-generation).
- Removed support for `config/volumes.php`. Volumes can now specify per-environment filesystems.
- Removed support for the `CRAFT_SITE_URL` PHP constant. Environment-specific site URLs can be defined via environment variables.
- Removed the `enabledForSite` GraphQL argument. `status` should be used instead.
- Removed the `{% includeHiResCss %}` Twig tag.
- Removed support for deprecated `DateTime` faux Twig methods `atom()`, `cookie()`, `iso8601()`, `rfc822()`, `rfc850()`, `rfc1036()`, `rfc1123()`, `rfc2822()`, `rfc3339()`, `rss()`, `w3c()`, `w3cDate()`, `mySqlDateTime()`, `localeDate()`, `localeTime()`, `year()`, `month()`, `day()`, `nice()`, and `uiTimestamp()`.
- Removed the `locale` element property. `siteId` should be used instead.
- Removed the `ownerLocale` Matrix block query param. `site` or `siteId` should be used instead.
- Removed support for `sourceLocale` in `relatedTo` element query params. `sourceSite` should be used instead.
- Removed the `craft.categoryGroups` Twig variable.
- Removed the `craft.config` Twig variable.
- Removed the `craft.deprecator` Twig variable.
- Removed the `craft.elementIndexes` Twig variable.
- Removed the `craft.emailMessages` Twig variable.
- Removed the `craft.feeds` Twig variable.
- Removed the `craft.fields` Twig variable.
- Removed the `craft.globals` Twig variable.
- Removed the `craft.i18n` Twig variable.
- Removed the `craft.request` Twig variable.
- Removed the `craft.sections` Twig variable.
- Removed the `craft.session` Twig variable.
- Removed the `craft.systemSettings` Twig variable.
- Removed the `craft.userGroups` Twig variable.
- Removed the `craft.userPermissions` Twig variable.
- Removed the `assignUserGroups` user permission, which authorized users to assign other users to their own groups. Authorization must now be explicitly granted for each group. ([#10422](https://github.com/craftcms/cms/issues/10422))
- Removed the `customizeSources` user permission. Only admins can customize element sources now, and only from an environment that allows admin changes.
- Removed the `publishPeerEntryDrafts:<uid>` permissions, as they were pointless. (If a user is authorized to save an entry and view other users’ drafts of it, there’s nothing stopping them from making the same changes themselves.)
- Removed the `assets/edit-asset` action.
- Removed the `assets/thumb` action.
- Removed the `categories/edit-category` action.
- Removed the `categories/preview-category` action.
- Removed the `categories/share-category` action.
- Removed the `categories/view-shared-category` action.
- Removed the `dashboard/get-feed-items` action.
- Removed the `elements/get-editor-html` action.
- Removed the `entries/switch-entry-type` action.
- Removed `craft\base\ApplicationTrait::getEntryRevisions()`.
- Removed `craft\base\ApplicationTrait::getFeed()`.
- Removed `craft\base\Element::ATTR_STATUS_CONFLICTED`.
- Removed `craft\base\Element::getHasFreshContent()`. `getIsFresh()` should be used instead.
- Removed `craft\base\ElementInterface::getEditorHtml()`. Element edit forms are now exclusively driven by their field layout.
- Removed `craft\base\FieldLayoutElementInterface`.
- Removed `craft\base\FlysystemVolume`.
- Removed `craft\base\LocalVolumeInterface`.
- Removed `craft\base\Volume`.
- Removed `craft\base\VolumeInterface`.
- Removed `craft\base\VolumeTrait`.
- Removed `craft\behaviors\FieldLayoutBehavior::setFields()`.
- Removed `craft\config\DbConfig::updateDsn()`.
- Removed `craft\console\Request::getIsSingleActionRequest()`.
- Removed `craft\controllers\AssetTransformsController`.
- Removed `craft\controllers\BaseUpdaterController::ACTION_COMPOSER_OPTIMIZE`.
- Removed `craft\controllers\BaseUpdaterController::actionComposerOptimize()`.
- Removed `craft\controllers\Drafts`.
- Removed `craft\controllers\ElementIndexesController::$paginated`.
- Removed `craft\controllers\EntriesController::EVENT_PREVIEW_ENTRY`.
- Removed `craft\controllers\UtilitiesController::actionAssetIndexPerformAction()`.
- Removed `craft\db\Connection::trimObjectName()`.
- Removed `craft\db\Table::ASSETTRANSFORMINDEX`.
- Removed `craft\db\Table::ASSETTRANSFORMS`.
- Removed `craft\elements\actions\SetStatus::$allowDisabledForSite`.
- Removed `craft\elements\actions\SetStatus::DISABLED_FOR_SITE`.
- Removed `craft\elements\actions\SetStatus::DISABLED_GLOBALLY`.
- Removed `craft\elements\Asset::getSupportsPreview()`.
- Removed `craft\elements\Asset::getTransformSource()`.
- Removed `craft\elements\Asset::setTransformSource()`.
- Removed `craft\elements\db\ElementQuery::getIterator()`.
- Removed `craft\elements\db\ElementQuery::offsetExists()`.
- Removed `craft\elements\db\ElementQuery::offsetGet()`.
- Removed `craft\elements\db\ElementQuery::offsetSet()`.
- Removed `craft\elements\db\ElementQuery::offsetUnset()`.
- Removed `craft\elements\User::mergePreferences()`.
- Removed `craft\errors\AssetTransformException`.
- Removed `craft\errors\FieldNotFoundException`.
- Removed `craft\errors\InvalidVolumeException`.
- Removed `craft\errors\MissingVolumeFolderException`.
- Removed `craft\errors\VolumeException`.
- Removed `craft\errors\VolumeObjectExistsException`.
- Removed `craft\errors\VolumeObjectNotFoundException`.
- Removed `craft\events\AssetTransformEvent`.
- Removed `craft\events\AssetTransformImageEvent`.
- Removed `craft\events\DefineComponentsEvent`.
- Removed `craft\events\GenerateTransformEvent::$image`.
- Removed `craft\events\GenerateTransformEvent::$tempPath`.
- Removed `craft\events\GetAssetThumbEvent`.
- Removed `craft\events\GetAssetThumbUrlEvent::$generate`.
- Removed `craft\events\GetAssetThumbUrlEvent::$size`.
- Removed `craft\events\GlobalSetContentEvent`.
- Removed `craft\events\RegisterGqlPermissionsEvent`.
- Removed `craft\events\SearchEvent::getElementIds()`.
- Removed `craft\events\SearchEvent::setElementIds()`.
- Removed `craft\feeds\Feeds`.
- Removed `craft\feeds\GuzzleClient`.
- Removed `craft\fields\BaseOptionsField::optionLabel()`.
- Removed `craft\fields\Url::$placeholder`.
- Removed `craft\gql\base\Resolver::extractEagerLoadCondition()`.
- Removed `craft\gql\base\Resolver::getArrayableArguments()`.
- Removed `craft\gql\base\Resolver::prepareArguments()`.
- Removed `craft\helpers\App::dbMutexConfig()`.
- Removed `craft\helpers\App::getDefaultLogTargets()`.
- Removed `craft\helpers\App::logConfig()`.
- Removed `craft\helpers\Cp::editElementTitles()`.
- Removed `craft\helpers\Localization::localeData()`.
- Removed `craft\helpers\Stringy`.
- Removed `craft\i18n\Locale::setDateTimeFormats()`.
- Removed `craft\log\FileTarget`.
- Removed `craft\log\StreamLogTarget`.
- Removed `craft\models\AssetTransform`.
- Removed `craft\models\AssetTransformIndex`.
- Removed `craft\models\BaseEntryRevisionModel`.
- Removed `craft\models\EntryDraft`.
- Removed `craft\models\EntryVersion`.
- Removed `craft\models\FieldLayout::setFields()`.
- Removed `craft\models\FieldLayoutTab::getFields()`.
- Removed `craft\models\Site::$originalBaseUrl`.
- Removed `craft\models\Site::$originalName`.
- Removed `craft\models\Site::overrideBaseUrl()`.
- Removed `craft\models\Site::overrideName()`.
- Removed `craft\models\VolumeListing`.
- Removed `craft\mutex\DbMutexTrait`.
- Removed `craft\mutex\FileMutex`.
- Removed `craft\mutex\MysqlMutex`.
- Removed `craft\mutex\PgsqlMutex`.
- Removed `craft\mutex\PrefixedMutexTrait`.
- Removed `craft\queue\jobs\DeleteStaleTemplateCaches`.
- Removed `craft\records\AssetTransform`.
- Removed `craft\records\MatrixBlockType::$validateUniques`.
- Removed `craft\services\AssetIndexer::deleteStaleIndexingData()`.
- Removed `craft\services\AssetIndexer::extractFolderItemsFromIndexList()`.
- Removed `craft\services\AssetIndexer::extractSkippedItemsFromIndexList()`.
- Removed `craft\services\AssetIndexer::getIndexingSessionId()`.
- Removed `craft\services\AssetIndexer::getMissingFiles()`.
- Removed `craft\services\AssetIndexer::prepareIndexList()`.
- Removed `craft\services\AssetIndexer::processIndexForVolume()`.
- Removed `craft\services\Assets::$generatePendingTransformsViaQueue`.
- Removed `craft\services\Assets::EVENT_GET_ASSET_THUMB_URL`.
- Removed `craft\services\Assets::EVENT_GET_THUMB_PATH`.
- Removed `craft\services\Assets::getThumbPath()`.
- Removed `craft\services\AssetTransforms`.
- Removed `craft\services\Composer::$disablePackagist`.
- Removed `craft\services\Composer::optimize()`.
- Removed `craft\services\Content::getContentRow()`.
- Removed `craft\services\Content::populateElementContent()`.
- Removed `craft\services\Drafts::EVENT_AFTER_MERGE_SOURCE_CHANGES`.
- Removed `craft\services\Drafts::EVENT_AFTER_PUBLISH_DRAFT`.
- Removed `craft\services\Drafts::EVENT_BEFORE_MERGE_SOURCE_CHANGES`.
- Removed `craft\services\Drafts::EVENT_BEFORE_PUBLISH_DRAFT`.
- Removed `craft\services\Drafts::publishDraft()`.
- Removed `craft\services\EntryRevisions`.
- Removed `craft\services\Fields::assembleLayout()`.
- Removed `craft\services\Fields::getFieldIdsByLayoutId()`.
- Removed `craft\services\Fields::getFieldsByElementType()`.
- Removed `craft\services\Fields::getFieldsByLayoutId()`.
- Removed `craft\services\Gql::getAllPermissions()`.
- Removed `craft\services\Path::getAssetThumbsPath()`.
- Removed `craft\services\ProjectConfig::CONFIG_ALL_KEY`.
- Removed `craft\services\ProjectConfig::CONFIG_ALL_KEY`.
- Removed `craft\services\ProjectConfig::CONFIG_KEY`.
- Removed `craft\services\Sections::isSectionTemplateValid()`.
- Removed `craft\services\SystemSettings`.
- Removed `craft\services\TemplateCaches::deleteCacheById()`.
- Removed `craft\services\TemplateCaches::deleteCachesByKey()`.
- Removed `craft\services\TemplateCaches::deleteExpiredCaches()`.
- Removed `craft\services\TemplateCaches::deleteExpiredCachesIfOverdue()`.
- Removed `craft\services\TemplateCaches::EVENT_AFTER_DELETE_CACHES`.
- Removed `craft\services\TemplateCaches::EVENT_BEFORE_DELETE_CACHES`.
- Removed `craft\services\TemplateCaches::handleResponse()`.
- Removed `craft\services\TemplateCaches::handleResponse()`.
- Removed `craft\services\TemplateCaches::includeElementInTemplateCaches()`.
- Removed `craft\services\TemplateCaches::includeElementQueryInTemplateCaches()`.
- Removed `craft\services\Volumes::createVolume()`.
- Removed `craft\services\Volumes::EVENT_REGISTER_VOLUME_TYPES`.
- Removed `craft\services\Volumes::getAllVolumeTypes()`.
- Removed `craft\services\Volumes::getVolumeOverrides()`.
- Removed `craft\volumes\Local`.
- Removed `craft\volumes\MissingVolume`.
- Removed `craft\volumes\Temp`.
- Removed `craft\web\AssetBundle::useCompressedJs()`.
- Removed `craft\web\AssetManager::getPublishedPath()`.
- Removed `craft\web\Request::getIsSingleActionRequest()`.
- Removed `craft\web\twig\Template`.
- Removed `craft\web\twig\variables\CategoryGroups`.
- Removed `craft\web\twig\variables\Config`.
- Removed `craft\web\twig\variables\Deprecator`.
- Removed `craft\web\twig\variables\ElementIndexes`.
- Removed `craft\web\twig\variables\EmailMessages`.
- Removed `craft\web\twig\variables\Feeds`.
- Removed `craft\web\twig\variables\Fields`.
- Removed `craft\web\twig\variables\Globals`.
- Removed `craft\web\twig\variables\I18N`.
- Removed `craft\web\twig\variables\Request`.
- Removed `craft\web\twig\variables\Sections`.
- Removed `craft\web\twig\variables\SystemSettings`.
- Removed `craft\web\twig\variables\UserGroups`.
- Removed `craft\web\twig\variables\UserPermissions`.
- Removed `craft\web\twig\variables\UserSession`.
- Removed `craft\web\User::destroyDebugPreferencesInSession()`.
- Removed `craft\web\User::saveDebugPreferencesToSession()`.
- Removed `craft\web\View::$minifyCss`.
- Removed `craft\web\View::$minifyJs`.
- Removed `craft\web\View::registerHiResCss()`.
- Removed `craft\web\View::renderTemplateMacro()`.
- Removed the `_layouts/element` control panel template.
- Removed the `assets/_edit` control panel template.
- Removed the `categories/_edit` control panel template.
- Removed the `entries/_edit` control panel template.
- Removed the `cp.assets.edit.content` control panel template hook.
- Removed the `cp.assets.edit.details` control panel template hook.
- Removed the `cp.assets.edit.meta` control panel template hook.
- Removed the `cp.assets.edit.settings` control panel template hook.
- Removed the `cp.assets.edit` control panel template hook.
- Removed the `cp.categories.edit.content` control panel template hook.
- Removed the `cp.categories.edit.details` control panel template hook.
- Removed the `cp.categories.edit.meta` control panel template hook.
- Removed the `cp.categories.edit.settings` control panel template hook.
- Removed the `cp.categories.edit` control panel template hook.
- Removed the `cp.elements.edit` control panel template hook.
- Removed the `cp.entries.edit.content` control panel template hook.
- Removed the `cp.entries.edit.details` control panel template hook.
- Removed the `cp.entries.edit.meta` control panel template hook.
- Removed the `cp.entries.edit.settings` control panel template hook.
- Removed the `cp.entries.edit` control panel template hook.
- Removed the `Craft.AssetEditor` JavaScript class.
- Removed the `Craft.BaseElementEditor` JavaScript class.
- Removed the `Craft.DraftEditor` JavaScript class.
- Removed the `Craft.queueActionRequest()` JavaScript method. `Craft.queue.push()` can be used instead.
- Removed the Flysystem package. The `craftcms/flysystem-adapter` package now provides a base Flysystem adapter class.
- Removed the laminas-feed package.
- Removed the yii2-swiftmailer package.

### Fixed
- Fixed a bug where pending project config changes in the YAML would get applied when other project config changes were made. ([#9660](https://github.com/craftcms/cms/issues/9660))
- Fixed a bug where revisions weren’t getting propagated when a section was enabled for new sites, or its Propagation Method was changed. ([#10634](https://github.com/craftcms/cms/issues/10634))

### Security
- Generated control panel URLs now begin with the `@web` alias value if the `baseCpUrl` config setting isn’t defined.
- HTML entities output within email body text are now escaped by default in HTML email bodies.
