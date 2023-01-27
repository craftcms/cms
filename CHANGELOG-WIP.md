# Release Notes for Craft CMS 4.4 (WIP)

### Content Management
- Volume subfolders are now displayed within the element listing pane on asset indexes, rather than as nested sources in the sidebar. ([#12558](https://github.com/craftcms/cms/pull/12558), [#9171](https://github.com/craftcms/cms/discussions/9171), [#5809](https://github.com/craftcms/cms/issues/5809))
- Asset indexes now display the current subfolder path above the element listing. ([#12558](https://github.com/craftcms/cms/pull/12558))
- It’s now possible to move volume folders and assets to a new location via a new “Move…” bulk element action, rather than via drag-and-drop interactions. ([#12558](https://github.com/craftcms/cms/pull/12558))
- All element sources now have a “Duplicate” action, even if the element type’s `defineActions()` method didn’t include one. ([#12382](https://github.com/craftcms/cms/discussions/12382))
- Element index pages now track the search term in a query param, so the results can be shared. ([#8942](https://github.com/craftcms/cms/discussions/8942), [#12399](https://github.com/craftcms/cms/pull/12399))
- Entries with more than 10 revisions now include a “View all revisions” item within their revision menu, which links to a new revisions index page for the entry that paginates through all its revisions. ([#8609](https://github.com/craftcms/cms/discussions/8609))
- Entries and Categories fields now have “Maintain hierarchy” settings, which become available when a single structured source is selected. ([#8522](https://github.com/craftcms/cms/discussions/8522), [#8748](https://github.com/craftcms/cms/discussions/8748), [#11749](https://github.com/craftcms/cms/pull/11749))
- Entries fields now have a “Branch Limit” setting, which becomes available when “Maintain hierarchy” is enabled, replacing “Min Relations” and “Max Relations”.
- Categories fields now have “Min Relations” and “Max Relations” settings, which become available when “Maintain hierarchy” is disabled, replacing “Branch Limit”.
- Added “Viewable” asset and entry condition rules. ([#12240](https://github.com/craftcms/cms/discussions/12240), [#12266](https://github.com/craftcms/cms/pull/12266))
- Renamed the “Editable” asset and entry condition rules to “Savable”. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Assets, categories, and entries will now redirect to the last-selected source on their index pages when saved. ([#11996](https://github.com/craftcms/cms/discussions/11996))
- Dropdown fields that don’t have a blank option and are missing a value will now include and select a blank option at the beginning of their menu. ([#12235](https://github.com/craftcms/cms/discussions/12235))
- Tip and Warning field layout UI elements can now be marked as dismissible, giving authors the ability to hide them. ([#12188](https://github.com/craftcms/cms/discussions/12188))

### Accessibility
- Improved the announcement menu for screen readers. ([#12361](https://github.com/craftcms/cms/pull/12361))
- Improved keyboard control of the Updates utility. ([#12189](https://github.com/craftcms/cms/pull/12189))
- Improved the color contrast and keyboard control of the Customize Sources modal. ([#12233](https://github.com/craftcms/cms/pull/12233))
- Improved info icons for screen readers. ([#12272](https://github.com/craftcms/cms/pull/12272))
- Removed input autofocussing throughout the control panel. ([#12324](https://github.com/craftcms/cms/discussions/12324), [#12332](https://github.com/craftcms/cms/pull/12332), [#12406](https://github.com/craftcms/cms/pull/12406))
- Improved the login screen for screen readers. ([#12386](https://github.com/craftcms/cms/pull/12386))

### Administration
- Conditional layout components are now identified using a condition icon within field layout designers. ([#12250](https://github.com/craftcms/cms/issues/12250))
- All CLI commands now support an `--isolated` option, which ensures the command is run in isolation. ([#12337](https://github.com/craftcms/cms/discussions/12337), [#12350](https://github.com/craftcms/cms/pull/12350))
- The `plugin/install`, `plugin/uninstall`, `plugin/enable`, and `plugin/disabled` commands now support an `--all` option, which applies the action to all applicable Composer-installed plugins. ([#11373](https://github.com/craftcms/cms/discussions/11373), [#12218](https://github.com/craftcms/cms/pull/12218))

### Development
- Added the `exec` command, which executes an individual PHP statement and outputs the result. ([#12528](https://github.com/craftcms/cms/pull/12528))
- Added the `editable` and `savable` asset query params. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added the `savable` entry query param. ([#12266](https://github.com/craftcms/cms/pull/12266))
- The `editable` entry query param can now be set to `false` to only show entries that _can’t_ be viewed by the current user. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added the `{% dump %}` tag, which dumps variables into a new “Dumps” Debug Toolbar panel. ([#12506](https://github.com/craftcms/cms/pull/12506))
- The `dump()` Twig function now utilizes `Craft::dump()`, and no longer requires Dev Mode to be active. ([#12486](https://github.com/craftcms/cms/pull/12486), [#12479](https://github.com/craftcms/cms/discussions/12479))
- The `{% dd %}` Twig tag can now output the entire `context` array, if no variable is passed to it. ([#12486](https://github.com/craftcms/cms/pull/12486))
- Added `ancestors` and `descendants` fields to categories queried via GraphQL. ([#12427](https://github.com/craftcms/cms/issues/12427))
- Added `craft\elements\Asset::getFormat()`. ([#12398](https://github.com/craftcms/cms/pull/12398))
- `Craft::dump()`, `Craft::dd()`, the `dump()` Twig function, and the `{% dd %}` Twig tag now use Symfony’s VarDumper. ([#12479](https://github.com/craftcms/cms/discussions/12479))
- `Craft::dump()` now has a `$return` argument, which will cause the resulting dump to be returned as a string rather than output, if `true`.
- `craft\elements\Asset::getMimeType()` now has a `$transform` argument, and will now return the MIME type of the asset with a transform applied, when appropriate. ([#12269](https://github.com/craftcms/cms/discussions/12269), [#12397](https://github.com/craftcms/cms/pull/12397))

### Extensibility
- Added the `elements/revisions` action. ([#12211](https://github.com/craftcms/cms/pull/12211))
- Console controllers that directly use `craft\console\ControllerTrait` no longer need to call `$this->checkTty()` or `$this->checkRootUser()` themselves; they are now called from `ControllerTrait::init()` and `beforeAction()`.
- Element source definitions can now include a `defaultSourcePath` key.
- Added `craft\base\Element::cpRevisionsUrl()`.
- Added `craft\base\Element::indexElements()`.
- Added `craft\base\ElementInterface::findSource()`.
- Added `craft\base\ElementInterface::getCpRevisionsUrl()`.
- Added `craft\base\ElementInterface::indexElementCount()`.
- Added `craft\console\ControllerTrait::beforeAction()`.
- Added `craft\console\ControllerTrait::init()`.
- Added `craft\console\ControllerTrait::options()`.
- Added `craft\console\ControllerTrait::runAction()`.
- Added `craft\debug\DumpPanel`.
- Added `craft\elements\conditions\assets\ViewableConditionRule`. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added `craft\elements\conditions\entries\ViewableConditionRule`. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Added `craft\elements\Entry::EVENT_DEFINE_PARENT_SELECTION_CRITERIA`. ([#12475](https://github.com/craftcms/cms/discussions/12475))
- Added `craft\events\DefineInputOptionsEvent`. ([#12351](https://github.com/craftcms/cms/pull/12351))
- Added `craft\events\ListVolumesEvent`.
- Added `craft\events\UserPhotoEvent`. ([#12360](https://github.com/craftcms/cms/pull/12360))
- Added `craft\fields\BaseOptionsField::EVENT_DEFINE_OPTIONS`. ([#12351](https://github.com/craftcms/cms/pull/12351))
- Added `craft\fields\BaseRelationField::$branchLimit`.
- Added `craft\fields\BaseRelationField::$maintainHierarchy`.
- Added `craft\models\VolumeFolder::getHasChildren()`.
- Added `craft\models\VolumeFolder::setHasChildren()`.
- Added `craft\queue\jobs\GenerateImageTransform`. ([#12340](https://github.com/craftcms/cms/pull/12340))
- Added `craft\services\Assets::createFolderQuery()`.
- Added `craft\services\Assets::foldersExist()`.
- Added `craft\services\Elements::deleteElementForSite()`.
- Added `craft\services\Elements::deleteElementsForSite()`.
- Added `craft\services\Elements::EVENT_AFTER_DELETE_FOR_SITE`. ([#12354](https://github.com/craftcms/cms/issues/12354))
- Added `craft\services\Elements::EVENT_BEFORE_DELETE_FOR_SITE`. ([#12354](https://github.com/craftcms/cms/issues/12354))
- Added `craft\services\Fields::getFieldsByType()`. ([#12381](https://github.com/craftcms/cms/discussions/12381))
- Added `craft\services\Search::normalizeSearchQuery()`.
- Added `craft\services\Users::EVENT_AFTER_DELETE_USER_PHOTO`. ([#12360](https://github.com/craftcms/cms/pull/12360))
- Added `craft\services\Users::EVENT_AFTER_SAVE_USER_PHOTO`. ([#12360](https://github.com/craftcms/cms/pull/12360))
- Added `craft\services\Users::EVENT_BEFORE_DELETE_USER_PHOTO`. ([#12360](https://github.com/craftcms/cms/pull/12360))
- Added `craft\services\Users::EVENT_BEFORE_SAVE_USER_PHOTO`. ([#12360](https://github.com/craftcms/cms/pull/12360))
- Added `craft\utilities\AssetIndexes::EVENT_LIST_VOLUMES`. ([#12383](https://github.com/craftcms/cms/pull/12383), [#12443](https://github.com/craftcms/cms/pull/12443))
- Renamed `craft\elements\conditions\assets\EditableConditionRule` to `SavableConditionRule`, while preserving the original class name with an alias. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Renamed `craft\elements\conditions\entries\EditableConditionRule` to `SavableConditionRule`, while preserving the original class name with an alias. ([#12266](https://github.com/craftcms/cms/pull/12266))
- Deprecated `craft\queue\jobs\GeneratePendingTransforms`. `GenerateImageTransform` should be used instead. ([#12340](https://github.com/craftcms/cms/pull/12340))
- Added `Craft.Accordion`. ([#12189](https://github.com/craftcms/cms/pull/12189))
- Added `Craft.AssetMover`.
- Added `Craft.BaseElementIndex::getSourcePathActionLabel()`.
- Added `Craft.BaseElementIndex::getSourcePathActions()`.
- Added `Craft.BaseElementIndex::getSourcePathLabel()`.
- Added `Craft.BaseElementIndex::onSourcePathChange()`.
- Added `Craft.BaseElementIndex::sourcePath`.
- Added `Craft.BaseElementSelectorModal::getElementIndexParams()`.
- Added `Craft.BaseElementSelectorModal::getIndexSettings()`.
- Added `Craft.BaseElementSelectorModal::hasSelection()`.
- Added `Craft.ElementFieldSettings`.
- Added `Craft.VolumeFolderSelectorModal`.
- Added `Garnish.MultiFunctionBtn`.
- The custom `activate` jQuery event will now trigger when the <kbd>Return</kbd> key is pressed.
- The custom `activate` jQuery event will no longer trigger for <kbd>Ctrl</kbd>/<kbd>Command</kbd>-clicks.
- Deprecated `Craft.CategorySelectInput`. `Craft.BaseElementSelectInput` should be used instead. ([#11749](https://github.com/craftcms/cms/pull/11749))

### System
- Improved element deletion performance. ([#12223](https://github.com/craftcms/cms/pull/12223))
- Improved queue performance. ([#12274](https://github.com/craftcms/cms/issues/12274), [#12340](https://github.com/craftcms/cms/pull/12340))
- Assets’ alternative text values are now included as search keywords.
- Updated LitEmoji to v4. ([#12226](https://github.com/craftcms/cms/discussions/12226))
- Fixed a database deadlock error that could occur when updating a relation or structure position for an element that was simultaneously being saved. ([#9905](https://github.com/craftcms/cms/issues/9905))
