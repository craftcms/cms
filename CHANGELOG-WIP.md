# Release Notes for Craft CMS 5.9 (WIP)

### Content Management
- Matrix fields using “Cards” or “Inline” view modes now show an “Add” button per entry type group, when the viewport is wide enough to support it. ([#17731](https://github.com/craftcms/cms/pull/17731))
- Chips and cards are generally no longer hyperlinked. ([#17591](https://github.com/craftcms/cms/pull/17591))
- It’s now possible to add new sites to entries via their slideout editors. ([#17795](https://github.com/craftcms/cms/issues/17795))
- Elements created via “Save as a new…” actions now initially have an empty slug. ([#17932](https://github.com/craftcms/cms/pull/17932))

### Accessibility
- Improved the accessibility of the Orientation setting within the Image Editor’s crop tool. ([#17690](https://github.com/craftcms/cms/pull/17690))
- The Image Editor’s focal point tool is now keyboard accessible. ([#17880](https://github.com/craftcms/cms/pull/17880))

### Administration
- It’s now possible to divide entry sources into multiple index pages, via the Customize Sources modal. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added the “View user” GraphQL schema option for Craft Solo. ([#17863](https://github.com/craftcms/cms/pull/17863))
- Users’ User Groups settings now show a component select input, and support inline group editing/creation on environments that allow administrative changes.
- Relational fields now have an “Inline list” view mode. ([#17744](https://github.com/craftcms/cms/pull/17744))
- Relational fields and Matrix fields now have a “Card grid” view mode, replacing the “Show cards in a grid” setting. ([#17744](https://github.com/craftcms/cms/pull/17744))
- Relational fields’ selectable element conditions can now have “Status” condition rules. ([#17945](https://github.com/craftcms/cms/discussions/17945))
- Added the “Show ON/OFF labels in cards” setting to Lightswitch fields. ([#17743](https://github.com/craftcms/cms/discussions/17743))
- Control panel-defined routes now have action menus with “Move up”/“Move down” actions. ([#17706](https://github.com/craftcms/cms/pull/17706))
- “Generate image transform” jobs now include the asset’s filename in the job description. ([#17753](https://github.com/craftcms/cms/issues/17753))
- “Field” and “Section” condition rules now show field/section handles for users with the “Show field handles in edit forms” preference enabled. ([#17909](https://github.com/craftcms/cms/pull/17909))
- “Remove” actions on the Plugins index page now show a confirmation dialog. ([#17922](https://github.com/craftcms/cms/pull/17922))
- `entrify` commands no longer require a category group/tag group/global set handle to be passed.
- `entrify` commands now automatically assign newly-created channel/structure sections to “Categories” or “Tags” pages. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added the `useIdnaNontransitionalToUnicode` config setting. ([#17946](https://github.com/craftcms/cms/pull/17946))

### Development
- Reference tags now support fallback values when no attribute is specified. ([#17688](https://github.com/craftcms/cms/pull/17688))
- Added support for referencing environment variables anywhere within settings that support them (e.g. `foo/$ENV_NAME/bar` or `foo-${ENV_NAME}-bar`). ([#17794](https://github.com/craftcms/cms/pull/17794))
- Environmental settings can now reference `CRAFT_SITE` (the current site’s handle) and `CRAFT_SITE_UPPER` (the current site’s handle in UPPER_SNAKE_CASE) environment variables, which are defined at runtime. ([#17794](https://github.com/craftcms/cms/pull/17794))
- It’s now possible to create unpublished drafts via GraphQL. ([#17805](https://github.com/craftcms/cms/pull/17805))
- Added the `uuid()` Twig function.
- The Twig `hash` filter now supports passing a hashing algorithm, such as `'md5'` or `'sha256'`. ([#17885](https://github.com/craftcms/cms/issues/17885))

### Extensibility
- Subnav items within the global control panel navigation can now have icons. ([#17879](https://github.com/craftcms/cms/pull/17879))
- Added `Craft.BaseElementIndex::asyncSelectDefaultSource()`.
- Added `Craft.BaseElementIndex::asyncSelectSource()`.
- Added `Craft.BaseElementIndex::asyncSelectSourceByKey()`.
- Added `Craft.BaseElementIndex::ensureSourceAttributeInfo()`.
- Added `craft\base\ElementIndex::multiPageSources()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\base\ElementTrait::$hasProvisionalChanges`. ([#17915](https://github.com/craftcms/cms/pull/17915))
- Added `craft\elements\conditions\HintableConditionRuleTrait`. ([#17909](https://github.com/craftcms/cms/pull/17909))
- Added `craft\events\RegisterElementCardAttributesEvent::$fieldLayout`. ([#17920](https://github.com/craftcms/cms/pull/17920))
- Added `craft\fields\BaseRelationField::VIEW_MODE_CARDS_GRID`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_CARDS`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_LIST_INLINE`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_LIST`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_THUMBS`.
- Added `craft\fields\Matrix::VIEW_MODE_CARDS_GRID`.
- Added `craft\helpers\ElementHelper::loadProvisionalChanges()`. ([#17915](https://github.com/craftcms/cms/pull/17915))
- Added `craft\services\ElementSources::getFirstPage()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\services\ElementSources::getPageSettings()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\services\ElementSources::getPages()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\services\ElementSources::pageExists()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\services\ElementSources::pageNameId()`. ([#17779](https://github.com/craftcms/cms/pull/17779))
- Added `craft\web\GqlResponseFormatter`.
- Added `craft\web\Response::FORMAT_GQL`.
- Added `craft\web\twig\nodes\BaseNode`.
- `craft\base\ElementInterface::cardAttributes()` now has a `$fieldLayout` argument. ([#17920](https://github.com/craftcms/cms/pull/17920))
- `craft\helpers\FileHelper::writeToFile()` now throws an exception if the file path isn’t writable, or there isn’t sufficient free space on the disk. ([#17762](https://github.com/craftcms/cms/pull/17762))
- `craft\helpers\UrlHelper` now encodes square brackets in generated URLs. ([#17840](https://github.com/craftcms/cms/pull/17840))
- `craft\services\ElementSources::getSources()` now has a `$page` argument. ([#17779](https://github.com/craftcms/cms/pull/17779))
- `craft\services\ElementSources::sourceExists()` now has a `$page` argument. ([#17779](https://github.com/craftcms/cms/pull/17779))
- `craft\web\Request::accepts()` now accepts wildcard characters (`*`) in the `$contentType` argument, to check for a range of MIME types (e.g. `application/*+json`).
- `craft\web\Request::getAcceptsJson()` now returns `true` for requests with `Content-Type` headers that match `application/*+json`, in addition to `application/json`.
- Deprecated `craft\fields\BaseRelationField::$showCardsInGrid`.
- Deprecated `craft\fields\Matrix::$showCardsInGrid`.
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
- Updated Twig to 3.21. ([#17603](https://github.com/craftcms/cms/discussions/17603))
