# Release Notes for Craft CMS 4.13 (WIP)

### Administration
- Added `pc/*` commands as an alias of `project-config/*`.

### Extensibility
- Added `craft\base\RequestTrait::getIsWebRequest()`. ([#15690](https://github.com/craftcms/cms/pull/15690))
- Added `craft\events\DefineAddressCountriesEvent`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\filters\BasicHttpAuthLogin`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\BasicHttpAuthStatic`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\SiteFilterTrait::$enabled`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\services\Addresses::EVENT_DEFINE_ADDRESS_COUNTRIES`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\services\Addresses::getCountryList()`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\web\View::registerCpTwigExtension()`.
- Added `craft\web\View::registerSiteTwigExtension()`.
- Deprecated the `enableBasicHttpAuth` config setting. `craft\filters\BasicHttpAuthLogin` should be used instead. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added the `serializeForm` event to `Craft.ElementEditor`. ([#15794](https://github.com/craftcms/cms/discussions/15794))
