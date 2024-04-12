# Release Notes for Craft CMS 5.1 (WIP)

### Content Management
- Sort options are now sorted alphabetically within element indexes, and custom fields’ options are now listed in a “Fields” group. ([#14725](https://github.com/craftcms/cms/issues/14725))
- Unselected table column options are now sorted alphabetically within element indexes.

### User Management
- Team edition users are no longer required to be admins.
- Added the “User Permissions” settings page for managing the permissions of non-admin Team edition users. ([#14768](https://github.com/craftcms/cms/discussions/14768))
- Table views within element index pages are now scrolled directly, so that their horizontal scrollbars are always visible without scrolling to the bottom of the page. ([#14765](https://github.com/craftcms/cms/issues/14765))

### Administration
- `resave` commands now support an `--if-invalid` option. ([#14731](https://github.com/craftcms/cms/issues/14731))

### Extensibility
- Added `craft\controllers\EditUserTrait`. ([#14789](https://github.com/craftcms/cms/pull/14789))
- Added `craft\controllers\UsersController::EVENT_DEFINE_EDIT_SCREENS`. ([#14789](https://github.com/craftcms/cms/pull/14789))
- Added `craft\events\DefineEditUserScreensEvent`. ([#14789](https://github.com/craftcms/cms/pull/14789))
- Added `craft\web\Request::getBearerToken()`. ([#14784](https://github.com/craftcms/cms/pull/14784))
- `craft\base\NameTrait::prepareNamesForSave()` no longer updates the name properties if `fullName`, `firstName`, and `lastName` are already set. ([#14665](https://github.com/craftcms/cms/issues/14665))
- Added `Craft.MatrixInput.Entry`. ([#14730](https://github.com/craftcms/cms/pull/14730))
