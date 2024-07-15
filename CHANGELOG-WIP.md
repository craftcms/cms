# Release Notes for Craft CMS 5.3 (WIP)

### Content Management
- Added the “Link” field type, which replaces “URL”, and can store URLs, `mailto` and `tel` URIs, and entry/asset/category references. ([#15251](https://github.com/craftcms/cms/pull/15251)) 
- Entry and category conditions now have a “Has Descendants” rule. ([#15276](https://github.com/craftcms/cms/discussions/15276))
- “Replace file” actions now display success notices on complete. ([#15217](https://github.com/craftcms/cms/issues/15217))
- Double-clicking on folders within asset indexes and folder selection modals now navigates the index/modal into the folder. ([#15238](https://github.com/craftcms/cms/discussions/15238))
- Matrix fields now show validation errors when nested entries don’t validate. ([#15161](https://github.com/craftcms/cms/issues/15161), [#15165](https://github.com/craftcms/cms/pull/15165))
- Matrix fields set to inline-editable blocks view now support selecting all blocks by pressing <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>A</kbd> when a checkbox is focused. ([#15326](https://github.com/craftcms/cms/issues/15326)) 
- Users’ Permissions, Preferences, and Password & Verification screens now have “Save and continue editing” actions, as well as support for <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>S</kbd> keyboard shortcuts.

### Accessibility
- Improved the accessibility of two-step verification setup. ([#15229](https://github.com/craftcms/cms/pull/15229))
- The notification heading is no longer read to screen readers when no notifications are active. ([#15294](https://github.com/craftcms/cms/pull/15294)) 
- The login modal that appears once a user’s session has ended now has a `lang` attribute, in case it differs from the user’s preferred language.

### Administration
- Icon fields now have an “Include Pro icons” setting, which determines whether Font Awesome Pro icon should be selectable. ([#15242](https://github.com/craftcms/cms/issues/15242))
- New sites’ Base URL settings now default to an environment variable name based on the site name. ([#15347](https://github.com/craftcms/cms/pull/15347))
- Craft now warns against using the `@web` alias for URL settings, regardless of whether it was explicitly defined. ([#15347](https://github.com/craftcms/cms/pull/15347))

### Development
- GraphQL mutations for saving drafts of nested entries are now named with `Field` after the Matrix/CKEditor field handle. ([#15269](https://github.com/craftcms/cms/issues/15269))

### Extensibility
- Added `craft\base\ElementInterface::addInvalidNestedElementIds()`.
- Added `craft\base\ElementInterface::getInvalidNestedElementIds()`.
- Added `craft\base\FieldLayoutComponent::EVENT_DEFINE_SHOW_IN_FORM`. ([#15260](https://github.com/craftcms/cms/issues/15260))
- Added `craft\events\DefineShowFieldLayoutComponentInFormEvent`. ([#15260](https://github.com/craftcms/cms/issues/15260))
- Added `craft\fields\Link`.
- Added `craft\fields\data\LinkData`.
- Added `craft\fields\linktypes\Asset`.
- Added `craft\fields\linktypes\BaseElementLinkType`.
- Added `craft\fields\linktypes\BaseLinkType`.
- Added `craft\fields\linktypes\BaseTextLinkType`.
- Added `craft\fields\linktypes\Category`.
- Added `craft\fields\linktypes\Email`.
- Added `craft\fields\linktypes\Phone`.
- Added `craft\fields\linktypes\Url`.
- Deprecated `craft\fields\Url`, which is now an alias for `craft\fields\Link`.
- Added `Craft.EnvVarGenerator`.
- Added `Craft.endsWith()`.
- Added `Craft.removeLeft()`.
- Added `Craft.removeRight()`.
- Added `Craft.ui.addAttributes()`.
- `Craft.ElementEditor` now triggers a `checkActivity` event each time author activity is fetched. ([#15237](https://github.com/craftcms/cms/discussions/15237))
- `Craft.ensureEndsWith()` now has a `caseInsensitive` argument.
- `Craft.ensureStartsWith()` now has a `caseInsensitive` argument.
- `Craft.startsWith()` is no longer deprecated, and now has a `caseInsensitive` argument.
- Added `Garnish.once()`, for handling a class-level event only once.
- Checkbox selects now support passing a `targetPrefix`.

### System
- The control panel now displays Ajax response-defined error messages when provided, rather than a generic “server error” message. ([#15292](https://github.com/craftcms/cms/issues/15292))
