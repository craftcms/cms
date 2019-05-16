# Running Release Notes for Craft CMS 3.2

> {warning} This update introduces a few changes in behavior to be aware of:
>
> - Custom login controllers must now explicitly set their `$allowAnonymous` values to include `self::ALLOW_ANONYMOUS_OFFLINE` if they wish to be available when the system is offline.

### Added
- Sections now have a “Propagation Method” setting, enabling entries to only be propagated to other sites in the same site group, or with the same language. ([#3554](https://github.com/craftcms/cms/issues/3554))
- Added the “Temp Uploads Location” system setting (available from Settings → Assets → Settings), which makes it possible to choose the volume and path that temporary asset uploads should be stored. ([#4010](https://github.com/craftcms/cms/issues/4010))
- The `site` element query params now support passing multiple site handles, or `'*'`, to query elements across multiple sites at once. ([#2854](https://github.com/craftcms/cms/issues/2854))
- Table fields can now have Dropdown, Email, and URL columns. ([#811](https://github.com/craftcms/cms/issues/811), [#4180](https://github.com/craftcms/cms/pull/4180))
- Dropdown and Multi-select fields can now have optgroups. ([#4236](https://github.com/craftcms/cms/issues/4236))
- Added the `unique` element query param, which can be used to prevent duplicate elements when querying elements across multiple sites.
- Added the `preferSites` element query param, which can be used to set the preferred sites that should be used for multi-site element queries, when the `unique` param is also enabled.
- Element index pages are now paginated for non-Structure views. ([#818](https://github.com/craftcms/cms/issues/818))
- Element index pages now have an “Export…” button that will export all of the elements in the current view (across all pages) or up to a custom limit, in either CSV, XLS, XLSX, or ODS format. ([#994](https://github.com/craftcms/cms/issues/994))
- Added the `attr()` Twig function, which can generate a list of HTML/XML attributes. ([#4237](https://github.com/craftcms/cms/pull/4237))
- Jobs can new set progress labels, which will be shown below their description and progress bar in the queue HUD. ([#1931](https://github.com/craftcms/cms/pull/1931))
- The `_layouts/cp` Control Panel template now supports a `footer` block, which will be output below the main content area.
- Added `craft\base\ElementInterface::getUiLabel()`, which is now used to define what an element will be called in the Control Panel. ([#4211](https://github.com/craftcms/cms/pull/4211))
- Added `craft\base\ElementInterface::pluralDisplayName()`, which element type classes can use to define the plural of their display name.
- Added `craft\base\FieldInterface::valueType()`. ([#3894](https://github.com/craftcms/cms/issues/3894))
- Added `craft\helpers\Component::validateComponentClass()`.
- Added `craft\models\Section::$propagationMethod`.
- Added `craft\services\Elements::resaveElements()` along with `EVENT_BEFORE_RESAVE_ELEMENTS`, `EVENT_AFTER_RESAVE_ELEMENTS`, `EVENT_BEFORE_RESAVE_ELEMENT`, and `EVENT_AFTER_RELAVE_ELEMENT` events. ([#3482](https://github.com/craftcms/cms/issues/3482))
- Added `craft\web\Request::getIsLoginRequest()` and `craft\console\Request::getIsLoginRequest()`.
- Added `editImagesInVolume` permission. ([#3349](https://github.com/craftcms/cms/issues/3349))

### Changed
- Relational fields are now capable of selecting elements from multiple sites, if they haven’t been locked down to only related elements from a single site. ([#3584](https://github.com/craftcms/cms/issues/3584))
- The Control Panel now shows the sidebar on screens that are at least 1,000 pixels wide. ([#4079](https://github.com/craftcms/cms/issues/4079))
- Renamed `craft\helpers\ArrayHelper::filterByValue()` to `where()`.
- Anonymous/offline/Control Panel access validation now takes place from `craft\web\Controller::beforeAction()` rather than `craft\web\Application::handleRequest()`, giving controllers a chance to do things like set CORS headers before a `ForbiddenHttpException` or `ServiceUnavailableHttpException` is thrown. ([#4008](https://github.com/craftcms/cms/issues/4008))
- Controllers can now set `$allowAnonymous` to a combination of bitwise integers `self::ALLOW_ANONYMOUS_LIVE` and `self::ALLOW_ANONYMOUS_OFFLINE`, or an array of action ID/bitwise integer pairs, to define whether their actions should be accessible anonymously even when the system is offline.
- `craft\base\ElementInterface::eagerLoadingMap()` and `craft\base\EagerLoadingFieldInterface::getEagerLoadingMap()` can now return `null` to opt out of eager-loading. ([#4220](https://github.com/craftcms/cms/pull/4220))
- `craft\db\ActiveRecord` no longer sets the `uid`, `dateCreated`, or `dateUpdated` values for new records if they were already explicitly set.
- `craft\db\ActiveRecord` no longer updates the `dateUpdated` value for existing records if nothing else changed or if `dateUpdated` had already been explicitly changed.
- `craft\queue\jobs\PropagateElements` no longer needs to be configured with a `siteId`, and no longer propagates elements to sites if they were updated in the target site more recently than the source site.
- `craft\services\Elements::deleteElement()` now has a `$hardDelete` argument.
- `craft\services\Elements::propagateElement()` now has a `$siteElement` argument.
- `craft\services\Elements::saveElement()` now preserves the `uid`, `dateCreated`, and `dateUpdated` values on new elements if they were explicitly set. ([#2909](https://github.com/craftcms/cms/issues/2909))
- `craft\services\Elements::saveElement()` now preserves existing elements’ current `dateUpdated` value when propagating or auto-resaving elements.
- `craft\queue\BaseJob::setProgress()` now has a `$label` argument.
- `craft\queue\QueueInterface::setProgress()` now has a `$label` argument.

### Removed
- Removed the `--batch-size` option from `resave/*` actions.

### Deprecated
- Deprecated `craft\helpers\ArrayHelper::filterByValue()`. Use `where()` instead.
- Deprecated `craft\models\Section::$propagateEntries`. Use `$propagationMethod` instead.
- Deprecated `craft\web\Request::getIsSingleActionRequest()` and `craft\console\Request::getIsSingleActionRequest()`.
