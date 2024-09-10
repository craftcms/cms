# Release Notes for Craft CMS 4.13 (WIP)

### Content Management
- It’s now possible to define the list of available countries when creating an address.

### Extensibility
- Added `craft\base\RequestTrait::getIsWebRequest()`. ([#15690](https://github.com/craftcms/cms/pull/15690))
- Added `craft\events\DefineAddressCountriesEvent`.
- Added `craft\services\Addresses::EVENT_DEFINE_ADDRESS_COUNTRIES`.