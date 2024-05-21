# Release Notes for Craft CMS 5.2 (WIP)

### Content Management
- It’s now possible to edit assets’ alternative text from the Assets index page. ([#14893](https://github.com/craftcms/cms/discussions/14893))
- Double-clicking anywhere within a table row on an element index page will now open the element’s editor slideout. ([#14379](https://github.com/craftcms/cms/discussions/14379))
- Element index checkboxes no longer have a lag when deselected, except within element selection modals. ([#14896](https://github.com/craftcms/cms/issues/14896))
- Relational field condition rules no longer factor in the target elements’ statuses or sites. ([#14989](https://github.com/craftcms/cms/issues/14989))
- Improved mobile styling. ([#14910](https://github.com/craftcms/cms/pull/14910))
- Improved the look of slideouts.
- Table views within element index pages are no longer scrolled directly. ([#14927](https://github.com/craftcms/cms/pull/14927))
- Improved the look of user gradicons when selected.

### Administration
- Added the `--format` option to the `db/backup` and `db/restore` commands for PostgreSQL installs. ([#14931](https://github.com/craftcms/cms/pull/14931))
- The `db/restore` command now autodetects the backup format for PostgreSQL installs, if `--format` isn’t passed. ([#14931](https://github.com/craftcms/cms/pull/14931))
- The `install` command and web-based installer now validate the existing project config files at the outset, and abort installation if there are any issues.
- The `resave/entries` command now has an `--all-sections` flag.
- The web-based installer now displays the error message when installation fails. 

### Development
- Added the `withCustomFields` element query param. ([#15003](https://github.com/craftcms/cms/pull/15003))
- Entry queries now support passing `*` to the `section` param, to filter the results to all section entries. ([#14978](https://github.com/craftcms/cms/discussions/14978))

### Extensibility
- Added `craft\db\getBackupFormat()`.
- Added `craft\db\getRestoreFormat()`.
- Added `craft\db\setBackupFormat()`.
- Added `craft\db\setRestoreFormat()`.
- Added `craft\events\InvalidateElementcachesEvent::$element`.
- `craft\base\Element::defineTableAttributes()` now returns common attribute definitions used by most element types.
- Added `craft\fields\BaseRelationField::existsQueryCondition()`.
- Added `craft\helpers\StringHelper::indent()`.
- Added the `reloadOnBroadcastSave` setting to `Craft.ElementEditor`. ([#14814](https://github.com/craftcms/cms/issues/14814))
- Added the `waitForDoubleClicks` setting to `Garnish.Select`, `Craft.BaseElementIndex`, and `Craft.BaseElementIndexView`.

### System
- Improved overall system performance. ([#15003](https://github.com/craftcms/cms/pull/15003))
- Improved the performance of `exists()` element queries.
- Improved the performance of `craft\base\Element::toArray()`.
- The Debug Toolbar now pre-serializes objects stored as request parameters, fixing a bug where closures could prevent the entire Request panel from showing up. ([#14982](https://github.com/craftcms/cms/discussions/14982))
