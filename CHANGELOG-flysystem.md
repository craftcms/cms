### Added
- Added `craft\helpers\Assets::downloadFile()`.
- Added `craft\base\Volume::CONFIG_MIMETYPE`.
- Added `craft\base\Volume::CONFIG_VISIBILITY`.
- Added `craft\base\Volume::VISIBILITY_DEFAULT`.
- Added `craft\base\Volume::VISIBILITY_HIDDEN`.
- Added `craft\base\Volume::VISIBILITY_PUBLIC`.
- Added `craft\models\VolumeListing`.

### Changed
- Local Volume no longer uses the FlySystem package.

### Deprecated
- Deprecated `craft\base\VolumeInterface::createFileByStream()`.
- Deprecated `craft\base\VolumeInterface::saveFileLocally()`.
- Deprecated `craft\base\VolumeInterface::updateFileByStream()`.

### Removed
- Removed `craft\base\VolumeInterface::createDir()`.
- Removed `craft\base\VolumeInterface::deleteDir()`.
- Removed `craft\base\VolumeInterface::getFileMetadata()`.
- Removed `craft\base\VolumeInterface::renameDir()`.
