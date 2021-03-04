# Release Notes for Craft CMS 3.7

### Added
- Matrix fields can now be set to custom propagation methods, based on a propagation key template. ([#7610](https://github.com/craftcms/cms/issues/7610))
- Added `craft\fields\Matrix::$propagationKeyFormat`.
- Added `craft\fields\Matrix::PROPAGATION_METHOD_CUSTOM`.
- Added `craft\helpers::batch()` and `each()`, which can be used instead of `craft\db\Query::batch()` and `each()`, to execute batched SQL queries over a new, unbuffered database connection (if using MySQL). ([#7338](https://github.com/craftcms/cms/issues/7338))

### Deprecated
- Deprecated `craft\elements\Asset::KIND_FLASH`.

### Removed
- Removed support for the “Flash” file kind. ([#7626](https://github.com/craftcms/cms/issues/7626))
