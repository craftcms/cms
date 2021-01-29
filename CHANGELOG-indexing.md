### Added
- Added `craft\controllers\AssetIndexesController`.
- Added `craft\db\Table::ASSETINDEXINGSESSIONS`
- Added `craft\models\AssetIndexingSession`.
- Added `craft\services\AssetIndexer::getExistingIndexingSessions()`.
- Added `craft\services\AssetIndexer::startIndexingSession()`.

### Changed
- `craft\services\AssetIndexer::storeIndexList()` now expect the first argument to be a Generator, and the session id must be an integer.
