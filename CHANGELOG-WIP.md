# Release notes for Craft CMS 4.8.0 (WIP)

### Administration
- The `queue/run` command now supports a `--job-id` option.

### Development
- The `{% js %}` and `{% css %}` tags now support `.js.gz` and `.css.gz` URLs. ([#14243](https://github.com/craftcms/cms/issues/14243))

### Extensibility
- Added `craft\services\Relations::deleteLeftoverRelations()`. ([#13956](https://github.com/craftcms/cms/issues/13956))

### System
- Relations for fields that are no longer included in an element’s field layout are now deleted after element save. ([#13956](https://github.com/craftcms/cms/issues/13956))
