# Release Notes for Craft CMS 3.x

## 3.0.1 - 2018-04-04

### Deprecated
- Brought back and depretated the `Craft::Personal` and `Craft::Client` constants.

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
