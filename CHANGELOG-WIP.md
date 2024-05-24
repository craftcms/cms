# Release notes for Craft CMS 4.10 (WIP)

### Content Management
- Relational field condition rules no longer factor in the target elements’ statuses or sites. ([#14989](https://github.com/craftcms/cms/issues/14989))

### Administration
- Added the `--format` option to the `db/backup` and `db/restore` commands for PostgreSQL installs. ([#14931](https://github.com/craftcms/cms/pull/14931))
- The `db/restore` command now autodetects the backup format for PostgreSQL installs, if `--format` isn’t passed. ([#14931](https://github.com/craftcms/cms/pull/14931))
- The `install` command and web-based installer now validate the existing project config files at the outset, and abort installation if there are any issues.
- The web-based installer now displays the error message when installation fails. 

### Development
- Added `craft\elements\ElementCollection::find()`, which can return an element or elements in the collection based on a given element or ID. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- Added `craft\elements\ElementCollection::fresh()`, which reloads each of the collection elements from the database. ([#15023](https://github.com/craftcms/cms/discussions/15023)) 
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
- Added `craft\helpers\StringHelper::indent()`.
- Added `craft\queue\Queue::getJobId()`.
- `craft\elements\ElementCollection::with()` now supports collections made up of multiple element types.
- Added the `reloadOnBroadcastSave` setting to `Craft.ElementEditor`. ([#14814](https://github.com/craftcms/cms/issues/14814))

### System
- Improved the performance of `exists()` element queries.
- The Debug Toolbar now pre-serializes objects stored as request parameters, fixing a bug where closures could prevent the entire Request panel from showing up. ([#14982](https://github.com/craftcms/cms/discussions/14982))
- Batched queue jobs now verify that they are still reserved before each step, and before spawning additional batch jobs. ([#14986](https://github.com/craftcms/cms/discussions/14986))
