# Release Notes for Craft CMS 4.5 (WIP)

### Content Management

### Accessibility
- Image assets’ thumbnails and `<img>` tags generated via `craft\element\Asset::getImg()` no longer use the assets’ titles as `alt` fallback values. ([#12854](https://github.com/craftcms/cms/pull/12854))
- Element index pages now have visually-hidden “Sources” headings for screen readers. ([#12961](https://github.com/craftcms/cms/pull/12961))
- Element metadata fields now have visually-hidden “Metadata” headings for screen readers. ([#12961](https://github.com/craftcms/cms/pull/12961))

### Administration

### Development
- The `|replace` Twig filter now supports passing in a hash with regular expression keys. ([#12956](https://github.com/craftcms/cms/issues/12956))

### Extensibility
- Added `craft\web\CpScreenResponseBehavior::$pageSidebar`, `pageSidebar()`, and `pageSidebarTemplate()`. ([#13019](https://github.com/craftcms/cms/pull/13019), [#12795](https://github.com/craftcms/cms/issues/12795))
- `craft\validators\UniqueValidator` now supports setting an additional filter via the `filter` property. ([#12941](https://github.com/craftcms/cms/pull/12941))
- Deprecated `craft\helpers\UrlHelper::buildQuery()`. `http_build_query()` should be used instead.

### System
- All generated URL param characters are now properly encoded. ([#12796](https://github.com/craftcms/cms/issues/12796))
- `migrate` commands besides `migrate/create` no longer create the migration directory if it doesn’t exist yet. ([#12732](https://github.com/craftcms/cms/pull/12732))
