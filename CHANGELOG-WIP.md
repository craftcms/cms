# Release Notes for Craft CMS 5.4 (WIP)

### Content Management
- Asset chips and cards no longer include the “Replace file” action. ([#15498](https://github.com/craftcms/cms/issues/15498))

### Extensibility
- `craft\services\Elements::saveContent()`’ now saves dirty fields’ content even if `$saveContent` is `false`. ([#15393](https://github.com/craftcms/cms/pull/15393))
- Element action menu items returned by `craft\base\Element::safeActionMenuItems()` and `destructiveActionMenuItems()` can now include a `showInChips` key to explicitly opt into/out of being shown within element chips and cards.
