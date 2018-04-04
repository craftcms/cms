# Craft CMS Changelog

## 3.0.0 - 2018-04-04

### Added
- The codebase has been completely rewritten and refactored, powered by [Yii 2](http://www.yiiframework.com/).
- Added multi-site support.
- Craft can now be installed via Composer in addition to a zip file. (See the [Installation](https://docs.craftcms.com/v3/installation.html) guide for step-by-step instructions on how to install Craft 3.) ([#895](https://github.com/craftcms/cms/issues/895))
- Craft’s setup wizard is now available as a CLI tool in addition to the web-based one.
- Plugins are now loaded as Composer dependencies, and implemented as extensions of [Yii modules](http://www.yiiframework.com/doc-2.0/guide-structure-modules.html). (See [Intro to Plugin Development](https://docs.craftcms.com/v3/plugin-intro.html) for more info on building plugins for Craft 3.)
- Added the Plugin Store, where plugins can be discovered, trialled, and purchased. ([#808](https://github.com/craftcms/cms/issues/808))
- Plugins can now be updated and removed from within the Control Panel.
- Asset sources are now called “volumes”, and plugins can supply their own volume types.
- Added the Image Editor, which can be used to rotate, crop, and flip images, as well as set focal points on them.
- Added asset previews, which can be triggered via a “Preview file” action on the Assets index, or with a `shift` + `spacebar` keyboard shortcut throughout the Control Panel.
- Asset editor HUDs now show image previews. ([#837](https://github.com/craftcms/cms/issues/837))
- Added the “Utilities” section to the Control Panel, replacing the Tools area of the Settings page.
- Added the Debug Toolbar, powered by the [Debug Extension for Yii 2](http://www.yiiframework.com/doc-2.0/guide-tool-debugger.html).
- Added support for PostgreSQL.
- Replaced background task support with the [Yii 2 Queue Extension](https://github.com/yiisoft/yii2-queue)
- It’s now possible to override Craft’s application config via  `config/app.php`.
- Craft 3 now requires PHP 7.0.0 or later.
- Craft 3 now requires MySQL 5.5 or later (if using MySQL).

### Changed
- The Control Panel has been redesigned for better usability, readability and responsiveness.
- User photos are now assets. ([#933](https://github.com/craftcms/cms/issues/933))
- Assets now have a “Link” table attribute option.
- Web requests are now logged to `storage/logs/web.log`.
- Web requests that result in 404 errors are now logged to `storage/logs/web-404s.log`.
- Console requests are now logged to `storage/logs/console.log`.
- Logs that occur during `queue` requests now get saved in `storage/logs/queue.log`.
- URL validation now allows international domain names.
- All user-defined strings in the Control Panel (e.g. section names) are now translated using the `'site'` category, to prevent translation conflicts with Craft’s own Control Panel translations.
- The `translationDebugOutput` config setting will now wrap strings with `@` characters if the category is `'app'`, `$` if the category is `'site'`, and `%` for anything else.
- The `t` filter now always defaults to translating the given string using the `'site'` category unless it is otherwise specified (e.g. `myString|t('pluginhandle')`).
- Routes can now be stored on a per-site basis, rather than per-locale.
- Renamed all "URL Format" things to "URI Format", in the Control Panel UI and in the code.
- Editable table fields in the Control Panel can now specify unique `select` column options on a per-cell basis, by setting the cell’s value to an object with `options` and `value` keys.
- Header cells within editable table fields in the Control Panel can now specify their `<th>` class names.
- Editable tables can now define placeholder text within text columns.
- It’s now possible to specify default row values for editable tables by passing a `defaultValues` object to `_includes/forms/editableTable.html`.
- Added the “Propagate entries across all enabled sites?” section setting. If disabled, entries will only be associated with the site they were created on. ([#2330](https://github.com/craftcms/cms/issues/2330))
- Structure sections and category groups no longer have Nested URL Format settings. (It's still possible to achieve the same result with a single URI Format setting.)
- When a category is deleted, its nested categories are no longer deleted with it.
- The `|date` filter can be passed `'short'`, `'medium'`, `'long'`, and `'full'`, which will format the date with a localized date format.
- The `getCsrfInput()` global function has been renamed to `csrfInput()`. (getCsrfInput() still works but produces a deprecation error.)
- The `{% cache %}` tag no longer includes the query string when storing the cache URL.
- It’s no longer possible to run new migrations while Craft is in Maintenance Mode, preventing the possibility of two people running migrations at the same time.
- The `migrate/up` console command will now update the appropriate schema version in the database after successfully completing all migrations. ([#1907](https://github.com/craftcms/cms/issues/1907))
- When an entry type is updated, Craft now re-saves all entries of that type.
- Craft no longer re-saves *all* localizable elements after a new site is created; entries and Matrix blocks are skipped, and plugins that supply custom element types must now re-save their elements manually as well.
- The “New entry” and “New category” buttons on Entries and Categories index pages now load the Edit page for the currently-selected site. ([#2236](https://github.com/craftcms/cms/issues/2236))
- Element types’ `statuses()` method can now specify status colors, by defining a status using an array with `label` and `color` keys.
- Elements now validate that custom field values will fit within their database columns, for fields with textual or numeric column types.
- Volumes’ “Base URL” settings can now begin with `@web`, which is an alias for the root URL that Craft is running from.
- Local volumes’ “File System Path” settings can now begin with `@webroot`, which is an alias for the path to the directory that `index.php` lives in.
- Users are no longer logged out when verifying a new email address on their own account. ([#1421](https://github.com/craftcms/cms/issues/1421))
- Users no longer get an exception or error message if they click on an invalid/expired email verification link and are already logged in. Instead they’ll be redirected to wherever they would normally be taken immediately after logging in. ([#1422](https://github.com/craftcms/cms/issues/1422))
- If anything prevents a user from being deleted, any changes that were made in preparation for deleting the user are now rolled back.
- Removed support for automatically determining the values for the `omitScriptNameInUrls` and `usePathInfo` config settings.
- Renamed the `runTasksAutomatically` config setting to `runQueueAutomatically`.
- Craft now checks if the current installation can manipulate an image instead of checking against a predefined list. ([#1648](https://github.com/craftcms/cms/issues/1648), [#1545](https://github.com/craftcms/cms/issues/1545))
- Color inputs can now be left blank.
- Color values within Table fields are now represented by `craft\fields\data\ColorData` objects.
- Matrix fields with only one block type, and equal values for the Min Blocks and Max Blocks settings, now hide the UI for adding and deleting blocks.
- Matrix fields with only one block type will now auto-create the minimum number of blocks required by the field, per the Min Blocks setting, for new elements. ([#850](https://github.com/craftcms/cms/issues/850))
- Element titles now get a validation error if they contain any 4+ byte characters (like emoji), on servers running MySQL. ([#2513](https://github.com/craftcms/cms/issues/2513))
- Lightswitch fields that don’t have a value yet will now be assigned the default field value, even for existing elements. ([#2404](https://github.com/craftcms/cms/issues/2404))
- The system installer now sets the initial admin account’s preferred language to the site language selected in the installation wizard. ([#2480](https://github.com/craftcms/cms/issues/2480))
- Table fields now have “Min Rows”, “Max Rows”, and “Add Row Label” settings. ([#2372](https://github.com/craftcms/cms/issues/2372))
- Table fields now have “Date”, “Time”, “Lightswitch”, and “Color” column type options.
- Color fields now return a `craft\fields\data\ColorData` object, with `hex`, `rgb`, `red`, `green`, `blue`, `r`, `g`, `b`, and `luma` properties.
- Matrix fields now have a "Manage blocks on a per-site basis" setting.
- Matrix fields now have a “Min Blocks” setting. ([#850](https://github.com/craftcms/cms/issues/850))
- Matrix fields now have a “Size” setting.
- Added the `|timestamp` Twig filter, for formatting a date as a user-friendly timestamp.
- Added the `|datetime` Twig filter, for formatting a date with a localized date+time format.
- Added the `|time` Twig filter, for formatting a date with a localized time format.
- Added the `|multisort` Twig filter, which duplicates an array and sorts it with [craft\helpers\ArrayHelper::multisort()](http://www.yiiframework.com/doc-2.0/yii-helpers-basearrayhelper.html#multisort()-detail).
- Added the `|atom` and `|rss` Twig filters, for formatting dates in Atom and RSS date formats, respectively.
- Added the `|column` Twig filter, for capturing the key/property values of a series of arrays/objects.
- Added the `|index` Twig filter, for indexing an array of arrays/objects by one of their keys/values.
- Added the `|filterByValue` Twig filter.
- Added the `|duration` Twig filter, which converts a `DateInterval` object into a human-readable duration.
- Added the `convertFilenamesToAscii` config setting.
- Added the `preserveExifData` config setting, `false` by default and requires Imagick. ([#2034](https://github.com/craftcms/cms/issues/2034))
- Added the `aliases` config setting, providing an easy way to define custom [aliases](http://www.yiiframework.com/doc-2.0/guide-concept-aliases.html).
- Added `webp` as a web-safe image format.
- It is now possibly to customize the SQL of element queries, and there are more choices on how the data should be returned.
- Element queries are no longer limited to 100 results by default.

### Deprecated
- Many things have been deprecated. See [Changes in Craft 3](https://docs.craftcms.com/v3/changes-in-craft-3.html).

### Fixed
- Fixed a bug where a PHP session would be started on every template rendering request whether it was needed or not. ([#1765](https://github.com/craftcms/cms/issues/1765))
