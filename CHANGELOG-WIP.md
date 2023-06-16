# Release Notes for Craft CMS 5.0 (WIP)

### Content Management

### Accessibility
- Improved source item navigation for screen readers. ([#12054](https://github.com/craftcms/cms/pull/12054))
- Content tab menus are now implemented as disclosure menus. ([#12963](https://github.com/craftcms/cms/pull/12963))

### Administration
- Added support for defining custom locale aliases, via a new `localeAliases` config setting. ([#12705](https://github.com/craftcms/cms/pull/12705))

### Development

### Extensibility
- The control panel now defines new CSS variables for orange, green, and violet colors. Existing color palette CSS variables have been updated to match the Tailwind 3 color palette.
- All core element query param methods now return `static` instead of `self`. ([#11868](https://github.com/craftcms/cms/pull/11868))
- Selectize menus no longer apply special styling to options with the value `new`. The `_includes/forms/selectize.twig` control panel template should be used instead (or `craft\helpers\Cp::selectizeHtml()`/`selectizeFieldHtml()`), which will append an styled “Add” option when `addOptionFn` and `addOptionLabel` settings are passed. ([#11946](https://github.com/craftcms/cms/issues/11946))
- The `assets/move-asset` and `assets/move-folder` actions no longer include `success` keys in responses. ([#12159](https://github.com/craftcms/cms/pull/12159))
- The `assets/upload` controller action now includes `errors` object in failure responses. ([#12159](https://github.com/craftcms/cms/pull/12159))
- Added `craft\i18n\Locale::$aliasOf`.
- Added `craft\i18n\Locale::setDisplayName()`.
- Renamed `craft\web\CpScreenResponseBehavior::$additionalButtons()` and `additionalButtons()` to `$additionalButtonsHtml` and `additionalButtonsHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$content()` and `content()` to `$contentHtml` and `contentHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$contextMenu()` and `contextMenu()` to `$contextMenuHtml` and `contextMenuHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$notice()` and `notice()` to `$noticeHtml` and `noticeHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$pageSidebar()` and `pageSidebar()` to `$pageSidebarHtml` and `pageSidebarHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$sidebar()` and `sidebar()` to `$metaSidebarHtml` and `metaSidebarHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- `craft\i18n\I18N::getPrimarySiteLocale()` is now deprecated. `craft\models\Site::getLocale()` should be used instead.
- `craft\i18n\I18N::getPrimarySiteLocaleId()` is now deprecated. `craft\models\Site::$language` should be used instead.

### System
- Craft now requires PHP 8.1 or later.
- The `defaultTemplateExtensions` config setting now lists `twig` before `html` by default. ([#11809](https://github.com/craftcms/cms/discussions/11809))
