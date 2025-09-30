# Release Notes for Craft CMS 5.9 (WIP)

### Content Management
- Matrix fields using “Cards” or “Inline” view modes now show an “Add” button per entry type group, when the viewport is wide enough to support it. ([#17731](https://github.com/craftcms/cms/pull/17731))
- Chips and cards are generally no longer hyperlinked. ([#17591](https://github.com/craftcms/cms/pull/17591))
- It’s now possible to add new sites to entries via their slideout editors. ([#17795](https://github.com/craftcms/cms/issues/17795))

### Accessibility
- Improved the accessibility of the Orientation setting within the Image Editor’s crop tool. ([#17690](https://github.com/craftcms/cms/pull/17690))

### Administration
- Added the “View user” GraphQL schema option for Craft Solo. ([#17863](https://github.com/craftcms/cms/pull/17863))
- Users’ User Groups settings now show a component select input, and support inline group editing/creation on environments that allow administrative changes.
- Relational fields now have an “Inline list” view mode. ([#17744](https://github.com/craftcms/cms/pull/17744))
- Relational fields and Matrix fields now have a “Card grid” view mode, replacing the “Show cards in a grid” setting. ([#17744](https://github.com/craftcms/cms/pull/17744))
- Added the “Show ON/OFF labels in cards” setting to Lightswitch fields. ([#17743](https://github.com/craftcms/cms/discussions/17743))
- Control panel-defined routes now have action menus with “Move up”/“Move down” actions. ([#17706](https://github.com/craftcms/cms/pull/17706))
- “Generate image transform” jobs now include the asset’s filename in the job description. ([#17753](https://github.com/craftcms/cms/issues/17753))

### Development
- Reference tags now support fallback values when no attribute is specified. ([#17688](https://github.com/craftcms/cms/pull/17688))
- Added support for referencing environment variables anywhere within settings that support them (e.g. `foo/$ENV_NAME/bar` or `foo-${ENV_NAME}-bar`). ([#17794](https://github.com/craftcms/cms/pull/17794))
- Environmental settings can now reference `CRAFT_SITE` (the current site’s handle) and `CRAFT_SITE_UPPER` (the current site’s handle in UPPER_SNAKE_CASE) environment variables, which are defined at runtime. ([#17794](https://github.com/craftcms/cms/pull/17794))
- It’s now possible to create unpublished drafts via GraphQL. ([#17805](https://github.com/craftcms/cms/pull/17805))

### Extensibility
- Added `Craft.BaseElementIndex::asyncSelectDefaultSource()`.
- Added `Craft.BaseElementIndex::asyncSelectSource()`.
- Added `Craft.BaseElementIndex::asyncSelectSourceByKey()`.
- Added `Craft.BaseElementIndex::ensureSourceAttributeInfo()`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_CARDS_GRID`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_CARDS`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_LIST_INLINE`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_LIST`.
- Added `craft\fields\BaseRelationField::VIEW_MODE_THUMBS`.
- Added `craft\fields\Matrix::VIEW_MODE_CARDS_GRID`.
- Added `craft\web\GqlResponseFormatter`.
- Added `craft\web\Response::FORMAT_GQL`.
- Added `craft\web\twig\nodes\BaseNode`.
- `craft\helpers\FileHelper::writeToFile()` now throws an exception if the file path isn’t writable, or there isn’t sufficient free space on the disk. ([#17762](https://github.com/craftcms/cms/pull/17762))
- `craft\helpers\UrlHelper` now encodes square brackets in generated URLs. ([#17840](https://github.com/craftcms/cms/pull/17840))
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
- Updated Twig to 3.21. ([#17603](https://github.com/craftcms/cms/discussions/17603))
