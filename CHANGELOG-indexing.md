### Added
- Added `craft\controllers\AssetIndexesController`.
- Added `craft\db\Table::ASSETINDEXINGSESSIONS`.
- Added `craft\errors\MissingVolumeFolderException`.
- Added `craft\models\AssetIndexingSession`.
- Added `craft\records\AssetIndexingSession`.
- Added `craft\services\AssetIndexer::createIndexingSession()`.
- Added `craft\services\AssetIndexer::getExistingIndexingSessions()`.
- Added `craft\services\AssetIndexer::getIndexingSessionById()`.
- Added `craft\services\AssetIndexer::getMissingEntriesForSession()`.
- Added `craft\services\AssetIndexer::getSkippedItemsForSession()`.
- Added `craft\services\AssetIndexer::indexFolderByEntry()`.
- Added `craft\services\AssetIndexer::indexFolderByListing()`.
- Added `craft\services\AssetIndexer::processIndexSession()`.
- Added `craft\services\AssetIndexer::removeCliIndexingSessions()`.
- Added `craft\services\AssetIndexer::startIndexingSession()`.
- Added `craft\services\AssetIndexer::stopIndexingSession()`.
- Added `craft\services\AssetTransforms::deleteTransformIndexDataByAssetIds()`.
- Added the `index-assets/cleanup` command that cleans up any leftover CLI indexing sessions.

### Changed
- Asset Indexing sessions ar IDs are integers now, instead of being a string.
- `craft\services\AssetIndexer::storeIndexList()` now expects the first argument to be a Generator of `craft\models\VolumeListing` items.

### Removed
- Removed `craft\controllers\UtilitiesController::actionAssetIndexPerformAction()`.
- Removed `craft\services\AssetIndexer::deleteStaleIndexingData()`.
- Removed `craft\services\AssetIndexer::extractFolderItemsFromIndexList`.
- Removed `craft\services\AssetIndexer::extractSkippedItemsFromIndexList`.
- Removed `craft\services\AssetIndexer::getIndexingSessionId()`.
- Removed `craft\services\AssetIndexer::getMissingFiles`.
- Removed `craft\services\AssetIndexer::prepareIndexList`.
- Removed `craft\services\AssetIndexer::processIndexForVolume()`.
