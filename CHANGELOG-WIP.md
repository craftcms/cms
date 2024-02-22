# Release notes for Craft CMS 4.8.0 (WIP)

### Content Management
- Assets fields’ selection modals now open to the last-viewed location by default, if their Default Upload Location doesn’t specify a subpath. ([#14382](https://github.com/craftcms/cms/pull/14382))
- Element sources no longer display `0` badges.

### Administration
- The `queue/run` command now supports a `--job-id` option.

### Development
- The `{% js %}` and `{% css %}` tags now support `.js.gz` and `.css.gz` URLs. ([#14243](https://github.com/craftcms/cms/issues/14243))
- Relation fields’ element query params now factor in the element query’s target site(s). ([#14258](https://github.com/craftcms/cms/issues/14258), [#14348](https://github.com/craftcms/cms/issues/14348), [#14304](https://github.com/craftcms/cms/pull/14304))
- Element queries’ `level` param now supports passing an array which includes `null`. ([#14419](https://github.com/craftcms/cms/issues/14419))

### Extensibility
- Added `craft\services\ProjectConfig::EVENT_AFTER_WRITE_YAML_FILES`. ([#14365](https://github.com/craftcms/cms/discussions/14365))
- Added `craft\services\Relations::deleteLeftoverRelations()`. ([#13956](https://github.com/craftcms/cms/issues/13956))
- Added `craft\services\Search::shouldCallSearchElements()`. ([#14293](https://github.com/craftcms/cms/issues/14293))

### System
- Relations for fields that are no longer included in an element’s field layout are now deleted after element save. ([#13956](https://github.com/craftcms/cms/issues/13956))
- The Sendmail email transport type now uses the `sendmail_path` PHP ini setting by default. ([#14433](https://github.com/craftcms/cms/pull/14433))
