# Release Notes for Craft CMS 4.9 (WIP)

### Content Management
- It’s now possible to preview revisions. ([#14521](https://github.com/craftcms/cms/discussions/14521))

### Development
- GraphQL responses now include full exception details, when Dev Mode is enabled or an admin is signed in with the “Show full exception views when Dev Mode is disabled” preference enabled. ([#14527](https://github.com/craftcms/cms/issues/14527))

### Extensibility
- Added `craft\web\ErrorHandler::exceptionAsArray()`.
- Added `craft\web\ErrorHandler::showExceptionDetails()`.
