# Craft CMS 3.0 Working Changelog

## 3.0.0-RC17.1 - 2018-03-29

### Added
- Added a `shift` + `spacebar` keyboard shortcut for opening asset previews, and arrow keys can be used to navigate between assets from the preview modal.
- Asset previews are now available in asset selection modals and Assets fields, via the `shift` + `spacebar` shortcut.
- Assets now have a “Link” table attribute option.
- Added the `SORT_REGULAR`, `SORT_NUMERIC`, `SORT_STRING`, `SORT_LOCALE_STRING`, `SORT_NATURAL`, and `SORT_FLAG_CASE` global template variables.

### Changed
- Table fields’ Min Rows and Max Rows settings can now be set independently of each other.
- Updated JavaScript dependencies.

### Fixed
- Fixed JavaScript errors that occurred when working with most editable table fields. ([#2654](https://github.com/craftcms/cms/issues/2654))
- Fixed a database error that could occur when saving non-ASCII characters in a Plain Text field. ([#2650](https://github.com/craftcms/cms/issues/2650))
- Fixed issues that could occur when saving content with multibyte characters. ([#1768](https://github.com/craftcms/cms/issues/1768))

## 3.0.0-RC17 - 2018-03-28

### Added
- Plugins can now specify a `minVersionRequired` public property, which will prevent the plugin from getting updated unless a minimum prior version had been installed.
- Added a “Preview file” action for assets, which replaces “View asset”.
- Table fields now have “Min Rows”, “Max Rows”, and “Add Row Label” settings. ([#2372](https://github.com/craftcms/cms/issues/2372))
- Prism.js is now available to for Control Panel use.
- Added `craft\elements\actions\PreviewAsset`.
- Added `craft\web\assets\prismjs\PrismJsAsset`.

### Changed
- Improved defenses against errors when deleting asset folders.
- Plain Text fields now have the option to be styled with a monospaced font. ([#2636](https://github.com/craftcms/cms/pull/2636))
- `searchKeywords` is now a reserved field handle. ([#2645](https://github.com/craftcms/cms/issues/2645))

### Fixed
- Fixed issues that could occur when saving content with multibyte characters. ([#1768](https://github.com/craftcms/cms/issues/1768))
- Fixed an error that occurred on multi-site installs when creating a new entry from an entry selection modal, if the user didn’t have permission to edit the primary site. ([#2627](https://github.com/craftcms/cms/issues/2627))
- Fixed a bug where available plugin updates weren’t getting reported in the Control Panel, and plugin installation counters weren’t getting updated in the Plugin Store. ([#2642](https://github.com/craftcms/cms/issues/2642))
- Fixed a bug where Craft could consider HTML and JSON files to be manipulatable images.

## 3.0.0-RC16.1 - 2018-03-25

### Fixed
- Fixed a PHP error that could occur if `config/app.php` was overriding the `mutex` component.
- Fixed a bug where Craft was showing invalid license edition notices when it shouldn’t have.

## 3.0.0-RC16 - 2018-03-23

### Added
- Added the `defineBehaviors` event to `craft\base\Component` and `craft\db\Query`.
- Added `craft\helpers\FileHelper::hasAnythingChanged()`.
- Added `craft\helpers\Localization::normalizeLanguage()`.
- Added `craft\helpers\UrlHelper::baseCpUrl()`.
- Added `craft\helpers\UrlHelper::baseRequestUrl()`.
- Added `craft\helpers\UrlHelper::baseSiteUrl()`.
- Added `craft\helpers\UrlHelper::cpHost()`.
- Added `craft\helpers\UrlHelper::hostInfo()`.
- Added `craft\helpers\UrlHelper::siteHost()`.
- Added `craft\services\Api::request()`.
- Added `craft\services\Config::getConfigFilePath()`.
- Added `craft\validators\ColorValidator::normalizeColor()`.
- Added `craft\web\twig\Environment::getDefaultEscaperStrategy()` and `setDefaultEscaperStrategy()`.

### Changed
- Improved the page header styles in the Control Panel.
- It’s now possible to define custom values for the `@web` and `@webroot` aliases from the `aliases` config setting. ([#2566](https://github.com/craftcms/cms/issues/2566))
- Console requests will now look for a `web/`, `public/`, or `public_html/` folder alongside the `craft` executable when setting the default value for the `@webroot` alias.
- System message templates are now passed `fromName` and `fromEmail` variables, set to the system email settings values.
- `craft\helpers\UrlHelper::baseUrl()` now returns the base CP or site URL, depending on the request type.
- `craft\helpers\UrlHelper::host()` now returns the CP or site host info, depending on the request type.
- `craft\validators\ColorValidator::validateAttribute()` now attempts to normalize the model’s color value before validating it.
- `craft\validators\LanguageValidator` can now be used to validate raw values, in addition to model attributes.
- `craft\validators\LanguageValidator` will now normalize the model attribute into the correct language format (e.g. `en-US` rather than `en_us`).
- `craft\validators\LanguageValidator` now has an `onlySiteLanguages` property, which can be set to `false` to validate the value against all known languages.
- `craft\validators\LanguageValidator` now has a `notAllowed` property, which can be used to customize the error message.
- `craft\web\View::renderString()` and `renderObjectTemplate()` no longer escape dynamically-output HTML in the template by default.
- The `defineBehaviors` event on `craft\web\twig\variables\CraftVariable` is no longer deprecated.
- Craft now requires Yii 2.0.15 or later.
- Plugin settings defined in config files are no longer recursively merged with the database-stored settings. ([#2561](https://github.com/craftcms/cms/issues/2561))
- Element queries now support the `groupBy` parameter. ([#2603](https://github.com/craftcms/cms/issues/2603))
- Statuses can now explicitly be set to `white`. ([#2628](https://github.com/craftcms/cms/pull/2628))
- The Settings → Email → Settings page now displays a warning if `config/app.php` is overriding the `mailer` component. ([#2554](https://github.com/craftcms/cms/issues/2554))

### Removed
- Removed `craft\errors\ApiException`.

### Fixed
- Fixed a bug where database backups could fail on PostgreSQL if the database password contained special characters. ([#2568](https://github.com/craftcms/cms/issues/2568))
- Fixed a bug where front-end Asset uploads would be handled incorrectly in multi-site environments. ([#2551](https://github.com/craftcms/cms/issues/2551))
- Fixed a bug where custom field validation errors weren’t being reported on the edit page. ([#2572](https://github.com/craftcms/cms/issues/2572))
- Fixed a bug where it was possible to set an invalid language on the initial site, when installing Craft from the command line. ([#2573](https://github.com/craftcms/cms/issues/2573))
- Fixed a bug where users’ preferred languages were not always getting migrated correctly when updating to Craft 3. ([#2574](https://github.com/craftcms/cms/issues/2574))
- Fixed a bug where switching transform format could cause sub-optimal transforms to be used. ([#2565](https://github.com/craftcms/cms/issues/2565))
- Fixed a bug where invoking `craft\services\Feeds` during a console request would cause a PHP error. ([#2576](https://github.com/craftcms/cms/issues/2576))
- Fixed a bug where you could get a PHP serialization error on some element queries inside of a `{% cache %}` tag. ([#2586](https://github.com/craftcms/cms/issues/2586))
- Fixed a bug where `queue` commands weren’t working. ([#2594](https://github.com/craftcms/cms/issues/2594))
- Fixed an error that would occur if a `SiteGroupNotFoundException` was thrown. ([#2595](https://github.com/craftcms/cms/issues/2595))
- Fixed a bug where Control Panel resource requests could 404 in load-balanced environments. ([#2500](https://github.com/craftcms/cms/issues/2500))
- Fixed a bug where you would get a PHP error in `craft\fields\PlainText` when updating from a really old Craft install.
- Fixed a bug where new entry versions weren’t being generated when publishing drafts. ([#2579](https://github.com/craftcms/cms/issues/2579))
- Fixed a bug where the `baseCpUrl` config setting wasn’t being factored in when generating action URLs for the Control Panel. ([#2581](https://github.com/craftcms/cms/issues/2581))
- Fixed a bug where some Control Panel messages were not getting translated. ([#2583](https://github.com/craftcms/cms/issues/2583))
- Fixed a bug where Color fields’ color previews weren’t showing the selected color in element indexes and entry versions. ([#2587](https://github.com/craftcms/cms/issues/2587))
- Fixed a bug where datepickers and timepickers weren’t always visible in Live Preview. ([#2591](https://github.com/craftcms/cms/issues/2591))
- Fixed a bug where fields’ Translation Method settings weren’t listing “Translate for each site group” as an option after changing the field type, until the field was saved and re-edited. ([#2602](https://github.com/craftcms/cms/issues/2602))
- Fixed an error that occurred when attempting to set a value on a newly-created field within a content migration. ([#2597](https://github.com/craftcms/cms/issues/2597))
- Fixed a SQL error that occurred if an element query was executed with the `fixedOrder` param enabled but the `id` param wasn’t set. ([#2607](https://github.com/craftcms/cms/issues/2607))
- Fixed a bug where newlines could be counted as two characters when validating the length of Plain Text field values. ([#2257](https://github.com/craftcms/cms/issues/2257))
- Fixed a bug where delayed jobs would never get run when using Craft’s built-in queue driver. ([#2609](https://github.com/craftcms/cms/issues/2609))
- Fixed a SQL error that could occur when saving content with multibyte characters. ([#1768](https://github.com/craftcms/cms/issues/1768))
- Fixed a SQL error that could occur if saving a `sortOrder` value that exceeded a `TINYINT` column’s capacity. ([#2613](https://github.com/craftcms/cms/issues/2613))
- Fixed a bug where plugins weren’t sorted alphabetically by name by default in the Control Panel sidebar. ([#2614](https://github.com/craftcms/cms/pull/2614))
- Fixed a bug where Color fields’ Default Color settings were being overly strict about the color value. ([#2588](https://github.com/craftcms/cms/issues/2588))
- Fixed a PHP error that would occur when saving a Number field, if a non-integer number was entered into its Min Value, Max Value, or Decimal Points settings. ([#2612](https://github.com/craftcms/cms/issues/2612))
- Fixed a bug where calling `getPrev({status: null})` or `getNext({status: null})` on an element wasn’t working if the neighboring element wasn’t enabled.
- Fixed a bug where `craft\web\Request::getSegments()` was returning an array with an empty string on homepage requests, rather than an empty array. ([#2633](https://github.com/craftcms/cms/issues/2633))

## 3.0.0-RC15 - 2018-03-13

### Added
- Added `craft\base\Element::isFieldEmpty()`, which can be used to determine if a field value is considered “empty” by the field type.
- Added `craft\base\FieldInterface::isValueEmpty()`, which replaces the deprecated `isEmpty()` method.
- Added `craft\db\Connection::getSupportsMb4()` and `setSupportsMb4()`.
- Added `craft\helpers\StringHelper::containsMb4()`.
- Added `craft\helpers\UrlHelper::host()`.
- Added `craft\validators\StringValidator`.

### Changed
- Element titles now get a validation error if they contain any 4+ byte characters (like emoji), on servers running MySQL. ([#2513](https://github.com/craftcms/cms/issues/2513))
- Craft now sets the `@web` and `@webroot` aliases for console requests.
- The web and console installers now set the default site URL to `@web`.
- Cleaned up the styling of subnav items in the global Control Panel navigation.
- Craft now requires Yii 2.0.14.2 or later, which fixes performance issues related to `upsert()`, so schema caching is now only enabled when Dev Mode is not enabled.

### Deprecated
- Deprecated `craft\base\Field::isEmpty()`. `isValueEmpty()` should be used instead.

### Fixed
- Fixed an error that occurred when creating a new entry draft. ([#2544](https://github.com/craftcms/cms/issues/2544))
- Fixed a bug where the primary action button on element index pages was getting positioned off-screen on IE11. ([#2545](https://github.com/craftcms/cms/issues/2545))
- Fixed a bug where custom fields were taking precedence over actual element properties when their names conflicted (e.g. `author`). ([#2548](https://github.com/craftcms/cms/issues/2548))
- Fixed a bug where `craft\db\pgsql\Schema::gatLastInsertID()` was prepending the default schema to the sequence name even if a schema was already present.
- Fixed a bug where loading the Plugin Store would fail if Craft didn’t have a valid license key yet.
- Fixed a “Headers already sent” error that would occur when calling `Craft::dd()`.
- Fixed a bug where submitting a form from the front-end could cause Asset folders to be created even if there was nothing to put in them. ([#2303](https://github.com/craftcms/cms/issues/2303))
- Fixed a bug where file upload drop zone could be too small on the Assets index page. ([#2479](https://github.com/craftcms/cms/issues/2479))
- Fixed a bug where asset reference tags were not getting parsed correctly when transforms were used. ([#2524](https://github.com/craftcms/cms/issues/2524))
- Fixed a bug where buggy SVGs would break the Control Panel in some cases. ([#2543](https://github.com/craftcms/cms/issues/2543))
- Fixed a bug where Assets fields weren’t validating the file types of files uploaded via front-end entry forms. ([#2550](https://github.com/craftcms/cms/issues/2550))
- Fixed a bug where action URLs were including the request’s host name rather than the current site’s host name, if they differed. ([#2558](https://github.com/craftcms/cms/issues/2558))

## 3.0.0-RC14 - 2018-03-06

### Added
- Added `craft\base\Model::addModelErrors()`.

### Changed
- Schema caching is now enabled on the `db` app component, improving `upsert()` performance among other things.
- Matrix fields now include any block validation errors on the owner element with the attribute format `MatrixFieldHandle[BlockIndex].NestedFieldHandle`.
- Sections and category groups now include any site settings validation errors with the attribute format `siteSettings[Index].NestedAttribute`.

### Fixed
- Fixed a bug where entries and entry types were not serializable. ([#2506](https://github.com/craftcms/cms/issues/2506))
- Fixed a bug where you would get a PHP error loading an entry if it had a draft that was missing its entry type ID.
- Fixed a bug where all element queries with a `search` param were getting ordered by search score, even if the query had been configured not to be. ([#2520](https://github.com/craftcms/cms/issues/2520))
- Fixed a bug where it wasn’t possible to use the `_includes/forms/select.html` include template’s `toggle` feature if the template was getting namespaced.
- Fixed a bug where entries in Channel sections weren’t being ordered by `postDate desc` by default. ([#2531](https://github.com/craftcms/cms/issues/2531))
- Fixed a SQL error that could occur when `true` or `false` was passed to a Lightswitch field’s element query param on PostgreSQL. ([#2530](https://github.com/craftcms/cms/issues/2530))
- Fixed a bug where if a plugin prevented an element from being moved within a structure, the element index page would react as if the move had completed successfully. ([#2537](https://github.com/craftcms/cms/issues/2537))

## 3.0.0-RC13 - 2018-02-27

### Added
- Added the `|multisort` Twig filter, which duplicates an array and sorts it with [craft\helpers\ArrayHelper::multisort()](http://www.yiiframework.com/doc-2.0/yii-helpers-basearrayhelper.html#multisort()-detail).

### Changed
- The `@web` alias now includes the request’s host info (`scheme://hostname/`) in addition to the base URI. ([#2486](https://github.com/craftcms/cms/issues/2486))
- Elements’ `toArray()` results no longer include relational objects by default, and must be specified via the `$expand` argument if desired.
- `craft\helpers\ChartHelper::getRunChartDataFromQuery()` now has `$func` and `$q` arguments, which should be set to the aggregate query function and the column/expression to be passed into it.
- `craft\helpers\UrlHelper` now includes the current request’s host info (`scheme://hostname/`) when generating URLs that have to include the script name.
- `craft\web\View::renderObjectTemplate()` now includes fields defined by the object’s `extraFields()` method, if it looks like they are being referenced within the template.
- Undeprecated the `getAssetThumbUrl` event on `craft\services\Assets`. ([#2493](https://github.com/craftcms/cms/issues/2493))
- If anything prevents a user from being deleted, any changes that were made in preparation for deleting the user are now rolled back.

### Fixed
- Fixed a bug where the `Craft.getUrl()` function would prepend the base URL even if the passed-in path began with `/`. ([#2475](https://github.com/craftcms/cms/issues/2475))
- Fixed a bug where SVG images with viewboxes that had negative numbers could not be resized. ([#2477](https://github.com/craftcms/cms/issues/2477))
- Fixed an infinite recursion bug that could occur where calling `toArray()` on an element.
- Fixed a bug where Matrix fields were assigning the wrong field namespace to their blocks, when creating the blocks from revision or POST data. ([#2484](https://github.com/craftcms/cms/pull/2484))
- Fixed an error that could occur when viewing entry revisions. ([#2491](https://github.com/craftcms/cms/issues/2491))
- Fixed an error that occurred when programmatically saving an element that had been fetched with eager-loaded relations.
- Fixed a bug where users’ Language preference options weren’t sorted alphabetically.
- Fixed a bug where Assets fields’ “Upload Location” and “Default Upload Location” settings weren’t showing correct example code for Matrix fields.

## 3.0.0-RC12 - 2018-02-22

### Added
- Added the `getenv()` Twig function. ([#2471](https://github.com/craftcms/cms/pull/2471))
- Added `craft\elements\Asset::getPath()`.
- Added `craft\services\Users::getUserPreference()`.

### Changed
- Element query classes can now specify the default `orderBy` value by overriding `craft\elements\db\ElementQuery::defaultOrderBy`.
- The Photo field on Edit User pages now has `id="photo"`. ([#2469](https://github.com/craftcms/cms/pull/2469))
- Built-in element types now support several more attributes in their array representations.
- The system installer now sets the initial admin account’s preferred language to the site language selected in the installation wizard. ([#2480](https://github.com/craftcms/cms/issues/2480))
- It’s now possible to order elements by either `score desc` or `score asc` when the `search` element query param is in use.

### Deprecated
- Deprecated `craft\elements\Asset::getHasUrls()`.
- Deprecated `craft\elements\Asset::getUri()`. Use `getPath()` instead.

### Fixed
- Fixed a bug where various asset transform operations could result in a PHP error. ([#2463](https://github.com/craftcms/cms/issues/2463))
- Fixed an error that occurred if a site’s base URL was set to `@web` and the `CRAFT_SITE` constant wasn’t defined. ([#2464](https://github.com/craftcms/cms/issues/2464))
- Fixed a bug where global sets and other elements could become hidden if a new site was added with the “Is this the primary site?” setting enabled. ([#2465](https://github.com/craftcms/cms/issues/2465))
- Fixed a bug where `craft\helpers\ChartHelper::getRunChartDataFromQuery()` was overriding the query’s `SELECT` clause.
- Fixed a bug where Single sections were showing the currently logged-in user as their author.
- Fixed a bug where element queries for entries within Structure sections weren’t getting ordered in the Structure-defined order by default in some cases.
- Fixed a bug where `yii\web\User::getIdentity()` would return `null` when called from a plugin’s `init()` method. ([#2473](https://github.com/craftcms/cms/issues/2473))
- Fixed a bug where entries would not save certain field values if their entry type had changed. ([#2474](https://github.com/craftcms/cms/issues/2474))
- Fixed a bug where database backups could fail if the database password contained quotes or `$` symbols.
- Fixed a bug where entering a database password with a quote in it from the `setup/db-creds` command would cause Craft to generate an invalid `.env` file.

## 3.0.0-RC11 - 2018-02-20

### Added
- Added `craft\services\Sites::getCurrentSite()`.
- Added `craft\services\Sites::getHasCurrentSite()`.
- Added `craft\services\Sites::setCurrentSite()`.

### Changed
- Craft no longer relies on the `CRAFT_SITE` constant to determine which site it should serve. If it’s not set, it will compare the requested URL with the sites’ base URLs and use the closest match. ([#2397](https://github.com/craftcms/cms/issues/2397))
- It is no longer necessary to route sites with base URI paths to separate `index.php` files. Craft will automatically detect URI segments that were meant to be part of the site base URI, and ignore them when routing the request.
- Dashboard widgets no longer animate into place when the browser is resized.
- Added a `$defaultOperator` argument to `craft\helpers\Db::parseParam()` and `parseDateParam()`.
- Updated Yii to 2.0.14.
- Various database columns have been converted back to tiny ints, now that Yii 2 supports it.
- Renamed the fourth argument of `craft\helpers\UrlHelper::url()` from `$mustShowScriptName` to `$showScriptName`. Now passing `false` will explicitly tell it to not include the script name; `null` will tell it to defer to the `omitScriptNameInUrls` config setting; and `true` will tell it to include the script name (as it always has).
- `craft\mail\Mailer::send()` now catches `Swift_TransportException` exceptions that are thrown when sending emails, and logs the exception and returns `false`.

### Deprecated
- Deprecated `craft\helpers\FileHelper::removeFile()`. Use `craft\helpers\FileHelper::unlink()` instead.

### Removed
- Removed `craft\web\Application::getTranslatedBrowserLanguage()`.
- Removed `craft\web\Request::getHostName()`. (`yii\web\Request::getHostName()` is still there, and more robust.)

### Fixed
- Fixed an error that occurred if there were any non-image files in `storage/rebrand/icon/` or `storage/rebrand/logo/`.
- Fixed an error that occurred if an SVG file without `width` and `height` attributes was uploaded as the Login Page Logo. ([#2435](https://github.com/craftcms/cms/issues/2435))
- Fixed a bug where the `defaultCpLanguage` setting was only working in some cases.
- Fixed a bug where Dashboard widgets could go freaking crazy when the browser was resized. ([#2438](https://github.com/craftcms/cms/issues/2438))
- Fixed a bug where Control Panel dates, times, and numbers were formatted for US English regardless of the user’s preferred language, if any plugins were registering an asset bundle that relied on `craft\web\assets\cp\CpAsset` from their `init()` methods. ([#2439](https://github.com/craftcms/cms/issues/2439))
- Fixed a bug where entries would get a “Title cannot be blank” validation error when attempting to publish a draft with a dynamic title. ([#2440](https://github.com/craftcms/cms/issues/2440))
- Fixed a bug where if both the `before` and `after` params were applied to an entry query, results would include entries where either of them matched, rather than both. ([#2442](https://github.com/craftcms/cms/issues/2442))
- Fixed an error that occurred if an invalid user group ID was passed into `craft\services\UserGroups::getGroupById()`. ([#2443](https://github.com/craftcms/cms/issues/2443))
- Fixed an error that occurred if the `privateTemplateTrigger` config setting was set to an empty string. ([#2449](https://github.com/craftcms/cms/issues/2449))
- Fixed a bug where textareas within editable tables may not be set to the full height of the table row if the first textarea within the row was in a hidden column.
- Fixed some awkward styling on editable table headings that contained info buttons.
- Fixed the styling of small select buttons.
- Fixed a bug where relational fields could lose relations to target elements that weren’t available on all of the source element’s sites. ([#2451](https://github.com/craftcms/cms/issues/2451))
- Fixed a bug where Craft was failing silently when a user that required a password reset attempted to log in, if Craft wasn’t properly configured to send emails yet. ([#2460](https://github.com/craftcms/cms/issues/2460))

## 3.0.0-RC10.1 - 2018-02-14

### Fixed
- Fixed an error that occurred when saving an element on multi-site installs. ([#2431](https://github.com/craftcms/cms/issues/2431))
- Fixed an error that occurred when changing an entry’s type. ([#2432](https://github.com/craftcms/cms/issues/2432))

## 3.0.0-RC10 - 2018-02-13

### Added
- Added support for `config/app.web.php` and `config/app.console.php` files, for customizing the application configuration for specific request types. ([#2424](https://github.com/craftcms/cms/issues/2424))
- Added the `setup/db` command, as an alias for `setup/db-creds`.
- Added support for calling `distinct()` on element queries. ([#2414](https://github.com/craftcms/cms/issues/2414))
- Added `craft\behaviors\FieldLayoutBehavior::getFieldLayoutId()` and `setFieldLayoutId()`.
- Added `craft\behaviors\FieldLayoutBehavior::getFields()` and `setFields()`.
- Added `craft\errors\WrongEditionException`.
- Added `craft\fields\Matrix::getBlockTypeFields()`.
- Added `craft\services\Assets::getIconPath()`.
- Added `craft\services\Fields::getFieldIdsByLayoutIds()`.

### Changed
- Asset editor HUDs will now show a thumbnail for all assets that can have one (giving plugins a chance to have a say), regardless of whether Craft thinks it can manipulate the asset. ([#2398](https://github.com/craftcms/cms/issues/2398))
- Assets fields now prevent filename conflicts when new files are uploaded from front-end forms.
- The Plugin Store installer will now enable plugins that had been installed previously but were disabled.
- The Plugin Store installer will now run any pending migrations for plugins that had been installed previously.
- Craft no longer executes two queries per block type when preparing a Matrix block query. ([#2410](https://github.com/craftcms/cms/issues/2410))
- Element types’ `statuses()` method can now specify status colors, by defining a status using an array with `label` and `color` keys.
- It is no longer necessary to set the `fieldLayoutId` attribute when programmatically creating assets, categories, entries, Matrix blocks, tags, or users.
- `craft\services\Assets::getThumbUrl()` and `getThumbPath()` now have `$fallbackToIcon` arguments, which can be set to `false` to cause the methods to throw an exception rather than returning a generic file extension icon, if a real thumbnail can’t be generated for the asset.
- `craft\behaviors\FieldLayoutBehavior` can now be configured with a `fieldLayoutId` attribute, set to either a field layout ID, the name of a method on the owner that will return the ID, or a closure that will return the ID. (`idAttribute` is still supported as well.)
- `craft\behaviors\FieldLayoutBehavior::getFieldLayout()` will now throw an exception if its `fieldLayoutId` attribute was set to an invalid ID.
- `craft\services\Security::encryptByKey()` and `decryptByKey()` no longer require the `$inputKey` argument. If omitted or `null`, the `securityKey` config setting will be used.

### Removed
- Removed `craft\errors\ActionCancelledException`.
- Removed `craft\errors\AssetMissingException`.
- Removed `craft\errors\CategoryNotFoundException`.
- Removed `craft\errors\DbUpdateException`.
- Removed `craft\errors\DownloadPackageException`.
- Removed `craft\errors\ElementException`.
- Removed `craft\errors\ElementSaveException`.
- Removed `craft\errors\EntryNotFoundException`.
- Removed `craft\errors\FilePermissionsException`.
- Removed `craft\errors\MatrixBlockNotFoundException`.
- Removed `craft\errors\MinimumRequirementException`.
- Removed `craft\errors\MissingFileException`.
- Removed `craft\errors\TagNotFoundException`.
- Removed `craft\errors\ValidatePackageException`.
- Removed `craft\errors\ValidationException`.

### Fixed
- Fixed a couple errors that could occur when running the `setup` command if there was no `.env` file or it didn’t define a `DB_DRIVER` environment variable yet.
- Fixed a bug where passing `null` or an empty array to an element query’s `orderBy()` method would still result in the default `orderBy` param being applied.
- Fixed a bug where Table fields would forget if they were saved without any rows in their Default Values setting, and bring back an empty row. ([#2418](https://github.com/craftcms/cms/issues/2418))
- Fixed a bug where the `install/craft` console command no longer accepted `--email`, `--username`, `--password`, `--siteName`, `--siteUrl`, or `--language` options. ([#2422](https://github.com/craftcms/cms/issues/2422))
- Fixed a “Service Unavailable” error that would occur after installing a plugin in the Plugin Store, if it was already Craft-installed with an older schema version.
- Fixed a bug where clicking “Save as a draft” when creating a new entry could result in the main entry getting saved as enabled. ([#2429](https://github.com/craftcms/cms/issues/2429))

## 3.0.0-RC9 - 2018-02-06

### Added
- Added the `init` event to `craft\db\Query`. ([#2377](https://github.com/craftcms/cms/issues/2377))
- Added `craft\elements\Asset::getHasFocalPoint()`.
- Added `craft\services\Composer::$disablePackagist`, which can be set to `false` from `config/app.php` to prevent the Control Panel updater from disabling Packagist.
- Added the `getThumbPath` event to `craft\services\Assets`. ([#2398](https://github.com/craftcms/cms/issues/2398))

### Changed
- The Control Panel updater now optimizes the Composer autoloader in the same step as it installs/updates/removes Composer dependencies.
- When saving an element with a Matrix field that had recently been set to manage blocks on a per-site basis, any nested translatable fields will now retain their per-site values when Matrix duplicates the current blocks for each of the element’s sites.
- The `install/index` command has been renamed to `install/craft`. (It’s still the default action though.)
- Improved the console output for the `install/craft` and `install/plugin` commands.
- `craft\web\Request::getUserIP()` and `getRemoteIP()` now return `null` if the IP is invalid.
- `craft\web\Request::getUserIP()` and `getRemoveIP()` now accept a `$filterOptions` argument.
- `craft\web\View::renderObjectTemplate()` now has a `$variables` argument, for setting any variables that should be available to the template in addition to the object’s properties.
- Fixed an error that could occur when saving a new element with a Matrix field. ([#2389](https://github.com/craftcms/cms/issues/2389))
- Lightswitch fields that don’t have a value yet will now be assigned the default field value, even for existing elements. ([#2404](https://github.com/craftcms/cms/issues/2404))

### Deprecated
- Deprecated `craft\services\Composer::optimize()`. (It will be removed in 3.0.0-RC10.)
- Deprecated the `getAssetThumbUrl` event on `craft\services\Assets`. Use the new `getThumbPath` event instead.

### Removed
- Removed `craft\helpers\App::craftDownloadUrl()`.
- Removed `craft\helpers\App::majorMinorVersion()`.
- Removed `craft\helpers\App::majorVersion()`.
- Removed `craft\helpers\App::normalizeVersionNumber()`.

### Fixed
- Fixed an error that occurred if an empty, non-null value was passed into `craft\elements\Asset::getUrl()`. ([#2376](https://github.com/craftcms/cms/issues/2376))
- Fixed a bug where error messages concerning upgrading/downgrading Craft to incompatible versions were including broken links to supported Craft versions.
- Fixed a bug where updating a plugin could immediately abort with a “Nothing to update” message, if the plugin’s version numbers weren’t formatted consistently. ([#2378](https://github.com/craftcms/cms/issues/2378))
- Fixed an error that could occur when saving a section that isn’t enabled for the first listed site. ([#2385](https://github.com/craftcms/cms/issues/2385))
- Fixed a bug where you would get a PHP error when using `craft\validators\UrlValidator` if the [intl extension](https://secure.php.net/manual/en/book.intl.php) was loaded, but was not compiled with the `INTL_IDNA_VARIANT_UTS46` constant.
- Fixed an error that occurred when attempting to edit an entry, if the latest revision of the entry was created by a deleted user. ([#2390](https://github.com/craftcms/cms/issues/2390))
- Fixed a bug where `craft\web\Request::getUserIP()` was ignoring the `ipHeaders` config setting.
- Fixed an error that could occur when calling `craft\web\Request::getUserIP()` if `$_SERVER['REMOTE_ADDR']` wasn’t set.
- Fixed a bug where all image assets were getting an explicit focal point at 50%-50% when uploaded or saved without an explicit focal point.
- Fixed a bug where Composer’s autoloader may not be generated after running the Control Panel updater.
- Fixed a SQL error that occurred when saving an element with a Matrix field that had recently been set to manage blocks on a per-site basis, if the field had nested relational fields that were set to manage relations on a per-site basis. ([#2391](https://github.com/craftcms/cms/issues/2391))
- Fixed a bug where jQuery Timepicker asset bundle was not depending on the jQuery bundle.
- Fixed a bug where field types that stored boolean data (e.g. Lightswitch fields) were being validated as numbers.

## 3.0.0-RC8 - 2018-01-30

### Added
- Added the `clone()` template function. ([#2375](https://github.com/craftcms/cms/issues/2375))
- Added `craft\base\ApplicationTrait::updateTargetLanguage()`.
- Added `craft\elements\Asset::setFocalPoint()`.
- Added `craft\queue\QueueLogBehavior`.

### Changed
- Craft now logs any output from Composer commands it runs, even if nothing bad happened.
- Message translations registered with `craft\web\View::registerTranslations()` now get `<script>` tags added at the `POS_BEGIN` position, reducing the chance that they’re not ready in time for scripts that rely on them.
- Improved the console output when running `queue/run -v` or `queue/listen -v` from the terminal. ([#2355](https://github.com/craftcms/cms/pull/2355))
- Queue logs now include start/stop/error info about executed jobs. ([#2366](https://github.com/craftcms/cms/pull/2366))
- `craft\elements\Asset::focalPoint` is now a shortcut for `getFocalPoint()`, so the value will either be an array with `x` and `y` keys, or `null` if the asset isn’t an image.
- `craft\elements\Asset::getFocalPoint()` now has a `$asCss` argument that can be set to `true` to get the focal point returned in CSS syntax (e.g. `"50% 25%"`). ([#2356](https://github.com/craftcms/cms/pull/2356))

### Deprecated
- Deprecated `craft\helpers\UrlHelper::urlWithProtocol()` (previously removed).
- Deprecated `craft\helpers\UrlHelper::getProtocolForTokenizedUrl()` (previously removed).

### Fixed
- Fixed an error that could occur when calling `craft\helpers\UrlHelper` methods from the console. ([#2347](https://github.com/craftcms/cms/issues/2347))
- Fixed a bug where Matrix fields with equal Min Blocks and Max Blocks settings weren’t creating default blocks for existing elements. ([#2353](https://github.com/craftcms/cms/issues/2353))
- Fixed a bug where Matrix fields were enforcing required sub-field validation when the parent element was enabled globally but not for the current site.
- Fixed a bug where the Debug Toolbar was showing the Yii logo instead of the Craft logo in the Control Panel. ([#2348](https://github.com/craftcms/cms/issues/2348))
- Fixed a bug where Single entries’ titles were always reset to their section name when saving their section’s settings. ([#2349](https://github.com/craftcms/cms/issues/2349))
- Fixed a bug where Singles’ dynamic entry title formats were not getting applied.
- Fixed an error that occurred when calling `craft\helpers\Assets::getFileKindLabel()` if the file kind was unknown. ([#2354](https://github.com/craftcms/cms/issues/2354))
- Fixed an error that occurred if a textual field was converted to a Table field. ([#2365](https://github.com/craftcms/cms/issues/2365))
- Fixed an error that occurred when submitting a front-end login form with an invalid username and an empty password. ([#2367](https://github.com/craftcms/cms/issues/2367))
- Fixed a bug where tags weren’t getting slugs. ([#883](https://github.com/craftcms/cms/issues/883))

## 3.0.0-RC7.1 - 2018-01-24

### Changed
- Improved the error output when running `migrate` commands with a missing or invalid `--plugin` argument. ([#2342](https://github.com/craftcms/cms/pull/2342))
- Matrix fields only one block type will now auto-create the minimum number of blocks required by the field, per the Min Blocks setting, for new elements. ([#850](https://github.com/craftcms/cms/issues/850))
- Matrix fields with only one block type, and equal values for the Min Blocks and Max Blocks settings, now hide the UI for adding and deleting blocks.

### Fixed
- Fixed a bug where saving a Single or Structure section with more than one site enabled would result in all of the section’s entries getting deleted, in all but one of the sites. ([#2345](https://github.com/craftcms/cms/issues/2345))
- Fixed an error that occurred if a QueryAbortedException was thrown when calling `craft\db\Query::exists()`. ([#2346](https://github.com/craftcms/cms/issues/2346))
- Fixed a bug where Matrix fields with a Min Blocks value were enforcing their Min Blocks setting even if the element wasn’t live yet, which made it impossible for them to be added to a Global Set. ([#2350](https://github.com/craftcms/cms/issues/2350))

## 3.0.0-RC7 - 2018-01-23

### Added
- Asset editor HUDs now show image previews. ([#837](https://github.com/craftcms/cms/issues/837))
- It’s now possible to access the Image Editor from Assets fields and asset indexes by double-clicking on an asset and clicking on the image preview within the HUD that opens up. ([#1324](https://github.com/craftcms/cms/issues/1324))
- Added the “Propagate entries across all enabled sites?” section setting. If disabled, entries will only be associated with the site they were created on. ([#2330](https://github.com/craftcms/cms/issues/2330))
- Added the “Min Blocks” setting to Matrix fields. ([#850](https://github.com/craftcms/cms/issues/850))
- Added the “Date” and “Time” column type options to Table fields.
- Added the `alias()` Twig function, which translates a path/URL alias (`@someAlias/sub/path`) into an actual path/URL. ([#2327](https://github.com/craftcms/cms/issues/2327))
- Added `craft\elements\Asset::getSupportsImageEditor()`.
- Added `craft\elements\db\ElementQuery::inReverse()`, which can be used to reverse the order that elements are returned in.
- Added `craft\events\GetAssetThumbUrlEvent::width` and `height`, which should be used instead of `size`.
- Added `craft\helpers\Assets::filename2Title()`.
- Added `craft\models\CategoryGroup_SiteSettings::getSite()`.
- Added `craft\models\Section::propagateEntries`.
- Added `craft\models\Section_SiteSettings::getSite()`.
- Added the `cp.categories.edit.content` template hook to the `categories/_edit.html` template.
- Added the `cp.entries.edit.content` template hook to the `entries/_edit.html` template.
- Added the `cp.users.edit.content` template hook to the `users/_edit.html` template.
- Added support for `date` and `time` columns to `Craft.EditableTable`.
- Added `Craft.ui.createDateInput()`.
- Added `Craft.ui.createDateField()`.
- Added `Craft.ui.createTimeInput()`.
- Added `Craft.ui.createTimeField()`.

### Changed
- Color values within Table fields are now represented by `craft\fields\data\ColorData` objects.
- Table fields now validate Color cell values.
- Improved the styling of Table fields.
- System messages’ Subject and Body templates can now include site templates. ([#2315](https://github.com/craftcms/cms/issues/2315))
- Improved handling of missing Asset files when generating transforms. ([#2316](https://github.com/craftcms/cms/issues/2316))
- The Craft version number is now shown at the bottom of the global sidebar. ([#2318](https://github.com/craftcms/cms/issues/2318))
- Route params are no longer returned by `craft\web\Request::getQueryParams()`.
- It’s now possible to specify default row values for editable tables by passing a `defaultValues` object to `_includes/forms/editableTable.html`.
- Improved the appearance of the “sidebar” menu button for mobile views. ([#2323](https://github.com/craftcms/cms/issues/2323))
- It’s now possible to modify the variables that will be passed to a template from the `beforeRenderTemplate` and `beforeRenderPageTemplate` events on `craft\web\View`, by modifying `craft\events\TemplateEvent::variables`.
- Replaced the `$size` argument with `$width` and `$height` arguments on `craft\services\Assets::getThumbUrl()` and `getThumbPath()`.
- `craft\models\EntryType::getSection()` now throws a `yii\base\InvalidConfigException` if its `sectionId` property is null or invalid.
- Renamed `craft\helpers\UrlHelper::urlWithProtocol()` to `urlWithScheme()`.
- Renamed `craft\helpers\UrlHelper::getProtocolForTokenizedUrl()` to `getSchemeForTokenizedUrl()`.
- `craft\helpers\StringHelper::toString()` will now call the object’s `__toString()` method if it has one, even if it implements `IteratorAggregate`.s

### Deprecated
- Deprecated `craft\events\GetAssetThumbUrlEvent::size`. Use `width` and `height` instead.

### Removed
- Removed `craft\models\MatrixSettings`.
- Removed `craft\web\assets\imageeditor\ImageEditorAsset`. The image editor is available globally throughout the Control Panel now.

### Fixed
- Fixed a bug where Table fields’ Default Values setting didn’t start with one row by default.
- Fixed a bug where color inputs’ color pickers weren’t preselecting the current input value.
- Fixed a PHP error that occurred when calling `craft\services\UserGroups::getGroupByHandle()` and passing an invalid group handle. ([#2317](https://github.com/craftcms/cms/issues/2317))
- Fixed a PHP error that occurred if an element query was passed into the `|group` filter. A deprecation error is logged instead now.
- Fixed a bug where the Debug Toolbar wasn’t loading in the Control Panel if it wasn’t enabled for the front-end as well.
- Fixed a bug where disabled Matrix blocks were getting deleted on multi-site installs, and when their owner elements were re-saved via Resave Elements jobs. ([#2320](https://github.com/craftcms/cms/issues/2320))
- Fixed the styling of the URL pattern input in route settings modals.
- Fixed a bug where properties of objects passed to `craft\web\View::renderObjectTemplate()` whose values were objects were getting converted to arrays.
- Fixed a bug where HUDs could get themselves into infinite repositioning loops.
- Fixed a bug where the Updates utility would show awkwardly-labeled “Update to” buttons, that triggered a non-update, if the `allowUpdates` config setting was disabled.
- Fixed a bug where Matrix field settings would show the same sub-field settings for all new, unsaved block types, if there were any validation errors on the field.
- Fixed a bug where selecting a Default checkbox on a Dropdown or Radio Buttons field within a Matrix field could deselect the Default checkbox from other Dropdown or Radio Buttons fields within the same Matrix field. ([#2261](https://github.com/craftcms/cms/issues/2261))
- Fixed a bug where words in assets’ default titles were getting separated by hyphens instead of spaces. ([#2324](https://github.com/craftcms/cms/issues/2324))
- Fixed a bug where it was possible to get a section into a strange state if the sites it was enabled for were completely replaced by different sites in one fell swoop.
- Fixed a bug where a red bar remained visible at the top of the Control Panel after resolving a domain mismatch alert. ([#2328](https://github.com/craftcms/cms/issues/2328))
- Fixed a bug where various category, entry, and user actions would remember the currently-selected tab, when they shouldn’t have. ([#2334](https://github.com/craftcms/cms/issues/2334))
- Fixed an error that occurred if an object was passed into `craft\helpers\StringHelper::toString()` that didn’t have a `__toString()` method.

## 3.0.0-RC6 - 2018-01-16

### Added
- Added the “Color” column type option to Table fields.
- Added the `registerSiteTemplateRoots` event to `craft\web\View`, making it possible for plugins and modules to provide templates for the front-end.
- Added missing translations for the Plugin Store.
- Added `craft\db\Connection::getVersion()`.
- Added `craft\elements\User::EVENT_BEFORE_AUTHENTICATE`.
- Added `craft\events\AuthenticateUserEvent`.
- Added `craft\fields\BaseOptionsField::getIsMultiOptionsField()`. ([#2302](https://github.com/craftcms/cms/issues/2302))
- Added `craft\helpers\App::extensionVersion()`.
- Added `craft\helpers\App::normalizeVersion()`.
- Added `craft\helpers\App::phpVersion()`.
- Added `craft\services\Api::getOptimizedComposerRequirements()`.
- Added `craft\services\Composer::getLockPath()`.
- Added `craft\services\Images::getVersion()`.
- Added `craft\services\Search::minFullTextWordLength`, which can be set from `config/app.php` if MySQL’s [ft_min_word_len](https://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_ft_min_word_len) setting is something other than `4`. ([#1736](https://github.com/craftcms/cms/issues/1736))
- Added `craft\validators\UrlValidator::$allowAlias`.
- Added support for `color` columns to `Craft.EditableTable`.
- Added a `defaultValues` setting to `Craft.EditableTable` JavaScript objects.
- Added `Craft.ui.createColorInput()`.
- Added `Craft.ui.createColorField()`.

### Changed
- It’s now possible to reference object properties without typing `object.` in templates parsed with `craft\web\View::renderObjectTemplate()` (e.g. sections’ Entry URI Format settings), even if the property name doesn’t immediately follow a `{` brace.
- Craft no longer sets `passwordResetRequired` to `false` when saving a new password on a user, if it had been set to `true` at the same time that the new password was set.
- Platform versions (PHP, extensions, etc.) no longer include server distribution details, when displayed in the System Report utility, or when posting an issue to GitHub from the Craft Support widget.
- Improved Composer’s performance when updating or installing Craft/plugins in the Control Panel.

### Removed
- Removed `craft\elements\User::setActive()`.
- Removed `craft\helpers\Search::minWordLength()`.
- Removed `craft\helpers\Search::stopWords()`.
- Removed `craft\records\User::setActive()`.

### Fixed
- Fixed a bug that prevented the plugin details modal from loading in the Plugin Store. ([#2289](https://github.com/craftcms/cms/issues/2289))
- Fixed a SQL error that occurred when saving an element with a Matrix field that had recently been made translatable, if it contained any relations. ([#2292](https://github.com/craftcms/cms/issues/2292))
- Fixed a bug where Debug Toolbar controllers were available even when the Debug Toolbar was supposed to be disabled.
- Fixed a bug where the `search` param wasn’t returning any results when the search term was less than 4 characters (or whatever MySQL’s [ft_min_word_len](https://dev.mysql.com/doc/refman/5.7/en/server-system-variables.html#sysvar_ft_min_word_len) setting was set to). ([#1735](https://github.com/craftcms/cms/issues/1735))
- Fixed a bug where Multi-select and Checkboxes fields were setting inaccurate `selected` states on the options returned by their `getOptions()` methods. ([#2301](https://github.com/craftcms/cms/issues/2301))
- Fixed a SQL error that could occur due to an `archived` column name conflict when querying for users. ([#2305](https://github.com/craftcms/cms/issues/2305))
- Fixed an error that occurred when calling `addOrderBy()` on an element query, if `orderBy()` had not been called first. ([#2310](https://github.com/craftcms/cms/issues/2310))
- Fixed an error that occurred on Windows servers if a volume’s File System Path setting contained an alias and any backslashes. ([#2309](https://github.com/craftcms/cms/issues/2309))
- Fixed a bug where Site Base URLs that began with an alias were getting saved as `http://@alias/...`, breaking front-end site URLs. ([#2312](https://github.com/craftcms/cms/issues/2312))
- Fixed one rogue bullet. ([#2314](https://github.com/craftcms/cms/issues/2314))

## 3.0.0-RC5 - 2018-01-09

### Added
- Added the `allowUpdates` config setting, which can be set to `false` to disable updating Craft and plugins from the Control Panel, as well as installing new plugins from the Plugin Store. ([#2154](https://github.com/craftcms/cms/issues/2154))
- Added the `previewCategory` event to `craft\controllers\CategoriesController`.
- Added the `previewEntry` event to `craft\controllers\EntriesController`. ([#2259](https://github.com/craftcms/cms/pull/2259))
- Added `Craft.formatNumber()` for formatting number based on the user’s language.

### Changed
- Moved the “View site” option up to the first position in the system menu.
- Improved the style of Multi-select fields. ([#2271](https://github.com/craftcms/cms/pull/2271))
- More global sidebar improvements. ([#2213](https://github.com/craftcms/cms/issues/2213))
- The `setup/db-creds` console command now supports `--driver`, `--server`, `--port`, `--user`, `--password`, `--database`, and `--schema` options, making it possible to use the command non-interactively. ([#2273](https://github.com/craftcms/cms/issues/2273))
- Documentation and Changelog are now being opened in a new browser tab. ([#2260](https://github.com/craftcms/cms/issues/2260))

### Deprecated
- Deprecated the `allowAutoUpdates` config setting. Use the new `allowUpdates` config setting instead.

### Removed
- Removed support for `'minor-only'` and `'patch-only'` values for the deprecated `allowAutoUpdates` config setting.

### Fixed
- Fixed the position of asset folder toggles. ([#2264](https://github.com/craftcms/cms/issues/2264))
- Fixed a bug where default Craft database backups and restores would fail if there was a space in the file path. ([#2274](https://github.com/craftcms/cms/issues/2274))
- Fixed a bug where non-admins were able to access the Settings page. ([#2275](https://github.com/craftcms/cms/issues/2275))
- Fixed a bug where `craft\fields\data\ColorData::getBlue()` was returning the wrong value. ([#2277](https://github.com/craftcms/cms/pull/2277))
- Fixed a bug where any occurrences of `$` followed by numeric characters in a database password would be stripped out when running the `setup/db-creds` command. ([#2283](https://github.com/craftcms/cms/issues/2283))
- Fixed a bug where the login logo was broken on the Control Panel login screen on Windows. ([#2281](https://github.com/craftcms/cms/issues/2281))
- Fixed a bug where it was not possible to index Assets when using PostgreSQL. ([#2284](https://github.com/craftcms/cms/issues/2284))
- Fixed a bug where the D3 language would fallback to English, even when the user had a different supported language selected.
- Fixed “Active Installs“ number formatting in the Plugin Store. ([#2183](https://github.com/craftcms/cms/issues/2183))
- Fixed an error that occurred when attempting to replace an existing asset with another file of the same name.

## 3.0.0-RC4 - 2018-01-02

### Added
- Added the `aliases` config setting, providing an easy way to define custom [aliases](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html).
- Some URL and path settings are now parsed for [aliases](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html), including sites’ Base URL settings, volumes’ Base URL settings, and Local volumes’ File System Path settings. If those settings currently begin with a Craft 2-style environment variable tag (e.g. `{variable}`),  they will automatically be converted to the new alias syntax (e.g. `@variable`).
- Added the ability to search plugins by package name in the Plugin Store. ([#2139](https://github.com/craftcms/cms/issues/2139))
- Added `craft\errors\InvalidElementException`.
- Added `craft\models\FieldLayoutTab::getHtmlId()`.
- Added a check icon for already installed plugins in the Plugin Store. ([#2207](https://github.com/craftcms/cms/issues/2207))

### Changed
- More global sidebar improvements. ([#2213](https://github.com/craftcms/cms/issues/2213))
- The “New entry” and “New category” buttons on Entries and Categories index pages now load the Edit page for the currently-selected site. ([#2236](https://github.com/craftcms/cms/issues/2236))
- It’s now possible to change a field to an incompatible type. ([#2232](https://github.com/craftcms/cms/issues/2232))
- The Control Panel now uses default system UI fonts, rather than Helvetica Neue or whatever `sans-serif` results in. ([#2241](https://github.com/craftcms/cms/issues/2241))
- Field layout tabs’ IDs are now based on their tab name, for nicer-looking URL fragments.
- Auto-generated site names created when upgrading from Craft 2 now include the ID of the locale they were based on.
- The Plugin Store now shows a “Page not found“ message when there is a routing error.
- Updated svg-sanitizer to ~0.8.2, which no longer removes seemingly-safe `<use>` elements.
- Any `id` attributes (and their references) within SVG files output by the `svg()` template function now get namespaced, avoiding potential conflicts between the SVG and other elements on the page.

### Deprecated
- Deprecated the `environmentVariables` config setting (previously removed). Use the new `aliases` config setting instead. ([#2250](https://github.com/craftcms/cms/issues/2250))

### Fixed
- Fixed a bug where long directory/file names could cause the Deprecation Errors utility page to break out from the content container. ([#2231](https://github.com/craftcms/cms/issues/2231))
- Fixed a bug where entry drafts would discard all but the first new Matrix block when saved. ([2235](https://github.com/craftcms/cms/issues/2235))
- Fixed a bug where the Plugin Store wasn’t loading properly when the `omitScriptNameInUrls` config setting was disabled. ([2209](https://github.com/craftcms/cms/issues/2209))
- Fixed a bug where validation errors that occurred when duplicating an entry or category would result in a generic error page, rather than the original Edit page with validation errors displayed. ([#2237](https://github.com/craftcms/cms/issues/2237))
- Fixed a bug where you could get a fatal PHP error if you were using the latest 2.0.2 release of the `yii2-queue` library from the command line in Craft. ([#2248](https://github.com/craftcms/cms/issues/2248))
- Fixed a bug where some field validation errors weren’t showing up if the Edit Field form was submitted without a name. ([#2249](https://github.com/craftcms/cms/issues/2249))
- Fixed a bug where default field values weren’t being applied to newly-uploaded assets. ([#2238](https://github.com/craftcms/cms/issues/2238))
- Fixed a bug where the window wasn’t scrolling when dragging things near the edges. ([#2253](https://github.com/craftcms/cms/issues/2253))

## 3.0.0-RC3 - 2017-12-19

### Added
- Added the `beforeDeleteTransforms` and `afterDeleteTransforms` events to `craft\services\AssetTransforms`. ([#2212](https://github.com/craftcms/cms/issues/2212))
- Added `craft\events\AssetTransformImageEvent`.

### Changed
- Control Panel tabs that switch the visible pane on the page (rather than linking to a different page) now add a fragment to the URL when selected, so the selected tab can be remembered when sharing the URL.
- Improved the account info presentation in the global sidebar. ([#2213](https://github.com/craftcms/cms/issues/2213))
- Pending jobs in the queue are now ran whenever an image is saved with the Image Editor. ([#2211](https://github.com/craftcms/cms/issues/2211))
- Improved list styles in `.readable` blocks in the Control Panel.

### Fixed
- Fixed a bug where it wasn't possible to expand Asset subfolders sometimes. ([#2210](https://github.com/craftcms/cms/issues/2210), [#2215](https://github.com/craftcms/cms/issues/2215))
- Fixed a bug where changing an Asset's focal point would invalidate the transforms a bit too early. ([#2198](https://github.com/craftcms/cms/issues/2198))
- Fixed a regression where it wasn’t possible to deep-link to specific tabs in the Control Panel. ([#2206](https://github.com/craftcms/cms/issues/2206))
- Fixed a bug where `craft\services\Volumes::getVolumeByHandle()` would return `null` if the volume had just been saved, and `getAllVolumes()` had been called earlier in the request.
- Fixed a PHP error that could occur during a Craft 2 to 3 upgrade.
- Fixed a bug where it was not possible to delete disabled Matrix blocks. ([#2219](https://github.com/craftcms/cms/issues/2219))
- Fixed a SQL error that could occur when storing template caches. ([#1792](https://github.com/craftcms/cms/issues/1792))
- Fixed a layout issue on small screens. ([#2224](https://github.com/craftcms/cms/issues/2224))
- Fixed a bug where Craft would issue unsaved data warnings when leaving edit pages, even if nothing had actually changed, in some cases. ([#2225](https://github.com/craftcms/cms/issues/2225))
- Fixed a bug where element index pages weren’t loading more elements when the content area was scrolled to the bottom. ([#2228](https://github.com/craftcms/cms/issues/2228))

## 3.0.0-RC2 - 2017-12-12

### Added
- Added the `migrate/all` console command for running all Craft, content, and plugin migrations.

### Changed
- Field Type dropdowns no longer completely hide incompatible field types; now they are visible but disabled.
- URI segments matched with the “any” (`*`) token are now passed to the template as variables named `any`, `any2`, `any3`, etc.
- Control Panel templates can now define `.info` elements with multiple paragraphs or line breaks. ([#2185](https://github.com/craftcms/cms/issues/2185))
- Control Panel content tabs now shrink to fit if they’re too wide for the tab bar. ([#2186](https://github.com/craftcms/cms/issues/2186))
- Craft’s bootstrap scripts now ensure that the server is running PHP 7+ before loading any files that would cause a syntax error on lower PHP versions.
- The `migrate/up` console command will now update the appropriate schema version in the database after successfully completing all migrations. ([#1907](https://github.com/craftcms/cms/issues/1907))

### Fixed
- Fixed a bug where the “New entry” and “New category” buttons on entry/category index pages weren’t getting translated. ([#2164](https://github.com/craftcms/cms/issues/2164))
- Fixed a bug where the main content area could expand wider than it was supposed to. ([#2169](https://github.com/craftcms/cms/issues/2169))
- Fixed several Control Panel layout issues.
- Fixed a bug where Color fields were getting saved as `{}`. ([#2170](https://github.com/craftcms/cms/issues/2170))
- Fixed a bug where the `searchScore` property wasn’t being set on elements queried with the `search` param. ([#2174](https://github.com/craftcms/cms/issues/2174))
- Fixed a bug where the Craft logo was broken on the Control Panel login screen on Windows. ([#2179](https://github.com/craftcms/cms/issues/2179))
- Fixed a bug where some updates to RC1 would fail if using PostgreSQL.
- Fixed a bug where `craft\helpers\FileHelper::getMimeType()` was returning `text/html` for some SVG files. ([#2181](https://github.com/craftcms/cms/issues/2181))
- Fixed a bug where routes that contained an “any” (`*`) token were not working. ([#2184](https://github.com/craftcms/cms/issues/2184))
- Fixed a bug where some Control Panel JavaScript features weren’t getting initialized. ([#2188](https://github.com/craftcms/cms/issues/2188))
- Fixed a bug where Craft was never pinging the licensing server to learn about its license key status.
- Fixed a bug where the Edit User Group page was not ensuring the user had an elevated session if one was going to be required. ([#2194](https://github.com/craftcms/cms/issues/2194))
- Fixed a conflict with jQuery UI Autocomplete. ([#2196](https://github.com/craftcms/cms/issues/2196))
- Fixed a bug where Matrix fields were not validating required sub-fields. ([#2197](https://github.com/craftcms/cms/issues/2197))
- Fixed a bug where entry types’ “Title Field Label” and “Title Format” settings weren’t getting validated. ([#2199](https://github.com/craftcms/cms/issues/2199))
- Fixed a bug where changing an Asset's focal point would not invalidate the transforms. ([#2198](https://github.com/craftcms/cms/issues/2198))

### Security
- Fixed an XSS vulnerability in the Control Panel.
- Fixed a bug where an admin could be coerced into installing or removing a plugin.

## 3.0.0-RC1 - 2017-12-05

### Added
- Added the Plugin Store section to the Control Panel (currently in beta; non-commercial plugins only). ([#808](https://github.com/craftcms/cms/issues/808))
- Installed plugins now have a “Disable” option that temporarily disables them without uninstalling them.
- Uninstalled plugins now have a “Remove” option that completely removes their files from the project.
- Added the concept of “Site Groups”. ([#1668](https://github.com/craftcms/cms/issues/1668))
- Added the Craft License to the web-based setup wizard.
- Added a “Connect the database” screen to the web-based setup wizard, which will show up if a database connection can’t already be established, and Craft determines that it can control the DB connection settings via the `.env` file.
- Added the OAuth 2.0 Client library.
- `.formsubmit` elements can now specify a `data-form` attribute, so they no longer need to be nested within the `<form>` they’re associated with.
- Added the “Default Color” setting to Color fields. ([#949](https://github.com/craftcms/cms/issues/949))
- Color fields now return a `craft\fields\data\ColorData` object, with `hex`, `rgb`, `red`, `green`, `blue`, `r`, `g`, `b`, and `luma` properties.
- Added support for the `text/markdown` MIME type.
- Element sources can now specify which sites they are available in, by adding a `sites` key to the source definition.
- Added the `beforeSaveSiteGroup`, `afterSaveSiteGroup`, `beforeDeleteSiteGroup`, and `afterDeleteSiteGroup` events to `craft\services\Sites`.
- Added the “Interlacing” image transform setting. ([#1487](https://github.com/craftcms/cms/issues/1487))
- Added the `svg()` Twig function, which will sanitize and return SVG XML code. You can pass in the path to an SVG file or raw SVG XML code.
- Added an `attr` block to each of the templates in `_includes/forms/`, which can be overridden when the templates are embedded, to add custom HTML attributes to the input elements. ([#1430](https://github.com/craftcms/cms/issues/1430))
- Added a `tabs` block to the `_layouts/cp.html` template that surrounds `<nav id="tabs">`, allowing the tab HTML to be overridden by sub-templates. ([#2128](https://github.com/craftcms/cms/issues/2128))
- Added `craft\controllers\SitesController::actionSaveGroup()`.
- Added `craft\controllers\SitesController::actionDeleteGroup()`.
- Added `craft\db\Connection::createFromConfig()`.
- Added `craft\errors\ApiException`.
- Added `craft\errors\SiteGroupNotFoundException`.
- Added `craft\events\SiteGroupEvent`.
- Added `craft\fields\data\ColorData`.
- Added `craft\image\Raster::getImagineImage()`. (([#1488](https://github.com/craftcms/cms/issues/1488))
- Added `craft\image\Raster::setInterlace()`.
- Added `craft\models\Section::getSiteIds()`.
- Added `craft\models\Site::groupId`.
- Added `craft\models\Site::getGroup()`.
- Added `craft\models\SiteGroup`.
- Added `craft\models\Update::getHasCritical()`.
- Added `craft\models\Update::getHasReleases()`.
- Added `craft\models\Update::getLatest()`.
- Added `craft\models\Updates`.
- Added `craft\records\Site::getGroup()`.
- Added `craft\records\SiteGroup`.
- Added `craft\services\Api`, available from `Craft::$app->api`.
- Added `craft\services\Assets::getThumbPath()`.
- Added `craft\services\Config::getDotEnvPath()`.
- Added `craft\services\Config::setEnvVar()`.
- Added `craft\services\Plugins::disablePlugin()`.
- Added `craft\services\Plugins::enablePlugin()`.
- Added `craft\services\Plugins::isPluginDisabled()`.
- Added `craft\services\Plugins::isPluginEnabled()`.
- Added `craft\services\Plugins::isPluginInstalled()`.
- Added `craft\services\Sites::getAllGroups()`.
- Added `craft\services\Sites::getGroupById()`.
- Added `craft\services\Sites::saveGroup()`.
- Added `craft\services\Sites::deleteGroupById()`.
- Added `craft\services\Sites::deleteGroup()`.
- Added `craft\services\Sites::getSitesByGroupId()`.
- Added `craft\validators\ColorValidator`.
- Added `Craft.ColorInput` (JS class).
- Added the `beforeDisablePlugin`, `afterDisablePlugin`, `beforeEnablePlugin`, and `afterEnablePlugin` events to `craft\services\Plugins`.

### Changed
- The Control Panel has been redesigned for better usability, readability and responsiveness.
- Control Panel templates can now easily add UI elements to the page header, via new `contextMenu` and `actionButton` blocks.
- Control Panel templates can now have a details pane, via the new `details` block.
- The “Delete” button on entry and category edit pages has been moved to the Save button menu.
- The site selection on entry and category edit pages has been moved to the context menu in the page header.
- The icons on date/time inputs now behave like placeholder text; they can be clicked on to focus the input, and they become hidden when the input has a value. ([#1730](https://github.com/craftcms/cms/issues/1730))
- Users’ field layouts can now have multiple tabs. ([#892](https://github.com/craftcms/cms/issues/892))
- Global sets’ field layouts can now have multiple tabs. ([#1196](https://github.com/craftcms/cms/issues/1196))
- Edit User pages’ Save buttons are now positioned in the page header, like similar pages.
- The Language, Week Start Day, and Debug Toolbar settings have been moved to a new “Preferences” tab on the My Account page.
- Users’ Langauge preference is now visible for single-site installs, and now shows all supported application languages; not just the site language(s). ([#847](https://github.com/craftcms/cms/issues/847))
- Element indexes now hide any sources that aren’t available for the currently-selected site. ([#2021](https://github.com/craftcms/cms/issues/2021))
- Fields on multi-site installs can now be translated per site group.
- Resource file URLs now have a timestamp appended to them, preventing browsers from loading cached versions when the files change.
- `craft\services\Elements::parseRefs()` now has a `$siteId` argument, which can be set to the site ID that referenced elements should be queried in.
- `craft\web\AssetManager::getPublishedUrl()` now has a `$filePath` argument, which can be set to a file path relative to `$sourcePath`, which should be appended to the returned URL.
- Color inputs have been redesigned so they look the same regardless of whether the browser supports `<input type="color">`, and no longer use a JavaScript color-picker polyfill. ([#2059](https://github.com/craftcms/cms/issues/2059), [#2061](https://github.com/craftcms/cms/issues/2061))
- Color inputs can now be left blank.
- CP nav item definitions registered with `craft\web\twig\variables\Cp::EVENT_REGISTER_CP_NAV_ITEMS` can now specify their icon with an `icon` key, whether it’s the path to an SVG file, SVG XML code, or a Craft font icon ligature. (Support for the `iconSvg` key has been removed.)
- Element source definitions can now include `icon` or `iconMask` keys, set to either the path to an SVG file, SVG XML code, or a Craft font icon ligature. (Use `icon` for colored icons; use `iconMask` for masked icons that should change color depending on whether the source is selected.)
- Assets fields and the Assets index page now create a queue runner automatically after new assets are uploaded, in case there are any new jobs that were registered as part of the asset save process.
- Craft now uses Sendmail as the default mailer, as Swift Mailer removed support for the PHP mailer in 6.0. ([#2149](https://github.com/craftcms/cms/issues/2149))
- Structure info and relationships are now available on elements during Structure operations. ([#2153](https://github.com/craftcms/cms/issues/2153))
- Craft will now always respond with JSON when an exception occurs for requests that include the `Accept: application/json` header.
- `craft\config\DbConfig` will not parse the `dsn` string if it was provided, populating the other config values.
- `craft\services\Plugins::getComposerPluginInfo()` will now return all Composer plugin info, if no handle is specified.
- `craft\services\Updates::getIsCriticalUpdateAvailable()` now has a `$check` argument.
- `craft\services\Updates::getTotalAvailableUpdates()` now has a `$check` argument.
- `craft\events\LoginFailureEvent` now has a customizable `message` property that defines the user-facing error message. ([#2147](https://github.com/craftcms/cms/issues/2147))
- Updated all Control Panel language translations.

### Removed
- Removed the “Rich Text” field type. If you had any Rich Text fields, just install the [Redactor](https://github.com/craftcms/redactor) plugin after updating to RC1+.
- Removed the “Position Select” field type. Any Position Select fields will get converted to Dropdowns.
- The `_includes/forms/field.html` template no longer supports a `dataAttributes` variable. (Use the new `attr` block instead.)
- Removed `craft\events\RegisterRedactorPluginEvent`.
- Removed `craft\events\RegisterRichTextLinkOptionsEvent`.
- Removed `craft\fields\data\RichTextData`.
- Removed `craft\fields\PositionSelect`.
- Removed `craft\fields\RichText`.
- Removed `craft\mail\transportadapters\Php`.
- Removed `craft\models\AppUpdate`.
- Removed `craft\models\AppUpdateRelease`.
- Removed `craft\services\Et::checkForUpdates()`.
- Removed `craft\services\Et::downloadUpdate()`.
- Removed `craft\services\Et::getUpdateFileInfo()`.
- Removed `craft\services\Updates::checkForUpdates()`.
- Removed `craft\services\Updates::checkPluginChangelogs()`.
- Removed `craft\services\Updates::fetchPluginChangelog()`.
- Removed `craft\services\Updates::parsePluginChangelog()`.
- Removed `craft\services\Updates::addNotesToPluginRelease()`.s
- Removed `craft\web\assets\redactor\RedactorAsset`.
- Removed `craft\web\assets\richtext\RichTextAsset`.
- Removed `lib/colorpicker/`.
- Removed `lib/redactor/`.
- Removed `Craft.ColorPicker` (JS class).
- Removed `Craft.RichTextInput` (JS class).
- Removed the `oauthtokens` table.

### Fixed
- Fixed a bug where removing the parent element from an entry or category had no effect. ([#2152](https://github.com/craftcms/cms/issues/2152))
- Fixed a bug where `{id}` tags in element URI formats weren’t getting replaced with the element ID when saving a brand new element. ([#2157](https://github.com/craftcms/cms/issues/2157))
- Fixed a bug where it was not possible to reassign a field within a Matrix field to a different field type if its current field type class was missing.

## 3.0.0-beta.36 - 2017-11-29

### Added
- Added a `cp.layouts.base` template hook to the `_layouts/base.html` template.

### Fixed
- Fixed a SQL error that occurred when installing Craft. ([#2142](https://github.com/craftcms/cms/issues/2142))

## 3.0.0-beta.35 - 2017-11-28

### Added
- Added `craft\elements\Asset::getFilename()`.
- Added `craft\helpers\FileHelper::isSvg()`.
- Added the `$generateNow` argument to `craft\services\Assets::getAssetUrl()`, which can be set to `true` to generate a transform immediately if it doesn’t exist. ([#2103](https://github.com/craftcms/cms/issues/2103))

### Changed
- The default `trustedHosts` config setting value is now `['any']`, meaning all hosts are trusted.
- `craft\db\Query::one()` and `nth()` now return `null` instead of `false` if there was no result. ([#2105](https://github.com/craftcms/cms/issues/2105))
- `craft\services\Content::getContentRow()` now returns `null` instead of `false` if there was no result.
- Updated Composer to ~1.5.2.
- Updated Stringy to ~3.1.0.
- Updated svg-sanitizer to ~0.7.2.
- Updated Guzzle to ~6.3.0.
- Updated CLI-Prompt to ~1.0.3.
- Updated Twig to ~2.4.4.
- Updated SwiftMailer Extension for Yii 2 to ~2.1.0.

### Fixed
- Fixed a PHP error that occurred when running the `cache/flush-all` command. ([#2099](https://github.com/craftcms/cms/issues/2099))
- Fixed a PHP permissions error that might happen on some Craft 2 to Craft 3 upgrades.
- Fixed a bug where ET and changelog requests weren’t factoring in custom Guzzle configs in `config/guzzle.php`.
- Fixed a SQL error that could occur when upgrading a Craft 2 site to Craft 3 if there were any deprecation errors originated by a template, but the line number wasn’t known.
- Fixed a PHP error that could occur if there was a logged-in user ID in the PHP session, but the corresponding user didn’t exist in the database. ([#2117](https://github.com/craftcms/cms/issues/2117))
- Fixed a bug where `QueryAbortedException`s weren’t getting caught by `ResaveElement` jobs.
- Fixed a PHP error that occurred when executing an element query with a custom `SELECT` clause.
- Fixed the Debug Toolbar’s “C” icon in Firefox.
- Fixed a bug where the selected site was not maintained when switching between global sets. ([#2123](https://github.com/craftcms/cms/issues/2123))
- Fixed a deprecation error when a relation field’s column was included in an element index page.
- Fixed an error that could occur in the Control Panel if the `queue` component was not an instance of `craft\queue\QueueInterface`.

## 3.0.0-beta.34 - 2017-11-09

### Added
- Added the `trustedHosts`, `secureHeaders`, `ipHeaders`, and `secureProtocolHeaders` config settings, which map to the `yii\web\Request` properties of the same names. They should be used to fix SSL detection for environments where an `X-Forwarded-Proto` HTTP header is used to forward SSL status to the web server. See [Trusted Proxies and Headers](http://www.yiiframework.com/doc-2.0/guide-runtime-requests.html#trusted-proxies) in Yii’s documentation for an explanation of these properties.
- Created an `oauthtokens` database table.
- Added the [League's OAuth 2 Client](http://oauth2-client.thephpleague.com/) as a dependency. ([#1481](https://github.com/craftcms/cms/issues/1481))

### Fixed
- Fixed a bug where updating to beta 31 could cause a fatal database error on PostgreSQL.

## 3.0.0-beta.33 - 2017-11-08

### Fixed
- Fixed a bug where Craft was saving entries when attempting to switch the entry type.

## 3.0.0-beta.32 - 2017-11-08

### Fixed
- Fixed a segmentation fault that occurred on fresh installs.

## 3.0.0-beta.31 - 2017-11-08

### Added
- Added the `getAssetThumbUrl` event to `craft\services\Assets`. ([#2073](https://github.com/craftcms/cms/issues/2073))
- Added `craft\events\GetAssetThumbUrlEvent`.
- Added `craft\services\Plugins::getPluginHandleByClass()`.

### Changed
- Control Panel JavaScript translations registered with `craft\web\View::registerTranslations()` now get registered via `registerJs()`, so Ajax-loaded Control Panel content can register new translations to the main page on the fly.
- `craft\helpers\Component::createComponent()` will now throw a `MissingComponentException` if the component belongs to a plugin that’s not installed.
- `craft\helpers\FileHelper::removeDirectory()` now uses `Symfony\Component\Filesystem::remove()` as a fallback if an error occurred.
- `craft\db\Query::one()` and `scalar()` now explicitly add `LIMIT 1` to the SQL statement.
- It’s now possible to create element indexes with batch actions on non-index pages. ([#1479](https://github.com/craftcms/cms/issues/1479))
- Updated Yii to 2.0.13.
- Updated D3 to 4.11.0.
- Updated Fabric to 1.7.19.
- Updated Inputmask to 3.3.10.
- Updated jQuery to 3.2.1.
- Updated Timepicker to 1.11.12.
- Updated yii2-pjax to 2.0.7.

### Removed
- Removed the “RSS caches” option from the Clear Caches utility. (RSS feeds are cached using Craft’s data caching now.)
- Removed the `cacheMethod` config setting. To use a different cache method, override the `cache` application component from `config/app.php`. ([#2053](https://github.com/craftcms/cms/issues/2053))
- Removed `craft\config\DbCacheConfig`.
- Removed `craft\config\FileCacheConfig`.
- Removed `craft\config\MemCacheConfig`.
- Removed `craft\services\Cache::getDbCache()`.
- Removed `craft\services\Cache::getFileCache()`.
- Removed `craft\services\Cache::getMemCache()`.
- Removed `craft\services\Plugins::getPluginByClass()`.
- Removed `craft\web\View::getTranslations()`.
- Removed the `getTranslations()` template function.

### Fixed
- Fixed an issue where `photoSubpath` user setting was missing a default value. ([#2095](https://github.com/craftcms/cms/issues/2095))
- Fixed a Composer error that could occur when updating Craft or a plugin from the Control Panel.
- Fixed a PHP error that occurred when loading the French app translation messages.
- Fixed a PHP error that occurred if a reference tag didn’t specify a property name and the element didn’t have a URL. ([#2082](https://github.com/craftcms/cms/issues/2082))
- Fixed a bug where the `install` console command wasn’t validating the password length.
- Fixed a bug where the Debug Toolbar was labeling the current user as a guest.
- Fixed a bug where image editor strings were not getting translated.
- Fixed various PHP errors that could occur after uninstalling (but not Composer-removing) a plugin, if any plugin-supplied components (fields, widgets, etc.) were still around. ([#1877](https://github.com/craftcms/cms/issues/1877))
- Fixed a bug where the image editor was re-saving images even if the only thing that changed was the focal point. ([#2089](https://github.com/craftcms/cms/pull/2089))
- Fixed a PHP error that occurred when duplicating an entry in a section that has URLs.

## 3.0.0-beta.30 - 2017-10-31

### Added
- Added `craft\base\Element::SCENARIO_LIVE`, which should be used when required custom field validation is desired.
- Added `craft\base\PluginTrait::$isInstalled`, which will be set to `true` or `false` depending on whether the plugin is currently installed.
- Added `craft\console\Request::getIsLivePreview()` (always `false`). ([#2020](https://github.com/craftcms/cms/pull/2020))
- Added `craft\services\AssetTransforms::getTransformUri()`.
- Added `craft\web\Application::ensureResourcePathExists()`.
- Added `craft\web\Application::debugBootstrap()`.
- The installer now creates a “Common” field group.
- It's now possible to specify subpath for uploaded user photos. ([#1575](https://github.com/craftcms/cms/issues/1575))
- Added the `preserveExifData` config setting, `false` by default and requires Imagick. ([#2034](https://github.com/craftcms/cms/issues/2034))

### Changed
- Explicitly added `craft\base\PluginInterface::getVersion()`. ([#2012](https://github.com/craftcms/cms/issues/2012))
- Improved the contrast of focal point icons. ([#1452](https://github.com/craftcms/cms/issues/1452))
- Craft no longer requires you to manually create a [pgpass](https://www.postgresql.org/docs/9.4/static/libpq-pgpass.html) file when using the default database backup and restore commands.
- Renamed `craft\services\Assets::ensureFolderByFullPathAndVolumeId()` to `craft\services\Assets::ensureFolderByFullPathAndVolume()`.
- It is now possible for application configs returned by `config/app.php` to override the application class, via a `'class'` key.

### Removed
- Removed `craft\base\Element::validateCustomFields`.
- Removed `craft\base\Element::validateCustomFields()`.

### Fixed
- Fixed a bug where Craft was not enforcing current password validation when a user changed their password from a front-end form.
- Fixed a bug where Craft was not performing normal user validation when an invalid profile photo was uploaded from a front-end form.
- Fixed a bug where image transform URLs were getting a backslash on Windows servers. ([#2026](https://github.com/craftcms/cms/issues/2026))
- Fixed a 404 error that occurred when loading the jQuery.payment library in the Control Panel.
- Fixed a bug where Craft’s bootstrap file was not taking into account the `CRAFT_LICENSE_KEY_PATH` PHP constant when doing folder sanity checks.
- Fixed a bug where the Password field in the installer wizard wasn’t displaying validation errors.
- Fixed a JavaScript error that occurred after running the Find and Replace utility, preventing the Control Panel from immediately tracking the job’s progress. ([#2030](https://github.com/craftcms/cms/issues/2030))
- Fixed a bug where Craft would consider PDF to be a manipulatable image which is not desired behavior. ([#1938](https://github.com/craftcms/cms/issues/1938))
- Fixed a bug where the self-updater wouldn’t work on environments without a `HOME` or `COMPOSER_HOME` environment variable set. ([#2019](https://github.com/craftcms/cms/issues/2019))
- Fixed a PHP error if any of the URL rules weren’t an instance of `craft\web\UrlRule`. ([#2042](https://github.com/craftcms/cms/pull/2042))
- Fixed a bug where `craft\helpers\FileHelper::getMimeType()` was returning `'text/plain'` for `.svg` files that didn’t have an XML declaration.
- Fixed an error that occurred if the database credentials were set correctly but no database had been selected yet.
- Fixed a bug where calls to undefined methods within Twig templates were A) not suppressing the UnknownMethodException when Dev Mode was disabled; and B) not showing the appropriate template line when Dev Mode was disabled. ([#2066](https://github.com/craftcms/cms/pull/2066))
- Fixed a bug where custom element query conditions were getting ignored when `{% paginate %}` tags fetched the current page’s elements. ([#2051](https://github.com/craftcms/cms/issues/2051))

## 3.0.0-beta.29 - 2017-09-29

### Added
- Added the `checkboxGroupField()` macro to `_includes/forms.html`. ([#1991](https://github.com/craftcms/cms/issues/1991))
- Added the `Craft.DynamicGenerator` JavaScript class, for creating dynamic input generators.
- Added `craft\config\DbConfig::updateDsn()`.
- Added `craft\console\Request::getIsActionRequest()` (always `false`).
- Added `craft\console\Request::getIsCpRequest()` (always `false`).
- Added `craft\console\Request::getIsSiteRequest()` (always `false`).
- Added a `setup/db-creds` command that collects all the database credentials, tests them, and saves them to the `.env` file.
- Added a `setup` command that runs through the `setup/security-key`, `setup/db-creds`, and `install` commands.
- Brought back the `siteName` config setting. ([#2003](https://github.com/craftcms/cms/issues/2003))

### Changed
- It is now possible to force an update to be installed, even if Craft thinks that someone else may already be performing an update. ([#1990](https://github.com/craftcms/cms/issues/1990))
- `options` arrays passed to `_includes/forms/checkboxGroup.html` and `checkboxSelect.html` can now contain any of the properties supported by `checkbox.html` (e.g. `disabled`). ([#1994](https://github.com/craftcms/cms/issues/1994))
- `options` arrays passed to `_includes/forms/radioGroup.html` can now contain any of the properties supported by `radio.html` (e.g. `disabled`). ([#1994](https://github.com/craftcms/cms/issues/1994))
- The `limit` property is no longer required by `_includes/forms/elementSelect.html`.
- Craft’s required PHP version and extensions are now specified in `composer.json`.
- Craft no longer re-saves *all* localizable elements after a new site is created; entries and Matrix blocks are skipped, and plugins that supply custom element types must now re-save their elements manually as well.

### Fixed
- Fixed a PHP error that could occur if a DeleteStaleTemplateCaches job was improperly configured.
- Fixed a PHP error that occurred after canceling a DB-only update.
- Fixed a bug where all fields were getting marked as translatable on edit pages. ([#1996](https://github.com/craftcms/cms/issues/1996))
- Fixed a PHP error that would occur when calling `craft\services\UserGroups::getGroupByHandle()`.
- Fixed a JavaScript error that occurred if an asset select input was initialized without a `criteria` setting.
- Fixed a bug where field types, volume types, mail transport types, and widget types weren’t getting listed in alphabetical order.
- Fixed a bug where the queue info in the Control Panel wasn’t refreshing instantly after retrying or canceling a failed job.
- Fixed a bug where a queue job to resave all Matrix blocks after creating a new site would fail. ([#2001](https://github.com/craftcms/cms/issues/2001))
- Fixed a PHP error that occurred when submitting a front-end user profile form with a new user photo. ([#2005](https://github.com/craftcms/cms/issues/2005))

## 3.0.0-beta.28 - 2017-09-15

### Changed
- Console requests no longer validate the DB connection settings up front, so the `setup/security-key` console command can be run even with invalid settings.

### Fixed
- Fixed a PHP error that occurred when saving a new relational field within a Matrix field, if the “Manage relations on a per-site basis” setting was enabled. ([#1987](https://github.com/craftcms/cms/issues/1987))
- Fixed a bug where the `setup/security-key` console command wouldn’t recognize that the `.env` file already specified a security key, if it wasn’t defined on the first line of the file.

## 3.0.0-beta.27 - 2017-09-15

### Added
- Added the `install/plugin` console command, which makes it possible to install plugins from the command line. ([#1973](https://github.com/craftcms/cms/pull/1973))
- Added the `setup/security-key` console command, which generates a new security key and stores it in a `CRAFT_SECURITY_KEY` variable in the project’s `.env` file.
- Added support for a `CRAFT_LICENSE_KEY_PATH` PHP constant, which can be used to customize the location of the license key file. ([#1015](https://github.com/craftcms/cms/issues/1015))
- Added the “Email” and “URL” field types. ([#1981](https://github.com/craftcms/cms/pull/1981))
- Added `craft\helpers\StringHelper::split()`.
- Added `craft\services\Assets::getThumbUrl()`.
- Added `craft\services\Path::getAssetThumbsPath()`.
- Added `craft\web\View::createTwig()`.
- Added `craft\web\View::registerTwigExtension()`, which should be used instead of `craft\web\View::getTwig()->addExtension()`.
- Added a `@root` path alias, which is set to the project root directory (what `$craftPath`/`CRAFT_BASE_PATH` is set to in `index.php`).
- Added the `assets/download-temp-asset` action, for viewing temporary assets that haven’t been placed in their permanent volume yet.
- Added the `assets/generate-thumb` action, which generates an asset thumb and redirects to its URL.
- The `_includes/forms/field` include template now supports passing a `data` variable, set to an object of `data-x` attribute name/value pairs.

### Changed
- Renamed the `validationKey` config setting to `securityKey`.
- If the `securityKey` config setting isn’t set, an auto-generated key will now be stored at `storage/security.key` rather than `storage/runtime/validation.key`.
- Plugin translation sources are now configured with `forceTranslations` enabled.
- Checkbox and radio groups now have `div.checkbox-group` and `div.radio-group` container elements. ([#1965](https://github.com/craftcms/cms/pull/1965))
- Improved the performance of `craft\services\Images::getSupportImageFormats()`. ([#1969](https://github.com/craftcms/cms/pull/1969))
- Improved the performance of `craft\helpers\Image::canManipulateAsImage()`. ([#1969](https://github.com/craftcms/cms/pull/1969))
- Queue info requests in the Control Panel no longer extend the user session.
- `craft\web\View` now manages two separate Twig environments – one for CP templates and another for site templates.
- Custom field inputs’ `<div class="field">` container elements now have a `data-type` attribute set to the field’s class name. ([#1965](https://github.com/craftcms/cms/pull/1965))
- Asset thumbnails are now published to the web-accessible `cpresources/` folder, so they can be served by web servers directly rather than going through PHP. ([#1982](https://github.com/craftcms/cms/issues/1982))
- Moved the location of cached asset sources, thumbnails, and icons up to `storage/runtime/assets/`.
- Renamed `craft\services\Assets::getUrlForAsset()` to `getAssetUrl()`, and now it is possible that `null` will be returned.
- Renamed `craft\services\Path::getAssetsTempVolumePath()` to `getTempAssetUploadsPath()`.
- Renamed `craft\services\Path::getAssetsImageSourcePath()` to `getAssetSourcesPath()`.

### Deprecated
- Splitting a string on commas via `craft\helpers\ArrayHelper::toArray()` is now deprecated. Use `craft\helpers\StringHelper::split()` instead.
- Deprecated the `defaultFilePermissions`, `defaultFolderPermissions`, `useWriteFileLock`, `backupDbOnUpdate`, `restoreDbOnUpdateFailure`, `activateAccountFailurePath`, and `validationKey`. (These were removed outright in earlier Craft 3 Beta releases.)
- Running Craft without the `securityKey` config setting explicitly set is now deprecated.
- Deprecated `craft\services\Security::getValidationKey()`. Use `Craft::$app->config->general->securityKey` instead.

### Removed
- Removed support for “resource URLs”, including the `resources` service (`craft\services\Resources`), `craft\helpers\UrlHelper::resourceTrigger()`, `craft\helpers\UrlHelper::resourceUrl()`, the `resourceUrl()` Twig function, the `Craft.getResourceUrl()` JS function, the `resourceTrigger` config setting.
- Removed `craft\base\ApplicationTrait::getResources()`.
- Removed `craft\elements\Asset::getHasThumb()`.
- Removed `craft\services\AssetTransforms::getResizedAssetServerPath()`.
- Removed `craft\services\Path::getAssetsCachePath()`.
- Removed `craft\services\Path::getResizedAssetsPath()`.
- Removed `craft\services\Path::getTempUploadsPath()`.

### Fixed
- Fixed a SQL error that could occur when using the `relatedTo` element query param. ([#1939](https://github.com/craftcms/cms/issues/1939))
- Fixed a bug where the “Parent” field was showing up on Edit Entry pages in Structure sections that were limited to a single level. ([#1940](https://github.com/craftcms/cms/pull/1940))
- Fixed a bug where the “Delete Stale Template Cache” task would fail when saving an existing Single section.
- Fixed a bug where it was not possible to use `:notempty:` with PostgreSQL.
- Fixed a bug where the `job` column in the `queue` table wasn’t large enough to store some job data. ([#1948](https://github.com/craftcms/cms/issues/1948))
- Fixed a JS error that occurred on CP templates that extended `_layouts/cp` but overrode the `body` block to remove the sidebar, if there were any running/waiting jobs in the queue. ([#1950](https://github.com/craftcms/cms/issues/1950))
- Fixed some JS errors due to outdated references to `Craft.runPendingTasks()`. ([#1951](https://github.com/craftcms/cms/issues/1951))
- Fixed a bug where the “Translation Method” setting was visible for existing Matrix sub-fields even when there was only one available translation method (e.g. Entries fields). ([#1967](https://github.com/craftcms/cms/issues/1967))
- Fixed a bug where Craft would get confused whether or not it could manipulate SVG files. ([#1874](https://github.com/craftcms/cms/issues/1874))
- Fixed a CSRF validation error that would occur when attempting to re-login via the login modal in the Control Panel. ([#1957](https://github.com/craftcms/cms/issues/1957))
- Fixed a “divide by zero” error when paginating an empty set of elements. ([#1970](https://github.com/craftcms/cms/pull/1970))
- Fixed a bug where the “Host Name”, “Port”, and “Timeout” SMTP mailer type settings weren’t marked as required. ([#1976](https://github.com/craftcms/cms/issues/1976))
- Fixed some weird behavior when saving a Matrix field with validation errors. ([#1971](https://github.com/craftcms/cms/issues/1971))
- Fixed a bug where temporary assets didn’t get URLs.
- Fixed some deprecation errors in the Control Panel. ([#1983](https://github.com/craftcms/cms/issues/1983))
- Fixed a PHP error that occurred when saving an entry or category with a parent entry/category, if there were any validation errors. ([#1984](https://github.com/craftcms/cms/issues/1984))

## 3.0.0-beta.26 - 2017-08-22

### Fixed
- Fixed a SQL error that could occur when logging deprecation errors if the line number is unknown. ([#1927](https://github.com/craftcms/cms/issues/1927))
- Fixed a deprecation error that occurred when using a Tags field. ([#1932](https://github.com/craftcms/cms/issues/1932))
- Fixed a PHP error that occurred if a volume type class was missing. ([#1934](https://github.com/craftcms/cms/issues/1934))
- Fixed a PHP error that occurred when saving a new entry in a section with versioning enabled. ([#1935](https://github.com/craftcms/cms/issues/1935))

## 3.0.0-beta.25 - 2017-08-17

### Added
- Added `craft\elements\Entry::$revisionCreatorId`.
- Added `craft\services\EntryRevisions::doesEntryHaveVersions()`.

### Changed
- Craft now retroactively creates entry versions when saving entries, for entries that didn’t have any version history yet. ([#1926](https://github.com/craftcms/cms/issues/1926))

### Fixed
- Fixed an error that occurred when chaining a custom field method and a normal query method (e.g. `all()`) together on an element query. ([#1887](https://github.com/craftcms/cms/issues/1887))
- Fixed a PHP error that occurred when processing a user verification request.
- Fixed a bug where newly-created `div.matrixblock` elements weren’t getting a `data-type` attribute like existing blocks had. ([#1925](https://github.com/craftcms/cms/pull/1925))
- Fixed a bug where you would get a SQL error if you tried to push a job to the queue using PostgreSQL.
- Fixed a bug that would trigger the browser’s JavaScript debugger if you saved a Matrix field that had collapsed blocks.
- Fixed a bug where `craft\helpers\ChartHelper::getRunChartDataFromQuery()` an SQL query instead of the actual results.
- Fixed a bug where `craft\controllers\BaseElementsController::context()` was expecting a`string ` return, while return could also be `null`.

## 3.0.0-beta.24 - 2017-08-15

### Added
- Craft’s tasks implementation has been replaced with a queue, based on the [Yii 2 Queue Extension](https://github.com/yiisoft/yii2-queue). ([#1910](https://github.com/craftcms/cms/issues/1910))
- The “Failed” message in the queue HUD in the Control Panel now shows the full error message as alt text. ([#855](https://github.com/craftcms/cms/issues/855))
- It’s now possible to install Craft from the command line, using the new `install` command. ([#1917](https://github.com/craftcms/cms/pull/1917))
- Added the `instance of()` Twig test.
- Added `craft\base\FlysystemVolume`, which replaces `craft\base\Volume` as the new base class for Flysystem-based volumes.
- Added `craft\behaviors\SessionBehavior`, making it possible for `config/app.php` to customize the base `session` component while retaining Craft’s custom session methods.
- Added `craft\controllers\QueueController`.
- Added `craft\events\UserEvent`.
- Added `craft\queue\BaseJob`, a base class for queue jobs that adds support for descriptions and progress.
- Added `craft\queue\Command`, which provides `queue/run`, `queue/listen`, and `queue/info` console commands.
- Added `craft\queue\InfoAction`.
- Added `craft\queue\JobInterface`, an interface for queue jobs that want to support descriptions and progress.
- Added `craft\queue\jobs\DeleteStaleTemplateCaches`, replacing `craft\tasks\DeleteStaleTemplateCaches`.
- Added `craft\queue\FindAndReplace`, replacing `craft\tasks\FindAndReplace`.
- Added `craft\queue\GeneratePendingTransforms`, replacing `craft\tasks\GeneratePendingTransforms`.
- Added `craft\queue\LocalizeRelations`, replacing `craft\tasks\LocalizeRelations`.
- Added `craft\queue\UpdateElementSlugsAndUris`, replacing `craft\tasks\UpdateElementSlugsAndUris`.
- Added `craft\queue\Queue`, a built-in queue driver.
- Added `craft\queue\QueueInterface`, an interface for queue drivers that want to support the queue UI in the Control Panel.
- Added `craft\services\Composer::getJsonPath()`.
- Added `craft\services\Volumes::getVolumeByHandle()`.

### Changed
- Renamed the `runTasksAutomatically` config setting to `runQueueAutomatically`.
- Logs that occur during `queue` requests now get saved in `storage/logs/queue.log`.
- The updater now ensures it can find `composer.json` before putting the system in Maintenance Mode, reducing the likelihood that Craft will mistakingly think that it’s already mid-update later on. ([#1883](https://github.com/craftcms/cms/issues/1883))
- The updater now ensures that the `COMPOSER_HOME`, `HOME` (\*nix), or `APPDATA` (Windows) environment variable is set before putting the system in Maintenance Mode, reducing the likelihood that Craft will mistakingly think that it’s already mid-update later on. ([#1890](https://github.com/craftcms/cms/issues/1890#issuecomment-319715460)) 
- `craft\mail\Mailer::send()` now processes Twig code in the email message before parsing it as Markdown, if the message was composed via `craft\mail\Mailer::composeFromKey()`. ([#1895](https://github.com/craftcms/cms/pull/1895))
- `craft\mail\Mailer::send()` no longer catches exceptions thrown by its parent method, or fires a `sendMailFailure` event in the event of a send failure. ([#1896](https://github.com/craftcms/cms/issues/1896))
- Renamed `craft\helpers\Component::applySettings()` to `mergeSettings()`, and it no longer takes the `$config` argument by reference, instead returning a new array.
- Renamed `craft\web\twig\nodes\GetAttr` to `GetAttrNode`.
- `craft\base\Volume` is now only focused on things that every volume would need, regardless of whether it will use Flysystem under the hood.
- `craft\base\VolumeInterface::createFileByStream()`, `updateFileByStream()`, `deleteFile()`, `renameFile()`, `copyFile()`, `createDir()`, `deleteDir()`, and `renameDir()` no longer require their implementation methods to return a boolean value.
- `div.matrixblock` elements in the Control Panel now have a `data-type` attribute set to the Matrix block type’s handle. ([#1915](https://github.com/craftcms/cms/pull/1915))

### Deprecated
- Looping through element queries directly is now deprecated. Use the `all()` function to fetch the query results before looping over them. ([#1902](https://github.com/craftcms/cms/issues/1902))

### Removed
- Removed `craft\base\Task`.
- Removed `craft\base\TaskInterface`.
- Removed `craft\base\TaskTrait`.
- Removed `craft\controllers\TasksController`.
- Removed `craft\controllers\VolumesController::actionLoadVolumeTypeData()`.
- Removed `craft\db\TaskQuery`.
- Removed `craft\events\MailFailureEvent`.
- Removed `craft\events\TaskEvent`.
- Removed `craft\events\UserActivateEvent`. Use `craft\events\UserEvent` instead.
- Removed `craft\events\UserSuspendEvent`. Use `craft\events\UserEvent` instead.
- Removed `craft\events\UserTokenEvent`. Use `craft\events\UserEvent` instead.
- Removed `craft\events\UserUnlockEvent`. Use `craft\events\UserEvent` instead.
- Removed `craft\events\UserUnsuspendEvent`. Use `craft\events\UserEvent` instead.
- Removed `craft\mail\Mailer::EVENT_SEND_MAIL_FAILURE`.
- Removed `craft\records\Task`.
- Removed `craft\services\Tasks`.
- Removed `craft\tasks\ResaveAllElements`.
- Removed `craft\web\Request::getQueryParamsWithoutPath()`.
- Removed `craft\web\twig\variables\Tasks`, which provided the deprecated `craft.tasks` template variable.

### Fixed
- Fixed a migration error that could occur if `composer.json` didn’t have any custom `repositories` defined.
- Fixed a bug where clicking “Go to Updates” from the Updates widget would take you to a 404, if the available update info wasn’t already cached before loading the Dashboard. ([#1882](https://github.com/craftcms/cms/issues/1882))
- Fixed a SQL error that could occur when querying for users using the `lastLoginDate` param. ([#1886](https://github.com/craftcms/cms/issues/1886))
- Fixed a bug where custom field methods on element queries weren’t returning a reference to the element query object. ([#1887](https://github.com/craftcms/cms/issues/1887))
- Fixed a PHP error that could occur if a TypeError or other non-Exception error occurred when running the updater, masking the original error.
- Fixed a bug where `craft\web\Request::getQueryStringWithoutPath()` was including route params in addition to query string params. ([#1891](https://github.com/craftcms/cms/issues/1891))
- Fixed a PHP error that occurred if a volume with overridden config settings in `config/volumes.php` was missing its type. ([#1899](https://github.com/craftcms/cms/issues/1899))
- Fixed a bug where `craft\helpers\StringHelper::removeRight()` was actually removing the substring if it existed as a prefix, rather than a suffix. ([#1900](https://github.com/craftcms/cms/pull/1900))
- Fixed a PHP error that occurred if `config/routes.php` specified any site-specific routes that didn’t target the currently-requested site.
- Fixed lots of bugs with the Deprecated panel in the Debug Toolbar.
- Fixed a 404 error for `bootstrap.js` that occurred when the Debug Toolbar was opened.
- Fixed some deprecation errors caused by relational and Matrix field inputs.
- Fixed a bug where a plugin would get a PHP error if it tried to get the current site in the middle of a Craft update.
- Fixed a bug where the Migrations utility would display an error message even if the migrations were applied successfully. ([#1911](https://github.com/craftcms/cms/issues/1911))
- Fixed a PHP error that occurred if calling `getMimeType()` on an asset with an extension with an unknown MIME type. ([#1919](https://github.com/craftcms/cms/pull/1919))

## 3.0.0-beta.23 - 2017-07-28

### Added
- Added the `init` event to `craft\base\Component`, giving plugins a chance to attach custom Behaviors to various Craft components. ([#1856](https://github.com/craftcms/cms/pull/1856))
- Added the `init` event to `craft\web\twig\variables\CraftVariable`, giving plugins a chance to attach custom Behaviors and Components to the global `craft` template variable (replacing the now-deprecated `defineBehaviors` and `defineComponents` events). ([#1856](https://github.com/craftcms/cms/pull/1856))

### Changed
- Renamed the `afterInit` event on `craft\base\ApplicationTrait` to `init`. ([#1856](https://github.com/craftcms/cms/pull/1856))
- During a database backup, Craft will now default to excluding data from `assetindexdata`, `assettransformindex`, `cache`, `sessions`, `templatecaches`, `templatecachecriteria`, and `templatecacheelements` tables.
- Craft no longer saves new entry versions every time an entry is saved, regardless of how/why it’s being saved. Now they are only created when saving via the `entries/save-entry` action.
- Craft is no longer reliant on asset-packagist.org or `fxp/composer-asset-plugin` for installing front-end dependencies.
- Updated D3 to 4.10.0.
- Updated selectize.js to 0.12.4.
- Updated XRegExp to 3.2.0.
- The APC cache class will now only use the APCu library. ([#1867](https://github.com/craftcms/cms/issues/1867))
- The `users/save-user` action’s JSON response now has an `errors` key with all the validation errors, if any. ([#1860](https://github.com/craftcms/cms/pull/1860))
- Fixed a bug where parse errors in files that got loaded when Craft was determining the current user would not get reported, and redirect the browser to the login page. ([#1858](https://github.com/craftcms/cms/issues/1858))
- Fixed a bug where an `InvalidParamException` was thrown if a front-end login form was submitted without a password. ([#1857](https://github.com/craftcms/cms/issues/1857))
- Background tasks’ labels in the Control Panel sidebar now get truncated rather than taking up multiple lines.

### Deprecated
- Deprecated the `defineBehaviors` and `defineComponents` variables on `craft\web\twig\variables\CraftVariable`. Use the new `init` event instead. ([#1856](https://github.com/craftcms/cms/pull/1856))

### Removed
- Removed `craft\config\ApcConfig`.

### Fixed
- Fixed a JavaScript error that would occur when choosing a user whom another (soon-to-be-deleted) user’s content should be transfered to. ([#1837](https://github.com/craftcms/cms/issues/1837))
- Fixed a Twig error that would occur when saving a Matrix field if there were any validation errors.
- Fixed a PHP error that could occur if two Matrix blocks (either in the same Matrix field or across multiple Matrix fields) had sub-fields with identical handles, but different casings.
- Fixed a bug where listeners to the `selectElements` JavaScript event for Categories fields weren’t getting passed the list of new categories.
- Fixed a bug where previously-selected categories could become unselectable within a Categories field after they had been replaced. ([#1846](https://github.com/craftcms/cms/issues/1846))
- Fixed a bug where Craft would send an activation email when an admin registered a new user even if the “Send an activation email now?” checkbox was unchecked.
- Fixed a SQL error that would occur when saving a user if no user groups were checked. ([#1849](https://github.com/craftcms/cms/issues/1849))
- Fixed a bug where the background tasks HUD would show duplicate tasks when closed and reopened repeatedly without reloading the page. ([#1850](https://github.com/craftcms/cms/issues/1850))
- Fixed a bug where required relational fields weren’t getting validation errors if they were left blank. ([#1851](https://github.com/craftcms/cms/issues/1851))
- Fixed a SQL error that could occur when using the `{% cache %}` tag on sites with a large number of custom fields. ([#1862](https://github.com/craftcms/cms/issues/1862))
- Fixed a bug where variables set with `craft\web\UrlManager::setRouteParams()` weren’t available to the resulting template, unless nestled inside a `variables` key. ([#1857](https://github.com/craftcms/cms/issues/1857))
- Fixed a Twig error that could occur if a `null` value was passed to the `|t` filter. ([#1864](https://github.com/craftcms/cms/issues/1864))
- Fixed a PHP error that would occur if a content migration created a new field or renamed an existing field’s handle, and then attempted to reference that field in the same request. ([#1865](https://github.com/craftcms/cms/issues/1865))
- Fixed a bug where the Customize Sources Modal would always select the first asset volume’s source by default if a subfolder had been selected when opening the modal. ([#1871](https://github.com/craftcms/cms/issues/1871))
- Fixed a bug where uploaded Assets would have their width and height set to `null` on upload  on multi-site installs. ([#1872](https://github.com/craftcms/cms/issues/1872))
- Fixed a bug where cached versions of cloud images would not be saved when indexing for files that weren't already indexed.
- Fixed a JavaScript error that prevented dialog prompts in Assets manager from being displayed correctly.
- Fixed a bug on multi-site installs where relational fields wouldn’t save related elements that were disabled (either globally for a specific site site). ([#1854](https://github.com/craftcms/cms/issues/1854))

## 3.0.0-beta.22 - 2017-07-14

### Added
- Added support for asset reference tags that explicitly target an image transform (e.g. `{asset:123:transform:thumbnail}`).
- Added `craft\elements\Asset::copyWithTransform()`.
- Added the `beforeUpdateSlugAndUri` and `afterUpdateSlugAndUri` events to `craft\services\Elements::updateElementSlugAndUri()`. ([#1828](https://github.com/craftcms/cms/issues/1828))

### Changed
- HTMLPurifier no longer removes the following HTML5 elements by default: `<address>`, `<hgroup>`, `<figure>`, `<figcaption>`, `<s>`, `<var>`, `<sub>`, `<sup>`, `<mark>`, `<wbr>`, `<ins>`, `<del>`.
- HTMLPurifier no longer sets missing `alt` attributes to the image filename by default.
- `craft\db\elements\ElementQuery::one()` and `nth()` now consistently return `false` rather than `null` when no matching element is found.
- Editable table fields in the Control Panel can now specify unique `select` column options on a per-cell basis, by setting the cell’s value to an object with `options` and `value` keys.
- Header cells within editable table fields in the Control Panel can now specify their `<th>` class names.
- `craft\helpers\Localization::normalizeNumber()` now has an optional `$localeId` argument.
- `craft\services\Dashboard::deleteWidget()` now triggers the `beforeDeleteWidget` event and calls `beforeDelete()` on the widget at the top of the function.
- `craft\services\Dashboard::saveWidget()` now triggers the `beforeSaveWidget` event and calls `beforeSave()` on the widget at the top of the function.
- `craft\services\Elements::deleteElement()` now triggers the `beforeDeleteElement` event and calls `beforeDelete()` on the element at the top of the function.
- `craft\services\Elements::saveElement()` now triggers the `beforeSaveElement` event and calls `beforeSave()` on the element at the top of the function.
- `craft\services\Elements::deleteFields()` now triggers the `beforeDeleteField` event and calls `beforeDelete()` on the field at the top of the function.
- `craft\services\Elements::saveFields()` now triggers the `beforeSaveField` event and calls `beforeSave()` on the field at the top of the function.
- `craft\services\Elements::deleteTasks()` now triggers the `beforeDeleteTask` event and calls `beforeDelete()` on the task at the top of the function.
- `craft\services\Elements::saveTasks()` now triggers the `beforeSaveTask` event and calls `beforeSave()` on the task at the top of the function.
- `craft\services\Elements::deleteVolumes()` now triggers the `beforeDeleteVolume` event and calls `beforeDelete()` on the volume at the top of the function.
- `craft\services\Elements::saveVolumes()` now triggers the `beforeSaveVolume` event and calls `beforeSave()` on the volume at the top of the function.
- Craft no longer logs caught `craft\db\QueryAbortedException` exceptions. ([#1827](https://github.com/craftcms/cms/issues/1827))
- Renamed `craft\services\User::updateUserLoginInfo()` to `handleValidLogin()`.

### Fixed
- Fixed an issue where non-admins were not able to download assets using the element action. ([#1829](https://github.com/craftcms/cms/issues/1829))
- Fixed a bug where Assets fields were only enforcing their “Restrict allowed file types?” settings when uploading brand new files.
- Fixed a bug where elements’ custom fields were not getting validated on save if the `fieldLayoutId` had not been set beforehand.
- Fixed a bug where transformed images within Rich Text fields would break if the transform handle was identical to a custom field handle. ([#1804](https://github.com/craftcms/cms/issues/1804))
- Fixed a bug where some SVG file dimensions could not be detected.
- Fixed a bug where some field type data could become corrupted in various ways when propagating field values to different sites. ([#1833](https://github.com/craftcms/cms/issues/1833))
- Fixed a bug where the settings for new fields being created within an existing Matrix field would not show the Translation Method setting when they should. ([#1834](https://github.com/craftcms/cms/issues/1834))

## 3.0.0-beta.21 - 2017-07-07

### Fixed
- Fixed a PHP error that would occur if a migration failed to be reverted.
- Fixed a PHP error that would occur if the Yii Debug extension was installed at v2.0.9. ([#1823](https://github.com/craftcms/cms/issues/1823))

## 3.0.0-beta.20 - 2017-07-07 [CRITICAL]

> {note} There are a few breaking changes in this release. See the [Beta 20 Update Guide](https://github.com/craftcms/cms/wiki/Beta-20--Update-Guide) for update instructions.

### Added
- Added some “Update” buttons to the Updates utility (for Craft _and_ plugins). If multiple updates are available, an “Update all” button even shows up.
- The Updater has been rewritten to use Composer under the hood, and now requires significantly less (if any) site downtime. ([#1790](https://github.com/craftcms/cms/issues/1790))
- It’s now possible to apply both Craft and plugin migrations at the same time after a manual update. ([#1506](https://github.com/craftcms/cms/issues/1506))
- Migration exceptions are now displayed in the Updater. ([#1197](https://github.com/craftcms/cms/issues/1197))
- Added the `app/migrate` action (replacing `update/run-pending-migrations`), which runs any new Craft, plugin, and content migrations.
- Added the `|duration` Twig filter, which converts a `DateInterval` object into a human-readable duration.
- Added `craft\controllers\UpdaterController`.
- Added `craft\errors\MigrateException`.
- Added `craft\errors\MigrationException`.
- Added `craft\events\DefineBehaviorsEvent`.
- Added `craft\events\DefineComponentsEvent`.
- Added `craft\services\Composer`, available via `Craft::$app->composer` or `Craft::$app->getComposer()`.
- Added `craft\config\GeneralConfig::getBackupOnUpdates()`.
- Added `craft\base\Plugin::getHandle()`, as an alias for `Plugin::$id`.
- Added `craft\base\PluginInterface::getMigrator()` (previously already included in `craft\base\Plugin`).
- Added `craft\base\PluginTrait::$developerEmail`. When a plugin’s migration fails, the “Send for help” button will link to this email, if set.
- Added `craft\helpers\ArrayHelper::firstValue()`.
- Added `craft\helpers\ConfigHelper::sizeInBytes()`.
- Added `craft\services\Fields::getCompatibleFieldTypes()`.
- Added `craft\services\Path::getCompiledClassesPath()`.
- Added `craft\services\Updates::getPendingMigrationHandles()`.
- Added `craft\services\Updates::runMigrations()`.
- Added the `defineBehaviors` event to `craft\web\twig\variables\CraftVariable`, which can be used to register new behaviors on the `craft` template variable.
- Added the `defineComponents` event to `craft\web\twig\variables\CraftVariable`, which can be used to register new services on the `craft` template variable.
- Added the `beforeRenderTemplate`, `afterRenderTemplate`, `beforeRenderPageTemplate`, and `afterRenderPageTemplate` events to `craft\web\View`.
- Added Composer as a dependency.

### Changed
- Plugin handles must be `kebab-cased` now, rather than `camelCased`. ([#1733](https://github.com/craftcms/cms/issues/1733))
- Plugin module IDs are now set to the exact same value as their handles, as handles are already in the correct format now.
- The `maxUploadFileSize` config setting can now be set to a [shorthand byte value](http://php.net/manual/en/faq.using.php#faq.using.shorthandbytes) ending in `K` (Kilobytes), `M` (Megabytes), or `G` (Gigabytes).
- The `allowAutoUpdates` config setting applies to plugins too now.
- Matrix fields’ nested Field Type settings now take field compatibility into account, like the main Field Type setting. ([#1773](https://github.com/craftcms/cms/issues/1773))
- The DOM PHP extension is now a mandatory requirement.
- The `url` database config setting now supports `postgres://` and `postgresql://` schemes, in addition to `pgsql://`. ([#1774](https://github.com/craftcms/cms/pull/1774))
- Plugin changelogs can now use dots in release date formats (e.g. `2017.05.28`).
- Plugin changelogs can now have additional text before the version number in release headings (e.g. the plugin name).
- Plugin changelogs can now contain warnings (e.g. `> {warning} Some warning!`).
- Renamed `craft\services\Updates::getIsBreakpointUpdateNeeded()` to `getWasCraftBreakpointSkipped()`.
- Renamed `craft\services\Updates::getIsSchemaVersionCompatible()` to `getIsCraftSchemaVersionCompatible()`.
- Added a `$withContent` argument to `craft\services\EntryRevisions::getDraftsByEntryId()` and `getVersionsByEntryId()` (defaults to `false`). ([#1755](https://github.com/craftcms/cms/issues/1755))
- Craft now lists `craftcms/plugin-installer` as a dependency, so projects don’t need to explicitly require it.
- `craft\db\Migration::up()` and `down()` now have a `$throwExceptions` argument (defaults to `false`).
- `craft\db\MigrationManager::up()`, `down()`, `migrateUp()`, and `migrateDown()` now throw a `craft\errors\MigrationException` if a migration fails rather than returning `true`/`false`.
- The `app/check-for-updates` action now checks for a `includeDetails` param, which tells it to include the full update details in its response.
- It’s no longer possible to run new migrations while Craft is in Maintenance Mode, preventing the possibility of two people running migrations at the same time.
- It’s no longer needed to set the `$fieldLayoutId` property on users, tags, Matrix blocks, entries, or categories when creating them programmatically. ([#1756](https://github.com/craftcms/cms/issues/1756))
- Improved the accuracy of deprecation errors.
- Panes within panes in the Control Panel now have special styling.
- Craft now prioritizes Composer’s autoloader over Yii’s for faster class loading.
- Renamed the `categorygroups_i18n`, `elements_i18n`, and `sections_i18n` tables to `*_sites`. ([#1791](https://github.com/craftcms/cms/issues/1791))
- Updated [php-shellcommand](https://github.com/mikehaertl/php-shellcommand) to 1.2.5. ([#1788](https://github.com/craftcms/cms/issues/1788)).

### Removed
- Removed support for manually-installed plugins in a `plugins/` folder. ([#1734](https://github.com/craftcms/cms/issues/1734))
- Removed the `restoreOnUpdateFailure` config setting.
- Removed the `@plugins` Yii alias.
- Removed the `plugins/disable-plugin` action.
- Removed the `plugins/enable-plugin` action.
- Removed the `blx` global template variable.
- Removed the dynamically-compiled `craft\behaviors\ContentTrait` and `craft\behaviors\ElementQueryTrait` traits.
- Removed `craft\base\ApplicationTrait::getIsUpdating()`.
- Removed `craft\base\Plugin::afterUpdate()`.
- Removed `craft\base\Plugin::beforeUpdate()`.
- Removed `craft\base\Plugin::defineTemplateComponent()`. Plugins should use the new `defineComponents` or `defineBehaviors` events on `craft\web\twig\variables\CraftVariable` instead. ([#1733](https://github.com/craftcms/cms/issues/1733))
- Removed `craft\base\Plugin::update()`.
- Removed `craft\behaviors\FieldLayoutTrait`.
- Removed `craft\controllers\UpdateController`.
- Removed `craft\db\NestedSetsQueryTrait`.
- Removed `craft\db\NestedSetsTrait`.
- Removed `craft\errors\UnpackPackageException`.
- Removed `craft\errors\UpdateValidationException`.
- Removed `craft\events\UpdateEvent`.
- Removed `craft\helpers\App::isComposerInstall()`.
- Removed `craft\helpers\App::phpConfigValueInBytes()`. Use `craft\helpers\ConfigHelper::sizeInBytes()` instead.
- Removed `craft\helpers\Update`.
- Removed `craft\updates\Updater`.
- Removed `craft\services\Path::getAppPath()`.
- Removed `craft\services\Path::getPluginsPath()`.
- Removed `craft\services\Plugins::disablePlugin()`.
- Removed `craft\services\Plugins::enablePlugin()`.
- Removed `craft\services\Plugins::getConfig()`.
- Removed `craft\services\Plugins::getPluginByModuleId()`.
- Removed `craft\services\Plugins::isComposerInstall()`.
- Removed `craft\services\Plugins::validateConfig()`.
- Removed `craft\services\Updates::backupDatabase()`
- Removed `craft\services\Updates::backupFiles()`.
- Removed `craft\services\Updates::criticalCraftUpdateAvailable()`.
- Removed `craft\services\Updates::criticalPluginUpdateAvailable()`.
- Removed `craft\services\Updates::getIsManualUpdateRequired()`.
- Removed `craft\services\Updates::getPluginsThatNeedDbUpdate()`.
- Removed `craft\services\Updates::getUnwritableFolders()`.
- Removed `craft\services\Updates::prepareUpdate()`.
- Removed `craft\services\Updates::processUpdateDownload()`.
- Removed `craft\services\Updates::rollbackUpdate()`.
- Removed `craft\services\Updates::updateCleanUp()`.
- Removed `craft\services\Updates::updateDatabase()`.
- Removed `craft\services\Updates::updateFiles()`.
- Removed `craft\services\View::getRenderingTemplate()`.
- Removed the `beforeDisablePlugin`, `afterDisablePlugin`, `beforeEnablePlugin`, and `afterEnablePlugin` events from `craft\services\Plugins`.
- Removed the `beforeUpdate`, `afterUpdate`, and `updateFailure` events from `craft\services\Updates`.

### Fixed
- Fixed an exception that occurred when attempting to change an entry’s type from the Edit Entry page. ([#1748](https://github.com/craftcms/cms/pull/1748))
- Fixed a deprecation error on the Edit Entry page. ([#1749](https://github.com/craftcms/cms/issues/1749))
- Fixed a PHP error caused by the default Memcached config. ([#1751](https://github.com/craftcms/cms/issues/1751))
- Fixed a SQL error caused by the default `DbCacheConfig->cacheTableName` setting.
- Fixed a bug where a PHP session would be started on every template rendering request whether it was needed or not. ([#1765](https://github.com/craftcms/cms/issues/1765))
- Fixed a bug where you would get a PostgreSQL error when saving large amounts of data in a textual field. ([#1768](https://github.com/craftcms/cms/issues/1768))
- Fixed a bug where you would get a PHP error in `services/Feeds->getFeedItems()` when trying to parse an RSS feed that had no publish date. ([#1770](https://github.com/craftcms/cms/issues/1770))
- Fixed a PHP error that occurred when PHP’s `memory_limit` setting was set to something greater than `PHP_INT_MAX` when represented in bytes. ([#1771](https://github.com/craftcms/cms/issues/1771))
- Fixed a bug where adding a new site did not update any existing category groups with the new site’s category URI format and template settings.
- Fixed a PHP error that occurred when calling `craft\elements\Asset::getWidth()` if the `$transform` argument was anything other than a string. ([#1796](https://github.com/craftcms/cms/issues/1796))
- Fixed a bug where the Updates utility would spin indefinitely for users that didn’t have permission to perform updates. ([#1719](https://github.com/craftcms/cms/issues/1719))
- Fixed a SQL error that occurred when editing a non-admin user.
- Fixed a bug where attempting to log in with a user account that doesn’t have a password yet would fail silently.
- Fixed a Twig error that occurred when editing a suspended user.
- Fixed a bug where Matrix blocks were being saved an excessive amount of times when saving the owner element, and potentially resulting in errors when enabling a section for a new site.
- Fixed a bug where `craft\services\Updates::getIsCriticalUpdateAvailable()` wasn’t returning `true` if a plugin had a critical update available, according to its changelog.
- Fixed a bug where the PostgreSQL `upsert` method would only take into account a table’s primary keys instead of the passed in keys when deciding whether to insert or update. ([#1814](https://github.com/craftcms/cms/issues/1814))
- Fixed a SQL error that could occur when calling `count()` on an element query.
- Fixed a SQL error that could occur when saving an element with a Matrix field on a site using PostgreSQL, if the Matrix field’s handle had been renamed. ([#1810](https://github.com/craftcms/cms/issues/1810))
- Fixed a bug where assets with a transform applied via `setTransform()` were still returning their original dimensions via their `width` and `height` properties. ([#1817](https://github.com/craftcms/cms/issues/1817))
- Fixed a SQL error that occurred when updating to Craft 3 if there was a `CRAFT_LOCALE` constant defined in `index.php`. ([#1798](https://github.com/craftcms/cms/issues/1798))
- Fixed a bug where querying for Matrix blocks by block type handles that didn’t exist would still return results. ([#1819](https://github.com/craftcms/cms/issues/1819))
- Fixed a bug where Matrix fields were showing disabled blocks on the front end. ([#1786](https://github.com/craftcms/cms/issues/1786))
- Fixed a PHP error that occurred when using an earlier version of PHP than 7.0.10. ([#1750](https://github.com/craftcms/cms/issues/1750))
- Fixed a bug where routes created in the Control Panel which included tokens weren’t working. ([#1741](https://github.com/craftcms/cms/issues/1741))
- Fixed a bug where only admin users were allowed to perform some asset actions. ([#1821](https://github.com/craftcms/cms/issues/1821))

## 3.0.0-beta.19 - 2017-05-31

### Added
- Added the `beforeHandleException` event to `craft\web\ErrorHandler`. ([#1738](https://github.com/craftcms/cms/issues/1738))

### Changed
- The image editor now loads a higher-resolution image when the image viewport size has increased significantly.
- `craft\db\Migration::addPrimaryKey()`, `addForeignKey()`, and `createIndex` now automatically generate the key/index name if `$name` is `null`.
- Removed the deprecated global `user` template variable. ([#1744](https://github.com/craftcms/cms/issues/1744))
- Updated Yii to 2.0.12.

### Fixed
- Fixed a bug where entries and categories created from element selection modals weren’t getting a field layout ID assigned to them. ([#1725](https://github.com/craftcms/cms/issues/1725))
- Fixed a 403 error that occurred when a non-Admin attempted to edit a Category on a single-site install. ([#1729](https://github.com/craftcms/cms/issues/1729))
- Fixed a bug where plugin index templates weren’t getting resolved without appending an `/index` to the end of the template path.
- Fixed a PHP error that occurred when saving an element with a Number field, if using a locale with a non-US number format. ([#1739](https://github.com/craftcms/cms/issues/1739))
- Fixed a bug where a plugin’s control panel nav item was not having it’s `subnav` rendered within the control panel navigation.
- Fixed a bug where `craft\web\View::head()`, `beginBody()`, and `endBody()` were getting called for non-“page” templates that contained `<head>` and/or `<body>` tags. ([#1742](https://github.com/craftcms/cms/issues/1742))
- Fixed a bug where singles were forgetting their field layouts when their section settings were re-saved. ([#1743](https://github.com/craftcms/cms/issues/1743))

## 3.0.0-beta.18 - 2017-05-19

### Added
- It’s now possible to assign custom fields to users on Craft Personal and Client editions.
- Added `craft\elements\db\ElementQuery::getCriteria()`.
- Added `craft\elements\db\ElementQuery::criteriaAttributes()`.

### Changed
- The image editor now matches the screen resolution when displaying images.
- The image editor now shows a loading spinner when loading images.
- Improved focal point positioning reliability in the image editor.
- It’s now possible to specify recipient names in the `testToEmailAddress` setting value. ([#1711](https://github.com/craftcms/cms/pull/1711)

### Removed
- Removed `craft\helpers\App::isPhpDevServer()`.

### Fixed
- Fixed a bug where Craft was not removing leading/trailing/double slashes when parsing element URI formats. ([#1707](https://github.com/craftcms/cms/issues/1707))
- Fixed a bug where emails sent from the “Test” button on Settings → Email were not including the settings values being tested.
- Fixed a PHP error that occurred when saving plugin settings without any post data.
- Fixed a regression where the `testToEmailAddress` config setting did not work with an array of email addresses. ([#1711](https://github.com/craftcms/cms/pull/1711)
- Fixed PHP errors that occurred if `craft\fields\Matrix::normalizeValue()` or `craft\fields\BaseRelationField::normalizeValue()` were ever called without passing an element.
- Fixed a bug where the Quick Post widget was not calling fields’ `normalizeValue()` methods.
- Fixed a bug where Matrix blocks were not returning the posted field values in Live Preview if they were accessed as an array. ([#1710](https://github.com/craftcms/cms/issues/1710))
- Fixed a bug where it was not possible to set the User Photo Volume on Craft Personal and Client editions. ([#1717](https://github.com/craftcms/cms/issues/1717))
- Fixed a bug where changing a named image transform’s dimensions was not invalidating existing transformed images.
- Fixed an error that occurred when applying an image transform without a weight. ([#1713](https://github.com/craftcms/cms/issues/1713))
- Really fixed a bug where Panes’ sidebar could get a wrong height when scrolling down. ([#1364](https://github.com/craftcms/cms/issues/1364))

## 3.0.0-beta.17 - 2017-05-13

### Fixed
- Fixed a bug that broke template loading for template paths with uppercase letters on case-sensitive file systems. ([#1706](https://github.com/craftcms/cms/issues/1706))
- Fixed a deprecation error caused by the Craft Support widget. ([#1708](https://github.com/craftcms/cms/issues/1708))

## 3.0.0-beta.16 - 2017-05-13

### Fixed
- Fixed a PHP error that occurred when editing elements if a Checkboxes/Multi-select field was converted to a Dropdown/Radio Buttons field. ([#1701](https://github.com/craftcms/cms/issues/1701))
- Fixed a bug where entry URIs weren’t getting updated after re-saving a section with a new Entry URI Format. ([#1705](https://github.com/craftcms/cms/issues/1705))

## 3.0.0-beta.15 - 2017-05-12

### Added
- Added `craft\events\getAssetUrlEvent` which plugins can use to modify the URL of an Asset being fetched.
- Added the `registerCpTemplateRoots` event to `craft\web\View`, making it possible for non-plugins to register CP template root paths/directories.
- Added `craft\events\RegisterTemplateRootsEvent`.
- Added `craft\web\View::getCpTemplateRoots()`.

### Changed
- The Field Layout Designer is now using the default font instead of the Coming Soon font. ([#1537](https://github.com/craftcms/cms/issues/1537))
- Updated Stringy to 3.0.
- Improved focal point tracking in Image editor when dealing with scaled images.

### Fixed
- Fixed a PHP error that occurred when creating a new user.
- Fixed a 403 error that occurred when a non-Admin attempted to edit a Global Set on a single-site install. ([#1687](https://github.com/craftcms/cms/issues/1687))
- Fixed a bug where JS scripts registered from plugin settings pages weren’t getting properly namespaced, so generally didn’t work. ([#1691](https://github.com/craftcms/cms/issues/1691))
- Fixed a bug where some locales were always showing two-digit day/month numbers in formatted dates (e.g. `01/05`).
- Fixed a bug where form-submitted date/time values were always being treated as US-formatted dates/times, if the Intl extension wasn’t enabled. ([#1495](https://github.com/craftcms/cms/issues/1495))
- Fixed a bug where it was possible to break UI in Image editor with triggering crop mode twice.

## 3.0.0-beta.14 - 2017-05-02

### Added
- Added an aspect ratio constraint menu to the Crop tool in the Image Editor.
- Added the `postLogoutRedirect` config setting, making it possible to customize where users should be redirected to after logging out from the front-end. ([#1003](https://github.com/craftcms/cms/issues/1003))
- Added the `currentSite` global template variable.
- Added the `registerRedactorPlugin` event to `craft\fields\RichText`, which plugins can listen to if they supply any Redactor plugins that may need be registered on the page.
- Added `craft\base\FieldInterface::isEmpty()`, which gives field types a chance to determine whether their value should be considered empty for validators.
- Added `craft\base\VolumeInterface::createDir()`.
- Added `craft\base\VolumeInterface::deleteDir()`.
- Added `craft\base\VolumeInterface::renameDir()`.
- Added `craft\base\Volume::folderExists()`.
- Added `craft\base\Volume::createDir()`.
- Added `craft\base\Volume::deleteDir()`.
- Added `craft\base\Volume::renameDir()`.
- Added `craft\config\GeneralConfig::getPostCpLoginRedirect()`.
- Added `craft\config\GeneralConfig::getPostLoginRedirect()`.
- Added `craft\config\GeneralConfig::getPostLogoutRedirect()`.
- Added `craft\db\Query::getRawSql()`, as a shortcut for `createCommand()->getRawSql()`.
- Added `craft\helpers\DateTimeHelper::timeZoneAbbreviation()`.
- Added `craft\helpers\DateTimeHelper::timeZoneOffset()`.
- Added `craft\services\Images::getSupportedImageFormats()`.
- Added `craft\web\View::getIsRenderingPageTemplate()`. ([#1652](https://github.com/craftcms/cms/pull/1652))
- Added `webp` as a web-safe image format.
- Added SVG file support for image editor.

### Changed
- Craft’s `composer.json` no longer specifies server requirements (so the `--ignore-platform-reqs` flag is no longer necessary).
- Loosened Craft’s dependency requirements to allow build updates without explicitly changing `composer.json`.
- Updated Stringy to 2.4.
- Updated Twig to 2.3.
- Updated zend-feed to 2.8.
- Updated D3 to 4.8.
- Updated d3-format to 1.2.
- Updated Velocity to 1.5.
- Updated Fabric to 1.13.
- Plugin classes’ global instances are now registered from `craft\base\Plugin::init()`, so `Plugin::getInstance()` can be called as early as plugins’ `init()` methods, once they’ve called `parent::init()`. ([#1641](https://github.com/craftcms/cms/issues/1641))
- Craft now supports reference tags that begin with the fully qualified element class name.
- Rich Text fields no longer parse reference tags that aren’t within a `href` or `src` attribute when displaying their form input, so the tags don’t get lost when the element is re-saved. ([#1643](https://github.com/craftcms/cms/issues/1643))
- `craft\helpers\ConfigHelper::localizedValue()` now accepts a PHP callable value for `$value`.
- The following config settings can now be set to a PHP callable, which returns the desired value at runtime: `activateAccountSuccessPath`, `invalidUserTokenPath`, `loginPath`, `logoutPath`, `postCpLoginRedirect`, `postLoginRedirect`, `postLogoutRedirect`, `setPasswordPath`, and `setPasswordSuccessPath`.
- There’s no more special treatment for volume types that have better support for subfolders.
- Renamed `craft\helpers\Image::isImageManipulatable()` to `canManipulateAsImage()`.
- Craft now checks if the current installation can manipulate an image instead of checking against a predefined list. ([#1648](https://github.com/craftcms/cms/issues/1648), [#1545](https://github.com/craftcms/cms/issues/1545))
- The old `Craft\DateTime` methods from Craft 2 no longer cause PHP errors when called from a template. A deprecation error will be logged instead.
- `craft\helpers\FileHelper::clearDirectory()` now supports `filter`, `except`, and `only` options.
- Craft now deletes outdated resource files when newer ones are published. ([#1670](https://github.com/craftcms/cms/issues/1670))

### Removed
- Removed `craft\base\Field::isValueEmpty()`.
- Removed `craft\base\FolderVolumeInterface`.
- Removed `craft\base\FolderVolume`.

### Fixed
- Fixed some JavaScript errors that could occur when expanding the Debug toolbar from the Control Panel, due to CP JavaScript files getting loaded inside the Debug panel. ([#1639](https://github.com/craftcms/cms/issues/1639))
- Fixed a bug where Craft would sometimes upload an Asset file but fail to create an Asset.
- Fixed a bug where reference tags created automatically by Rich Text fields included the full element class name, rather than the element type’s reference handle. ([#1645](https://github.com/craftcms/cms/issues/1645))
- Fixed an error that displayed in the Control Panel after submitting  the Database Backup utility, if “Download backup?” was unchecked, even though the backup may have been created successfully. ([#1644](https://github.com/craftcms/cms/issues/1644))
- Fixed a bug where the Image Editor could have a white background.
- Fixed a bug where non-required Dropdown and Radio Buttons fields were getting validation errors when omitted from a front-end entry form.
- Fixed a bug where required Checkboxes, Dropdown, Multi-select, Radio Buttons, and Rich Text fields were not getting validation errors when submitted without a value.
- Fixed a bug where Assets fields weren’t enforcing their Limit settings during server-side validation.
- Fixed a bug where deleting folders on remote sources would not work in some cases.
- Fixed a bug where renaming a folder would sometimes leave a folder behind.
- Fixed a bug where creating a new Asset would not trigger the `beforeSave()` method for it's fields. ([#1623](https://github.com/craftcms/cms/issues/1623))
- Fixed a bug where it was impossible to set validation errors on elements that had no field layouts set. ([#1598](https://github.com/craftcms/cms/issues/1598))
- Fixed a bug where no error message was being displayed on failed uploads. ([#1598](https://github.com/craftcms/cms/issues/1598))
- Fixed a bug where the site image was getting resized to 500px instead of 300px. ([#1428](https://github.com/craftcms/cms/issues/1428))
- Fixed a bug where it was not possible to use the Assets Replace File element action.
- Fixed a bug where Asset resized versions would not be deleted if the extension had been changed during the resize.
- Fixed an error that occurred if a plugin’s Settings model tried calling `Plugin::getInstance()` or `Craft::t()` from its `init()` method.
- Fixed an error that occurred if the “Date Created” or “Date Updated” columns were selected to be shown on the Users index.
- Fixed a bug where element indexes weren’t remembering the selected site across page loads. ([#1653](https://github.com/craftcms/cms/issues/1653))
- Fixed a bug where Panes’ sidebar could get a wrong height when scrolling down. ([#1364](https://github.com/craftcms/cms/issues/1364))
- Fixed a PHP error that occurred when attempting to create a new field. ([#1683](https://github.com/craftcms/cms/issues/1683))

## 3.0.0-beta.13 - 2017-04-18

### Added
- Added support for adding new `join`s to element queries, via `craft\elements\db\ElementQuery::join()`, `innerJoin()`, `leftJoin()`, and `rightJoin()`.
- Added `craft\web\Request::getQueryParamsWithoutPath()`.

### Changed
- SQL fragments generated by the `QueryBuilder` classes are now separated by newlines, making the combined SQL easier to read.
- Renamed `craft\elements\db\ElementRelationParamParser::parseRelationParam()` to `parse()`, and it no longer accepts a `$query` argument.

### Removed
- Removed `craft\elements\db\ElementRelationParamParser::getIsRelationFieldQuery()`.
- Removed `craft\services\Users::getClient()`.

### Fixed
- Fixed a bug where Craft was not enforcing the “Require a password reset on next login” user setting. ([#1632](https://github.com/craftcms/cms/issues/1632))
- Fixed a bug where element queries could return duplicate results when using the `relatedTo` param. ([#1635](https://github.com/craftcms/cms/issues/1635))

## 3.0.0-beta.12 - 2017-04-14

### Fixed
- Fixed a bug where Checkboxes and Multi-select fields weren’t saving their values properly, or normalizing their values properly. ([#1619](https://github.com/craftcms/cms/issues/1619))

## 3.0.0-beta.11 - 2017-04-14

### Added
- Added `craft\web\View::registerScript()`, as a more generic way to register new `<script>` tags on the page than `registerJs()`. ([#1617](https://github.com/craftcms/cms/pull/1617))
- Added the `uploadParamName` setting to `Craft.ImageUpload`, which specifies the param name that should be used for file uploads. (Default is `files`.)

### Changed
- If `craft\web\View::$title` is set, a `<title>` tag will now automatically get injected into the page’s `<head>`. ([#1625](https://github.com/craftcms/cms/pull/1625))
- Craft no longer encodes 4-byte characters (like emojis) present in Rich Text fields and template caches, if using PostgreSQL.
- It’s no longer possible to disable plugins that were installed via Composer, since their classes are auto-loadable via Composer regardless, so the concept of “disabling” them is misleading. ([#1626](https://github.com/craftcms/cms/issues/1626))
- Renamed Rich Text fields’ `configFile` setting to `redactorConfig`, to avoid ambiguity with the new `purifierConfig` setting.

### Removed
- Removed support for referring to Redactor’s `source` plugin by its old name, `html`, within Redactor JSON configs.

### Fixed
- Fixed a PHP error that occurred when creating new elements with Matrix fields. ([#1610](https://github.com/craftcms/cms/issues/1610))
- Fixed a bug where it was impossible to upload user photos, site icon and site logo.
- Fixed an issue where Rich Text Field Asset modals would ignore the defined Volume order.
- Fixed a SQL error that occurred when saving an entry with a Rich Text field that contained a 4-byte character (like an emoji), if using MySQL. ([#1627](https://github.com/craftcms/cms/issues/1627))
- Fixed an `UnknownPropertyException` that could occur on Rich Text fields after upgrading from Craft 2.

## 3.0.0-beta.10 - 2017-04-07

### Added
- Added `craft\console\User::getId()`.
- Added `craft\controllers\ElementsController::actionGetElementHtml()`
- Added `craft\helpers\ArrayHelper::filterByValue()`.
- Added `craft\helpers\Console`.
- Added `craft\services\Elements::duplicateElement()`.
- Added the `|filterByValue` Twig filter.
- Added `Craft.selectFullValue()`.

### Changed
- `craft\image\Raster::rotate()` now requires a float parameter, instead of an integer.
- Rotating images by degree fractions in image editor is now possible only when using Imagick as the image driver.
- Improved the behavior of auto-generated text inputs (like Handle fields).
- The “Target Site” relational field setting has been redesigned as two-step process, for added clarity. ([#1499](https://github.com/craftcms/cms/issues/1499))
- The `beforeSaveGlobalSet` event on `craft\services\Globals` is no longer cancellable.
- `migrate` console actions now support `-t` and `-p` aliases for `--type` and `--plugin` options.
- Console requests now report if there are any database connection issues. ([#1580](https://github.com/craftcms/cms/issues/1580))
- `craft\elements\db\ElementQuery::one()` now returns the first cached element if the element query has any results cached on it.
- `craft\base\SavableComponent::settingsAttributes()` now returns all public, non-static properties on any parent classes in addition to the called class, so long as they weren’t defined in an abstract class.
- `craft\controllers\AssetsController::uploadFile()` now also returns `assetId` on a successful upload.
- Renamed `craft\base\Element::SCENARIO_SITE_PROPAGATION` to `SCENARIO_ESSENTIALS`.

### Removed
- Removed `craft\base\TaskInterface::getDescription()`.
- Removed `craft\db\Connection::DRIVER_MYSQL`. Use `craft\config\DbConfig::DRIVER_MYSQL` instead.
- Removed `craft\db\Connection::DRIVER_PGSQL`. Use `craft\config\DbConfig::DRIVER_PGSQL` instead.
- Removed `craft\helpers\StringHelper::ensureEndsWith()`. Use `ensureRight()` instead.
- Removed `craft\helpers\StringHelper::ensureStartsWith()`. Use `ensureLeft()` instead.

### Fixed
- Fixed an issue where renaming the current folder in Assets manager would break the URLs for currently loaded elements. ([#1474](https://github.com/craftcms/cms/issues/1474))
- Fixed an issue where focal point would not be tracked correctly under certain circumstances. ([#1305](https://github.com/craftcms/cms/issues/1305))
- Fixed an issue where image operations were being performed when saving an edited image without anything warranting them. ([#1329](https://github.com/craftcms/cms/issues/1329), [#1588](https://github.com/craftcms/cms/issues/1588))
- Fixed a bug where it was not possible to install plugins manually. ([#1572](https://github.com/craftcms/cms/issues/1572))
- Fixed a bug where tasks’ default descriptions were not showing up in the Control Panel, for tasks that weren’t created with a custom description.
- Fixed a PostgreSQL error that could occur if you were saving a large amount of data into a field that needed to be search indexed. ([#1589](https://github.com/craftcms/cms/issues/1589))
- Fixed a bug where focal point would not be updated when replacing an image with the image editor.
- Fixed a bug that broke the “Resaving all localizable elements” task after creating a new site, if there were any Matrix fields set to manage blocks on a per-site basis.
- Fixed a bug where only the initially-selected field type’s supported translation methods were being taken into account when populating the Translation Method setting options.
- Fixed an error that occurred on the Dashboard if there was a Feed widget without a Limit set. ([#1565](https://github.com/craftcms/cms/issues/1565))
- Fixed a PHP error that could occur after a task failure. ([#1567](https://github.com/craftcms/cms/issues/1567))
- Fixed a bug where the tip of the task info HUD would remain visible after the last task had been manually canceled. ([#1566](https://github.com/craftcms/cms/issues/1566))
- Fixed a PHP error that occurred when saving a new Structure section. ([#1573](https://github.com/craftcms/cms/issues/1573))
- Fixed a PHP error that would occur when creating a new entry without an author from a console controller. ([#1581](https://github.com/craftcms/cms/issues/1581))
- Fixed a SQL error that occurred when attempting to update to 3.0.0-beta.8 or later, on installs using PostgreSQL. ([#1586](https://github.com/craftcms/cms/issues/1586))
- Fixed a bug where newly-created global sets weren’t remembering their field layouts. ([#1582](https://github.com/craftcms/cms/issues/1582))
- Fixed a bug where Craft wasn’t invalidating OPcache after writing new auto-generated classes in `storage/runtime/compiled_classes/`. ([#1595](https://github.com/craftcms/cms/issues/1595))
- Fixed incorrectly named Asset permissions. ([#1602](https://github.com/craftcms/cms/issues/1602))
- Fixed a bug where calling `.one()` on a Matrix/relation field within a custom entry title format would not return the first *posted* matrix block/relation. ([#1597](https://github.com/craftcms/cms/issues/1597))
- Fixed a bug where the Recent Entries widget wasn’t displaying any entries for non-admin users on single-site Craft installs. ([#1601](https://github.com/craftcms/cms/issues/1601))
- Fixed a PHP error that would occur if Craft was not installed and you had the `CRAFT_SITE` constant defined in your public index.php file.  ([#1494](https://github.com/craftcms/cms/issues/1494))
- Fixed a bug where drag-and-drop uploading was not possible for Asset fields. ([#1604](https://github.com/craftcms/cms/issues/1604))
- Fixed a bug where it was not possible to trigger a prompt when uploading an Asset with a conflicting name.
- Fixed a bug where clicking “Save as a new entry” on an Edit Entry page, or “Save as a new category” on an Edit Category page, would only use the current site’s content, discarding the title and any translatable custom field values from other sites. ([#1523](https://github.com/craftcms/cms/issues/1523))

## 3.0.0-beta.9 - 2017-03-27

### Added
- Added `craft\services\Plugins::getPluginByClass()`.

### Fixed
- Fixed a PHP error that occurred on the Dashboard if there were any Quick Post widgets.
- Fixed a bug where there was no visible “Content” tab in the Field Layout Designer when creating a new global set.
- Fixed a PHP error that occurred when saving a new global set. ([#1570](https://github.com/craftcms/cms/issues/1570))
- Fixed a bug where clicking “Sign out” in the Control Panel would generally result in a 404 error. ([#1568](https://github.com/craftcms/cms/issues/1568))
- Fixed a bug where saving an Assets field without Volumes defined and then trying to use it would result in an exception. ([#1423](https://github.com/craftcms/cms/issues/1423))
- Fixed an issue where deleting an Asset volume would delete all of the physical files of Assets indexed.
- Fixed a bug where Craft could not connect to MySQL databases using the `unixSocket` setting.

## 3.0.0-beta.8 - 2017-03-24

### Added
- Added support for a `url` DB config setting, which can be set to a DB connection URL as provided by some PaaS solutions. ([#1317](https://github.com/craftcms/cms/issues/1317))
- Added `craft\base\FieldInterface::getIsTranslatable()`.
- Added `craft\base\FieldInterface::supportedTranslationMethods()`.
- Added `craft\base\FolderVolumeInterface::folderExists()`.
- Added `craft\base\Plugin::cpNavIconPath()`.
- Added `craft\base\PluginInterface::getCpNavItem()`.
- Added `craft\config\ApcConfig`.
- Added `craft\config\DbCacheConfig`.
- Added `craft\config\DbConfig`.
- Added `craft\config\FileCacheConfig`.
- Added `craft\config\GeneralConfig`.
- Added `craft\config\MemCacheConfig`.
- Added `craft\controllers\AssetsController::actionDeleteAsset()`.
- Added `craft\elements\Asset::$avoidFilenameConflicts`, which determines whether new files’ names should be automatically renamed to avoid conflicts with exiting files.
- Added `craft\elements\Asset::$conflictingFilename`, which stores a record of the attempted filename that ended up conflicting with an existing file.
- Added `craft\elements\Asset::$newFolderId`, which indicates an asset's new intended folder ID.
- Added `craft\elements\Asset::$newLocation`, which indicates an asset's new intended location. If null, it will be constructed from the `$newFolderId` and `$newFilename` properties.
- Added `craft\helpers\App::maxPowerCaptain()`.
- Added `craft\helpers\Assets::parseFileLocation()`.
- Added `craft\helpers\ConfigHelper`.
- Added `craft\helpers\DateTimeHelper::intervalToSeconds()`.
- Added `craft\helpers\DateTimeHelper::secondsToInterval()`.
- Added `craft\helpers\FileHelper::useFileLocks()`.
- Added `craft\helpers\UrlHelper::resourceTrigger()`.
- Added `craft\services\Config::getApc()`.
- Added `craft\services\Config::getConfigFromFile()`.
- Added `craft\services\Config::getDb()`.
- Added `craft\services\Config::getDbCache()`.
- Added `craft\services\Config::getFileCache()`.
- Added `craft\services\Config::getGeneral()`.
- Added `craft\services\Config::getMemCache()`.
- Added `craft\validators\AssetLocationValidator`.
- Added the `beforeHandleFile` event to `craft\elements\Asset`, which fires whenever a new file is getting uploaded, or an existing file is being moved/renamed.
- Added `Craft.registerElementEditorClass()` and the `Craft.createElementEditor()` factory function, making it possible to set element editor classes specific to an element type.
- Added `Craft.BaseElementSelectInput::createElementEditor()`, making it possible for subclasses to customize the settings passed to the element editor.
- Element indexes now have a `toolbarFixed` setting, which dictates whether the toolbar should be fixed when scrolling. ([#1504](https://github.com/craftcms/cms/issues/1504))
- Element indexes now have `refreshSourcesAction`, `updateElementsAction`, and `submitActionsAction` settings, which define the controller actions that various Ajax requests should be posted to. ([#1480](https://github.com/craftcms/cms/issues/1480))
- Added an `onAfterAction()` method to `Craft.BaseElementIndex`. ([#1534](https://github.com/craftcms/cms/issues/1534))
- Plugins can now define [sub-modules](http://www.yiiframework.com/doc-2.0/guide-structure-modules.html) via `extra.modules` in their `composer.json` file. ([#1559](https://github.com/craftcms/cms/issues/1559))
- Elements are now “hard-coded” with their field layout IDs, via a new `fieldLayoutId` column in the `elements` table and a `$fieldLayoutId` property on `craft\base\ElementTrait`. Plugins that provide custom element types should start making sure `$fieldLayoutId` is set on their elements before passing them to `Craft::$app->elements->saveElement()`.

### Changed
- Asset file operations have been refactored to work alongside asset element saving.
- `craft\controllers\AssetsController::actionMoveAsset()` now accepts a `force` param, rather than `userResponse`.
- `craft\controllers\AssetsController::actionMoveAsset()` now returns `conflict` and `suggestedFilename` keys in the event of a filename conflict, rather than `prompt`
- `craft\controllers\AssetsController::actionMoveFolder()` now accepts `force` and `merge` params, rather than `userResponse`.
- `craft\controllers\AssetsController::actionMoveFolder()` now returns a `conflict` key in the event of a filename conflict, rather than `prompt` and `foldername`.
- `craft\controllers\AssetsController::actionReplaceFile()` now accepts `sourceAssetId` and `targetFilename` params.
- `craft\controllers\AssetsController::actionSaveAsset()` no longer accepts `assetId` and `userResponse` params.
- `craft\controllers\AssetsController::actionSaveAsset()` now returns `conflict` and `conflictingAssetId` keys in the event of a filename conflict, rather than `prompt`.
- `craft\elements\Asset` now supports a `create` scenario that should be used when creating a new asset.
- `craft\elements\Asset` now supports a `fileOperations` scenario that should be used when an existing asset’s file is being moved around.
- `craft\elements\Asset` now supports a `index` scenario scenario that should be used when indexing an asset’s file.
- `craft\elements\Asset` now supports a `replace` scenario that should be used when replacing an asset’s file.
- `craft\helpers\Assets::editorImagePath()` was renamed to `getImageEditorSource()`.
- `craft\helpers\Assets::fileTransferList()` no longer accepts a `$merge` argument.
- `craft\services\Assets::createFolder()` now accepts an `$indexExisting` argument that determines whether unindexed folders on the volume should be silently indexed.
- `craft\services\Assets::getNameReplacementInFolder()` now combines the file lists on the volume and the asset index when figuring out a safe replacement filename to use.
- `craft\services\Assets::getNameReplacementInFolder()` now throws an `InvalidParamException` if `$folderId` is set to an invalid folder ID.
- `craft\services\Assets::moveAsset()` now accepts an instance of `craft\models\VolumeFolder` instead of a folder ID.
- `craft\services\Assets::moveAsset()` now returns a boolean value.
- The `cacheDuration`, `cooldownDuration`, `defaultTokenDuration`, `elevatedSessionDuration`, `invalidLoginWindowDuration`, `purgePendingUsersDuration`, `rememberUsernameDuration`, `rememberedUserSessionDuration`, `userSessionDuration`, and `verificationCodeDuration` config settings can now be set to an integer (number of seconds), string ([duration interval](https://en.wikipedia.org/wiki/ISO_8601#Durations)), or `DateInterval` object.
- Plugin config file values in `config/pluginhandle.php` are now merged with database-stored plugin settings, and applied to the plugin’s settings model. (Also removed support for plugin `config.php` files.) ([#1096](https://github.com/craftcms/cms/issues/1096))
- `craft\services\Config::getConfigSettings()` now only accepts a `$category` value of `apc`, `db`, `dbcache`, `filecache`, `general`, or `memcache`. (It no longer accepts plugin handles.)
- Renamed `craft\base\PluginTrait::$hasSettings` to `$hasCpSettings`.
- Removed support for automatically determining the values for the `omitScriptNameInUrls` and `usePathInfo` config settings.
- Removed support for `@web`, `@webroot`, and other aliases in volume settings, as they cause more problems than they solve in multi-site Craft installs.
- Local volumes’ “File System Path” settings can now begin with `@webroot`, which is an alias for the path to the directory that `index.php` lives in.
- `craft\base\Element::getFieldLayout()` now returns a field layout based on the `$fieldLayoutId` property (if set). It no longer returns the first field layout associated with the static element class.
- `craft\services\Fields::assembleLayoutFromPost()` now sets the ID on the returned field layout if the post data came from a Field Layout Designer for an existing field layout.
- `craft\services\Fields::saveLayout()` is now capable of updating existing field layouts, not just creating new ones. So there’s no longer a need to delete the old field layout and save a brand new one each time something changes.
- Field types that don’t support a column in the `content` table are no longer assumed to be untranslatable. If a field type wants to opt out of having a Translation Method setting, it should override its static `supportedTranslationMethods()` method and return either `['none']` or `['site']`, depending on whether its values should be propagated across other sites or not.

### Removed
- Removed the `beforeUploadAsset` event from `craft\services\Asset`.
- Removed `craft\base\ApplicationTrait::validateDbConfigFile()`.
- Removed `craft\elements\Asset::$indexInProgress`.
- Removed `craft\helpers\DateTimeHelper::timeFormatToSeconds()`.
- Removed `craft\services\Assets::renameFile()`.
- Removed `craft\services\Assets::saveAsset()`.
- Removed `craft\services\Config::allowAutoUpdates()`.
- Removed `craft\services\Config::exists()`. Use `isset(Craft::$app->config->general->configSetting)`.
- Removed `craft\services\Config::get()`. Use `Craft::$app->config->general`, et al.
- Removed `craft\services\Config::getAllowedFileExtensions()`. Use `Craft::$app->config->general->allowedFileExtensions`.
- Removed `craft\services\Config::getCacheDuration()`. Use `Craft::$app->config->general->cacheDuration`.
- Removed `craft\services\Config::getCpLoginPath()`. It’s `login`.
- Removed `craft\services\Config::getCpLogoutPath()`. It’s `logout`.
- Removed `craft\services\Config::getCpSetPasswordPath()`. It’s `setpassword`.
- Removed `craft\services\Config::getDbPort()`. Use `Craft::$app->config->db->port`.
- Removed `craft\services\Config::getDbTablePrefix()`. Use `Craft::$app->config->db->tablePrefix`.
- Removed `craft\services\Config::getElevatedSessionDuration()`. Use `Craft::$app->config->general->elevatedSessionDuration`.
- Removed `craft\services\Config::getLocalized()`. Use `Craft::$app->config->general->getLoginPath()`, et al.
- Removed `craft\services\Config::getLoginPath()`. Use `Craft::$app->config->general->getLoginPath()`.
- Removed `craft\services\Config::getLogoutPath()`. Use `Craft::$app->config->general->getLogoutPath()`.
- Removed `craft\services\Config::getOmitScriptNameInUrls()`. Use `Craft::$app->config->general->omitScriptNameInUrls`.
- Removed `craft\services\Config::getResourceTrigger()`. Use `craft\helpers\UrlHelper::resourceTrigger()`.
- Removed `craft\services\Config::getSetPasswordPath()`. Use `Craft::$app->config->general->getSetPasswordPath()`.
- Removed `craft\services\Config::getUseFileLocks()`. Use `craft\helpers\FileHelper::useFileLocks()`.
- Removed `craft\services\Config::getUsePathInfo()`. Use `Craft::$app->config->general->usePathInfo`.
- Removed `craft\services\Config::getUserSessionDuration()`. Use `Craft::$app->config->general->userSessionDuration`.
- Removed `craft\services\Config::isExtensionAllowed()`.
- Removed `craft\services\Config::maxPowerCaptain()`. Use `craft\helpers\App::maxPowerCaptain()`.
- Removed `craft\services\Config::set()`.
- Removed `craft\validators\AssetFilenameValidator`.
- Removed `Craft.showElementEditor()`.

### Fixed
- Fixed a bug where `Dashboard.js` would not load on case-sensitive file systems. ([#1500](https://github.com/craftcms/cms/issues/1500))
- Fixed a bug that would cause a SQL error on some Craft 2.6 to 3 updates.
- Fixed a bug where Craft’s stored field version would not update after saving/deleting a field in a non-global context.
- Fixed a PHP error that occurred when installing Craft, if the user settings had any validation errors.
- Fixed a bug where it was not possible to refresh element sources in element views.
- Fixed an exception that could occur when loading an entry with a stored version that didn’t have a valid entry type ID.
- Fixed a bug where Single entries weren’t getting their URIs or slugs updated when the section settings were re-saved. ([#1547](https://github.com/craftcms/cms/issues/1547))
- Fixed a bug where the `CRAFT_ENVIRONMENT` PHP constant wasn’t working. ([#1555](https://github.com/craftcms/cms/issues/1555))
- Fixed a bug where permissions were not checked prior to deleting an Asset via Element Action.
- Fixed a bug where Matrix and relational fields’ values were getting propagated across other sites, even if they were set to manage blocks/relations on a per-site basis. ([#1560](https://github.com/craftcms/cms/issues/1560))
- Fixed a PHP error that occurred on some console requests due to `craft\console\Application::getUser()` not existing. ([#1562](https://github.com/craftcms/cms/issues/1562))
- Fixed a bug where newly-created Matrix block type fields’ Instructions settings were limited to 64 characters. ([#1543](https://github.com/craftcms/cms/issues/1543))
- Fixed a bug that could prevent Craft from updating correctly in case of missing files.
- Fixed a PHP error that could occur when saving an element that says it has content, but doesn’t have a field layout.
- Fixed a bug where editing an asset from an Assets field wouldn’t show the correct custom fields, if the element hadn’t been saved yet or was disabled, and the asset hadn’t been placed in its target volume yet due to a dynamic subfolder path setting on the Assets field.
- Fixed a PHP error that could occur when updating Craft if an Assets field didn’t have valid settings.
- Fixed a PHP error that could occur when updating Craft if a Plain Text field didn’t have valid settings.

## 3.0.0-beta.7 - 2017-03-10

### Added
- Added `craft\services\Assets::getCurrentUserTemporaryUploadFolder()`.
- Added `craft\services\Assets::getUserTemporaryUploadFolder()`.

### Changed
- `UserException` reports are now styled like other exceptions when Dev Mode is enabled, with the full stack trace shown.
- It is no longer possible to create a temporary volume by calling `craft\services\Volumes::getVolumeById()` without passing an ID.
- Assets indexes now show a “Temporary uploads” volume that contain any assets uploaded by the current user, which haven’t been moved to a more permanent location yet.
- Craft now stores temporary asset uploads on a per-user basis, rather than per-user/per-Assets field.
- Disabled Matrix blocks are no longer shown in Live Preview. ([#13](https://github.com/craftcms/cms/issues/13))
- Rich Text fields now remember if their “Available Volumes” or “Available Transforms” settings were saved with no options selected, and disables the corresponding functionalities if so. ([#21](https://github.com/craftcms/cms/issues/21))
- `craft\base\Plugin::beforeUpdate()` and `afterUpdate()` now get passed a `$fromVersion` argument.)
- `craft\console\User::getIdentity()`’s return types are now consistent with `craft\web\User::getIdentity()`.
- `craft\services\Elements::saveElement()` now has a `$propagate` argument, which determines whether the element should be saved across all its supported sites (defaults to `true`).
- When an element is being saved across multiple sites, each site will now fire the before/after-save events.
- Exceptions that are thrown when running a task are now logged.
- Plugins’ translation categories are now all-lowercase by default, but they can be overridden by setting the `$t9nCategory` plugin property.
- The `_includes/forms/checkboxSelect.html` Control Panel template no longer shows an “All” checkbox by default. Set `showAllOption = true` to show it.
- The `_includes/forms/checkboxSelect.html` Control Panel template no longer interprets an empty default value to mean the “All” option should be checked.
- Updated the craftcms/server-check library to 1.0.11.

### Removed
- Removed support for chain-setting model properties via magic property setters. Models that wish to support this behavior must supply their own setter methods.
- Removed `craft\base\Model::copy()`.
- Removed `craft\fields\Assets::getFolderOptions()`.
- Removed `craft\services\Assets::getUserFolder()`.

### Fixed
- Fixed a bug where the `deferPublicRegistrationPassword` config setting was having the opposite effect it was intended for.
- Fixed a PHP error that would occur if some, but not all, of the auto-generated classes in `storage/runtime/compiled_classes/` didn’t exist or was out of date. ([#1427](https://github.com/craftcms/cms/issues/1427))
- Fixed a bug where exceptions that were thrown during template rendering were being displayed without any styling. ([#1454](https://github.com/craftcms/cms/issues/1454))
- Fixed a bug where the Clear Caches utility was ignoring any cache options registered with the `registerCacheOptions` event. ([#1461](https://github.com/craftcms/cms/issues/1461))
- Fixed the styling of Element Editor HUD headers and footers. ([#1457](https://github.com/craftcms/cms/issues/1457))
- Fixed a Slug validation error that would occur when saving an entry with no slug, if the entry type was configured to not show the Title field. ([#1456](https://github.com/craftcms/cms/issues/1456))
- Fixed an exception that occurred when an Assets field’s dynamic upload path could not be resolved. ([#1414](https://github.com/craftcms/cms/issues/1414))
- Fixed a bug where Redactor was losing its custom styling in Live Preview and Element Editor modals. ([#1467](https://github.com/craftcms/cms/issues/1467))
- Fixed a PHP error that occurred when passing anything besides an integer into an element query’s `level()` method. ([#1469](https://github.com/craftcms/cms/issues/1469))
- Fixed a bug where the Edit Entry page would always assume an entry had the first available entry type. ([#1470](https://github.com/craftcms/cms/issues/1470))
- Fixed a PHP error that occurred when attempting to rerun a failed task.
- Fixed a bug where Matrix and relational field values weren’t getting propagated to new sites correctly. ([#16](https://github.com/craftcms/cms/issues/16))
- Fixed a bug where the `CRAFT_SITE` constant wasn’t being respected. ([#1472](https://github.com/craftcms/cms/issues/1472))

## 3.0.0-beta.6 - 2017-03-03

### Added
- Added a “Cache remote images?” setting to the Asset Indexes utility, which if unchecked will dramatically speed up remote image indexing.
- Added `craft\base\Volume::getFileMetadata()`.
- Added `craft\base\Volume::getFileStream()`.
- Added `craft\base\VolumeInterface::getFileMetadata()`.
- Added `craft\base\VolumeInterface::getFileStream()`.
- Added `craft\elements\Asset::getFocalPoint()`.
- Added `craft\elements\Asset::getStream()`.
- Added `craft\helpers\Image::imageSizeByStream()`.
- Added `craft\services\AssetIndexer::extractFolderItemsFromIndexList()`.
- Added `craft\services\AssetIndexer::extractSkippedItemsFromIndexList()`.
- Added `craft\services\AssetIndexer::getIndexListOnVolume()`.
- Added `craft\services\AssetIndexer::getNextIndexEntry()`.
- Added `craft\services\AssetIndexer::processIndexForVolume()`.
- Added `craft\services\AssetIndexer::storeIndexList()`.
- Added `craft\services\AssetIndexer::updateIndexEntry()`.
- Added `craft\services\Tasks::rerunTask()`.
- Added `craft\web\Response::getContentType()`.

### Changed
- Asset focal point coordinates are now stored as decimal fractions instead of absolute coordinates.
- Craft now does fuzzy searching on the right side of a keyword by default. ([#1420](https://github.com/craftcms/cms/issues/1420))
- It’s now possible to add columns to an element query’s `select` clause without completely replacing all of the default columns, by calling its `addSelect()` method.
- Users are no longer logged out when verifying a new email address on their own account. ([#1421](https://github.com/craftcms/cms/issues/1421))
- Users no longer get an exception or error message if they click on an invalid/expired email verification link and are already logged in. Instead they’ll be redirected to wherever they would normally be taken immediately after logging in. ([#1422](https://github.com/craftcms/cms/issues/1422))
- `craft\base\Volume::filesystem()` now accepts a config parameter.
- `craft\base\Volume::getFileList()` now returns the file list array indexed by the file URIs.
- `craft\base\Volume::getMissingFiles()` no longer accepts a list of volume IDs and returns all missing files for that session,
- `craft\base\Volume::indexFile()` now requires an instance of `craft\base\Volume` (instead of `craft\base\VolumeInterface`) and a URI path as parameters.
- `craft\base\Volume::indexFile()` now accepts a parameter to indicate whether to cache remote files or not.
- `craft\controllers\TasksController::actionRerunTask()` now returns `1`, rather than the Json-encoded task info.
- `craft\services\Assets::findFolders()` now returns the folder list array indexed by folder ids.
- `craft\services\Tasks::rerunTaskById()` now returns `true` if the task was queued up to be rerun successfully, and will throw an exception if the task isn’t a top-level one.
- `craft\web\Controller::renderTemplate()` now returns a `yii\web\Response` object rather than a string.
- `craft\web\User::getReturnUrl()` now returns a URL based on the `postCpLoginRedirect` or `postLoginRedirect` config setting depending on the user’s permissions, if they didn’t have a return URL stored in their session data, and a default URL was not supplied.
- Renamed `craft\services\EmailMessages` to `SystemMessages`, which is now available to Craft Personal installations.
- Renamed `craft\base\ApplicationTrait::$emailMessages` to `$systemMessages`.
- Renamed `craft\base\ApplicationTrait::getEmailMessages()` to `getSystemMessages()`.
- Renamed `craft\controllers\EmailMessagesController` to `SystemMessagesController`.
- Renamed the `emailmessages` DB table to `systemmessages`.
- Renamed `craft\models\RebrandEmail` to `SystemMessage`, and removed its `$language` and `$htmlBody` properties.
- Renamed `craft\records\EmailMessage` to `SystemMessage`.
- Renamed `craft\web\assets\emailmessages\EmailMessagesAsset` to `craft\web\assets\systemmessages\SystemMessagesAsset`.
- System messages registered with the `craft\services\SystemMessages::EVENT_REGISTER_MESSAGES` event must now either be `craft\models\SystemMessage` objects or arrays with `key`, `heading`, `subject`, and `body` keys.
- Rich Text field settings will no longer show the “All” option for the “Available Volumes and “Available Transforms” settings if no volumes or transforms have been defined. 

### Removed 

- Removed `craft\elements\db\UserQuery::$withPassword`.
- Removed `craft\elements\db\UserQuery::withPassword()`.
- Removed `craft\helpers\Header`.
- Removed `craft\helpers\Json::sendJsonHeaders()`.
- Removed `craft\helpers\Json::setJsonContentTypeHeader()`.
- Removed `craft\models\AssetIndexData::$offset`.
- Removed `craft\records\AssetIndexData::$offset`.
- Removed `craft\services\AssetIndexer::getIndexEntry()`.
- Removed `craft\services\AssetIndexer::processIndexForVolume()`.
- Removed `craft\services\AssetIndexer::storeIndexEntry()`.
- Removed `craft\services\AssetIndexer::updateIndexEntryRecordId()`.
- Removed `craft\services\Users::getUserByEmail()`.
- Removed `craft\web\Application::returnAjaxError()`.
- Removed `craft\web\Application::returnAjaxException()`.
- Removed `craft\web\Controller::asJson()` and `asXml()`, because the base `yii\web\Controller` class now defines the exact same methods.

### Fixed
- Fixed a bug where it was not possible to update a Asset transform index entry. ([#1434](https://github.com/craftcms/cms/issues/1434))
- Fixed a bug where the Control Panel wouldn’t keep up with task progress after the user chose to rerun a task.
- Fixed a PHP error that could occur if `craft\web\AssetBundle` had been loaded before `craft\web\View`.
- Fixed a bug where new Assets could not be indexed. ([#1437](https://github.com/craftcms/cms/issues/1437))
- Fixed a bug where system email subjects and bodies were just getting the translation keys, e.g. `activate_account_subject` and `activate_account_body`.
- Fixed a bug where you would get a SQL error when saving an Assets field that had a selected asset. ([#1444](https://github.com/craftcms/cms/issues/1444))
- Fixed a couple bugs that broke new email verification.
- Fixed an InvalidParamException that was thrown when clicking a user email verification link with an invalid/expired token.
- Fixed a SQL error that could occur when restoring a database backup after a failed update.
- Fixed a bug where the `invalidUserTokenPath` config setting wasn’t being respected.
- Fixed a bug where creating/editing an entry with a Rich Text field that had Asset Volumes attached in its settings would create a SQL error on PostgreSQL. ([#1438](https://github.com/craftcms/cms/issues/1438))
- Fixed a bug where template requests were not getting a `Content-Type` header based on the template’s MIME type. ([#1424](https://github.com/craftcms/cms/issues/1424))
- Fixed a bug where element pagination would only think there was one page. ([#1440](https://github.com/craftcms/cms/issues/1440))
- Fixed a bug where the `offset` param would doubly reduce the number of elements that could be paginated. ([#1425](https://github.com/craftcms/cms/issues/1425))
- Fixed a bug where Composer-installed plugins’ source translations weren’t getting loaded. ([#1446](https://github.com/craftcms/cms/issues/1446))
- Fixed a SQL error that could occur when loading the elements on an element index page if there were any collapsed elements. ([#1450](https://github.com/craftcms/cms/issues/1450))

## 3.0.0-beta.5 - 2017-02-24

### Added
- Added a “Column Type” advanced setting to Plain Text fields.
- Added a “Column Type” advanced setting to Table fields. ([#1381](https://github.com/craftcms/cms/issues/1381))
- Added `craft\base\ElementInterface::hasUris()`. Elements that can have URIs must add this static method and return `true` now.
- Added `craft\db\Connection::getIsMysql()`.
- Added `craft\db\Connection::getIsPgsql()`.
- Added `craft\events\GenerateTransformEvent`.
- Added `craft\helpers\Component::applySettings()`.
- Added `craft\helpers\Db::getMinAllowedValueForNumericColumn()`.
- Added `craft\helpers\Db::getMaxAllowedValueForNumericColumn()`.
- Added `craft\helpers\Db::isNumericColumnType()`.
- Added `craft\helpers\Db::isTextualColumnType()`.
- Added `craft\helpers\Db::parseColumnLength()`.
- Added `craft\helpers\Db::parseColumnType()`.
- Added `craft\validators\ElementUriValidator`.
- Added `craft\validators\SlugValidator`.

### Changed
- The `cacheDuration` config setting can now be set to an integer (number of seconds).
- Volumes’ “Base URL” settings can now begin with `@web`, which is an alias for the root URL that Craft is running from.
- Local volumes’ “File System Path” settings can now begin with `@webroot`, which is an alias for the path to the directory that `index.php` lives in.
- Volume settings defined in `config/volumes.php` are now passed directly into volume class constructors.
- Moved Rich Text fields’ “Clean up HTML?”, “Purify HTML?”, and “Column Type” settings into an “Advanced” section.
- Renamed Plain Text fields’ “Max Length” setting to “Character Limit”.
- Element queries can now be explicitly configured to ignore the element structure tables by setting the `withStructure` param to `false`.
- Required custom field validation rules are now created by the element, so `craft\base\Field::getElementValidationRules()` just returns an empty array now.
- Elements now validate that custom field values will fit within their database columns, for fields with textual or numeric column types.
- `craft\feeds\Feeds::getFeedItems()` no longer explicitly sets the cache duration to the `cacheDuration` config setting; it lets the data caching driver decide what the default should be (which is set based on the `cacheDuration` config setting… by default).
- `craft\helpers\Db::getTextualColumnStorageCapacity()` now returns `false` if the max capacity can’t be determined, rather than throwing an exception.
- `craft\helpers\Db::getTextualColumnStorageCapacity()` now supports passing in full column type definitions, including attributes like `NOT NULL`, etc.
- `craft\helpers\Db::getTextualColumnStorageCapacity()` will now return the max length for`string` and `char` column type definitions.
- `craft\helpers\ElementHelper::findSource()` now adds a `keyPath` key to the returned source definition array if the source was nested.
- `craft\helpers\ElementHelper::setUniqueUri()` now behaves consistently whether or not the element’s URI format has a `{slug}` token – it will always throw a OperationAbortedException if it can’t find a unique URI.
- `craft\i18n\Formatter::asText` will now format DateTime objects to text.
- `craft\mail\Mailer::send()` now returns `false` if the message couldn’t be sent, rather than throwing a `SendEmailException`.
- Updated the Yii Debug extension to 2.0.8.
- Updated d3.js to 4.6.0.0.
- Updated timepicker to 1.11.10.
- Updated Velocity to 1.4.3.
- Updated Fabric to 1.7.6.
- Updated Codeception to 2.2.9.
- Updated Codeception Verify to 0.3.3.
- Updated Codeception Specify to 0.4.6.
- Updated Flysystem to 1.0.35.
- Updated Yii to 2.0.11.2.

### Removed
- Removed `craft\errors\SendEmailException`.
- Removed `craft\helpers\ElementHelper::setValidSlug()`.

### Fixed
- Fixed a bug where Assets Indexing utility would generate an erroneous request at the end of the operation. ([#1373](https://github.com/craftcms/cms/issues/1373))
- Fixed a JS error that occurred on edit pages with a Color field, unless the `useCompressedJs` config setting was set to `false`. ([#1392](https://github.com/craftcms/cms/issues/1392))
- Fixed a bug where the `cacheDuration` config setting wasn’t getting applied to data caches, so everything was getting cached indefinitely by default.
- Fixed a PHP error that occurred when saving a new entry draft, if the entry’s section only had one entry type. ([#1390](https://github.com/craftcms/cms/issues/1390))
- Fixed a bug where entries’ “Title” field would receive two identical validation errors if a brand new entry was immediately saved as a draft, and didn’t have a title yet.
- Fixed a bug where it was not possible to edit categories on anything but the primary site. ([#1403](https://github.com/craftcms/cms/issues/1403))
- Fixed a PHP type error that could occur when editing an entry or category, if its corresponding template was `NULL` in the database, for some reason.
- Fixed an exception that occurred when testing email settings, if the settings weren’t correct. ([#1405](https://github.com/craftcms/cms/issues/1405))
- Fixed a bug where new Dashboard widgets would get placed before other widgets after reloading the Dashboard. ([#1410](https://github.com/craftcms/cms/issues/1410))
- Fixed a bug where Assets modal would not work when using dynamic paths. ([#1374](https://github.com/craftcms/cms/issues/1374))
- Fixed a bug that prevented the database from being restored properly in certain circumstances if a 2.x to 3.0 upgrade failed.
- Removed the “Column Type” setting from Rich Text fields for PostgreSQL installs, since PostgreSQL doesn’t have/need a `mediumtext` column type.
- Fixed a bug where clicking on the link in a forgot password email would cause a “Invalid Verification Code” error to be thrown. ([#1386](https://github.com/craftcms/cms/pull/1386))
- Fixed a bug where the admin “Copy Password Reset URL” option for a user account would give an error when used.
- Fixed a bug where checking the “Require a password reset on next login” for a user would cause a SQL error when saving that user. ([#1411](https://github.com/craftcms/cms/issues/1411))
- Fixed a bug where custom field validation errors didn’t always include the correct field name.
- Fixed a bug where Craft was throwing an exception when it couldn’t set a valid slug on an element during save, rather than adding a validation error.
- Fixed a bug where saving an element with the title “0” would result in the element’s title getting saved as “-”. ([#1383](https://github.com/craftcms/cms/issues/1383))
- Fixed a bug where the Control Panel layout templates didn’t to a good job handling pages with a title of “0”.
- Fixed a bug where the migration responsible for converting user photos to Assets would fail intermittently.
- Fixed a bug where existing entries were not getting their structure data if their section was converted from a Single/Channel to a Structure. ([#1407](https://github.com/craftcms/cms/issues/1407))
- Fixed a bug where `craft\services\Globals::saveSet()` could return `true` even if the global set hadn’t been saved successfully.
- Fixed a bug where it was possible to save an element without a slug if the Title didn’t contain any alphanumeric characters. ([#22](https://github.com/craftcms/cms/issues/22))
- Fixed a bug where it was possible to save a Single section with an invalid URI. ([#1416](https://github.com/craftcms/cms/issues/1416))
- Fixed a bug where saving an element with an invalid URI would halfway work. ([#1416](https://github.com/craftcms/cms/issues/1416))

## 3.0.0-beta.4 - 2017-02-17

### Added
- Added the “Branch Limit” Categories field setting, which replaces the “Limit” setting. ([#1388](https://github.com/craftcms/cms/issues/1388))
- Added the `withStructure` param to element queries (defaults to `true` for entry and category queries).
- Added `craft\base\PluginInterface::setSettings()`.
- Added `craft\base\FolderVolumeInterface` that should be used by all Volumes supporting discrete folders.
- Added `craft\base\FolderVolume` that should be used by all Volumes supporting discrete folders.
- Added `craft\elements\db\ElementQueryInterface::withStructure()`.
- Added `craft\helpers\App::humanizeClass()`.
- Added `craft\helpers\FileHelper::lastModifiedTime()`.
- Added `craft\models\FieldLayout::getFieldByHandle()`.
- Added `craft\services\Categories::applyBranchLimitToCategories()`.
- Added `craft\services\Tasks::getTaskInfo()`.
- Added `craft\web\Request::accepts()`.
- Added the `$maybeAutoRun` argument to `craft\services\Tasks::queueTask()`.
- Added Craft’s required PHP extensions to `composer.json`.

### Changed
- The Resave Elements task now shows a more humanized version of the element type’s class name in its default descriptions.
- Elements no longer get a default title on save, unless they’re being saved without validation.
- `craft\fields\BaseRelationField::$allowMultipleSources` is now public.
- `craft\fields\BaseRelationField::$allowLimit` is now public.
- Renamed `craft\fields\BaseRelationField::sourceOptions()` to `getSourceOptions()`, and now it’s public.
- Renamed `craft\fields\BaseRelationField::targetSiteFieldHtml()` to `getTargetSiteFieldHtml()`, and now it’s public.
- Renamed `craft\fields\BaseRelationField::viewModeFieldHtml()` to `getViewModeFieldHtml()`, and now it’s public.
- It’s now possible for field types that extend `craft\fields\BaseRelationField` to override the settings template by setting the `$settingsTemplate` property.
- `craft\volumes\Local` now extends `craft\base\FolderVolume` instead of `craft\base\Volume`.
- Replaced `craft\services::fillGapsInCategoryIds()` with `fillGapsInCategories()`.
- Updated Garnish to 0.1.13.

### Removed
- Removed `craft\base\Element::resolveStructureId()`.
- Removed `craft\base\ElementInterface::getStructureId()`.
- Removed `craft\base\ElementInterface::setStructureId()`.
- Removed `craft\base\Volume::createDir()`.
- Removed `craft\base\Volume::deleteDir()`.
- Removed `craft\base\Volume::folderExists()`.
- Removed `craft\base\Volume::renameDir()`.
- Removed `craft\base\VolumeInterface::createDir()`.
- Removed `craft\base\VolumeInterface::deleteDir()`.
- Removed `craft\base\VolumeInterface::renameDir()`.
- Removed the `table.inputs` styles. Use `div.flex` instead.

### Fixed
- Fixed a bug where a plugin’s `init()` method couldn’t access its own settings values. ([#1361](https://github.com/craftcms/cms/issues/1361))
- Fixed a PHP type error if you tried to save a Number field’s setting with “Min Value” set to nothing.
- Fixed a bug where it was not possible to rename a file with the Asset "Rename File" action.
- Fixed a PHP error that occurred when uploading a user photo. ([#1367](https://github.com/craftcms/cms/issues/1367))
- Fixed a bug where element titles were not translatable. ([#1365](https://github.com/craftcms/cms/issues/1365))
- Fixed a PHP error that occurred on the Settings → General page if the `timezone` config setting was set. ([#1366](https://github.com/craftcms/cms/issues/1366))
- Fixed a bug where some Control Panel message strings were getting translated with the `site` translation category rather than `app`.
- Fixed a bug where pagination URLs would define page numbers using a `pg` query string param if the `pageTrigger` config setting was set to `'?p'` and the `pathParam` config setting was set to something besides `'p'`.
- Fixed a bug where if a Craft update failed and the attempt to restore the database from a backup failed, the UI would still show that the restore was successful.
- Fixed several migration related bugs that were preventing some Craft 2.6 installs from being able to update to Craft 3.
- Fixed a bug where renaming a folder would remove it’s trailing slash from path.
- Fixed a bug where asset bundles weren’t always getting re-published when a sub-file(s) had been updated. ([#1371](https://github.com/craftcms/cms/issues/1371))
- Fixed a bug where SVG images without a viewbox defined would not be scaled correctly.
- Fixed a bug where Craft would generate warning when trying to index images with no content in them.
- Fixed a bug where the Database Backup utility wouldn’t show an error when the backup failed. ([#1372](https://github.com/craftcms/cms/issues/1372))
- Fixed a bug where saving preexisting Active Record objects was not updating the `dateUpdated` column automatically.
- Fixed a bug where required fields on a field layout were not being enforced. ([#1380](https://github.com/craftcms/cms/issues/1380))
- Fixed a bug where required Plain Text fields were not getting a validation error if left blank.
- Fixed a PHP type error that occurred when calling `craft\base\Element::getPrevSibling()` or `getNextSibling()`.
- Fixed a bug where Structure-related element methods (e.g. `getParent()`) weren’t working for elements that weren’t queried with the `structureId` param set. ([#1375](https://github.com/craftcms/cms/issues/1375))
- Fixed a bug where an exception was thrown when saving an element with a slug that was more than 255 characters long, rather than giving the slug a validation error. ([#1389](https://github.com/craftcms/cms/issues/1389))
- Fixed a bug where the Password input on the Control Panel Login page was getting extra padding on the right side instead of the left side for browsers that preferred a RTL language. ([#1391](https://github.com/craftcms/cms/issues/1391))
- Fixed several issues and unexpected behaviors around the Number field, its default values, min and max settings and required validation.
- Fixed a bug where the element listing wouldn’t get refreshed after running the “Set status” action, if any of the elements couldn’t be enabled due to validation errors.

## 3.0.0-beta.3 - 2017-02-07

### Added
- Added the new “System Name” general setting, which defines the name that should be visible in the global CP sidebar. ([#1338](https://github.com/craftcms/cms/issues/1338))
- Added `craft\base\ElementInterface::getSearchKeywords()`.
- Added `craft\helpers\Db::areColumnTypesCompatible()`.
- Added `craft\helpers\Db::getSimplifiedColumnType()`.
- Added `craft\services\Security::redactIfSensitive()`.

### Changed
- The “Set status” batch element action now goes through the normal element save process, rather than directly modifying the DB values, ensuring that the elements validate before enabling them. ([#2](https://github.com/craftcms/cms/issues/2))
- The “Set status” batch element action now updates elements’ site statuses in addition to their global statuses, when setting the status to Enabled.
- Sensitive global values are now redacted from the logs. ([#1328](https://github.com/craftcms/cms/issues/1328))
- Editable tables now support a `radioMode` checkbox column option, which prevents more than one of the column’s checkboxes from being checked at a time.
- `craft\helpers\Db::getNumericalColumnType()` no longer returns unsigned integer column types for MySQL.
- The “Field Type” setting on Edit Field pages no longer shows field type options where there’s no chance the existing field data will map over.
- When an entry type is updated, Craft now re-saves all entries of that type.
- Added a `string` return type declaration to `craft\base\PreviewableFieldInterface::getTableAttributeHtml()`.
- Updated Craft Server Check to 1.0.8.

### Removed
- Removed the `afterSetStatus` event from `craft\elements\actions\SetStatus`.
- Removed `craft\enums\ColumnType`.
- Removed `craft\helpers\Logging`.
- Removed `craft\models\SiteSettings`.
- Removed `craft\web\assets\jcrop\JcropAsset`.

### Fixed
- Fixed a bug where saving a disabled entry or draft without a post/expiry date would default to the currently-set date on the entry/draft, rather than clearing out the field.
- Fixed some asterisk icons.
- Fixed a bug where it was impossible to upload user photo, site logo or site icon. ([#1334](https://github.com/craftcms/cms/issues/1334))
- Fixed a bug where it was possible to select multiple default options for Dropdown and Radio Buttons fields. ([#8](https://github.com/craftcms/cms/issues/8))
- Fixed a bug where the “Globals” Control Panel nav item would link to a 404 right after deleting the first global set in Settings → Globals.  ([#9](https://github.com/craftcms/cms/issues/9))
- Fixed a bug that occurred when generating transforms for images with focal points/ ([#1341](https://github.com/craftcms/cms/issues/1341))
- Fixed a bug where the utility status was overlapping the submit button in Utilities. ([#1342](https://github.com/craftcms/cms/issues/1342))
- Fixed a bug where `craft\helpers\Db::getNumericalColumnType()` could return a column type that wouldn’t actually fix the `$max` argument in PostgreSQL.
- Fixed a bug where entry URIs weren’t getting updated after an entry type was updated when the URI format referenced entry type properties. ([#15](https://github.com/craftcms/cms/issues/15))
- Fixed a bug that broke site administration. ([#1332](https://github.com/craftcms/cms/issues/1332))
- Fixed a PHP error that occurred when saving an entry with a Matrix field on a multi-site install, in some cases.
- Fixed a PHP error that occurred when saving an element with a Date/Time field. ([#1332](https://github.com/craftcms/cms/issues/1332))
- Fixed a Twig syntax error when editing an element with a Color field. ([#1354](https://github.com/craftcms/cms/issues/1354))
- Fixed a bug where fields that implemented `craft\base\PreviewableFieldInterface` were not showing up as options on element indexes.
- Fixed task re-running.
- Fixed a bug with transforming SVG files.
- Fixed a bug with transforming images on external sources.
- `config/app.php` can now be a multi-environment config. ([#1344](https://github.com/craftcms/cms/issues/1344))
- Fixed a PHP error that occurred when creating a new tag. ([#1345](https://github.com/craftcms/cms/issues/1345))
- Fixed a bug where relations would be dropped when running the Resave Elements task. ([#1360](https://github.com/craftcms/cms/issues/1360))
- Fixed a PHP error that occurred when executing an element query with the `relatedTo` param set to an element. ([#1346](https://github.com/craftcms/cms/issues/1346))
- Fixed a JavaScript error that was preventing Redactor from loading for Rich Text fields, for users with a non-English preferred language. ([#1349](https://github.com/craftcms/cms/issues/1349))
- Fixed a PHP type error that would occur when calling `craft\services\Globals::getSetByHandle()`. ([#1351](https://github.com/craftcms/cms/issues/1351))
- Fixed a bug where Plain Text fields weren’t enforcing their Max Length setting, and Number fields weren’t enforcing their Min and Max Value settings. ([#1350](https://github.com/craftcms/cms/issues/1350))
- Fixed a 404 error that would occur when switching sites when editing a global set. ([#1355](https://github.com/craftcms/cms/issues/1355))
- Fixed a bug that broke reference tags for Global Sets, Matrix Blocks and Tags. ([#1359](https://github.com/craftcms/cms/issues/1359))
- Fixed a Twig parse error that occurred when using the deprecated `{% includecss %}` or `{% includejs %}` tags as tag pairs. ([#1358](https://github.com/craftcms/cms/issues/1358))
- Fixed a bug where Craft was only logging warnings and errors when Dev Mode was enabled.
- Fixed the “x” button’s icon that appears in search inputs, used to clear the search input. ([#1356](https://github.com/craftcms/cms/issues/1356))
- Fixed a bug where you would get a validation error if you tried to purchase Craft with a 100%-off coupon code.
- Fixed a migration error that was preventing Craft 2.6 installs from being able to update to Craft 3.  ([#1347](https://github.com/craftcms/cms/issues/1347))

## 3.0.0-beta.2 - 2017-02-02

### Changed
- Craft now logs `craft\db\QueryAbortedException`s.
- Element queries will now throw `craft\db\QueryAbortedException`s if any structure params are set, but `structureId` is not set.
- `craft\services\Categories::fillGapsInCategoryIds()` now has a required `$structureId` argument.
- Added `type` and `value` to the list of reserved field handles. ([#1331](https://github.com/craftcms/cms/issues/1331))
- Console requests now get the CP template mode by default.
- Site requests now resolve plugin template paths as if they were CP requests when rendering with the CP template mode. ([#1335](https://github.com/craftcms/cms/issues/1335))
- Updated Yii to 2.0.11.1.

### Removed
- Removed support for Memcache (without a d), as it is not compatible with PHP 7. ([#1309](https://github.com/craftcms/cms/pull/1309))

### Fixed
- Fixed a bug where `craft\feeds\Feeds::getFeedItems()` was returning `null` when the results were not already cached, resulting in an “unknown error” on the Dashboard.
- Fixed an InvalidConfigException that would get thrown when attempting to edit an entry version with an author that had been deleted.
- Fixed a bug where swapping between entries in a section enabled for multiple sites would cause a PHP type error. ([#1310](https://github.com/craftcms/cms/pull/1310))
- Fixed a bug where the “Save as a draft” entry Save menu option would take you to a 404.
- Fixed a bug where the “Publish draft” entry draft Save menu option would take you to a 404.
- Fixed a bug where the “Delete draft” entry draft Save menu options would take you to a 404.
- Fixed a bug where the “Delete” category button would take you to a 404.
- Fixed a bug where saving a user with no permissions would result in a PHP type error.
- Fixed a bug where removing a user’s admin permissions using PostgreSQL would result in a SQL error.
- Fixed a bug where the “Revert entry to this version” button on entry version pages would result in an “No element exists with the ID 'X'” exception. ([#1037](https://github.com/craftcms/cms/issues/1037))
- Fixed a bug where creating a new user would cause a PHP type error. ([#1311](https://github.com/craftcms/cms/issues/1311))
- Fixed a bug where `src/config/defaults/general.php` was listing `'redis'` as a possible `cacheMethod` setting value, but Yii 2 does not have native support for Redis. ([#1314](https://github.com/craftcms/cms/issues/1314))
- Fixed a bug where `craft\db\QueryAbortedException`s were not getting caught when calling `craft\db\Query::scalar()` or `column()`.
- Fixed a bug where expanding a collapsed Structure entry or category on an index page would come up empty. ([#1321](https://github.com/craftcms/cms/issues/1321))
- Fixed some `TypeError`s in controller action responses. ([#1316](https://github.com/craftcms/cms/issues/1316))
- Fixed a PHP error that occurred when using the `{% nav %}` tag, or when selecting categories in a Categories field. ([#1313](https://github.com/craftcms/cms/issues/1313))
- Fixed a bug where deleting all the selections in a relation field would result in no changes being made to the field on save.
- Fixed a PHP error that occurred when editing a Rich Text field with the “Available Transforms” setting set to `*`. ([#1322](https://github.com/craftcms/cms/issues/1322))
- Fixed a PHP error that occurred when editing an image using GD. ([#1312](https://github.com/craftcms/cms/issues/1312))
- Fixed a PHP error that occurred when generating image transforms. ([#1323](https://github.com/craftcms/cms/issues/1323))
- Fixed a bug where Assets fields’ “Sources” settings weren’t working.
- Fixed a bug where disabled entries and categories weren’t showing up in their Control Panel indexes. ([#1325](https://github.com/craftcms/cms/issues/1325))
- Fixed a bug where creating a Number field type on PostgreSQL would result a SQL error.
- Fixed a bug where calling `craft\services\Sections::getEntryTypesByHandle()` would cause a PHP type error. ([#1326](https://github.com/craftcms/cms/issues/1326))
- Fixed a bug where plugin updates could be displayed with the wrong date if the system time zone was behind UTC.

## 3.0.0-beta.1 - 2017-01-29

### Added
- Ported all recent changes from Craft 2, including chart-related things added in Craft 2.6.
- Craft 3 now requires PHP 7.0.0 or later.
- Added an image editor to the Assets index page, with support for cropping, rotating, and flipping images, as well as setting focal points on them, which influences where images should be cropped for image transforms.
- Craft can now be installed via Composer: `composer require craftcms/craft`.
- Craft now supports installing plugins via Composer, with the help [Craft CMS Composer Installer](https://github.com/craftcms/plugin-installer).
- Craft now checks for plugin info in a composer.json file, rather than plugin.json, for plugins that were manually installed in `plugins/`. (See the [Craft CMS Composer Installer](https://github.com/craftcms/plugin-installer) readme for details on how the info should be formatted.)
- Plugin icons now must be stored at the root of the plugin’s source directory.
- Plugin IDs are now `kebab-case` versions of their handles.
- Craft now automatically loads the `vendor/autoload.php` file (if it exists) for plugins that were manually installed.
- Added the `bootstrap/` folder alongside the `src/` folder, with new web.php and console.php bootstrap files.
- Added PostgreSQL support, which can be enabled by setting the `driver` setting in `config/db.php` to `'pgsql'`.
- Added the `update/run-pending-migrations` controller action, which can be used as a post-deploy hook for deployment services like DeployBot, to get Craft to automatically run any pending migrations, minimizing site downtime.
- Added the `backupCommand` config setting, which can be used to override the command Craft executes when creating a database backup.
- Added the `restoreCommand` config setting, which can be used to override the command Craft executes when restoring a database backup.
- Added the `dsn` DB config setting, which can be used to manually specify the DSN string, ignoring most other DB config settings.
- Added the `schema` DB config setting, which can be used to assign the default schema used when connecting to a PostgreSQL database.
- Added support for setting Volume config settings in `config/volumes.php`. The file should return an array with keys that match volume handles, and values that are config arrays for the volumes.
- It is now possible to override the default Guzzle settings from `config/guzzle.php`.
- Added the `view` global Twig variable, which is a reference to the View class that is rendering the template.
- Added `craft.matrixBlocks()`, which can be used to query for Matrix blocks.
- Added the `SORT_ASC` and `SORT_DESC` global Twig variables, which can be used to define query sorting in element queries.
- Added the `POS_HEAD`, `POS_BEGIN`, `POS_END`, `POS_READY`, and `POS_LOAD` global Twig variables, which can be used to define the placement of registered scripts.
- Added the `className()` global Twig function, which returns the class name of a given object.
- Added the `|atom` and `|rss` Twig filters, for formatting dates in Atom and RSS date formats, respectively.
- Added the `|column` Twig filter, for capturing the key/property values of a series of arrays/objects.
- Added the `|index` Twig filter, for indexing an array of arrays/objects by one of their keys/values.
- Added the “Utilities” section to the Control Panel, replacing the Tools area of the Settings page.
- Added the Utility API, which enables plugins to provide custom utilities.
- Added the JavaScript method `BaseElementIndex::refreshSources()`.
- Added method parameter and return types everywhere possible.
- Added a new `@lib` Yii alias, pointed to `vendor/craftcms/cms/lib/`.
- Added `Craft::createGuzzleClient()`, which creates a Guzzle client instance with any custom config settings merged in with the site default settings.
- Added `craft\base\LocalVolumeInterface`.
- Added `craft\base\Utility`.
- Added `craft\base\UtilityInterface`.
- Added `craft\controllers\UtilitiesController`.
- Added `craft\db\pgsql\QueryBuilder`.
- Added `craft\db\pgsql\Schema`.
- Added `craft\db\TableSchema`.
- Added `craft\elements\actions\EditImage`.
- Added `craft\errors\InvalidPluginException`.
- Added `craft\errors\ShellCommandException`.
- Added `craft\events\RegisterAssetFileKindsEvent`.
- Added `craft\events\RegisterCacheOptionsEvent`.
- Added `craft\events\RegisterComponentTypesEvent`.
- Added `craft\events\RegisterCpAlertsEvent`.
- Added `craft\events\RegisterCpNavItemsEvent`.
- Added `craft\events\RegisterElementDefaultTableAttributesEvent`.
- Added `craft\events\RegisterElementHtmlAttributesEvent`.
- Added `craft\events\RegisterElementSearchableAttributesEvent`.
- Added `craft\events\RegisterElementSortOptionsEvent`.
- Added `craft\events\RegisterElementSourcesEvent`.
- Added `craft\events\RegisterElementTableAttributesEvent`.
- Added `craft\events\RegisterEmailMessagesEvent`.
- Added `craft\events\RegisterRichTextLinkOptionsEvent`.
- Added `craft\events\RegisterUrlRulesEvent`.
- Added `craft\events\RegisterUserActionsEvent`.
- Added `craft\events\RegisterUserPermissionsEvent`.
- Added `craft\events\ResolveResourcePathEvent`.
- Added `craft\events\SetAssetFilenameEvent`.
- Added `craft\events\SetElementRouteEvent`.
- Added `craft\events\SetElementTableAttributeHtmlEvent`.
- Added `craft\helpers\FileHelper`.
- Added `craft\helpers\MailerHelper`.
- Added `craft\services\Utilities`.
- Added `craft\utilities\AssetIndexes`.
- Added `craft\utilities\ClearCaches`.
- Added `craft\utilities\DbBackup`.
- Added `craft\utilities\DeprecationErrors`.
- Added `craft\utilities\FindAndReplace`.
- Added `craft\utilities\PhpInfo`.
- Added `craft\utilities\SearchIndexes`.
- Added `craft\utilities\SystemReport`.
- Added `craft\utilities\Updates`.
- Added `craft\validators\ArrayValidator`.
- Added `craft\validators\AssetFilenameValidator`.
- Added `craft\validators\UsernameValidator`.
- Added `craft\validators\UserPasswordValidator`.
- Added `craft\web\AssetBundle`.
- Added `craft\web\assets\assetindexes\AssetIndexesAsset`.
- Added `craft\web\assets\clearcaches\ClearCachesAsset`.
- Added `craft\web\assets\colorpicker\ColorpickerAsset`.
- Added `craft\web\assets\craftsupport\CraftSupportAsset`.
- Added `craft\web\assets\dashboard\DashboardAsset`.
- Added `craft\web\assets\datepickeri18n\DatepickerI18nAsset`.
- Added `craft\web\assets\dbbackup\DbBackupAsset`.
- Added `craft\web\assets\deprecationerrors\DeprecationErrorsAsset`.
- Added `craft\web\assets\editcategory\EditCategoryAsset`.
- Added `craft\web\assets\editentry\EditEntryAsset`.
- Added `craft\web\assets\edittransform\EditTransformAsset`.
- Added `craft\web\assets\edituser\EditUserAsset`.
- Added `craft\web\assets\emailmessages\EmailMessagesAsset`.
- Added `craft\web\assets\fabric\FabricAsset`.
- Added `craft\web\assets\feed\FeedAsset`.
- Added `craft\web\assets\fields\FieldsAsset`.
- Added `craft\web\assets\fileupload\FileUploadAsset`.
- Added `craft\web\assets\findreplace\FindReplaceAsset`.
- Added `craft\web\assets\generalsettings\GeneralSettingsAsset`.
- Added `craft\web\assets\imageeditor\ImageEditorAsset`.
- Added `craft\web\assets\installer\InstallerAsset`.
- Added `craft\web\assets\jcrop\JcropAsset`.
- Added `craft\web\assets\jqueryui\JqueryUiAsset`.
- Added `craft\web\assets\login\LoginAsset`.
- Added `craft\web\assets\matrix\MatrixAsset`.
- Added `craft\web\assets\matrixsettings\MatrixSettingsAsset`.
- Added `craft\web\assets\newusers\NewUsersAsset`.
- Added `craft\web\assets\plugins\PluginsAsset`.
- Added `craft\web\assets\positionselect\PositionSelectAsset`.
- Added `craft\web\assets\quickpost\QuickPostAsset`.
- Added `craft\web\assets\qunit\QunitAsset`.
- Added `craft\web\assets\recententries\RecentEntriesAsset`.
- Added `craft\web\assets\redactor\RedactorAsset`.
- Added `craft\web\assets\richtext\RichTextAsset`.
- Added `craft\web\assets\routes\RoutesAsset`.
- Added `craft\web\assets\searchindexes\SearchIndexesAsset`.
- Added `craft\web\assets\sites\SitesAsset`.
- Added `craft\web\assets\tablesettings\TableSettingsAsset`.
- Added `craft\web\assets\tests\TestsAsset`.
- Added `craft\web\assets\updater\UpdaterAsset`.
- Added `craft\web\assets\updates\UpdatesAsset`.
- Added `craft\web\assets\updateswidget\UpdatesWidgetAsset`.
- Added `craft\web\assets\userpermissions\UserPermissionsAsset`.
- Added `craft\web\assets\utilities\UtilitiesAsset`.
- Added `craft\web\assets\whatsnew\WhatsNewAsset`.
- Added `craft\web\assets\xregexp\XregexpAsset`.
- Added `craft\base\ApplicationTrait::$env`, which stores the current environment ID, which is set to `$_SERVER['SERVER_NAME']` by default and can be overridden with the `CRAFT_ENVIRONMENT` PHP constant.
- Added `craft\base\Element::$validateCustomFields`, which can be set to true or false to explicitly require/prevent custom field validation.
- Added `craft\base\Element::afterDelete()`, which is called after an element is deleted.
- Added `craft\base\Element::afterMoveInStructure()`, which is called after an element is moved within a structure.
- Added `craft\base\Element::defineDefaultTableAttributes()`.
- Added `craft\base\Element::beforeDelete()`, which is called before the element is deleted.
- Added `craft\base\Element::defineActions()`.
- Added `craft\base\Element::defineSearchableAttributes()`.
- Added `craft\base\Element::defineSortOptions()`.
- Added `craft\base\Element::defineSources()`.
- Added `craft\base\Element::defineTableAttributes()`.
- Added `craft\base\Element::getHtmlAttributes()`, which gives elements a chance to define any HTML attributes that should be included when rendering an element node for the Control Panel.
- Added `craft\base\Element::getSerializedFieldValues()`.
- Added `craft\base\Element::htmlAttributes()`.
- Added `craft\base\Element::route()`.
- Added `craft\base\Element::tableAttributeHtml()`.
- Added `craft\base\ElementInterface::refHandle()`.
- Added `craft\base\Field::afterElementDelete()`, which is called by an element after it is deleted.
- Added `craft\base\Field::beforeElementDelete()`, which is called by an element before it is deleted.
- Added `craft\base\Field::getElementValidationRules()`, which field types can override to return their element-level validation rules.
- Added `craft\base\MissingComponentTrait::createFallback()`.
- Added `craft\base\Plugin::$changelogUrl`, which replaces `$releaseFeedUrl` and should point to a Markdown-formatted changelog.
- Added `craft\base\Plugin::$downloadUrl`, which should point to the plugin’s download URL.
- Added `craft\base\Plugin::$hasCpSection`, which replaces the static `hasCpSection()` method.
- Added `craft\controllers\AssetsController::actionImageEditor()`.
- Added `craft\controllers\AssetsController::actionEditImage()`.
- Added `craft\controllers\AssetsController::actionSaveImage()`.
- Added `craft\db\Connection::backupTo()`.
- Added `craft\db\mysql\Schema::findIndexes()`.
- Added `craft\elements\Asset::$focalPoint`.
- Added `craft\elements\Asset::$keepFileOnDelete`, which can be set to true if the corresponding file should not be deleted when deleting the asset.
- Added `craft\elements\Asset::$newFilename`, which can be set before saving an asset to rename its file.
- Added `craft\helpers\App::craftDownloadUrl()`.
- Added `craft\helpers\App::isComposerInstall()`.
- Added `craft\helpers\App::majorMinorVersion()`.
- Added `craft\helpers\ArrayHelper::rename()`.
- Added `craft\helpers\Assets::editorImagePath()`.
- Added `craft\helpers\Db::isTypeSupported()`.
- Added `craft\helpers\Update::getBasePath()`.
- Added `craft\helpers\Update::parseManifestLine()`.
- Added `craft\helpers\Assets::getFileKindByExtension()`.
- Added `craft\helpers\Assets::getFileKindLabel()`.
- Added `craft\helpers\Assets::getFileKinds()`.
- Added `craft\image\Raster::flipHorizontally()`.
- Added `craft\image\Raster::flipVertically()`.
- Added `craft\services\Config::getAllowedFileExtensions()`.
- Added `craft\services\Config::getDbPort()`.
- Added `craft\services\Config::getUseWriteFileLock()`.
- Added `craft\services\Config::isExtensionAllowed()`.
- Added `craft\services\Elements::deleteElement()`.
- Added `craft\services\Elements::getElementTypesByIds()`.
- Added `craft\services\Images::getCanUseImagick()`.
- Added `craft\services\Images::getImageMagickApiVersion()`.
- Added `craft\services\Path::getImageEditorSourcesPath()`.
- Added `craft\services\Plugins::getPluginByModuleId()`.
- Added `craft\services\Plugins::getPluginByPackageName()`.
- Added `craft\services\Plugins::isComposerInstall()`.
- Added `craft\web\AssetManager::getPublishedPath()`.
- Added `craft\web\AssetManager::getPublishedUrl()`.
- Added `craft\web\Session::addAssetBundleFlash()`.
- Added `craft\web\Session::getAssetBundleFlashes()`.
- Added `craft\web\UploadedFile::saveAsTempFile()`.
- Added the `beforeDelete`, `afterDelete`, `beforeMoveInStructure`, and `afterMoveInStructure` events to `craft\base\Element`.
- Added the `beforeElementSave`, `afterElementSave`, `beforeElementDelete`, and `afterElementDelete` events to `craft\base\Field`.
- Added the `beforeRestoreBackup` and `afterRestoreBackup` events to `craft\db\Connection`.
- Added the `registerActions` event to `craft\base\Element`.
- Added the `registerAlerts` event to `craft\helpers\Cp`.
- Added the `registerFileKinds` event to `craft\helpers\Assets`.
- Added the `registerCacheOptions` event to `craft\tools\ClearCaches`.
- Added the `registerCpNavItems` event to `craft\web\twig\variables\Cp`.
- Added the `registerCpUrlRules` and `registerSiteUrlRules` events to `craft\web\UrlManager`.
- Added the `registerDefaultTableAttributes` event to `craft\base\Element`.
- Added the `registerElementTypes` event to `craft\services\Elements`.
- Added the `registerFieldTypes` event to `craft\services\Fields`.
- Added the `registerHtmlAttributes` event to `craft\base\Element`.
- Added the `registerLinkOptions` event to `craft\fields\RichText`.
- Added the `registerMailerTransportTypes` event to `craft\helpers\MailerHelper`.
- Added the `registerMessages` event to `craft\services\EmailMessages`.
- Added the `registerPermissions` event to `craft\services\UserPermissions`.
- Added the `registerSearchableAttributes` event to `craft\base\Element`.
- Added the `registerSortOptions` event to `craft\base\Element`.
- Added the `registerSources` event to `craft\base\Element`.
- Added the `registerTableAttributes` event to `craft\base\Element`.
- Added the `registerUserActions` event to `craft\controllers\UsersController`.
- Added the `registerVolumeTypes` event to `craft\services\Volumes`.
- Added the `registerWidgetTypes` event to `craft\services\Dashboard`.
- Added the `resolveResourcePath` event to `craft\services\Resources`.
- Added the `setFilename` event to `craft\helpers\Assets`.
- Added the `setRoute` event to `craft\base\Element`.
- Added the `setTableAttributeHtml` event to `craft\base\Element`.
- Added support for a `.readable` CSS class for views that are primarily textual content.
- Added a “Size” setting to Number fields.
- Added `d3FormatLocaleDefinition`, `d3TimeFormatLocaleDefinition`, `d3Formats` global JS variables.
- Added the `xAxis.showAxis`, `xAxis.formatter` and `yAxis.formatter` settings to the Area chart.
- Added `Craft.charts.BaseChart.setSettings()`.
- Added `Craft.charts.utils.getNumberFormatter()`.
- Added `Craft.charts.utils.getTimeFormatter()`.
- Added php-shellcommand.
- Added the ZendFeed library.
- Added the fabric.js JavaScript library.
- Added the d3-format JavaScript library.
- Added the d3-time-format JavaScriptlibrary.

### Changed
- The bootstrap script now assumes that the `vendor/` folder is 3 levels up from the `bootstrap/` directory by default (e.g. `vendor/craftcms/cms/bootstrap/`). If that is not the case (most likely because Craft had been symlinked into place), the `CRAFT_VENDOR_PATH` PHP constant can be used to correct that.
- The default `port` DB config value is now either `3306` (if MySQL) or `5432` (if PostgreSQL).
- The default `tablePrefix` DB config value is now empty.
- Renamed the `defaultFilePermissions` config setting to `defaultFileMode`, and it is now `null` by default.
- Renamed the `defaultFolderPermissions` config setting to `defaultDirMode`.
- Renamed the `useWriteFileLock` config setting to `useFileLocks`.
- Renamed the `backupDbOnUpdate` config setting to `backupOnUpdate`. Note that performance should no longer be a major factor when setting this to false, since the backup is no longer generated by PHP.
- Renamed the `restoreDbOnUpdateFailure` config setting to `restoreOnUpdateFailure`.
- File-based data caching now respects the `defaultDirMode` config setting.
- Redactor config files must now be valid JSON.
- When a category is deleted, its nested categories are no longer deleted with it.
- Craft Personal and Client editions are now allowed to have custom Volume types (e.g. Amazon S3).
- Renamed the “Get Help” widget to “Craft Support”.
- When editing a field whose type class cannot be found, Craft will now select Plain Text as a fallback and display a validation error on the Field Type setting.
- When editing a volume whose type class cannot be found, Craft will now select Local as a fallback and display a validation error on the Volume Type setting.
- When editing email settings and the previously-selected transport type class cannot be found, Craft will now select PHP Mail as a fallback and display a validation error on the Transport Type setting.
- The Feed widget is now limited to show 5 articles by default.
- Element queries’ `status` params must now always be set to valid statuses, or the query won’t return any results.
- Craft now relies on command line tools to create database backups (`mysqldump` and `pg_dump`).
- Test emails now mask the values for any Mailer transport type settings that include “key” or “password” in their setting name.
- The Control Panel page header is now fixed when scrolling down.
- Translatable fields are no longer marked as translatable when editing an element type that isn’t localizable (e.g. user accounts).
- Custom email messages are now stored on o per-language basis rather than per-site basis.
- Element indexes now remember which sources were expanded across multiple requests.
- Element indexes now remember if a nested source was selected across multiple requests.
- Plugin schema versions now default to `'1.0.0'`, and plugins absolutely must increment their schema version if they want any pending migrations to be noticed.
- Resource requests no longer serve files within Craft’s or plugins’ `resources/` folders.
- Renamed the `{% registercss %}` Twig tag to `{% css %}`.
- Renamed the `{% registerjs %}` Twig tag to `{% js %}`.
- `craft\base\Plugin` no longer automatically registers field types in the plugin’s `fields/` subfolder.
- `craft\base\Plugin` no longer automatically registers widget types in the plugin’s `widgets/` subfolder.
- `craft\base\Plugin` no longer automatically registers volume types in the plugin’s `volumes/` subfolder.
- `craft\elements\User` now supports a `password` validation scenario, which only validates the `$newPassword` property.
- `craft\elements\User` now supports a `registration` validation scenario, which only validates the `$username`, `$email`, and `$newPassword` properties.
- It is no longer possible to change a user’s locked/suspended/pending/archived status when saving the User element normally.
- `craft\elements\db\MatrixBlockQuery::owner()` and `ownerSiteId()` now set the `$siteId` property when appropriate.
- The source keys that are passed into element methods’ `$source` arguments now reflect the full path to the source, if it is a nested source (e.g. `folder:1/folder:2`).
- The `Craft.publishableSections` Javascript array now includes info about each section’s entry types.
- `craft\db\Connection::backup()` now throws an exception if something goes wrong, rather than returning `true` or `false`. If no exception is thrown, it worked.
- `craft\db\mysql\Schema::getTableNames()` no longer only returns the table names with the right table prefix.
- `craft\services\Elements::deleteElementById()` no longer accepts an array of element IDs.
- `craft\base\Element::afterSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\base\Element::beforeSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\base\Field::afterElementSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\base\Field::beforeElementSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\base\SavableComponent::afterSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\base\SavableComponent::beforeSave()` now has an `$isNew` argument, which will indicate whether the element is brand new.
- `craft\db\Connection::columnExists()`’s `$table` argument can now be a `craft\yii\db\TableSchema` object.
- `craft\elements\Asset::getFolder()` now throws a `yii\base\InvalidConfigException` if its `$folderId` property is set to an invalid folder ID.
- `craft\elements\Asset::getVolume()` now throws a `yii\base\InvalidConfigException` if its `$volumeId` property is set to an invalid volume ID.
- `craft\elements\Tag::getGroup()` now throws a `yii\base\InvalidConfigException` if its `$groupId` property is set to an invalid tag group ID.
- `craft\elements\User::getAuthor()` now throws a `yii\base\InvalidConfigException` if its `$authorId` property is set to an invalid user ID.
- `craft\elements\User::getPhoto()` now throws a `yii\base\InvalidConfigException` if its `$photoId` property is set to an invalid asset ID.
- `craft\models\FieldLayoutTab::getLayout()` now throws a `yii\base\InvalidConfigException` if its `$layoutId` property is set to an invalid field layout ID.
- `craft\services\Elements::deleteElementById()` now has `$elementType` and `$siteId` arguments.
- `craft\services\Element::getElementTypeById()` no longer accepts an array of element IDs. Use `getElementTypesByIds()` instead.
- `craft\services\Path::getAppPath()` now throws an exception if it is called within a Composer install, as there is no “app path”.
- The `beforeElementSave` and `afterElementSave` events triggered by `craft\base\Element` now have `$isNew` properties, which indicate whether the element is brand new.
- The `beforeSave` and `afterSave` events triggered by `craft\base\Element` now have `$isNew` properties, which indicate whether the element is brand new.
- The `beforeSave` and `afterSave` events triggered by `craft\base\SavableComponent` now have `$isNew` properties, which indicate whether the component is brand new.
- Renamed literally every Craft class’ namespace from `craft\app\*` to `craft\*`.
- Renamed `craft\base\Savable` to `Serializable`, and its `getSavableValue()` method was renamed to `serialize()`.
- Renamed `craft\et\Et` to `EtTransport`.
- Renamed `craft\events\DbBackupEvent` to `BackupEvent`.
- Renamed `craft\events\EntryEvent` to `VersionEvent`.
- Renamed `craft\events\Event` to `CancelableEvent`.
- Renamed `craft\helpers\Url` to `UrlHelper`.
- Renamed `craft\services\Feeds` to `craft\feeds\Feeds`.
- Renamed `craft\mail\transportadaptors\BaseTransportAdaptor` to `craft\mail\transportadapters\BaseTransportAdapter`.
- Renamed `craft\mail\transportadaptors\Gmail` to `craft\mail\transportadapters\Gmail`.
- Renamed `craft\mail\transportadaptors\Php` to `craft\mail\transportadapters\Php`.
- Renamed `craft\mail\transportadaptors\Sendmail` to `craft\mail\transportadapters\Sendmail`.
- Renamed `craft\mail\transportadaptors\Smtp` to `craft\mail\transportadapters\Smtp`.
- Renamed `craft\mail\transportadaptors\TransportAdaptorInterface` to `craft\mail\transportadapters\TransportAdapterInterface`.
- Renamed `craft\models\AppNewRelease` to `AppUpdateRelease`.
- Renamed `craft\models\PluginNewRelease` to `UpdateRelease`.
- Renamed `Craft::getCookieConfig()` to `cookieConfig()`.
- Renamed `craft\base\Element::defineAvailableTableAttributes()` to `tableAttributes()`.
- Renamed `craft\base\Element::defineSearchableAttributes()` to `searchableAttributes()`.
- Renamed `craft\base\Element::defineSortableAttributes()` to `sortOptions()`.
- Renamed `craft\base\Element::getAvailableActions()` to `actions()`, and the method must return an array now.
- Renamed `craft\base\Element::getContentPostLocation()` to `getFieldParamNamespace()`.
- Renamed `craft\base\Element::getDefaultTableAttributes()` to `defaultTableAttributes()`.
- Renamed `craft\base\Element::getEagerLoadingMap()` to `eagerLoadingMap()`.
- Renamed `craft\base\Element::getFieldByHandle()` to `fieldByHandle()`.
- Renamed `craft\base\Element::getFields()` to `fieldLayoutFields()`.
- Renamed `craft\base\Element::getIndexHtml()` to `indexHtml()`.
- Renamed `craft\base\Element::getSources()` to `sources()`.
- Renamed `craft\base\Element::getStatuses()` to `statuses()`, and the method must return an array now.
- Renamed `craft\base\Element::setContentPostLocation()` to `setFieldParamNamespace()`.
- Renamed `craft\base\Element::setFieldValuesFromPost()` to `setFieldValuesFromRequest()`, and the method no longer accepts an array of field values. Only call this method as a shortcut for `setFieldParamNamespace()` and `setFieldValues()`, passing in the param namespace the field values should be extracted from on the request body.
- Renamed `craft\base\Field::getContentPostLocation()` to `requestParamName()`.
- Renamed `craft\base\Field::prepareValue()` to `normalizeValue()`.
- Renamed `craft\base\Field::prepareValueForDb()` to `serializeValue()`.
- Renamed `craft\base\Plugin::getSettingsHtml()` to `settingsHtml()`.
- Renamed `craft\base\PluginInterface::getVariableDefinition()` to `defineTemplateComponent()`.
- Renamed `craft\base\Task::getDefaultDescription()` to `defaultDescription()`.
- Renamed `craft\base\Volume::getAdapter()` to `adapter()`.
- Renamed `craft\base\Volume::getFilesystem()` to `filesystem()`.
- Renamed `craft\base\Volume::getVisibilitySetting()` to `visibility()`.
- Renamed `craft\base\WidgetInterface::getMaxColspan()` to `maxColspan()` (now static).
- Renamed `craft\base\WidgetInterface::getIconPath()` to `iconPath()` (now static).
- Renamed `craft\controllers\BaseElementsController::getContext()` to `context()`.
- Renamed `craft\controllers\BaseElementsController::getElementType()` to `elementType()`.
- Renamed `craft\db\Command::insertOrUpdate()` to `upsert()`.
- Renamed `craft\db\Migration::insertOrUpdate()` to `upsert()`.
- Renamed `craft\db\mysql\QueryBuilder::insertOrUpdate()` to `upsert()`.
- Renamed `craft\elements\User::getAuthData()` to `authData()`
- Renamed `craft\fields\BaseOptionsField::getDefaultValue()` to `defaultValue()`.
- Renamed `craft\fields\BaseOptionsField::getOptionLabel()` to `optionLabel()`.
- Renamed `craft\fields\BaseOptionsField::getOptionsSettingsLabel()` to `optionsSettingLabel()`.
- Renamed `craft\fields\BaseOptionsField::getTranslatedOptions()` to `translatedOptions()`.
- Renamed `craft\fields\BaseRelationField::getAvailableSources()` to `availableSources()`.
- Renamed `craft\fields\BaseRelationField::getInputSelectionCriteria()` to `inputSelectionCriteria()`.
- Renamed `craft\fields\BaseRelationField::getInputSources()` to `inputSources()`.
- Renamed `craft\fields\BaseRelationField::getInputTemplateVariables()` to `inputTemplateVariables()`.
- Renamed `craft\fields\BaseRelationField::getSourceOptions()` to `sourceOptions()`.
- Renamed `craft\fields\BaseRelationField::getSupportedViewModes()` to `supportedViewModes()`, and the method must return an array now.
- Renamed `craft\fields\BaseRelationField::getTargetSiteFieldHtml()` to `targetSiteFieldHtml()`.
- Renamed `craft\fields\BaseRelationField::getTargetSiteId()` to `targetSiteId()`.
- Renamed `craft\fields\BaseRelationField::getViewMode()` to `viewMode()`.
- Renamed `craft\fields\BaseRelationField::getViewModeFieldHtml()` to `viewModeFieldHtml()`.
- Renamed `craft\helpers\App::getEditionName()` to `editionName()`.
- Renamed `craft\helpers\App::getEditions()` to `editions()`.
- Renamed `craft\helpers\App::getMajorVersion()` to `majorVersion()`.
- Renamed `craft\helpers\App::getPhpConfigValueAsBool()` to `phpConfigValueAsBool()`.
- Renamed `craft\helpers\App::getPhpConfigValueInBytes()` to `phpConfigValueInBytes()`.
- Renamed `craft\helpers\ArrayHelper::getFirstKey()` to `firstKey()`.
- Renamed `craft\helpers\Assets::getFileTransferList()` to `fileTransferList()`.
- Renamed `craft\helpers\Assets::getPeriodList()` to `periodList()`.
- Renamed `craft\helpers\Assets::getTempFilePath()` to `tempFilePath()`.
- Renamed `craft\helpers\Assets::getUrlAppendix()` to `urlAppendix()`.
- Renamed `craft\helpers\ChartHelper::getCurrencyFormat()` to `currencyFormat()`.
- Renamed `craft\helpers\ChartHelper::getDateRanges()` =>dateRanges()
- Renamed `craft\helpers\ChartHelper::getDecimalFormat()` =>decimalFormat()
- Renamed `craft\helpers\ChartHelper::getFormats()` to `formats()`.
- Renamed `craft\helpers\ChartHelper::getPercentFormat()` to `percentFormat()`.
- Renamed `craft\helpers\ChartHelper::getShortDateFormats()` to `shortDateFormats()`.
- Renamed `craft\helpers\Cp::getAlerts()` to `alerts()`.
- Renamed `craft\helpers\ElementHelper::getEditableSiteIdsForElement()` to `editableSiteIdsForElement()`.
- Renamed `craft\helpers\ElementHelper::getSupportedSitesForElement()` to `supportedSitesForElement()`.
- Renamed `craft\helpers\Image::getImageSize()` to `imageSize()`.
- Renamed `craft\helpers\Image::getPngImageInfo()` to `pngImageInfo()`.
- Renamed `craft\helpers\Image::getWebSafeFormats()` to `webSafeFormats()`.
- Renamed `craft\helpers\Localization::getLocaleData()` to `localeData()`.
- Renamed `craft\helpers\MailerHelper::getAllMailerTransportTypes()` to `allMailerTransportTypes()`.
- Renamed `craft\helpers\Search::getMinWordLength()` to `minWordLength()`.
- Renamed `craft\helpers\Search::getStopWords()` to `stopWords()`.
- Renamed `craft\helpers\StringHelper::getAsciiCharMap()` to `asciiCharMap()`.
- Renamed `craft\helpers\StringHelper::getCharAt()` to `charAt()`.
- Renamed `craft\helpers\StringHelper::getEncoding()` to `encoding()`.
- Renamed `craft\helpers\StringHelper::uppercaseFirst()` to `upperCaseFirst()`.
- Renamed `craft\helpers\Template::getRaw()` to `raw()`.
- Renamed `craft\helpers\UrlHelper::getActionUrl()` to `actionUrl()`.
- Renamed `craft\helpers\UrlHelper::getCpUrl()` to `cpUrl()`.
- Renamed `craft\helpers\UrlHelper::getResourceUrl()` to `resourceUrl()`.
- Renamed `craft\helpers\UrlHelper::getSiteUrl()` to `siteUrl()`.
- Renamed `craft\helpers\UrlHelper::getUrl()` to `url()`.
- Renamed `craft\helpers\UrlHelper::getUrlWithParams()` to `urlWithParams()`.
- Renamed `craft\helpers\UrlHelper::getUrlWithProtocol()` to `urlWithProtocol()`.
- Renamed `craft\helpers\UrlHelper::getUrlWithToken()` to `urlWithToken()`.
- Renamed `craft\mail\transportadapters\TransportAdapterInterface::getTransportConfig()` to `defineTransport()`, and it is now called at runtime when configuring the Mailer app component, rather than only when email settings are saved.
- Renamed `craft\models\AssetTransform::getTransformModes()` to `modes()`.
- Renamed `craft\services\Assets::renameAsset()` to `renameFile()`, and replaced its `$newFilename` argument with `$runValidation`.
- Renamed `craft\services\Config::omitScriptNameInUrls()` to `getOmitScriptNameInUrls()`.
- Renamed `craft\services\Config::usePathInfo()` to `getUsePathInfo()`.
- Renamed `craft\services\Resources::getResourcePath()` to `resolveResourcePath()`.
- Renamed `craft\volumes\AwsS3::getClient()` to `client()`.
- Renamed `craft\volumes\AwsS3::getStorageClasses()` to `storageClasses()`.
- Renamed `craft\volumes\GoogleCloud::getClient()` to `client()`.
- Renamed `craft\volumes\Rackspace::getClient()` to `client()`.
- Renamed `craft\web\assets\AppAsset` to `craft\web\assets\cp\CpAsset`.
- Renamed `craft\web\assets\D3Asset` to `craft\web\assets\d3\D3Asset`.
- Renamed `craft\web\assets\ElementResizeDetectorAsset` to `craft\web\assets\elementresizedetector\ElementResizeDetectorAsset`.
- Renamed `craft\web\assets\GarnishAsset` to `craft\web\assets\garnish\GarnishAsset`.
- Renamed `craft\web\assets\JqueryPaymentAsset` to `craft\web\assets\jquerypayment\JqueryPaymentAsset`.
- Renamed `craft\web\assets\JqueryTouchEventsAsset` to `craft\web\assets\jquerytouchevents\JqueryTouchEventsAsset`.
- Renamed `craft\web\assets\PicturefillAsset` to `craft\web\assets\picturefill\PicturefillAsset`.
- Renamed `craft\web\assets\SelectizeAsset` to `craft\web\assets\selectize\SelectizeAsset`.
- Renamed `craft\web\assets\TimepickerAsset` to `craft\web\assets\timepicker\TimepickerAsset`.
- Renamed `craft\web\assets\VelocityAsset` to `craft\web\assets\velocity\VelocityAsset`.
- Renamed `craft\web\twig\variables\CraftVariable::getLocale()` back to `locale()`.
- Moved `craft\volumes\VolumeInterface::getRootPath()` to `craft\volumes\LocalVolumeInterface::getRootPath()`.
- `craft\base\Element::getEditorHtml()` is no longer static, and no longer has an `$element` argument.
- `craft\base\Element::getElementRoute()` is no longer static, no longer has an `$element` argument, and has been renamed to `getRoute()`.
- `craft\base\Element::getElementQueryStatusCondition()` has been moved to `craft\elements\db\ElementQuery::statusCondition()`, and no longer has a `$query` argument.
- `craft\base\Element::getFieldsForElementQuery()` has been moved to `craft\elements\db\ElementQuery::customFields()`, and no longer has a `$query` argument.
- `craft\base\Element::getTableAttributeHtml()` is no longer static, and no longer has an `$element` argument.
- `craft\base\Element::onAfterMoveElementInStructure()` is no longer static, no longer has an `$element` argument, and has been renamed to `afterMoveInStructure()`.
- `craft\services\AssetIndexer::getIndexEntry()` now returns `null` if the index doesn’t exist, instead of `false`.
- `craft\services\Updates::getUnwritableFolders()` now returns folder paths without trailing slashes.
- `craft\web\Session::addJsFlash()` now has `$position` and `$key` arguments.
- `craft\web\Session::getJsFlashes()` now returns an array of nested arrays, each defining the JS code, the position, and the key.
- `craft\web\View::getTwig()` no longer has `$loaderClass` or `$options` arguments.
- Renamed `craft.getAssets()` back to `craft.assets()`.
- Renamed `craft.getCategories()` back to `craft.categories()`.
- Renamed `craft.getEntries()` back to `craft.entries()`.
- Renamed `craft.getTags()` back to `craft.tags()`.
- Renamed `craft.getUsers()` back to `craft.users()`.
- Moved `numberFormat`, `percentFormat` and `currencyFormat` from `ChartHelper` to the `Craft.charts.BaseChart` default settings.
- Improved `Craft.charts.BaseChart` and `Craft.charts.Area` settings.
- Renamed `xAxisGridlines` setting to `xAxis.gridlines` and `yAxisGridlines` setting to `yAxis.gridlines` for the Area chart.
- Renamed `axis.y.show` setting to `yAxis.showAxis` for the Area chart.
- Renamed the `enablePlots` Area chart setting to `plots`.
- Renamed the `enableTips` Area chart setting to `tips`.
- All Craft and library dependencies that make remote calls use Craft's centralized Guzzle instance.
- Updated Yii to 2.0.10.
- Updated Yii 2 Debug Extension to 2.0.7.
- Updated Yii 2 SwiftMailer to 2.0.6.
- Updated Twig to 2.1.0.
- Updated Guzzle to 6.2.2.
- Updated D3 to 4.5.0.0.
- Updated Imagine to the new `pixelandtonic/imagine` fork at 0.6.3.2.
- Updated Garnish to 0.1.12.
- Updated Velocity to 1.4.2.
- Updated element-resize-detector.js to 1.1.10.
- Updated flysystem to 1.0.34.
- Updated qUnit to 2.1.1.
- Updated Redactor to 1.4.
- Craft no longer requires the mcrypt PHP extension.
- Improved the way the height of sidebars is calculated for panes with no tabs
- Moved Utilities nav item to keep Settings as the last item

### Deprecated
- The `getTranslations()` global Twig function has been deprecated. Use `craft.app.view.getTranslations()` instead.
- `craft\web\View::registerHiResCss()` has been deprecated. Use `registerCss()` instead, and type your own media selector.

### Removed
- Removed support for the `CRAFT_FRAMEWORK_PATH` PHP constant in the bootstrap script. It is now expected Yii is located alongside Craft and other dependencies in the `vendor/` folder.
- Removed support for the `environmentVariables` config setting. Use the `siteUrl` config setting in `config/general.php` to set the site URL, and override volume settings with `config/volumes.php`.
- Removed support for Yii 1-style controller action paths (e.g. `entries/saveEntry`), which were previously deprecated. Use the Yii 2 style instead (e.g. `entries/save-entry`).
- Removed the deprecated `activateAccountFailurePath` config setting.
- Removed the `appId` config setting.
- Removed the `collation` DB config setting.
- Removed the `initSQLs` DB config setting.
- Removed the `{% registerassetbundle %}` Twig tag. Use `{% do view.registerAssetBundle("class\\name") %}` instead.
- Removed the `{% registercssfile %}` Twig tag. Use `{% do view.registerCssFile("/url/to/file.css") %}` instead.
- Removed the `{% registercssresource %}` and `{% includecssresource %}` Twig tags.
- Removed the `{% registerhirescss %}` Twig tag. Use `{% css %}` instead, and type your own media selector.
- Removed the `{% registerjsfile %}` Twig tag. Use `{% do view.registerJsFile("/url/to/file.js") %}` instead.
- Removed the `{% registerjsresource %}` and `{% includejsresource %}` Twig tags.
- Removed the `{% endpaginate %}` Twig tag as it’s unnecessary.
- Removed the `childOf`, `childField`, `parentOf`, and `parentField` element query params. Use `relatedTo` instead.
- Removed the `depth` element query param. Use `level` instead.
- Removed the `name` tag query param. Use `title` instead.
- Removed support for passing `"name"` into the `orderBy` tag query param. Pass `"title"` instead.
- Removed the PEL library.
- Removed the PclZip library.
- Removed the SimplePie library.
- Removed support for EXIF data removal and automatic image rotating for servers without ImageMagick installed.
- Removed the automatic creation of `@craft/plugins/HANDLE` aliases for installed plugins.
- Removed `craft\base\Tool`.
- Removed `craft\base\ToolInterface`.
- Removed `craft\cache\FileCache`.
- Removed `craft\cache\adapters\GuzzleCacheAdapter`.
- Removed `craft\controllers\ToolsController`.
- Removed `craft\dates\DateTime`.
- Removed `craft\dates\DateInterval`.
- Removed `craft\db\DbBackup`.
- Removed `craft\enums\BaseEnum`.
- Removed `craft\errors\DbBackupException`.
- Removed `craft\errors\ErrorException`.
- Removed `craft\errors\InvalidateCacheException`.
- Removed `craft\events\CategoryEvent`.
- Removed `craft\events\BackupFailureEvent`.
- Removed `craft\events\DeleteUserEvent`.
- Removed `craft\events\EntryDeleteEvent`.
- Removed `craft\events\RestoreFailureEvent`.
- Removed `craft\events\UserEvent`.
- Removed `craft\helpers\Io`.
- Removed `craft\log\EmailTarget`.
- Removed `craft\models\AccountSettings`
- Removed `craft\models\Username`.
- Removed `craft\io\BaseIO`.
- Removed `craft\io\File`.
- Removed `craft\io\Folder`.
- Removed `craft\io\PclZip`.
- Removed `craft\io\Zip`.
- Removed `craft\io\ZipArchive`.
- Removed `craft\io\ZipInterface`.
- Removed `craft\models\LogEntry`.
- Removed `craft\models\Password`.
- Removed `craft\web\twig\StringTemplate`.
- Removed `craft\base\Component::getType()`. It was only really there for objects that implement `craft\base\MissingComponentInterface`, and now they have an `$expectedType` property.
- Removed `craft\base\ComponentInterface::classHandle()`.
- Removed `craft\base\Element::getContentFromPost()`.
- Removed `craft\base\Element::getSourceByKey()`.
- Removed `craft\base\Element::saveElement()`.
- Removed `craft\base\Element::setRawPostValueForField()`.
- Removed `craft\base\Field::getElementValue()`.
- Removed `craft\base\Field::setElementValue()`.
- Removed `craft\base\FieldInterface::validateValue()`. Fields should start implementing `getElementValidationRules()` if they want to customize how their values get validated.
- Removed `craft\base\Model::create()`.
- Removed `craft\base\Model::getAllErrors()`.
- Removed `craft\base\Model::populateModel()`.
- Removed `craft\base\Plugin::$releaseFeedUrl`. Plugins that wish to have update notifications should now set `$changelogUrl`.
- Removed `craft\base\Plugin::getClassesInSubpath()`.
- Removed `craft\base\Plugin::getFieldTypes()`.
- Removed `craft\base\Plugin::getVolumeTypes()`.
- Removed `craft\base\Plugin::getWidgetTypes()`.
- Removed `craft\base\PluginInterface::hasCpSection()`. Plugins that have a CP section should set the `$hasCpSection` property.
- Removed `craft\base\VolumeInterface::isLocal()`. Local volumes should implement `craft\base\LocalVolumeInterface` instead.
- Removed `craft\db\Command::addColumnAfter()`.
- Removed `craft\db\Command::addColumnBefore()`.
- Removed `craft\db\Command::addColumnFirst()`.
- Removed `craft\db\Migration::addColumnAfter()`.
- Removed `craft\db\Migration::addColumnBefore()`.
- Removed `craft\db\Migration::addColumnFirst()`.
- Removed `craft\db\mysql\QueryBuilder::addColumnAfter()`.
- Removed `craft\db\mysql\QueryBuilder::addColumnBefore()`.
- Removed `craft\db\mysql\QueryBuilder::addColumnFirst()`.
- Removed `craft\db\Query::scalar()`.
- Removed `craft\db\Query::column()`.
- Removed `craft\elements\db\ElementQuery::configure()`.
- Removed `craft\elements\Tag::getName()`. Use the `title` property instead.
- Removed `craft\helpers\ArrayHelper::getFirstValue()`.
- Removed `craft\helpers\Json::removeComments()`.
- Removed `craft\helpers\MigrationHelper::makeElemental()`.
- Removed `craft\helpers\MigrationHelper::refresh()`.
- Removed `craft\helpers\MigrationHelper::restoreAllForeignKeysOnTable()`.
- Removed `craft\helpers\MigrationHelper::restoreAllIndexesOnTable()`.
- Removed `craft\helpers\MigrationHelper::restoreAllUniqueIndexesOnTable()`.
- Removed `craft\helpers\Update::cleanManifestFolderLine()`.
- Removed `craft\helpers\Update::isManifestLineAFolder()`.
- Removed `craft\services\Assets::deleteAssetsByIds()`.
- Removed `craft\services\Assets::deleteCategory()`.
- Removed `craft\services\Assets::deleteCategoryById()`.
- Removed `craft\services\Assets::findAsset()`.
- Removed `craft\services\Assets::findAssets()`.
- Removed `craft\services\Assets::getFilesByVolumeId()`.
- Removed `craft\services\Categories::saveCategory()`.
- Removed `craft\services\Content::validateContent()`.
- Removed `craft\services\Entries::deleteEntry()`.
- Removed `craft\services\Entries::deleteEntryById()`.
- Removed `craft\services\Entries::saveEntry()`.
- Removed `craft\services\Globals::deleteSetById()`.
- Removed `craft\services\Globals::saveContent()`.
- Removed `craft\services\Images::getIsImagickAtLeast()`.
- Removed `craft\services\Matrix::deleteBlockById()`.
- Removed `craft\services\Matrix::saveBlock()`.
- Removed `craft\services\Matrix::validateBlock()`.
- Removed `craft\services\Path::getMigrationsPath()`.
- Removed `craft\services\Path::getResourcesPath()`.
- Removed `craft\services\Plugins::call()`.
- Removed `craft\services\Plugins::callFirst()`.
- Removed `craft\services\SystemSettings::getCategoryTimeUpdated()`.
- Removed `craft\services\Tags::saveTag()`.
- Removed `craft\services\Updates::flushUpdateInfoFromCache()`.
- Removed `craft\services\Users::changePassword()`.
- Removed `craft\services\Users::deleteUser()`.
- Removed `craft\tools\*`.
- Removed `craft\web\Session::addJsResourceFlash()`.
- Removed `craft\web\Session::getJsResourceFlashes()`.
- Removed `craft\web\twig\variables\CraftVariable::getTimeZone()`.
- Removed `craft\web\twig\variables\Fields::createField()`.
- Removed `craft\web\View::registerCssResource()`.
- Removed `craft\web\View::registerJsResource()`.
- Removed the `$attribute` argument from `craft\base\ApplicationTrait::getInfo()`.
- Removed the `$except` argument from `craft\base\Element::getFieldValues()`.
- Removed the `$indexBy` argument from `craft\elements\User::getGroups()`.
- Removed the `$indexBy` argument from `craft\models\Section::getEntryTypes()`.
- Removed the `$indexBy` argument from `craft\services\AssetTransforms::getAllTransforms()`.
- Removed the `$indexBy` argument from `craft\services\Categories::getGroupSiteSettings()`.
- Removed the `$indexBy` argument from `craft\services\CategoryGroups::getAllGroups()`.
- Removed the `$indexBy` argument from `craft\services\CategoryGroups::getEditableGroups()`.
- Removed the `$indexBy` argument from `craft\services\Dashboard::getAllWidgets()`.
- Removed the `$indexBy` argument from `craft\services\Fields::getAllFields()`.
- Removed the `$indexBy` argument from `craft\services\Fields::getAllGroups()`.
- Removed the `$indexBy` argument from `craft\services\Fields::getFieldsByElementType()`.
- Removed the `$indexBy` argument from `craft\services\Fields::getFieldsByGroupId()`.
- Removed the `$indexBy` argument from `craft\services\Globals::getAllSets()`.
- Removed the `$indexBy` argument from `craft\services\Globals::getEditableSets()`.
- Removed the `$indexBy` argument from `craft\services\Matrix::getBlockTypesByFieldId()`.
- Removed the `$indexBy` argument from `craft\services\Sections::getAllSections()`.
- Removed the `$indexBy` argument from `craft\services\Sections::getEditableSections()`.
- Removed the `$indexBy` argument from `craft\services\Sections::getSectionSiteSettings()`.
- Removed the `$indexBy` argument from `craft\services\Sections::getEntryTypesBySectionId()`.
- Removed the `$indexBy` argument from `craft\services\Sites::getAllSites()`.
- Removed the `$indexBy` argument from `craft\services\Sites::getEditableSites()`.
- Removed the `$indexBy` argument from `craft\services\Tags::getAllTagGroups()`.
- Removed the `$indexBy` argument from `craft\services\UserGroups::getAllGroups()`.
- Removed the `$indexBy` argument from `craft\services\UserGroups::getGroupsByUserId()`.
- Removed the `$indexBy` argument from `craft\services\Volumes::getViewableVolumes()`.
- Removed the `$indexBy` argument from `craft\services\Volumes::getPublicVolumes()`.
- Removed the `$indexBy` argument from `craft\services\Volumes::getAllVolumes()`.
- Removed the `$newName` and `$after` arguments from `craft\db\Command::alterColumn()`.
- Removed the `$newName` and `$after` arguments from `craft\db\Migration::alterColumn()`.
- Removed the `$newName` and `$after` arguments from `craft\db\mysql\QueryBuilder::alterColumn()`.
- Removed the `$runValidation` argument from `craft\services\Content::saveContent()`.
- Removed the `$params` argument from `craft\helpers\Db::parseDateParam()`.
- Removed the `$params` argument from `craft\helpers\Db::parseParam()`.
- Removed the `beforeDeleteAsset`, `afterDeleteAsset`, `beforeSaveAsset` and `afterSaveAsset` events from `craft\services\Assets`.
- Removed the `beforeDeleteCategory`, `afterDeleteCategory`, `beforeSaveCategory` and `afterSaveCategory` events from `craft\services\Categories`.
- Removed the `beforeDeleteEntry`, `afterDeleteEntry`, `beforeSaveEntry` and `afterSaveEntry` events from `craft\services\Entry`.
- Removed the `beforeDeleteGlobalSet`, `beforeDeleteGlobalSet`, `beforeSaveGlobalContent` and `afterSaveGlobalContent` events from `craft\services\Globals`.
- Removed the `beforeDeleteUser`, `afterDeleteUser`, `beforeSaveUser`, `afterSaveUser`, `beforeSetPassword`, and `afterSetPassword` events from `craft\services\Users`.
- Removed the `backupFailure` event from `craft\db\Connection`.
- Removed the `beforeSaveTag` and `afterSaveTag` events from `craft\services\Tags`.
- Removed the `addRichTextLinkOptions` plugin hook. Custom Rich Text field link options should be registered using the `registerLinkOptions` event on `craft\fields\RichText` now.
- Removed the `addTwigExtension` plugin hook. Custom Twig extensions should be added by calling `Craft::$app->view->twig->addExtension()` directly.
- Removed the `addUserAdministrationOptions` plugin hook. Custom actions for the Edit User page should be registered using the `registerUserActions` event on `craft\controllers\UsersController` now.
- Removed the `defineAdditionalAssetTableAttributes`, `defineAdditionalCategoryTableAttributes`, `defineAdditionalEntryTableAttributes`, and `defineAdditionalUserTableAttributes` plugin hooks. Custom table attributes should be registered using the `registerTableAttributes` event on `craft\base\Element` or one of its subclasses now.
- Removed the `defineAssetActions`, `defineCategoryActions`, `defineEntryActions`, and `defineUserActions` plugin hooks. Custom element actions should be registered using the `registerActions` event on `craft\base\Element` or one of its subclasses now.
- Removed the `getAssetTableAttributeHtml`, `getCategoryTableAttributeHtml`, `getEntryTableAttributeHtml`, and `getUserTableAttributeHtml` plugin hooks. Table attribute HTML should be overridden using the `setTableAttributeHtml` event on `craft\base\Element` or one of its subclasses now.
- Removed the `getCpAlerts` plugin hook. Custom Control Panel alerts should be registered using the `registerAlerts` event on `craft\helpers\Cp` now.
- Removed the `getElementRoute` plugin hook. Element routes should be overridden using the `setRoute` event on `craft\base\Element` or one of its subclasses now.
- Removed the `getFieldTypes` plugin hook. Custom field types should be registered using the `registerFieldTypes` event on `craft\services\Fields` now.
- Removed the `getMailTransportAdapters` plugin hook. Custom transport types should be registered using the `registerMailerTransportTypes` event on `craft\helpers\MailerHelper` now.
- Removed the `getResourcePath` plugin hook. Custom resource URIs should be resolved to file paths using the `resolveResourcePath` event on `craft\services\Resources` now.
- Removed the `getTableAttributesForSource` plugin hook.
- Removed the `getVolumeTypes` plugin hook. Custom volume types should be registered using the `registerVolumeTypes` event on `craft\services\Volumes` now.
- Removed the `getWidgetTypes` plugin hook. Custom widget types should be registered using the `registerWidgetTypes` event on `craft\services\Dashboard` now.
- Removed the `modifyAssetFilename` plugin hook. Asset filenames should be overridden using the `setFilename` event on `craft\helpers\Assets` now.
- Removed the `modifyAssetSortableAttributes`, `modifyCategorySortableAttributes`, `modifyEntrySortableAttributes`, and `modifyUserSortableAttributes` plugin hooks. Sortable attribute modifications should be made using the `registerSortableAttributes` event on `craft\base\Element` or one of its subclasses now.
- Removed the `modifyAssetSources`, `modifyCategorySources`, `modifyEntrySources`, and `modifyUserSources` plugin hooks. Element source modifications should be made using the `registerSources` event on `craft\base\Element` or one of its subclasses now.
- Removed the `modifyCpNav` plugin hook. Control Panel nav modifications should be made using the `registerCpNavItems` event on `craft\web\twig\variables\Cp` now.
- Removed the `registerCachePaths` plugin hook. Custom options for the Clear Caches tool (which can be set to callbacks now in addition to file paths) should be registered using the `registerCacheOptions` event on `craft\tools\ClearCaches` now.
- Removed the `registerCpRoutes` and `registerSiteRoutes` plugin hooks. Custom URL rules for the Control Panel and front-end site should be registered using the `registerCpUrlRules` and `registerSiteUrlRules` events on `craft\web\UrlManager` now.
- Removed the `registerEmailMessages` plugin hook. Custom email messages should be registered using the `registerMessages` event on `craft\services\EmailMessages` now.
- Removed the `registerUserPermissions` plugin hook. Custom user permissions should be registered using the `registerPermissions` event on `craft\services\UserPermissions` now.
- Removed the `craft\requirements` folder.  It is now a composer dependency.
- Removed the `Craft.charts.utils.applyShadowFilter()` JavaScript method.
- Removed the `Craft.charts.utils.arrayToDataTable()` JavaScript method.

### Fixed
- Fixed a bug where custom 503 templates weren’t rendering when Craft was in the middle of updating from an earlier version than 3.0.2933.
- Fixed a validation error that occurred when saving a field.
- Fixed a PHP error that occurred when using the `{% header %}` Twig tag.
- Fixed the Generate Pending Transforms task creation that occurs in `craft\services\Assets::getUrlForAsset()`.
- Fixed a PHP error that occurred when viewing entry revisions that were created before updating to Craft 3.
- Fixed a bug where brand new elements were not getting their `$uid` property set on them after getting saved.
- Fixed a bug where user activation emails that were sent immediately after creating the user account were getting an invalid activation URL.
- Fixed a PHP error that occurred when saving a Structure section entry with a new parent.
- Fixed a bug where entry titles were required for validation even if the entry type didn’t opt to show the title field.
- Fixed a bug where the `users/edit-user` controller action wasn’t respecting the passed in User object, if there was one, so validation errors weren’t getting reported.
- Fixed an error that occurred when the Control Panel attempted to start running background tasks if there were no tasks queued up.
- Fixed a bug where Recent Entries widgets would lose their “Locale” (now “Site”) setting values when upgrading from an older version of Craft.
- Fixed a bug where the Dashboard was allowing users to add widgets that were calling themselves unselectable.
- Fixed a bug where sessions table rows weren’t getting deleted after users logged out.
- Fixed a bug where the `fixedOrder` parameter wasn’t being respected for entry queries.
- Fixed a bug where plugin-supplied custom fields weren’t working.
- Fixed a PHP error that occurred when opening an element editor.
- Fixed a PHP error that occurred when re-saving a category group.
- Fixed a PHP error that occurred when using the `{% exit %}` tag with a specific status code.
- Fixed authorization error that occurred when editing an entry in a section that’s not enabled for the current site.
- Fixed a PHP error when using the `{% cache %}` tag.
- Fixed an error that occurred when clicking on an email message to edit it.
- Fixed an error that occurred when `craft\cache\FileCache::setValue()` was called and the destination folder already existed.
- Fixed support for the `testToEmailAddress` config setting.
- Fixed a bug where the `tasks/run-pending-tasks` controller action was requiring an authenticated session.
- Fixed a PHP error that occurred when saving a Recent Entries widget.
- Fixed a bug where Recent Entries widgets’ Site and Limit settings weren’t being validated correctly.
- Fixed a bug where widget settings errors were getting reported twice.
- Fixed a bug where console requests were only working when running the Pro edition.
- Fixed a bug where the Instructions setting within newly-created sub-fields in a Matrix field’s settings were getting marked as required.
- Fixed a bug where custom nested element sources registered by plugins were not getting Admin-defined custom table attributes.
- Fixed a bug where searching by an `"exact phrase"` wasn’t working.
- Fixed a bug where the Backup Database tool wasn’t downloading the DB backup if the “Download backup?” checkbox was checked.
- Fixed a bug where the requirements checker wasn’t taking into account MySQL/PostgreSQL installs running on non-standard ports.
- Fixed a bug where you’d get a fatal PHP error during an update if you didn’t meet one of Craft’s requirements.
- Fixed a bug where you’d get a database error when saving a private Assets Volume.
- Fixed a bug where linking to an entry or category from a Rich Text field wasn’t working.
- Fixed `Plugins::validateConfig()`’s nulls.
- Fixed a bug where JavaScript flashes weren’t getting registered on the subsequent page.
- Fixed a bug where `craft\db\Connection::columnExists()` wasn’t returning `true` if the column existed.

## 3.0.0-alpha.2948 - 2016-09-29

### Added
- Added `craft\i18n\Locale::getNumberPattern()`.
- Added `craft\web\Request::getValidatedBodyParam()`.
- Added `craft\services\Fields::deleteGroup()`.
- Added `craft\services\Fields::deleteLayout()`.
- Added `craft\services\Sections::deleteSection()`.
- Added `craft\services\Sections::deleteEntryType()`.
- Added `craft\services\Sites::deleteSite()`.
- Added `craft\services\Volumes::deleteVolume()`.
- Added `craft\base\SavableComponent::beforeSave()`.
- Added `craft\base\SavableComponent::afterSave()`.
- Added `craft\base\SavableComponent::beforeDelete()`.
- Added `craft\base\SavableComponent::afterDelete()`.
- Added the `beforeSave`, `afterSave`, `beforeDelete`, and `afterDelete` events to `craft\base\SavableComponent`.
- Added the `beforeSaveWidget`, `afterSaveWidget`, `beforeDeleteWidget`, and `afterDeleteWidget` events to `craft\services\Dashboard`.
- Added the `beforeSaveFieldLayout`, `beforeDeleteFieldLayout`, `afterDeleteFieldLayout`, `beforeSaveFieldGroup`, `afterSaveFieldGroup`, `beforeDeleteFieldGroup`, `afterDeleteFieldGroup`, `beforeSaveField`, `afterSaveField`, `beforeDeleteField`, and `afterDeleteField` events to `craft\services\Fields`.
- Added the `beforeLoadPlugins`, `beforeEnablePlugin`, `afterEnablePlugin`, `beforeDisablePlugin`, `afterDisablePlugin`, `beforeInstallPlugin`, `afterInstallPlugin`, `beforeUninstallPlugin`, `afterUninstallPlugin`, `beforeSavePluginSettings`, and `afterSavePluginSettings` events to `craft\services\Plugins`.
- Added the `beforeSaveRoute`, `afterSaveRoute`, `beforeDeleteRoute`, and `afterDeleteRoute` events to `craft\services\Routes`.
- Added the `beforeSearch` and `afterSearch` events to `craft\services\Search`.
- Added the `beforeSaveTask`, `afterSaveTask`, `beforeDeleteTask`, and `afterDeleteTask` events to `craft\services\Tasks`.
- Added the `loginFailure` event to `craft\controllers\UsersController` (replacing the like-named event on `craft\elements\User`).

### Changed
- Updated the Intl fallback data based on ICU 56.1. If you have any additional locale data files in `locales/`, you should [update them](https://github.com/craftcms/locales), too.
- Ported recent changes from Craft 2.
- The `beforeSaveAssetTransform` event on `craft\services\AssetTransforms` no longer supports an `$isValid` property.
- The `beforeDeleteAssetTransform` event on `craft\services\AssetTransforms` no longer supports a `$isValid` property.
- The `beforeSaveCategory` event on `craft\services\Categories` no longer supports an `$isValid` property.
- The `beforeDeleteGroup` event on `craft\services\Categories` no longer supports an `$isValid` property.
- The `beforeDeleteSection` event on `craft\services\Sections` no longer supports an `$isValid` property.
- The `beforeSaveEntryType` event on `craft\services\Sections` no longer supports an `$isValid` property.
- The `beforeDeleteEntryType` event on `craft\services\Sections` no longer supports an `$isValid` property.
- The `beforeReorderSites` event on `craft\services\Sites` no longer supports an `$isValid` property.
- The `beforeMoveElement` event on `craft\services\Structures` no longer supports an `$isValid` property.
- The `beforeSaveTag` event on `craft\services\Tags` no longer supports an `$isValid` property.
- The `beforeSaveGroup` event on `craft\services\Tags` no longer supports an `$isValid` property.
- The `beforeDeleteGroup` event on `craft\services\Tags` no longer supports an `$isValid` property.
- The `beforeSaveUserGroup` event on `craft\services\UserGroups` no longer supports an `$isValid` property.
- The `beforeDeleteUserGroup` event on `craft\services\UserGroups` no longer supports an `$isValid` property.
- The `beforeSaveVolume` event on `craft\services\Volumes` no longer supports an `$isValid` property.
- The `beforeDeleteVolume` event on `craft\services\Volumes` no longer supports an `$isValid` property.
- `craft\services\AssetTransforms::saveTransform()` now accepts a `$runValidation` argument.
- `craft\services\Categories::saveCategory()` now accepts a `$runValidation` argument.
- `craft\services\Entries::saveEntry()` now accepts a `$runValidation` argument.
- `craft\services\Fields::saveField()` now accepts a `$runValidation` argument.
- `craft\services\Fields::saveGroup()` now accepts a `$runValidation` argument.
- `craft\services\Fields::saveLayout()` now accepts a `$runValidation` argument.
- `craft\services\Tags::saveTag()` now accepts a `$runValidation` argument.
- `craft\services\Tags::saveTagGroup()` now accepts a `$runValidation` argument.
- `craft\services\UserGroups::saveGroup()` now accepts a `$runValidation` argument.
- `craft\services\Volumes::saveVolume()` now accepts a `$runValidation` argument.
- `craft\services\Sections::deleteEntryTypeById()` no longer accepts an array of entry type IDs.
- Renamed `craft\validators\DateTime` to `DateTimeValidator`.
- Renamed `craft\validators\Handle` to `HandleValidator`.
- Renamed `craft\validators\SingleSectionUri` to `SingleSectionUriValidator`.
- Renamed `craft\validators\SiteId` to `SiteIdValidator`.
- Renamed `craft\validators\Unique` to `UniqueValidator`.
- Renamed `craft\validators\Uri` to `UriValidator`.
- Renamed `craft\validators\Url` to `UrlValidator`.
- Renamed `craft\validators\UriFormat` to `UriFormatValidator`.
- Deleted the `craft\services\Volumes::VOLUME_INTERFACE` constant.
- Deleted the `craft\services\Tasks::TASK_INTERFACE` constant.
- Deleted the `craft\services\Fields::FIELD_INTERFACE` constant.
- Deleted the `craft\services\Elements::ELEMENT_INTERFACE` constant.
- Deleted the `craft\services\Elements::ACTION_INTERFACE` constant.
- Deleted the `craft\services\Dashboard::WIDGET_INTERFACE` constant.
- Deleted `craft\enums\ConfigCategory`. Its constants have been moved to `craft\services\Config` in the form of `CATEGORY_X`.

### Removed
- Removed the concept of “Safe Mode” from Twig, as it is no longer needed.
- Removed the `beforeInstall`, `afterInstall`, `beforeUpdate`, `afterUpdate`, `beforeUninstall`, and `afterUninstall` events from `craft\base\Plugin`.

### Fixed
- Fixed a bug where Craft couldn’t locate the DB backup file when attempting to restore it after a failed update.
- Fixed bug where element queries weren’t automatically getting ordered in their structure’s order, when applicable.
- Fixed a bug where changes to users’ Preferred Language settings weren’t sticking.
- Fixed a bug where `craft\i18n\I18N::getAppLocales()` and `getAppLocaleIds()` were only returning en-US.
- Fixed a bug where Craft was including a localized `0` character when formatting numbers as JPY, KRW, VND, XAF, XOF, and XPF currencies, if the Intl extension was not installed.
- Fixed a bug where Craft wasn’t displaying the “Can’t run Craft CMS” page correctly if it couldn’t connect to the database.
- Fixed a bug where updating to Craft 3.0.2933 or later could fail if the `backupDbOnUpdate` config setting was enabled.
- Fixed a bug where Matrix fields would lose their blocks when the owner element was saved outside of a normal Save Entry (etc.) POST request, e.g. when creating a new site.
- Fixed a PHP error that occurred when loading a category page on the front-end.
- Fixed a PHP error that occurred when calling the `site()` method on an element query.
- Fixed an infinite recursion bug when calling the `toArray()` method on an element query, or passing an element query object into `craft\helpers\ArrayHelper::toArray()`.
- Fixed a PHP error that occurred when deleting an entry.
- Fixed a bug where Craft wasn’t renaming content table columns when a field’s handle was renamed; instead it was just adding a new column based on the new handle.

## 3.0.0-alpha.2942 - 2016-09-21

### Added
- Added [Content Migrations](https://craftcms.com/news/craft-3-content-migrations) support.
- Added the `|timestamp` filter, for formatting a date as a user-friendly timestamp.
- Added the `|datetime` filter, for formatting a date with a localized date+time format.
- Added the `|time` filter, for formatting a date with a localized time format.
- Added `craft\web\Request::getAcceptsJson()`, which returns whether the HTTP request accepts a JSON response.
- Added `craft\web\Controller::requireAcceptsJson()`, which mandates that the HTTP request accepts a JSON response.
- Added `craft\i18n\Formatter::asTimestamp()`, for formatting a date as a user-friendly timestamp.
- Added `craft\helpers\StringHelper::ensureStartsWith()`, which will check if a string begins with a substring, and prepend it if it doesn’t.
- Added `craft\helpers\StringHelper::ensureEndsWith()`, which will check if a string ends with a substring, and append it if it doesn’t.
- Added the `craft\services\EntryRevisions::beforeRevertEntryToVersion` event.
- Added `craft\helpers\MigrationHelper::doesForeignKeyExist()`.
- Added `craft\helpers\MigrationHelper::doesIndexExist()`.

### Changed
- Improved the error message when a Migrate action was called with an invalid --plugin arg.
- Ported all recent changes from Craft 2.
- The `|date` filter can be passed `'short'`, `'medium'`, `'long'`, and `'full'`, which will format the date with a localized date format.
- The `{% cache %}` tag no longer includes the query string when storing the cache URL.
- `craft\helpers\DateTimeHelper::isToday()`, `isYesterday()`, `isThisYear()`, `isThisWeek()`, `isThisMonth()`, `isWithinLast()`, `isInThePast()`, and `timeAgoInWords()` now support all the same types of `$date` values as `craft\helpers\DateTimeHelper::toDateTime()`.
- `craft\helpers\MigrationHelper::dropForeignKeyIfExists()` and `dropIndexIfExists()` now allow the `$columns` argument to be a string.
- The `craft\services\EntryRevisions::beforeSaveDraft` event’s `$isValid` property is no longer respected. To prevent a draft from saving, set `$isValid = false` on the `craft\models\EntryDraft::beforeValidate` event.
- The `craft\services\EntryRevisions::beforePublishDraft` event’s `$isValid` property is no longer respected. To prevent a draft from publishing, set `$isValid = false` on the `craft\models\EntryDraft::beforeValidate` event.
- The `craft\services\EntryRevisions::beforeDeleteDraft` event’s `$isValid` property is no longer respected.
- The `craft\services\Updates::afterUpdateFail` event has been renamed to `updateFailure`.
- The `craft\mail\Mailer::sendEmailError` event has been renamed to `sendMailFailure`.
- Front-end forms that submit Ajax requests to Craft that expect a JSON response must start passing an `Accept: application/json` header. jQuery’s Ajax methods will do this if the `dataType` option is set to `'json'`.
- Renamed `craft\helpers\DateTimeHelper::wasYesterday()` to `isYesterday()`.
- Renamed `craft\helpers\DateTimeHelper::wasWithinLast()` to `isWithinLast()`.
- Renamed `craft\helpers\DateTimeHelper::wasInThePast()` to `isInThePast()`.
- The `$day` argument of `craft\i18n\Locale::getWeekDayName()` should now be 0-6 instead of 1-7, where `0` represents Sunday.

### Deprecated
- Deprecated the `round()` Twig function. Use the `|round` filter instead.
- Deprecated `craft\app\dates\DateTime::__toString()`. Use `format('Y-m-d')` instead.
- Deprecated `craft\app\dates\DateTime::atom()`. Use `format(DateTime::ATOM)` instead.
- Deprecated `craft\app\dates\DateTime::cookie()`. Use `format(DateTime::COOKIE)` instead.
- Deprecated `craft\app\dates\DateTime::iso8601()`. Use `format(DateTime::ISO8601)` instead.
- Deprecated `craft\app\dates\DateTime::rfc822()`. Use `format(DateTime::RFC822)` instead.
- Deprecated `craft\app\dates\DateTime::rfc850()`. Use `format(DateTime::RFC850)` instead.
- Deprecated `craft\app\dates\DateTime::rfc1036()`. Use `format(DateTime::RFC1036)` instead.
- Deprecated `craft\app\dates\DateTime::rfc1123()`. Use `format(DateTime::RFC1123)` instead.
- Deprecated `craft\app\dates\DateTime::rfc2822()`. Use `format(DateTime::RFC2822)` instead.
- Deprecated `craft\app\dates\DateTime::rfc3339()`. Use `format(DateTime::RFC3339)` instead.
- Deprecated `craft\app\dates\DateTime::rss()`. Use `format(DateTime::RSS)` instead.
- Deprecated `craft\app\dates\DateTime::w3c()`. Use `format(DateTime::W3C)` instead.
- Deprecated `craft\app\dates\DateTime::w3cDate()`. Use `format('Y-m-d')` instead.
- Deprecated `craft\app\dates\DateTime::mySqlDateTime()`. Use `format('Y-m-d H:i:s')` instead.
- Deprecated `craft\app\dates\DateTime::localeDate()`. Use `Craft::$app->formatter->asDate($date, 'short')` instead.
- Deprecated `craft\app\dates\DateTime::localeTime()`. Use `Craft::$app->formatter->asTime($date, 'short')` instead.
- Deprecated `craft\app\dates\DateTime::year()`. Use `format('Y')` instead.
- Deprecated `craft\app\dates\DateTime::month()`. Use `format('n')` instead.
- Deprecated `craft\app\dates\DateTime::day()`. Use `format('j')` instead.
- Deprecated `craft\app\dates\DateTime::nice()`. Use `Craft::$app->formatter->asDatetime($date)` instead.
- Deprecated `craft\app\dates\DateTime::uiTimestamp()`. Use `Craft::$app->formatter->asTimestamp($date, 'short')` instead.

### Removed
- Removed the static `$plugin` property on `craft\base\Plugin`.
- Removed `craft\web\Controller::requireAjaxRequest()`. Use `requireAcceptsJson()` instead.
- Removed `craft\helpers\DateTimeHelper::fromString()`. Use `DateTimeHelper::toDateTime($date)->getTimestamp()` instead.
- Removed `craft\helpers\DateTimeHelper::uiTimestamp()`. Use `Craft::$app->formatter->asTimestamp($date)` instead.
- Removed `craft\helpers\DateTimeHelper::timeAgoInWords()`. Use `Craft::$app->formatter->asRelativeTime($date)` instead.
- Removed `craft\helpers\DateTimeHelper::nice()`. Use `Craft::$app->formatter->asDatetime($date)` instead.
- Removed `craft\helpers\DateTimeHelper::niceShort()`.
- Removed the `craft\app\dates\DateTime::W3C_DATE` constant.
- Removed the `craft\app\dates\DateTime::MYSQL_DATETIME` constant.
- Removed the `craft\app\dates\DateTime::UTC constant`.
- Removed the `craft\app\dates\DateTime::DATEFIELD_24HOUR` constant.
- Removed the `craft\app\dates\DateTime::DATEFIELD_12HOUR` constant.

### Fixed
- Fixed PHP error when `Craft::$app->getIsUpdating()` was called in a console request.
- Fixed a bug that occurred when applying a coupon to a Craft Client/Pro purchase.
- Fixed bug where `craft\helpers\StringHelper::startsWith()` and `endsWith()` were returning strings instead of booleans.
- Fixed a bug where the `$isNew` property on entry draft events wasn’t getting set correctly.
- Fixed the default email settings.
- Fixed a PHP error that occurred when installing/updating/uninstalling a plugin that didn’t have a `migrations/` folder.
- Fixed a bug where files that were registered via an asset bundle weren’t getting included when calling `craft\web\View::getHeadHtml()`.
- Fixed a bug where localized datepicker resources weren’t getting included properly.
- Fixed a bug where matrixblocks.ownerSiteId was getting set to NOT NULL for new Craft installs, resulting in MySQL errors when saving non-localized Matrix fields.
- Fixed a PHP error that occurred when saving a category.
- Fixed a JavaScript error in `Craft.compare()` that affected Live Preview.
- Fixed a bug where changes to tags’ titles made from the inline editor within Tags fields would not be reflected in the Tags field, for newly-selected tags.

## 3.0.0-alpha.2939 - 2016-09-15

### Fixed
- Fixed a bug where `craft\helpers\Io::copyFolder()` was not working.
- Fixed an "Entry is missing its type ID" bug when saving a entry without multiple entry types.
- Fixed a PHP error that occurred when saving a Matrix field's settings on a Craft install with only one Site.
- Fixed a couple places where Craft wasn't enforcing the PHP 5.6 requirement.
- Fixed a bug where Craft would attempt to create a 'migrations' folder within plugins' folders when checking if they have any new migrations.
- Fixed a bug where ordering element queries based on custom field values wasn't working if the orderBy param was set via the `orderBy()` method.
- Fixed a MySQL error that occurred when updating Craft when any plugins were installed.
- Fixed a bug where element queries were not always respecting the 'limit' and 'offset' params when the 'search' param was applied.
- Fixed a bug where element queries were non respecting the 'orderBy' param if it was set to "score" and the 'search' param was applied.

## 3.0.0-alpha.2937 - 2016-09-13

### Changed
- It is now possible to override 'assetManager', 'cache', 'db', 'mailer', 'locale', 'formatter', and 'log' application components' configurations from `config/app.php`.
- Renamed `craft\services\Plugins::getPluginInfo()` to `getAllPluginInfo()`.
- `craft\elements\MatrixBlock::getType()` now throws an `yii\base\InvalidConfigException` if the block type cannot be determined, rather than returning `null`.

### Fixed
- Fixed a MySQL error that occurred when updating a Craft install to Craft Dev 3.0.2933, if any user permissions had been assigned.
- Fixed an "Invalid entry type ID" error that occurred when creating a new entry.
- Fixed a bug where fields weren't passing validation, with no visible validation errors, when there was only one Site.
- Fixed a bug where elements' `afterSave()` functions and fields' `afterElementSave()` functions weren't getting called when saving new elements, which prevented Matrix and relationship fields' values from getting saved.
- Fixed a PHP error that occurred when executing `migrate` commands from the CLI.
- Fixed a bug where `config/db.php` was not getting validated before attempting to establish a DB connection.

## 3.0.0-alpha.2933 - 2016-09-12

### Added
- [Multi-site management!](https://craftcms.com/news/craft-3-multi-site)
- Locale permissions have been replaced by Site permissions.
- Sections and category groups can now choose to have URLs on a per-site basis.
- Field types that have a column in the content table now get a "Translation Method" setting (replacing the "This field is translatable" setting), with the options "Not translatable", "Translate for each language", "Translate for each site", and "Custom…".
- Matrix fields now have a "Manage blocks on a per-site basis" setting.
- Relationship fields now have a "Manage relations on a per-site basis" setting.
- Added support for a `CRAFT_SITE` PHP constant, which can be set to the handle of the current site.
- Element queries now support 'site', 'siteId', and 'enabledForSite' criteria params.
- Added `craft\services\Sites`.
- Added `craft\validators\SiteId`.
- Added `Craft::$app->getIsMultiSite()`, replacing `getIsLocalized()`.
- Added `Craft::$app->getSystemUid()`, replacing `getSiteUid()`.
- Added `craft\i18n\I18N::getSiteLanguages()`, replacing `getSiteLocaleIds()`.
- Added `craft\elements\User::getPreferredLanguage()`, replacing `getPreferredLocale()`.
- Added `craft\helpers\ElementHelper::getSupportedSitesForElement()`
- Added `craft\helpers\ElementHelper::getEditableSiteIdsForElement()`, replacing `getEditableLocaleIdsForElement()`
- Added `ElementInterface::getSupportedSites()`, replacing `getLocales()`.
- Added `Craft.isMultiSite` for JavaScript, replacing `Craft.isLocalized`.
- Added `Craft.systemUid` for JavaScript, replacing `Craft.siteUid`.
- Added `Craft.UriFormatGenerator` for JavaScript, replacing `Craft.EntryUrlFormatGenerator`.
- Plugins now have a static `$plugin` property, which gets set to the instance of the plugin, if it has been initialized.

### Changed
- Routes can now be stored on a per-site basis, rather than per-locale.
- Renamed all "URL Format" things to "URI Format", in the Control Panel UI and in the code.
- Structure sections and category groups no longer have Nested URL Format settings. (It's still possible to achieve the same result with a single URI Format setting.)
- Fields that are translatable now have a chat bubble icon, replacing the locale ID badge.
- `craft\elements\Category::getGroup()` now throws an `yii\base\InvalidConfigException` if the category group type cannot be determined, rather than returning `null`.
- Renamed `craft\base\PluginTrait::$releasesFeedUrl` to `$releaseFeedUrl`.
- `craft\mail\Message::setTo()` will now set a 'user' variable on the message, if it was passed a User model.
- `Craft.ui.createSelect()` and `createLightswitch()` now initialize the `Craft.FieldToggle` class on the returned elements.

### Fixed
- Fixed a PHP error that occurred if a Position Select field had a blank first option.
- Fixed a bug where users without Control Panel access weren't getting redirected correctly after account activation.
- Fixed a JavaScript error that occurred when calling `Craft.ui.createCheckboxField()`.

## 3.0.0-alpha.2928 - 2016-08-31

### Added
- Table fields now have a "Lightswitch" column type option.
- Added a `|unique` Twig filter for filtering out duplicate values in an array.
- Added `craft\app\validators\Unique`, which can be used as a drop-in replacement for `craft\yii\validators\UniqueValidator`, but with better support for validating a model's attributes based on an associated record's attributes.
- Added `Craft.ui.createTextarea()`, `createTextareaField()`, `createLightswitch()`, and `createLightswitchField()`.
- Added a `lightswitch` column type option to editable tables.
- Text columns within editable tables can now define placeholder text.
- Editable table cell values can now be specified as an array with `value` and `hasErrors` (optional boolean) keys. Cells where `hasErrors` is set to `true` will get a red border.

### Changed
- Ported all recent changes from Craft 2.
- `craft\elements\Entry::getSection()` and `getType()` now throw an `yii\base\InvalidConfigException` if the section/entry type cannot be determined, rather than returning `null`.

### Fixed
- Fixed bug where `&nbsp;` was getting output for editable table headings that should have been empty.
- Fixed a bug where xregexp-all.js was not getting included properly when the `useCompressedJs` config setting was set to `true` (as it is by default).

## 3.0.0-alpha.2918 - 2016-08-25

### Added
- Added `$flavor` and `$inlineOnly` arguments to the `|markdown` filter, making it possible to choose which Markdown flavor should be used, and whether it should parse paragraphs or treat the whole thing as inline elements.
- Added `Craft::$app->getIsUpdating()`, which returns `true` if Craft is undergoing an application update.
- Added the beforeSaveGlobalSet, afterSaveGlobalSet, beforeDeleteGlobalSet, and afterDeleteGlobalSet events to `craft\services\Globals`.
- Added the beforeSaveUserGroup, afterSaveUserGroup, beforeDeleteUserGroup and afterDeleteUserGroup events to `craft\services\UserGroups`.
- Added the beforeSaveAssetTransform, afterSaveAssetTransform, beforeDeleteAssetTransform, and afterDeleteAssetTransform events to `craft\services\AssetTransforms`.
- Added the beforeDeleteEntryType and afterDeleteEntryType events to `craft\services\Sections`.
- Added the afterInit event to `craft\web\Application` and `craft\console\Application`.
- Added the loginFailure event to `craft\elements\User`.

### Changed
- All before/after-save events now have an `$isNew` property that identifies whether the object of the event is brand new or not.
- The `_includes/forms/field` include template now parses the passed in `warning` variable as an inline Markdown string, if it exists.
- The `_includes/forms/editableTable` include template now supports a `staticRows` variable, which if defined and set to `true` will prevent row CRUD operations in the resulting table.
- The `_includes/forms/editableTable` include template now supports a `heading` column type, which can be used in conjunction with `staticRows` to define row headings.
- The `_includes/forms/editableTable` include template now supports an `info` property on column configs, which will add an info button to the column header which reveas instruction text for the column.
- Renamed some event classes to be more consistent.

### Fixed
- Fixed a bug where it was not possible to replace a file from the Assets page.
- Fixed a bug where asset volumes' Cache Duration settings were not being respected.
- Fixed a bug that affected asset indexing reliability.
- Fixed a PHP error that occurred when rendering a Twig template from a console request.
- Fixed a bug where setting custom field attributes on an element query would prevent the query from executing.
- Fixed a bug where plugin Control Panel nav items were getting the full plugin class name in the URL instead of just handle.

## 3.0.0-alpha.2915 - 2016-08-17

### Added
- User photos are now assets. (Note that the `storage/userphotos/` folder must be manually moved to a publicly accessible location, and the User Photos asset volume’s settings must be updated accordingly, for user photos to work properly.)
- Added `craft\elements\User::getPhoto()`, which returns the user’s photo asset, if they have one.
- Added `craft\helpers\StringHelper::randomStringWithChars()`.
- Added the `beforeSaveContent` event to `craft\services\Content`.
- Added the `beforeSaveDraft` and `beforePublishDraft` events to `craft\services\EntryRevisions`.
- Added the `beforeSaveGroup`, `afterSaveGroup`, `beforeDeleteGroup`, and `afterDeleteGroup` events to `craft\services\Tags`.
- Added the `beforeSaveVolume`, `afterSaveVolume`, `beforeDeleteVolume`, and `afterDeleteVolume` events to `craft\services\Volumes`.
- Added the `redirectInput()` global function, which simplifies the code required to create `redirect` params.

### Changed
- All `redirect` params must now be hashed using the `|hash` filter (e.g. `<input type="hidden" name="redirect" value="{{ 'foo'|hash }}">`). A new `redirectInput()` global function makes this a little easier (e.g. `{{ redirectInput('foo') }}`).
- Ported recent changes from Craft 2.
- The `getCsrfInput()` global function has been renamed to `csrfInput()`. (getCsrfInput() still works but produces a deprecation error.)
- The `|hash` filter no longer requires a `$key` argument.
- Brought back `getId()`, `getName()`, and `getNativeName()` methods to Locale, so Craft 2 templates don’t need to be updated right away, although they will produce a deprecation error.

### Deprecated
- `craft\elements\User::getPhotoUrl()` is now deprecated in favor of `user.getPhoto.getUrl()`.

### Fixed
- Fixed a bug where `craft\helpers\StringHelper::toTitleCase()` was returning all-lowercase strings.
- Fixed a typo where `craft\helpers\Json::encodeIfJson()` should have been named `decodeIfJson()`.
- Fixed a bug where element queries were not respecting the `offset` property.
- Fixed a MySQL error that occurred when querying users with the `can` property.
- Fixed a MySQL error that occurred when executing an element query that had custom query params.
- Fixed a PHP error that occurred when rendering templates with a `{% paginate %}` tag. (Note that you may need to delete your `storage/runtime/compiled_templates/` folder.)
- Fixed a PHP warning that occurred when using `craft\services\Feeds` on a server running PHP 7.
- Fixed a few bugs related to saving elements on localized sites.
- Fixed tag group saving and deleting.
- Fixed a bug where all global sets were listed in the sidebar of the Globals page, rather than just the ones the user had permission to edit.
- Fixed a bug where nested Structure section entries were not showing the parent entry on their edit pages.
- Fixed a bug where nested categories were not showing the parent category on their edit pages.
- Fixed a MySQL error that occurred when saving a nested Structure section entry.
- Fixed a MySQL error that occurred when saving a nested category.
- Fixed a bug where User elements returned by `craft\services\Users::getUserByUsernameOrEmail()` and `getUserByEmail()` were missing their content.
- Fixed a bug where user info like Last Login Date was not getting updated when users logged in.
- Fixed a bug where email messages were not respecting the recipient user’s preferred language.
- Fixed a PHP error that occurred when saving an element with an invalid field, if the field’s value couldn’t be converted to a string.
- Fixed a PHP error that occurred when sharing an entry or category with a tokenized share URL.
- Fixed a bug where the `dateCreated`, `dateUpdated`, and `uid` attributes on new active record objects would not necessarily reflect the values in the database, after saving the record for the first time.
- Fixed a bug that broke cookie-based authentication.
- Fixed a bug where temporary asset uploads were getting deleted when clearing data caches.
- Fixed a JavaScript error that occurred in the Control Panel.

### Security
- `craft\services\Security::hashData` and `validateData()` no longer require a `$key` argument.

## 3.0.0-alpha.2910 - 2016-08-04

### Added
- Ported all the changes from Craft 2.
- Craft 3 now requires PHP 5.6 or later.
- Craft 3 now requires MySQL 5.5 or later.
- The debug toolbar now has the Craft “C” logo rather than the Yii logo.
- `craft.app` now points to the same Application object that `Craft::$app` references in PHP, giving templates access to literally all of the service methods, etc., that PHP code has access to.
- Added the `is missing` Twig test for determining if an object implements `craft\base\MissingComponentInterface`.
- Added a new `uid` parameter to element queries, for fetching elements by their UID.
- Elements’ UIDs are now fetched in element queries, and stored as a `uid` property on the resulting elements.
- Added support for Reduced Redundancy Storage and Infrequent Access Storage for Amazon S3 asset volumes.
- Added the beforeCreateBackup event to `craft\db\Connection`.
- Added the beforeSaveGroup and afterSaveGroup events to `craft\services\Categories`.
- Documented all magic properties and methods in class doc blocks, so IDEs should always know what’s available.
- Added `craft\db\mysql\Schema::TYPE_TINYTEXT` and `TYPE_LONGTEXT`, and added custom support for them in `craft\db\mysql\ColumnSchemaBuilder`.
- Added `craft\db\Migration::tinyText()`, `mediumText()`, `longText()`, `enum()`, and `uid()` column schema definition helpers.
- Added `craft\errors\DbBackupException`.
- Added `craft\errors\DbUpdateException`.
- Added `craft\errors\DownloadPackageException`.
- Added `craft\errors\FilePermissionsException`.
- Added `craft\errors\InvalidateCacheException`.
- Added `craft\errors\MinimumRequirementException`.
- Added `craft\errors\MissingFileException`.
- Added `craft\errors\UnpackPackageException`.
- Added `craft\errors\ValidatePackageException`.
- Added `craft\helpers\Localization::getLocaleData()`.
- Added `craft\volumes\Temp`.
- Added a `$format` argument to `craft\i18n\Locale::getDateFormat()`, `getTimeFormat()`, and `getDateTimeFormat()`, for specifying whether the returned string should be in the ICU, PHP, or jQuery UI date/time format.

### Changed
- Craft now checks for a plugin.json file within plugin directories, rather than config.json.
- Plugin-based email messages now get translated using the plugin handle as the translation category, rather than `'app'`.
- “owner” is now a reserved field handle.
- Made lots of improvements to exceptions.
- Improved the handling of invalid locale IDs. (Work in progress, though.)
- Renamed lots of class and method names to be more in-line with Yii conventions.
- Renamed the `craft\i18n\Locale::FORMAT_*` constants to `LENGTH_*`.
- Renamed `craft\web\View::includeTranslations()` to `registerTranslations()`, and it now accepts two arguments: `$category` and `$messages`.
- `craft\db\Command::createTable()` and `craft\db\Migration::createTable()` no longer accept `$addIdColumn` or `$addAuditColumn` arguments. The `id`, `dateCreated`, `dateUpdated`, and `uid` columns are now expected to be included in the `$columns` array.
- `craft\web\View::clearJsBuffer()` no longer returns an array.
- Renamed `Craft.locale` to `Craft.language` in JS.
- `Craft.t()` in JS now accepts a category argument, like `Craft::t()` in PHP.
- Updated Yii to 2.0.9.
- Updated the Yii 2 Debug module to 2.0.6.
- Updated the Yii 2 Nested Sets module to 0.9.0.
- Updated Swiftmailer to 5.4.3.
- Updated SimplePie to 1.4.2.
- Updated Guzzle to 6.2.1.
- Updated Twig to 1.24.1.
- Updated PEL to 0.9.4.
- Updated Stringy to 2.3.2.
- Updated Imagine to 0.6.3.
- Updated Flysystem to 1.0.26.
- Updated PclZip to 2.8.2.
- Updated jQuery Timepicker to 1.11.2.
- Updated qUnit to 2.0.1.
- Updated Redactor II to 1.2.5.
- Updated selectize.js to 0.12.2.
- Updated element-resize-detector.js to 1.1.6.
- Updated jQuery UI to 1.12.0.
- Updated Picturefill to 3.0.2.
- Updated xRegExp to 3.1.1.

### Deprecated
- Deprecated `craft.categoryGroups`.
- Deprecated `craft.config`.
- Deprecated `craft.deprecator`.
- Deprecated `craft.elementIndexes`.
- Deprecated `craft.entryRevisions`.
- Deprecated `craft.feeds`.
- Deprecated `craft.fields`.
- Deprecated `craft.globals`.
- Deprecated `craft.i18n`.
- Deprecated `craft.request`.
- Deprecated `craft.sections`.
- Deprecated `craft.systemSettings`.
- Deprecated `craft.tasks`.
- Deprecated `craft.session`.
- Deprecated `craft.userGroups`.
- Deprecated `craft.emailMessages`.
- Deprecated `craft.userPermissions`.

### Removed
- Removed the `overridePhpSessionLocation` config setting.
- Removed `craft.elements`.
- Removed the `{% includeTranslations %}` tag.
- Removed the `craft\db\InstallMigration` class.
- Removed the `craft\web\twig\variables\ComponentInfo` class.

### Fixed
- Fixed a lot of bugs. OK?

## 3.0.0-alpha.2687 - 2015-08-20

### Added
- Ported all improvements and bug fixes from the latest Craft 2.4 builds.
- Added the `showBetaUpdates` config setting.
- Added `craft\helpers\StringHelper::delimit()`.
- Added `craft\helpers\StringHelper::indexOf()`.
- Added `craft\helpers\StringHelper::indexOfLast()`.
- Added `craft\helpers\StringHelper::lines()`.

### Changed
- Image transforms’ Quality settings are now represented as a dropdown input in the Control Panel, with the options “Auto”, “Low”, “Medium”, “High”, “Very High (Recommended)”, and “Maximum”.
- It is now possible to access `Craft.Grid` objects from their container elements, via `.data('grid')`.
- It is now possible to access `Craft.BaseElementSelectInput` objects from their container elements, via `.data('elementSelect')`.
- Craft now displays helpful errors after failed asset uploads.

### Fixed
- Fixed an error that occurred when backing up the database.
- Fixed a bug where plugin settings were getting HTML-encoded.
- Fixed an error that occurred when deleting a field group.
- Fixed a MySQL error that occurred when saving a category group without URLs.
- Fixed an error that occurred when fields existed with invalid types, if there was no corresponding column in the database.
- Fixed a MySQL error that occurred when saving user groups.
- Fixed a bug where user group names and handles were not being validated for uniqueness.
- Fixed a bug where route params registered with `craft\web\UrlManager::setRouteParams()` were not being passed to the template, for requests that ultimately get routed to a template.
- Fixed a bug where no Admin user would be created when installing Craft with the `useEmailAsUsername` config setting set to `true`.
- Fixed a bug where Title fields were not being validated.
- Fixed a bug where custom fields’ validation errors were not being reported after attempting to save elements.
- Fixed an error that occurred when attempting to save an element with a required Rich Text field that had no value.
- Fixed a bug where PHP could easily run out of memory when unzipping large zip files.
- Fixed a bug where `craft\helpers\MigrationHelper` would forget that it had dropped a table after `dropTable()` was called.
- Fixed an error that would occur if another error occurred during a MySQL transaction that included savepoints, and the savepoints had already been implicitly committed.

## 3.0.0-alpha.2681 - 2015-07-22

### Added
- Ported all improvements and bug fixes from the latest Craft 2.4 builds.
- Added the `enableCsrfCookie` config setting, which determines whether CSRF tokens should be saved in cookies. Defaults to `true`. CSRF tokens will be stored in the PHP session if set to `false`.
- The Content model has been removed. Elements now have `craft\behaviors\ContentBehavior`s directly. Because speed!
- Added `craft\base\ElementTrait::$contentId` for storing the content row’s ID.
- Added `craft\base\ElementTrait::$title` for storing the element’s title, replacing `craft\base\ElementInterface::getTitle()`.
- Added `craft\base\ElementInterface::getFieldValues()`, replacing `getContent()`.
- Added `craft\base\ElementInterface::setFieldValues()`, replacing `setContent()`.
- Added `craft\base\ElementInterface::setFieldValuesFromPost()`, replacing `setContentFromPost()`.
- Added `craft\base\ElementInterface::setRawPostValueForField()`, replacing `setRawPostContent()`.
- Added `craft\base\ElementInterface::setFieldValue()`.
- All `$value` arguments on Field methods are now set to the prepared field value, with the sole exception of `prepareValue()`.
- Added `craft\base\FieldInterface::prepareValueForDb()`, giving fields direct control over how their values should be saved to the database, without affecting the value stored on the element. (This replaces the protected `prepareValueBeforeSave()` method.)
- Added protected `craft\base\Field::isValueEmpty()` which aids `craft\base\Field::validateValue()` in required-field validation.
- Added `craft\helpers\DateTimeHelper::normalizeTimeZone()`.
- Added `craft.getTimeZone()` for Control Panel JavaScript.
- Added the `craft\app\base\Savable` interface. Objects that implement it have control over how `craft\helpers\DbHelper::prepareValueForDb()` prepares them to be saved to the database.
- Added `craft\app\web\View::getBodyHtml()`, replacing `getBodyBeginHtml()` and `getBodyEndHtml()`.

### Changed
- Updated Yii to 2.0.5.
- Element queries are no longer limited to 100 results by default.
- `craft\elements\db\ElementQuery::count()` now returns the total cached results, if set.
- `craft\base\FieldInterface::validateValue()` is now responsible for required-field validation (and a basic is-empty check is included in `craft\base\Field`).
- `craft\base\FieldInterface::validateValue()` no longer needs to return `true` if the value passed validation.
- `craft\dates\DateTimeHelper::toDateTime()`’s `$timezone` argument has been replaced with `$assumeSystemTimeZone`. If set to `true` and if `$value` doesn’t have an explicit time zone, the method will use the system’s time zone. Otherwise UTC will be used. (Defaults to `false`.)
- `craft\dates\DateTimeHelper::toDateTime()` now checks for a `timezone` key when `$value` is in the date/time-picker array format.
- `craft\dates\DateTimeHelper::toDateTime()` now returns `false` instead of `null` when an array without `'date'` or `'time'` keys is passed in.
- `craft\dates\DateTime::createFromFormat()` no longer sets the `$timezone` argument if left null, matching the base `DateTime` class’s behavior.
- Date/time-pickers in the CP now explicitly declare that they are using the system time zone in their post data.
- Nested database transactions will now set savepoints within the master transaction, as MySQL doesn’t support nested transactions.
- `PluginClassName::getInstance()` will now return the singular instance of the plugin class.

### Removed
- Removed `craft\dates\DateTime::format()`, so it no longer has a `$timezone` argument and automatically sets the time zone on the `DateTime` object.

### Fixed
- Fixed many, many bugs.

## 3.0.0-alpha.2671 - 2015-06-18

### Added
- Ported all new features and improvements that were introduced in [Craft 2.4](http://buildwithcraft.com/updates#build2664).
- The codebase now follows the [PSR-2](http://www.php-fig.org/psr/psr-2/) coding style.
- Added support for `config/app.php`, which can return an array that will be merged with Craft’s core application config array.
- Craft now looks for translation files at `translations/locale-ID/category.php`, where `category` can either be `'app'`, `'site'`, `'yii'`, or a plugin’s handle.
- All user-defined strings in the Control Panel (e.g. section names) are now translated using the `'site'` category, to prevent translation conflicts with Craft’s own Control Panel translations.
- Craft now uses SwiftMailer to send emails.
- Added the ability for plugins to provide custom SwiftMailer transport options.
- Added `craft\helpers\DateTimeHelper::toIso8601()`, for converting a date (or date-formatted string) into an ISO-8601-formatted string.
- Added `craft\helpers\DateTimeHelper::isIso8601()`, for determining if a given variable is an ISO-8601-formatted string.
- Added `craft\helpers\DateTimeHelper::translateDate()`, for translating month and weekday names from English to the active application language.
- Added `craft\helpers\DbHeleper::prepareDateForDb()`, which replaces the old `craft\helpers\DateTimeHelper::formatTimeForDb()`.
- Added `craft\behaviors\FieldLayoutBehavior`, which replaces `craft\base\FieldLayoutTrait`, enabling a single class to have more than one field layout associated with it.
- The localized date format used by JavaScript date pickers is now available from `Craft.datepickerFormat`.
- Added the JavaScript method, `Craft.formatDate()`, for formatting a date (or date-formatted string) into a string using the same localized date format used by JavaScript date pickers.

### Changed
- The `translationDebugOutput` config setting will now wrap strings with `@` characters if the category is `'app'`, `$` if the category is `'site'`, and `%` for anything else.
- Web requests are now logged to `storage/logs/web.log`.
- Web requests that result in 404 errors are now logged to `storage/logs/web-404s.log`.
- Console requests are now logged to `storage/logs/console.log`.
- Template error handling now works similarly to how it does in Craft 2 when Craft is running in Dev Mode, where the template source is shown in the error view.
- Twig class names now link to their respective class reference URLs in the error view’s stack trace.
- The `registercss`, `registerhirescss`, and `registerjs` tags can now be used as tag pairs.
- The `registerassetbundle`, `registerjs`, `registerjsfile`, and `registerjsresource` tags now support an `at` param for specifying the location on the page their HTML tags should be placed. (Possible values are `at head`, `at beginBody`, and `at endBody`.)
- The `registerjs` tag now supports an `on` param for specifying when the JavaScript code should be executed. (Possible values are `on ready` and `on load`.) (The `at` and `on` parameters are mutually exclusive.)
- The `registercss`, `registerhirescss`, `registercssfile`, `registercssresource`, `registerjsfile`, and `registerjsresource` tags now support a `with` param, which can be set to an object that specifies additional attributes on the resulting tags (e.g. `with { rel: "stylesheet" }`).
- The `t` filter now always defaults to translating the given string using the `'site'` category unless it is otherwise specified (e.g. `myString|t('pluginhandle')`).
- `@craft/plugins/pluginhandle` aliases are now registered _before_ Craft attempts to load a plugin’s primary class, making it easier for plugins to use custom class names.
- `craft\base\Model::toArray()` now converts `DateTime` attributes to ISO-8601 strings.
- Renamed `craft\helpers\AssetHelper::cleanAssetName()` to `prepareAssetName()`.
- Renamed `craft\helpers\DbHelper::prepObjectValues()` to `prepareValuesForDb()`.
- Renamed `craft\helpers\DbHelper::prepValue()` to `prepareValueForDb()`.
- Renamed `craft\events\Event::$performAction` to `$isValid` to be consistent with Yii events.
- `craft\helpers\DateTimeHelper::toDateTime()` will now return `false` if it is unable to convert `$object` to a `DateTime` object.
- `craft\helpers\DateTimeHelper::toDateTime()` now supports ISO-8601-formatted dates.
- `craft\helpers\DbHelper::prepareValueForDb()` now treats ISO-8601 strings as dates, and converts them to the MySQL date format.

### Deprecated
- All the new `register*` tags must be all-lowercase now. (The old `include*` tags still work as they do in Craft 2, however they remain deprecated.)

### Removed
- Removed `craft\helpers\DateTimeHelper::currentTimeForDb()`.
- Removed `craft\helpers\DateTimeHelper::formatTimeForDb()`.

### Fixed
- Fixed a bug where Craft was not enforcing required entry titles and custom fields.
- Fixed a bug where it was not possible to create new Volumes on Craft Personal/Craft Client installs.
- Fixed an error that occurred when using the `type` param when querying for Matrix blocks.
- Fixed an “IP address restriction” error that prevented the Debug toolbar from showing up for some people.
- Fixed a PHP error that would occur when converting models without `$dateCreated` or `$dateUpdated` properties to an array.
- Fixed a PHP error that occurred when resetting a password.
- Fixed a PHP error that occurred when sending an activation email.
- Fixed a PHP error that occurred when saving an entry with validation errors, or when loading a draft for an entry, if the entry had any Matrix fields.
- Fixed a PHP error that occurred when editing an entry draft or version.
- Fixed a MySQL error that occurred when saving new image transforms.
- Fixed a MySQL error that occurred when saving a section with entry versioning disabled.
- Fixed `craft\web\View::registerHiResCss()`, and the `{% registerhirescss %}` tag.
- Fixed a bug that prevented user and email settings from being remembered.
- Fixed a JavaScript error that occurred on pages with fields that could be toggled by a checkbox.

## 3.0.0-alpha.2663 - 2015-05-27

### Changed
- Plugins can now add an array of Twig extensions from the `addTwigExtension` hook.

### Fixed
- Fixed a PHP error that occurred when installing Craft or validating URLs if the Intl extension wasn’t loaded.

## 3.0.0-alpha.2661 - 2015-05-22

### Added
- Added a `withPassword` criteria parameter to User queries, which includes the users’ hashed passwords in the query and sets them on the resulting User models.
- Added a `$setToSystemTimeZone` argument to `craft\helpers\DateTimeHelper::toDateTime()`, which will set the resulting `DateTime` object to the system’s Timezone setting (defaults to `true`).
- Added the `convertFilenamesToAscii` config setting.

### Changed
- The Debug toolbar no longer requires Dev Mode to be enabled.
- URL validation now accounts for URLs that include environment variables.
- URL validation now allows international domain names.
- `contentTable` is now a reserved field handle.
- Craft is now distributed with a `public/cpresources/` folder, which should be set with writable permissions.
- Craft now ensures that the public Resources folder exists and is writable at a much earlier stage so it can give a more helpful error message.

### Fixed
- Fixed a bug where many locales had the wrong display name in the CP if the Intl extension was not loaded.
- Fixed an error that occurred when saving a Volume using Craft Personal or Craft Client.
- Fixed an error error that occurred when validating a URL that included an environment variable.
- Fixed a bug where some dates were being output with the wrong timezone.
- Fixed an error that occurred when saving a Date/Time field set to only show the timepicker.
- Fixed a UI glitch where the bottom shadow of entry Save buttons would span the full width of the grid column if entry versioning was disabled or unavailable.
- Fixed a bug where the Min Build Required error message was getting HTML-encoded.
- Fixed a couple errors in that could occur if Craft didn’t receive the expected response from its web service.
- Fixed an error that occurred when attempting to log in with the wrong password multiple times.
- Fixed a bug where users would become permanently locked when their cooldown period had expired.
- Fixed a “Craft Client is required” error when editing entries in Craft Personal.
- Fixed an error that occurred after an Admin entered their password in order to change a user account’s email/password.
- Fixed a validation error on the New Password field if a user attempted to update their email address but didn’t want to change their existing password.
- Corrected the default config paths in the comments of `config/db.php` and `config/general.php`.
- Fixed a bug that resulted in the Updates page never getting past the “Checking for updates” step when an update was available.

## 3.0.0-alpha.2659 - 2015-05-19

### Added
- Added support for registering plugin resources via `craft\web\View::registerCssResource()` and `registerJsResource()`.
- Added `craft\base\Element::getStructureId()`, `setStructureId()`, and `resolveStructureId()`.

### Changed
- Drastically reduced the likelihood of importing a database backup with a falsely-identical schemaVersion as the files stored in `storage/runtime/compiled_classes/`.

### Fixed
- Updated the sample config files to use PHP 5.4’s short array syntax.
- Fixed a PHP error that would occur throughout the Control Panel when a new Craft update was available and its info was cached.
- Fixed a 404 error for jquery.placeholder.min.js that occurred on several pages in the Control Panel.
- Fixed a PHP error that occurred when formatting dates if the Intl extension wasn’t loaded.
- Fixed a PHP error that occurred when opening the category selection modal on a Categories field.
- Fixed a PHP error that occurred when saving an entry with a Categories field.
- Fixed a PHP error that occurred when searching for tags within a Tags field.
- Fixed a PHP error that occurred when using Date/Time fields configured to only show the timepicker.
- Fixed a bug where asset bundles wouldn’t get re-published if a file within one of its subdirectories was created or updated.
- Fixed a bug where database backups would get stored in `storagebackups/` instead of `storage/backups/`.
- Fixed a bug where the Min Build Required error message had encoded HTML.

## 3.0.0-alpha.2657 - 2015-05-19

### Added
- Completely rewritten and refactored codebase, powered by [Yii 2](http://www.yiiframework.com/).
- Improved internationalization support with PHP’s [Intl extension](http://php.net/manual/en/book.intl.php) and [Stringy](https://github.com/danielstjules/Stringy).
- Plugins are now loaded as Yii [modules](http://www.yiiframework.com/doc-2.0/guide-structure-modules.html).
- Asset Sources are now called Volumes, and plugins can supply their own Volume Types (made easy with [Flysystem](http://flysystem.thephpleague.com/)).
- It is now possibly to customize the SQL of element queries, and there are more choices on how the data should be returned.
- Included the [Yii 2 Debug Extension](http://www.yiiframework.com/doc-2.0/guide-tool-debugger.html).
