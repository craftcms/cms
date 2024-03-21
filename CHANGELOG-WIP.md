# Release Notes for Craft CMS 4.9 (WIP)

### Content Management
- It’s now possible to preview revisions. ([#14521](https://github.com/craftcms/cms/discussions/14521))
- Element conditions can now include condition rules for Time fields. ([#14616](https://github.com/craftcms/cms/discussions/14616))

### Development
- Added the `language` element query param, which filters the resulting elements based on their sites’ languages. ([#14631](https://github.com/craftcms/cms/discussions/14631))
- GraphQL responses now include full exception details, when Dev Mode is enabled or an admin is signed in with the “Show full exception views when Dev Mode is disabled” preference enabled. ([#14527](https://github.com/craftcms/cms/issues/14527))

### Extensibility
- Added `craft\services\Sites::getSitesByLanguage()`.
- Added `craft\web\ErrorHandler::exceptionAsArray()`.
- Added `craft\web\ErrorHandler::showExceptionDetails()`.

### System
- Craft now calls `setlocale()` based on the target language, so that `SORT_LOCALE_STRING` behaves as expected. ([#14509](https://github.com/craftcms/cms/issues/14509), [#14513](https://github.com/craftcms/cms/pull/14513))
- Improved the performance of scalar element queries like `count()`.
- Fixed a bug where `craft\elements\db\ElementQuery::count()` could return the wrong number if the query had a cached result, with `offset` or `limit` params.
- Console requests no longer filter out info logs. ([#14280](https://github.com/craftcms/cms/issues/14280), [#14434](https://github.com/craftcms/cms/pull/14434))
