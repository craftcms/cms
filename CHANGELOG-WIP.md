# Release Notes for Craft CMS 4.13 (WIP)

### Content Management
- It’s now possible to define the list of available countries when creating an address.

### Extensibility
- Added `craft\base\RequestTrait::getIsWebRequest()`. ([#15690](https://github.com/craftcms/cms/pull/15690))
- Added `craft\events\DefineAddressCountriesEvent`.
- Added `craft\filters\BasicHttpAuthLogin`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\BasicHttpAuthStatic`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\SiteFilterTrait::$enabled`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\services\Addresses::EVENT_DEFINE_ADDRESS_COUNTRIES`.
- Added `craft\web\View::registerCpTwigExtension()`.
- Added `craft\web\View::registerSiteTwigExtension()`.
- Deprecated the `enableBasicHttpAuth` config setting. `craft\filters\BasicHttpAuthLogin` should be used instead. ([#15720](https://github.com/craftcms/cms/pull/15720))
