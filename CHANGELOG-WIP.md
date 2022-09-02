# Release Notes for Craft CMS 5.0 (WIP)

### Changed
- Craft now requires PHP 8.1 or later.
- The `defaultTemplateExtensions` config setting now lists `twig` before `html` by default. ([#11809](https://github.com/craftcms/cms/discussions/11809))
- The control panel now defines new CSS variables for orange, green, and violet colors. Existing color palette CSS variables have been updated to match the Tailwind 3 color palette.
- All core element query param methods now return `static` instead of `self`. ([#11868](https://github.com/craftcms/cms/pull/11868))
