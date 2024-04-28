# Release Notes for Craft CMS 4.9 (WIP)

### Content Management
- It’s now possible to preview revisions. ([#14521](https://github.com/craftcms/cms/discussions/14521))
- Element conditions can now include condition rules for Time fields. ([#14616](https://github.com/craftcms/cms/discussions/14616))
- Sort options are now sorted alphabetically within element indexes, and custom fields’ options are now listed in a “Fields” group. ([#14725](https://github.com/craftcms/cms/issues/14725))
- Unselected table column options are now sorted alphabetically within element indexes. 

### Administration
- Added the `asyncCsrfInputs` config setting. ([#14625](https://github.com/craftcms/cms/pull/14625))
- `resave` commands now support an `--if-invalid` option. ([#14731](https://github.com/craftcms/cms/issues/14731))

### Development
- Added the `safeMode` config setting. ([#14734](https://github.com/craftcms/cms/pull/14734))
- Added the `language` element query param, which filters the resulting elements based on their sites’ languages. ([#14631](https://github.com/craftcms/cms/discussions/14631))
- GraphQL responses now include full exception details, when Dev Mode is enabled or an admin is signed in with the “Show full exception views when Dev Mode is disabled” preference enabled. ([#14527](https://github.com/craftcms/cms/issues/14527))
- `craft\helpers\Html::csrfInput()` and the `csrfInput` Twig function now support passing an `async` key to the `options` array, overriding the default behavior per the `asyncCsrfInputs` config setting. ([#14625](https://github.com/craftcms/cms/pull/14625))

### Extensibility
- Added `craft\services\ProjectConfig::getAppliedChanges()`. ([#14851](https://github.com/craftcms/cms/discussions/14851))
- Added `craft\services\Sites::getSitesByLanguage()`.
- Added `craft\web\ErrorHandler::exceptionAsArray()`.
- Added `craft\web\ErrorHandler::showExceptionDetails()`.
- Added `craft\web\Request::getBearerToken()`. ([#14784](https://github.com/craftcms/cms/pull/14784))
- `craft\base\NameTrait::prepareNamesForSave()` no longer updates the name properties if `fullName`, `firstName`, and `lastName` are already set. ([#14665](https://github.com/craftcms/cms/issues/14665))

### System
- Batched queue jobs now set their progress based on the total progress across all batches, rather than just the current batch. ([#14817](https://github.com/craftcms/cms/pull/14817))
- Craft now calls `setlocale()` based on the target language, so that `SORT_LOCALE_STRING` behaves as expected. ([#14509](https://github.com/craftcms/cms/issues/14509), [#14513](https://github.com/craftcms/cms/pull/14513))
- Improved the performance of scalar element queries like `count()`.
- Fixed a bug where `craft\elements\db\ElementQuery::count()` could return the wrong number if the query had a cached result, with `offset` or `limit` params.
- Console requests no longer filter out info logs. ([#14280](https://github.com/craftcms/cms/issues/14280), [#14434](https://github.com/craftcms/cms/pull/14434))
- Fixed a styling issue with Categories and Entries fields when “Maintain Hierarchy” was enabled.
