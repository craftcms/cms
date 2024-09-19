# Release Notes for Craft CMS 4.13 (WIP)

### Extensibility
- Added `craft\base\RequestTrait::getIsWebRequest()`. ([#15690](https://github.com/craftcms/cms/pull/15690))
- Added `craft\filters\BasicHttpAuthLogin`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\BasicHttpAuthStatic`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\SiteFilterTrait::$enabled`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Deprecated the `enableBasicHttpAuth` config setting. `craft\filters\BasicHttpAuthLogin` should be used instead. ([#15720](https://github.com/craftcms/cms/pull/15720))
