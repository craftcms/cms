# Release Notes for Craft CMS 5.0 (WIP)

### Content Management

### Accessibility

### Administration

### Development

### Extensibility
- The control panel now defines new CSS variables for orange, green, and violet colors. Existing color palette CSS variables have been updated to match the Tailwind 3 color palette.
- All core element query param methods now return `static` instead of `self`. ([#11868](https://github.com/craftcms/cms/pull/11868))
- Selectize menus no longer apply special styling to options with the value `new`. The `_includes/forms/selectize.twig` control panel template should be used instead (or `craft\helpers\Cp::selectizeHtml()`/`selectizeFieldHtml()`), which will append an styled “Add” option when `addOptionFn` and `addOptionLabel` settings are passed. ([#11946](https://github.com/craftcms/cms/issues/11946))

### System
- Craft now requires PHP 8.1 or later.
- The `defaultTemplateExtensions` config setting now lists `twig` before `html` by default. ([#11809](https://github.com/craftcms/cms/discussions/11809))
