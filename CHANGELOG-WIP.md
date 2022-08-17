# Release Notes for Craft CMS 4.3 (WIP)

### Added
- Added `craft\elements\actions\Restore::$restorableElementsOnly`.
- Added `craft\events\CreateTwigEvent`.
- Added `craft\events\DefineAddressFieldLabelEvent`.
- Added `craft\events\DefineAddressFieldsEvent`.
- Added `craft\i18n\FormatConverter::convertDatePhpToHuman()`. ([#10546](https://github.com/craftcms/cms/pull/10546))
- Added `craft\i18n\Locale::FORMAT_HUMAN`.
- Added `craft\services\Addresses::EVENT_DEFINE_FIELD_LABEL`. ([#11788](https://github.com/craftcms/cms/discussions/11788))
- Added `craft\services\Addresses::EVENT_DEFINE_USED_FIELDS`. ([#11788](https://github.com/craftcms/cms/discussions/11788))
- Added `craft\services\Addresses::EVENT_DEFINE_USED_SUBDIVISION_FIELDS`. ([#11788](https://github.com/craftcms/cms/discussions/11788))
- Added `craft\services\Addresses::getFieldLabel()`.
- Added `craft\services\Addresses::getUsedFields()`.
- Added `craft\services\Addresses::getUsedSubdivisionFields()`.
- Added `craft\web\Controller::getCurrentUser()`. ([#11754](https://github.com/craftcms/cms/pull/11754))
- Added `craft\web\View::EVENT_AFTER_CREATE_TWIG`. ([#11774](https://github.com/craftcms/cms/pull/11774))
- Added the `Craft.useMobileStyles()` JavaScript method. ([#11636](https://github.com/craftcms/cms/pull/11636))

### Changed
- Improved the control panel accessibility. ([#10546](https://github.com/craftcms/cms/pull/10546), [#11534](https://github.com/craftcms/cms/pull/11534), [#11565](https://github.com/craftcms/cms/pull/11565), [#11578](https://github.com/craftcms/cms/pull/11578), [#11589](https://github.com/craftcms/cms/pull/11589), [#11604](https://github.com/craftcms/cms/pull/11604), [#11610](https://github.com/craftcms/cms/pull/11610), [#11611](https://github.com/craftcms/cms/pull/11611), [#11613](https://github.com/craftcms/cms/pull/11613), [#11636](https://github.com/craftcms/cms/pull/11636), [#11662](https://github.com/craftcms/cms/pull/11662)[#11703](https://github.com/craftcms/cms/pull/11703), [#11727](https://github.com/craftcms/cms/pull/11727), [#11763](https://github.com/craftcms/cms/pull/11763), [#11768](https://github.com/craftcms/cms/pull/11768), [#11775](https://github.com/craftcms/cms/pull/11775))
- It’s now possible to restore assets that were deleted programmatically, with `craft\elements\Asset::$keepFile` set to `true`. ([#11761](https://github.com/craftcms/cms/issues/11761))
- `users/session-info` responses now include a `csrfTokenName` key. ([#11706](https://github.com/craftcms/cms/pull/11706), [#11767](https://github.com/craftcms/cms/pull/11767))
- `craft\helpers\Component::iconSvg()` now namespaces the SVG contents, and adds `aria-hidden="true"`. ([#11703](https://github.com/craftcms/cms/pull/11703))
- `craft\services\Search::EVENT_BEFORE_INDEX_KEYWORDS` is now cancellable by setting `$event->isValid` to `false`. ([#11705](https://github.com/craftcms/cms/discussions/11705))
- `checkboxSelect` inputs without `showAllOption: true` now post an empty value if no options were selected. ([#11748](https://github.com/craftcms/cms/issues/11748))

### Deprecated
- Deprecated `craft\elements\Address::addressAttributeLabel()`. `craft\services\Addresses::getFieldLabel()` should be used instead.
