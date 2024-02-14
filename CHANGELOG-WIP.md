# Release notes for Craft CMS 4.8.0 (WIP)

### Administration
- The `queue/run` command now supports a `--job-id` option.

### Development
- The `{% js %}` and `{% css %}` tags now support `.js.gz` and `.css.gz` URLs. ([#14243](https://github.com/craftcms/cms/issues/14243))
- Relation fields’ element query params now factor in the element query’s target site(s). ([#14258](https://github.com/craftcms/cms/issues/14258), [#14348](https://github.com/craftcms/cms/issues/14348), [#14304](https://github.com/craftcms/cms/pull/14304))

### Extensibility
- Added `craft\services\Relations::deleteLeftoverRelations()`. ([#13956](https://github.com/craftcms/cms/issues/13956))
- Added `craft\services\Search::shouldCallSearchElements()`. ([#14293](https://github.com/craftcms/cms/issues/14293))

### System
- Relations for fields that are no longer included in an element’s field layout are now deleted after element save. ([#13956](https://github.com/craftcms/cms/issues/13956))
