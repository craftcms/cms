# Release Notes for Craft CMS 5.1 (WIP)

### Content Management
- Sort options are now sorted alphabetically within element indexes, and custom fields’ options are now listed in a “Fields” group. ([#14725](https://github.com/craftcms/cms/issues/14725))
- Unselected table column options are now sorted alphabetically within element indexes.
- Table views within element index pages are now scrolled directly, so that their horizontal scrollbars are always visible without scrolling to the bottom of the page. ([#14765](https://github.com/craftcms/cms/issues/14765))

### Administration
- `resave` commands now support an `--if-invalid` option. ([#14731](https://github.com/craftcms/cms/issues/14731))

### Extensibility
- `craft\base\NameTrait::prepareNamesForSave()` no longer updates the name properties if `fullName`, `firstName`, and `lastName` are already set. ([#14665](https://github.com/craftcms/cms/issues/14665))
- Added `Craft.MatrixInput.Entry`. ([#14730](https://github.com/craftcms/cms/pull/14730))
