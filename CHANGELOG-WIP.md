# Release Notes for Craft CMS 5.4 (WIP)

### Content Management
- Element conditions can now have a “Not Related To” rule. ([#15496](https://github.com/craftcms/cms/pull/15496))
- Asset chips and cards no longer include the “Replace file” action. ([#15498](https://github.com/craftcms/cms/issues/15498))
- Category slugs are now inline-editable from the Categories index page. ([#15560](https://github.com/craftcms/cms/pull/15560))
- Entry post dates, expiry dates, slugs, and authors are now inline-editable from the Entries index page. ([#15560](https://github.com/craftcms/cms/pull/15560))
- Improved Addresses field validation to be more consistent with Matrix fields.

### Accessibility
- Improved the accessibility of Tags fields.

### Administration
- Link fields now have “Allow root-relative URLs” and “Allow anchors” settings. ([#15579](https://github.com/craftcms/cms/issues/15579))
- Custom field selectors within field layouts now display a pencil icon if their name, instructions, or handle have been overridden. ([#15597](https://github.com/craftcms/cms/discussions/15597))
- Custom field settings within field layouts now display a chip for the associated global field. ([#15619](https://github.com/craftcms/cms/pull/15619), [#15597](https://github.com/craftcms/cms/discussions/15597))

### Development
- Added the `notRelatedTo` and `andNotRelatedTo` element query params. ([#15496](https://github.com/craftcms/cms/pull/15496))
- Added the `notRelatedTo` GraphQL element query argument. ([#15496](https://github.com/craftcms/cms/pull/15496))
- `relatedToAssets`, `relatedToCategories`, `relatedToEntries`, `relatedToTags`, and `relatedToUsers` GraphQL arguments now support passing `relatedViaField` and `relatedViaSite` keys to their criteria objects. ([#15508](https://github.com/craftcms/cms/pull/15508))
- Country field values and `craft\elements\Address::getCountry()` now return the country in the current application locale.

### Extensibility
- Added `craft\base\ApplicationTrait::getEnvId()`. ([#15313](https://github.com/craftcms/cms/issues/15313))
- Added `craft\base\ElementInterface::getRootOwner()`. ([#15534](https://github.com/craftcms/cms/discussions/15534))
- Added `craft\elements\conditions\NotRelatedToConditionRule`.
- Added `craft\gql\arguments\RelationCriteria`.
- Added `craft\gql\types\input\criteria\AssetRelation`.
- Added `craft\gql\types\input\criteria\CategoryRelation`.
- Added `craft\gql\types\input\criteria\EntryRelation`.
- Added `craft\gql\types\input\criteria\TagRelation`.
- Added `craft\gql\types\input\criteria\UserRelation`.
- Added `craft\helpers\Inflector`.
- `craft\services\Elements::saveContent()`’ now saves dirty fields’ content even if `$saveContent` is `false`. ([#15393](https://github.com/craftcms/cms/pull/15393))
- Deprecated `craft\db\mysql\Schema::quoteDatabaseName()`.
- Deprecated `craft\db\pgqsl\Schema::quoteDatabaseName()`.
- Deprecated `craft\helpers\ElementHelper::rootElement()`. `craft\base\ElementInterface::getRootOwner()` should be used instead.
- Added `Craft.cp.announce()`, simplifying live region announcements for screen readers. ([#15569](https://github.com/craftcms/cms/pull/15569))
- Element action menu items returned by `craft\base\Element::safeActionMenuItems()` and `destructiveActionMenuItems()` can now include a `showInChips` key to explicitly opt into/out of being shown within element chips and cards.
- Control panel CSS selectors that take orientation into account now use logical properties. ([#15522](https://github.com/craftcms/cms/pull/15522))

### System
- MySQL mutex locks and PHP session names are now namespaced using the application ID combined with the environment name. ([#15313](https://github.com/craftcms/cms/issues/15313))
- Added support for “City/Town” address locality labels. ([#15585](https://github.com/craftcms/cms/pull/15585))
- `x-craft-preview` and `x-craft-live-preview` params are now hashed, and `craft\web\Request::getIsPreview()` will only return `true` if the param validates. ([#15605](https://github.com/craftcms/cms/discussions/15605))
- Generated URLs no longer include `x-craft-preview` or `x-craft-live-preview` query string params based on the requested URL, if either were set to an unverified string. ([#15605](https://github.com/craftcms/cms/discussions/15605))
- Updated Twig to 3.12. ([#15568](https://github.com/craftcms/cms/discussions/15568))
- Fixed styling issues. ([#15537](https://github.com/craftcms/cms/pull/15537))
