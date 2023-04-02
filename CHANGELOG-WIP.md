# Release Notes for Craft CMS 4.5 (WIP)

### Content Management

### Accessibility
- Image assets’ thumbnails and `<img>` tags generated via `craft\element\Asset::getImg()` no longer use the assets’ titles as `alt` fallback values. ([#12854](https://github.com/craftcms/cms/pull/12854))
- Element index pages now have visually-hidden “Sources” headings for screen readers. ([#12961](https://github.com/craftcms/cms/pull/12961))
- Element metadata fields now have visually-hidden “Metadata” headings for screen readers. ([#12961](https://github.com/craftcms/cms/pull/12961))

### Administration

### Development
- The `|replace` Twig filter now supports passing in a hash with regular expression keys. ([#12956](https://github.com/craftcms/cms/issues/12956))
- Elements now include custom field values when being iterated over, and when being merged. ([#13009](https://github.com/craftcms/cms/issues/13009))

### Extensibility
- Added `craft\web\CpScreenResponseBehavior::$pageSidebar`, `pageSidebar()`, and `pageSidebarTemplate()`. ([#13019](https://github.com/craftcms/cms/pull/13019), [#12795](https://github.com/craftcms/cms/issues/12795))
- `craft\validators\UniqueValidator` now supports setting an additional filter via the `filter` property. ([#12941](https://github.com/craftcms/cms/pull/12941))
- Deprecated `craft\helpers\UrlHelper::buildQuery()`. `http_build_query()` should be used instead.

### System
- All generated URL param characters are now properly encoded. ([#12796](https://github.com/craftcms/cms/issues/12796))
- `migrate` commands besides `migrate/create` no longer create the migration directory if it doesn’t exist yet. ([#12732](https://github.com/craftcms/cms/pull/12732))
- Dropdown and Radio Buttons fields now use smart `content` table column lengths, based on their longest option value’s length. ([#13025](https://github.com/craftcms/cms/pull/13025), [#12954](https://github.com/craftcms/cms/issues/12954))
- When `content` table columns are resized, if any existing values are too long, all column data is now backed up into a new table, and the overflowing values are set to `null`. ([#13025](https://github.com/craftcms/cms/pull/13025))
- When `content` table columns are renamed, if an existing column with the same name already exists, the original column data is now backed up into a new table and then deleted from the `content` table. ([#13025](https://github.com/craftcms/cms/pull/13025))
