# Release Notes for Craft CMS 5.11 (WIP)

> [!WARNING]  
> GraphQL fields that return user data (`author`, `authorId`, `authors`, `authorIds`, `draftCreator`, `revisionCreator`, `uploader`, and `uploaderId`) are no longer available to schemas that don’t have “Query for users” enabled.

### Content Management
- Asset alt text no longer gets propagated to all sites the first time it’s filled in. ([#19067](https://github.com/craftcms/cms/pull/19067))

### Development
- The `params` argument of the `url()` Twig function now accepts `false` to remove all params from the passed-in URL. ([#19102](https://github.com/craftcms/cms/pull/19102))
- Added `craft\web\DbSession`, which should be used instead of `yii\web\DbSession` to prevent “headers already sent” warnings from getting logged. ([#19139](https://github.com/craftcms/cms/issues/19139))
- Arrays created from `craft\fields\data\LinkData` objects now include `type`, `value`, `url`, `label`, `filename`, `link`, `attributes`, `defaultLabel`, `elementType`, `elementId`, `elementSiteId`, and `elementTitle` keys. ([craftcms/element-api#201](https://github.com/craftcms/element-api/issues/201)) 

### Extensibility
- Added `craft\fieldlayoutelements\CustomField::$oldFieldUid`.
- Added `craft\fields\conditions\FieldConditionRuleInterface::getFieldUid()`.
- Added `craft\helpers\Gql::canQueryAllUsers()`.
- Added `craft\helpers\UrlHelper::removeAllParams()`. ([#19102](https://github.com/craftcms/cms/pull/19102))
- Added `craft\helpers\UrlHelper::removeParams()`. ([#19102](https://github.com/craftcms/cms/pull/19102))
- Added `craft\services\Elements::reorderNestedElements()`. ([#19321](https://github.com/craftcms/cms/issues/19321))
- The `$params` argument of `craft\helpers\UrlHelper::url()` now accepts `false` to remove all params from the passed-in URL. ([#19102](https://github.com/craftcms/cms/pull/19102))

### System
- Added support for `.well-known/passkey-endpoints` requests. ([#19364](https://github.com/craftcms/cms/pull/19364))
- The front-end login page no longer returns a redirect response for logged-in users, if it’s a preview request. ([#19360](https://github.com/craftcms/cms/discussions/19360))
- Improved queue job reservation performance when queues contain many large jobs. ([#19097](https://github.com/craftcms/cms/issues/19097))
- Updated svg-sanitizer to 1.0.
- Updated Twig to 3.28.
- Updated yii2-debug to 2.1.28.
- Fixed an error that could occur when editing an element. ([#17268](https://github.com/craftcms/cms/issues/17268))
- Fixed a bug where `getEagerLoadedElements()` wasn’t returning results for eager-loaded native fields, such as `authors`. ([#19471](https://github.com/craftcms/cms/pull/19471))
- Fixed a bug where field condition rules within field layout components weren’t getting updated when a custom field was replaced within the layout. ([#19515](https://github.com/craftcms/cms/pull/19515))
- Fixed [moderate-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) information disclosure vulnerabilities. (GHSA-pcmv-c398-gc5m, GHSA-4w9w-3x96-7ghp)
