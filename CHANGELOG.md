# Release Notes for Craft CMS 4

## Unreleased

- Fixed a styling bug.

## 4.12.8 - 2024-10-22

- The `install` command now runs through database connection setup, if Craft can’t yet connect to the database. ([#15943](https://github.com/craftcms/cms/issues/15943))
- Fixed a bug where admin table header cells weren’t indicating when they were sorted. ([#15897](https://github.com/craftcms/cms/issues/15897))
- Fixed an error that occurred when creating a database backup, if the System Name contained any quote-like characters. ([#15933](https://github.com/craftcms/cms/issues/15933))
- Fixed a bug where buttons could bleed out of their containers. ([#15931](https://github.com/craftcms/cms/issues/15931), [#15946](https://github.com/craftcms/cms/pull/15946))
- Fixed a PHP error. ([#15915](https://github.com/craftcms/cms/issues/15915))
- Fixed an information disclosure vulnerability.

## 4.12.7 - 2024-10-15

- Fixed a privilege escalation vulnerability.

## 4.12.6.1 - 2024-10-09

- Custom field condition rules are now ignored if they reference a field with an incompatible type. ([#15850](https://github.com/craftcms/cms/issues/15850))

## 4.12.6 - 2024-10-08

- The Plugin Store now displays plugin ratings and reviews. ([#15860](https://github.com/craftcms/cms/pull/15860))
- An `InvalidConfigException` is now thrown if the `defaultCountryCode` config setting is set to an empty string. ([#15812](https://github.com/craftcms/cms/pull/15812))
- Fixed an error that could occur when saving an element, if a Date field’s time zone input was focused.
- Fixed a bug where the time zones listed in Date fields weren’t labelled properly based on the selected date. ([#15805](https://github.com/craftcms/cms/issues/15805))
- Fixed an error that could occur if a native element property was attempted to be eager-loaded. ([#15822](https://github.com/craftcms/cms/issues/15822))
- Fixed errors that could occur if a custom source or field condition referenced a custom field whose type had changed. ([#15850](https://github.com/craftcms/cms/issues/15850))
- Fixed a bug where disclosure menus weren’t sticking to their trigger element as it was scrolled, if it was within a slideout or other inline-scrollable container. ([#15852](https://github.com/craftcms/cms/issues/15852))
- Fixed a bug where the default backup command for MySQL was exporting triggers twice. ([#15854](https://github.com/craftcms/cms/pull/15854))
- Fixed a bug where Multi-select fields were saving the selected options in the user-selected order rather than the field-defined order. ([#15857](https://github.com/craftcms/cms/issues/15857))
- Fixed a missing authorization vulnerability.

## 4.12.5 - 2024-09-27

- Fixed a bug where entries’ `deletedWithEntryType` values in the `entries` table weren’t getting set back to `null` after being restored.
- Fixed a bug where it wasn’t possible to discard changes for related elements via slideouts, if they didn’t exist in the primary site. ([#15798](https://github.com/craftcms/cms/issues/15798))
- Fixed an information disclosure vulnerability.

## 4.12.4.1 - 2024-09-24

- Fixed a JavaScript error. ([#15784](https://github.com/craftcms/cms/issues/15784))

## 4.12.4 - 2024-09-23

- Auto-generated handles, slugs, etc. now update immediately when the source input is changed. ([#15754](https://github.com/craftcms/cms/issues/15754))
- Fixed a bug where Table fields’ Default Values table could lose existing rows if they only consisted of Dropdown columns without configured options.
- Fixed a bug where custom fields’ `required` properties were always `false`. ([#15752](https://github.com/craftcms/cms/issues/15752))
- Fixed a bug where `craft\helpers\StringHelper::toHandle()` was allowing non-alphanumeric/underscore characters through. ([#15772](https://github.com/craftcms/cms/pull/15772))
- Fixed a bug where entries were getting auto-saved while dragging elements within element select inputs.
- Fixed a bug where the `maxBackups` config setting wasn’t working. ([#15780](https://github.com/craftcms/cms/issues/15780))

## 4.12.3 - 2024-09-14

- Fixed a data deletion bug that occurred during garbage collection on PostgreSQL. ([#14891](https://github.com/craftcms/cms/issues/14891))
- Fixed a bug where image constraint labels weren’t translated within the Image Editor.
- Fixed a bug where image orientation labels weren’t getting translated for screen readers within the Image Editor.
- Fixed a PHP error. ([#14635](https://github.com/craftcms/cms/issues/14635))

## 4.12.2 - 2024-09-11

- Updated Twig to 3.14. ([#15704](https://github.com/craftcms/cms/issues/15704))
- Fixed a bug where soft-deleted structures weren’t getting hard-deleted via garbage collection. ([#15705](https://github.com/craftcms/cms/pull/15705))
- Fixed an RCE vulnerability.

## 4.12.1 - 2024-09-06

- Added `craft\services\Security::isSystemDir()`.
- Fixed a bug where `craft\helpers\StringHelper::lines()` was returning an array of `Stringy\Stringy` objects, rather than strings.
- Fixed styling issues with Template field layout UI elements’ selector labels.
- Fixed a validation error that could occur when saving a relational field, if the “Maintain hierarchy” setting had been enabled but was no longer applicable. ([#15666](https://github.com/craftcms/cms/issues/15666))
- Fixed a bug where formatted addresses weren’t using the application locale consistently. ([#15668](https://github.com/craftcms/cms/issues/15668))
- Fixed a bug where Tip and Warning field layout UI elements would display in field layouts even if they had no content. ([#15681](https://github.com/craftcms/cms/issues/15681))
- Fixed an error that could occur when reverting an element’s content from a revision, if the element had been added to additional sites since the time the revision was created. ([#15679](https://github.com/craftcms/cms/issues/15679))
- Fixed an information disclosure vulnerability.

## 4.12.0 - 2024-09-03

### Content Management
- Element conditions can now have a “Site Group” rule, if there are two or more site groups. ([#15625](https://github.com/craftcms/cms/discussions/15625))

### Development
- Country field values and `craft\elements\Address::getCountry()` now return the country in the current application locale.

### Extensibility
- Added `craft\base\ApplicationTrait::getEnvId()`. ([#15313](https://github.com/craftcms/cms/issues/15313))
- Added `craft\base\ElementInterface::getRootOwner()`. ([#15534](https://github.com/craftcms/cms/discussions/15534))
- Added `craft\elements\conditions\SiteGroupConditionRule`.
- Added `craft\helpers\Session::close()`.
- Added `craft\services\Sites::getEditableSitesByGroupId()`.
- `craft\helpers\Session` methods are now safe to call on console requests.
- Deprecated `craft\helpers\ElementHelper::rootElement()`. `craft\base\ElementInterface::getRootOwner()` should be used instead.
- Deprecated `craft\db\mysql\Schema::quoteDatabaseName()`.
- Deprecated `craft\db\pgqsl\Schema::quoteDatabaseName()`.

### System
- MySQL mutex locks and PHP session names are now namespaced using the application ID combined with the environment name. ([#15313](https://github.com/craftcms/cms/issues/15313))
- Craft now sends `X-Robots-Tag: none` headers for preview requests. ([#15612](https://github.com/craftcms/cms/pull/15612), [#15586](https://github.com/craftcms/cms/issues/15586))
- `x-craft-preview` and `x-craft-live-preview` params are now hashed, and `craft\web\Request::getIsPreview()` will only return `true` if the param validates. ([#15605](https://github.com/craftcms/cms/discussions/15605))
- Generated URLs no longer include `x-craft-preview` or `x-craft-live-preview` query string params based on the requested URL, if either were set to an unverified string. ([#15605](https://github.com/craftcms/cms/discussions/15605))
- The PHP session is now closed before making API requests. ([#15643](https://github.com/craftcms/cms/issues/15643))
- Updated Twig to 3.12. ([#15568](https://github.com/craftcms/cms/discussions/15568))
- Fixed a SQL error that occurred when running the `db/convert-charset` command if there were any custom database views or sequences. ([#15598](https://github.com/craftcms/cms/issues/15598))
- Fixed a bug where `craft\helpers\Db::supportsTimeZones()` could return `false` on databases that supported time zone conversion. ([#15592](https://github.com/craftcms/cms/issues/15592))
- Fixed a bug where Assets fields were validating settings that weren’t applicable depending on the “Restrict assets to a single location” setting. ([#15545](https://github.com/craftcms/cms/issues/15545))
- Fixed a bug where `null` values within associative arrays were ignored when applying project config data. ([#10512](https://github.com/craftcms/cms/issues/10512))

## 4.11.5 - 2024-08-26

- Fixed a bug where it wasn’t possible to override named transforms in GraphQL queries. ([#15572](https://github.com/craftcms/cms/issues/15572))
- Fixed a bug where address subdivision fields could be incorrectly labelled and/or populated with the wrong options. ([#15551](https://github.com/craftcms/cms/issues/15551), [#15584](https://github.com/craftcms/cms/pull/15584))
- Fixed a bug where Country fields were displaying the selected country code within element index tables, rather than the country name.
- Fixed a bug where `{% cache %}` tags were caching content for Live Preview requests. ([#15586](https://github.com/craftcms/cms/issues/15586))

## 4.11.4 - 2024-08-21

- Updated jQuery UI to 1.14.0. ([#15558](https://github.com/craftcms/cms/issues/15558))
- Fixed a bug where `craft\helpers\App::env()` and `normalizeValue()` could return incorrect results for values that looked like floats. ([#15533](https://github.com/craftcms/cms/issues/15533))
- Fixed a bug where the `users/set-password` action wasn’t respecting `redirect` params. ([#15538](https://github.com/craftcms/cms/issues/15538))
- Fixed a bug where the “Default Values” Table field setting wasn’t escaping column headings. ([#15552](https://github.com/craftcms/cms/issues/15552))
- Fixed a bug where Craft couldn’t be installed with existing project config files, if any plugins specified their schema version via `composer.json`. ([#15559](https://github.com/craftcms/cms/issues/15559))
- Fixed a bug where Money fields’ min, max, and default values weren’t being set to the correct currency. ([#15565](https://github.com/craftcms/cms/issues/15565), [#15566](https://github.com/craftcms/cms/pull/15566))
- Fixed a bug where PHP-originated Craft Console API requests weren’t timing out if the API was down. ([#15571](https://github.com/craftcms/cms/pull/15571))

## 4.11.3 - 2024-08-13

- Fixed a bug where the system name in the control panel’s global sidebar was getting hyperlinked even if the primary site didn’t have a URL. ([#15525](https://github.com/craftcms/cms/issues/15525))
- Fixed a right-to-left styling issue.

## 4.11.2 - 2024-08-12

- Fixed an error that could occur if a new element was saved recursively. ([#15517](https://github.com/craftcms/cms/issues/15517))
- Fixed a bug where plugins were being instantiated at the beginning of Craft installation requests, rather than after Craft was installed. ([#15506](https://github.com/craftcms/cms/issues/15506))
- Fixed a bug where an unhelpful error message was output when `config/general.php` returned an array with unsupported config settings. ([#15514](https://github.com/craftcms/cms/discussions/15514))

## 4.11.1 - 2024-08-07

- Fixed a PHP error that could occur when generating URLs via console requests. ([#15374](https://github.com/craftcms/cms/issues/15374))
- Fixed a bug where `craft\filters\Headers` and `craft\filters\Cors` were applied to control panel requests rather than site requests. ([#15495](https://github.com/craftcms/cms/issues/15495))
- Fixed a JavaScript error.

## 4.11.0.2 - 2024-08-06

- Fixed an error that could occur on console requests.

## 4.11.0.1 - 2024-08-06

- Fixed an error that occurred when accessing custom config settings defined in `config/custom.php`. ([#15481](https://github.com/craftcms/cms/issues/15481))

## 4.11.0 - 2024-08-06

### Content Management
- Entry and category conditions now have a “Has Descendants” rule. ([#15276](https://github.com/craftcms/cms/discussions/15276))
- “Replace file” actions now display success notices on complete. ([#15217](https://github.com/craftcms/cms/issues/15217))
- Double-clicking on folders within asset indexes and folder selection modals now navigates the index/modal into the folder. ([#15238](https://github.com/craftcms/cms/discussions/15238))
- When propagating an element to a new site, relation fields no longer copy relations for target elements that wouldn’t have been selectable from the propagated site based on the field’s “Related elements from a specific site?” and “Show the site menu” settings. ([#15459](https://github.com/craftcms/cms/issues/15459))

### Administration
- Added the `env`, `env/set`, and `env/remove` commands. ([#15431](https://github.com/craftcms/cms/pull/15431))
- New sites’ Base URL settings now default to an environment variable name based on the site name. ([#15347](https://github.com/craftcms/cms/pull/15347))
- Craft now warns against using the `@web` alias for URL settings, regardless of whether it was explicitly defined. ([#15347](https://github.com/craftcms/cms/pull/15347))

### Development
- Added the `withCustomFields` element query param.
- Added support for application-type based `general` and `db` configs (e.g. `config/general.web.php`). ([#15346](https://github.com/craftcms/cms/pull/15346))
- `general` and `db` config files can now return a callable that modifies an existing config object. ([#15346](https://github.com/craftcms/cms/pull/15346))
- Added the `lazyGqlTypes` config setting. ([#15429](https://github.com/craftcms/cms/issues/15429))
- The `allowedGraphqlOrigins` config setting is now deprecated. `craft\filters\Cors` should be used instead. ([#15397](https://github.com/craftcms/cms/pull/15397))
- The `permissionsPolicyHeader` config settings is now deprecated. `craft\filters\Headers` should be used instead. ([#15397](https://github.com/craftcms/cms/pull/15397))
- `{% cache %}` tags now cache any asset bundles registered within them.
- Country field values are now set to `CommerceGuys\Addressing\Country\Country` objects. ([#15455](https://github.com/craftcms/cms/issues/15455), [#15463](https://github.com/craftcms/cms/pull/15463))
- Auto-populated section and category group Template settings are now suffixed with `.twig`.
- `x-craft-preview`/`x-craft-live-preview` URL query string params are now added to generated URLs for Live Preview requests, so `craft\web\Request::getIsPreview()` continues to return `true` on subsequent pages loaded within the iframe. ([#15447](https://github.com/craftcms/cms/discussions/15447))

### Extensibility
- Added `craft\config\GeneralConfig::addAlias()`. ([#15346](https://github.com/craftcms/cms/pull/15346))
- Added `craft\elements\Address::getCountry()`. ([#15463](https://github.com/craftcms/cms/pull/15463))
- Added `craft\elements\Asset::$sanitizeOnUpload`. ([#15430](https://github.com/craftcms/cms/discussions/15430))
- Added `craft\filters\Cors`. ([#15397](https://github.com/craftcms/cms/pull/15397))
- Added `craft\filters\Headers`. ([#15397](https://github.com/craftcms/cms/pull/15397))
- Added `craft\helpers\App::configure()`.
- Added `craft\models\ImageTransform::$indexId`.
- Added `craft\web\View::clearAssetBundleBuffer()`.
- Added `craft\web\View::startAssetBundleBuffer()`.
- Added `Craft.EnvVarGenerator`.
- `craft\helpers\UrlHelper::cpUrl()` now returns URLs based on the primary site’s base URL (if it has one), for console requests if the `baseCpUrl` config setting isn’t set, and the `@web` alias wasn’t explicitly defined. ([#15374](https://github.com/craftcms/cms/issues/15374))
- `craft\services\Config::setDotEnvVar()` now accepts `false` for its `value` argument, which removes the environment variable from the `.env` file.
- Deprecated `craft\web\assets\elementresizedetector\ElementResizeDetectorAsset`.

### System
- Improved the performance of element indexes in structure view.
- The control panel now displays Ajax response-defined error messages when provided, rather than a generic “server error” message. ([#15292](https://github.com/craftcms/cms/issues/15292))
- Craft no longer sets the `Permissions-Policy` header on control panel responses. ([#15348](https://github.com/craftcms/cms/issues/15348))
- Control panel `resize` events now use ResizeObserver.
- Twig templates no longer attempt to preload singles for global variable names. ([#15468](https://github.com/craftcms/cms/pull/15468))
- Craft no longer ensures that the `cpresources` folder is writable.
- Front-end queue runner scripts are now injected before the `</body>` tag, rather than at the end of the response HTML.
- `graphql/api` requests no longer update the schema’s `lastUsed` timestamp if it was already updated within the last minute. ([#15464](https://github.com/craftcms/cms/issues/15464))
- Updated Yii to 2.0.51.
- Updated yii2-debug to 2.1.25.
- Updated svg-sanitizer to 0.19.
- Updated Axios to 0.28.1. ([#15448](https://github.com/craftcms/cms/issues/15448))
- Fixed a bug where error messages returned by the `users/send-password-reset-email` action weren’t accounting for the `useEmailAsUsername` config setting. ([#15425](https://github.com/craftcms/cms/issues/15425))
- Fixed a bug where `$element->isNewForSite` was always `false` from fields’ `normalizeValue()` methods when propagating an element to a new site.
- Fixed a bug where `assets/generate-transforms` requests could generate the wrong transform, if another transform index with the same parameters existed. ([#15402](https://github.com/craftcms/cms/pull/15402), [#15477](https://github.com/craftcms/cms/pull/15477))

## 4.10.8 - 2024-08-05

- Fixed a PHP error. ([#14635](https://github.com/craftcms/cms/issues/14635))
- Fixed a PHP error that could occur when running Codeception tests. ([#15445](https://github.com/craftcms/cms/issues/15445))
- Fixed a bug where `deleteAsset`, `deleteCategory`, `deleteEntry`, and `deleteTag` GraphQL mutations were returning `null` rather than `true` or `false`. ([#15465](https://github.com/craftcms/cms/issues/15465))
- Fixed a styling issue. ([#15473](https://github.com/craftcms/cms/issues/15473))
- Fixed a bug where `exists()` element queries weren’t working if `distinct`, `groupBy`, `having,` or `union` params were set on them during query preparation. ([#15001](https://github.com/craftcms/cms/issues/15001), [#15223](https://github.com/craftcms/cms/pull/15223))
- Fixed a bug where users’ `username` properties weren’t getting set if `useEmailAsUsername` was enabled. ([#15475](https://github.com/craftcms/cms/issues/15475))

## 4.10.7 - 2024-07-29

- Fixed a bug where element index result counts weren’t getting updated when the element list was refreshed but pagination was preserved. ([#15367](https://github.com/craftcms/cms/issues/15367))
- Fixed a PHP error that occurred when making a field layout component conditional on a Time or CKEditor field. ([craftcms/ckeditor#267](https://github.com/craftcms/ckeditor/issues/267))
- Fixed an error that occurred when editing a user via a slideout, if the current user didn’t have permission to edit the primary site. ([#15408](https://github.com/craftcms/cms/issues/15408))
- Fixed a bug where editable tables with single-select checkbox columns weren’t deselecting the selected option automatically. ([#15415](https://github.com/craftcms/cms/issues/15415))
- Fixed a styling issue. ([#15422](https://github.com/craftcms/cms/issues/15422))
- Fixed a bug where category groups’ Template settings weren’t being auto-populated for new groups.

## 4.10.6 - 2024-07-16

- `craft\helpers\UrlHelper::actionUrl()` now returns URLs based on the primary site’s base URL (if it has one), for console requests if the `@web` alias wasn’t explicitly defined.
- Fixed a bug where it wasn’t possible to expand/collapse descendants of disabled table rows within element select modals. ([#15337](https://github.com/craftcms/cms/issues/15337))
- Fixed a bug where PhpStorm autocomplete wasn’t working when chaining custom field methods defined by `CustomFieldBehavior`. ([#15336](https://github.com/craftcms/cms/issues/15336))
- Fixed a bug where new Matrix blocks created on newly-created elements weren’t getting duplicated to all other sites for the owner element. ([#15321](https://github.com/craftcms/cms/issues/15321))
- Fixed a bug where focus could jump unexpectedly when a slideout was opened. ([#15314](https://github.com/craftcms/cms/issues/15314))

## 4.10.5 - 2024-07-11

> [!NOTE]
> Craft now sends no-cache headers for requests that generate/retrieve a CSRF token. If your Craft install is behind a static caching service like Cloudflare, enable the [asyncCsrfInputs](https://craftcms.com/docs/5.x/reference/config/general.html#asynccsrfinputs) config setting to avoid a significant cache hit reduction. ([#15293](https://github.com/craftcms/cms/pull/15293), [#15281](https://github.com/craftcms/cms/pull/15281))

- Craft now sends no-cache headers for any request that calls `craft\web\Request::getCsrfToken()`. ([#15293](https://github.com/craftcms/cms/pull/15293), [#15281](https://github.com/craftcms/cms/pull/15281))
- Fixed a bug where structures’ Max Levels settings weren’t being enforced when dragging elements with collapsed descendants. ([#15310](https://github.com/craftcms/cms/issues/15310))
- Fixed a bug where `craft\helpers\ElementHelper::isDraft()`, `isRevision()`, and `isDraftOrRevision()` weren’t returning `true` if a nested draft/revision element was passed in, but the root element was canonical. ([#15303](https://github.com/craftcms/cms/issues/15303))
- Fixed a bug where focus could be trapped within slideout sidebars. ([#15314](https://github.com/craftcms/cms/issues/15314))

## 4.10.4 - 2024-07-02

- Craft now sends no-cache headers for any request that generates a CSRF token. ([#15281](https://github.com/craftcms/cms/pull/15281), [verbb/formie#1963](https://github.com/verbb/formie/issues/1963))
- Fixed a JavaScript error that occurred when creating a new custom element source, preventing the Default Sort and Default Table Columns fields from showing up.
- Fixed a bug where the control panel was getting asynchronous CSRF inputs if the `asyncCsrfInputs` config setting was enabled.
- Fixed a bug where Craft’s Twig implementation wasn’t respecting sandboxing rules for object properties. ([#15278](https://github.com/craftcms/cms/issues/15278))

## 4.10.3 - 2024-06-27

- Previewing PDF/video assets without public URLs now displays a “Preview not supported.” message. ([#15235](https://github.com/craftcms/cms/pull/15235))
- Added `Garnish.once()` and `Garnish.Base::once()`, for registering event handlers that should only be triggered one time.
- Fixed a bug where Edit Asset pages showed a “View” button for assets without URLs. ([#15235](https://github.com/craftcms/cms/pull/15235))
- Fixed a bug where asset indexes attempted to link to assets without URLs. ([#15235](https://github.com/craftcms/cms/pull/15235))
- Fixed a bug where queue job tracking and element activity tracking could stop working after a user session expired and then was reauthenticated.
- Fixed an error that occurred if an element select input was initialized without a `name` value.
- Fixed a bug where Selectize inputs could be immediately focused and marked as dirty when opening an element editor slideout, if they were the first focusable element in the field layout. ([#15245](https://github.com/craftcms/cms/issues/15245))

## 4.10.2 - 2024-06-18

- Added `craft\base\conditions\BaseNumberConditionRule::$step`.
- Added `Garnish.muteResizeEvents()`.
- Fixed a JavaScript performance degradation bug. ([#14510](https://github.com/craftcms/cms/issues/14510))
- Fixed a bug where scalar element queries weren’t working if `distinct`, `groupBy`, `having,` or `union` params were set on them during query preparation. ([#15001](https://github.com/craftcms/cms/issues/15001))
- Fixed a bug where Edit Asset pages would warn about losing unsaved changes when navigating away, if the file was replaced but nothing else had changed.
- Fixed a bug where Edit Asset pages would show a notification with a “Reload” button after the file was replaced.
- Fixed a bug where Number fields’ condition rules weren’t allowing decimal values. ([#15222](https://github.com/craftcms/cms/issues/15222))

## 4.10.1 - 2024-06-17

- Added `craft\web\View::getModifiedDeltaNames()`.
- `craft\web\View::registerDeltaName()` now has a `$forceModified` argument.
- Fixed a bug where changed field values could be forgotten within Matrix fields, if a validation error occurred. ([#15190](https://github.com/craftcms/cms/issues/15190))
- Fixed a bug where the `graphql/create-token` command was prompting for the schema name, when it meant the token name. ([#15205](https://github.com/craftcms/cms/pull/15205))
- Fixed an error that could occur when applying a draft. ([#15211](https://github.com/craftcms/cms/issues/15211))
- Fixed a bug where keyboard shortcuts weren’t getting registered properly for modals and slideouts opened via a disclosure menu. ([#15209](https://github.com/craftcms/cms/issues/15209))

## 4.10.0 - 2024-06-12

### Content Management
- Relational field condition rules no longer factor in the target elements’ statuses or sites. ([#14989](https://github.com/craftcms/cms/issues/14989))
- “Save and continue editing” actions now restore the page’s scroll position on reload.

### Administration
- Added the `--format` option to the `db/backup` and `db/restore` commands for PostgreSQL installs. ([#14931](https://github.com/craftcms/cms/pull/14931))
- The `db/restore` command now autodetects the backup format for PostgreSQL installs, if `--format` isn’t passed. ([#14931](https://github.com/craftcms/cms/pull/14931))
- The `install` command and web-based installer now validate the existing project config files at the outset, and abort installation if there are any issues.
- The web-based installer now displays the error message when installation fails.
- GraphQL schema edit pages now have a “Save and continue editing” alternate action.
- The system edition can now be defined by a `CRAFT_EDITION` environment variable. ([#15094](https://github.com/craftcms/cms/discussions/15094))
- The rebrand assets path can now be defined by a `CRAFT_REBRAND_PATH` environment variable. ([#15110](https://github.com/craftcms/cms/pull/15110))

### Development
- Added the `{% expires %}` tag, which simplifies setting cache headers on the response. ([#14969](https://github.com/craftcms/cms/pull/14969))
- Added `craft\elements\ElementCollection::find()`, which can return an element or elements in the collection based on a given element or ID. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- Added `craft\elements\ElementCollection::fresh()`, which reloads each of the collection elements from the database. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- The `collect()` Twig function now returns a `craft\elements\ElementCollection` instance if all of the items are elements.
- `craft\elements\ElementCollection::contains()` now returns `true` if an element is passed in and the collection contains an element with the same ID and site ID; or if an integer is passed in and the collection contains an element with the same ID. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::countBy()`, `collapse()`, `flatten()`, `keys()`, `pad()`, `pluck()`, and `zip()` now return an `Illuminate\Support\Collection` object. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::diff()` and `intersect()` now compare the passed-in elements to the collection elements by their IDs and site IDs. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::flip()` now throws an exception, as element objects can’t be used as array keys. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::map()` and `mapWithKeys()` now return an `Illuminate\Support\Collection` object, if any of the mapped values aren’t elements. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::merge()` now replaces any elements in the collection with passed-in elements, if their ID and site ID matches. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::only()` and `except()` now compare the passed-in values to the collection elements by their IDs, if an integer or array of integers is passed in. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::unique()` now returns all elements with unique IDs, if no key is passed in. ([#15023](https://github.com/craftcms/cms/discussions/15023))

### Extensibility
- Improved type definitions for `craft\db\Query`, element queries, and `craft\elements\ElementCollection`.
- Added `craft\db\getBackupFormat()`.
- Added `craft\db\getRestoreFormat()`.
- Added `craft\db\setBackupFormat()`.
- Added `craft\db\setRestoreFormat()`.
- Added `craft\events\InvalidateElementcachesEvent::$element`.
- Added `craft\fields\BaseRelationField::existsQueryCondition()`.
- Added `craft\helpers\DateTimeHelper::relativeTimeStatement()`.
- Added `craft\helpers\DateTimeHelper::relativeTimeToSeconds()`.
- Added `craft\helpers\StringHelper::indent()`.
- Added `craft\queue\Queue::getJobId()`.
- `craft\elements\ElementCollection::with()` now supports collections made up of multiple element types.
- Added the `reloadOnBroadcastSave` setting to `Craft.ElementEditor`. ([#14814](https://github.com/craftcms/cms/issues/14814))

### System
- Improved the performance of `exists()` element queries.
- The Debug Toolbar now pre-serializes objects stored as request parameters, fixing a bug where closures could prevent the entire Request panel from showing up. ([#14982](https://github.com/craftcms/cms/discussions/14982))
- Batched queue jobs now verify that they are still reserved before each step, and before spawning additional batch jobs. ([#14986](https://github.com/craftcms/cms/discussions/14986))
- Updated Yii to 2.0.50. ([#15124](https://github.com/craftcms/cms/issues/15124))
- Updated inputmask to 5.0.9.
- Updated punycode to 2.3.1.

## 4.9.7 - 2024-06-05

- Fixed a bug where the `db/backup` command could fail on Windows. ([#15090](https://github.com/craftcms/cms/issues/15090))
- Fixed an error that could occur when applying project config changes if a site was deleted. ([#14373](https://github.com/craftcms/cms/issues/14373))

## 4.9.6 - 2024-06-03

- Added `craft\helpers\Gql::isIntrospectionQuery()`.
- Fixed a bug where some condition rules weren’t getting added when applying project config changes, if they depended on another component which hadn’t been added yet. ([#15037](https://github.com/craftcms/cms/issues/15037))
- Fixed a bug where the `craft\base\Element::EVENT_DEFINE_ADDITIONAL_BUTTONS` event wasn’t being respected for user edit pages. ([#15095](https://github.com/craftcms/cms/issues/15095))
- Fixed a bug where GraphQL queries could be misidentified as introspection queries. ([#15100](https://github.com/craftcms/cms/issues/15100))

## 4.9.5 - 2024-05-22

- Scalar element queries now set `$select` to the scalar expression, and `$orderBy`, `$limit`, and `$offset` to `null`, on the element query. ([#15001](https://github.com/craftcms/cms/issues/15001))
- Added `craft\fieldlayoutelements\TextareaField::inputTemplateVariables()`.
- Fixed a bug where `craft\helpers\Assets::prepareAssetName()` wasn’t sanitizing filenames if `$preventPluginModifications` was `true`.
- Fixed a bug where element queries’ `count()` methods were factoring in the `limit` param when searching with `orderBy` set to `score`. ([#15001](https://github.com/craftcms/cms/issues/15001))
- Fixed a bug where soft-deleted elements that belonged to a revision could be deleted by garbage collection. ([#14995](https://github.com/craftcms/cms/pull/14995))
- Fixed a bug where soft-deleted structure data associated with elements that belonged to a revision could be deleted by garbage collection. ([#14995](https://github.com/craftcms/cms/pull/14995))
- Fixed a bug where element edit pages’ scroll positions weren’t always retained when automatically refreshed.
- Fixed a bug where the `up` command could remove component name comments from the project config YAML files, for newly-added components. ([#15012](https://github.com/craftcms/cms/issues/15012))
- Fixed a bug where assets’ Alternative Text fields didn’t expand to match the content height. ([#15026](https://github.com/craftcms/cms/issues/15026))
- Fixed a bug where `craft\helpers\UrlHelper::isAbsoluteUrl()` was returning `true` for Windows file paths. ([#15043](https://github.com/craftcms/cms/issues/15043))

## 4.9.4 - 2024-05-17

- Fixed a bug where `craft\elements\db\ElementQuery::exists()` would return `true` if `setCachedResult()` had been called, even if an empty array was passed.
- Fixed a bug where it wasn’t possible to interact with disabled Matrix blocks. ([#15002](https://github.com/craftcms/cms/issues/15002))
- Fixed an infinite recursion bug that could occur when `craft\web\Response::redirect()` was called. ([#15014](https://github.com/craftcms/cms/pull/15014))

## 4.9.3 - 2024-05-14

- Fixed a SQL error that could occur when applying or rebuilding the project config.
- Fixed a bug where adjacent selected table rows were getting extra spacing in Firefox.
- Fixed a SQL error that could occur when creating revisions after garbage collection was run. ([#14309](https://github.com/craftcms/cms/issues/14309))
- Fixed a bug where the `serve` command wasn’t serving paths with non-ASCII characters. ([#14977](https://github.com/craftcms/cms/issues/14977))
- Fixed a bug where element chips within element selection tables had insufficient contrast. ([#14963](https://github.com/craftcms/cms/issues/14963))
- Fixed a bug where `craft\helpers\Html::explodeStyle()` and `normalizeTagAttributes()` weren’t handling styles with encoded images via `url()` properly. ([#14964](https://github.com/craftcms/cms/issues/14964))
- Fixed a bug where the `db/backup` command would fail if the destination path contained a space.

## 4.9.2 - 2024-05-07

- Fixed a bug where the `db/backup` command would prompt for password input on PostgreSQL. ([#14945](https://github.com/craftcms/cms/issues/14945))
- Fixed a bug where pressing <kbd>Shift</kbd> + <kbd>Spacebar</kbd> wasn’t reliably opening the asset preview modal on the Assets index page. ([#14943](https://github.com/craftcms/cms/issues/14943))
- Fixed a bug where pressing <kbd>Shift</kbd> + <kbd>Spacebar</kbd> within an asset preview modal wasn’t closing the modal.
- Fixed a bug where pressing arrow keys within asset preview modals wasn’t retargeting the preview modal to adjacent assets. ([#14943](https://github.com/craftcms/cms/issues/14943))
- Fixed a bug where entry selection modals could have a “New entry” button even if there weren’t any sections enabled for the selected site. ([#14923](https://github.com/craftcms/cms/issues/14923))

## 4.9.1 - 2024-05-02

- Fixed a bug where disclosure menus weren’t releasing their `scroll` and `resize` event listeners on hide. ([#14911](https://github.com/craftcms/cms/pull/14911), [#14510](https://github.com/craftcms/cms/issues/14510))
- Fixed a bug where MySQL backups weren’t restorable on certain environments. ([#14925](https://github.com/craftcms/cms/pull/14925))
- Fixed a bug where `app/resource-js` requests weren’t working for guest requests. ([#14908](https://github.com/craftcms/cms/issues/14908))

## 4.9.0 - 2024-04-30

### Content Management
- It’s now possible to preview revisions. ([#14521](https://github.com/craftcms/cms/discussions/14521))
- Element conditions can now include condition rules for Time fields. ([#14616](https://github.com/craftcms/cms/discussions/14616))
- Sort options are now sorted alphabetically within element indexes, and custom fields’ options are now listed in a “Fields” group. ([#14725](https://github.com/craftcms/cms/issues/14725))
- Unselected table column options are now sorted alphabetically within element indexes.

### Administration
- Improved the behavior of the URI input within Edit Route modals. ([#14884](https://github.com/craftcms/cms/issues/14884))
- The “Upgrade Craft CMS” page in the Plugin Store no longer lists unsupported editions.
- Added the `asyncCsrfInputs` config setting. ([#14625](https://github.com/craftcms/cms/pull/14625))
- Added the `backupCommandFormat` config setting. ([#14897](https://github.com/craftcms/cms/pull/14897))
- The `backupCommand` config setting can now be set to a closure, which will be passed a `mikehaertl\shellcommand\Command` object. ([#14897](https://github.com/craftcms/cms/pull/14897))
- `resave` commands now support an `--if-invalid` option. ([#14731](https://github.com/craftcms/cms/issues/14731))

### Development
- Added the `safeMode` config setting. ([#14734](https://github.com/craftcms/cms/pull/14734))
- Added the `language` element query param, which filters the resulting elements based on their sites’ languages. ([#14631](https://github.com/craftcms/cms/discussions/14631))
- GraphQL responses now include full exception details, when Dev Mode is enabled or an admin is signed in with the “Show full exception views when Dev Mode is disabled” preference enabled. ([#14527](https://github.com/craftcms/cms/issues/14527))
- `craft\helpers\Html::csrfInput()` and the `csrfInput` Twig function now support passing an `async` key to the `options` array, overriding the default behavior per the `asyncCsrfInputs` config setting. ([#14625](https://github.com/craftcms/cms/pull/14625))

### Extensibility
- Added `craft\services\ProjectConfig::getAppliedChanges()`. ([#14851](https://github.com/craftcms/cms/discussions/14851))
- Added `craft\services\Sites::getSitesByLanguage()`.
- Added `craft\web\ErrorHandler::exceptionAsArray()`.
- Added `craft\web\ErrorHandler::showExceptionDetails()`.
- Added `craft\web\Request::getBearerToken()`. ([#14784](https://github.com/craftcms/cms/pull/14784))
- `craft\base\NameTrait::prepareNamesForSave()` no longer updates the name properties if `fullName`, `firstName`, and `lastName` are already set. ([#14665](https://github.com/craftcms/cms/issues/14665))

### System
- Batched queue jobs now set their progress based on the total progress across all batches, rather than just the current batch. ([#14817](https://github.com/craftcms/cms/pull/14817))
- Craft now calls `setlocale()` based on the target language, so that `SORT_LOCALE_STRING` behaves as expected. ([#14509](https://github.com/craftcms/cms/issues/14509), [#14513](https://github.com/craftcms/cms/pull/14513))
- Improved the performance of scalar element queries like `count()`.
- Fixed a bug where `craft\elements\db\ElementQuery::count()` could return the wrong number if the query had a cached result, with `offset` or `limit` params.
- Console requests no longer filter out info logs. ([#14280](https://github.com/craftcms/cms/issues/14280), [#14434](https://github.com/craftcms/cms/pull/14434))
- Fixed a styling issue with Categories and Entries fields when “Maintain Hierarchy” was enabled.
- Fixed a bug where Delete actions weren’t working in admin tables. ([craftcms/commerce#3444](https://github.com/craftcms/commerce/issues/3444))

## 4.8.11 - 2024-04-29

- Fixed a bug where element caches weren’t getting invalidated when an element was moved within a structure. ([#14846](https://github.com/craftcms/cms/issues/14846))
- Fixed a bug where CSV’s header rows weren’t using the configured delimiter. ([#14855](https://github.com/craftcms/cms/issues/14855))
- Fixed a bug where editable table cell text styling could change after initial focus. ([#14857](https://github.com/craftcms/cms/issues/14857))
- Fixed a bug where conditions could list rules with duplicate labels.
- Fixed a bug where admin tables weren’t displaying disabled statuses. ([#14861](https://github.com/craftcms/cms/issues/14861))

## 4.8.10 - 2024-04-23

- Fixed a SQL error that could occur when converting a field to a Lightswitch field on PostgreSQL. ([#14792](https://github.com/craftcms/cms/issues/14792))
- Fixed a bug where the Database Backup utility was present when the `backupCommand` config setting was set to `false`.
- Fixed an error that occurred when running the `db/convert-charset` command, if any tables contained `char` or `varchar` foreign key columns. ([#14815](https://github.com/craftcms/cms/issues/14815))
- Fixed a bug where parsed first/last names could have different casing than the full name that was submitted. ([#14723](https://github.com/craftcms/cms/issues/14723))
- Fixed a bug where `craft\helpers\UrlHelper::isAbsoluteUrl()` was returning `false` for URLs with schemes other than `http` or `https`, such as `mailto` and `tel`. ([#14830](https://github.com/craftcms/cms/issues/14830))
- Fixed a JavaScript error that occurred when opening Live Preview, if an Assets field’s “Upload files” button had been pressed. ([#14832](https://github.com/craftcms/cms/issues/14832))
- Fixed a bug where Twig’s spread operator (`...`) wasn’t working with attribute accessors. ([#14827](https://github.com/craftcms/cms/issues/14827))

## 4.8.9 - 2024-04-10

- Fixed a bug where element queries with the `relatedTo` param set to a list of element IDs were overly complex.
- Fixed a bug where redundant Matrix block revisions were getting created.
- Fixed a bug where Twig’s spread operator (`...`) wasn’t working when the `preloadSingles` config setting was enabled. ([#14783](https://github.com/craftcms/cms/issues/14783))
- Fixed a bug where Live Preview wasn’t retaining the scroll position properly. ([#14218](https://github.com/craftcms/cms/issues/14218))

## 4.8.8 - 2024-04-09

- Fixed a bug where `craft\helpers\ElementHelper::siteStatusesForElement()` wasn’t working for soft-deleted elements. ([#14753](https://github.com/craftcms/cms/issues/14753))
- Fixed a bug where the Queue Manager was listing delayed jobs before others. ([#14755](https://github.com/craftcms/cms/discussions/14755))
- Fixed a bug where LTR and RTL characters weren’t getting stripped from sanitized asset filenames. ([#14711](https://github.com/craftcms/cms/issues/14711))
- Fixed a bug where admin table row reordering wasn’t working in Safari. ([#14752](https://github.com/craftcms/cms/issues/14752))
- Fixed a bug where the `utils/fix-field-layout-uids` command wasn’t looking at field layouts defined with a `fieldLayout` key in the project config.
- Fixed a bug where element indexes’ View menus could show the “Sort by” field when the structure view was selected. ([#14780](https://github.com/craftcms/cms/issues/14780))

## 4.8.7 - 2024-04-03

- The Craft 5 Upgrade utility now shows the upgrade status and latest compatible version for abandoned plugins.
- Fixed a “Double-instantiating a checkbox select on an element” JavaScript warning. ([#14707](https://github.com/craftcms/cms/issues/14707))
- Fixed a bug where `craft\cache\DbCache` was attempting to store values beyond the `cache.data` column’s storage capacity.
- Fixed a bug where the Updates utility could include submit buttons without labels for abandoned plugins.

## 4.8.6 - 2024-03-26

- Added the “Craft 5 Upgrade” utility.

## 4.8.5 - 2024-03-22

- Selectize inputs no longer automatically select the hovered option on <kbd>Tab</kbd> press. ([selectize/selectize.js#2085](https://github.com/selectize/selectize.js/issues/2085))
- The `setup/cloud` command now ensures that the environment and `composer.json` are configured to use PHP 8.1+.
- Fixed a JavaScript error that could occur if another error occurred when performing an element action.
- Fixed a bug where filesystems’ `afterSave()` and `afterDelete()` methods weren’t getting called. ([#14634](https://github.com/craftcms/cms/pull/14634))
- Fixed an error that could occur on `elements/recent-activity` Ajax requests when editing an element. ([#14635](https://github.com/craftcms/cms/issues/14635))

## 4.8.4 - 2024-03-19

- Craft no longer shows an alert when an installed Craft/plugin edition is a lower tier than the licensed edition.

## 4.8.3 - 2024-03-15

- `craft\helpers\DateTime::toDateTime()` now attempts to create a `DateTime` object by passing the passe-in value into its constructor.
- Fixed a bug where navigating back after creating an entry or applying a draft would return a 404 error. ([#14587](https://github.com/craftcms/cms/issues/14587))
- Fixed a bug where action URLs weren’t respecting the subpath specified by the `@web` alias, if it wasn’t present in the local URL to `index.php`.

## 4.8.2 - 2024-03-12

- Entry queries are now ordered by `postDate DESC, id DESC` by default, rather than just `postDate DESC`, to ensure their order is consistent when two entries have the same post date.
- Fixed a bug where `craft\helpers\DateRange::dateIntervalByTimePeriod()` wasn’t accounting for DST changes.
- Fixed a bug where programmatically-updated `<textarea>`s weren’t triggering autosaves. ([craftcms/ckeditor#172](https://github.com/craftcms/ckeditor/issues/172))
- Fixed a JavaScript error that could occur when navigating between asset folders, when a JavaScript-based uploader was registered. ([#14542](https://github.com/craftcms/cms/pull/14542))
- Fixed a bug where action URLs were getting duplicate URI segments if Craft was installed in a subpath within the webroot. ([#14559](https://github.com/craftcms/cms/issues/14559))

## 4.8.1 - 2024-03-05

- Fixed a bug where some characters were getting misinterpreted as elisions when normalizing search keywords.
- Fixed a bug where `craft\helpers\UrlHelper::actionUrl()` was using the site URL rather than the requested URL. ([#14440](https://github.com/craftcms/cms/issues/14440))
- Fixed a bug where `craft\helpers\Html::parseTagAttribute()` wasn’t handling attribute values with newlines. ([#14498](https://github.com/craftcms/cms/issues/14498))
- Fixed a bug where the “Filesystem Type” setting wasn’t toggling type-specific settings when editing a filesystem via a slideout. ([#14522](https://github.com/craftcms/cms/issues/14522))
- Fixed a potential SSTI vulnerability.

## 4.8.0 - 2024-02-26

> [!NOTE]  
> Trialing Craft and plugin updates with expired licenses is allowed now, on non-public domains.

> [!WARNING]  
> When licensing issues occur on public domains, the control panel will now become temporarily inaccessible for logged-in users, alerting them to the problems and giving them an opportunity to resolve them. (The front end will not be impacted.)

### Content Management
- Assets fields’ selection modals now open to the last-viewed location by default, if their Default Upload Location doesn’t specify a subpath. ([#14382](https://github.com/craftcms/cms/pull/14382))
- Element sources no longer display `0` badges.

### Administration
- Color fields now have a “Presets” settings. ([#14463](https://github.com/craftcms/cms/discussions/14463))
- It’s now possible to update expired licenses from the Updates utility, on non-public domains. 
- The `queue/run` command now supports a `--job-id` option.
- `update all` and `update <handle>` commands now support a `--with-expired` option. 

### Development
- The GraphQL API is now available for Craft Solo installs.
- The `{% js %}` and `{% css %}` tags now support `.js.gz` and `.css.gz` URLs. ([#14243](https://github.com/craftcms/cms/issues/14243))
- Relation fields’ element query params now factor in the element query’s target site(s). ([#14258](https://github.com/craftcms/cms/issues/14258), [#14348](https://github.com/craftcms/cms/issues/14348), [#14304](https://github.com/craftcms/cms/pull/14304))
- Element queries’ `level` param now supports passing an array which includes `null`. ([#14419](https://github.com/craftcms/cms/issues/14419))

### Extensibility
- Added `craft\services\ProjectConfig::EVENT_AFTER_WRITE_YAML_FILES`. ([#14365](https://github.com/craftcms/cms/discussions/14365))
- Added `craft\services\Relations::deleteLeftoverRelations()`. ([#13956](https://github.com/craftcms/cms/issues/13956))
- Added `craft\services\Search::shouldCallSearchElements()`. ([#14293](https://github.com/craftcms/cms/issues/14293))

### System
- Relations for fields that are no longer included in an element’s field layout are now deleted after element save. ([#13956](https://github.com/craftcms/cms/issues/13956))
- The Sendmail email transport type now uses the `sendmail_path` PHP ini setting by default. ([#14433](https://github.com/craftcms/cms/pull/14433))
- Composer installation commands suggested by the Plugin Store now include a minimum version constraint.
- Fixed a bug where it wasn’t possible to eager-load Matrix block revisions, or load them via GraphQL. ([#14448](https://github.com/craftcms/cms/issues/14448))
- Fixed a PHP warning that could occur when publishing asset bundles on Dev Mode. ([#14455](https://github.com/craftcms/cms/pull/14455))
- Fixed a bug where the Updates utility and Updates widget weren’t handling update check failures.
- Updated Twig to 3.8.

## 4.7.4 - 2024-02-22

- The Plugin Store now shows “Tested on Cloud” and “Supports GraphQL” labels for plugins when appropriate.
- Fixed a JavaScript error that could occur when switching between asset sources, if the user had permission to upload assets to some but not others. ([#14403](https://github.com/craftcms/cms/issues/14403))
- Fixed a bug where setting `showSiteMenu` to `true` on element index templates was being treated as `'auto'`. ([#14404](https://github.com/craftcms/cms/pull/14404))
- Fixed a SQL error that occurred when setting the Max Length of a URL field beyond what’s allowed by a `varchar` column. ([#14408](https://github.com/craftcms/cms/issues/14408))
- Fixed a bug where `data-target` and `data-reverse-target` attributes weren’t getting properly namespaced if they included a class name selector.
- Fixed the type annotation for `craft\behaviors\CustomFieldBehavior::$fieldHandles`. ([#14421](https://github.com/craftcms/cms/issues/14421))
- Fixed a bug where the <kbd>Shift</kbd> + <kbd>Spacebar</kbd> keyboard shortcut for previewing assets was only working for the first selected asset, rather than the focused one.
- Fixed a JavaScript error that could occur when previewing an image.
- Fixed a bug where PHP errors that occurred during bootstrap weren’t getting logged to `stderr` for console requests. ([#14424](https://github.com/craftcms/cms/pull/14424))
- Fixed a bug where disabled elements and drafts weren’t automatically getting propagated to newly-created sites, when appropriate.
- Fixed a bug where the selected tab wasn’t being remembered when switching sites on element edit pages. ([#4018](https://github.com/craftcms/cms/issues/4018))

## 4.7.3 - 2024-02-15

- Updated the bundled `composer.phar` to Composer 2.7.1. ([CVE-2024-24821](https://github.com/advisories/GHSA-7c6p-848j-wh5h))
- Fixed a bug where read/write splitting was always getting disabled for GraphQL POST requests. ([#14324](https://github.com/craftcms/cms/issues/14324))
- Fixed a bug where GraphQL schema edit pages could include empty category headings.
- Fixed a bug where asset slideouts weren’t showing validation errors on the Filename field. ([#14329](https://github.com/craftcms/cms/issues/14329))
- Fixed a bug where element slideouts would open when long-pressing on an element’s “Remove” button within an element select input.
- Fixed a bug where relations weren’t getting deleted when an element was deleted for a site. ([#14347](https://github.com/craftcms/cms/issues/14347))
- Fixed a SQL error that occurred when saving a Number field with Decimals set to more than 30 on MySQL. ([#14370](https://github.com/craftcms/cms/issues/14370))

## 4.7.2.1 - 2024-02-08

- Craft now requires Composer ^2.7.0. ([CVE-2024-24821](https://github.com/advisories/GHSA-7c6p-848j-wh5h))
- Fixed a PHP error that could occur when using Checkboxes or Multi-select fields. ([#14326](https://github.com/craftcms/cms/issues/14326))

## 4.7.2 - 2024-02-07

- It’s now possible to select the temp asset volume within Assets fields, if the temp upload location includes a subpath. ([#14246](https://github.com/craftcms/cms/pull/14246))
- Fixed a bug where it wasn’t possible to set the “Formatting Locale” user preference back to “Same as language” once another value had been selected.
- Fixed a bug where layout components provided by disabled plugins weren’t getting omitted. ([#14236](https://github.com/craftcms/cms/pull/14236))
- Fixed a bug where “Move to the left” and “Move to the right” actions within field layout designers weren’t always getting enabled when they should, if a new tab was added.
- Fixed a bug where “Move to the left” and “Move to the right” actions within field layout designers were labelled incorrectly for right-to-left languages.
- Fixed a bug where users with “Create entries” permission but not “Delete entries” weren’t allowed to delete their own unpublished drafts. ([#14294](https://github.com/craftcms/cms/issues/14294))
- Fixed a bug where Yii-provided Chinese translations weren’t getting picked up in the control panel. ([#14287](https://github.com/craftcms/cms/issues/14287))
- Fixed an alignment bug with sortable structure views. ([#14299](https://github.com/craftcms/cms/issues/14299))

## 4.7.1 - 2024-01-29

- Unpublished drafts no longer show “Created at” or “Updated at” metadata values. ([#14204](https://github.com/craftcms/cms/issues/14204))
- Fixed a bug where empty Dropdown fields were getting treated as dirty when unchanged.
- Fixed a bug where Recent Entries widgets were getting mangled when new entries were created via Quick Post widgets.
- Fixed an error that occurred when adding a Dropdown field condition rule, if the field contained any optgroups. ([#14224](https://github.com/craftcms/cms/issues/14224))
- Fixed a bug where Dropdown field condition rules weren’t displaying `0` options. ([#14232](https://github.com/craftcms/cms/pull/14232))

## 4.7.0 - 2024-01-23

> [!NOTE]  
> Assets fields with “All” selected for the available sources will [no longer](https://github.com/craftcms/cms/issues/11405#issuecomment-1905998661) include the temp asset volume, if there is one.

### Content Management
- Admin tables now have sticky footers. ([#14149](https://github.com/craftcms/cms/pull/14149))

### Administration
- Added “Save and continue editing” actions to all core settings pages with full-page forms. ([#14168](https://github.com/craftcms/cms/discussions/14168))
- It’s no longer possible to select the temp asset volume within Assets fields. ([#11405](https://github.com/craftcms/cms/issues/11405), [#14141](https://github.com/craftcms/cms/pull/14141))
- Added the `utils/prune-orphaned-matrix-blocks` command. ([#14154](https://github.com/craftcms/cms/pull/14154))

### Extensibility
- Added `craft\base\ElementInterface::beforeDeleteForSite()`.
- Added `craft\base\ElementInterface::afterDeleteForSite()`.
- Added `craft\base\FieldInterface::beforeElementDeleteForSite()`.
- Added `craft\base\FieldInterface::afterElementDeleteForSite()`.

### System
- Reduced the system font file size, and prevented the flash of unstyled type for it. ([#13879](https://github.com/craftcms/cms/pull/13879))
- Log message timestamps are now set to the system time zone. ([#13341](https://github.com/craftcms/cms/issues/13341))
- Database backups no longer include data from the `phpsessions` table, if it exists. ([#13589](https://github.com/craftcms/cms/pull/13589))
- Selectize inputs now use the `auto_position` plugin. ([#14160](https://github.com/craftcms/cms/pull/14160))
- Fixed a bug where paths passed to `craft\web\CpScreenResponseBehavior::editUrl()` weren’t getting resolved to absolute URLs.
- Fixed a bug where deleting an entry for a site wasn’t propagating to Matrix blocks for that entry/site. ([#13948](https://github.com/craftcms/cms/issues/13948))

## 4.6.1 - 2024-01-16

- `craft\log\MonologTarget` instances are now created via `Craft::createObject()`. ([#13341](https://github.com/craftcms/cms/issues/13341))
- Fixed a bug where `craft\helpers\Db::prepareValueForDb()` wasn’t converting objects to arrays for JSON columns.
- Fixed a bug where Checkboxes, Multi-select, Dropdown, and Radio Buttons fields weren’t displaying `0` options within element indexes or condition rules. ([#14127](https://github.com/craftcms/cms/issues/14127), [#14143](https://github.com/craftcms/cms/pull/14143))
- Fixed a bug where `craft\db\Migration::renameTable()` was renaming the table for the primary database connection, rather than the migration’s connection. ([#14131](https://github.com/craftcms/cms/issues/14131))
- Fixed a bug where `Craft.FormObserver` wasn’t working reliably for non-`<form>` containers.
- Fixed a bug where Selectize inputs were triggering autosaves, even when the value didn’t change.
- Fixed a bug where custom source labels weren’t getting translated. ([#14137](https://github.com/craftcms/cms/issues/14137))
- Fixed a bug where Dropdown columns within Table fields were loosing their options when the field was edited. ([#14134](https://github.com/craftcms/cms/issues/14134))

## 4.6.0 - 2024-01-09

### Content Management
- Added live conditional field support to asset edit pages, as well as asset, user, and tag slideouts. ([#14115](https://github.com/craftcms/cms/pull/14115))
- Added the “Country” field type. ([#13789](https://github.com/craftcms/cms/discussions/13789))
- It’s now possible to delete volume folders using the “Delete” asset action. ([#13086](https://github.com/craftcms/cms/discussions/13086))
- Date range condition rules are now inclusive of their end dates. ([#13435](https://github.com/craftcms/cms/issues/13435))
- Custom field condition rules now show their field handles, for users with the “Show field handles in edit forms” preference enabled. ([#13300](https://github.com/craftcms/cms/pull/13300))
- Element conditions now include condition rules for fields with duplicate names, for users with the “Show field handles in edit forms” preference enabled. ([#13300](https://github.com/craftcms/cms/pull/13300))
- Improved element search performance. ([#14055](https://github.com/craftcms/cms/pull/14055))
- Improved the performance of large editable tables. ([#13852](https://github.com/craftcms/cms/issues/13852))

### Administration
- Edit Field pages now have a “Save and add another” action. ([#13865](https://github.com/craftcms/cms/discussions/13865))
- Added the `disabledUtilities` config setting. ([#14044](https://github.com/craftcms/cms/discussions/14044))
- Added the `showFirstAndLastNameFields` config setting. ([#14097](https://github.com/craftcms/cms/pull/14097))
- `resave` commands now pass an empty string (`''`) to fields’ `normalizeValue()` methods when `--to` is set to `:empty:`. ([#13951](https://github.com/craftcms/cms/issues/13951))
- The `sections/create` command now supports `--name`, `--handle`, `--type`, `--no-versioning`, `--uri-format`, and `--template` options, and can now be run non-interactively. ([#13864](https://github.com/craftcms/cms/discussions/13864))
- The `index-assets/one` and `index-assets/all` commands now accept a `--delete-empty-folders` option. ([#13947](https://github.com/craftcms/cms/discussions/13947))

### Extensibility
- Added partial support for field types storing data in JSON columns (excluding MariaDB). ([#13916](https://github.com/craftcms/cms/issues/13916))
- Added `craft\base\conditions\ConditionRuleInterface::getLabelHint()`.
- Added `craft\helpers\AdminTable::moveToPage()`. ([#14051](https://github.com/craftcms/cms/pull/14051))
- Added `craft\helpers\App::dbMutexConfig()`.
- Added `craft\helpers\ElementHelper::searchableAttributes()`.
- Added `craft\services\Elements::setElementUri()`.
- Added `craft\services\Elements::EVENT_SET_ELEMENT_URI`. ([#13930](https://github.com/craftcms/cms/discussions/13930))
- Added `craft\services\Search::createDbQuery()`.
- `craft\base\MemoizableArray` now supports passing a normalizer method to the constructor, which will be lazily applied to each array item once, only if returned by `all()` or `firstWhere()`. ([#14104](https://github.com/craftcms/cms/pull/14104))
- `craft\elements\actions\DeleteAssets` is no longer deprecated.
- `craft\helpers\ArrayHelper::firstWhere()` now has a `$valueKey` argument, which can be passed a variable by reference that should be set to the resulting value’s key in the array.
- Deprecated `craft\helpers\App::mutexConfig()`.
- Added `Craft.FormObserver`. ([#14114](https://github.com/craftcms/cms/pull/14114))
- Admin tables now have `footerActions`, `moveToPageAction`, `onCellClicked`, `onCellDoubleClicked`, `onRowClicked`, `onRowDoubleClicked`, and `paginatedReorderAction` settings. ([#14051](https://github.com/craftcms/cms/pull/14051))

### System
- “Updating search indexes” jobs are no longer queued when saving elements with change tracking enabled, if no searchable fields or attributes were changed. ([#13917](https://github.com/craftcms/cms/issues/13917))
- `queue/get-job-info` action requests no longer create a mutex lock.
- The `mutex` driver is now set to `yii\mutex\MysqlMutex` or `yii\mutex\PgsqlMutex` by default, once again. ([#14102](https://github.com/craftcms/cms/pull/14102))

## 4.5.14 - 2024-01-02

- Improved the performance of input namespacing.
- The Licensing Issues alert now includes a “Refresh” button. ([#14080](https://github.com/craftcms/cms/pull/14080))
- `relatedToAssets`, `relatedToCategories`, `relatedToEntries`, `relatedToTags`, and `relatedToUsers` are now reserved user field handles. ([#14075](https://github.com/craftcms/cms/issues/14075))
- `craft\services\Security::$sensitiveKeywords` is no longer case-sensitive. ([#14064](https://github.com/craftcms/cms/discussions/14064))
- Fixed a bug where the `index-assets/cleanup` command accepted `--cache-remote-images`, `--create-missing-assets`, and `--delete-missing-assets` options, even though they didn’t do anything.
- Fixed a bug where automatically-created relations could be lost when a new site was added to an entry. ([#14065](https://github.com/craftcms/cms/issues/14065))
- Fixed a bug where `craft\web\Request::getIsPreview()` was returning `true` for requests with expired tokens. ([#14066](https://github.com/craftcms/cms/discussions/14066))
- Fixed a bug where asset conflict resolution modals were closing prematurely if there were multiple conflicts. ([#14045](https://github.com/craftcms/cms/issues/14045))
- Fixed a bug where meta fields weren’t showing change indicators.
- Fixed a bug where the `index-assets/one` command was overly-destructive when run with a subpath and the `--delete-missing-assets` option. ([#14087](https://github.com/craftcms/cms/issues/14087))
- Fixed a privilege escalation vulnerability.

## 4.5.13 - 2023-12-15

- Address fields now have the appropriate `autocomplete` values when editing an address that belongs to the current user. ([#13938](https://github.com/craftcms/cms/pull/13938))
- The `|markdown` and `|md` filters now accept an `encode` argument, which can be set to `true` to HTML-encode the content before parsing it as Markdown.
- Added the `pre-encoded` Markdown flavor, which can be used when the content has already been HTML-encoded.
- Added `craft\elements\Address::getBelongsToCurrentUser()`.
- Fixed a bug where `{% namespace %}` tags weren’t respecting namespaces set to `0`. ([#13943](https://github.com/craftcms/cms/issues/13943))
- Fixed an error that could occur when using a custom asset uploader. ([#14029](https://github.com/craftcms/cms/pull/14029))
- Fixed an error that could occur when saving an asset using `SCENARIO_CREATE`, if `Asset::$tempFilePath` wasn’t set. ([#14041](https://github.com/craftcms/cms/pull/14041))
- Fixed a bug where some HTML entities within Tip and Warning field layout elements colud get double-encoded. ([#13959](https://github.com/craftcms/cms/issues/13959))
- Fixed an infinite recursion bug. ([#14033](https://github.com/craftcms/cms/issues/14033))

## 4.5.12 - 2023-12-12

- It’s no longer possible to dismiss asset conflict resolution modals by pressing <kbd>Esc</kbd> or clicking outside of the modal. ([#14002](https://github.com/craftcms/cms/issues/14002))
- Improved performance for sites with lots of custom fields in non-global contexts. ([#13992](https://github.com/craftcms/cms/issues/13992))
- Username, Full Name, and Email fields now have the appropriate `autocomplete` values when editing the current user. ([#13941](https://github.com/craftcms/cms/pull/13941))
- Queue job info is now broadcasted to other browser tabs opened to the same control panel. ([#13990](https://github.com/craftcms/cms/issues/13990))
- Volumes’ Asset Filesystem settings now list filesystems that are already selected by another volume, as disabled options. ([#14004](https://github.com/craftcms/cms/pull/14004))
- Added `craft\db\Connection::onAfterTransaction()`.
- Added `craft\errors\MutexException`. ([#13985](https://github.com/craftcms/cms/pull/13985))
- Added `craft\fieldlayoutelements\TextField::$inputType`. ([#13988](https://github.com/craftcms/cms/issues/13988))
- Deprecated `craft\fieldlayoutelements\TextField::$type`. `$inputType` should be used instead. ([#13988](https://github.com/craftcms/cms/issues/13988))
- Fixed a bug where WebP image transforms weren’t respecting transform quality settings. ([#13998](https://github.com/craftcms/cms/issues/13998))
- Fixed a bug where `craft\base\ApplicationTrait::onAfterRequest()` callbacks weren’t necessarily triggered if an `EVENT_AFTER_REQUEST` handler got in the way.
- Fixed a bug where keyboard shortcuts could stop working. ([#14011](https://github.com/craftcms/cms/issues/14011))
- Fixed a bug where the `craft\services\Elements::EVENT_AUTHORIZE_VIEW` event wasn’t always triggered when editing elements. ([#13981](https://github.com/craftcms/cms/issues/13981))
- Fixed a bug that prevented Live Preview from opening for edited entries, when the `autosaveDrafts` config setting was disabled. ([#13921](https://github.com/craftcms/cms/issues/13921))
- Fixed a bug where JavaScript-based slug generation wasn’t working consistently with PHP. ([#13971](https://github.com/craftcms/cms/pull/13971))
- Fixed a bug where asset upload failure notifications could be ambiguous if a server connection issue occurred. ([#14003](https://github.com/craftcms/cms/issues/14003))
- Fixed a “Changes to the project config are not possible while in read-only mode.” error that could occur when adimn changes were disallowed. ([#14018](https://github.com/craftcms/cms/issues/14018))
- Fixed a bug where it was possible to create a volume without a filesystem selected. ([#14004](https://github.com/craftcms/cms/pull/14004))
- Fixed a privilege escalation vulnerability.

## 4.5.11.1 - 2023-11-23

- Fixed a PHP error that occurred due to a conflict with psr/log v3. ([#13963](https://github.com/craftcms/cms/issues/13963))

## 4.5.11 - 2023-11-16

- Date fields with “Show Time Zone” enabled will now remember IANA-formatted time zones set via GraphQL. ([#13893](https://github.com/craftcms/cms/issues/13893))
- Added `craft\gql\types\DateTime::$setToSystemTimeZone`.
- `craft\gql\types\DateTime` now supports JSON-encoded objects with `date`, `time`, and `timezone` keys.
- `craft\web\Response::setCacheHeaders()` now includes the `public` directive in the `Cache-Control` header. ([#13922](https://github.com/craftcms/cms/pull/13922))
- Fixed a bug where <kbd>↑</kbd> and <kbd>↓</kbd> key presses would set focus to disabled menu options. ([#13911](https://github.com/craftcms/cms/issues/13911))
- Fixed a bug where elements’ `localized` GraphQL field wasn’t returning any results for drafts or revisions. ([#13924](https://github.com/craftcms/cms/issues/13924))
- Fixed a bug where dropdown option labels within Table fields weren’t getting translated. ([#13914](https://github.com/craftcms/cms/issues/13914))
- Fixed a bug where “Updating search indexes” jobs were getting queued for Matrix block revisions. ([#13917](https://github.com/craftcms/cms/issues/13917))
- Fixed a bug where control panel resources weren’t getting published on demand. ([#13935](https://github.com/craftcms/cms/issues/13935))
- Fixed privilege escalation vulnerabilities.

## 4.5.10 - 2023-11-07

- Added the `db/drop-table-prefix` command.
- Top-level disabled related/nested elements are now included in “Extended” element exports. ([#13496](https://github.com/craftcms/cms/issues/13496))
- Related element validation is no longer recursive. ([#13904](https://github.com/craftcms/cms/issues/13904))
- Addresses’ owner elements are now automatically set on them during initialization, if they were queried with the `owner` address query param.
- Entry Title fields are no longer shown when “Show the Title field” is disabled and there’s a validation error on the `title` attribute. ([#13876](https://github.com/craftcms/cms/issues/13876))
- Improved the reliability of image dimension detection. ([#13886](https://github.com/craftcms/cms/pull/13886))
- The default backup command for PostgreSQL no longer passes in `--column-inserts` to `pg_dump`.
- Log contexts now include the environment name. ([#13882](https://github.com/craftcms/cms/pull/13882))
- Added `craft\web\AssetManager::$cacheSourcePaths`.
- Fixed a bug where disclosure menus could be positioned off-screen on mobile.
- Fixed a bug where element edit pages could show a context menu when it wasn’t necessary.
- Fixed a bug where the “Delete entry for this site” action wasn’t deleting the canonical entry for the selected site, when editing a provisional draft.
- Fixed an error that occurred when cropping an image that was missing its dimension info. ([#13884](https://github.com/craftcms/cms/issues/13884))
- Fixed an error that occurred if a filesystem didn’t have any settings. ([#13883](https://github.com/craftcms/cms/pull/13883))
- Fixed a bug where related element validation wansn’t ensuring that related elements were loaded in the same site as the source element when possible. ([#13907](https://github.com/craftcms/cms/issues/13907))
- Fixed a bug where sites weren’t always getting queried in the same order, if multiple sites’ `sortOrder` values were the same. ([#13896](https://github.com/craftcms/cms/issues/13896))

## 4.5.9 - 2023-10-23

- Fixed a bug where it was possible to change the status for entries that didn’t show the Status field, via bulk editing. ([#13854](https://github.com/craftcms/cms/issues/13854))
- Fixed a PHP error that could occur when editing elements via slideouts. ([#13867](https://github.com/craftcms/cms/issues/13867))
- Fixed an error that could occur if no `storage/` folder existed.

## 4.5.8 - 2023-10-20

- Improved the styling and accessibility of revision pages. ([#13857](https://github.com/craftcms/cms/pull/13857), [#13850](https://github.com/craftcms/cms/issues/13850))
- Added the `focalPoint` argument to asset save mutations. ([#13846](https://github.com/craftcms/cms/discussions/13846))
- The `up` command now accepts a `--no-backup` option.
- `{% cache %}` tags now store any `<meta>` tags registered with `yii\web\View::registerMetaTag()`. ([#13832](https://github.com/craftcms/cms/issues/13832))
- Added `craft\errors\ExitException`.
- Added `craft\web\View::startMetaTagBuffer()`.
- Added `craft\web\View::clearMetaTagBuffer()`.
- Added support for modifying the application config via a global `craft_modify_app_config()` function. ([#13855](https://github.com/craftcms/cms/pull/13855))
- Fixed a bug where `{% exit %}` tags without a status code weren’t outputting any HTML that had already been output in the template. ([#13848](https://github.com/craftcms/cms/discussions/13848))
- Fixed a bug where it wasn’t possible to Ctrl/Command-click on multiple elements to select them. ([#13853](https://github.com/craftcms/cms/issues/13853))

## 4.5.7 - 2023-10-17

- Field containers are no longer focusable unless a corresponding validation message is clicked on. ([#13782](https://github.com/craftcms/cms/issues/13782))
- Improved element save performance.
- Added `pgpassword` and `pwd` to the list of keywords that Craft will look for when determining whether a value is sensitive and should be redacted from logs, etc.
- Added `craft\events\DefineCompatibleFieldTypesEvent`.
- Added `craft\services\Fields::EVENT_DEFINE_COMPATIBLE_FIELD_TYPES`. ([#13793](https://github.com/craftcms/cms/discussions/13793))
- Added `craft\web\assets\inputmask\InputmaskAsset`.
- `craft\web\Request::accepts()` now supports wildcard (e.g. `application/*`). ([#13759](https://github.com/craftcms/cms/issues/13759))
- `Craft.ElementEditor` instances are now configured with an `elementId` setting, which is kept up-to-date when a provisional draft is created. ([#13795](https://github.com/craftcms/cms/discussions/13795))
- Added `Garnish.isPrimaryClick()`.
- Fixed a bug where relational fields’ element selector modals weren’t always getting set to the correct site per the field’s “Relate entries from a specific site?” setting. ([#13750](https://github.com/craftcms/cms/issues/13750))
- Fixed a bug where Dropdown fields weren’t visible when viewing revisions and other static forms. ([#13753](https://github.com/craftcms/cms/issues/13753), [craftcms/commerce#3270](https://github.com/craftcms/commerce/issues/3270))
- Fixed a bug where the `defaultDirMode` config setting wasn’t being respected when the `storage/runtime/` and `storage/logs/` folders were created. ([#13756](https://github.com/craftcms/cms/issues/13756))
- Fixed a bug where the “Save and continue editing” action wasn’t working on Edit User pages if they contained a Money field. ([#13760](https://github.com/craftcms/cms/issues/13760))
- Fixed a bug where relational fields’ validation messages weren’t using the actual field name. ([#13807](https://github.com/craftcms/cms/issues/13807))
- Fixed a bug where element editor slideouts were appearing behind element selector modals within Live Preview. ([#13798](https://github.com/craftcms/cms/issues/13798))
- Fixed a bug where element URIs weren’t getting updated for propagated sites automatically. ([#13812](https://github.com/craftcms/cms/issues/13812))
- Fixed a bug where dropdown input labels could overflow out of their containers. ([#13817](https://github.com/craftcms/cms/issues/13817))
- Fixed a bug where the `transformGifs` and `transformSvgs` config settings weren’t always being respected when using `@transform` GraphQL directives. ([#13808](https://github.com/craftcms/cms/issues/13808))
- Fixed a bug where Composer operations were sorting `require` packages differently than how Composer does it natively, when `config.sort-packages` was set to `true`. ([#13806](https://github.com/craftcms/cms/issues/13806))
- Fixed a MySQL error that could occur when creating a Plain Text field with a high charcter limit. ([#13781](https://github.com/craftcms/cms/pull/13781))
- Fixed a bug where entries weren’t always being treated as live for View and Preview buttons, when editing a non-primary site. ([#13746](https://github.com/craftcms/cms/issues/13746))
- Fixed a bug where Ctrl-clicks were being treated as primary clicks in some browsers. ([#13823](https://github.com/craftcms/cms/issues/13823))
- Fixed a bug where some language options were showing “false” hints. ([#13837](https://github.com/craftcms/cms/issues/13837))
- Fixed a bug where Craft was tracking changes to elements when they were being resaved. ([#13761](https://github.com/craftcms/cms/issues/13761))
- Fixed a bug where sensitive keywords weren’t getting redacted from log contexts.
- Fixed RCE vulnerabilities.

## 4.5.6.1 - 2023-09-27

- Crossdomain JavaScript resources are now loaded via a proxy action.
- Fixed JavaScript errors that could occur after loading new UI components over Ajax. ([#13751](https://github.com/craftcms/cms/issues/13751))

## 4.5.6 - 2023-09-26

- When slideouts are opened within Live Preview, they now slide up over the editor pane, rather than covering the preview pane. ([#13739](https://github.com/craftcms/cms/pull/13739))
- Cross-site validation now only involves fields which were actually modified in the element save. ([#13675](https://github.com/craftcms/cms/discussions/13675))
- Row headings within Table fields now get statically translated. ([#13703](https://github.com/craftcms/cms/discussions/13703))
- Element condition settings within field layout components now display a warning if the `autosaveDrafts` config setting is disabled. ([#12348](https://github.com/craftcms/cms/issues/12348))
- Added the `resave/addresses` command. ([#13720](https://github.com/craftcms/cms/discussions/13720))
- The `resave/matrix-blocks` command now supports an `--owner-id` option.
- Added `craft\helpers\App::phpExecutable()`.
- `craft\helpers\Component::createComponent()` now filters out `as X` and `on X` keys from the component config.
- `craft\services\Announcements::push()` now has an `$adminsOnly` argument. ([#13728](https://github.com/craftcms/cms/discussions/13728))
- `Craft.appendHeadHtml()` and `appendBodyHtml()` now load external scripts asynchronously, and return promises.
- Improved the reliability of Composer operations when PHP is running via FastCGI. ([#13681](https://github.com/craftcms/cms/issues/13681))
- Fixed a bug where it wasn’t always possible to create new entries from custom sources which were limited to one section.
- Fixed a bug where relational fields weren’t factoring in cross-site elements when enforcing their “Min Relations”, “Max Relations”, and “Validate related entries” settings. ([#13699](https://github.com/craftcms/cms/issues/13699))
- Fixed a bug where pagination wasn’t working for admin tables, if the `onQueryParams` callback method wasn’t set. ([#13677](https://github.com/craftcms/cms/issues/13677))
- Fixed a bug where relations within Matrix blocks weren’t getting restored when restoring a revision’s content. ([#13626](https://github.com/craftcms/cms/issues/13626))
- Fixed a bug where the filesystem and volume-creation slideouts could keep reappearing if canceled. ([#13707](https://github.com/craftcms/cms/issues/13707))
- Fixed an error that could occur when reattempting to update to Craft 4.5. ([#13714](https://github.com/craftcms/cms/issues/13714))
- Fixed a bug where date and time inputs could be parsed incorrectly, if the user’s formatting locale wasn’t explicitly set, or it changed between page load and form submit. ([#13731](https://github.com/craftcms/cms/issues/13731))
- Fixed JavaScript errors that could occur when control panel resources were being loaded from a different domain. ([#13715](https://github.com/craftcms/cms/issues/13715))
- Fixed a PHP error that occurred if the `CRAFT_DOTENV_PATH` environment variable was set, or a console command was executed with the `--dotenvPath` option. ([#13725](https://github.com/craftcms/cms/issues/13725))
- Fixed a bug where long element titles weren’t always getting truncated in the control panel. ([#13718](https://github.com/craftcms/cms/issues/13718))
- Fixed a bug where checkboxes could be preselected if they had an empty value. ([#13710](https://github.com/craftcms/cms/issues/13710))
- Fixed a bug where links in validation summaries weren’t working if the offending field was in a collapsed Matrix block. ([#13708](https://github.com/craftcms/cms/issues/13708))
- Fixed a bug where cross-site validation could apply even if `craft\services\Elements::saveElement()` was called with `$runValidation` set to `false`.
- Fixed some wonky scrolling behavior on pages where the details pane was shorter than the content pane. ([#13637](https://github.com/craftcms/cms/issues/13637))
- Fixed a division by zero error. ([#13712](https://github.com/craftcms/cms/issues/13712))
- Fixed an RCE vulnerability.

## 4.5.5 - 2023-09-14

- Added the `maxGraphqlBatchSize` config setting. ([#13693](https://github.com/craftcms/cms/issues/13693))
- Fixed a bug where page sidebars and detail panes weren’t scrolling properly if their height was greater than the main content pane height. ([#13637](https://github.com/craftcms/cms/issues/13637))
- Fixed an error that could occur when changing a field’s type, if a backup table needed to be created to store the old field values. ([#13669](https://github.com/craftcms/cms/issues/13669))
- Fixed a bug where it wasn’t possible to save blank Dropdown values. ([#13695](https://github.com/craftcms/cms/issues/13695))

## 4.5.4 - 2023-09-12

- Added the `@stripTags` and `@trim` GraphQL directives. ([#9971](https://github.com/craftcms/cms/discussions/9971))
- Added `SK` to the list of keywords that Craft will look for when determining whether a value is sensitive and should be redacted from logs, etc. ([#3619](https://github.com/craftcms/cms/issues/3619))
- Improved the scrolling behavior for page sidebars and detail panes. ([#13637](https://github.com/craftcms/cms/issues/13637))
- Filesystem edit pages now have a “Save and continue editing” alternative submit action, and the <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>S</kbd> keyboard shortcut now redirects back to the edit page. ([#13658](https://github.com/craftcms/cms/pull/13658)) 
- Attribute labels are no longer surrounded by asterisks for front-end validation messages. ([#13640](https://github.com/craftcms/cms/issues/13640))
- The `|replace` Twig filter now has a `regex` argument, which can be set to `false` to disable regular expression parsing. ([#13642](https://github.com/craftcms/cms/discussions/13642))
- Added `craft\events\DefineUserGroupsEvent`.
- Added `craft\services\Users::EVENT_DEFINE_DEFAULT_USER_GROUPS`. ([#12283](https://github.com/craftcms/cms/issues/12283))
- Added `craft\services\Users::getDefaultUserGroups()`.
- `craft\events\UserAssignGroupEvent` now extends `DefineUserGroupsEvent`, giving it a new `$userGroups` property.
- `craft\helpers\DateTimeHelper::toDateTime()` now supports `DateTimeImmutable` values. ([#13656](https://github.com/craftcms/cms/issues/13656))
- `craft\web\Response::setCacheHeaders()` no longer includes `public` in the `Cache-Control` header when `$overwrite` is `false`. ([#13676](https://github.com/craftcms/cms/issues/13676))
- Deprecated `craft\events\UserAssignGroupEvent`. `DefineUserGroupsEvent` should be used instead.
- Fixed a bug where the “Active Trials” section in the Plugin Store cart modal wasn’t listing plugins in trial. ([#13661](https://github.com/craftcms/cms/issues/13661))
- Fixed a bug where changed fields weren’t being tracked properly when applying a draft for a multi-site entry.
- `craft\services\Elements::duplicateElement()` now supports passing a `siteAttributes` array to the `$attributes` argument, for setting site-specific attributes.
- Fixed an error that could occur when executing a GraphQL query with fragments. ([#13622](https://github.com/craftcms/cms/issues/13622))
- Fixed a bug where addresses queried via GraphQL had a `photo` field.
- Fixed a bug where boolean environment variables weren’t always getting the correct value indicators within Selectize fields. ([#13613](https://github.com/craftcms/cms/issues/13613))
- Fixed a bug where some system icons were getting black backgrounds when displayed within Vue apps. ([#13632](https://github.com/craftcms/cms/issues/13632))
- Fixed a bug where the user and address field layouts were getting new UUIDs each time they were saved. ([#13588](https://github.com/craftcms/cms/issues/13588))
- Fixed an error that could occur if a Money field was set to an array without a `value` key. ([#13648](https://github.com/craftcms/cms/pull/13648))
- Fixed a bug where relations weren’t getting restored when restoring a revision’s content. ([#13626](https://github.com/craftcms/cms/issues/13626))
- Fixed a bug where “Entry Type” fields were showing `typeId` labels for admin users with “Show field handles in edit forms” enabled. ([#13627](https://github.com/craftcms/cms/issues/13627))
- Fixed a bug where Lightswitch fields with only one label weren’t getting the correct padding on the unlabelled side of the container. ([#13629](https://github.com/craftcms/cms/issues/13629))
- Fixed a bug where the `transformGifs` and `transformSvgs` config settings weren’t always being respected. ([#13624](https://github.com/craftcms/cms/issues/13624), [#13635](https://github.com/craftcms/cms/issues/13635))
- Fixed a bug where filesystems weren’t requiring the “Base URL” setting to be set. ([#13657](https://github.com/craftcms/cms/pull/13657))
- Fixed a bug where applying a draft could redirect to the “Current” revision on a different site, if a new site had been added on the draft. ([#13668](https://github.com/craftcms/cms/pull/13668))
- Fixed an error that could occur when changing a field’s type, if a backup table needed to be created to store the old field values. ([#13669](https://github.com/craftcms/cms/issues/13669))
- Fixed a bug where Matrix blocks that were initially created for a newly-added site within a draft could be lost when applying the draft. ([#13670](https://github.com/craftcms/cms/pull/13670))
- Fixed a bug where `fill` transform properties weren’t being passed along by `craft\elements\Asset::getUrlsBySize()` and `getSrcset()`. ([#13650](https://github.com/craftcms/cms/issues/13650))
- Fixed a bug where SVG asset icons weren’t visible in Safari. ([#13685](https://github.com/craftcms/cms/issues/13685))
- Fixed two RCE vulnerabilities.

## 4.5.3 - 2023-08-29

- Fixed a bug where custom fields could be marked as changed within element editor slideouts, if they modified their input values on initialization. ([craftcms/ckeditor#128](https://github.com/craftcms/ckeditor/issues/128))
- Fixed a bug where elements were getting saved a second time after being converted to a provisional draft within a slideout. ([#13604](https://github.com/craftcms/cms/issues/13604))
- Fixed a JavaScript error. ([#13605](https://github.com/craftcms/cms/issues/13605))
- Fixed support for storing PHP session info in the database on PostgreSQL.
- Fixed a bug where search inputs within element selector modals weren’t getting focus rings.
- Fixed a bug where boolean menu inputs were initially treating `null` values as `true`.
- Fixed a bug where boolean menu inputs weren’t toggling other fields. ([#13613](https://github.com/craftcms/cms/issues/13613))
- Fixed a bug where `Craft.namespaceId()` wasn’t working properly if the namespace ended in a `]` character.
- Fixed a bug where the `|replace` Twig filter wasn’t identifying regular expressions that contained escaped slashes. ([#13618](https://github.com/craftcms/cms/issues/13618))
- Fixed a bug where entries that were cloned from a provisional draft weren’t getting propagated to other sites initially. ([#13599](https://github.com/craftcms/cms/issues/13599))
- Fixed an error that could occur when cloning a multi-site provisional draft that contained nested Matrix/Neo/Super Table blocks.

## 4.5.2 - 2023-08-24

- `craft\helpers\UrlHelper::buildQuery()` is no longer deprecated. ([#12796](https://github.com/craftcms/cms/issues/12796))
- Fixed a bug where control panel notifications weren’t always closing automatically if they contained interactive elements. ([#13591](https://github.com/craftcms/cms/issues/13591))
- Fixed a bug where default user avatars were getting black backgrounds when displayed within Vue apps. ([#13597](https://github.com/craftcms/cms/issues/13597))
- Fixed a bug where the Username and Email fields weren’t required for public registrations forms, if “Deactivate users by default” was enabled. ([#13596](https://github.com/craftcms/cms/issues/13596))
- Fixed a bug where switching sites when editing a global site wasn’t working. ([#12796](https://github.com/craftcms/cms/issues/12796), [#13603](https://github.com/craftcms/cms/issues/13603))
- Fixed a bug where page shortcuts weren’t working after a related element was saved via a slideout. ([#13601](https://github.com/craftcms/cms/issues/13601))

## 4.5.1 - 2023-08-23

- Control panel notifications no longer block page keyboard shortcuts. ([#13591](https://github.com/craftcms/cms/issues/13591))
- `Garnish.uiLayerManager.addLayer()` now supports a `bubble` option, which allows non-matching keyboard shortcuts to bubble up to the parent layer.
- Fixed an error that could occur when Craft was performing a Composer operation, if no `HOME` environment variable was set for PHP. ([#13590](https://github.com/craftcms/cms/issues/13590))
- Fixed a bug where `craft\fields\Matrix::serializeValue()` was setting `fields` keys to a closure. ([#13592](https://github.com/craftcms/cms/issues/13592))
- Fixed a bug where time values weren’t saving properly for Greek locales. ([#9942](https://github.com/craftcms/cms/issues/9942))
- Fixed a bug where the “Status” lightswitch would always be enabled on edit pages for single-site elements. ([#13595](https://github.com/craftcms/cms/issues/13595))

## 4.5.0 - 2023-08-22

### Content Management
- Entry and category edit pages now show other authors who are currently editing the same element. ([#13420](https://github.com/craftcms/cms/pull/13420))
- Entry and category edit pages now display a notification when the element has been saved by another author. ([#13420](https://github.com/craftcms/cms/pull/13420))
- Entry and category edit pages now display a validation error summary at the top of the page, including a mention of errors from other sites. ([#11569](https://github.com/craftcms/cms/issues/11569), [#12125](https://github.com/craftcms/cms/pull/12125))
- Table fields can now have a “Row heading” column. ([#13231](https://github.com/craftcms/cms/pull/13231))
- Table fields now have a “Static Rows” setting. ([#13231](https://github.com/craftcms/cms/pull/13231))
- Table fields no longer show a heading row, if all heading values are blank. ([#13231](https://github.com/craftcms/cms/pull/13231))
- Element slideouts now show their sidebar content full-screen for elements without a field layout, rather than having an empty body. ([#13056](https://github.com/craftcms/cms/pull/13056), [#13053](https://github.com/craftcms/cms/issues/13053))
- Relational fields no longer track the previously-selected element(s) when something outside the field is clicked on. ([#13123](https://github.com/craftcms/cms/issues/13123))
- Element indexes now use field layouts’ overridden field labels, if all field layouts associated with an element source use the same label. ([#8903](https://github.com/craftcms/cms/discussions/8903))
- Element indexes now track souces’ filters in the URL, so they can be sharable and persisted when navigating back to the index page via the browser history. ([#13499](https://github.com/craftcms/cms/pull/13499))
- Users’ default thumbnails are now the user initials over a unique color gradient. ([#13511](https://github.com/craftcms/cms/pull/13511))
- Improved the styling and max height of Selectize inputs. ([#13065](https://github.com/craftcms/cms/discussions/13065), [#13176](https://github.com/craftcms/cms/pull/13176))
- Selectize inputs now support click-and-drag selection. ([#13273](https://github.com/craftcms/cms/discussions/13273))
- Selectize single-select inputs now automatically select the current value on focus. ([#13273](https://github.com/craftcms/cms/discussions/13273))
- It’s now possible to create new entries from entry select modals when a custom source is selected, if the source is configured to only show entries from one section. ([#11499](https://github.com/craftcms/cms/discussions/11499))
- The Entries index page now shows a primary “New entry” button when a custom source is selected, if the source is configured to only show entries from one section. ([#13390](https://github.com/craftcms/cms/discussions/13390))
- Invalid Dropdown fields now automatically select their default option and get marked as changed (if they have a default option). ([#13540](https://github.com/craftcms/cms/pull/13540))

### Accessibility
- Image assets’ thumbnails and `<img>` tags generated via `craft\element\Asset::getImg()` no longer use the assets’ titles as `alt` fallback values. ([#12854](https://github.com/craftcms/cms/pull/12854))
- Element index pages now have visually-hidden “Sources” headings for screen readers. ([#12961](https://github.com/craftcms/cms/pull/12961))
- Element metadata fields now have visually-hidden “Metadata” headings for screen readers. ([#12961](https://github.com/craftcms/cms/pull/12961))
- Structure elements within element indexes now convey their levels to screen readers. ([#13020](https://github.com/craftcms/cms/pull/13020))
- Non-image asset thumbnails in the control panel now have `alt` attributes set to the file extension. ([#12724](https://github.com/craftcms/cms/pull/12724))
- Improved copy-text buttons for screen readers. ([#13073](https://github.com/craftcms/cms/pull/13073))
- Improved the contrast of asset file type icons. ([#13262](https://github.com/craftcms/cms/pull/13262))

### Administration
- Added the “Slug Translation Method” setting to entry types. ([#8962](https://github.com/craftcms/cms/discussions/8962), [#13291](https://github.com/craftcms/cms/pull/13291))
- Added the “Show the Status field” setting to entry types. ([#12837](https://github.com/craftcms/cms/discussions/12837), [#13265](https://github.com/craftcms/cms/pull/13265))
- Added the `setup/cloud` command, which prepares a Craft install to be deployed to Craft Cloud.
- Added the `setup/message-tables` command, which can be run to set the project up for database-stored static translations via [DbMessageSource](https://www.yiiframework.com/doc/api/2.0/yii-i18n-dbmessagesource). ([#13542](https://github.com/craftcms/cms/pull/13542))
- Entry types created via the `entrify/global-set` command now have “Show the Status field” disabled by default. ([#12837](https://github.com/craftcms/cms/discussions/12837))
- Added the `defaultCountryCode` config setting. ([#13478](https://github.com/craftcms/cms/discussions/13478))
- Custom element sources can now be configured to only appear for certain sites. ([#13344](https://github.com/craftcms/cms/discussions/13344))
- The “My Account” page no longer shows a “Require a password reset on next login” checkbox.
- The Asset Indexes utility no longer shows the “Cache remote images” option on ephemeral environments. ([#13202](https://github.com/craftcms/cms/issues/13202))
- It’s now possible to configure UK addresses to show a “County” field. ([#13361](https://github.com/craftcms/cms/pull/13361))
- The “Login Page Logo” and “Site Icon” general settings’ image previews now have checkered backgrounds. ([#13210](https://github.com/craftcms/cms/discussions/13210), [#13229](https://github.com/craftcms/cms/pull/13229))
- Empty field layout tabs are no longer pruned out. ([#13132](https://github.com/craftcms/cms/issues/13132))
- `active`, `addresses`, `admin`, `email`, `friendlyName`, `locked`, `name`, `password`, `pending`, `suspended`, and `username` are now reserved user field handles. ([#13579](https://github.com/craftcms/cms/issues/13579))

### Development
- Added a new `_globals` global Twig variable for front-end templates, which can be used to store custom values in a global scope. ([#13050](https://github.com/craftcms/cms/pull/13050), [#12951](https://github.com/craftcms/cms/discussions/12951))
- The `|replace` Twig filter now supports passing in a hash with regular expression keys. ([#12956](https://github.com/craftcms/cms/issues/12956))
- `{% exit %}` tags now support passing a message after the status code. ([#13166](https://github.com/craftcms/cms/discussions/13166))
- Built-in element types’ GraphQL queries now support passing `null` to `relatedToAssets`, `relatedToEntries`, `relatedToUsers`, `relatedToCategories`, `relatedToTags`, and `relatedToAll` arguments. ([#7954](https://github.com/craftcms/cms/issues/7954))
- Elements now include custom field values when being iterated over, and when being merged. ([#13009](https://github.com/craftcms/cms/issues/13009))
- Dropdown and Radio Buttons fields now have a “Column Type” setting, which will be set to `varchar` for existing fields, and defaults to “Automatic” for new fields. ([#13025](https://github.com/craftcms/cms/pull/13025), [#12954](https://github.com/craftcms/cms/issues/12954))
- Successful `users/login` JSON responses now include information about the logged-in user. ([#13374](https://github.com/craftcms/cms/discussions/13374))

### Extensibility
- Filesystem types can now register custom file uploaders. ([#13313](https://github.com/craftcms/cms/pull/13313))
- When applying a draft, the canonical elements’ `getDirtyAttributes()` and `getDirtyFields()` methods now return the attribute names and field handles that were modified on the draft for save events. ([#12967](https://github.com/craftcms/cms/issues/12967))
- Admin tables can be configured to pass custom query params to the data endpoint. ([#13416](https://github.com/craftcms/cms/pull/13416))
- Admin tables can now be programatically reloaded. ([#13416](https://github.com/craftcms/cms/pull/13416))
- Admin table properties are now reactive. ([#13558](https://github.com/craftcms/cms/pull/13558), [#13520](https://github.com/craftcms/cms/discussions/13520))
- Native element sources can now define a `defaultFilter` key, which defines the default filter condition that should be applied when the source is selected. ([#13499](https://github.com/craftcms/cms/pull/13499))
- Added `craft\addresses\SubdivisionRepository`. ([#13361](https://github.com/craftcms/cms/pull/13361))
- Added `craft\base\Element::showStatusField()`. ([#13265](https://github.com/craftcms/cms/pull/13265))
- Added `craft\base\Element::thumbSvg()`. ([#13262](https://github.com/craftcms/cms/pull/13262))
- Added `craft\base\ElementInterface::getIsSlugTranslatable()`.
- Added `craft\base\ElementInterface::getSlugTranslationDescription()`.
- Added `craft\base\ElementInterface::getSlugTranslationKey()`.
- Added `craft\base\ElementInterface::getThumbHtml()`.
- Added `craft\base\ElementInterface::modifyCustomSource()`.
- Added `craft\base\ElementInterface::setDirtyFields()`.
- Added `craft\base\ElementInterface::setFieldValueFromRequest()`. ([#12935](https://github.com/craftcms/cms/issues/12935))
- Added `craft\base\FieldInterface::normalizeValueFromRequest()`. ([#12935](https://github.com/craftcms/cms/issues/12935))
- Added `craft\base\FieldLayoutProviderInterface`. ([#13250](https://github.com/craftcms/cms/pull/13250))
- Added `craft\base\FsInterface::getShowHasUrlSetting()`. ([#13224](https://github.com/craftcms/cms/pull/13224))
- Added `craft\base\FsInterface::getShowUrlSetting()`. ([#13224](https://github.com/craftcms/cms/pull/13224))
- Added `craft\base\FsTrait::$showHasUrlSetting`. ([#13224](https://github.com/craftcms/cms/pull/13224))
- Added `craft\base\FsTrait::$showUrlSetting`. ([#13224](https://github.com/craftcms/cms/pull/13224))
- Added `craft\behaviors\EventBehavior`. ([#13502](https://github.com/craftcms/cms/discussions/13502))
- Added `craft\controllers\AssetsControllerTrait`.
- Added `craft\elements\db\ElementQuery::EVENT_BEFORE_POPULATE_ELEMENT`.
- Added `craft\events\AssetBundleEvent`.
- Added `craft\events\DefineAddressSubdivisionsEvent`. ([#13361](https://github.com/craftcms/cms/pull/13361))
- Added `craft\events\MoveElementEvent::$action`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Added `craft\events\MoveElementEvent::$targetElementId`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Added `craft\events\MoveElementEvent::getTargetElement()`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Added `craft\gql\GqlEntityRegistry::getOrCreate()`. ([#13354](https://github.com/craftcms/cms/pull/13354))
- Added `craft\helpers\Assets::iconSvg()`.
- Added `craft\helpers\StringHelper::escapeShortcodes()`. ([#12935](https://github.com/craftcms/cms/issues/12935))
- Added `craft\helpers\StringHelper::unescapeShortcodes()`. ([#12935](https://github.com/craftcms/cms/issues/12935))
- Added `craft\models\FieldLayout::$provider`. ([#13250](https://github.com/craftcms/cms/pull/13250))
- Added `craft\services\Addresses::$formatter`, which can be used to override the default address formatter. ([#13242](https://github.com/craftcms/cms/pull/13242), [#12615](https://github.com/craftcms/cms/discussions/12615))
- Added `craft\services\Addresses::EVENT_DEFINE_ADDRESS_SUBDIVISIONS`. ([#13361](https://github.com/craftcms/cms/pull/13361))
- Added `craft\services\Addresses::defineAddressSubdivisions()`. ([#13361](https://github.com/craftcms/cms/pull/13361))
- Added `craft\services\Elements::collectCacheInfoForElement()`.
- Added `craft\services\Elements::getRecentActivity()`. ([#13420](https://github.com/craftcms/cms/pull/13420))
- Added `craft\services\Elements::trackActivity()`. ([#13420](https://github.com/craftcms/cms/pull/13420))
- Added `craft\services\ProjectConfig::$cacheDuration`. ([#13164](https://github.com/craftcms/cms/issues/13164))
- Added `craft\services\Structures::ACTION_APPEND`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Added `craft\services\Structures::ACTION_PLACE_AFTER`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Added `craft\services\Structures::ACTION_PLACE_BEFORE`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Added `craft\services\Structures::ACTION_PREPEND`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Added `craft\services\Structures::EVENT_AFTER_INSERT_ELEMENT`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Added `craft\services\Structures::EVENT_BEFORE_INSERT_ELEMENT`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Added `craft\web\Controller::EVENT_DEFINE_BEHAVIORS`. ([#13477](https://github.com/craftcms/cms/pull/13477))
- Added `craft\web\Controller::defineBehaviors()`. ([#13477](https://github.com/craftcms/cms/pull/13477))
- Added `craft\web\CpScreenResponseBehavior::$errorSummary`, `errorSummary()`, and `errorSummaryTemplate()`. ([#12125](https://github.com/craftcms/cms/pull/12125))
- Added `craft\web\CpScreenResponseBehavior::$pageSidebar`, `pageSidebar()`, and `pageSidebarTemplate()`. ([#13019](https://github.com/craftcms/cms/pull/13019), [#12795](https://github.com/craftcms/cms/issues/12795))
- Added `craft\web\CpScreenResponseBehavior::$slideoutBodyClass`.
- Added `craft\web\Response::$defaultFormatters`. ([#13541](https://github.com/craftcms/cms/pull/13541))
- Added `craft\web\View::EVENT_AFTER_REGISTER_ASSET_BUNDLE`.
- `craft\elements\actions\NewChild` is no longer triggerable on elements that have a `data-disallow-new-children` attribute. ([#13539](https://github.com/craftcms/cms/discussions/13539))
- `craft\elements\actions\SetStatus` is no longer triggerable on elements that have a `data-disallow-status` attribute.
- `craft\helpers\Cp::selectizeFieldHtml()`, `selectizeHtml()`, and `_includes/forms/selectize.twig` now support a `multi` param. ([#13176](https://github.com/craftcms/cms/pull/13176))
- `craft\helpers\Typecast::properties()` now supports backed enum values. ([#13371](https://github.com/craftcms/cms/pull/13371))
- `craft\services\Assets::getRootFolderByVolumeId()` now ensures the root folder actually exists, and caches its results internally, improving performance. ([#13297](https://github.com/craftcms/cms/issues/13297))
- `craft\services\Assets::getThumbUrl()` now has an `$iconFallback` argument, which can be set to `false` to prevent a file icon URL from being returned as a fallback for assets that don’t have image thumbnails.
- `craft\services\Assets::getAllDescendantFolders()` now has an `$asTree` argument. ([#13535](https://github.com/craftcms/cms/discussions/13535))
- `craft\services\Structures::EVENT_BEFORE_MOVE_ELEMENT` is now cancellable. ([#13429](https://github.com/craftcms/cms/pull/13429))
- `craft\validators\UniqueValidator` now supports setting an additional filter via the `filter` property. ([#12941](https://github.com/craftcms/cms/pull/12941))
- `craft\web\Response::setCacheHeaders()` now has `$duration` and `$overwrite` arguments.
- `craft\web\Response::setNoCacheHeaders()` now has an `$overwrite` argument.
- `craft\web\UrlManager` no longer triggers its `EVENT_REGISTER_CP_URL_RULES` and `EVENT_REGISTER_SITE_URL_RULES` events until the request is ready to be routed, making it safe to call `UrlManager::addRules()` from plugin/module constructors. ([#13109](https://github.com/craftcms/cms/issues/13109))
- Deprecated `craft\base\Element::EVENT_AFTER_MOVE_IN_STRUCTURE`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Deprecated `craft\base\Element::EVENT_BEFORE_MOVE_IN_STRUCTURE`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Deprecated `craft\base\Element::afterMoveInStructure()`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Deprecated `craft\base\Element::beforeMoveInStructure()`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Deprecated `craft\events\ElementStructureEvent`. ([#13429](https://github.com/craftcms/cms/pull/13429))
- Deprecated `craft\helpers\ArrayHelper::firstKey()`. `array_key_first()` should be used instead.
- Deprecated `craft\helpers\Assets::iconPath()`. `craft\helpers\Assets::iconSvg()` or `craft\elements\Asset::getThumbHtml()` should be used instead.
- Deprecated `craft\helpers\Assets::iconUrl()`.
- Deprecated `craft\helpers\UrlHelper::buildQuery()`. `http_build_query()` should be used instead.
- Deprecated `craft\services\Volumes::ensureTopFolder()`. `craft\services\Assets::getRootFolderByVolumeId()` should be used instead.
- Added `Craft.BaseUploader`. ([#13313](https://github.com/craftcms/cms/pull/13313))
- Added `Craft.createUploader()`. ([#13313](https://github.com/craftcms/cms/pull/13313))
- Added `Craft.registerUploaderClass()`. ([#13313](https://github.com/craftcms/cms/pull/13313))
- Added `Craft.Tooltip`.

### System
- Added support for setting environmental values in a “secrets” PHP file, identified by a `CRAFT_SECRETS_PATH` environment variable. ([#13283](https://github.com/craftcms/cms/pull/13283))
- Added support for the `CRAFT_LOG_ALLOW_LINE_BREAKS` environment variable. ([#13544](https://github.com/craftcms/cms/pull/13544))
- All generated URL param characters are now properly encoded. ([#12796](https://github.com/craftcms/cms/issues/12796))
- `migrate` commands besides `migrate/create` no longer create the migration directory if it doesn’t exist yet. ([#12732](https://github.com/craftcms/cms/pull/12732))
- When `content` table columns are resized, if any existing values are too long, all column data is now backed up into a new table, and the overflowing values are set to `null`. ([#13025](https://github.com/craftcms/cms/pull/13025))
- When `content` table columns are renamed, if an existing column with the same name already exists, the original column data is now backed up into a new table and then deleted from the `content` table. ([#13025](https://github.com/craftcms/cms/pull/13025))
- Plain Text and Table fields no longer convert emoji to shortcodes on PostgreSQL.
- Reduced the size of control panel Ajax request headers.
- Improved GraphQL performance. ([#13354](https://github.com/craftcms/cms/pull/13354))
- Fixed an error that occurred when exporting assets, if a subfolder was selected. ([#13570](https://github.com/craftcms/cms/issues/13570))
- Fixed a bug where icons within secondary buttons were illegible when active.
- Fixed a bug where the `|replace` filter was treating search strings as regular expressions even if they were invalid. ([#12956](https://github.com/craftcms/cms/issues/12956))
- Fixed a bug where user groups without “Edit users” permission were being granted “Assign users to [group name]” when upgrading to Craft 4.
- Fixed a bug where keyboard shortcuts weren’t getting reactivated when control panel notifications were dismissed. ([#13574](https://github.com/craftcms/cms/issues/13574))
- Fixed a bug where Plain Text and Table fields were converting posted shortcode-looking strings to emoji. ([#12935](https://github.com/craftcms/cms/issues/12935))
- Fixed a bug where `craft\elements\Asset::getUrl()` was returning invalid URLs for GIF and SVG assets within filesystems without base URLs, if the `transformGifs` or `transformSvgs` config settings were disabled. ([#13306](https://github.com/craftcms/cms/issues/13306))
- Fixed a bug where the GraphQL API wasn’t enforcing schema site selections for the requested site. ([#13346](https://github.com/craftcms/cms/pull/13346))
- Fixed a bug where PM times were getting converted to AM for Greek locales. ([#9942](https://github.com/craftcms/cms/issues/9942))
- Fixed a bug where <kbd>Command</kbd>/<kbd>Ctrl</kbd> + clicks on “New entry” button menu options would open the Entries index page in a new tab, and redirect to the Edit Entry page in the current tab. ([#13550](https://github.com/craftcms/cms/issues/13550))
- Fixed DB cache support for PostgreSQL.
- Updated Yii to 2.0.48.1. ([#13445](https://github.com/craftcms/cms/pull/13445))
- Loosened the Composer constraint to `^2.2.19`. ([#13396](https://github.com/craftcms/cms/discussions/13396))
- Internal Composer operations now use a bundled `composer.phar` file, rather than Composer’s PHP API. ([#13519](https://github.com/craftcms/cms/pull/13519))
- Updated Selectize to 0.15.2. ([#13273](https://github.com/craftcms/cms/discussions/13273))

## 4.4.17 - 2023-08-08

- `meta.__names__` values in the project config are now updated throughout the process of applying incoming project config changes, rather than at the end of the request.
- The `project-config/rebuild` command now rebuilds the `meta.__names__` array from scratch. ([#13456](https://github.com/craftcms/cms/issues/13456))
- Fixed a bug where `Craft.BaseElementIndexView::this.canSelectElement()` wasn’t getting applied for lazy-loaded elements.
- Fixed a bug where setting an element query’s `status` param to `archived` would always yield zero results. ([#13465](https://github.com/craftcms/cms/issues/13465))
- Fixed a bug where `update` commands could fail on some environments.
- Fixed a bug where element thumbnails weren’t getting loaded for expanded relational field previews within element indexes.
- Fixed an error that occurred when deleting a volume with a missing filesystem type.
- Fixed a bug where Color field values were illegible within selected element index rows.
- Fixed a bug where multi-site content could be overwritten when creating a draft. ([#13451](https://github.com/craftcms/cms/issues/13451))
- Fixed a bug where some nested component names weren’t getting deleted from the `meta.__names__` array in the project config. ([#13456](https://github.com/craftcms/cms/issues/13456))
- Fixed a bug where `craft\helpers\DateTimeHelper::toDateInterval()` didn’t support negative integers. ([#13463](https://github.com/craftcms/cms/pull/13463))
- Fixed a bug where admin tables were initially displaying an empty results message rather than a loading spinner, when the initial data was loading via Ajax. ([#13459](https://github.com/craftcms/cms/issues/13459))
- Fixed a bug where garbage collection could terminate prematurely if an exception was thrown when deleting a pending user. ([#13490](https://github.com/craftcms/cms/issues/13490))
- Fixed an error that occurred if the `purify` Twig filter was applied to a `null` value. ([#13495](https://github.com/craftcms/cms/issues/13495))
- Fixed an error that could occur if a console controller’s `runAction()` method returned `null`.
- Fixed a bug where image transforms weren’t respecting their `format` settings. ([#13493](https://github.com/craftcms/cms/issues/13493))
- Fixed an information disclosure vulnerability.

## 4.4.16.1 - 2023-07-19

- Fixed a bug where lightswitch inputs weren’t always stretching to fit their labels, when there was enough space for it. ([#13452](https://github.com/craftcms/cms/issues/13452))

## 4.4.16 - 2023-07-18

- The “Access the control panel” user permission now includes a warning that the permission grants view-only access to user data and most content.
- Added info buttons to “View entries”, “Create entries”, and “Save entries” user permissions, clarifying which actions they allow. ([#13375](https://github.com/craftcms/cms/discussions/13375))
- Improved performance when loading users with eager-loaded `addresses`. ([#13400](https://github.com/craftcms/cms/issues/13400))
- `createDraft` GraphQL mutations now support a `creatorId` argument. ([#13401](https://github.com/craftcms/cms/issues/13401))
- Garbage collection now deletes entries for sites that aren’t supported by their section. ([#13383](https://github.com/craftcms/cms/issues/13383))
- Added `craft\elements\Address::setOwner()`.
- `craft\base\ElementInterface::eagerLoadingMap()` can now include a `createElement` key in the returned array, which defines a target element factory function.
- `craft\base\Element::toArray()` now clones custom field values, similar to `__get()`. ([#13392](https://github.com/craftcms/cms/issues/13392))
- Fixed a bug where entry titles could overflow within Entries fields with “Maintain hierarchy” enabled. ([#13382](https://github.com/craftcms/cms/issues/13382))
- Fixed a bug where batched jobs with a configured limit could be repeated endlessly. ([#13387](https://github.com/craftcms/cms/issues/13387))
- Fixed an error that could occur if `null` was passed to `craft\elements\MatrixBlock::setOwner()`.
- Fixed a bug where eager-loaded categories were always loaded in the structure-defined order, even if the Categories field didn’t have “Maintain hierarchy” enabled. ([#13394](https://github.com/craftcms/cms/issues/13394))
- Fixed a bug where time inputs weren’t handling malformed values properly if ICU 72.1 was installed. ([#13381](https://github.com/craftcms/cms/issues/13381))
- Fixed legacy Live Preview support.
- Fixed a bug where lightswitch inputs could overflow. ([#13419](https://github.com/craftcms/cms/issues/13419))
- Fixed a bug where Matrix field validation wasn’t working properly if the field value was eager-loaded. ([#13421](https://github.com/craftcms/cms/issues/13421))
- Fixed a bug where date and time inputs weren’t always working properly on mobile. ([#13424](https://github.com/craftcms/cms/issues/13424))
- Fixed an RCE vulnerability.

## 4.4.15 - 2023-07-03 [CRITICAL]

- The control panel footer now includes a message about active trials, with a link to purchase the licenses.
- Tags fields now only show up to five suggestions. ([#13322](https://github.com/craftcms/cms/issues/13322))
- The `up`, `migrate/up`, and `migrate/all` commands now revert any project config changes created by migrations on failure.
- The `up`, `migrate/up`, and `migrate/all` commands now prompt to restore the backup created at the outset of the command, or recommend restoring a backup, on failure.
- Added `craft\console\controllers\BackupTrait::restore()`.
- Added `craft\helpers\Component::cleanseConfig()`.
- `craft\log\ContextProcessor::filterVars()` now supports filtering keys using dot notation and `!` negation. ([#13362](https://github.com/craftcms/cms/pull/13362))
- Fixed an error that occurred when passing arguments to an element’s `prev` and `next` fields via GraphQL. ([#13334](https://github.com/craftcms/cms/issues/13334))
- Fixed a bug where Single entries weren’t getting preloaded for template macros, if the template body wasn‘t rendered. ([#13312](https://github.com/craftcms/cms/issues/13312))
- Fixed a bug where asset folders could get dynamically created for elements with temporary slugs. ([#13311](https://github.com/craftcms/cms/issues/13311))
- Fixed a bug where Matrix fields with custom propagation methods were being marked as translatable if the rendered translation key was blank. ([#13329](https://github.com/craftcms/cms/issues/13329))
- Fixed a bug where transformed images’ `width` or `height` properties could be `null` if the transform didn’t specify both dimensions. ([#13335](https://github.com/craftcms/cms/issues/13335))
- Fixed a bug where heading UI elements within field layouts were getting a top border if they were preceded by conditionally-hidden fields. ([#13308](https://github.com/craftcms/cms/issues/13308))
- Fixed a bug where new Single sections could get URIs filled in on form submit based on the section name, if the input was blank and hadn’t been directly edited. ([#13350](https://github.com/craftcms/cms/issues/13350), [#13355](https://github.com/craftcms/cms/pull/13355))
- Fixed a bug where it was possible to drag items beyond the normal page scroll limits. ([#13351](https://github.com/craftcms/cms/issues/13351))
- Fixed two RCE vulnerabilities.

## 4.4.14 - 2023-06-13

- The `utils/fix-field-layout-uids` command now adds missing field layout component UUIDs.
- The `_includes/forms/date` and `_includes/forms/time` templates now accept a `timeZone` variable.
- Invalid utility URLs now redirect to the first permitted utility (besides “Updates”). ([#13282](https://github.com/craftcms/cms/issues/13282))
- Fixed an error that could occur when updating a plugin with the `craft update` command, if it provided a new migration but still had the same schema version.
- Fixed an error that occurred when rendering editable tables with Date or Time columns. ([#13270](https://github.com/craftcms/cms/issues/13270))
- Fixed a bug where CSS classes that contained a pseudo-selector weren’t getting namespaced. ([#13251](https://github.com/craftcms/cms/pull/13251))
- Fixed a JavaScript error that could occur when renaming assets without URLs. ([#13223](https://github.com/craftcms/cms/pull/13223))
- Fixed a bug where `craft\base\Element::setFieldValuesFromRequest()` wasn’t properly handling empty strings passed as the namespace. ([#13252](https://github.com/craftcms/cms/discussions/13252))
- Fixed a styling issue with control panel notifications. ([#13258](https://github.com/craftcms/cms/pull/13258))
- Fixed a bug where element thumbnails could stop getting loaded when quickly switching between element sources. ([#13253](https://github.com/craftcms/cms/issues/13253))
- Fixed an error that occurred when uploading an asset with a filename over 232 characters long, directly to an Assets field. ([#13264](https://github.com/craftcms/cms/issues/13264))
- Fixed an error that occurred when transforming an image with a filename over 232 characters long. ([#13266](https://github.com/craftcms/cms/pull/13266))
- Fixed a SQL error that could occur when upgrading to 4.4 on PostgreSQL, if the database was converted from MySQL. ([#12855](https://github.com/craftcms/cms/issues/12855))
- Fixed a bug where `craft\db\Query::collect()` was returning a `craft\elements\ElementCollection` instance.
- Fixed a SQL error that could occur when upgrading to Craft 4 if any database tables had foreign keys to `entryversions` or other now-unused tables that are removed during the upgrade.
- Fixed a bug where the `users/save-user` action wasn’t including user details in successful responses. ([#13267](https://github.com/craftcms/cms/issues/13267))
- Fixed a PHP error that occurred if an asset without a `dateModified` value was passed to `craft\helpers\Assets::revParams()`. ([#13268](https://github.com/craftcms/cms/pull/13268))
- Fixed a bug where the Updates utility’s heading wasn’t reflecting updates that were blocked due to an expired plugin. ([#13274](https://github.com/craftcms/cms/issues/13274))
- Fixed a bug where element deletion events weren’t getting triggered when elements were hard-deleted from an element index. ([#13280](https://github.com/craftcms/cms/issues/13280))
- Fixed a bug where dropdowns within editable tables had blank optgroup labels. ([#13298](https://github.com/craftcms/cms/issues/13298))
- Fixed a bug where textual cells within editable tables would display `[object Object]` if an object was passed as their value. ([#13303](https://github.com/craftcms/cms/issues/13303))

## 4.4.13 - 2023-05-24

- Fixed a bug where asset sources weren‘t immediately showing a source path on a clear `localStorage` cache.
- Fixed a JavaScript error that could occur when searching within an asset index, when there was no source path. ([#13241](https://github.com/craftcms/cms/issues/13241))
- Fixed a bug where Date fields with “Show Time Zone” enabled were displaying their values in the system’s time zone within element indexes. ([#13233](https://github.com/craftcms/cms/issues/13233))
- Fixed a bug where the “Cancel” buttons within Dashboard widgets’ settings didn’t do anything. ([#13239](https://github.com/craftcms/cms/issues/13239))

## 4.4.12 - 2023-05-23

- Asset indexes now remember their previously-selected source path. ([#13147](https://github.com/craftcms/cms/issues/13147))
- Added the `enabledForSite` field for entries queried via GraphQL. ([#13214](https://github.com/craftcms/cms/pull/13214))
- Added `craft\base\ElementInterface::sourcePath()`.
- Improved `craft\helpers\FileHelper::getExtensionByMimeType()` for some ambiguous, web-friendly MIME types.
- Fixed a bug where reverting an entry’s content from a revision could omit some Matrix blocks.
- Fixed an error that could occur when adding a new site to an entry which contained Matrix blocks, if the same site had been added and removed previously.
- Fixed a bug where Matrix blocks nested within Neo or Super Table fields could be omitted when propagating an entry to a new site. ([#13207](https://github.com/craftcms/cms/issues/13207))
- Fixed a bug where `craft\web\View::registerTwigExtension()` could throw an exception if Twig was already initialized. ([#13208](https://github.com/craftcms/cms/issues/13208))
- Fixed a bug where entries mutated via GraphQL weren’t becoming globally enabled if `enabled` was set to `true`. ([#13214](https://github.com/craftcms/cms/pull/13214))
- Fixed a styling issue with “Post Date” and “Expiry Date” fields. ([#13216](https://github.com/craftcms/cms/issues/13216))
- Removed the OAuth 2.0 Client library, as it’s no longer used in core.
- Fixed a bug where activation emails sent to newly-created users could link to the front-end site, if they were granted control panel access via a user group. ([#13204](https://github.com/craftcms/cms/issues/13204))
- Fixed a bug where “Required” lightswitch labels within field layout designers’ field settings slideouts weren’t getting translated. ([#13230](https://github.com/craftcms/cms/issues/13230))
- Fixed a bug where Date fields could display the wrong date. ([#13233](https://github.com/craftcms/cms/issues/13233))
- Deprecated the `Craft.startsWith()` JavaScript method. `String.prototype.startsWith()` should be used instead.
- Fixed two XSS vulnerabilities.

## 4.4.11 - 2023-05-15

- Fixed a bug where Matrix blocks weren’t getting propagated to newly-added sites for entries. ([#13181](https://github.com/craftcms/cms/issues/13181))
- Fixed a SQL error that could occur when updating to Craft 4.4 on PostgreSQL. ([#13186](https://github.com/craftcms/cms/issues/13186))
- Fixed a bug where `craft\helpers\StringHelper::isUtf8()` was unreliable.
- Fixed a styling issue with Date fields. ([#13182](https://github.com/craftcms/cms/issues/13182))

## 4.4.10.1 - 2023-05-10

- Fixed a bug where it wasn’t possible to add new Matrix blocks via the “Add a block” menu. ([#13177](https://github.com/craftcms/cms/issues/13177))

## 4.4.10 - 2023-05-09

- PHP warnings and notices no longer halt execution when Dev Mode is disabled. ([#13164](https://github.com/craftcms/cms/issues/13164))
- Fixed a “Double-instantiating a menu button on an element” console warning that occurred on pages with Matrix fields. ([#6338](https://github.com/craftcms/cms/issues/6338))
- Fixed a bug where Quick Post widget settings weren’t filtering custom field options for the selected entry type.
- Fixed a bug where Matrix blocks could get detached from entries when sections were enabled for a new site. ([#13155](https://github.com/craftcms/cms/issues/13155))
- Fixed an error that could occur when entrifying a global set without a field layout. ([#13156](https://github.com/craftcms/cms/issues/13156))
- Fixed a bug where Single entries’ edit pages could have “Save and add another” actions. ([#13157](https://github.com/craftcms/cms/issues/13157))
- Fixed styling issues with Date fields. ([#13167](https://github.com/craftcms/cms/issues/13167))
- Fixed the vertical alignment of element labels. ([#13168](https://github.com/craftcms/cms/issues/13168))
- Fixed a bug where the System Report utility could display MariaDB’s MySQL-equivalent version, if it was listed first in the server version string.
- Added `craft\helpers\ArrayHelper::containsRecursive()`.
- `craft\helpers\App::normalizeVersion()` now returns the highest version found before distribution info.

## 4.4.9 - 2023-05-02

- Volumes no longer validate if their field layout contains a field called `extension`, `filename`, `height`, `kind`, `size`, or `width`.
- It’s now possible for plugins to register errors to entries’ `typeId`, `parentId`, and `authorId` attributes. ([#13138](https://github.com/craftcms/cms/pull/13138))
- Stack traces returned by `craft\helpers\App::backtrace()` now more closely resemble exception stack traces.
- “Element query executed before Craft is fully initialized” warnings now include a stack trace.
- Fixed a bug where queue-runner Ajax requests triggered on the front end weren’t getting closed before running the queue, potentially causing long front-end load delays.
- Fixed a bug where long element titles weren’t wrapping. ([#13143](https://github.com/craftcms/cms/issues/13143))
- Fixed a user enumeration timing attack vulnerability.
- Fixed a SQL error that could occur when upgrading to Craft 4, if any `matrixblocks` table rows referenced nonexistent element IDs. ([#13121](https://github.com/craftcms/cms/issues/13121))
- Fixed a SQL error that could occur when upgrading to Craft 4, if anything triggered an asset or volume query. ([#13130](https://github.com/craftcms/cms/issues/13130))
- Fixed a SQL error that occurred when deleting a category group on PostgreSQL, when configured with a table prefix. ([#13127](https://github.com/craftcms/cms/issues/13127))
- Fixed a bug where it was possible to query for elements with soft-deleted site IDs.
- Fixed a JavaScript error that could occur on the control panel login form.

## 4.4.8 - 2023-04-25

- Category/tag/global set reference tags now map to entries, if no category groups/tag groups/global sets exist. ([#13082](https://github.com/craftcms/cms/issues/13082))
- HTML Purifier now allows `data-oembed-url` attributes on `div` tags. ([craftcms/ckeditor#80](https://github.com/craftcms/ckeditor/issues/80))
- Added `craft\queue\Queue::EVENT_AFTER_EXEC_AND_RELEASE`. ([#13096](https://github.com/craftcms/cms/issues/13096))
- `craft\services\Elements::propagateElements()` now returns the element in the target site.
- A globally-defined element thumb loader is now available in the control panel via `Craft.cp.elementThumbLoader`.
- Fixed a bug where it was possible to select a disallowed volume as the Default Asset Location in Assets field settings. ([#13072](https://github.com/craftcms/cms/issues/13072))
- Fixed a bug where it was possible to upload files to Assets fields outside of the allowed volumes, if the Default Upload Location was set to a disallowed volume. ([#13072](https://github.com/craftcms/cms/issues/13072))
- Fixed an error that could occur if a Plain Text field had over 1,000,000 bytes. ([#13083](https://github.com/craftcms/cms/issues/13083))
- Fixed a bug where relational field values weren’t yielding any results for event handlers immediately after a draft had been merged. ([#13087](https://github.com/craftcms/cms/issues/13087))
- Fixed a bug where element labels could bleed out of their container. ([#13099](https://github.com/craftcms/cms/issues/13099))
- Fixed an error that occurred if `yii\web\UrlManager::addRules()` was called on a console request. ([#13109](https://github.com/craftcms/cms/issues/13109))
- Fixed a bug where it was possible to select the current folder as the target when moving a volume folder, resulting in the folder and its contents being lost. ([#13118](https://github.com/craftcms/cms/issues/13118))
- Fixed an error that could occur when running tests. ([#13076](https://github.com/craftcms/cms/issues/13076))
- Fixed an error that occurred when sending the activation email for a new user, if there were any validation errors due to new group assignments. ([#13060](https://github.com/craftcms/cms/issues/13060))
- Fixed a bug where the “Send an activation email now” user account checkbox was losing its value if there were any validation errors.
- Fixed an error that could occur when creating a database backup on MySQL and MariaDB. ([#12996](https://github.com/craftcms/cms/issues/12996))
- Fixed a bug where Edit Category screens were including a Parent field, even if the category group’s Max Levels was set to `1`. ([#13097](https://github.com/craftcms/cms/discussions/13097))
- Fixed a bug where the uploader’s user photo wasn’t always loading on Edit Asset pages.
- Fixed a bug where the “Delete for site” bulk element action was deleting disabled elements and drafts across all sites. ([#13116](https://github.com/craftcms/cms/issues/13116))
- Fixed a bug where Entries and Categories fields with “Maintain hierarchy” enabled could lose relations to elements that didn’t exist in the primary site. ([#13057](https://github.com/craftcms/cms/issues/13057))

## 4.4.7.1 - 2023-04-15

- Locked the Yii 2 PSR Log Target library to 1.1.3 to avoid a PHP error that occurs on 1.1.4.

## 4.4.7 - 2023-04-11

- Improved the control panel styling when the Debug Toolbar is enabled.
- The image transformer now verifies that transforms don’t exist if the index record is missing, before queuing up the transform generation, for local filesystems. ([#13052](https://github.com/craftcms/cms/issues/13052))
- Added the `--propagate-to` and `--set-enabled-for-site` options to the `resave/entries` command.
- Craft’s bootstrap script now defines a `CRAFT_ENVIRONMENT` environment variable, as a safety measure for plugins that may be checking for it rather than `Craft::$app->env`.
- Added `craft\helpers\ElementHelper::siteStatusesForElement()`.
- `craft\elements\Asset::EVENT_BEFORE_DEFINE_URL` now sends a `craft\events\DefineAssetUrlEvent` object, rather than `craft\events\DefineUrlEvent`. ([#13018](https://github.com/craftcms/cms/issues/13018))
- `craft\web\View::renderObjectTemplate()` now trims the returned template output.
- Fixed a bug where users were “View other users’ drafts” section permissions weren’t being enforced for unpublished drafts.
- Fixed a bug where Matrix fields weren’t counting disabled blocks when enforcing their Min Blocks settings. ([#13059](https://github.com/craftcms/cms/issues/13059))
- Fixed a bug where volume folder modals’ sidebars and content were being cut off. ([#13074](https://github.com/craftcms/cms/issues/13074))
- Fixed a bug where element editors were showing provisional changes, even if the user didn’t have permission to save them.
- Fixed a bug where the control panel could be inaccessible if a mutex lock couldn’t be acquired for the queue. ([#13052](https://github.com/craftcms/cms/issues/13052))
- Fixed a bug where it wasn’t possible to update a Matrix block on a revision without a new block ID being assigned. ([#13064](https://github.com/craftcms/cms/discussions/13064))
- Fixed a JavaScript error that could occur on field layout designers, if any tabs didn’t have any elements. ([#13062](https://github.com/craftcms/cms/issues/13062))
- Fixed a bug where selecting an image with a transform within an asset selector modal wasn’t ever resolving.
- Fixed a PHP error that could occur if there was a problem sending a password-reset email. ([#13070](https://github.com/craftcms/cms/pull/13070))
- Fixed a bug where users’ User Groups and Permissions settings were getting cleared in the UI when sending an activation email, if the email failed to send. ([#13061](https://github.com/craftcms/cms/issues/13061))
- Fixed XSS vulnerabilities.
- Updated yii2-debug to 2.1.22. ([#13058](https://github.com/craftcms/cms/issues/13058))

## 4.4.6.1 - 2023-04-04

- Fixed a bug where Categories fields weren’t retaining custom sort orders when “Maintain hierarchy” was disabled. ([#10560](https://github.com/craftcms/cms/discussions/10560))

## 4.4.6 - 2023-04-04

- Content tab menus now reveal when a tab contains validation errors, and invalid tabs’ menu options get the same warning icon treatment as inline tabs do. ([#12971](https://github.com/craftcms/cms/issues/12971))
- Selectize menus now expand upwards when there’s not ample space below them. ([#12976](https://github.com/craftcms/cms/issues/12976))
- Element index bulk action spinners are now centered on the viewport. ([#12972](https://github.com/craftcms/cms/issues/12972))
- All control panel errors are new presented via error notifications rather than browser alerts. ([#13024](https://github.com/craftcms/cms/issues/13024))
- The `up` command now sets its default `--isolated` option value to `true`, and no longer creates a redundant mutex lock.
- Added `craft\base\Element::EVENT_BEFORE_DEFINE_URL`. ([#13018](https://github.com/craftcms/cms/issues/13018))
- Added `craft\utilities\AssetIndexes::volumes()`.
- `craft\controllers\AssetIndexesController::actionStartIndexing()` now cross-references the selected volumes with those allowed by `craft\utilities\AssetIndexes::EVENT_LIST_VOLUMES` event handlers. ([#13039](https://github.com/craftcms/cms/pull/13039), [#12819](https://github.com/craftcms/cms/pull/12819))
- Fixed a bug where Assets fields weren’t respecting their View Mode setting when viewing entry revisions. ([#12948](https://github.com/craftcms/cms/issues/12948))
- Fixed a bug where asset pagination was broken when there was more than 100 subfolders. ([#12969](https://github.com/craftcms/cms/issues/12969))
- Fixed a bug where entry index pages’ “Revision Notes” and “Last Edited By” columns weren’t getting populated for disabled entries. ([#12981](https://github.com/craftcms/cms/issues/12981))
- Fixed a bug where assets were getting relocated to the root volume folder when renamed. ([#12995](https://github.com/craftcms/cms/issues/12995))
- Fixed a bug where it wasn’t possible to preview entries on another domain when the system was offline. ([#12979](https://github.com/craftcms/cms/issues/12979))
- Fixed a bug where users were able to access volumes they didn’t have permission to view via Assets fields. ([#13006](https://github.com/craftcms/cms/issues/13006))
- Fixed a bug where zero-width spaces, invisible plus signs, and byte order marks weren’t getting stripped from sanitized asset filenames. ([#13022](https://github.com/craftcms/cms/issues/13022))
- Fixed a bug where the Plugin Store wasn’t accurately reporting installed plugins’ license statuses. ([#12986](https://github.com/craftcms/cms/issues/12986))
- Fixed a bug where the Plugin Store wasn’t handling 403 API responses for cart operations properly, once a cart had been handed off to Craft Console and assigned to an organization. ([#12916](https://github.com/craftcms/cms/issues/12916))
- Fixed a bug where `craft\helpers\FileHelper::absolutePath()` wasn’t treating Windows file paths beginning drive letters as absolute. ([craftcms/generator#16](https://github.com/craftcms/generator/issues/16))
- Fixed a bug where it wasn’t possible to sort Categories fields with “Maintain hierarchy” disabled. ([#10560](https://github.com/craftcms/cms/discussions/10560))
- Fixed a bug where selectize inputs didn’t have a minimum width. ([#12950](https://github.com/craftcms/cms/issues/12950))
- Fixed a bug where the wrong tab would appear to be initially selected after an autosave, if the selected tab had changed during the autosave. ([#12960](https://github.com/craftcms/cms/issues/12960))
- Fixed a bug where it wasn’t possible to add a Dropdown field without a blank option to a global set. ([#12965](https://github.com/craftcms/cms/issues/12965))
- Fixed a bug where automatically-added Matrix blocks (per the field’s Min Blocks setting) were getting discarded if no changes were made to them. ([#12973](https://github.com/craftcms/cms/issues/12973))
- Fixed an error that could occur when installing Craft with an existing project config, if any image transforms were defined that didn’t specify the `upscale` property.
- Fixed a bug where nested folders in asset search results weren’t showing their relative path.
- Fixed a bug where admin tables’ default delete icon title text wasn’t getting translated. ([#13030](https://github.com/craftcms/cms/issues/13030))
- Fixed a bug where it was possible to save a Local filesystem pointed at a system directory (e.g. the `templates/` or `vendor/` folders), which mitigates a potential RCE vulnerability.
- Fixed XSS vulnerabilities.

## 4.4.5 - 2023-03-21

- Fixed a bug where relation data was getting deleted when running garbage collection on PostgreSQL. ([#9905](https://github.com/craftcms/cms/issues/9905))
- Fixed a bug where Lightswitch fields’ “OFF Label” and “ON Label” settings weren’t getting translated. ([#12942](https://github.com/craftcms/cms/issues/12942))
- Fixed a bug where `craft\events\DefineUserContentSummaryEvent::$userId` was never set for `craft\controllers\EVENT_DEFINE_CONTENT_SUMMARY` events. ([#12944](https://github.com/craftcms/cms/issues/12944))
- Fixed a bug where element edit pages weren’t displaying layout tabs that didn’t have a unique name. ([#12928](https://github.com/craftcms/cms/issues/12928))
- Fixed a bug where the `CRAFT_LOG_PHP_ERRORS` constant/environment variable wasn’t being respected when set to `false`. ([#12862](https://github.com/craftcms/cms/issues/12862))
- Fixed a bug where the `entrify/categories` command wasn’t converting disabled categories. ([#12945](https://github.com/craftcms/cms/issues/12945))
- Updated svg-sanitizer to 0.16. ([#12943](https://github.com/craftcms/cms/issues/12943))

## 4.4.4 - 2023-03-20

- Input autofocussing has been reintroduced throughout the control panel. ([#12921](https://github.com/craftcms/cms/discussions/12921))
- The `|json_encode` Twig filter now calls `craft\helpers\Json::encode()` internally, improving error handling. ([#12919](https://github.com/craftcms/cms/issues/12919))
- `craft\helpers\Json::encode()` no longer sets the `JSON_UNESCAPED_SLASHES` flag by default.
- Fixed a JavaScript error that occurred when resolving an asset move conflict. ([#12920](https://github.com/craftcms/cms/issues/12920))
- Fixed a bug where volume subfolders were being shown when viewing soft-deleted assets. ([#12927](https://github.com/craftcms/cms/issues/12927))
- Fixed a bug where structure data was getting deleted when running garbage collection on PostgreSQL. ([#12925](https://github.com/craftcms/cms/issues/12925))
- Fixed an error that could occur when rebuilding the project config, if there were any custom source definitions for element types that weren’t Composer-installed. ([#12881](https://github.com/craftcms/cms/issues/12881))
- Fixed an XSS vulnerability.

## 4.4.3 - 2023-03-16

- Customize Sources modals no longer hide when the <kbd>Esc</kbd> key is pressed on the surrounding area is clicked on. ([#12895](https://github.com/craftcms/cms/issues/12895))
- Sections created via `entrify` commands no longer get a “Primary entry page” preview target by default, unless it was sourced from a category group with URLs. ([#12897](https://github.com/craftcms/cms/issues/12897))
- `entrify` commands and the `sections/create` command now prompt for the initial entry type name and handle. ([#12894](https://github.com/craftcms/cms/discussions/12894))
- Added `craft\helpers\FileHelper::uniqueName()`.
- Added `craft\helpers\StringHelper::emojiToShortcodes()`.
- Added `craft\helpers\StringHelper::shortcodesToEmoji()`.
- Fixed an error that occurred when uploading an asset with a filename over 250 characters long. ([#12889](https://github.com/craftcms/cms/issues/12889))
- Fixed an error that could occur when preparing licensing alerts, if any licenses were invalid. ([#12899](https://github.com/craftcms/cms/issues/12899))
- Fixed a bug where it wasn’t possible to drag nested Neo blocks. ([#12896](https://github.com/craftcms/cms/issues/12896))
- Fixed a bug where fields with reduced widths in Matrix blocks were becoming full-width while dragged. ([#12909](https://github.com/craftcms/cms/issues/12909))
- Fixed a bug where multi-edition plugins weren’t showing their edition labels within the Plugin Store cart. ([#12910](https://github.com/craftcms/cms/issues/12910))
- Fixed a bug where private plugins’ control panel templates weren’t directly accessible by URL.
- Fixed a bug where element selector modals were persisting parts of their state in the query string. ([#12900](https://github.com/craftcms/cms/issues/12900))
- Fixed a PHP error that occurred if a field type stored enum values. ([#12297](https://github.com/craftcms/cms/issues/12297))
- Fixed an error that could occur when generating transforms for images stored in Google Cloud Storage. ([#12878](https://github.com/craftcms/cms/issues/12878))
- Fixed a bug where some unicode characters were getting removed by LitEmoji. ([#12905](https://github.com/craftcms/cms/issues/12905))

## 4.4.2 - 2023-03-14

- The `entrify/categories` and `entrify/global-set` commands now update user permissions for the new sections. ([#12849](https://github.com/craftcms/cms/discussions/12849))
- Variable dumps now use a light theme for web requests by default. ([#12857](https://github.com/craftcms/cms/pull/12857))
- Added the `dumper` application component and `craft\base\ApplicationTrait::getDumper()`. ([#12869](https://github.com/craftcms/cms/pull/12869))
- Added `craft\models\ImageTransform::getConfig()`.
- Fixed a bug where it wasn’t always possible to access entry or category edit pages if the `slugWordSeparator` config setting was set to `/`. ([#12871](https://github.com/craftcms/cms/issues/12871))
- Fixed a bug where `craft\helpers\Html::parseTagAttribute()` wasn’t decoding attribute values, which could lead to double-encoded attributes, e.g. when using the `|attr` filter. ([#12887](https://github.com/craftcms/cms/issues/12887))
- Fixed a bug where multi-value field inputs would be considered modified even if they weren’t, if the field type’s `isValueEmpty()` method returned `true`. ([#12858](https://github.com/craftcms/cms/issues/12858))
- Fixed a bug where asset thumbnails within secondary slideout tabs weren’t loading immediately. ([#12859](https://github.com/craftcms/cms/issues/12859))
- Fixed a bug where rebuilding the project config would lose track of image transforms’ `fill` and `upscale` settings. ([#12879](https://github.com/craftcms/cms/issues/12879))
- Fixed a bug where blank Dropdown options weren’t showing up in the Selectize menu. ([#12880](https://github.com/craftcms/cms/issues/12880))
- Fixed a bug where it was possible to save a Dropdown field without a value, even if the field didn’t have any blank options. ([#12880](https://github.com/craftcms/cms/issues/12880))
- Fixed a bug where element action triggers weren’t centered for element sources that don’t define any exporters. ([#12885](https://github.com/craftcms/cms/issues/12885))
- Fixed an error that could occur when generating a transform URL for an image in the temp folder.
- Fixed XSS vulnerabilities.
- Fixed an SSRF vulnerability.

## 4.4.1 - 2023-03-09

- Fixed a bug where it wasn’t possible to select subfolders on the Assets index page. ([#12802](https://github.com/craftcms/cms/issues/12802))
- Fixed a bug where element index search inputs were losing focus when the element listing was updated. ([#12846](https://github.com/craftcms/cms/issues/12846))
- Fixed a bug where the database driver was being referenced as “MySQL” when using MariaDB. ([#12827](https://github.com/craftcms/cms/issues/12827))
- Fixed a bug where users weren’t able to select assets within Assets fields, if they didn’t have full permissions for the volume. ([#12851](https://github.com/craftcms/cms/issues/12851))
- Fixed a bug where the Assets index page’s URL would get updated incorrectly when renaming a subfolder.
- Fixed a bug where non-admin users weren’t able to view Single section entries. ([#12838](https://github.com/craftcms/cms/issues/12838))
- Fixed an error that could occur when saving an element with eager-loaded relations. ([#12839](https://github.com/craftcms/cms/issues/12839))
- Fixed a bug where Customize Sources modals weren’t showing source headings with no subsequent sources. ([#12840](https://github.com/craftcms/cms/issues/12840))
- Fixed a bug where `entrify` commands could leave the database and project config data in inconsistent states, if aborted prematurely. ([#12850](https://github.com/craftcms/cms/pull/12850))
- Fixed a bug where the `entrify/global-set` command wasn’t always suggesting the command to run on other environments.
- Fixed a bug where the Assets index page would appear to keep loading indefinitely after renaming a subfolder.
- Fixed a bug where folders within asset indexes were getting the current site name appended to them, on multi-site installs. ([#12852](https://github.com/craftcms/cms/issues/12852))
- Fixed a SQL error that could occur when updating to Craft 4.4 on PostgreSQL. ([#12855](https://github.com/craftcms/cms/issues/12855))
- Added `craft\db\Connection::getDriverLabel()`.

## 4.4.0 - 2023-03-08

### Content Management
- Volume subfolders are now displayed within the element listing pane on asset indexes, rather than as nested sources in the sidebar. ([#12558](https://github.com/craftcms/cms/pull/12558), [#9171](https://github.com/craftcms/cms/discussions/9171), [#5809](https://github.com/craftcms/cms/issues/5809))
- Asset indexes now display the current subfolder path above the element listing. ([#12558](https://github.com/craftcms/cms/pull/12558))
- Assets and folders can now be drag-and-dropped simultaneously. ([#12792](https://github.com/craftcms/cms/pull/12792))
- Reduced the likelihood of accidentally triggering an asset drag operation. ([#12792](https://github.com/craftcms/cms/pull/12792))
- Reduced the likelihood of accidentally dropping dragged assets/folders on an unintended target. ([#12792](https://github.com/craftcms/cms/pull/12792))
- It’s now possible to move volume folders and assets to a new location via a new “Move…” bulk element action, rather than via drag-and-drop interactions. ([#12558](https://github.com/craftcms/cms/pull/12558))
- It’s now possible to sort asset indexes by image width and height. ([#12653](https://github.com/craftcms/cms/pull/12653))
- Structured element index sources now have a dedicated view mode option for showing elements as a structured table. ([#12722](https://github.com/craftcms/cms/pull/12722), [#12718](https://github.com/craftcms/cms/discussions/12718))
- Entry and category indexes can now have “Ancestors” and “Parent” columns. ([#12666](https://github.com/craftcms/cms/discussions/12666))
- All element sources now have a “Duplicate” action, even if the element type’s `defineActions()` method didn’t include one. ([#12382](https://github.com/craftcms/cms/discussions/12382))
- Element index pages now track the search term in a query param, so the results can be shared. ([#8942](https://github.com/craftcms/cms/discussions/8942), [#12399](https://github.com/craftcms/cms/pull/12399))
- Entries with more than 10 revisions now include a “View all revisions” item within their revision menu, which links to a new revisions index page for the entry that paginates through all its revisions. ([#8609](https://github.com/craftcms/cms/discussions/8609))
- Entries and Categories fields now have “Maintain hierarchy” settings, which become available when a single structured source is selected. ([#8522](https://github.com/craftcms/cms/discussions/8522), [#8748](https://github.com/craftcms/cms/discussions/8748), [#10560](https://github.com/craftcms/cms/discussions/10560), [#11749](https://github.com/craftcms/cms/pull/11749))
- Entries fields now have a “Branch Limit” setting, which becomes available when “Maintain hierarchy” is enabled, replacing “Min Relations” and “Max Relations”.
- Categories fields now have “Min Relations” and “Max Relations” settings, which become available when “Maintain hierarchy” is disabled, replacing “Branch Limit”.
- Added “Viewable” asset and entry condition rules. ([#12240](https://github.com/craftcms/cms/discussions/12240), [#12266](https://github.com/craftcms/cms/pull/12266))
- Renamed the “Editable” asset and entry condition rules to “Savable”. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Assets, categories, and entries will now redirect to the last-selected source on their index pages when saved. ([#11996](https://github.com/craftcms/cms/discussions/11996))
- Dropdown fields that don’t have a blank option and are missing a value will now include and select a blank option at the beginning of their menu. ([#12235](https://github.com/craftcms/cms/discussions/12235))
- Tip and Warning field layout UI elements can now be marked as dismissible, giving authors the ability to hide them. ([#12188](https://github.com/craftcms/cms/discussions/12188))
- All assets now get thumbnails in the control panel, even if they don’t have a transform filesystem with a base URL. ([#12531](https://github.com/craftcms/cms/issues/12531))
- Dropdown and Multi-select fields now use Selectize. ([#8403](https://github.com/craftcms/cms/discussions/8403))
- Improved the styling of bulk element action triggers. ([#12793](https://github.com/craftcms/cms/pull/12793))

### Accessibility
- Improved the announcement menu for screen readers. ([#12361](https://github.com/craftcms/cms/pull/12361))
- Improved keyboard control of the Updates utility. ([#12189](https://github.com/craftcms/cms/pull/12189))
- Improved the color contrast and keyboard control of the Customize Sources modal. ([#12233](https://github.com/craftcms/cms/pull/12233))
- Improved info icons for screen readers. ([#12272](https://github.com/craftcms/cms/pull/12272))
- Removed input autofocussing throughout the control panel. ([#12324](https://github.com/craftcms/cms/discussions/12324), [#12332](https://github.com/craftcms/cms/pull/12332), [#12406](https://github.com/craftcms/cms/pull/12406))
- Improved the login screen for screen readers. ([#12386](https://github.com/craftcms/cms/pull/12386))
- Improved _conditional_ and _required_ field indicators for screen readers. ([#12509](https://github.com/craftcms/cms/pull/12509))
- Improved element index pages for screen readers. ([#12731](https://github.com/craftcms/cms/pull/12731))
- Improved bulk element action triggers on element index pages. ([#12415](https://github.com/craftcms/cms/pull/12415))
- Improved the color contrast of element indexes. ([#12213](https://github.com/craftcms/cms/pull/12213))
- Asset preview modals now show videos’ alt text. ([#12637](https://github.com/craftcms/cms/pull/12637))
- Improved element indexes’ “Export” buttons for screen readers. ([#12754](https://github.com/craftcms/cms/pull/12754))
- Improved Quick Post widgets’ “Fields” settings for screen readers. ([#12772](https://github.com/craftcms/cms/pull/12772))
- Improved some element selector modals for screen readers. ([#12783](https://github.com/craftcms/cms/pull/12783))
- Fixed a bug where it wasn’t possible to vertically scroll element index filter HUDs. ([#12751](https://github.com/craftcms/cms/pull/12751))
- Fixed a bug where translated locale names within users’ “Language” and “Formatting Locale” preference menus didn’t have `lang` attributes. ([#12773](https://github.com/craftcms/cms/pull/12773))
- Fixed a bug where search icons on element indexes weren’t hidden from screen readers. ([#12785](https://github.com/craftcms/cms/pull/12785))

### Administration
- Most licensing issues are now consolidated into a single control panel alert, with a button to resolve them all with a single purchase on Craft Console. ([#12768](https://github.com/craftcms/cms/pull/12768))
- Conditional layout components are now identified using a condition icon within field layout designers. ([#12250](https://github.com/craftcms/cms/issues/12250))
- All CLI commands now support an `--isolated` option, which ensures the command is run in isolation. ([#12337](https://github.com/craftcms/cms/discussions/12337), [#12350](https://github.com/craftcms/cms/pull/12350))
- Added the `entrify/categories`, `entrify/tags`, and `entrify/global-set` commands, for converting categories, tags, and global sets to entries. ([#12689](https://github.com/craftcms/cms/pull/12689), [#9781](https://github.com/craftcms/cms/discussions/9781))
- Added the `sections/create` and `sections/delete` commands. ([#12689](https://github.com/craftcms/cms/pull/12689))
- The `plugin/install`, `plugin/uninstall`, `plugin/enable`, and `plugin/disabled` commands now support an `--all` option, which applies the action to all applicable Composer-installed plugins. ([#11373](https://github.com/craftcms/cms/discussions/11373), [#12218](https://github.com/craftcms/cms/pull/12218))
- The `project-config/apply` command now supports a `--quiet` option, which reduces the command output. ([#12568](https://github.com/craftcms/cms/discussions/12568))
- Added the `users/unlock` console command. ([#12345](https://github.com/craftcms/cms/discussions/12345))
- The Asset Indexes utility no longer skips volumes if the root folder was completely empty. ([#12585](https://github.com/craftcms/cms/issues/12585), [#12604](https://github.com/craftcms/cms/pull/12604))
- The Asset Indexes utility now has a “List empty folders” setting, which determines whether empty folders should be listed for deletion from the index. ([#12604](https://github.com/craftcms/cms/pull/12604))
- The Asset Indexes utility now lists missing/empty folders and files separately in the review screen. ([#12604](https://github.com/craftcms/cms/pull/12604))
- Relational condition rules for conditions that are saved to the project config now accept a Twig template which outputs a related element ID dynamically. ([#12679](https://github.com/craftcms/cms/pull/12679), [#12676](https://github.com/craftcms/cms/discussions/12676))
- Image transform settings no longer show the “Default Focal Point” setting when Mode is set to “Fit”, as the setting had no effect. ([#12774](https://github.com/craftcms/cms/pull/12774))
- Improved the CLI output for `index-assets` commands. ([#12604](https://github.com/craftcms/cms/pull/12604))

### Development
- Added the “Letterbox” (`letterbox`) image transform mode. ([#8848](https://github.com/craftcms/cms/discussions/8848), [#12214](https://github.com/craftcms/cms/pull/12214))
- Added the `preloadSingles` config setting, which causes front-end Twig templates to automatically preload Single section entries which are referenced in the template. ([#12698](https://github.com/craftcms/cms/pull/12787))
- Control panel-defined image transforms now have an “Allow Upscaling” setting, which will initially be set to the `upscaleImages` config setting for existing transforms. ([#12214](https://github.com/craftcms/cms/pull/12214))
- Template-defined image transforms can now have an `upscale` setting. The `upscaleImages` config setting will be used by default if not set. ([#12214](https://github.com/craftcms/cms/pull/12214))
- Added the `exec` command, which executes an individual PHP statement and outputs the result. ([#12528](https://github.com/craftcms/cms/pull/12528))
- Added the `editable` and `savable` asset query params. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added the `savable` entry query param. ([#12266](https://github.com/craftcms/cms/pull/12266))
- The `editable` entry query param can now be set to `false` to only show entries that _can’t_ be viewed by the current user. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added the `{% dump %}` tag, which dumps variables into a new “Dumps” Debug Toolbar panel. ([#12506](https://github.com/craftcms/cms/pull/12506))
- Added the `base64_decode` and `base64_encode` Twig filters. ([#12696](https://github.com/craftcms/cms/discussions/12696))
- The `dump()` Twig function now utilizes `Craft::dump()`, and no longer requires Dev Mode to be active. ([#12486](https://github.com/craftcms/cms/pull/12486), [#12479](https://github.com/craftcms/cms/discussions/12479))
- The `{% dd %}` Twig tag can now output the entire `context` array, if no variable is passed to it. ([#12486](https://github.com/craftcms/cms/pull/12486))
- Added the `revisionNotes` field to elements queried via GraphQL. ([#12610](https://github.com/craftcms/cms/issues/12610))
- Added `ancestors` and `descendants` fields to categories queried via GraphQL. ([#12427](https://github.com/craftcms/cms/issues/12427))
- Added `craft\elements\Asset::getFormat()` and the `format` field for assets queried via GraphQL. ([#12398](https://github.com/craftcms/cms/pull/12398), [#12521](https://github.com/craftcms/cms/pull/12521))
- `Craft::dump()`, `Craft::dd()`, the `dump()` Twig function, and the `{% dd %}` Twig tag now use Symfony’s VarDumper. ([#12479](https://github.com/craftcms/cms/discussions/12479))
- `Craft::dump()` now has a `$return` argument, which will cause the resulting dump to be returned as a string rather than output, if `true`.
- `craft\elements\Asset::getMimeType()` now has a `$transform` argument, and assets’ `mimeType` GraphQL fields now support a `@transform` directive. ([#12269](https://github.com/craftcms/cms/discussions/12269), [#12397](https://github.com/craftcms/cms/pull/12397), [#12522](https://github.com/craftcms/cms/pull/12522))
- `craft\helpers\App::env()` now returns `null` if the original value was `'null'`. ([#12742](https://github.com/craftcms/cms/pull/12742))
- `craft\helpers\Db::escapeParam()` now escapes `:empty:` and `:notempty:` strings. ([#12691](https://github.com/craftcms/cms/discussions/12691))

### Extensibility
- Added support for private plugins. ([#12716](https://github.com/craftcms/cms/pull/12716), [#8908](https://github.com/craftcms/cms/discussions/8908))
- Added the `elements/revisions` action. ([#12211](https://github.com/craftcms/cms/pull/12211))
- Console controllers that directly use `craft\console\ControllerTrait` no longer need to call `$this->checkTty()` or `$this->checkRootUser()` themselves; they are now called from `ControllerTrait::init()` and `beforeAction()`.
- Element source definitions can now include a `defaultSourcePath` key.
- Element custom field validation now respects the list of attributes passed to `validate()`.
- Improving IDE autocompletion for chained query param calls. ([#12656](https://github.com/craftcms/cms/pull/12656))
- Added `craft\base\ApplicationTrait::getEditionHandle()`.
- Added `craft\base\BaseFsInterface`. ([#12709](https://github.com/craftcms/cms/pull/12709))
- Added `craft\base\Batchable`.
- Added `craft\base\Element::cpRevisionsUrl()`.
- Added `craft\base\Element::indexElements()`.
- Added `craft\base\ElementInterface::findSource()`.
- Added `craft\base\ElementInterface::getCpRevisionsUrl()`.
- Added `craft\base\ElementInterface::getUiLabelPath()`.
- Added `craft\base\ElementInterface::indexElementCount()`.
- Added `craft\base\ElementInterface::setUiLabelPath()`.
- Added `craft\base\Event`, which provides a `once()` static method for registering an event handler that will only be triggered up to one time.
- Added `craft\console\ControllerTrait::beforeAction()`.
- Added `craft\console\ControllerTrait::failure()`.
- Added `craft\console\ControllerTrait::init()`.
- Added `craft\console\ControllerTrait::markdownToAnsi()`.
- Added `craft\console\ControllerTrait::note()`.
- Added `craft\console\ControllerTrait::options()`.
- Added `craft\console\ControllerTrait::runAction()`.
- Added `craft\console\ControllerTrait::success()`.
- Added `craft\console\ControllerTrait::tip()`.
- Added `craft\console\ControllerTrait::warning()`.
- Added `craft\db\QueryBatcher`.
- Added `craft\debug\DumpPanel`.
- Added `craft\elements\conditions\assets\ViewableConditionRule`. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added `craft\elements\conditions\ElementCondition::$referenceElement`.
- Added `craft\elements\conditions\entries\ViewableConditionRule`. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added `craft\elements\Entry::EVENT_DEFINE_PARENT_SELECTION_CRITERIA`. ([#12475](https://github.com/craftcms/cms/discussions/12475))
- Added `craft\events\DefineInputOptionsEvent`. ([#12351](https://github.com/craftcms/cms/pull/12351))
- Added `craft\events\ListVolumesEvent`.
- Added `craft\events\UserPhotoEvent`. ([#12360](https://github.com/craftcms/cms/pull/12360))
- Added `craft\fields\BaseOptionsField::EVENT_DEFINE_OPTIONS`. ([#12351](https://github.com/craftcms/cms/pull/12351))
- Added `craft\fields\BaseRelationField::$branchLimit`.
- Added `craft\fields\BaseRelationField::$maintainHierarchy`.
- Added `craft\helpers\Db::rawTableShortName()`.
- Added `craft\helpers\ImageTransforms::generateTransform()`.
- Added `craft\helpers\ImageTransforms::parseTransformString()`.
- Added `craft\helpers\StringHelper::toHandle()`.
- Added `craft\helpers\Template::fallback()`.
- Added `craft\helpers\Template::fallbackExists()`.
- Added `craft\helpers\Template::preloadSingles()`.
- Added `craft\image\Raster::scaleToFitAndFill()`.
- Added `craft\image\Raster::setFill()`.
- Added `craft\imagetransforms\FallbackTransformer`.
- Added `craft\models\CategoryGroup::$dateDeleted`.
- Added `craft\models\ImageTransform::$fill`.
- Added `craft\models\ImageTransform::$upscale`.
- Added `craft\models\TagGroup::$dateDeleted`.
- Added `craft\models\VolumeFolder::getHasChildren()`.
- Added `craft\models\VolumeFolder::setHasChildren()`.
- Added `craft\queue\BaseBatchedJob`. ([#12638](https://github.com/craftcms/cms/pull/12638))
- Added `craft\queue\jobs\GenerateImageTransform`. ([#12340](https://github.com/craftcms/cms/pull/12340))
- Added `craft\services\Assets::createFolderQuery()`.
- Added `craft\services\Assets::foldersExist()`.
- Added `craft\services\Elements::deleteElementForSite()`.
- Added `craft\services\Elements::deleteElementsForSite()`.
- Added `craft\services\Elements::EVENT_AFTER_DELETE_FOR_SITE`. ([#12354](https://github.com/craftcms/cms/issues/12354))
- Added `craft\services\Elements::EVENT_BEFORE_DELETE_FOR_SITE`. ([#12354](https://github.com/craftcms/cms/issues/12354))
- Added `craft\services\Entries::getSingleEntriesByHandle()`. ([#12698](https://github.com/craftcms/cms/pull/12787))
- Added `craft\services\Fields::getFieldsByType()`. ([#12381](https://github.com/craftcms/cms/discussions/12381))
- Added `craft\services\Path::getImageTransformsPath()`.
- Added `craft\services\Search::normalizeSearchQuery()`.
- Added `craft\services\Users::EVENT_AFTER_DELETE_USER_PHOTO`. ([#12360](https://github.com/craftcms/cms/pull/12360))
- Added `craft\services\Users::EVENT_AFTER_SAVE_USER_PHOTO`. ([#12360](https://github.com/craftcms/cms/pull/12360))
- Added `craft\services\Users::EVENT_BEFORE_DELETE_USER_PHOTO`. ([#12360](https://github.com/craftcms/cms/pull/12360))
- Added `craft\services\Users::EVENT_BEFORE_SAVE_USER_PHOTO`. ([#12360](https://github.com/craftcms/cms/pull/12360))
- Added `craft\utilities\AssetIndexes::EVENT_LIST_VOLUMES`. ([#12383](https://github.com/craftcms/cms/pull/12383), [#12443](https://github.com/craftcms/cms/pull/12443))
- Renamed `craft\elements\conditions\assets\EditableConditionRule` to `SavableConditionRule`, while preserving the original class name with an alias. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Renamed `craft\elements\conditions\entries\EditableConditionRule` to `SavableConditionRule`, while preserving the original class name with an alias. ([#12266](https://github.com/craftcms/cms/pull/12266))
- `craft\services\AssetIndexer::startIndexingSession()` and `createIndexingSession()` now have a `$listEmptyFolders` argument. ([#12604](https://github.com/craftcms/cms/pull/12604))
- `craft\base\ElementQuery::joinElementTable()` now accepts table names in the format of `{{%tablename}}`.
- `craft\models\Volume` now implements `craft\base\BaseFsInterface`. ([#12709](https://github.com/craftcms/cms/pull/12709))
- `craft\services\Categories::getGroupByHandle()` now has a `$withTrashed` argument.
- `craft\services\Globals::getSetByHandle()` now has a `$withTrashed` argument.
- `craft\services\Tags::getTagGroupByHandle()` now has a `$withTrashed` argument.
- `craft\services\Users::sendActivationEmail()`, `sendNewEmailVerifyEmail()`, `sendPasswordResetEmail()`, `getActivationUrl()`, `getEmailVerifyUrl()`, `getPasswordResetUrl()`, and `setVerificationCodeOnUser()` will now throw a `craft\errors\InvalidElementException` if the user has a non-unique email. ([#12431](https://github.com/craftcms/cms/issues/12431), [#12712](https://github.com/craftcms/cms/pull/12712))
- Deprecated `craft\elements\Asset::getFs()`. `getVolume()` should be used instead.
- Deprecated `craft\helpers\Assets::sortFolderTree()`.
- Deprecated `craft\imagetransforms\ImageTransformer::ensureTransformUrlByIndexModel()`. `getTransformUrl()` should be used instead.
- Deprecated `craft\imagetransforms\ImageTransformer::procureTransformedImage()`. `generateTransform()` should be used instead.
- Deprecated `craft\queue\jobs\GeneratePendingTransforms`. `GenerateImageTransform` should be used instead. ([#12340](https://github.com/craftcms/cms/pull/12340))
- Deprecated `craft\services\Assets::getFolderTreeByFolderId()`.
- Deprecated `craft\services\Assets::getFolderTreeByVolumeIds`.
- Added `Craft.Accordion`. ([#12189](https://github.com/craftcms/cms/pull/12189))
- Added `Craft.AssetMover`.
- Added `Craft.BaseElementIndex::getSourcePathActionLabel()`.
- Added `Craft.BaseElementIndex::getSourcePathActions()`.
- Added `Craft.BaseElementIndex::getSourcePathLabel()`.
- Added `Craft.BaseElementIndex::onSourcePathChange()`.
- Added `Craft.BaseElementIndex::sourcePath`.
- Added `Craft.BaseElementSelectorModal::getElementIndexParams()`.
- Added `Craft.BaseElementSelectorModal::getIndexSettings()`.
- Added `Craft.BaseElementSelectorModal::hasSelection()`.
- Added `Craft.ElementFieldSettings`.
- Added `Craft.VolumeFolderSelectorModal`.
- Added `Garnish.MultiFunctionBtn`.
- The custom `activate` jQuery event will now trigger when the <kbd>Return</kbd> key is pressed.
- The custom `activate` jQuery event will no longer trigger for <kbd>Ctrl</kbd>/<kbd>Command</kbd>-clicks.
- Deprecated `Craft.CategorySelectInput`. `Craft.BaseElementSelectInput` should be used instead. ([#11749](https://github.com/craftcms/cms/pull/11749))

### System
- Improved element deletion performance. ([#12223](https://github.com/craftcms/cms/pull/12223))
- Improved queue performance. ([#12274](https://github.com/craftcms/cms/issues/12274), [#12340](https://github.com/craftcms/cms/pull/12340))
- “Applying new propagation method to elements”, “Propagating [element type]”, and “Resaving [element type]” queue jobs are now split up into batches of up to 100 items. ([#12638](https://github.com/craftcms/cms/pull/12638))
- Assets’ alternative text values are now included as search keywords.
- Entry type, section, category group, and tag group deletion no longer involves looping through all affected entries/categories/tags and deleting each one individually via `craft\services\Elements::deleteElement()`. ([#12665](https://github.com/craftcms/cms/pull/12665))
- Updated LitEmoji to v4. ([#12226](https://github.com/craftcms/cms/discussions/12226))
- Fixed a database deadlock error that could occur when updating a relation or structure position for an element that was simultaneously being saved. ([#9905](https://github.com/craftcms/cms/issues/9905))
- Fixed a bug where element query `select()` and `orderBy()` params could resolve element extension table column names to custom field columns, if a custom field had a conflicting handle. ([#12652](https://github.com/craftcms/cms/issues/12652))
- Fixed a bug where it was possible to activate inactive users with non-unique emails. ([#12431](https://github.com/craftcms/cms/issues/12431), [#12712](https://github.com/craftcms/cms/pull/12712))
- Fixed a bug where hint text within Selectize menus had extremely low contrast on hover. ([#12829](https://github.com/craftcms/cms/issues/12829))

## 4.3.11 - 2023-03-07

- Fixed a bug where `craft\events\RegisterElementSourcesEvent::$context` wasn’t always set to `modal` when defining the available element sources for an element selection modal.
- Fixed a styling bug where multi-line checkbox labels within the Customize Sources modal and View menu weren’t wrapping properly. ([#12717](https://github.com/craftcms/cms/issues/12717))
- Fixed a bug where asset thumbnails within collapsed Matrix blocks weren’t loading when the block was expanded. ([#12720](https://github.com/craftcms/cms/issues/12720))
- Fixed a bug where user activation/verification/reset-password URLs could be root-relative, if the `@web` alias was explicitly set to a root-relative URL.
- Fixed a PHP error that could occur if an options field’s condition rule wasn’t configured with any values. ([#12713](https://github.com/craftcms/cms/pull/12713))
- Fixed a bug where control panel notifications weren’t being announced to screen readers. ([#12714](https://github.com/craftcms/cms/pull/12714))
- Fixed a bug where localized relations could be moved to a newly-added site rather than copied, when applying project config changes. ([#12702](https://github.com/craftcms/cms/issues/12702))
- Fixed a bug where element indexes’ “View” buttons could be inconsistently positioned in the toolbar.
- Fixed a bug where element selector modal footers could hide the modal contents. ([#12708](https://github.com/craftcms/cms/issues/12708))
- Fixed a bug where asset thumbnails weren’t respecting the `generateTransformsBeforePageLoad` config setting. ([#12750](https://github.com/craftcms/cms/issues/12750))
- Fixed a bug where soft hyphens, non-breaking spaces, zero-width characters, invisible times characters, and invisible separators weren’t getting stripped from sanitized asset filenames. ([#12741](https://github.com/craftcms/cms/issues/12741), [#12759](https://github.com/craftcms/cms/pull/12759))
- Fixed a bug where custom fields’ database columns would get deleted when applying project config changes, if the field type wasn’t present. ([#12760](https://github.com/craftcms/cms/issues/12760))
- Fixed an error that could occur if a non-numeric value was entered into an image transform’s Width or Height settings. ([#12776](https://github.com/craftcms/cms/pull/12776))
- Fixed a bug where Assets, Categories, and Tags fields weren’t respecting their “Allow self relations” settings. ([#12769](https://github.com/craftcms/cms/issues/12769))
- Fixed a bug where dynamically-generated entry titles weren’t always generated with the site’s formatting locale in place. ([12780](https://github.com/craftcms/cms/issues/12780))
- Fixed a bug where element titles weren’t getting a pointer cursor or underlines on hover, when selected on an element index page.
- Fixed a bug where it wasn’t possible to close modals that were opened by a custom select menu via the <kbd>Esc</kbd> key. ([#12814](https://github.com/craftcms/cms/pull/12814))
- Fixed an error that could occur when saving an element with an eager-loaded Matrix field. ([#12815](https://github.com/craftcms/cms/issues/12815))
- Fixed a bug where the “send to Developer Support” links within the Craft Support widget weren’t working. ([#12822](https://github.com/craftcms/cms/issues/12822))

## 4.3.10 - 2023-02-17

- Fixed a bug where clicking on the scrollbar of a disclosure menu would close it. ([#12681](https://github.com/craftcms/cms/issues/12681))
- Fixed an error that could occur when loading the Plugin Store, if there wasn’t a `.env` file. ([#12687](https://github.com/craftcms/cms/issues/12687))
- Fixed a bug where large asset thumbnails weren’t centered within Assets fields. ([#12682](https://github.com/craftcms/cms/issues/12682))
- Fixed a PHP error that could occur when versioning asset URLs. ([#12678](https://github.com/craftcms/cms/issues/12678))
- Fixed a bug where the “Site” element condition rule was available for non-localizable element types’ conditions. ([#12601](https://github.com/craftcms/cms/pull/12601))
- Fixed a bug where it wasn’t possible to sort assets within subfolders by anything besides the asset title. ([#12688](https://github.com/craftcms/cms/issues/12688))

## 4.3.9 - 2023-02-14

- Image thumbnails and previews are no longer versioned if their image URL doesn’t begin with one of the asset’s base filesystem URLs. ([#12663](https://github.com/craftcms/cms/issues/12663))
- HTML Purifier now allows `oembed` tags. ([craftcms/ckeditor#59](https://github.com/craftcms/ckeditor/issues/59))
- Added `craft\htmlpurifier\VideoEmbedUrlDef`.
- `craft\helpers\Assets::revUrl()` now has an `$fsOnly` argument.
- Fixed a bug where entries that aren’t propagated to the primary site weren’t showing revision notes. ([#12641](https://github.com/craftcms/cms/issues/12641))
- Fixed a bug where HTML tags weren’t getting stripped from auto-generated Handle and URI Format setting values.
- Fixed a JavaScript error that could occur if an object with `null` values was passed to `Craft.compare()`.
- Fixed a bug where `craft\elements\db\ElementQuery::toArray()` was calling getter methods whose names conflicted with custom field handles. ([#12635](https://github.com/craftcms/cms/pull/12635))

## 4.3.8.2 - 2023-02-08

- Fixed a PHP error that could occur if relational fields were getting eager-loaded for elements that the fields didn’t belong to. ([#12648](https://github.com/craftcms/cms/issues/12648))

## 4.3.8.1 - 2023-02-08

- Fixed a PHP error that occurred after performing a Composer action within Craft. ([#12647](https://github.com/craftcms/cms/issues/12647))
- Fixed a bug where element attributes weren’t getting eager-loaded. ([#12646](https://github.com/craftcms/cms/pull/12646), [#12645](https://github.com/craftcms/cms/issues/12645))
- Fixed a bug where image previews weren’t getting versioned, unless the `revAssetUrls` config setting was enabled. ([#12603](https://github.com/craftcms/cms/issues/12603))

## 4.3.8 - 2023-02-07

- Updated Composer to 2.2.19, fixing a PHP error that could occur when performing a Composer action within Craft, when the autoload classmap was generated with Composer 2.5. ([#12482](https://github.com/craftcms/cms/issues/12482))
- Fixed a bug where Matrix blocks weren’t getting eager-loaded. ([#12631](https://github.com/craftcms/cms/issues/12631))
- Fixed a PHP error that could occur when calling `craft\services\Assets::getAllDescendantFolders()` for the root folder. ([craftcms/feed-me#1231](https://github.com/craftcms/feed-me/issues/1231))
- Fixed a bug where revision notes weren’t always being retained for provisional drafts. ([#12641](https://github.com/craftcms/cms/issues/12641))
- Fixed a bug where it wasn’t possible to access the Customize Sources modal on element index pages, if all sources were disabled. ([#12634](https://github.com/craftcms/cms/issues/12634))

## 4.3.7.1 - 2023-02-05

- The `_includes/forms/checkbox.twig`, `select.twig`, and `text.twig` templates no longer add an `aria-labelledby` attribute to the input if an `aria-label` attribute is also specified.
- `craft\helpers\Cp::dateTimeFieldHtml()` now returns inputs contained within a fieldset.
- Fixed a bug where some inputs were getting `aria-labelledby` attributes that referenced a nonexistent label ID.
- Fixed a bug where the first changes to entries weren’t saving properly. ([#12623](https://github.com/craftcms/cms/issues/12623), [#12592](https://github.com/craftcms/cms/issues/12592))
- Fixed a bug where `craft\behaviors\CustomFieldBehavior::$owner` was getting nullified by `craft\base\Element::fieldByHandle()`. ([#12624](https://github.com/craftcms/cms/issues/12624))

## 4.3.7 - 2023-02-03

- Improved the performance of the “Generating pending image transforms” queue job. ([#12274](https://github.com/craftcms/cms/issues/12274))
- Removed the “Timeout” setting from the Gmail and SMTP mailer transporters, as they aren’t supported by Symfony Mailer’s transport configuration.
- Removed the “Encryption Method” setting from the SMTP mailer transporter, as Symfony Mailer already uses TLS when port 465 is used (as it is by default).
- The “Port” setting on the SMTP mailer transporter is no longer required. It will default to 465 or 25 depending on whether OpenSSL is installed.
- Added more reserved field handles to avoid conflicts with `craft\base\Element` properties. ([#12577](https://github.com/craftcms/cms/issues/12577))
- Assets’ `alt` properties are now automatically trimmed of leading/trailing whitespace.
- Asset element components in the control panel now include a `data-alt` attribute. ([#12600](https://github.com/craftcms/cms/discussions/12600))
- Control panel requests no longer override the `pageTrigger` config setting value to `'p'`. ([#12598](https://github.com/craftcms/cms/issues/12598), [#12614](https://github.com/craftcms/cms/pull/12614))
- Fixed field status badge styling in some contexts. ([#12403](https://github.com/craftcms/cms/issues/12403))
- Fixed a bug where exporting elements with multiple field layouts as a CSV file using the “Expanded” export type would result in mismatched column values.
- Fixed a bug where cancelling a conflicting volume folder move would result in the moved folder getting deleted.
- Fixed a bug where `craft\elements\Category::canDuplicate()` wasn’t factoring in save permissions.
- Fixed a bug where the horizontal scroll position wasn’t being retained when refreshing Live Preview. ([#12504](https://github.com/craftcms/cms/issues/12504))
- Fixed a bug where user group condition rules for the default user group weren’t getting matched properly during public registration. ([#12283](https://github.com/craftcms/cms/issues/12283))
- Fixed a bug where HTML tags within field labels, instructions, tips, and warnings weren’t always getting escaped.
- Fixed a bug where the sidebar scroll position wasn’t retained when selecting a new source on element index pages. ([#12523](https://github.com/craftcms/cms/issues/12523))
- Fixed a bug where `resave/*` commands’ output didn’t take the offset into account. ([#12526](https://github.com/craftcms/cms/issues/12526))
- Fixed a bug where warnings were getting logged for video assets that were “missing” their dimensions.
- Fixed a bug where `craft\services\Assets::getAllDescendantFolders()` could return unexpected results for folders that contained an underscore.
- Fixed a bug where accessing a custom field’s magic property on an element would return the field’s raw database value rather than `null`, if it didn’t belong to the element’s field layout anymore. ([#12539](https://github.com/craftcms/cms/issues/12539), [#12578](https://github.com/craftcms/cms/pull/12578))
- Fixed a bug where previewing an asset could wipe out all `h1` tags within Redactor fields. ([#12545](https://github.com/craftcms/cms/issues/12545))
- Fixed a bug where `craft\image\Raster::getIsTransparent()` wasn’t working. ([#12565](https://github.com/craftcms/cms/issues/12565))
- Fixed a bug where textual condition rules were still showing a text input when the “is empty” or “has a value” operators were selected. ([#12587](https://github.com/craftcms/cms/pull/12587))
- Fixed a bug where the component name comments in project config YAML files would always lag behind the current project config a little. ([#12576](https://github.com/craftcms/cms/issues/12576), [#12581](https://github.com/craftcms/cms/pull/12581))
- Fixed a SQL error that occurred when creating a database backup using the default backup command, when running MySQL 5.7.41+ or 8.0.32+. ([#12557](https://github.com/craftcms/cms/issues/12557), [#12560](https://github.com/craftcms/cms/pull/12560))
- Fixed a bug where database backups weren’t respecting SSL database connection settings if they were specified when using MySQL. ([#10351](https://github.com/craftcms/cms/issues/10351), [#11753](https://github.com/craftcms/cms/issues/11753), [#12596](https://github.com/craftcms/cms/pull/12596))
- Fixed a bug where element indexes could stop showing their loading spinner prematurely if the element listing needed to be reloaded multiple times in rapid succession. ([#12595](https://github.com/craftcms/cms/issues/12595))
- Fixed a bug where element editors wouldn’t show tabs that didn’t contain any characters that could be converted to ASCII. ([#12602](https://github.com/craftcms/cms/issues/12602))
- Fixed a bug where asset thumbnails weren’t getting versioned in the control panel, unless the `revAssetUrls` config setting was enabled. ([#12603](https://github.com/craftcms/cms/issues/12603))
- Fixed a bug where entry relations could be lost when switching entry types, for relational fields that didn’t exist on the prior entry type. ([#12592](https://github.com/craftcms/cms/issues/12592))
- Fixed a bug where Matrix blocks weren’t getting duplicated to other sites when first editing an unpublished draft. ([#11366](https://github.com/craftcms/cms/issues/11366))
- Fixed a bug where element indexes would show an expand/collapse toggle for structured elements that only had unsaved draft children, which aren’t actually shown. ([#11253](https://github.com/craftcms/cms/issues/11253))
- Fixed a SQL error that occurred when running the `index-assets` command on PostgreSQL. ([#12617](https://github.com/craftcms/cms/issues/12617))
- Added `craft\helpers\Assets::revUrl()`.
- Added `craft\helpers\Db::escapeForLike()`.
- Added `craft\web\twig\variables\Paginate::$pageTrigger`. ([#12614](https://github.com/craftcms/cms/pull/12614))
- `craft\helpers\Assets::revParams()` no longer takes the `revAssetUrls` config setting into account. That should be factored in by whatever is calling it.
- `craft\services\Assets::getAllDescendantFolders()` now has a `$withParent` argument, which can be passed `false` to omit the parent folder from the results. ([#12536](https://github.com/craftcms/cms/issues/12536))
- `craft\services\Matrix::duplicateBlocks()` now has a `$force` argument.
- Deprecated `craft\helpers\DateTimeHelper::timeZoneAbbreviation()`.
- Deprecated `craft\helpers\DateTimeHelper::timeZoneOffset()`.

## 4.3.6.1 - 2023-01-09

- Element edit pages now retain their previous scroll position when they’re automatically refreshed to keep up with changes from another browser tab.
- Fixed a bug where editing certain Matrix/Neo/Super Table fields could result in content loss. ([#12445](https://github.com/craftcms/cms/issues/12445))

## 4.3.6 - 2023-01-04

- Template caching is no longer enabled for tokenized requests. ([#12458](https://github.com/craftcms/cms/issues/12458))
- Elisions are now stripped from search keywords. ([#12467](https://github.com/craftcms/cms/issues/12467), [#12474](https://github.com/craftcms/cms/pull/12474))
- Added support for HEIC/HEIF images. ([#9115](https://github.com/craftcms/cms/discussions/9115))
- The `allowedFileExtensions` config setting now includes `heic`, `heif`, and `hevc` by default. ([#12490](https://github.com/craftcms/cms/discussions/12490))
- It’s now possible to assign aliases `children` fields queried via GraphQL. ([#12494](https://github.com/craftcms/cms/pull/12494))
- Added `craft\helpers\Image::isWebSafe()`.
- Added `craft\services\Images::getSupportsHeic()`.
- `Craft.MatrixInput` now fires `blockSortDragStop`, `beforeMoveBlockUp`, `moveBlockUp`, `beforeMoveBlockDown`, and `moveBlockDown` events. ([#12498](https://github.com/craftcms/cms/pull/12498))
- Fixed a bug where deleting a field layout tab could result in duplicated tabs. ([#12459](https://github.com/craftcms/cms/issues/12459))
- Fixed a bug where Feed widgets weren’t showing feed items when they were first loaded. ([#12460](https://github.com/craftcms/cms/issues/12460))
- Fixed an error that occurred when a Date field’s condition rule was set to a relative range, “has a value”, or “is empty”. ([#12457](https://github.com/craftcms/cms/issues/12457))
- Fixed an error that could occur when processing template caches in a console request, if a globally-scoped template cache was processed before it.
- Fixed styling issues with large element thumbnail views. ([#12455](https://github.com/craftcms/cms/issues/12455))
- Fixed a bug where it wasn’t possible to retry all failed jobs when using a proxy queue. ([#12471](https://github.com/craftcms/cms/issues/12471))
- Fixed a bug where the selected table columns would be forgotten if modified from a nested source. ([#12477](https://github.com/craftcms/cms/issues/12477))
- Fixed a bug where some custom field property types in `craft\behaviors\CustomFieldBehavior` were incorrect.
- Fixed an error that could occur if a Matrix sub-field’s handle was too long. ([#12422](https://github.com/craftcms/cms/issues/12422))
- Fixed a bug where element editor slideouts’ submit buttons weren’t always consistent with the full-page edit form. ([#12487](https://github.com/craftcms/cms/issues/12487))
- Fixed an XSS vulnerability.

## 4.3.5 - 2022-12-13

- Fixed a bug where entry tab contents could remain visible when switching to other tabs, after changing the entry type.
- Fixed a bug where it wasn’t possible to enter `0` in Number fields, Money fields, and numeric column cells within editable tables, using certain keyboard layouts. ([#12412](https://github.com/craftcms/cms/issues/12412))
- Fixed a bug where the default MySQL restore command would attempt to use credentials from `~/.my.cnf` if it existed, instead of Craft’s configured database connection settings. ([#12349](https://github.com/craftcms/cms/issues/12349), [#12430](https://github.com/craftcms/cms/pull/12430))
- Fixed a JavaScript error that could occur when autosaving an entry draft. ([#12445](https://github.com/craftcms/cms/issues/12445))
- Added `craft\base\ApplicationTrait::onInit()`. ([#12439](https://github.com/craftcms/cms/pull/12439))
- Added `craft\console\Controller::createDirectory()`. ([#12438](https://github.com/craftcms/cms/pull/12438))
- Added `craft\console\Controller::do()`. ([#12438](https://github.com/craftcms/cms/pull/12438))
- Added `craft\console\Controller::failure()`. ([#12438](https://github.com/craftcms/cms/pull/12438))
- Added `craft\console\Controller::note()`. ([#12438](https://github.com/craftcms/cms/pull/12438))
- Added `craft\console\Controller::success()`. ([#12438](https://github.com/craftcms/cms/pull/12438))
- Added `craft\console\Controller::tip()`. ([#12438](https://github.com/craftcms/cms/pull/12438))
- Added `craft\console\Controller::warning()`. ([#12438](https://github.com/craftcms/cms/pull/12438))
- Added `craft\console\Controller::writeJson()`. ([#12438](https://github.com/craftcms/cms/pull/12438))
- Added `craft\console\Controller::writeToFile()`. ([#12438](https://github.com/craftcms/cms/pull/12438))
- Added `craft\helpers\FileHelper::absolutePath()`.
- Added `craft\helpers\FileHelper::findClosestFile()`.
- Added `craft\helpers\FileHelper::isWithin()`.
- Added `craft\helpers\FileHelper::relativePath()`.
- Added `craft\helpers\Json::decodeFromFile()`.
- Added `Craft.filterInputVal()`.
- Added `Craft.filterNumberInputVal()`.

## 4.3.4 - 2022-11-29

- The `serve` command now routes requests for nonexistent files to Craft. ([#12310](https://github.com/craftcms/cms/pull/12310))
- `craft\base\Element::includeSetStatusAction()` now returns `false` by default regardless of what `hasStatuses()` returns, fixing a bug where some element indexes were unexpectedly getting “Set Status” actions.
- Query conditions generated by `craft\helpers\Db::parseParam()` no longer account for `null` values, due to a heavy performance impact. ([#11931](https://github.com/craftcms/cms/issues/11931))
- Fixed a bug where the default MySQL backup command would attempt to use credentials from `~/.my.cnf` if it existed, instead of Craft’s configured database connection settings. ([#12349](https://github.com/craftcms/cms/issues/12349))
- Fixed a bug where it wasn’t possible to access the Customize Sources modal if all sources were disabled. ([#12369](https://github.com/craftcms/cms/issues/12369))
- Fixed a bug where Assets fields weren’t taking their “Show unpermitted volumes” setting into account when defining the available input sources. ([#12364](https://github.com/craftcms/cms/issues/12364))
- Fixed a bug where user slideouts included status toggles.
- Updated Yii to 2.0.47.

## 4.3.3 - 2022-11-17

- Fixed an error that occurred if an arrow function was passed to the `|sort` Twig filter. ([#12334](https://github.com/craftcms/cms/issues/12334))
- Fixed a bug where nested fields set to numbers could be inadvertently changed when an entry draft was created.
- `craft\services\Fields::getFieldsWithContent()` and `getFieldsWithoutContent()` now have `$context` arguments.

## 4.3.2.1 - 2022-11-16

- Fixed a PHP error that could occur when generating an image transform. ([#12333](https://github.com/craftcms/cms/issues/12333))

## 4.3.2 - 2022-11-16

- `firstName` and `lastName` are now reserved field handles for the user field layout. ([#12241](https://github.com/craftcms/cms/issues/12241), [#12316](https://github.com/craftcms/cms/pull/12316))
- Asset filenames are truncated in large thumbnail views, as they were before 4.3.0. ([#12236](https://github.com/craftcms/cms/discussions/12236))
- Fixed an information disclosure vulnerability.
- Fixed an XSS vulnerability.
- Fixed asset indexing for Local filesystems that were configured to use a symlinked server path.
- Fixed an error that could occur when calling `craft\base\Element::getCanonicalUid()` on a draft loaded for a site that the canonical element didn’t exist on yet. ([#12228](https://github.com/craftcms/cms/issues/12228))
- Fixed a bug where long words in asset titles weren’t wrapping in the large thumbnail view. ([#12237](https://github.com/craftcms/cms/issues/12237))
- Fixed a bug where the Users index page was showing a “Set Status” bulk action, making it possible to disable users.
- Disabled users now identify themselves as disabled, and Edit User pages now provide a way for them to be re-enabled.
- Fixed a layout issue that could occur on Assets fields using the “Large Thumbnails” view mode. ([#12230](https://github.com/craftcms/cms/issues/12230))
- Fixed a bug where elements that weren’t viewable by the current user could still be hyperlinked in element indexes.
- Fixed a bug where `craft\helpers\DateTimeHelper::toDateInterval()` could return a `DateInterval` that was off by an hour around daylight savings time changes.
- Fixed a bug where sticky element index footers were obstructed when the Debug Toolbar was enabled. ([#12242](https://github.com/craftcms/cms/issues/12242))
- Fixed a bug where it wasn’t possible to change the sort attribute on element indexes while searching. ([#12256](https://github.com/craftcms/cms/issues/12256))
- Fixed a bug where `resave/*` commands weren’t catching exceptions thrown when applying the `--set` and `--to` options. ([#12262](https://github.com/craftcms/cms/issues/12262))
- Fixed the position of field status indicators within Matrix fields in Live Preview. ([#12287](https://github.com/craftcms/cms/issues/12287))
- Fixed PHP errors that could occur when executing GraphQL queries. ([#12271](https://github.com/craftcms/cms/pull/12271), [#12275](https://github.com/craftcms/cms/pull/12275))
- Fixed a bug where it was possible to add a “Status” condition rule to relational fields’ selectable element conditions. ([#12289](https://github.com/craftcms/cms/issues/12289))
- Fixed a PHP error that occurred if a field type stored enum values. ([#12297](https://github.com/craftcms/cms/issues/12297))
- Fixed a bug where PHP errors and exceptions thrown when formatting a control panel screen response weren’t being handled properly. ([#12308](https://github.com/craftcms/cms/issues/12308))
- Fixed a bug where element indexes were showing the labels of empty Dropdown options when selected. ([#12319](https://github.com/craftcms/cms/issues/12319))
- Fixed condition builder styling issues that occurred if any rules had exceptionally long names. ([#12311](https://github.com/craftcms/cms/issues/12311))
- Fixed an error that occurred when saving an entry via GraphQL, if a parent entry was assigned that didn’t exist on the requested site. ([#12291](https://github.com/craftcms/cms/issues/12291))
- Fixed a bug where conditional fields weren’t always saving for elements that didn’t support autosaved drafts. ([#12166](https://github.com/craftcms/cms/issues/12166))
- Fixed a bug where fields set to numbers could be inadvertently changed when an entry draft was created.
- Added `craft\base\Element::includeSetStatusAction()`.
- Added `craft\services\Fields::getFieldsWithoutContent()`.

## 4.3.1 - 2022-10-27

- Numeric values are no longer automatically formatted within element indexes. ([#12205](https://github.com/craftcms/cms/issues/12205))
- When `craft\base\Element::EVENT_DEFINE_URL` event handlers set `craft\events\DefineUrlEvent::$url` to `null`, that will no longer be respected unless `$handled` was also set to `true`. ([#12210](https://github.com/craftcms/cms/issues/12210), [nystudio107/craft-imageoptimize#359](https://github.com/nystudio107/craft-imageoptimize/issues/359))
- Asset folder and file names are now converted to ASCII using the primary site’s language for character mappings, regardless of the current user’s preferred language, when the `convertFilenamesToAscii` config setting is enabled. ([#12207](https://github.com/craftcms/cms/discussions/12207))
- Fixed a bug where locked users’ remaining cooldown times could be calculated incorrectly on PHP 8.0.
- Fixed an infinite recursion bug that occurred when editing a Matrix field with a custom propagation method. ([#12176](https://github.com/craftcms/cms/issues/12176))
- Fixed a bug where element index source lists were showing headings that didn’t have any visible nested sources. ([#12193](https://github.com/craftcms/cms/issues/12193))
- Fixed a JavaScript 404 error that occurred when users’ Language was set to Chinese. ([#12194](https://github.com/craftcms/cms/issues/12194))
- Fixed a bug where users couldn’t delete their own addresses. ([craftcms/commerce#3011](https://github.com/craftcms/commerce/issues/3011))
- Fixed a bug where the `users/save-address` action wasn’t validating addresses properly.
- Fixed an error that could occur during garbage collection, if any nested volume folders were missing their path. ([#12195](https://github.com/craftcms/cms/issues/12195))

## 4.3.0 - 2022-10-25

### Content Management
- Added a “View” menu to element indexes, which enable users to customize the visible table columns for themselves, without affecting other users. ([#11915](https://github.com/craftcms/cms/pull/11915))
- Added source setting menus to element index pages, which now contain the “Customize sources” option when allowed, and “New subfolder”, “Rename folder”, and “Delete folder” actions on the Assets index page. ([#11906](https://github.com/craftcms/cms/pull/11906))
- Added the “Editable” rule to asset and entry conditions. ([#11995](https://github.com/craftcms/cms/discussions/11995))
- Improved control panel mobile support. ([#11963](https://github.com/craftcms/cms/pull/11963), [#12005](https://github.com/craftcms/cms/pull/12005))
- Element indexes now respect field layouts’ user conditions when determining which custom field columns to show. ([#11913](https://github.com/craftcms/cms/pull/11913))
- Element index URLs now include the selected source key in a `source` query param, so all sources are now deep-linkable, including custom sources. ([#11996](https://github.com/craftcms/cms/discussions/11996))
- Notifications are now shown after executing folder actions on the Assets index page. ([#11906](https://github.com/craftcms/cms/pull/11906))
- Date range condition rules now support “Today”, “This week”, “This month”, “This year”, “Past 7 days”, “Past 30 days”, “Past 30 days”, “Past year”, “Before…”, and “After…” relative range types, in addition to specifying a custom date range. ([#10749](https://github.com/craftcms/cms/discussions/10749), [#11888](https://github.com/craftcms/cms/pull/11888))
- Number condition rules now support an “is between…” operator. ([#11950](https://github.com/craftcms/cms/pull/11950))
- All text, number, and date range condition rules now support “has a value” and “is empty” operators. ([#11070](https://github.com/craftcms/cms/discussions/11070))
- “Save as a new [element type]” actions will now discard any unsaved changes on the original element after the new element is created. ([#10204](https://github.com/craftcms/cms/issues/10204), [#11959](https://github.com/craftcms/cms/issues/11959), [#12102](https://github.com/craftcms/cms/issues/12102), [#12165](https://github.com/craftcms/cms/issues/12165))
- If Live Preview is triggered while a draft is saving, it will now wait until the save completes before opening. ([#11858](https://github.com/craftcms/cms/issues/11858), [#11895](https://github.com/craftcms/cms/pull/11895))
- Addresses now support change tracking.
- It’s now possible to restore assets that were deleted programmatically with `craft\elements\Asset::$keepFile` set to `true`. ([#11761](https://github.com/craftcms/cms/issues/11761))

### Accessibility
- Improved the styling of selected sidebar navigation items to meet contrast requirements. ([#11589](https://github.com/craftcms/cms/pull/11589))
- Improved the styling of slideouts for screens under 600 pixels wide. ([#11636](https://github.com/craftcms/cms/pull/11636))
- Improved the styling of the primary button on element index pages for small screens. ([#11963](https://github.com/craftcms/cms/pull/11963))
- Improved the contrast of modal resize handles. ([#11727](https://github.com/craftcms/cms/pull/11727))
- Improved the contrast of fields’ searchable and translatable indicator icons on the Fields index page. ([#12169](https://github.com/craftcms/cms/pull/12169))
- Improved the contrast of the “Same as language” text within the “Formatting Locale” user preference. ([#11767](https://github.com/craftcms/cms/pull/11767))
- Improved the contrast of the selected options within combo boxes. ([#11662](https://github.com/craftcms/cms/pull/11662))
- Improved the readability of the global navigation and date pickers when text is resized to 200%. ([#11767](https://github.com/craftcms/cms/pull/11767))
- Improved focus management for HUD components. ([#11985](https://github.com/craftcms/cms/pull/11985))
- Improved focus management for element index pagination links.  ([#11565](https://github.com/craftcms/cms/pull/11565)).
- Improved focus management for editable tables. ([#11662](https://github.com/craftcms/cms/pull/11662))
- Custom slider components now support <kbd>Home</kbd> and <kbd>End</kbd> key presses. ([#11578](https://github.com/craftcms/cms/pull/11578))
- Improved the markup and keyboard control for exclusive button groups. ([#11942](https://github.com/craftcms/cms/pull/11942))
- Improved keyboard control for the widget manager HUD on the Dashboard. ([#11578](https://github.com/craftcms/cms/pull/11578))
- Improved New Widget and Quick Post action menus for screen readers. ([#11611](https://github.com/craftcms/cms/pull/11611))
- Improved the widget manager HUD for screen readers. ([#11945](https://github.com/craftcms/cms/pull/11945))
- Improved the Craft Support widget for screen readers. ([#11703](https://github.com/craftcms/cms/pull/11703))
- Improved element index view mode buttons for screen readers. ([#11613](https://github.com/craftcms/cms/pull/11613))
- Improved element type selects within “Related To” condition rules for screen readers. ([#11662](https://github.com/craftcms/cms/pull/11662))
- Improved element action modals for screen readers. ([#11905](https://github.com/craftcms/cms/issues/7377))
- Improved field instructions, errors, and other visually-related text for screen readers. ([#11763](https://github.com/craftcms/cms/issues/7377))
- Improved field translation indicators for screen readers. ([#11662](https://github.com/craftcms/cms/pull/11662))
- Improved the parent selection button on structured entries’ and categories’ edit pages for screen readers. ([#11662](https://github.com/craftcms/cms/pull/11662))
- Improved the Rotate button within Live Preview for screen readers. ([#11953](https://github.com/craftcms/cms/pull/11953))
- Improved Date fields for screen readers. ([#10546](https://github.com/craftcms/cms/pull/10546))
- Fixed a mismatch between the visible and accessible text on the site name in the global sidebar. ([#11767](https://github.com/craftcms/cms/pull/11767))
- Added alternative text to element status indicators. ([#11604](https://github.com/craftcms/cms/pull/11604))
- Feed, Recent Entries, and Drafts widgets now use semantic lists instead of tables. ([#11952](https://github.com/craftcms/cms/pull/11952))
- Added autocomplete attributes to several address fields. ([#11767](https://github.com/craftcms/cms/pull/11767))
- Implemented fieldsets and descriptive headings on user accounts’ “Preferences” tab. ([#11534](https://github.com/craftcms/cms/pull/11534))
- The “All” status option within element index toolbars now uses a differentiated gradient icon. ([#11911](https://github.com/craftcms/cms/pull/11911))
- Improved status indicator shapes, when the “Use shapes to represent statuses” user preference is enabled. ([#11911](https://github.com/craftcms/cms/pull/11911))
- Boolean table columns within element indexes now use check icons to indicate `true` values. ([#11911](https://github.com/craftcms/cms/pull/11911))
- Element titles are no longer truncated on edit pages. ([#11768](https://github.com/craftcms/cms/pull/11768))
- Asset titles are no longer truncated in asset indexes. ([#11775](https://github.com/craftcms/cms/pull/11775))
- Element source navigation now programmatically conveys structure imparted by headings. ([#11777](https://github.com/craftcms/cms/pull/11777))
- Added a warning icon to related elements that contain validation errors. ([#11610](https://github.com/craftcms/cms/pull/11610))
- Element index footers now stick to the bottom of the window, and element action triggers are now inserted into the footer rather than replacing the contents of the page’s toolbar. ([#11844](https://github.com/craftcms/cms/pull/11844))

### Administration
- Added the `extraLastNamePrefixes` config setting. ([#11903](https://github.com/craftcms/cms/pull/11903))
- Added the `extraNameSalutations` config setting. ([#11903](https://github.com/craftcms/cms/pull/11903))
- Added the `extraNameSuffixes` config setting. ([#11903](https://github.com/craftcms/cms/pull/11903))
- Element sources now have a “Default Sort” setting in the Customize Sources modal. ([#12002](https://github.com/craftcms/cms/discussions/12002))
- Control panel-defined image transforms can now have custom quality values. ([#9622](https://github.com/craftcms/cms/discussions/9622))
- Added support for the `CRAFT_DOTENV_PATH` PHP constant. ([#11894](https://github.com/craftcms/cms/discussions/11894))
- Added support for `CRAFT_WEB_URL` and `CRAFT_WEB_ROOT` environment variables/PHP constants, which can be used to set the default `@web` and `@webroot` alias values. ([#11912](https://github.com/craftcms/cms/pull/11912))

### Development
- Added the `canCreateDrafts()` Twig function. ([#11797](https://github.com/craftcms/cms/discussions/11797), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added the `canDelete()` Twig function. ([#11797](https://github.com/craftcms/cms/discussions/11797), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added the `canDeleteForSite()` Twig function. ([#11797](https://github.com/craftcms/cms/discussions/11797), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added the `canDuplicate()` Twig function. ([#11797](https://github.com/craftcms/cms/discussions/11797), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added the `canSave()` Twig function. ([#11797](https://github.com/craftcms/cms/discussions/11797), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added the `canView()` Twig function. ([#11797](https://github.com/craftcms/cms/discussions/11797), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added the `|boolean` Twig filter. ([#11792](https://github.com/craftcms/cms/pull/11792))
- Added the `|float` Twig filter. ([#11792](https://github.com/craftcms/cms/pull/11792))
- Added the `|integer` Twig filter. ([#11792](https://github.com/craftcms/cms/pull/11792))
- Added the `|string` Twig filter. ([#11792](https://github.com/craftcms/cms/pull/11792))
- Twig templates now have `today`, `tomorrow`, and `yesterday` global variables available to them.
- Element query date params now support passing `today`, `tomorrow`, and `yesterday`. ([#10485](https://github.com/craftcms/cms/issues/10485))
- Element queries’ `relatedTo` params now only check for relations in the same site as `siteId`, if set. ([#12000](https://github.com/craftcms/cms/issues/12000), [#12072](https://github.com/craftcms/cms/pull/12072))
- Element queries now support passing ambiguous column names (e.g. `dateCreated`) and field handles into `select()`. ([#11790](https://github.com/craftcms/cms/pull/11790), [#11800](https://github.com/craftcms/cms/pull/11800))
- Element queries’ `collect()` methods eager-loaded elements now return `craft\elements\ElementCollection` objects, which extends `Illuminate\Support\Collection` with `ids()` and `with()` methods. ([#12113](https://github.com/craftcms/cms/discussions/12113))
- `{% cache %}` tags now store any HTML registered with `{% html %}` tags. ([#11811](https://github.com/craftcms/cms/discussions/11811))
- `{% cache %}` tags and GraphQL query caches now get a max cache duration based on the fetched/referenced entries’ expiry dates. ([#8525](https://github.com/craftcms/cms/discussions/8525), [#11901](https://github.com/craftcms/cms/pull/11901))
- Added the `siteHandle` field to elements queried via GraphQL. ([#10829](https://github.com/craftcms/cms/discussions/10829))
- `users/session-info` responses now include a `csrfTokenName` key. ([#11706](https://github.com/craftcms/cms/pull/11706), [#11767](https://github.com/craftcms/cms/pull/11767))
- The `elements/save-draft` action now supports being called from the front end. ([#12131](https://github.com/craftcms/cms/issues/12131))
- The `users/upload-user-photo` action now includes a `photoId` key in the response data. ([#12175](https://github.com/craftcms/cms/pull/12175))

### Extensibility
- Added `craft\base\conditions\BaseTextConditionRule::inputOptions()`.
- Added `craft\base\Element::EVENT_DEFINE_URL`. ([#12168](https://github.com/craftcms/cms/pull/12168))
- Added `craft\base\ExpirableElementInterface`. ([#11901](https://github.com/craftcms/cms/pull/11901))
- Added `craft\controllers\ElementsController::$element`.
- Added `craft\db\ActiveQuery::collect()`. ([#11842](https://github.com/craftcms/cms/pull/11842))
- Added `craft\elements\actions\Restore::$restorableElementsOnly`.
- Added `craft\elements\conditions\assets\EditableConditionRule`.
- Added `craft\elements\conditions\entries\EditableConditionRule`.
- Added `craft\elements\ElementCollection`. ([#12113](https://github.com/craftcms/cms/discussions/12113))
- Added `craft\events\AuthorizationCheckEvent::$element`.
- Added `craft\events\CreateTwigEvent`.
- Added `craft\events\DefineAddressFieldLabelEvent`.
- Added `craft\events\DefineAddressFieldsEvent`.
- Added `craft\events\DefineUrlEvent`. ([#12168](https://github.com/craftcms/cms/pull/12168))
- Added `craft\events\ImageTransformerOperationEvent::$tempPath`.
- Added `craft\events\SearchEvent::$scores`. ([#11882](https://github.com/craftcms/cms/discussions/11882))
- Added `craft\events\UserGroupPermissionsEvent`.
- Added `craft\events\UserPermissionsEvent`.
- Added `craft\helpers\DateRange`.
- Added `craft\helpers\DateTimeHelper::firstWeekDay()`.
- Added `craft\helpers\DateTimeHelper::lastMonth()`.
- Added `craft\helpers\DateTimeHelper::lastWeek()`.
- Added `craft\helpers\DateTimeHelper::lastWeekDay()`.
- Added `craft\helpers\DateTimeHelper::lastYear()`.
- Added `craft\helpers\DateTimeHelper::nextMonth()`.
- Added `craft\helpers\DateTimeHelper::nextWeek()`.
- Added `craft\helpers\DateTimeHelper::nextYear()`.
- Added `craft\helpers\DateTimeHelper::thisMonth()`.
- Added `craft\helpers\DateTimeHelper::thisWeek()`.
- Added `craft\helpers\DateTimeHelper::thisYear()`.
- Added `craft\helpers\DateTimeHelper::today()`.
- Added `craft\helpers\DateTimeHelper::tomorrow()`.
- Added `craft\helpers\DateTimeHelper::yesterday()`.
- Added `craft\helpers\ElementHelper::attributeHtml()`.
- Added `craft\helpers\Html::svg()`. ([#11932](https://github.com/craftcms/cms/pull/11932))
- Added `craft\i18n\FormatConverter::convertDatePhpToHuman()`. ([#10546](https://github.com/craftcms/cms/pull/10546))
- Added `craft\i18n\Locale::FORMAT_HUMAN`.
- Added `craft\nameparsing\CustomLanguage`.
- Added `craft\services\Addresses::EVENT_DEFINE_FIELD_LABEL`. ([#11788](https://github.com/craftcms/cms/discussions/11788))
- Added `craft\services\Addresses::EVENT_DEFINE_USED_FIELDS`. ([#11788](https://github.com/craftcms/cms/discussions/11788))
- Added `craft\services\Addresses::EVENT_DEFINE_USED_SUBDIVISION_FIELDS`. ([#11788](https://github.com/craftcms/cms/discussions/11788))
- Added `craft\services\Addresses::getFieldLabel()`.
- Added `craft\services\Addresses::getUsedFields()`.
- Added `craft\services\Addresses::getUsedSubdivisionFields()`.
- Added `craft\services\Elements::EVENT_AUTHORIZE_CREATE_DRAFTS`. ([#11759](https://github.com/craftcms/cms/discussions/11759), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added `craft\services\Elements::EVENT_AUTHORIZE_DELETE_FOR_SITE`. ([#11759](https://github.com/craftcms/cms/discussions/11759), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added `craft\services\Elements::EVENT_AUTHORIZE_DELETE`. ([#11759](https://github.com/craftcms/cms/discussions/11759), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added `craft\services\Elements::EVENT_AUTHORIZE_DUPLICATE`. ([#11759](https://github.com/craftcms/cms/discussions/11759), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added `craft\services\Elements::EVENT_AUTHORIZE_SAVE`. ([#11759](https://github.com/craftcms/cms/discussions/11759), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added `craft\services\Elements::EVENT_AUTHORIZE_VIEW`. ([#11759](https://github.com/craftcms/cms/discussions/11759), [#11808](https://github.com/craftcms/cms/pull/11808))
- Added `craft\services\Elements::canCreateDrafts()`.
- Added `craft\services\Elements::canDelete()`.
- Added `craft\services\Elements::canDeleteForSite()`.
- Added `craft\services\Elements::canDuplicate()`.
- Added `craft\services\Elements::canSave()`.
- Added `craft\services\Elements::canView()`.
- Added `craft\services\Elements::getIsCollectingCacheInfo()`. ([#11901](https://github.com/craftcms/cms/pull/11901))
- Added `craft\services\Elements::setCacheExpiryDate()`. ([#11901](https://github.com/craftcms/cms/pull/11901))
- Added `craft\services\Elements::startCollectingCacheInfo()`. ([#11901](https://github.com/craftcms/cms/pull/11901))
- Added `craft\services\Elements::stopCollectingCacheInfo()`. ([#11901](https://github.com/craftcms/cms/pull/11901))
- Added `craft\services\Search::EVENT_BEFORE_SCORE_RESULTS`. ([#11882](https://github.com/craftcms/cms/discussions/11882))
- Added `craft\services\UserPermissions::EVENT_AFTER_SAVE_GROUP_PERMISSIONS`. ([#12130](https://github.com/craftcms/cms/discussions/12130), [#12146](https://github.com/craftcms/cms/pull/12146))
- Added `craft\services\UserPermissions::EVENT_AFTER_SAVE_USER_PERMISSIONS`. ([#12130](https://github.com/craftcms/cms/discussions/12130), [#12146](https://github.com/craftcms/cms/pull/12146))
- Added `craft\web\Controller::currentUser()`. ([#11754](https://github.com/craftcms/cms/pull/11754), [#11916](https://github.com/craftcms/cms/pull/11916))
- Added `craft\web\View::EVENT_AFTER_CREATE_TWIG`. ([#11774](https://github.com/craftcms/cms/pull/11774))
- `craft\elements\Asset::EVENT_DEFINE_URL` now gets triggered after the default URL has been generated, and the URL will be passed to `craft\events\DefineAssetUrlEvent::$url`.
- `craft\elements\db\ElementQuery::collect()` and `craft\base\Element::getEagerLoadedElements()` now return `craft\elements\ElementCollection` instances. ([#12113](https://github.com/craftcms/cms/discussions/12113))
- `craft\events\DraftEvent::$creatorId` is now nullable. ([#11904](https://github.com/craftcms/cms/issues/11904))
- `craft\fieldlayoutelements\BaseField::statusClass()` and `statusLabel()` now return status info from the element for the attribute specified by `attribute()`.
- `craft\helpers\Component::iconSvg()` now namespaces the SVG contents, and adds `aria-hidden="true"`. ([#11703](https://github.com/craftcms/cms/pull/11703))
- `craft\services\Drafts::createDraft()` now accepts `null` passed to its `$creatorId` argument. ([#11904](https://github.com/craftcms/cms/issues/11904))
- `craft\services\Search::EVENT_AFTER_SEARCH` now includes the computed search result scores, set to `craft\events\SearchEvent::$scores`, and any changes made to it will be returned by `searchElements()`. ([#11882](https://github.com/craftcms/cms/discussions/11882))
- `craft\services\Search::EVENT_BEFORE_INDEX_KEYWORDS` is now cancellable by setting `$event->isValid` to `false`. ([#11705](https://github.com/craftcms/cms/discussions/11705))
- Deprecated `craft\base\Element::EVENT_AUTHORIZE_CREATE_DRAFTS`. `craft\services\Elements::EVENT_AUTHORIZE_CREATE_DRAFTS` should be used instead.
- Deprecated `craft\base\Element::EVENT_AUTHORIZE_DELETE_FOR_SITE`. `craft\services\Elements::EVENT_AUTHORIZE_DELETE_FOR_SITE` should be used instead.
- Deprecated `craft\base\Element::EVENT_AUTHORIZE_DELETE`. `craft\services\Elements::EVENT_AUTHORIZE_DELETE` should be used instead.
- Deprecated `craft\base\Element::EVENT_AUTHORIZE_DUPLICATE`. `craft\services\Elements::EVENT_AUTHORIZE_DUPLICATE` should be used instead.
- Deprecated `craft\base\Element::EVENT_AUTHORIZE_SAVE`. `craft\services\Elements::EVENT_AUTHORIZE_SAVE` should be used instead.
- Deprecated `craft\base\Element::EVENT_AUTHORIZE_VIEW`. `craft\services\Elements::EVENT_AUTHORIZE_VIEW` should be used instead.
- Deprecated `craft\elements\Address::addressAttributeLabel()`. `craft\services\Addresses::getFieldLabel()` should be used instead.
- Deprecated `craft\events\DefineAssetUrlEvent::$asset`. `$sender` should be used instead.
- Deprecated `craft\services\Elements::getIsCollectingCacheTags()`. `getIsCollectingCacheInfo()` should be used instead. ([#11901](https://github.com/craftcms/cms/pull/11901))
- Deprecated `craft\services\Elements::startCollectingCacheTags()`. `startCollectingCacheInfo()` should be used instead. ([#11901](https://github.com/craftcms/cms/pull/11901))
- Deprecated `craft\services\Elements::stopCollectingCacheTags()`. `stopCollectingCacheInfo()` should be used instead. ([#11901](https://github.com/craftcms/cms/pull/11901))
- `checkboxSelect` inputs without `showAllOption: true` now post an empty value if no options were selected. ([#11748](https://github.com/craftcms/cms/issues/11748))
- Added the `Craft.useMobileStyles()` JavaScript method. ([#11636](https://github.com/craftcms/cms/pull/11636))
- Added `Craft.BaseElementIndex::getParentSource()`.
- Added `Craft.BaseElementIndex::getRootSource()`.
- Added `Craft.BaseElementIndex::getSourceActions()`. ([#11906](https://github.com/craftcms/cms/pull/11906))
- Added `Craft.BaseElementIndex::getSourceLevel()`.
- `Craft.BaseElementSelectInput` now triggers a `change` event when elements are added programmatically or removed.

### System
- Name parsing now checks for common German salutations, suffixes, and last name prefixes.
- “Generating pending image transforms” jobs no longer attempt to process transforms that had previously failed. ([#11970](https://github.com/craftcms/cms/issues/11970))
- The default system email template now sets the `lang` attribute on the `<html>` tag. ([#12156](https://github.com/craftcms/cms/pull/12156))
- The default system email template now includes a `Content-Type` tag. ([#12156](https://github.com/craftcms/cms/pull/12156))
- Improved GraphQL cache reliability. ([#11994](https://github.com/craftcms/cms/issues/11994), [#12086](https://github.com/craftcms/cms/pull/12086))
- Control panel `.twig` templates are now prioritized over `.html`. ([#11809](https://github.com/craftcms/cms/discussions/11809), [#11840](https://github.com/craftcms/cms/pull/11840))
- Updated Yii to 2.0.46.
- Fixed a bug where addresses weren’t validating their country codes. ([#12161](https://github.com/craftcms/cms/issues/12161))
- Fixed a bug where Entry URI Format, Template, and Default Status switches were focusable within sections’ Site Settings tables, for disabled sites.
- Fixed a bug where `craft\helpers\Db::parseParam()` wasn’t generating conditions that would include `null` values when it should have. ([#11931](https://github.com/craftcms/cms/issues/11931))

## 4.2.8 - 2022-10-18

### Changed
- The `setup/keys` command will now set the application ID if `Craft::$app->id` is empty. ([#12103](https://github.com/craftcms/cms/pull/12103))

### Fixed
- Fixed an error that could occur when running tests. ([#12088](https://github.com/craftcms/cms/issues/12088), [#12089](https://github.com/craftcms/cms/issues/12089))
- Fixed a bug where the `db/restore` command would output a warning about a missing `info` row, even if one existed in the imported database. ([#12101](https://github.com/craftcms/cms/issues/12101))
- Fixed a bug where the “Your session has ended” modal could be shown on the control panel’s login page. ([#12121](https://github.com/craftcms/cms/issues/12121))
- Fixed a permission error that could occur when uploading a file to an Assets field.
- Fixed a bug where custom log targets were getting removed when processing queue jobs. ([#12109](https://github.com/craftcms/cms/pull/12109))
- Fixed a bug where Money fields weren’t distinguishing between `0` and empty values. ([#12122](https://github.com/craftcms/cms/issues/12122), [#12132](https://github.com/craftcms/cms/pull/12132))
- Fixed an error that could occur in the control panel. ([#12133](https://github.com/craftcms/cms/issues/12133))
- Fixed a bug where assets uploaded from Assets fields weren’t retaining their original filename for all but the initial site. ([#12142](https://github.com/craftcms/cms/pull/12142))
- Fixed a bug where non-admin users couldn’t always see the Temporary Uploads source when selecting assets from an Assets field. ([#12128](https://github.com/craftcms/cms/issues/12128))
- Fixed a bug where image transforms that used the `fit` mode but didn’t specify a width or height weren’t getting their missing dimension set on the asset. ([#12137](https://github.com/craftcms/cms/issues/12137))

## 4.2.7 - 2022-10-11

### Added
- Added the `setup/keys` command, which ensure Craft is configured with an application ID and security key.

### Changed
- The `install/craft` command now runs `setup/keys` before doing anything else.

## 4.2.6 - 2022-10-11

### Added
- Added the `--as-json` option to the `help` command. ([#12017](https://github.com/craftcms/cms/pull/12017), [#12074](https://github.com/craftcms/cms/pull/12074))
- Added `craft\helpers\ElementHelper::isAttributeEmpty()`.
- Added `craft\queue\jobs\ResaveElements::$ifEmpty`.
- Added `craft\queue\jobs\ResaveElements::$set`.
- Added `craft\queue\jobs\ResaveElements::$to`.
- Added `craft\queue\jobs\ResaveElements::$touch`.

### Changed
- When passing a PHP callback function to the `--to` option of a `resave/*` command, the `$element` argument is now optional.

### Deprecated
- Deprecated `craft\web\assets\focusvisible\FocusVisibleAsset`. ([#12037](https://github.com/craftcms/cms/pull/12037))

### Fixed
- Fixed an error that could occur when editing a draft of an element type that didn’t have change tracking enabled.
- Fixed an error that could occur when saving an entry with Matrix blocks, if the entry had been deleted for a site.
- Fixed a bug where `resave/*` commands weren’t respecting the `--set`, `--to`, or `--touch` options when `--queue` was passed. ([#11974](https://github.com/craftcms/cms/issues/11974))
- Fixed a bug where `relatedTo` params didn’t support collections.
- Fixed an error that could occur when passing an element query to a `relatedTo` param, if the parent element query contained any closures. ([#11981](https://github.com/craftcms/cms/issues/11981))
- Fixed a bug where `craft\log\MonologTarget::$allowLineBreaks` wasn’t getting a default value. ([#12004](https://github.com/craftcms/cms/pull/12004))
- Fixed a PHP error that occurred when attempting to edit an element by an invalid ID or UUID.
- Fixed a bug where unsaved drafts could be unintentionally deleted when saved, if a plugin or module was blocking the save via `EVENT_BEFORE_SAVE`. ([#12015](https://github.com/craftcms/cms/issues/12015))
- Fixed a bug where “Propagating tags” jobs would fail if two tags had similar titles.
- Fixed a bug where pressing “Disable focal point” within asset preview modals would only reset the focal point position, but not delete it. ([#12030](https://github.com/craftcms/cms/issues/12030))
- Fixed a bug where image transforms weren’t getting sized correctly in some cases when `upscaleImages` was disabled. ([#12023](https://github.com/craftcms/cms/issues/12023))
- Fixed a bug where table cells within Redactor fields could appear to be focused when they weren’t. ([#12001](https://github.com/craftcms/cms/issues/12001), [#12037](https://github.com/craftcms/cms/pull/12037))
- Fixed a bug where alerts saying a folder can’t be renamed due to a naming conflict were showing the old folder name instead of the new one. ([#12049](https://github.com/craftcms/cms/pull/12049))
- Fixed a bug where custom fields nested within Matrix fields weren’t always updating properly within slideout editors. ([#11988](https://github.com/craftcms/cms/issues/11988), [#12058](https://github.com/craftcms/cms/issues/12058))
- Fixed an error that could occur when adding new textual condition rules to a condition. ([#12077](https://github.com/craftcms/cms/pull/12077))
- Fixed a bug where Table fields’ Default Values settings were always showing at least one row, even if the setting had been saved without any rows. ([#12071](https://github.com/craftcms/cms/issues/12071))
- Fixed a bug where existing rows in Table fields’ Default Values settings were losing the ability to be reordered or deleted when the table columns were changed.
- Fixed a bug where sending a password reset email for an inactive user would set them to a pending state. ([#12080](https://github.com/craftcms/cms/pull/12080))
- Fixed a bug where some GraphQL results could be missing if multiple sets of nested elements were being queried using the same alias. ([#11982](https://github.com/craftcms/cms/issues/11982))

### Security
- Fixed information disclosure vulnerabilities.

## 4.2.5.2 - 2022-10-03

### Security
- Updated Twig to 3.4. ([#12022](https://github.com/craftcms/cms/issues/12022))

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
- Disable the preview button while drafts are saving ([#11858](https://github.com/craftcms/cms/issues/11858))

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
- Fixed a bug where Pashto was not being treated as an RTL language. ([#11428](https://github.com/craftcms/cms/issues/11428))
- Fixed a bug where the `upscaleImages` config setting wasn’t being respected for transforms where only a single image dimension was specified. ([#11398](https://github.com/craftcms/cms/issues/11398))
- Fixed an error that could occur when executing a GraphQL query, if a section didn’t have any entry types. ([#11273](https://github.com/craftcms/cms/issues/11273))
- Fixed an error that could occur when changing the primary site on installs with a large number of users. ([#11459](https://github.com/craftcms/cms/issues/11459))
- Fixed a bug where Assets fields within Vizy fields weren’t getting relocated from the user’s temp uploads folder. ([#11462](https://github.com/craftcms/cms/issues/11462))

### Security
- Environment-aware control panel fields no longer suggest environment variables that begin with `HTTP_`.
- The Sendmail mailer no longer validates if the Sendmail Command setting is set to an environment variable that begins with `HTTP_`.

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
- Fixed a bug where addresses’ County fields were mislabeled. ([#11314](https://github.com/craftcms/cms/pull/11314))
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
- Path options passed to console commands (e.g. `--basePath`) now take precedence over their environment variable/PHP constant counterparts.
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
