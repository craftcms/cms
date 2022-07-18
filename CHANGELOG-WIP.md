# Release notes for Craft 4.2.0 (WIP)

### Added
- Added `craft\events\IndexKeywordsEvent`.
- Added `craft\helpers\DateTimeHelper::humanDuration()`.
- Added `craft\services\Search::EVENT_BEFORE_INDEX_KEYWORDS`. ([#11575](https://github.com/craftcms/cms/discussions/11575))

### Changed
- Improved condition builder accessibility. ([#11588](https://github.com/craftcms/cms/pull/11588))
- The “Keep me signed in” checkbox label on the control panel’s login page now includes the remembered session duration, e.g. “Keep me signed in for 2 weeks”. ([#11594](https://github.com/craftcms/cms/discussions/11594))
- The `|duration` Twig filter can now be used with an integer representing a number of seconds, and its `showSeconds` argument is no longer required. Seconds will be output if the duration is less than one minute by default.
- The `|length` Twig filter now checks if the variable is a query, and if so, returns its count. ([#11625](https://github.com/craftcms/cms/discussions/11625))
- `craft\db\Query` now implements the `ArrayAccess` and `IteratorAggregate` interfaces, so queries (including element queries) can be treated as arrays.

### Deprecated
- Deprecated `craft\helpers\DateTimeHelper::humanDurationFromInterval()`. `humanDuration()` should be used instead.
- Deprecated `craft\helpers\DateTimeHelper::secondsToHumanTimeDuration()`. `humanDuration()` should be used instead.
