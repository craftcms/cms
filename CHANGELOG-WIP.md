# Release Notes for Craft CMS 4.12 (WIP)

### Extensibility
- Added `craft\base\ApplicationTrait::getEnvId()`. ([#15313](https://github.com/craftcms/cms/issues/15313))
- Added `craft\base\ElementInterface::getRootOwner()`. ([#15534](https://github.com/craftcms/cms/discussions/15534))
- Deprecated `craft\helpers\ElementHelper::rootElement()`. `craft\base\ElementInterface::getRootOwner()` should be used instead.

### System
- MySQL mutex locks and PHP session names are now namespaced using the application ID combined with the environment name. ([#15313](https://github.com/craftcms/cms/issues/15313))
- Updated Twig to 3.12. ([#15568](https://github.com/craftcms/cms/discussions/15568))
