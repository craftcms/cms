# Release notes for Craft CMS 4.6 (WIP)

- Added the “Country” field type. ([#13789](https://github.com/craftcms/cms/discussions/13789))
- Improved element search performance. ([#14055](https://github.com/craftcms/cms/pull/14055))
- Added partial support for field types storing data in JSON columns (excluding MariaDB). ([#13916](https://github.com/craftcms/cms/issues/13916))
- “Updating search indexes” jobs are no longer queued when saving elements with change tracking enabled, if no searchable fields or attributes were changed. ([#13917](https://github.com/craftcms/cms/issues/13917))
- `resave` commands now pass an empty string (`''`) to fields’ `normalizeValue()` methods when `--to` is set to `:empty:`. ([#13951](https://github.com/craftcms/cms/issues/13951))
- Added `craft\helpers\ElementHelper::searchableAttributes()`.
- Added `craft\services\Elements::setElementUri()`.
- Added `craft\services\Elements::EVENT_SET_ELEMENT_URI`. ([#13930](https://github.com/craftcms/cms/discussions/13930))
- Added `craft\services\Search::createDbQuery()`.
