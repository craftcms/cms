# Release Notes for Craft CMS 4.12 (WIP)

### Development
- Country field values and `craft\elements\Address::getCountry()` now return the country in the current application locale.

### Extensibility
- Added `craft\base\ApplicationTrait::getEnvId()`. ([#15313](https://github.com/craftcms/cms/issues/15313))
- Added `craft\base\ElementInterface::getRootOwner()`. ([#15534](https://github.com/craftcms/cms/discussions/15534))
- Deprecated `craft\helpers\ElementHelper::rootElement()`. `craft\base\ElementInterface::getRootOwner()` should be used instead.
- Deprecated `craft\db\mysql\Schema::quoteDatabaseName()`.
- Deprecated `craft\db\pgqsl\Schema::quoteDatabaseName()`.

### System
- MySQL mutex locks and PHP session names are now namespaced using the application ID combined with the environment name. ([#15313](https://github.com/craftcms/cms/issues/15313))
- `x-craft-preview` and `x-craft-live-preview` params are now hashed, and `craft\web\Request::getIsPreview()` will only return `true` if the param validates. ([#15605](https://github.com/craftcms/cms/discussions/15605))
- Updated Twig to 3.12. ([#15568](https://github.com/craftcms/cms/discussions/15568))
