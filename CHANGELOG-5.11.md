# Release Notes for Craft CMS 5.11 (WIP)

### Development
- The `params` argument of the `url()` Twig function now accepts `false` to remove all params from the passed-in URL. ([#19102](https://github.com/craftcms/cms/pull/19102))

### Extensibility
- Added `craft\helpers\UrlHelper::removeParams()`. ([#19102](https://github.com/craftcms/cms/pull/19102))
- Added `craft\helpers\UrlHelper::removeAllParams()`. ([#19102](https://github.com/craftcms/cms/pull/19102))
- The `$params` argument of `craft\helpers\UrlHelper::url()` now accepts `false` to remove all params from the passed-in URL. ([#19102](https://github.com/craftcms/cms/pull/19102))
