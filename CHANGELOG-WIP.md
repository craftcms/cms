# Release Notes for Craft CMS 4.1 (WIP)

### Added
- The `AdminTable` Vue component can now be included into other Vue apps, in addition to being used as a standalone app. ([#11107](https://github.com/craftcms/cms/pull/11107))
- Added a `one()` alias for `first()` to collections. ([#11134](https://github.com/craftcms/cms/discussions/11134))
- Added `craft\console\controllers\UsersController::$activate`.
- Added `craft\elements\conditions\ElementCondition::$sourceKey`.
- Added `craft\models\Section::description`
- Added `craft\models\EntryTypes::description`

### Changed
- Improved pagination UI accessibility. ([#11126](https://github.com/craftcms/cms/pull/11126))
- Improved element index accessibility. ([#11169](https://github.com/craftcms/cms/pull/11169))
- Live Preview now always shows a “Refresh” button, regardless of whether the preview target has auto-refresh enabled. ([#11160](https://github.com/craftcms/cms/discussions/11160)) 
- Entry Type condition rules now allow multiple selections. ([#11124](https://github.com/craftcms/cms/pull/11124))
- Element index filters now only show condition rules for the custom fields that are used by the field layouts in the selected source, if a native source is selected. ([#11187](https://github.com/craftcms/cms/discussions/11187))
- Sites’ Language settings now display the locale IDs as option hints, rather than the languages’ native names. ([#11195](https://github.com/craftcms/cms/discussions/11195))
- Selectize options can now specify searchable `keywords` that won’t be visible in the UI.
- Selectize inputs will now include their options’ values as search keywords.
- The `users/create` command now asks whether the user should be activated when saved.

### Removed
- Removed `craft\elements\conditions\entries\EntryTypeCondition::$sectionUid`.
- Removed `craft\elements\conditions\entries\EntryTypeCondition::$entryTypeUid`.
