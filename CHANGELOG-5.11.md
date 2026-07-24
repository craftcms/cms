# Release Notes for Craft CMS 5.11 (WIP)

> [!WARNING]  
> GraphQL fields that return user data (`author`, `authorId`, `authors`, `authorIds`, `draftCreator`, `revisionCreator`, `uploader`, and `uploaderId`) are no longer available to schemas that don’t have “Query for users” enabled.

### Development
- The `params` argument of the `url()` Twig function now accepts `false` to remove all params from the passed-in URL. ([#19102](https://github.com/craftcms/cms/pull/19102))
- Added `craft\web\DbSession`, which should be used instead of `yii\web\DbSession` to prevent “headers already sent” warnings from getting logged. ([#19139](https://github.com/craftcms/cms/issues/19139))

### Extensibility
- Added `craft\helpers\Gql::canQueryAllUsers()`.
- Added `craft\helpers\UrlHelper::removeParams()`. ([#19102](https://github.com/craftcms/cms/pull/19102))
- Added `craft\helpers\UrlHelper::removeAllParams()`. ([#19102](https://github.com/craftcms/cms/pull/19102))
- The `$params` argument of `craft\helpers\UrlHelper::url()` now accepts `false` to remove all params from the passed-in URL. ([#19102](https://github.com/craftcms/cms/pull/19102))

### System
- Updated Twig to 3.28.
- Fixed a [moderate-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) information disclosure vulnerability. (GHSA-pcmv-c398-gc5m)
