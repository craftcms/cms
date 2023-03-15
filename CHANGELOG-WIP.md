# Release Notes for Craft CMS 4.5 (WIP)

### Content Management

### Accessibility

### Administration

### Development

### Extensibility
- Deprecated `craft\helpers\UrlHelper::buildQuery()`. `http_build_query()` should be used instead.

### System
- All generated URL param characters are now properly encoded. ([#12796](https://github.com/craftcms/cms/issues/12796))
- `migrate` commands besides `migrate/create` no longer create the migration directory if it doesn’t exist yet. ([#12732](https://github.com/craftcms/cms/pull/12732))
