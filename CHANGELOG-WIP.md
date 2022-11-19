# Release Notes for Craft CMS 4.4 (WIP)

### Content Management
- Added “Viewable” asset and entry condition rules. ([#12240](https://github.com/craftcms/cms/discussions/12240), [#12266](https://github.com/craftcms/cms/pull/12266))
- Renamed the “Editable” asset and entry condition rules to “Savable”. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Assets, categories, and entries will now redirect to the last-selected source on their index pages when saved. ([#11996](https://github.com/craftcms/cms/discussions/11996))
- Dropdown fields that don’t have a blank option and are missing a value will now include and select a blank option at the beginning of their menu. ([#12235](https://github.com/craftcms/cms/discussions/12235))

### Administration
- Conditional layout components are now identified using a condition icon within field layout designers. ([#12250](https://github.com/craftcms/cms/issues/12250))
- All CLI commands now support an `--isolated` option, which ensures the command is run in isolation. ([#12337](https://github.com/craftcms/cms/discussions/12337), [#12350](https://github.com/craftcms/cms/pull/12350))
- Entries and categories fields now have a "Relate ancestors" setting that can be enabled when a single structured source is selected. [#11749](https://github.com/craftcms/cms/pull/11749)

### Development
- Added the `editable` and `savable` asset query params. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added the `savable` entry query param. ([#12266](https://github.com/craftcms/cms/pull/12266))
- The `editable` entry query param can now be set to `false` to only show entries that _can’t_ be viewed by the current user. ([#12266](https://github.com/craftcms/cms/pull/12266))

### Extensibility
- Console controllers that directly use `craft\console\ControllerTrait` no longer need to call `$this->checkTty()` or `$this->checkRootUser()` themselves; they are now called from `ControllerTrait::init()` and `beforeAction()`.
- Added `craft\console\ControllerTrait::beforeAction()`.
- Added `craft\console\ControllerTrait::init()`.
- Added `craft\console\ControllerTrait::options()`.
- Added `craft\console\ControllerTrait::runAction()`.
- Added `craft\elements\conditions\assets\ViewableConditionRule`. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added `craft\elements\conditions\entries\ViewableConditionRule`. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added `craft\events\DefineInputOptionsEvent`. ([#12351](https://github.com/craftcms/cms/pull/12351))
- Added `craft\fields\BaseOptionsField::EVENT_DEFINE_OPTIONS`. ([#12351](https://github.com/craftcms/cms/pull/12351))
- Renamed `craft\elements\conditions\assets\EditableConditionRule` to `SavableConditionRule`, while preserving the original class name with an alias. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Renamed `craft\elements\conditions\entries\EditableConditionRule` to `SavableConditionRule`, while preserving the original class name with an alias. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added `Craft.ElementFieldSettings`.
- Deprecated `Craft.CategorySelectInput()`. `Craft.BaseElementSelectInput()` should be used instead. [#11749](https://github.com/craftcms/cms/pull/11749)

### System
- Improved element deletion performance. ([#12223](https://github.com/craftcms/cms/pull/12223))
- Updated LitEmoji to v4. ([#12226](https://github.com/craftcms/cms/discussions/12226))
- Fixed a database deadlock error that could occur when updating a relation or structure position for an element that was simultaneously being saved. ([#9905](https://github.com/craftcms/cms/issues/9905))
- Updated Yii to 2.0.47.
