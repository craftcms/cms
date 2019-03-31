# Running Release Notes for Craft CMS 3.2

::: warning
This update introduces a few changes in behavior to be aware of:

- Custom login controllers must now explicitly set their `$allowAnonymous` values to include `self::ALLOW_ANONYMOUS_OFFLINE` if they wish to be available when the system is offline.
:::

### Added
- Sections now have a “Propagation Method” setting, enabling entries to only be propagated to other sites in the same site group, or with the same language. ([#3554](https://github.com/craftcms/cms/issues/3554))
- The `site` element query params now support passing multiple site handles, or `'*'`, to query elements across multiple sites at once. ([#2854](https://github.com/craftcms/cms/issues/2854))
- Added the `unique` element query param, which can be used to prevent duplicate elements when querying elements across multiple sites.
- Element index pages now show the total number of elements in the footer. ([#818](https://github.com/craftcms/cms/issues/818))
- The `_layouts/cp` Control Panel template now supports a `footer` block, which will be output below the main content area.
- Added `craft\base\ElementInterface::pluralDisplayName()`, which element type classes can use to define the plural of their display name.
- Added `craft\models\Section::$propagationMethod`.
- Added `craft\web\Request::getIsLoginRequest()` and `craft\console\Request::getIsLoginRequest()`.

### Changed
- Craft now propagates elements over background jobs, speeding up the initial element save request. ([#4064](https://github.com/craftcms/cms/issues/4064))
- Renamed `craft\helpers\ArrayHelper::filterByValue()` to `where()`.
- Anonymous/offline/Control Panel access validation now takes place from `craft\web\Controller::beforeAction()` rather than `craft\web\Application::handleRequest()`, giving controllers a chance to do things like set CORS headers before a `ForbiddenHttpException` or `ServiceUnavailableHttpException` is thrown. ([#4008](https://github.com/craftcms/cms/issues/4008))
- Controllers can now set `$allowAnonymous` to a combination of bitwise integers `self::ALLOW_ANONYMOUS_LIVE` and `self::ALLOW_ANONYMOUS_OFFLINE`, or an array of action ID/bitwise integer pairs, to define whether their actions should be accessible anonymously even when the system is offline.
- `craft\queue\jobs\PropagateElements` no longer needs to be configured with a `siteId`, and no longer propagates elements to sites if they were updated in the target site more recently than the source site.
- `craft\services\Elements::propagateElement()` now has a `$siteElement` argument.

### Deprecated
- Deprecated `craft\helpers\ArrayHelper::filterByValue()`. Use `where()` instead.
- Deprecated `craft\models\Section::$propagateEntries`. Use `$propagationMethod` instead.
- Deprecated `craft\web\Request::getIsSingleActionRequest()` and `craft\console\Request::getIsSingleActionRequest()`.
