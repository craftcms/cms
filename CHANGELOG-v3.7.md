# Release Notes for Craft CMS 3.7

### Added
- Matrix fields can now be set to custom propagation methods, based on a propagation key template. ([#7610](https://github.com/craftcms/cms/issues/7610))
- Added `craft\fields\Matrix::$propagationKeyFormat`.
- Added `craft\fields\Matrix::PROPAGATION_METHOD_CUSTOM`.
- Added `craft\helpers\Db::batch()` and `each()`, which can be used instead of `craft\db\Query::batch()` and `each()`, to execute batched SQL queries over a new, unbuffered database connection (if using MySQL). ([#7338](https://github.com/craftcms/cms/issues/7338))
- Added `craft\helpers\ElementHelper::isDraft()`.
- Added `craft\helpers\ElementHelper::isRevision()`.
- Added `craft\web\twig\variables\Cp::getTimeZoneOptions()`.
- Added the `timeZone` and `timeZoneField` macros to the `_includes/forms.html` control panel template.

### Changed
- Improved the UI of the Time Zone input in Settings → General.
- Custom fields with a custom translation method are no longer labelled as translatable if the translation key is an empty string. ([#7647](https://github.com/craftcms/cms/issues/7647))
- The `date()` Twig function now supports arrays with `date` and/or `time` keys. ([#7681](https://github.com/craftcms/cms/issues/7681))

### Deprecated
- Deprecated `craft\base\VolumeInterface::folderExists()`. `directoryExists()` should be used instead.
- Deprecated `craft\elements\Asset::KIND_FLASH`.
- Deprecated `craft\services\Content::getContentRow()`.
- Deprecated `craft\services\Content::populateElementContent()`.

### Removed
- Removed support for the “Flash” file kind. ([#7626](https://github.com/craftcms/cms/issues/7626))
