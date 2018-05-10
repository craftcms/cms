# Release Notes for Craft CMS 3.x

## 3.0.7 - 2018-05-10

### Added
- Added the `transformGifs` config setting, which can be set to `false` to prevent GIFs from getting transformed or cleansed. ([#2845](https://github.com/craftcms/cms/issues/2845))
- Added `craft\helpers\FileHelper::isGif()`.

### Changed
- Craft no longer logs warnings about missing translation files when Dev Mode isn’t enabled. ([#1531](https://github.com/craftcms/cms/issues/1531))
- Added `craft\services\Deprecator::$logTarget`. ([#2870](https://github.com/craftcms/cms/issues/2870))
- `craft\services\Deprecator::log()` no longer returns anything.

### Fixed
- Fixed a bug where it was impossible to upload new assets to Assets fields using base64-encoded strings. ([#2855](https://github.com/craftcms/cms/issues/2855))
- Fixed a bug where Assets fields would ignore all submitted asset IDs if any new assets were uploaded as well.
- Fixed a bug where SVG files that were using single quotes instead of double quotes would not be recognized as SVGs.
- Fixed a bug where translated versions of the “It looks like someone is currently performing a system update.” message contained an HTML-encoded `<br/>` tag.
- Fixed a bug where changing an entry’s type could skip adding the new entry type’s tabs, if the previous entry type didn’t have any tabs. ([#2859](https://github.com/craftcms/cms/issues/2859))
- Fixed warnings about missing SVG files that were logged by Control Panel requests.
- Fixed a bug where the `|date` filter would ignore date formatting characters that don’t have ICU counterparts. ([#2867](https://github.com/craftcms/cms/issues/2867))
- Fixed a bug where the global `currentUser` Twig variable could be set to `null` and global sets and could be missing some custom field values when a user was logged-in, if a plugin was loading Twig during or immediately after plugin instantiation. ([#2866](https://github.com/craftcms/cms/issues/2866))

## 3.0.6 - 2018-05-08

### Added
- Error messages about missing plugin-supplied field and volume types now show an Install button when possible.
- Added `craft\base\MissingComponentTrait::getPlaceholderHtml()`.
- Added `craft\db\Migration::EVENT_AFTER_UP` and `EVENT_AFTER_DOWN` events.
- Added `craft\elements\Asset::getContents()`.

### Changed
- Edit User pages will now warn editors when leaving the page with unsaved changes. ([#2832](https://github.com/craftcms/cms/issues/2832))
- Modules are once again loaded before plugins, so they have a chance to register Twig initialization events before a plugin initializes Twig. ([#2831](https://github.com/craftcms/cms/issues/2831))
- `craft\helpers\FileHelper::isSvg()` now returns `true` for files with an `image/svg` MIME type (missing the `+xml`). ([#2837](https://github.com/craftcms/cms/pull/2837))
- The `svg()` Twig function now accepts assets to be passed directly into it. ([#2838](https://github.com/craftcms/cms/pull/2838))
- The “Save and add another” save menu option on Edit Entry and Edit Categories pages now maintain the currently-selected site. ([#2844](https://github.com/craftcms/cms/issues/2844))
- PHP date patterns that are *only* a month name or week day name character will now format the date using the stand-alone month/week day name value. (For example, `'F'` will format a date as “Maggio” instead of “maggio”.)
- Servers without the Intl extension will now use location-agnostic locale data as a fallback if locale data for the specific locale isn’t available.
- The `|date` Twig filter always goes through `craft\i18n\Formatter::asDate()` now, unless formatting a `DateInterval` object.
- The Settings → Plugins page now shows “Buy now” buttons for any commercial plugins that don’t have a license key yet.

### Deprecated
- Deprecated `craft\helpers\DateTimeHelper::translateDate()`. `craft\i18n\Formatter::asDate()` should be used instead.

### Removed
- Removed the `translate` argument from the `|date`, `|time`, and `|datetime` Twig filters; the resulting formatted dates will always be translated now. (Use `myDate.format()` to avoid translations.)

### Fixed
- Fixed an error that could occur in the Plugin Store.
- Fixed a bug where `myDate|date('F')` was returning the short “May” translation rather than the full-length one. ([#2848](https://github.com/craftcms/cms/issues/2848))

## 3.0.5 - 2018-05-01

### Changed
- Fields’ translation icons now reveal the chosen Translation Method in their tooltip. ([#2808](https://github.com/craftcms/cms/issues/2808))
- Improved the error messages displayed when an Assets field has an invalid Upload Location setting. ([#2803](https://github.com/craftcms/cms/issues/2803))
- Craft now logs errors that occur when saving and replacing assets. ([#2814](https://github.com/craftcms/cms/issues/2814))
- Single sections’ entry types’ handles are now updated to match their section’s handle whenever the section is saved. ([#2824](https://github.com/craftcms/cms/issues/2824))
- The Control Panel background color was lightened up a bit.

### Fixed
- Fixed an error that would occur on servers without the Phar PHP extension enabled.
- Fixed an error that could occur if a Matrix block was deleted by a queue job. ([#2813](https://github.com/craftcms/cms/issues/2813))
- Fixed a bug where Twig could be configured to output times in UTC rather than the system timezone, if a bootstrapped module was loading Twig. ([#2761](https://github.com/craftcms/cms/issues/2761))
- Fixed a SQL error that could occur when upgrading from Craft 2 to Craft 3 with an active user session.
- Fixed various SQL errors that could occur when upgrading from Craft 2 to Craft 3, if there were any lingering Craft 3 database tables from a previous upgrade attempt.
- Fixed a bug where the Clear Caches tool was deleting the `.gitignore` file inside `web/cpresources/`. ([#2823](https://github.com/craftcms/cms/issues/2823))
- Fixed the vertical positioning of checkboxes in the Control Panel. ([#2825](https://github.com/craftcms/cms/issues/2825))
- Fixed a JavaScript error that could occur if an element type’s class name contained `\u`. ([#2826](https://github.com/craftcms/cms/issues/2826))

## 3.0.4 - 2018-04-24

### Added
- Added the `craft.globalSets()` template function. ([#2790](https://github.com/craftcms/cms/issues/2790))
- Added the `hasDescendants` element query param. ([#2786](https://github.com/craftcms/cms/issues/2786))
- Added `craft\elements\User::hasDashboard`.

### Changed
- Sections and category groups now ignore posted Template settings for sites that don’t have URI Formats.
- Control Panel resources are once again eager-published. ([#2763](https://github.com/craftcms/cms/issues/2763))
- `entries/save-entries` and `categories/save-category` actions now include the `slug` for responses that accept JSON. ([#2792](https://github.com/craftcms/cms/issues/2792))
- Most `craft\services\Path` methods now have a `$create` argument, which can be set to `false` to prevent the directory from being created if it doesn’t exist yet.
- Craft no longer creates directories when it just needed to clear it. ([#2771](https://github.com/craftcms/cms/issues/2771))
- `craft\services\Config::setDotEnvVar()` now sets the environment variable for the current request, in addition to updating the `.env` file.
- Removed `craft\controllers\AssetsController::actionDownloadTempAsset()`.
- User now must be logged in to use the Asset Preview File functionality.

### Fixed
- Fixed a bug where users would regain all default Dashboard widgets if all widgets were removed. ([#2769](https://github.com/craftcms/cms/issues/2769))
- Fixed a bug where you would get a “not a valid language” error message when creating a new site using certain languages.
- Fixed a bug where database connection settings that were set by the `setup` command weren’t always taking effect in time for the CLI installer. ([#2774](https://github.com/craftcms/cms/issues/2774))
- Fixed a bug where empty Plain Text fields were getting empty string values rather than `null`.
- Fixed a bug where elements within relational fields could have two thumbnails. ([#2785](https://github.com/craftcms/cms/issues/2785))
- Fixed a bug where it was not possible to pass a `--table-prefix` argument to the `setup/db-creds` command. ([#2791](https://github.com/craftcms/cms/pull/2791))
- Fixed an error that occurred for users without permission to perform updates, if available update info wasn’t cached.
- Fixed an error that occurred when `craft\elements\Asset::sources()` was called in a console request. ([#2798](https://github.com/craftcms/cms/issues/2798))
- Fixed JavaScript errors that could occur on the front-end after deleting Matrix blocks. ([#2799](https://github.com/craftcms/cms/pull/2799))

## 3.0.3.1 - 2018-04-18

### Fixed
- Fixed an error that occurred when editing an entry if any of the entry’s revisions were created with an entry type that no longer exists.
- Fixed an error that could occur when saving an asset. ([#2764](https://github.com/craftcms/cms/issues/2764))
- Fixed a bug where Craft assumed an asset was missing if there was an error when indexing it. ([#2763](https://github.com/craftcms/cms/issues/2763))

## 3.0.3 - 2018-04-17

### Added
- Added `craft\elements\Entry::updateTitle()`.
- Added `Yii::alias()`.

### Changed
- New sites’ Base URLs now default to `@web/`.
- Textual custom fields now ensure that they don’t contain 4+ byte characters. ([#2725](https://github.com/craftcms/cms/issues/2725))
- It is no longer expected that all of the `defaultSearchTermOptions` config setting options will be set if any of the default option values need to be overridden. ([#2737](https://github.com/craftcms/cms/issues/2737))
- Control Panel panes now have at least 48 pixels of bottom padding. ([#2744](https://github.com/craftcms/cms/issues/2744))
- Craft now intercepts 404-ing resource requests, and publishes the resources on the fly.
- The Clear Caches utility now has a “Control Panel resources” option.
- The Clear Caches utility now sorts the cache options alphabetically.
- When enabling new sites for a section, the new sites’ content is now based on the primary site’s content, if the section was and still is enabled for the primary site. ([#2748](https://github.com/craftcms/cms/issues/2748))
- Improved the responsiveness of element indexes.
- `Craft.BaseElementIndexView` now has a `loadMoreElementsAction` setting. ([#2762](https://github.com/craftcms/cms/pull/2762))

### Fixed
- Fixed a bug where the Clear Caches utility was not deleting template caches. ([#2720](https://github.com/craftcms/cms/issues/2720))
- Fixed a bug where the Plugin Store was not displaying payment errors on checkout.
- Fixed a bug where Control Panel-defined routes that contained special regular expression characters weren’t working. ([#2721](https://github.com/craftcms/cms/issues/2721))
- Fixed a bug where it was not possible to save system messages in some cases.
- Fixed a bug where static translations within dynamic entry title formats were getting translated using the current site’s language, rather than the entry’s language. ([#2722](https://github.com/craftcms/cms/issues/2722))
- Fixed a bug where deprecation errors for some date formatting methods were not escaping backslashes.
- Fixed a bug where plugins’ “Last update” timestamps in the Plugin Store weren’t getting formatted correctly in Safari. ([#2733](https://github.com/craftcms/cms/issues/2733))
- Fixed references to a nonexistent `Craft.eot` file in the Control Panel CSS. ([#2740](https://github.com/craftcms/cms/issues/2740))
- Fixed a bug where the default PostgreSQL database restore command wasn’t setting the `PGPASSWORD` environment variable. ([#2741](https://github.com/craftcms/cms/pull/2741))
- Fixed an error that could occur if the system time zone was not supported by the ICU library, on environments with the Intl extension loaded.
- Fixed a bug where several administrative fields had translatable icons. ([#2742](https://github.com/craftcms/cms/issues/2742))
- Fixed a bug where `craft\controllers\PluginStoreController::actionSavePluginLicenseKeys()` was trying to set a plugin license key for plugins which were not installed.

### Security
- Fixed a bug assets were not getting cleansed on upload. ([#2709](https://github.com/craftcms/cms/issues/2709))

## 3.0.2 - 2018-04-10

### Added
- Added the `EVENT_BEFORE_DELETE_CACHES` and `EVENT_AFTER_DELETE_CACHES` events to `craft\services\TemplateCaches`.
- Added `craft\events\DeleteTemplateCachesEvent`.

### Changed
- Craft now deletes all compiled templates whenever Craft or a plugin is updated. ([#2686](https://github.com/craftcms/cms/issues/2686))
- The Plugin Store now displays commercial plugins’ renewal prices. ([#2690](https://github.com/craftcms/cms/issues/2690))
- The Plugin Store no longer shows the “Upgrade Craft CMS” link if Craft is already running (and licensed to run) the Pro edition. ([#2713](https://github.com/craftcms/cms/issues/2713))
- Matrix fields now set `$propagating` to `true` when saving Matrix blocks, if the owner element is propagating.
- `craft\helpers\ArrayHelper::toArray()` no longer throws a deprecation error when a string without commas is passed to it. ([#2711](https://github.com/craftcms/cms/issues/2711))
- Editable tables now support an `html` column type, which will output cell values directly without encoding HTML entities. ([#2716](https://github.com/craftcms/cms/pull/2716))
- `Craft.EditableTable` instances are now accessible via `.data('editable-table')` on their `<table>` element. ([#2694](https://github.com/craftcms/cms/issues/2694))
- Updated Composer to 1.6.3. ([#2707](https://github.com/craftcms/cms/issues/2707))
- Updated Garnish to 0.1.22. ([#2689](https://github.com/craftcms/cms/issues/2689))

### Fixed
- Fixed an error that could occur in the Control Panel if any plugins with licensing issues were installed. ([#2691](https://github.com/craftcms/cms/pull/2691))
- Fixed a bug on the Plugin Store’s Payment screen where the “Use a new credit card” radio option would not get selected automatically even if it was the only one available.
- Fixed a bug where `craft\web\assets\vue\VueAsset` didn’t respect the `useCompressedJs` config setting.
- Fixed an error that occurred when saving a Single entry over Ajax. ([#2687](https://github.com/craftcms/cms/issues/2687))
- Fixed an error that could occur when disabling a site on a Single section. ([#2695](https://github.com/craftcms/cms/issues/2695))
- Fixed an error that could occur on requests without a content type on the response. ([#2704](https://github.com/craftcms/cms/issues/2704))
- Fixed a bug where the `includeSubfolders` asset query param wasn’t including results in the parent folder. ([#2706](https://github.com/craftcms/cms/issues/2706))
- Fixed an error that could occur when querying for users eager-loaded with their photos, if any of the resulting users didn’t have a photo. ([#2708](https://github.com/craftcms/cms/issues/2708))
- Fixed a bug where relational fields within Matrix fields wouldn’t save relations to elements that didn’t exist on all of the sites the owner element existed on. ([#2683](https://github.com/craftcms/cms/issues/2683))
- Fixed a bug where relational fields were ignoring disabled related elements in various functions, including required field validation and value serialization.
- Fixed an error that would occur if a new custom field was created and added to an element’s field layout, and its value was accessed, all in the same request. ([#2705](https://github.com/craftcms/cms/issues/2705))
- Fixed a bug where the `id` param was ignored when used on an eager-loaded elements’ criteria. ([#2717](https://github.com/craftcms/cms/issues/2717))
- Fixed a bug where the default restore command for MySQL wouldn’t actually restore the database. ([#2714](https://github.com/craftcms/cms/issues/2714))

## 3.0.1 - 2018-04-04

### Deprecated
- Brought back and deprecated the `Craft::Personal` and `Craft::Client` constants.

### Fixed
- Fixed a bug where elements’ `getNext()` and `getPrev()` methods were modifying the element query passed into them. ([#2160](https://github.com/craftcms/cms/issues/2160))
- Fixed a bug where Table fields could be pre-populated with one too many rows. ([#2680](https://github.com/craftcms/cms/pull/2680))

### Security
- Craft no longer sends exception messages to error templates, unless the exception is an instance of `yii\base\UserException`.

## 3.0.0.2 - 2018-04-04

### Fixed
- Fixed a bug where Craft Pro installs were getting identified as Craft Solo in the Control Panel.

## 3.0.0 - 2018-04-04

### Added
- The codebase has been completely rewritten and refactored to improve performance, maintainability, and extensibility.
- Craft can now be [installed](https://docs.craftcms.com/v3/installation.html) via Composer in addition to a zip file. ([#895](https://github.com/craftcms/cms/issues/895))
- Craft’s setup wizard is now available as a CLI tool in addition to the web-based one.
- [Plugins](https://docs.craftcms.com/v3/plugin-intro.html) are now loaded as Composer dependencies, and implemented as extensions of [Yii modules](http://www.yiiframework.com/doc-2.0/guide-structure-modules.html).
- Added [multi-site](https://docs.craftcms.com/v3/sites.html) support.
- Added the Plugin Store, where plugins can be discovered, trialled, and purchased. ([#808](https://github.com/craftcms/cms/issues/808))
- Plugins can now be updated and removed from within the Control Panel.
- Asset sources are now called “volumes”, and plugins can supply their own volume types.
- Added the Image Editor, which can be used to rotate, crop, and flip images, as well as set focal points on them.
- Added asset previews, which can be triggered via a “Preview file” action on the Assets index, or with a `shift` + `spacebar` keyboard shortcut throughout the Control Panel.
- Asset editor HUDs now show image previews. ([#837](https://github.com/craftcms/cms/issues/837))
- Added the “Utilities” section to the Control Panel, replacing the Tools area of the Settings page.
- Added the Debug Toolbar, powered by the [Debug Extension for Yii 2](http://www.yiiframework.com/doc-2.0/guide-tool-debugger.html).
- Added support for [Content Migrations](https://docs.craftcms.com/v3/content-migrations.html).
- Added support for PostgreSQL.

### Changed
- The Control Panel has been redesigned for better usability, readability and responsiveness.
- Renamed all “URL Format” things to “URI Format”, in the Control Panel UI and in the code.
- Added the “Propagate entries across all enabled sites?” section setting. If disabled, entries will only be associated with the site they were created on. ([#2330](https://github.com/craftcms/cms/issues/2330))
- Structure sections and category groups no longer have Nested URL Format settings. (It’s still possible to achieve the same result with a single URI Format setting.)
- When an entry type is updated, Craft now re-saves all entries of that type.
- When a category is deleted, its nested categories are no longer deleted with it.
- Craft no longer re-saves *all* localizable elements after a new site is created; entries and Matrix blocks are skipped, and plugins that supply custom element types must now re-save their elements manually as well.
- The “New entry” and “New category” buttons on Entries and Categories index pages now load the Edit page for the currently-selected site. ([#2236](https://github.com/craftcms/cms/issues/2236))
- Elements now validate that custom field values will fit within their database columns, for fields with textual or numeric column types.
- User photos are now assets. ([#933](https://github.com/craftcms/cms/issues/933))
- Assets now have a “Link” table attribute option.
- Volumes’ “Base URL” settings can now begin with `@web`, which is an alias for the root URL that Craft is running from.
- Local volumes’ “File System Path” settings can now begin with `@webroot`, which is an alias for the path to the directory that `index.php` lives in.
- Global Sets’ field layouts can now have custom tabs.
- Color inputs can now be left blank.
- Color values within Table fields are now represented by `craft\fields\data\ColorData` objects.
- Element titles now get a validation error if they contain any 4+ byte characters (like emoji), on servers running MySQL. ([#2513](https://github.com/craftcms/cms/issues/2513))
- Lightswitch fields that don’t have a value yet will now be assigned the default field value, even for existing elements. ([#2404](https://github.com/craftcms/cms/issues/2404))
- The system installer now sets the initial admin account’s preferred language to the site language selected in the installation wizard. ([#2480](https://github.com/craftcms/cms/issues/2480))
- Table fields now have “Min Rows”, “Max Rows”, and “Add Row Label” settings. ([#2372](https://github.com/craftcms/cms/issues/2372))
- Table fields now have “Date”, “Time”, “Lightswitch”, and “Color” column type options.
- Color fields now return a `craft\fields\data\ColorData` object, with `hex`, `rgb`, `red`, `green`, `blue`, `r`, `g`, `b`, and `luma` properties.
- Matrix fields now have “Manage blocks on a per-site basis”, “Min Blocks”, and “Max Blocks” settings.
- Matrix fields with only one block type, and equal values for the Min Blocks and Max Blocks settings, now hide the UI for adding and deleting blocks.
- Matrix fields with only one block type will now auto-create the minimum number of blocks required by the field, per the Min Blocks setting, for new elements. ([#850](https://github.com/craftcms/cms/issues/850))
- The `migrate/up` console command will now update the appropriate schema version in the database after successfully completing all migrations. ([#1907](https://github.com/craftcms/cms/issues/1907))
- Users can now set their preferred language to any supported application language. ([#847](https://github.com/craftcms/cms/issues/847))
- Users are no longer logged out when verifying a new email address on their own account. ([#1421](https://github.com/craftcms/cms/issues/1421))
- Users no longer get an exception or error message if they click on an invalid/expired email verification link and are already logged in. Instead they’ll be redirected to wherever they would normally be taken immediately after logging in. ([#1422](https://github.com/craftcms/cms/issues/1422))
- If anything prevents a user from being deleted, any changes that were made in preparation for deleting the user are now rolled back.
- Added `webp` as a web-safe image format.
- Craft now checks if the current installation can manipulate an image instead of checking against a predefined list. ([#1648](https://github.com/craftcms/cms/issues/1648), [#1545](https://github.com/craftcms/cms/issues/1545))
- The `getCsrfInput()` global function has been renamed to `csrfInput()`. (getCsrfInput() still works but produces a deprecation error.)
- The `{% cache %}` tag no longer includes the query string when storing the cache URL.
- Added the `|timestamp` Twig filter, for formatting a date as a user-friendly timestamp.
- Added the `|datetime` Twig filter, for formatting a date with a localized date+time format.
- Added the `|time` Twig filter, for formatting a date with a localized time format.
- Added the `|multisort` Twig filter, which duplicates an array and sorts it with [craft\helpers\ArrayHelper::multisort()](http://www.yiiframework.com/doc-2.0/yii-helpers-basearrayhelper.html#multisort()-detail).
- Added the `|atom` and `|rss` Twig filters, for formatting dates in Atom and RSS date formats, respectively.
- Added the `|column` Twig filter, for capturing the key/property values of a series of arrays/objects.
- Added the `|index` Twig filter, for indexing an array of arrays/objects by one of their keys/values.
- Added the `|filterByValue` Twig filter.
- Added the `|duration` Twig filter, which converts a `DateInterval` object into a human-readable duration.
- The `t` filter now always defaults to translating the given string using the `site` category unless it is otherwise specified (e.g. `myString|t('pluginhandle')`).
- The `|date` filter can be passed `'short'`, `'medium'`, `'long'`, and `'full'`, which will format the date with a localized date format.
- It is now possibly to customize the SQL of [element queries](https://docs.craftcms.com/v3/element-queries.html), and there are more choices on how the data should be returned.
- Element queries are no longer limited to 100 results by default.
- The “Failed” message in the queue HUD in the Control Panel now shows the full error message as alt text. ([#855](https://github.com/craftcms/cms/issues/855))
- Added the `convertFilenamesToAscii` config setting.
- Added the `preserveExifData` config setting, `false` by default and requires Imagick. ([#2034](https://github.com/craftcms/cms/issues/2034))
- Added the `aliases` config setting, providing an easy way to define custom [aliases](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html).
- Removed support for automatically determining the values for the `omitScriptNameInUrls` and `usePathInfo` config settings.
- It’s now possible to override Craft’s application config via `config/app.php`.
- It’s now possible to override volume settings via `config/volumes.php`.
- It’s now possible to override all plugins’ settings via `config/<plugin-handle>.php`.
- Renamed the `runTasksAutomatically` config setting to `runQueueAutomatically`.
- The `translationDebugOutput` config setting will now wrap strings with `@` characters if the category is `app`, `$` if the category is `site`, and `%` for anything else.
- All user-defined strings in the Control Panel (e.g. section names) are now translated using the `site` category, to prevent translation conflicts with Craft’s own Control Panel translations.
- Routes can now be stored on a per-site basis, rather than per-locale.
- Web requests are now logged to `storage/logs/web.log`.
- Web requests that result in 404 errors are now logged to `storage/logs/web-404s.log`.
- Console requests are now logged to `storage/logs/console.log`.
- Queue requests are now logged to `storage/logs/queue.log`.
- Craft 3 now requires PHP 7.0.0 or later.
- Craft 3 now requires MySQL 5.5+ or PostgreSQL 9.5+.
- Craft now takes advantage of the [PHP Intl extension](http://php.net/manual/en/book.intl.php) when available.
- Craft now uses Stringy for better string processing support.
- Craft now uses Flysystem for better asset volume support.
- Craft now uses Swiftmailer for better email sending support.
- Craft now uses the [Yii 2 Queue Extension](https://github.com/yiisoft/yii2-queue) for managing background tasks.
- Craft now uses the Zend Feed library for better RSS and Atom processing support.
- Updated Yii to 2.0.15.1.
- Updated Twig to 2.4.
- Updated Guzzle to 6.3.

### Deprecated
- Many things have been deprecated. See [Changes in Craft 3](https://docs.craftcms.com/v3/changes-in-craft-3.html) for a complete list.

### Fixed
- Fixed a bug where a PHP session would be started on every template rendering request whether it was needed or not. ([#1765](https://github.com/craftcms/cms/issues/1765))

### Security
- Craft uses OpenSSL for encryption rather than mcrypt, which is far more secure and well-maintained.
