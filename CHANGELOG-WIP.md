# Release Notes for Craft CMS 3.8 (WIP)

### Content Management
- Volume subfolders are now displayed within the element listing pane on asset indexes, rather than as nested sources in the sidebar. ([#12558](https://github.com/craftcms/cms/pull/12558), [#9171](https://github.com/craftcms/cms/discussions/9171), [#5809](https://github.com/craftcms/cms/issues/5809))
- Asset indexes now display the current subfolder path above the element listing. ([#12558](https://github.com/craftcms/cms/pull/12558))
- It’s now possible to move volume folders and assets to a new location via a new “Move…” bulk element action, rather than via drag-and-drop interactions. ([#12558](https://github.com/craftcms/cms/pull/12558))
- It’s now possible to sort asset indexes by image width and height. ([#12653](https://github.com/craftcms/cms/pull/12653))

### Accessibility

### Administration
- Added the `users/unlock` console command. ([#12345](https://github.com/craftcms/cms/discussions/12345))
- The `utils/prune-revisions` console command now has a `--section` option. ([#8783](https://github.com/craftcms/cms/discussions/8783))

### Development
- Added the `revesionNotes` field to elements queried via GraphQL. ([#12610](https://github.com/craftcms/cms/issues/12610))
- `craft\elements\Asset::getMimeType()` now has a `$transform` argument, and assets’ `mimeType` GraphQL fields now support a `@transform` directive. ([#12269](https://github.com/craftcms/cms/discussions/12269), [#12397](https://github.com/craftcms/cms/pull/12397), [#12522](https://github.com/craftcms/cms/pull/12522))

### Extensibility
- Element source definitions can now include a `defaultSourcePath` key.
- Added `craft\base\Element::indexElements()`.
- Added `craft\base\ElementInterface::findSource()`.
- Added `craft\base\ElementInterface::indexElementCount()`.
- Added `craft\db\Migration::dropForeignKeyIfExists()`.
- Added `craft\models\VolumeFolder::getHasChildren()`.
- Added `craft\models\VolumeFolder::setHasChildren()`.
- Added `craft\services\Assets::createFolderQuery()`.
- Added `craft\services\Assets::foldersExist()`.
- Added `craft\services\Search::normalizeSearchQuery()`.
- Added `Craft.AssetMover`.
- Added `Craft.BaseElementIndex::getSourcePathActionLabel()`.
- Added `Craft.BaseElementIndex::getSourcePathActions()`.
- Added `Craft.BaseElementIndex::getSourcePathLabel()`.
- Added `Craft.BaseElementIndex::onSourcePathChange()`.
- Added `Craft.BaseElementIndex::sourcePath`.
- Added `Craft.BaseElementSelectorModal::getElementIndexParams()`.
- Added `Craft.BaseElementSelectorModal::getIndexSettings()`.
- Added `Craft.BaseElementSelectorModal::hasSelection()`.
- Added `Craft.VolumeFolderSelectorModal`.
- The custom `activate` jQuery event will now trigger when the <kbd>Return</kbd> key is pressed.
- The custom `activate` jQuery event will no longer trigger for <kbd>Ctrl</kbd>/<kbd>Command</kbd>-clicks.

### System
- Fixed a database deadlock error that could occur when updating a relation or structure position for an element that was simultaneously being saved. ([#9905](https://github.com/craftcms/cms/issues/9905))
