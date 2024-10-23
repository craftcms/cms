# Release Notes for Craft CMS 5.5 (WIP)

### Content Management
- Improved the styling of element cards with thumbnails. ([#15692](https://github.com/craftcms/cms/pull/15692), [#15673](https://github.com/craftcms/cms/issues/15673))
- Elements within element selection inputs now have “Replace” actions.
- Entry types listed within entry indexes now show their icon and color. ([#15922](https://github.com/craftcms/cms/discussions/15922))
- Address index tables can now include “Country” columns.
- Action button cells within editable tables are now center-aligned vertically.
- Dropdown cells within editable tables are no longer center-aligned. ([#15742](https://github.com/craftcms/cms/issues/15742))
- Link fields marked as translatable now swap the selected element with the localized version when their value is getting propagated to a new site for a freshly-created element. ([#15821](https://github.com/craftcms/cms/issues/15821))
- Pressing <kbd>Return</kbd> when an inline-editable field is focused now submits the inline form. (Previously <kbd>Ctrl</kbd>/<kbd>Command</kbd> had to be pressed as well.) ([#15841](https://github.com/craftcms/cms/issues/15841))

### Accessibility
- Improved the control panel for screen readers. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Improved keyboard control. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Improved the color contrast of required field indicators. ([#15665](https://github.com/craftcms/cms/pull/15665))

### Administration
- All relation fields can now be selected as field layouts’ thumbnail providers. ([#15651](https://github.com/craftcms/cms/discussions/15651))
- Added the “Markdown” field layout UI element type. ([#15674](https://github.com/craftcms/cms/pull/15674), [#15664](https://github.com/craftcms/cms/discussions/15664))
- Added the “Language” element condition rule. ([#15952](https://github.com/craftcms/cms/discussions/15952))
- The Sections index table can now be sorted by Name, Handle, and Type. ([#15936](https://github.com/craftcms/cms/pull/15936))
- Sections are no longer required to have unique names. ([#9829](https://github.com/craftcms/cms/discussions/9829))
- Customize Sources modals now display native sources’ handles, when known.
- Removed the “Show the Title field” entry type setting. The “Title” element can now be removed from the field layout instead. ([#15942](https://github.com/craftcms/cms/pull/15942))
- Entry types can now specify a Default Title Format, which will be used even if the Title field is included in the field layout, to generate a default Title value if the field is blank. ([#15942](https://github.com/craftcms/cms/pull/15942))
- It’s now possible to control whether entry types’ Title fields are required. ([#15942](https://github.com/craftcms/cms/pull/15942))
- Added the `camera-retro`, `chevron-down`, `chevron-left`, `chevron-right`, `chevron-up`, `chevrons-down`, `chevrons-left`, `chevrons-right`, `chevrons-up`, `codepen`, `discord`, `dribbble`, `facebook`, `image-landscape`, `image-portrait`, `instagram`, `kickstarter`, `linkedin`, `panorama`, `pen-clip`, `pen-fancy`, `pen-field`, `pen-line`, `pen-nib`, `pen-paintbrush`, `pencil-mechanical`, `photo-film-music`, `photo-film`, `pinterest`, `product-hunt`, `rebel`, `shopify`, `threads`, `tiktok`, `vimeo`, `whatsapp`, `x-twitter`, and `youtube` icons.
- Added `pc/*` commands as an alias of `project-config/*`.
- Added the `resave/all` command.
- Added the `--except`, `--minor-only`, and `--patch-only` options to the `update` command. ([#15829](https://github.com/craftcms/cms/pull/15829))
- Added the `--with-fields` option to all native `resave/*` commands.
- The `fields/merge` and `fields/auto-merge` commands now prompt to resave elements that include relational fields before merging them, and provide a CLI command that should be run on other environments before the changes are deployed to them. ([#15869](https://github.com/craftcms/cms/issues/15869))

### Development
- Added the `encodeUrl()` Twig function. ([#15838](https://github.com/craftcms/cms/issues/15838))
- Added support for passing aliased field handles into element queries’ `select()`/`addSelect()` methods. ([#15827](https://github.com/craftcms/cms/issues/15827))
- Added support for appending subpaths to environment variable names in environmental settings (e.g. `$PRIMARY_SITE_URL/uploads`).

### Extensibility
- Added `craft\base\NestedElementTrait::saveOwnership()`. ([#15894](https://github.com/craftcms/cms/pull/15894))
- Added `craft\base\RequestTrait::getIsWebRequest()`. ([#15690](https://github.com/craftcms/cms/pull/15690))
- Added `craft\console\Controller::output()`. 
- Added `craft\console\controllers\ResaveController::hasTheFields()`.
- Added `craft\elements\db\NestedElementQueryTrait`. ([#15894](https://github.com/craftcms/cms/pull/15894))
- Added `craft\events\ApplyFieldSaveEvent`. ([#15872](https://github.com/craftcms/cms/discussions/15872))
- Added `craft\events\DefineAddressCountriesEvent`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\filters\BasicHttpAuthLogin`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\BasicHttpAuthStatic`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\ConditionalFilterTrait`. ([#15948](https://github.com/craftcms/cms/pull/15948))
- Added `craft\filters\UtilityAccess`.
- Added `craft\helpers\Console::indent()`.
- Added `craft\helpers\Console::indentStr()`.
- Added `craft\helpers\Console::outdent()`.
- Added `craft\helpers\StringHelper::firstLine()`.
- Added `craft\helpers\UrlHelper::encodeUrl()`. ([#15838](https://github.com/craftcms/cms/issues/15838))
- Added `craft\services\Addresses::EVENT_DEFINE_ADDRESS_COUNTRIES`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\services\Addresses::getCountryList()`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\services\Fields::EVENT_BEFORE_APPLY_FIELD_SAVE`. ([#15872](https://github.com/craftcms/cms/discussions/15872))
- Added `craft\services\Users::getMaxUsers()`.
- Added `craft\web\View::registerCpTwigExtension()`.
- Added `craft\web\View::registerSiteTwigExtension()`.
- Added `craft\helpers\Console::$outputCount`.
- Added `craft\helpers\Console::$prependNewline`.
- Added `craft\fieldlayoutelements\Template::$templateMode`. ([#15932](https://github.com/craftcms/cms/pull/15932))
- Added `craft\models\FieldLayout::prependElements()`.
- `craft\helpers\Console::output()` now prepends an indent to each line of the passed-in string, if `indent()` had been called prior.
- Deprecated the `enableBasicHttpAuth` config setting. `craft\filters\BasicHttpAuthLogin` should be used instead. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added the `serializeForm` event to `Craft.ElementEditor`. ([#15794](https://github.com/craftcms/cms/discussions/15794))

### System
- `Location` headers added via `craft\web\Response::redirect()` are now set to encoded URLs. ([#15838](https://github.com/craftcms/cms/issues/15838))
- Fixed a bug where the Recovery Codes slideout content overflowed its container on small screens. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Fixed a bug where entries that were soft-deleted along with their section weren’t getting restored if the section was restored. 
- Fixed a bug where field types weren’t getting a chance to normalize their values when propagated to a new site for a freshly-created element, if they were marked as translatable.
