# Release Notes for Craft CMS 5.1 (WIP)

### Content Management
- Sort options are now sorted alphabetically within element indexes, and custom fields’ options are now listed in a “Fields” group. ([#14725](https://github.com/craftcms/cms/issues/14725))
- Unselected table column options are now sorted alphabetically within element indexes.
- Table views within element index pages are now scrolled directly, so that their horizontal scrollbars are always visible without scrolling to the bottom of the page. ([#14765](https://github.com/craftcms/cms/issues/14765))
- Element tooltips now appear after a half-second delay. ([#14836](https://github.com/craftcms/cms/issues/14836))

### Administration
- Added the `asyncCsrfInputs` config setting. ([#14625](https://github.com/craftcms/cms/pull/14625))
- Added the `safeMode` config setting. ([#14734](https://github.com/craftcms/cms/pull/14734))
- `resave` commands now support an `--if-invalid` option. ([#14731](https://github.com/craftcms/cms/issues/14731))

### Extensibility
- Added `craft\controllers\EditUserTrait`. ([#14789](https://github.com/craftcms/cms/pull/14789))
- Added `craft\controllers\UsersController::EVENT_DEFINE_EDIT_SCREENS`. ([#14789](https://github.com/craftcms/cms/pull/14789))
- Added `craft\events\DefineEditUserScreensEvent`. ([#14789](https://github.com/craftcms/cms/pull/14789))
- Added `craft\web\Request::getBearerToken()`. ([#14784](https://github.com/craftcms/cms/pull/14784))
- Added `craft\db\CoalesceColumnsExpression`.
- Added `craft\db\ExpressionBuilder`.
- Added `craft\db\ExpressionInterface`.
- `craft\base\NameTrait::prepareNamesForSave()` no longer updates the name properties if `fullName`, `firstName`, and `lastName` are already set. ([#14665](https://github.com/craftcms/cms/issues/14665))
- `craft\helpers\Typecast::properties()` now typecasts numeric strings to integers, for `int|string` properties. ([#14618](https://github.com/craftcms/cms/issues/14618))
- Added `Craft.MatrixInput.Entry`. ([#14730](https://github.com/craftcms/cms/pull/14730))

### System
- Batched queue jobs now set their progress based on the total progress across all batches, rather than just the current batch. ([#14817](https://github.com/craftcms/cms/pull/14817))
- Fixed a bug where ordering by a custom field would only partially work, if the custom field was included in multiple field layouts for the resulting elements. ([#14821](https://github.com/craftcms/cms/issues/14821))
