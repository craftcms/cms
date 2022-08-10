# Release Notes for Craft CMS 4.3 (WIP)

### Changed
- `users/session-info` responses now include a `csrfTokenName` key. ([#11706](https://github.com/craftcms/cms/pull/11706))
- `craft\services\Search::EVENT_BEFORE_INDEX_KEYWORDS` is now cancellable by setting `$event->isValid` to `false`. ([#11705](https://github.com/craftcms/cms/discussions/11705))
- `checkboxSelect` inputs without `showAllOption: true` now post an empty value if no options were selected. ([#11748](https://github.com/craftcms/cms/issues/11748))
