# Running Release Notes for Craft CMS 3.2

> {warning} This update introduces a few changes in behavior to be aware of:
>
> - Custom login controllers must now explicitly set their `$allowAnonymous` values to include `self::ALLOW_ANONYMOUS_OFFLINE` if they wish to be available when the system is offline.

### Added
- All element types now have the option to support drafts and revisions.
- Drafts are now autocreated when content is modified, and autosaved whenever the content changes. ([#1034](https://github.com/craftcms/cms/issues/1034))
- Drafts and revisions now store content across all sites supported by the element. ([#2669](https://github.com/craftcms/cms/issues/2669))
- Content previewing is now draft-based, and drafts are stored as specialized elements, so it’s no longer necessary to add special cases in templates for preview requests. ([#1787](https://github.com/craftcms/cms/issues/1787), [#2801](https://github.com/craftcms/cms/issues/2801))
- Sections now have a “Preview Targets” setting when running Craft Pro, which can be used to configure additional locations that entries can be previewed from. ([#1489](https://github.com/craftcms/cms/issues/1489))
- Sections now have a “Propagation Method” setting, enabling entries to only be propagated to other sites in the same site group, or with the same language. ([#3554](https://github.com/craftcms/cms/issues/3554))
- Single entries now have editable slugs. ([#3368](https://github.com/craftcms/cms/issues/3368))
- Headless content previewing is now possible by forwarding request tokens off to content API requests. ([#1231](https://github.com/craftcms/cms/issues/1231))
- Preview iframes are now created with a `src` attribute already in place, improving SPA support. ([#2120](https://github.com/craftcms/cms/issues/2120))
- Added the “Temp Uploads Location” system setting (available from Settings → Assets → Settings), which makes it possible to choose the volume and path that temporary asset uploads should be stored. ([#4010](https://github.com/craftcms/cms/issues/4010))
- Added the `maxRevisions` config setting. ([#926](https://github.com/craftcms/cms/issues/926))
- Added `editImagesInVolume` permission. ([#3349](https://github.com/craftcms/cms/issues/3349))
- Added the `drafts`, `draftId`, `draftOf`, `draftCreator`, `revisions`, `revisionId`, `revisionOf`, and `revisionCreator` element query params.
- The `site` element query params now support passing multiple site handles, or `'*'`, to query elements across multiple sites at once. ([#2854](https://github.com/craftcms/cms/issues/2854))
- Relational fields now have a “Validate related elements” setting, which ensures that the related elements pass validation before the source element can be saved with them selected. ([#4095](https://github.com/craftcms/cms/issues/4095))
- Table fields can now have Dropdown, Email, and URL columns. ([#811](https://github.com/craftcms/cms/issues/811), [#4180](https://github.com/craftcms/cms/pull/4180))
- Dropdown and Multi-select fields can now have optgroups. ([#4236](https://github.com/craftcms/cms/issues/4236))
- Added the `unique` element query param, which can be used to prevent duplicate elements when querying elements across multiple sites.
- Added the `preferSites` element query param, which can be used to set the preferred sites that should be used for multi-site element queries, when the `unique` param is also enabled.
- Element index pages are now paginated for non-Structure views. ([#818](https://github.com/craftcms/cms/issues/818))
- Element index pages now have an “Export…” button that will export all of the elements in the current view (across all pages) or up to a custom limit, in either CSV, XLS, XLSX, or ODS format. ([#994](https://github.com/craftcms/cms/issues/994))
- Added the `attr()` Twig function, which can generate a list of HTML/XML attributes. ([#4237](https://github.com/craftcms/cms/pull/4237))
- Added the `|withoutKey` Twig filter.
- Jobs can new set progress labels, which will be shown below their description and progress bar in the queue HUD. ([#1931](https://github.com/craftcms/cms/pull/1931))
- Added the `_layouts/element` template, which can be extended by element edit pages that wish to support drafts, revisions, and content previewing.
- Added the `_special/sitepicker` template.
- Added `craft\base\Element::EVENT_AFTER_PROPAGATE`.
- Added `craft\base\Element::EVENT_REGISTER_PREVIEW_TARGETS`.
- Added `craft\base\Element::previewTargets()`.
- Added `craft\base\ElementInterface::afterPropagate()`.
- Added `craft\base\ElementInterface::getCurrentRevision()`.
- Added `craft\base\ElementInterface::getPreviewTargets()`.
- Added `craft\base\ElementInterface::getSourceId()`.
- Added `craft\base\ElementInterface::getUiLabel()`, which is now used to define what an element will be called in the Control Panel. ([#4211](https://github.com/craftcms/cms/pull/4211))
- Added `craft\base\ElementInterface::pluralDisplayName()`, which element type classes can use to define the plural of their display name.
- Added `craft\base\ElementInterface::setRevisionNotes()`.
- Added `craft\base\ElementTrait::$draftId`.
- Added `craft\base\ElementTrait::$hardDelete`.
- Added `craft\base\ElementTrait::$revisionId`.
- Added `craft\base\Field::EVENT_AFTER_ELEMENT_PROPAGATE`.
- Added `craft\base\FieldInterface::afterElementPropagate()`.
- Added `craft\base\FieldInterface::valueType()`. ([#3894](https://github.com/craftcms/cms/issues/3894))
- Added `craft\behaviors\DraftBehavior`.
- Added `craft\behaviors\RevisionBehavior`.
- Added `craft\controllers\PreviewController`.
- Added `craft\events\BatchElementActionEvent`.
- Added `craft\events\ElementQueryEvent`.
- Added `craft\events\RegisterPreviewTargetsEvent`.
- Added `craft\events\RevisionEvent`.
- Added `craft\helpers\Component::validateComponentClass()`.
- Added `craft\models\Section::$propagationMethod`.
- Added `craft\services\Drafts`, accessible via `Craft::$app->drafts`.
- Added `craft\services\Elements::propagateElements()` along with `EVENT_BEFORE_PROPAGATE_ELEMENTS`, `EVENT_AFTER_PROPAGATE_ELEMENTS`, `EVENT_BEFORE_PROPAGATE_ELEMENT`, and `EVENT_AFTER_PROPAGATE_ELEMENT` events. ([#4139](https://github.com/craftcms/cms/issues/4139))
- Added `craft\services\Elements::resaveElements()` along with `EVENT_BEFORE_RESAVE_ELEMENTS`, `EVENT_AFTER_RESAVE_ELEMENTS`, `EVENT_BEFORE_RESAVE_ELEMENT`, and `EVENT_AFTER_RESAVE_ELEMENT` events. ([#3482](https://github.com/craftcms/cms/issues/3482))
- Added `craft\services\Revisions`, accessible via `Craft::$app->revisions`.
- Added `craft\web\Request::getIsLoginRequest()` and `craft\console\Request::getIsLoginRequest()`.
- Added `craft\web\UrlManager::$checkToken`.
- Added the `Craft.escapeRegex()` JavaScript method.
- Added the `Craft.parseUrl()` JavaScript method.
- Added the `Craft.isSameHost()` JavaScript method.
- Added the `Craft.DraftEditor` JavaScript class.
- Added the `Craft.Preview` JavaScript class.

### Changed
- Relational fields are now capable of selecting elements from multiple sites, if they haven’t been locked down to only related elements from a single site. ([#3584](https://github.com/craftcms/cms/issues/3584))
- Reference tags can now specify the site to load the element from. ([#2956](https://github.com/craftcms/cms/issues/2956))
- Improved the button layout of Edit Entry pages. ([#2325](https://github.com/craftcms/cms/issues/2325))
- The Control Panel now shows the sidebar on screens that are at least 1,000 pixels wide. ([#4079](https://github.com/craftcms/cms/issues/4079))
- The `_layouts/cp` template now supports a `showHeader` variable that can be set to `false` to remove the header.
- The `_layouts/cp` Control Panel template now supports a `footer` block, which will be output below the main content area.
- Renamed `craft\helpers\ArrayHelper::filterByValue()` to `where()`.
- Anonymous/offline/Control Panel access validation now takes place from `craft\web\Controller::beforeAction()` rather than `craft\web\Application::handleRequest()`, giving controllers a chance to do things like set CORS headers before a `ForbiddenHttpException` or `ServiceUnavailableHttpException` is thrown. ([#4008](https://github.com/craftcms/cms/issues/4008))
- Controllers can now set `$allowAnonymous` to a combination of bitwise integers `self::ALLOW_ANONYMOUS_LIVE` and `self::ALLOW_ANONYMOUS_OFFLINE`, or an array of action ID/bitwise integer pairs, to define whether their actions should be accessible anonymously even when the system is offline.
- Improved the error message when Project Config reaches the maximum deferred event count.
- `craft\base\ElementInterface::eagerLoadingMap()` and `craft\base\EagerLoadingFieldInterface::getEagerLoadingMap()` can now return `null` to opt out of eager-loading. ([#4220](https://github.com/craftcms/cms/pull/4220))
- `craft\db\ActiveRecord` no longer sets the `uid`, `dateCreated`, or `dateUpdated` values for new records if they were already explicitly set.
- `craft\db\ActiveRecord` no longer updates the `dateUpdated` value for existing records if nothing else changed or if `dateUpdated` had already been explicitly changed.
- `craft\helpers\UrlHelper::siteUrl()` and `url()` will now include the current request’s token in the generated URL’s query string, for site URLs.
- `craft\events\MoveElementEvent` now extends `craft\events\ElementEvent`. ([#4315](https://github.com/craftcms/cms/pull/4315))
- `craft\queue\BaseJob::setProgress()` now has a `$label` argument.
- `craft\queue\jobs\PropagateElements` no longer needs to be configured with a `siteId`, and no longer propagates elements to sites if they were updated in the target site more recently than the source site.
- `craft\queue\QueueInterface::setProgress()` now has a `$label` argument.
- `craft\services\Elements::deleteElement()` now has a `$hardDelete` argument.
- `craft\services\Elements::deleteElement()` now has a `$hardDelete` argument. ([#3392](https://github.com/craftcms/cms/issues/3392))
- `craft\services\Elements::getElementById()` now has a `$criteria` argument.
- `craft\services\Elements::propagateElement()` now has a `$siteElement` argument.
- `craft\services\Elements::saveElement()` now preserves existing elements’ current `dateUpdated` value when propagating or auto-resaving elements.
- `craft\services\Elements::saveElement()` now preserves the `uid`, `dateCreated`, and `dateUpdated` values on new elements if they were explicitly set. ([#2909](https://github.com/craftcms/cms/issues/2909))
- `craft\web\UrlManager::setRouteParams()` now has a `$merge` argument, which can be set to `false` to completely override the route params.

### Removed
- Removed the `--batch-size` option from `resave/*` actions.
- Removed the `craft.entryRevisions` Twig component.
- Removed `craft\events\VersionEvent`.
- Removed `craft\records\Entry::getVersions()`.
- Removed `craft\records\EntryDraft`.
- Removed `craft\records\EntryVersion`.
- Removed `craft\services\EntryRevisions::saveDraft()`.
- Removed `craft\services\EntryRevisions::publishDraft()`.
- Removed `craft\services\EntryRevisions::deleteDraft()`.
- Removed `craft\services\EntryRevisions::saveVersion()`.
- Removed `craft\services\EntryRevisions::revertEntryToVersion()`.
- Removed the `Craft.EntryDraftEditor` JavaScript class.

### Deprecated
- Deprecated `craft\controllers\LivePreviewController`.
- Deprecated `craft\helpers\ArrayHelper::filterByValue()`. Use `where()` instead.
- Deprecated `craft\models\BaseEntryRevisionModel`.
- Deprecated `craft\models\EntryDraft`.
- Deprecated `craft\models\EntryVersion`.
- Deprecated `craft\models\Section::$propagateEntries`. Use `$propagationMethod` instead.
- Deprecated `craft\services\EntryRevisions`.
- Deprecated `craft\web\Request::getIsLivePreview()`.
- Deprecated `craft\web\Request::getIsSingleActionRequest()` and `craft\console\Request::getIsSingleActionRequest()`.
- Deprecated the `Craft.LivePreview` JavaScript class.

### Fixed
- Fixed a bug where `craft\helpers\UrlHelper` methods could add duplicate query params on generated URLs.
