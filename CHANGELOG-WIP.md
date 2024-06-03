# Release Notes for Craft CMS 5.2 (WIP)

### Content Management
- Live Preview now supports tabs, UI elements, and tab/field conditions. ([#15112](https://github.com/craftcms/cms/pull/15112))
- Live Preview now has a dedicated “Save” button. ([#15112](https://github.com/craftcms/cms/pull/15112))
- It’s now possible to edit assets’ alternative text from the Assets index page. ([#14893](https://github.com/craftcms/cms/discussions/14893))
- Double-clicking anywhere within a table row on an element index page will now open the element’s editor slideout. ([#14379](https://github.com/craftcms/cms/discussions/14379))
- Element index checkboxes no longer have a lag when deselected, except within element selection modals. ([#14896](https://github.com/craftcms/cms/issues/14896))
- Relational field condition rules no longer factor in the target elements’ statuses or sites. ([#14989](https://github.com/craftcms/cms/issues/14989))
- Element cards now display provisional changes, with an “Edited” label. ([#14975](https://github.com/craftcms/cms/pull/14975))
- Improved mobile styling. ([#14910](https://github.com/craftcms/cms/pull/14910))
- Improved the look of slideouts.
- Table views within element index pages are no longer scrolled directly. ([#14927](https://github.com/craftcms/cms/pull/14927))
- Improved the look of user gradicons when selected.
- “Save and continue editing” actions now restore the page’s scroll position on reload.
- “Remove” element actions within relational fields will now remove all selected elements, if the target element is selected. ([#15078](https://github.com/craftcms/cms/issues/15078))
- Action menus are now displayed within the page toolbar, rather than in the breadcrumbs. ([#14913](https://github.com/craftcms/cms/discussions/14913), [#15070](https://github.com/craftcms/cms/pull/15070))
- Site menus within element selector modals now filter out sites that don’t have any sources. ([#15091](https://github.com/craftcms/cms/discussions/15091))
- The meta sidebar toggle has been moved into the gutter between the content pane and meta sidebar. ([#15117](https://github.com/craftcms/cms/pull/15117))

### Accessibility
- Added the “Status” column option to category, entry, and user indexes. ([#14968](https://github.com/craftcms/cms/pull/14968))
- Element cards now display a textual status label rather than just the indicator. ([#14968](https://github.com/craftcms/cms/pull/14968))
- Darkened the color of page sidebar toggle icons to meet the minimum contrast for UI components.
- Darkened the color of context labels to meet the minimum contrast for text.
- Darkened the color of footer links to meet the minimum contrast for text.
- Set the language of the Craft edition in the footer, to improve screen reader pronunciation for non-English languages.
- The accessible name of “Select site” buttons is now translated to the current language.

### Administration
- Added the `--format` option to the `db/backup` and `db/restore` commands for PostgreSQL installs. ([#14931](https://github.com/craftcms/cms/pull/14931))
- The `db/restore` command now autodetects the backup format for PostgreSQL installs, if `--format` isn’t passed. ([#14931](https://github.com/craftcms/cms/pull/14931))
- The `install` command and web-based installer now validate the existing project config files at the outset, and abort installation if there are any issues.
- The `resave/entries` command now has an `--all-sections` flag.
- The web-based installer now displays the error message when installation fails.
- Edit Entry Type pages now have a “Delete” action. ([#14983](https://github.com/craftcms/cms/discussions/14983))
- After creating a new field, field layout designers now set their search value to the new field’s name. ([#15080](https://github.com/craftcms/cms/discussions/15080))
- GraphQL schema edit pages now have a “Save and continue editing” alternate action.
- Volumes’ “Subpath” and “Transform Subpath” settings can now be set to environment variables. ([#15087](https://github.com/craftcms/cms/discussions/15087))
- The system edition can now be defined by a `CRAFT_EDITION` environment variable. ([#15094](https://github.com/craftcms/cms/discussions/15094))
- The rebrand assets path can now be defined by a `CRAFT_REBRAND_PATH` environment variable. ([#15110](https://github.com/craftcms/cms/pull/15110))

### Development
- Added the `{% expires %}` tag, which simplifies setting cache headers on the response. ([#14969](https://github.com/craftcms/cms/pull/14969))
- Added the `withCustomFields` element query param. ([#15003](https://github.com/craftcms/cms/pull/15003))
- Entry queries now support passing `*` to the `section` param, to filter the results to all section entries. ([#14978](https://github.com/craftcms/cms/discussions/14978))
- Element queries now support passing an element instance, or an array of element instances/IDs, to the `draftOf` param.
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
- Added `craft\base\NestedElementTrait::$updateSearchIndexForOwner`.
- Added `craft\db\getBackupFormat()`.
- Added `craft\db\getRestoreFormat()`.
- Added `craft\db\setBackupFormat()`.
- Added `craft\db\setRestoreFormat()`.
- Added `craft\enums\Color::tryFromStatus()`.
- Added `craft\events\InvalidateElementcachesEvent::$element`.
- Added `craft\fields\BaseRelationField::existsQueryCondition()`.
- Added `craft\helpers\Cp::componentStatusIndicatorHtml()`.
- Added `craft\helpers\Cp::componentStatusLabelHtml()`.
- Added `craft\helpers\Cp::statusLabelHtml()`.
- Added `craft\helpers\DateTimeHelper::relativeTimeStatement()`.
- Added `craft\helpers\DateTimeHelper::relativeTimeToSeconds()`.
- Added `craft\helpers\ElementHelper::swapInProvisionalDrafts()`.
- Added `craft\helpers\StringHelper::indent()`.
- Added `craft\models\Volume::getTransformSubpath()`.
- Added `craft\models\Volume::setTransformSubpath()`.
- Added `craft\queue\Queue::getJobId()`.
- `craft\base\Element::defineTableAttributes()` now returns common attribute definitions used by most element types.
- `craft\elements\ElementCollection::with()` now supports collections made up of multiple element types.
- `craft\models\Volume::getSubpath()` now has a `$parse` argument.
- `craft\services\Drafts::applyDraft()` now has a `$newAttributes` argument.
- Added the `reloadOnBroadcastSave` setting to `Craft.ElementEditor`. ([#14814](https://github.com/craftcms/cms/issues/14814))
- Added the `waitForDoubleClicks` setting to `Garnish.Select`, `Craft.BaseElementIndex`, and `Craft.BaseElementIndexView`.

### System
- Improved overall system performance. ([#15003](https://github.com/craftcms/cms/pull/15003))
- Improved the performance of `exists()` element queries.
- Improved the performance of `craft\base\Element::toArray()`.
- The Debug Toolbar now pre-serializes objects stored as request parameters, fixing a bug where closures could prevent the entire Request panel from showing up. ([#14982](https://github.com/craftcms/cms/discussions/14982))
- Batched queue jobs now verify that they are still reserved before each step, and before spawning additional batch jobs. ([#14986](https://github.com/craftcms/cms/discussions/14986))
- The search keyword index is now updated for owner elements, when a nested element is saved directly which belongs to a searchable custom field. 
- Updated Yii to 2.0.50.
- Updated inputmask to 5.0.9.
