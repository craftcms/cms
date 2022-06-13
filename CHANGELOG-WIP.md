# Release Notes for Craft CMS 4.1 (WIP)

### Added
- Field layouts can now have “Line Break” UI elements. ([#11328](https://github.com/craftcms/cms/discussions/11328))
- Added the `db/drop-all-tables` command. ([#11288](https://github.com/craftcms/cms/pull/11288))
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
- Improved overall control panel accessibility. ([#11297](https://github.com/craftcms/cms/pull/11297), [#11296](https://github.com/craftcms/cms/pull/11296))
- Improved pagination UI accessibility. ([#11126](https://github.com/craftcms/cms/pull/11126))
- Improved element index accessibility. ([#11169](https://github.com/craftcms/cms/pull/11169), [#11200](https://github.com/craftcms/cms/pull/11200), [#11251](https://github.com/craftcms/cms/pull/11251))
- Improved Dashboard accessibility. ([#11217](https://github.com/craftcms/cms/pull/11217), [#11297](https://github.com/craftcms/cms/pull/11297))
- Improved address management accessibility. ([#11397](https://github.com/craftcms/cms/pull/11397))
- Improved Matrix field accessibility. ([#11306](https://github.com/craftcms/cms/pull/11306))
- Improved element selector modals for small screens. ([#11323](https://github.com/craftcms/cms/pull/11323))
- It’s now possible to remove all selected elements from relational fields by pressing <kbd>Backspace</kbd> or <kbd>Delete</kbd> while one of them is focussed.
- Live Preview now always shows a “Refresh” button, regardless of whether the preview target has auto-refresh enabled. ([#11160](https://github.com/craftcms/cms/discussions/11160)) 
- Entry Type condition rules now allow multiple selections. ([#11124](https://github.com/craftcms/cms/pull/11124))
- Element index filters now only show condition rules for the custom fields that are used by the field layouts in the selected source, if a native source is selected. ([#11187](https://github.com/craftcms/cms/discussions/11187))
- It’s now possible to sort entries by their section and type. ([#9192](https://github.com/craftcms/cms/discussions/9192), [#11335](https://github.com/craftcms/cms/discussions/11335))
- It’s now possible to sort assets by their file kind.
- Sites’ Language settings now display the locale IDs as option hints, rather than the languages’ native names. ([#11195](https://github.com/craftcms/cms/discussions/11195))
- Selectize options can now specify searchable `keywords` that won’t be visible in the UI.
- Selectize inputs will now include their options’ values as search keywords.
- Newly-created entries now get placeholder Post Date set on them, so they get sorted appropriately when querying for entries ordered by `postDate`. ([#11272](https://github.com/craftcms/cms/issues/11272)) 
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
- The `searchindex` table is now uses the InnoDB storage engine by default for MySQL installs. ([#11374](https://github.com/craftcms/cms/discussions/11374))
- `Garnish.DELETE_KEY` now refers to the actual <kbd>Delete</kbd> key code, and the <kbd>Backspace</kbd> key code is now referenced by `Garnish.BACKSPACE_KEY`.

### Deprecated
- Deprecated `craft\elements\actions\DeleteAssets`. `craft\elements\actions\Delete` should be used instead.

### Removed
- Removed `craft\elements\conditions\entries\EntryTypeCondition::$sectionUid`.
- Removed `craft\elements\conditions\entries\EntryTypeCondition::$entryTypeUid`.
