### Added
- Added `craft/base/FsInterface`.
- Added `craft/base/ImageTransformDriver`.
- Added `craft/base/ImageTransformDriverInterface`.
- Added `craft/base/LocalFsInterface`.
- Added `craft/fs/Local`.
- Added `craft/helpers/ImageTransforms`.
- Added `craft/base/Image::heartbeat()`.
- Added `craft/base/Image::setHeartbeatCallback()`.
- Added `craft/base/Volume::getFilesystem()`.
- Added `craft/base/VolumeInterface::getFilesystem()`.
- Added `craft/helpers/FileHelper::deleteFileAfterRequest()`.
- Added `craft/helpers/FileHelper::deleteQueuedFiles()`.
- Added `craft/models/ImageTransform::getDriver()`.
- Added `craft/models/ImageTransform::getImageTransformer()`.
- Added `craft/models/ImageTransform::setDriver()`.
- Added `craft/models/ImageTransformIndex::getImageTransformer()`.
- Added `craft/services/AssetTransforms::getImageTransformer()`.
- Added `craft/services/AssetTransforms::getSimilarTransformIndex()`.

### Changed
- Images that are not web-safe now are always converted to JPG when transforming, if auto format is selected.
- The `craft/assetpreviews` namespace was changed to `craft/assets/previews`.
- `craft/db/Table:ASSETTRANSFORMINDEX` has been renamed to `craft/db/Table:IMAGETRANSFORMINDEX`.
- `craft/db/Table:ASSETTRANSFORMS` has been renamed to `craft/db/Table:IMAGETRANSFORMS`.
- `craft/errors/AssetTransformException` is now `craft/errors/ImageTransformException`.
- `craft/models/AssetTransform` is now `craft/models/ImageTransform`.
- `craft/models/AssetTransformIndex` is now `craft/models/ImageTransformIndex`.
- `craft/records/AssetTransform` is now `craft/records/ImageTransform`.
- `craft/models/AssetTransform::$dimensionChangeTime` has been renamed to `$parameterChangeTime`.

### Removed
- Removed `craft/elements/Asset::getTransformSource()`.
- Removed `craft/services/AssetTransforms::deleteQueuedSourceFiles()`.
- Removed `craft/services/AssetTransforms::detectAutoTransformFormat()`.
- Removed `craft/services/AssetTransforms::getActiveTransformIndex()`.
- Removed `craft/services/AssetTransforms::getCachedCloudImageSize()`.
- Removed `craft/services/AssetTransforms::getLocalImageSource()`.
- Removed `craft/services/AssetTransforms::getTransformIndexModelByAssetIdAndHandle()`.
- Removed `craft/services/AssetTransforms::getUrlForTransformByAssetAndTransformIndex()`.
- Removed `craft/services/AssetTransforms::getUrlForTransformByIndexId()`.
- Removed `craft/services/AssetTransforms::queueSourceForDeletingIfNecessary()`.
- Removed `craft/services/AssetTransforms::storeLocalSource()`.
- Removed `craft/services/AssetTransforms::setActiveTransformIndex()`.
