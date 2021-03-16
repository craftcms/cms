# Release Notes for Craft CMS 4

## Unreleased

### Added
- Added `craft\base\Volume::CONFIG_MIMETYPE`.
- Added `craft\base\Volume::CONFIG_VISIBILITY`.
- Added `craft\base\Volume::VISIBILITY_DEFAULT`.
- Added `craft\base\Volume::VISIBILITY_HIDDEN`.
- Added `craft\base\Volume::VISIBILITY_PUBLIC`.
- Added `craft\controllers\AssetIndexesController`.
- Added `craft\db\Table::ASSETINDEXINGSESSIONS`.
- Added `craft\errors\MissingVolumeFolderException`.
- Added `craft\helpers\Assets::downloadFile()`.
- Added `craft\models\AssetIndexingSession`.
- Added `craft\models\VolumeListing`.
- Added `craft\records\AssetIndexingSession`.
- Added `craft\services\AssetIndexer::createIndexingSession()`.
- Added `craft\services\AssetIndexer::getExistingIndexingSessions()`.
- Added `craft\services\AssetIndexer::getIndexingSessionById()`.
- Added `craft\services\AssetIndexer::getMissingEntriesForSession()`.
- Added `craft\services\AssetIndexer::getSkippedItemsForSession()`.
- Added `craft\services\AssetIndexer::indexFileByListing()`.
- Added `craft\services\AssetIndexer::indexFolderByEntry()`.
- Added `craft\services\AssetIndexer::indexFolderByListing()`.
- Added `craft\services\AssetIndexer::processIndexSession()`.
- Added `craft\services\AssetIndexer::removeCliIndexingSessions()`.
- Added `craft\services\AssetIndexer::startIndexingSession()`.
- Added `craft\services\AssetIndexer::stopIndexingSession()`.
- Added `craft\services\AssetTransforms::deleteTransformIndexDataByAssetIds()`.
- Added the `index-assets/cleanup` command that cleans up any leftover CLI indexing sessions.

### Changed
- Craft now requires PHP 7.4 or later.
- Relational fields now load elements in the current site rather than the primary site, if the source element isn’t localizable. ([#7048](https://github.com/craftcms/cms/issues/7048))
- Local Volume no longer uses the FlySystem library.
- Asset Indexing sessions ar IDs are integers now, instead of being a string.
- `craft\base\Model::datetimeAttributes()` is now called from the constructor, instead of the `init()` method.
- `craft\base\Model::setAttributes()` now normalizes date attributes into `DateTime` objects.
- `craft\services\AssetIndexer::storeIndexList()` now expects the first argument to be a Generator of `craft\models\VolumeListing` items.
- `craft\services\Assets::ensureFolderByFullPathAndVolume()` now returns an instance of `craft\models\VolumeFolder` instead of the folder id.
- `craft\services\Assets::ensureTopFolder()` now returns an instance of `craft\models\VolumeFolder` instead of the folder id.
- Updated Twig to 3.3.
- Updated vue-autosuggest to 2.2.0.

### Deprecated
- Deprecated `craft\base\VolumeInterface::createFileByStream()`.
- Deprecated `craft\base\VolumeInterface::saveFileLocally()`.
- Deprecated `craft\base\VolumeInterface::updateFileByStream()`.
- Deprecated `craft\helpers\ArrayHelper::append()`. `array_unshift()` should be used instead.
- Deprecated `craft\helpers\ArrayHelper::prepend()`. `array_push()` should be used instead.

### Removed
- Removed the `suppressTemplateErrors` config setting.
- Removed `craft\base\VolumeInterface::createDir()`.
- Removed `craft\base\VolumeInterface::deleteDir()`.
- Removed `craft\base\VolumeInterface::getFileMetadata()`.
- Removed `craft\base\VolumeInterface::renameDir()`.
- Removed `craft\controllers\UtilitiesController::actionAssetIndexPerformAction()`.
- Removed `craft\services\AssetIndexer::deleteStaleIndexingData()`.
- Removed `craft\services\AssetIndexer::extractFolderItemsFromIndexList`.
- Removed `craft\services\AssetIndexer::extractSkippedItemsFromIndexList`.
- Removed `craft\services\AssetIndexer::getIndexingSessionId()`.
- Removed `craft\services\AssetIndexer::getMissingFiles`.
- Removed `craft\services\AssetIndexer::prepareIndexList`.
- Removed `craft\services\AssetIndexer::processIndexForVolume()`.
- Removed `craft\web\twig\Template`.
- Removed `craft\web\View::$minifyCss`.
- Removed `craft\web\View::$minifyJs`.
- Removed `craft\web\View::renderTemplateMacro()`.
