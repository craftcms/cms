### Added
-

### Changed
- Images that are not web-safe now are always converted to JPG when transforming, if auto format is selected.
- The `craft/assetpreviews` namespace was changed to `craft/assets/previews`.
- `craft/models/AssetTransform` is now `craft/models/AssetImageTransform`.
- `craft/models/AssetTransform::$dimensionChangeTime` has been renamed to `$parameterChangeTime`.
