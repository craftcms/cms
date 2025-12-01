# Release Notes for Craft CMS 5.9 (WIP)

### Content Management
- Matrix fields using “Cards” or “Inline” view modes now show an “Add” button per entry type group, when the viewport is wide enough to support it. ([#17731](https://github.com/craftcms/cms/pull/17731))
- Matrix fields set to the “Inline” view mode now have “Expand/collapse selected blocks” and “Copy selected blocks” field-level actions, if any blocks are selected. ([#18001](https://github.com/craftcms/cms/discussions/18001))
- Matrix fields set to the “Inline” view mode now have block action menus with “Expand/Collapse”, “Entry type settings”, and “Copy” actions, even if the field isn’t editable. ([#18013](https://github.com/craftcms/cms/discussions/18013)) 
- Chips and cards are generally no longer hyperlinked. ([#17591](https://github.com/craftcms/cms/pull/17591))
- Entry revision menus now always include a “View all revisions” link. ([#18050](https://github.com/craftcms/cms/pull/18050))
- Timestamps within entry revision menus now have tooltips that reveal the full date and time. ([#18050](https://github.com/craftcms/cms/pull/18050))
- It’s now possible to add new sites to entries via their slideout editors. ([#17795](https://github.com/craftcms/cms/issues/17795))
- Elements created via “Save as a new…” actions now initially have an empty slug. ([#17932](https://github.com/craftcms/cms/pull/17932))
- The control panel is no longer scrollable when a menu is expanded. ([#17960](https://github.com/craftcms/cms/issues/17960))
- Most site breadcrumbs no longer include selection menus if there’s only one selectable site. ([#16526](https://github.com/craftcms/cms/discussions/16526))
- Number fields with “Step Size” and “Min Value” or “Max Value” settings will now get `min`/`max` attributes set on their input. ([#17973](https://github.com/craftcms/cms/pull/17973))
- Element, field, and entry type edit pages now redirect back to the previous page’s URL on save. ([#16140](https://github.com/craftcms/cms/pull/16140))
- Bulk element actions are now available on element indexes for mobile devices.

### Accessibility
- Improved the accessibility of the Orientation setting within the Image Editor’s crop tool. ([#17690](https://github.com/craftcms/cms/pull/17690))
- The Image Editor’s focal point tool is now keyboard accessible. ([#17880](https://github.com/craftcms/cms/pull/17880))
- All sortable checkbox select options, selected Dashboard widgets, and site listings now have keyboard-accessible “Move up” and “Move down” action items. ([#18067](https://github.com/craftcms/cms/pull/18067))

### Administration
- It’s now possible to divide entry sources into multiple index pages, via the Customize Sources modal. ([#17779](https://github.com/craftcms/cms/pull/17779))
- The Customize Sources modal now supports mobile devices. ([#18067](https://github.com/craftcms/cms/pull/18067))
- Added the “UI Label Format” entry type setting. ([#18044](https://github.com/craftcms/cms/pull/18044))
- Added the “View user” GraphQL schema option for Craft Solo. ([#17863](https://github.com/craftcms/cms/pull/17863))
- Users’ User Groups settings now show a component select input, and support inline group editing/creation on environments that allow administrative changes.
- Address labels can now be made optional. ([#11410](https://github.com/craftcms/cms/discussions/11410))
- Relational fields now have an “Inline list” view mode. ([#17744](https://github.com/craftcms/cms/pull/17744))
- Relational fields and Matrix fields now have a “Card grid” view mode, replacing the “Show cards in a grid” setting. ([#17744](https://github.com/craftcms/cms/pull/17744))
- Relational fields’ selectable element conditions can now have “Status” condition rules. ([#17945](https://github.com/craftcms/cms/discussions/17945))
- Added the “Show ON/OFF labels in cards” setting to Lightswitch fields. ([#17743](https://github.com/craftcms/cms/discussions/17743))
- Control panel-defined routes now have action menus with “Move up”/“Move down” actions. ([#17706](https://github.com/craftcms/cms/pull/17706))
- “Generate image transform” jobs now include the asset’s filename in the job description. ([#17753](https://github.com/craftcms/cms/issues/17753))
- “Field” and “Section” condition rules now show field/section handles for users with the “Show field handles in edit forms” preference enabled. ([#17909](https://github.com/craftcms/cms/pull/17909))
- Native fields within element edit pages now have “Copy attribute name” actions. ([#18114](https://github.com/craftcms/cms/pull/18114))
- “Remove” actions on the Plugins index page now show a confirmation dialog. ([#17922](https://github.com/craftcms/cms/pull/17922))
- `entrify` commands no longer require a category group/tag group/global set handle to be passed.
- `entrify` commands now automatically assign newly-created channel/structure sections to “Categories” or “Tags” pages. ([#17779](https://github.com/craftcms/cms/pull/17779))
- The `clear-cache` command now accepts a space-delimited list of cache IDs that should be cleared.
- Compiled templates are now deleted by the `up` command rather than from `migrate` commands.
- Added the `useIdnaNontransitionalToUnicode` config setting. ([#17946](https://github.com/craftcms/cms/pull/17946))
- The `maxCachedCloudImageSize` config setting is now set to `0` by default. ([#17997](https://github.com/craftcms/cms/pull/17997))
- System message emails are now rendered using GitHub-flavored Markdown. ([#18058](https://github.com/craftcms/cms/discussions/18058))
- Drag-and-drop icons are now longer shown for devices that don’t support pointer events. ([#18067](https://github.com/craftcms/cms/pull/18067))

### Development
- Reference tags now support fallback values when no attribute is specified. ([#17688](https://github.com/craftcms/cms/pull/17688))
- Added support for referencing environment variables anywhere within settings that support them (e.g. `foo/$ENV_NAME/bar` or `foo-${ENV_NAME}-bar`). ([#17794](https://github.com/craftcms/cms/pull/17794))
- Environmental settings can now reference `CRAFT_SITE` (the current site’s handle) and `CRAFT_SITE_UPPER` (the current site’s handle in UPPER_SNAKE_CASE) environment variables, which are defined at runtime. ([#17794](https://github.com/craftcms/cms/pull/17794))
- It’s now possible to create unpublished drafts via GraphQL. ([#17805](https://github.com/craftcms/cms/pull/17805))
- Added the `randomString()` Twig function. ([#18020](https://github.com/craftcms/cms/discussions/18020))
- Added the `uuid()` Twig function.
- The Twig `hash` filter now supports passing a hashing algorithm, such as `'md5'` or `'sha256'`. ([#17885](https://github.com/craftcms/cms/issues/17885))

### Extensibility
- Subnav items within the global control panel navigation can now have icons. ([#17879](https://github.com/craftcms/cms/pull/17879))
- Added `craft\base\ElementIndex::multiPageSources()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\base\ElementTrait::$applyingDraft`. ([#18057](https://github.com/craftcms/cms/pull/18057))
- Added `craft\base\ElementTrait::$hasProvisionalChanges`. ([#17915](https://github.com/craftcms/cms/pull/17915))
- Added `craft\base\ElementTrait::$propagateRequired`.
- Added `craft\base\FieldInterface::propagateValue()`.
- Added `craft\elements\User::isInGroups()`. ([#17989](https://github.com/craftcms/cms/discussions/17989))
- Added `craft\elements\conditions\HintableConditionRuleTrait`. ([#17909](https://github.com/craftcms/cms/pull/17909))
- Added `craft\events\DefineFieldActionsEvent`.
- Added `craft\events\DefineGqlArgumentsEvent`.
- Added `craft\events\RegisterElementCardAttributesEvent::$fieldLayout`. ([#17920](https://github.com/craftcms/cms/pull/17920))
- Added `craft\fieldlayoutelements\BaseField::EVENT_DEFINE_ACTION_MENU_ITEMS`. ([#18037](https://github.com/craftcms/cms/issues/18037))
- Added `craft\fieldlayoutelements\BaseField::copyAttributeAction()`. ([#18114](https://github.com/craftcms/cms/pull/18114))
- Added `craft\fields\BaseRelationField::VIEW_MODE_CARDS_GRID`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_CARDS`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_LIST_INLINE`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_LIST`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_THUMBS`.
- Added `craft\fields\Matrix::VIEW_MODE_CARDS_GRID`.
- Added `craft\gql\base\ElementArguments::EVENT_DEFINE_ARGUMENTS`. ([#18062](https://github.com/craftcms/cms/discussions/18062))
- Added `craft\helpers\ElementHelper::loadProvisionalChanges()`. ([#17915](https://github.com/craftcms/cms/pull/17915))
- Added `craft\helpers\UrlHelper::cpReferralUrl()`.
- Added `craft\models\EntryType::$uiLabelFormat`.
- Added `craft\models\Section::getCpIndexUri()`.
- Added `craft\models\Section::getPage()`.
- Added `craft\services\ElementSources::getFirstPage()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\services\ElementSources::getPageSettings()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\services\ElementSources::getPages()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\services\ElementSources::pageExists()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\services\ElementSources::pageNameId()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\services\Structure::EVENT_AFTER_UPDATE_ELEMENT`.
- Added `craft\services\Structure::EVENT_BEFORE_UPDATE_ELEMENT`.
- Added `craft\web\GqlResponseFormatter`.
- Added `craft\web\Request::getHasInvalidToken()`.
- Added `craft\web\Response::FORMAT_GQL`.
- Added `craft\web\twig\nodes\BaseNode`.
- Added `craft\helpers\Assets::resolveSubpath()`. ([#18103](https://github.com/craftcms/cms/pull/18103))
- Added `Craft.BaseElementIndex::asyncSelectDefaultSource()`.
- Added `Craft.BaseElementIndex::asyncSelectSource()`.
- Added `Craft.BaseElementIndex::asyncSelectSourceByKey()`.
- Added `Craft.BaseElementIndex::ensureSourceAttributeInfo()`.
- `craft\base\Element::EVENT_AFTER_MOVE_IN_STRUCTURE` is no longer deprecated.
- `craft\base\Element::EVENT_BEFORE_MOVE_IN_STRUCTURE` is no longer deprecated.
- `craft\base\ElementInterface::afterMoveInStructure()` is no longer deprecated.
- `craft\base\ElementInterface::beforeMoveInStructure()` is no longer deprecated.
- `craft\base\ElementInterface::cardAttributes()` now has a `$fieldLayout` argument. ([#17920](https://github.com/craftcms/cms/pull/17920))
- `craft\events\ElementStructureEvent` is no longer deprecated.
- `craft\helpers\ElementHelper::findSource()` now has `$withDisabled` and `$page` arguments.
- `craft\helpers\FileHelper::writeToFile()` now throws an exception if the file path isn’t writable, or there isn’t sufficient free space on the disk. ([#17762](https://github.com/craftcms/cms/pull/17762))
- `craft\helpers\UrlHelper` now encodes square brackets in generated URLs. ([#17840](https://github.com/craftcms/cms/pull/17840))
- `craft\services\ElementSources::getSources()` now has a `$page` argument. ([#17779](https://github.com/craftcms/cms/pull/17779))
- `craft\services\ElementSources::sourceExists()` now has a `$page` argument. ([#17779](https://github.com/craftcms/cms/pull/17779))
- `craft\web\Request::accepts()` now accepts wildcard characters (`*`) in the `$contentType` argument, to check for a range of MIME types (e.g. `application/*+json`).
- `craft\web\Request::getAcceptsJson()` now returns `true` for requests with `Content-Type` headers that match `application/*+json`, in addition to `application/json`.
- Deprecated `craft\fields\BaseRelationField::$showCardsInGrid`.
- Deprecated `craft\fields\Matrix::$showCardsInGrid`.
- Deprecated `craft\services\Structure::EVENT_AFTER_MOVE_ELEMENT`. `EVENT_AFTER_UPDATE_ELEMENT` should be used instead.
- Deprecated `craft\services\Structure::EVENT_BEFORE_MOVE_ELEMENT`. `EVENT_BEFORE_UPDATE_ELEMENT` should be used instead.
- Deprecated `Craft.BaseElementIndex::selectDefaultSource()`.
- Deprecated `Craft.BaseElementIndex::selectSource()`.
- Deprecated `Craft.BaseElementIndex::selectSourceByKey()`.

### System
- GraphQL API responses now set their `Content-Type` header to `application/graphql-response+json`.
- GraphQL API responses now set cache headers based on whether a mutation was performed, regardless of the request type.
- Global set queries no longer register cache tags.
- Improved element index performance. ([#17557](https://github.com/craftcms/cms/pull/17557))
- Improved element query performance. ([#17850](https://github.com/craftcms/cms/pull/17850))
- Fixed a bug where elements with unsaved changes could show outdated attribute/field values within element index tables, chips, and cards throughout the control panel. ([#17915](https://github.com/craftcms/cms/pull/17915))
- Fixed a bug where Table fields with the “Static Rows” setting enabled would lose track of which values belonged to which row headings, if the “Default Values” table was reordered. ([#17090](https://github.com/craftcms/cms/issues/17090))
- Fixed a bug where requests with invalid tokens would throw an exception before the application was fully initialized, which could lead to other errors. ([#18000](https://github.com/craftcms/cms/issues/18000))
- Fixed a bug where titles, slugs, and required custom field values weren’t always getting propagated to other sites when creating a new element. ([#17955](https://github.com/craftcms/cms/issues/17955))
- Updated Twig to 3.21. ([#17603](https://github.com/craftcms/cms/discussions/17603))
