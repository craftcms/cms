# Release Notes for Craft CMS 5.11 (WIP)

### Development
- The `params` argument of the `url()` Twig function now accepts `false` to remove all params from the passed-in URL. ([#19102](https://github.com/craftcms/cms/pull/19102))
- Added `craft\web\DbSession`, which should be used instead of `yii\web\DbSession` to prevent “headers already sent” warnings from getting logged. ([#19139](https://github.com/craftcms/cms/issues/19139))

### Extensibility
- Added `craft\helpers\UrlHelper::removeAllParams()`. ([#19102](https://github.com/craftcms/cms/pull/19102))
- Added `craft\helpers\UrlHelper::removeParams()`. ([#19102](https://github.com/craftcms/cms/pull/19102))
- The `$params` argument of `craft\helpers\UrlHelper::url()` now accepts `false` to remove all params from the passed-in URL. ([#19102](https://github.com/craftcms/cms/pull/19102))

### System
- Updated Twig to 3.28.
