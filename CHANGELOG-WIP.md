# Release notes for Craft 4.2.0 (WIP)

### Added
- Added `craft\events\IndexKeywordsEvent`.
- Added `craft\services\Search::EVENT_BEFORE_INDEX_KEYWORDS`. ([#11575](https://github.com/craftcms/cms/discussions/11575))

### Changed
- Improved condition builder accessibility. ([#11588](https://github.com/craftcms/cms/pull/11588))
- The `|length` Twig filter now checks if the variable is a query, and if so, returns its count. ([#11625](https://github.com/craftcms/cms/discussions/11625))
- `craft\db\Query` now implements the `ArrayAccess` and `IteratorAggregate` interfaces, so queries (including element queries) can be treated as arrays.
