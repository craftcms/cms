# Release Notes for Craft CMS 5

## 5.5.10 - 2025-01-14

- Fixed a bug where the control panel could display a notice about the Craft CMS license belonging to a different domain, even when accessing the control panel from the correct domain. ([#16396](https://github.com/craftcms/cms/issues/16396))
- Fixed a bug where Unicode special characters weren’t getting stripped out of search keywords. ([#16430](https://github.com/craftcms/cms/issues/16430))
- Fixed an error that could occur when setting `relatedTo*` GraphQL arguments to `null`. ([#16431](https://github.com/craftcms/cms/issues/16431))
- Fixed a bug where field layout elements’ action menus could have an empty action group.
- Fixed a bug where Single section entries could be duplicated after running the `entry-types/merge` command. ([#16394](https://github.com/craftcms/cms/issues/16394))
- Fixed a styling bug with the system message modal. ([#16410](https://github.com/craftcms/cms/issues/16410))
- Fixed a bug where relational fields could eager-load elements from a different instance of the same field, if one of the instances had no relations. ([#16191](https://github.com/craftcms/cms/issues/16191))
- Fixed a bug where the `utils/prune-revisions` command was deleting nested entry revisions.

## 5.5.9 - 2025-01-06

- Fixed a bug where custom fields could cause validation errors when running the `users/create` command.
- Fixed a bug where deleting a volume folder wasn’t fully deleting asset data in descendant folders.
- Fixed a bug where `ancestors`, `children`, `descendants`, and `parent` eager-loading wasn’t working on some environments. ([#16381](https://github.com/craftcms/cms/issues/16381), [#16382](https://github.com/craftcms/cms/issues/16382))
- Fixed a JavaScript error that could occur if there was a problem applying changes to field layout elements. ([#16380](https://github.com/craftcms/cms/issues/16380))
- Fixed a bug where field layout designers were validating field names, handles, and instructions, even if they weren’t overridden within the field instance. ([#16380](https://github.com/craftcms/cms/issues/16380))
- Fixed an error that occurred when upgrading to Craft 5. ([#16383](https://github.com/craftcms/cms/issues/16383))
- Fixed a bug where “Full Name” could appear twice in the user card attributes list. ([#16358](https://github.com/craftcms/cms/issues/16358))
- Fixed a bug where multi-site element queries could return an incorrect number of results if the `search` param was used in conjunction with `offset` or `limit`. ([#16183](https://github.com/craftcms/cms/issues/16183))

## 5.5.8 - 2025-01-02

- Fixed a bug where custom fields were getting included in rendered field layout forms, even if their `getInputHtml()` method returned an empty string.
- Fixed a bug where the password input on the Set Password page wasn’t including the “Show” button.
- Fixed a SQL error that could occur if an element was saved with a title longer than 255 characters.
- Fixed a bug where some UI messages began with a lowercase letter in some languages. ([#16354](https://github.com/craftcms/cms/issues/16354))
- Fixed errors that could occur when working with field layouts for element types that are no longer installed. ([#16352](https://github.com/craftcms/cms/issues/16352))
- Fixed an error that could occur when creating nested entries within Matrix fields. ([#16331](https://github.com/craftcms/cms/issues/16331))
- Fixed a bug where element index View menus could include a “Use defaults” button when no view customizations had been made.
- Fixed a bug where new entries’ slugs weren’t getting propagated to other sites, if their entry type had a dynamic title format. ([#16347](https://github.com/craftcms/cms/issues/16347))
- Fixed a bug where address cards were only showing the first two lines of the address. ([#16353](https://github.com/craftcms/cms/issues/16353))
- Fixed a bug where `@transform` GraphQL directives weren’t always working on Assets fields with overridden handles. ([#15718](https://github.com/craftcms/cms/issues/15718))
- Fixed an error that occurred when adding “Full Name” to user cards. ([#16358](https://github.com/craftcms/cms/issues/16358))
- Fixed an error that could occur if `craft\base\NestedElementTrait::getOwner()` or `getPrimaryOwner()` were called on a nested element whose owner didn’t exist in the same site. ([#16359](https://github.com/craftcms/cms/issues/16359))
- Fixed a styling issue. ([#16342](https://github.com/craftcms/cms/issues/16342))
- Fixed an RCE vulnerability.

## 5.5.7 - 2024-12-17

- Fixed a bug where elements’ `getPrev()` and `getNext()` methods could cause duplicate queries. ([#16329](https://github.com/craftcms/cms/discussions/16329))
- Fixed a bug where assets that were shorter than the preview thumb container weren’t getting vertically centered within it.
- Fixed a bug where it was possible to set a focal point on SVGs, even though focal points on SVGs aren’t supported. ([#16258](https://github.com/craftcms/cms/issues/16258))
- Fixed a bug where `ancestors`, `children`, `descendants`, and `parent` eager-loading wasn’t working for previewed elements. ([#16327](https://github.com/craftcms/cms/issues/16327))
- Fixed a bug where field conditions weren’t taking effect within Matrix fields set to inline-editable blocks mode, if the owner element didn’t support drafts. ([#16315](https://github.com/craftcms/cms/pull/16315))
- Fixed a bug where Matrix fields’ entry types weren’t maintaining their original block type order when upgrading to Craft 5. ([#16314](https://github.com/craftcms/cms/issues/16314))
- Fixed a bug where element card labels were getting cut off when wrapped. ([#16325](https://github.com/craftcms/cms/issues/16325))
- Fixed a PHP error that could occur when eager-loading `owner` or `primaryOwner` on nested elements. ([#16339](https://github.com/craftcms/cms/issues/16339))

## 5.5.6.1 - 2024-12-11

- Fixed a bug where Tags fields had “Replace” actions. ([#16310](https://github.com/craftcms/cms/issues/16310))
- Fixed styling issues. ([#16298](https://github.com/craftcms/cms/issues/16298), [#16312](https://github.com/craftcms/cms/issues/16312))

## 5.5.6 - 2024-12-10

- Fixed a bug where some blank user group and entry type values weren’t getting omitted from project config data. ([#16272](https://github.com/craftcms/cms/pull/16272), [#16273](https://github.com/craftcms/cms/pull/16273))
- Fixed a bug where pressing <kbd>Return</kbd> when a color text input within an editable table was focused was submitting the form rather than moving focus to the next row.
- Fixed a JavaScript error that occurred on the Plugins index page, if there were any missing plugins associated with the Craft CMS license and no plugins were Composer-installed yet.
- Fixed a bug where custom fields would stay visible within Field Layout Designer field libraries when they didn’t match the search criteria, if they had previously been dragged. ([#16277](https://github.com/craftcms/cms/issues/16277))
- Fixed a bug where new, unsaved nested addresses and entries could cause validation errors when saving the owner element. ([#16282](https://github.com/craftcms/cms/issues/16282))
- Fixed a bug where spaces in phone number values within Link fields were getting replaced with `+` characters rather than `-`. ([#16300](https://github.com/craftcms/cms/issues/16300))
- Fixed a bug where Link fields weren’t responsive for small container sizes.

## 5.5.5 - 2024-12-03

- Fixed a bug where asset, category, and entry sources defined by the `EVENT_REGISTER_SOURCES` event didn’t have any custom fields available to them, unless the `EVENT_REGISTER_FIELD_LAYOUTS` event was also used to define the available field layouts for the event-defined source. ([#16256](https://github.com/craftcms/cms/discussions/16256))
- Fixed a bug where Link fields were getting `string` types in `CustomFieldBehavior` rather than `craft\fields\data\LinkData`.
- Fixed a JavaScript error that could occur when creating new nested elements. ([#16262](https://github.com/craftcms/cms/issues/16262))

## 5.5.4 - 2024-12-02

- Reduced the likelihood of a deadlock error occurring when updating search indexes. ([#15221](https://github.com/craftcms/cms/issues/15221))
- The PHP Info utility is no longer shown in environments where the `phpinfo()` function is disabled. ([#16229](https://github.com/craftcms/cms/pull/16229))
- “View” buttons within element indexes are now disabled when the selected view mode has no applicable settings. ([#16242](https://github.com/craftcms/cms/pull/16242))
- Fixed an error that could occur when duplicating an element with an Assets field that had a dynamic subpath. ([#16214](https://github.com/craftcms/cms/issues/16214))
- Fixed a bug where renaming asset folders could move them to the webroot on Windows. ([#16215](https://github.com/craftcms/cms/issues/16215))
- Fixed a bug where utilities’ `isSelectable()` methods weren’t being respected.
- Fixed an exception that could be thrown when displaying entry indexes, if any `EVENT_INIT` or `EVENT_DEFINE_BEHAVIORS` entry event handlers were calling `getType()` on the entry. ([#16254](https://github.com/craftcms/cms/issues/16254))
- Fixed a bug where element slideouts had Save buttons even if the user didn’t have permission to save the element. ([#16205](https://github.com/craftcms/cms/pull/16205))
- Fixed a bug where pagination wasn’t working properly on the Entry Types index page when searching. ([#16204](https://github.com/craftcms/cms/issues/16204))
- Fixed an error that could occur when saving an element with an invalid Link field value. ([#16212](https://github.com/craftcms/cms/issues/16212))
- Fixed a bug where sortable checkbox selects were displaying menu buttons even when only one option was selected. ([#16213](https://github.com/craftcms/cms/issues/16213))
- Fixed a bug where it wasn’t possible to sort embedded element indexes by custom fields.
- Fixed a bug where changes to nested elements weren’t getting saved to a draft of the parent, if the element editor was triggered via the “Edit” action menu item. ([#16251](https://github.com/craftcms/cms/issues/16251))
- Fixed a bug where all elements would get soft-deleted when deleting a section on PostgreSQL. ([#16230](https://github.com/craftcms/cms/issues/16230))
- Fixed a bug where entry cards could contain two entry type icons if the “Entry Type” attribute was included in the card view designer. ([#16234](https://github.com/craftcms/cms/issues/16234))
- Fixed a bug where address error summaries weren’t linking to Latitude/Longitude fields properly. ([#16244](https://github.com/craftcms/cms/issues/16244))
- Fixed a styling issue.

## 5.5.3 - 2024-11-22

- Element indexes now sort by ID by default, for sources that don’t define a default sort option.
- Fixed a bug where element indexes were sorting by the first sortable attribute alphabetically by default, rather than the first sortable attribute defined by the element type.
- Fixed a bug where `craft\events\ApplyFieldSaveEvent::$field` wasn’t being set consistently by `craft\services\Fields::EVENT_BEFORE_APPLY_FIELD_SAVE`. ([#16156](https://github.com/craftcms/cms/issues/16156))
- Fixed a bug where the address field layout’s project config data wasn’t getting recreated when running `project-config/rebuild`. ([#16189](https://github.com/craftcms/cms/issues/16189))
- Fixed an error that could occur when creating a nested element. ([#16162](https://github.com/craftcms/cms/pull/16162))
- Fixed a bug where custom fields weren’t being displayed at 25% width when they should have. ([#16165](https://github.com/craftcms/cms/pull/16170))
- Fixed a bug where the “Default Table Columns” element source settings could contain duplicate checkbox options. ([#16177](https://github.com/craftcms/cms/issues/16177))
- Fixed a JavaScript error that broke nested element creation in global sets. ([#16182](https://github.com/craftcms/cms/issues/16182))
- Fixed a bug where Number fields weren’t rounding existing values based on the precision specified by the Decimals setting. ([#16181](https://github.com/craftcms/cms/issues/16181))

## 5.5.2 - 2024-11-19 [CRITICAL]

- Fixed an error that could occur if an invalid folder ID was passed to `craft\services\Assets::deleteFoldersByIds()`. ([#16147](https://github.com/craftcms/cms/pull/16147))
- Fixed a SQL error that occurred when creating a new Single section. ([#16145](https://github.com/craftcms/cms/issues/16145))
- Fixed an error that occurred when running the `resave/all` command, if any of the options passed weren’t supported by other `resave/*` commands. ([#16148](https://github.com/craftcms/cms/pull/16148))
- Fixed an error that occurred when restoring a soft-deleted custom field. ([#16150](https://github.com/craftcms/cms/issues/16150))
- Fixed an RCE vulnerability.

## 5.5.1.1 - 2024-11-18

- Fixed a PHP error. ([#16142](https://github.com/craftcms/cms/issues/16142))

## 5.5.1 - 2024-11-18

- The `entry-types/merge` command can now be run non-interactively. ([#16135](https://github.com/craftcms/cms/pull/16135))
- Fixed a JavaScript error that could occur on element edit pages. ([#16055](https://github.com/craftcms/cms/issues/16055))
- Fixed a Twig deprecation error. ([#16107](https://github.com/craftcms/cms/issues/16107))
- Fixed a bug where `craft\services\Structures::fillGapsInElements()` wasn’t working properly if the elements weren’t passed in hierarchical order. ([#16085](https://github.com/craftcms/cms/issues/16085))
- Fixed a bug where `craft\helpers\Console::table()` wasn’t handling multi-byte characters and ANSI-formatted strings properly.
- Fixed a bug where entries could appear to retain old field values when switching entry types, even if the new type’s fields weret’t compatible with the original type’s fields. ([#16056](https://github.com/craftcms/cms/issues/16056))
- Fixed a bug where Link field query params weren’t working for elements that hadn’t been saved since updating to Craft 5.5.0+. ([#16113](https://github.com/craftcms/cms/issues/16113))
- Fixed a bug where Live Preview wasn’t reloading after reordering nested entries or addresses. ([#16122](https://github.com/craftcms/cms/issues/16122))
- Fixed a JavaScript error that could occur when reordering structured elements within an embedded element index. ([#16103](https://github.com/craftcms/cms/issues/16103))
- Fixed a bug where changes to nested entries/addresses in card view were getting published immediately on save, if the parent element was a draft.
- Fixed a bug where element cards could bleed out of their containers. ([#16112](https://github.com/craftcms/cms/issues/16112))
- Fixed a bug where nested entries could get deleted when restoring revisions. ([#16116](https://github.com/craftcms/cms/issues/16116))
- Fixed a bug where reordering nested addresses or entries would cause any provisional drafts to be fully created. ([#16094](https://github.com/craftcms/cms/issues/16094))
- Fixed a PHP error that could occur on element indexes. ([craftcms/commerce#3774](https://github.com/craftcms/commerce/issues/3774))
- Fixed a bug where an existing Single section entry could be deleted when the section’s Entry Types setting changed. ([#16102](https://github.com/craftcms/cms/pull/16102))
- Fixed a bug where Single section entries’ content could be lost after running the `entry-types/merge` command. ([#16102](https://github.com/craftcms/cms/pull/16102))
- Fixed a bug where Single section entries could be duplicated after running the `entry-types/merge` command. ([#16087](https://github.com/craftcms/cms/issues/16087), [#16102](https://github.com/craftcms/cms/pull/16102))

## 5.5.0.1 - 2024-11-13

- Fixed an error that prevented custom fields from loading on the Settings → Fields.

## 5.5.0 - 2024-11-12

### Content Management
- When saving a nested element within a Matrix/Addresses field in card view, the changes are now saved to a draft of the owner element, rather than published immediately. ([#16002](https://github.com/craftcms/cms/pull/16002))
- Nested element cards now show status indicators if they are new or contain unpublished changes. ([#16002](https://github.com/craftcms/cms/pull/16002))
- Improved the styling of element cards with thumbnails. ([#15692](https://github.com/craftcms/cms/pull/15692), [#15673](https://github.com/craftcms/cms/issues/15673))
- Elements within element selection inputs now have “Replace” actions.
- Entry types listed within entry indexes now show their icon and color. ([#15922](https://github.com/craftcms/cms/discussions/15922))
- Address index tables can now include “Country” columns.
- Action button cells within editable tables are now center-aligned vertically.
- Dropdown cells within editable tables are no longer center-aligned. ([#15742](https://github.com/craftcms/cms/issues/15742))
- Link fields marked as translatable now swap the selected element with the localized version when their value is getting propagated to a new site for a freshly-created element. ([#15821](https://github.com/craftcms/cms/issues/15821))
- Pressing <kbd>Return</kbd> when an inline-editable field is focused now submits the inline form. (Previously <kbd>Ctrl</kbd>/<kbd>Command</kbd> had to be pressed as well.) ([#15841](https://github.com/craftcms/cms/issues/15841))
- Improved the styling of element edit page headers, for elements with long titles. ([#16001](https://github.com/craftcms/cms/pull/16001))
- It’s now possible to preview audio and video assets from Edit Asset screens. ([#16021](https://github.com/craftcms/cms/pull/16021))
- Sidebar visibility states are now stored in a browser cookie, so they are retained between page/slideout loads. ([#16025](https://github.com/craftcms/cms/pull/16025), [#15982](https://github.com/craftcms/cms/discussions/15982))
- “Related To” condition rules now show the site menu in element selector modals. ([#16036](https://github.com/craftcms/cms/pull/16036))

### Accessibility
- Improved the control panel for screen readers. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Improved keyboard control. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Improved the color contrast of required field indicators. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Improved the accessibility of text inputs for screen readers.
- It’s now possible to move an image’s focal point without dragging it. ([#15904](https://github.com/craftcms/cms/pull/15904))
- Improved field status indicators for screen readers. ([#16081](https://github.com/craftcms/cms/pull/16081))

### Administration
- Added the “Range” field type. ([#15972](https://github.com/craftcms/cms/pull/15972))
- Added the “Allow custom options” setting to Checkboxes and Radio Buttons fields.
- Added the “Show the ‘Label’ field” and “Show the ‘Open in a new tab’ field” settings to Link fields. ([#15983](https://github.com/craftcms/cms/pull/15983))
- Link fields’ Allowed Link Types settings are now sortable. ([#15963](https://github.com/craftcms/cms/pull/15963))
- All relation fields can now be selected as field layouts’ thumbnail providers. ([#15651](https://github.com/craftcms/cms/discussions/15651))
- It’s now possible to include element attributes in card views, alongside custom fields, via new “Card Attributes” configurators. ([#15283](https://github.com/craftcms/cms/pull/15283))
- Added the “Markdown” field layout UI element type. ([#15674](https://github.com/craftcms/cms/pull/15674), [#15664](https://github.com/craftcms/cms/discussions/15664))
- Added the “Language” element condition rule. ([#15952](https://github.com/craftcms/cms/discussions/15952))
- The Sections index table can now be sorted by Name, Handle, and Type. ([#15936](https://github.com/craftcms/cms/pull/15936))
- Sections are no longer required to have unique names. ([#9829](https://github.com/craftcms/cms/discussions/9829))
- Customize Sources modals now display native sources’ handles, when known.
- Removed the “Show the Title field” entry type setting. The “Title” element can now be removed from the field layout instead. ([#15942](https://github.com/craftcms/cms/pull/15942))
- Entry types can now specify a Default Title Format, which will be used even if the Title field is included in the field layout, to generate a default Title value if the field is blank. ([#15942](https://github.com/craftcms/cms/pull/15942))
- It’s now possible to control whether entry types’ Title fields are required. ([#15942](https://github.com/craftcms/cms/pull/15942))
- Added the “Step Size” Number field setting.
- `icon` is now a reserved field handle. ([#16077](https://github.com/craftcms/cms/issues/16077))
- Added the “Default View Mode” element source setting. ([#15824](https://github.com/craftcms/cms/pull/15824))
- User impersonation now requires an elevated session. ([#16052](https://github.com/craftcms/cms/pull/16052))
- Elevated session prompts now authenticate against the original user, when impersonating a user. ([#16052](https://github.com/craftcms/cms/pull/16052))
- Added several new icons.
- Added `pc/*` commands as an alias of `project-config/*`.
- Added the `resave/all` command.
- Added the `users/remove-2fa` command. ([#16053](https://github.com/craftcms/cms/pull/16053))
- Added the `--except`, `--minor-only`, and `--patch-only` options to the `update` command. ([#15829](https://github.com/craftcms/cms/pull/15829))
- Added the `--with-fields` option to all native `resave/*` commands.
- The `fields/merge` and `fields/auto-merge` commands now prompt to resave elements that include relational fields before merging them, and provide a CLI command that should be run on other environments before the changes are deployed to them. ([#15869](https://github.com/craftcms/cms/issues/15869))

### Development
- Added the `encodeUrl()` Twig function. ([#15838](https://github.com/craftcms/cms/issues/15838))
- `{% cache %}` tags now support setting the duration number to an expression. ([#15970](https://github.com/craftcms/cms/discussions/15970))
- Added support for passing aliased field handles into element queries’ `select()`/`addSelect()` methods. ([#15827](https://github.com/craftcms/cms/issues/15827))
- Added support for appending subpaths to environment variable names in environmental settings (e.g. `$PRIMARY_SITE_URL/uploads`).

### Extensibility
- Added `craft\base\Element::EVENT_REGISTER_CARD_ATTRIBUTES`.
- Added `craft\base\Element::EVENT_REGISTER_DEFAULT_CARD_ATTRIBUTES`.
- Added `craft\base\Element::defineCardAttributes()`.
- Added `craft\base\Element::defineDefaultCardAttributes()`.
- Added `craft\base\ElementInterface::attributePreviewHtml()`.
- Added `craft\base\ElementInterface::cardAttributes()`.
- Added `craft\base\ElementInterface::defaultCardAttributes()`.
- Added `craft\base\ElementInterface::indexViewModes()`.
- Added `craft\base\NestedElementTrait::saveOwnership()`. ([#15894](https://github.com/craftcms/cms/pull/15894))
- Added `craft\base\PreviewableFieldInterface::previewPlaceholderHtml()`.
- Added `craft\base\RequestTrait::getIsWebRequest()`. ([#15690](https://github.com/craftcms/cms/pull/15690))
- Added `craft\base\conditions\BaseElementSelectConditionRule::elementSelectConfig()`.
- Added `craft\console\Controller::output()`.
- Added `craft\console\controllers\ResaveController::hasTheFields()`.
- Added `craft\elements\db\NestedElementQueryTrait`. ([#15894](https://github.com/craftcms/cms/pull/15894))
- Added `craft\events\ApplyFieldSaveEvent`. ([#15872](https://github.com/craftcms/cms/discussions/15872))
- Added `craft\events\DefineAddressCountriesEvent`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\events\RegisterElementCardAttributesEvent`.
- Added `craft\events\RegisterElementDefaultCardAttributesEvent`.
- Added `craft\fieldlayoutelements\Template::$templateMode`. ([#15932](https://github.com/craftcms/cms/pull/15932))
- Added `craft\fields\data\LinkData::$target`.
- Added `craft\fields\data\LinkData::setLabel()`.
- Added `craft\filters\BasicHttpAuthLogin`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\BasicHttpAuthStatic`. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added `craft\filters\ConditionalFilterTrait`. ([#15948](https://github.com/craftcms/cms/pull/15948))
- Added `craft\filters\UtilityAccess`.
- Added `craft\helpers\Console::$outputCount`.
- Added `craft\helpers\Console::$prependNewline`.
- Added `craft\helpers\Console::indent()`.
- Added `craft\helpers\Console::indentStr()`.
- Added `craft\helpers\Console::outdent()`.
- Added `craft\helpers\Cp::cardPreviewHtml()`.
- Added `craft\helpers\Cp::cardViewDesignerHtml()`.
- Added `craft\helpers\Cp::rangeFieldHtml()`. ([#15972](https://github.com/craftcms/cms/pull/15972))
- Added `craft\helpers\Cp::rangeHtml()`. ([#15972](https://github.com/craftcms/cms/pull/15972))
- Added `craft\helpers\ElementHelper::linkAttributeHtml()`.
- Added `craft\helpers\ElementHelper::uriAttributeHtml()`.
- Added `craft\helpers\Session::addFlash()`.
- Added `craft\helpers\Session::getAllFlashes()`.
- Added `craft\helpers\Session::getFlash()`.
- Added `craft\helpers\Session::hasFlash()`.
- Added `craft\helpers\Session::removeAllFlashes()`.
- Added `craft\helpers\Session::removeFlash()`.
- Added `craft\helpers\StringHelper::firstLine()`.
- Added `craft\helpers\UrlHelper::encodeUrl()`. ([#15838](https://github.com/craftcms/cms/issues/15838))
- Added `craft\log\MonologTarget::getAllowLineBreaks()`.
- Added `craft\log\MonologTarget::getFormatter()`.
- Added `craft\log\MonologTarget::getLevel()`.
- Added `craft\log\MonologTarget::getMaxFiles()`.
- Added `craft\log\MonologTarget::getName()`.
- Added `craft\log\MonologTarget::getProcessor()`.
- Added `craft\log\MonologTarget::getUseMicrosecondTimestamps()`.
- Added `craft\models\FieldLayout::getCardBodyAttributes()`.
- Added `craft\models\FieldLayout::getCardBodyElements()`.
- Added `craft\models\FieldLayout::getCardView()`.
- Added `craft\models\FieldLayout::prependElements()`.
- Added `craft\models\FieldLayout::setCardView()`.
- Added `craft\services\Addresses::EVENT_DEFINE_ADDRESS_COUNTRIES`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\services\Addresses::getCountryList()`. ([#15711](https://github.com/craftcms/cms/pull/15711))
- Added `craft\services\Fields::EVENT_BEFORE_APPLY_FIELD_SAVE`. ([#15872](https://github.com/craftcms/cms/discussions/15872))
- Added `craft\services\Gc::deleteOrphanedFieldLayouts()`.
- Added `craft\services\Users::getMaxUsers()`.
- Added `craft\web\View::registerCpTwigExtension()`.
- Added `craft\web\View::registerSiteTwigExtension()`.
- `craft\fields\data\LinkData::getLabel()` now has a `$custom` argument.
- `craft\helpers\Console::output()` now prepends an indent to each line of the passed-in string, if `indent()` had been called prior.
- Added the `elements/save-nested-element-for-derivative` action. ([#16002](https://github.com/craftcms/cms/pull/16002))
- Improved support for creating log targets for third party logging services. ([#14974](https://github.com/craftcms/cms/pull/14974))
- Deprecated the `enableBasicHttpAuth` config setting. `craft\filters\BasicHttpAuthLogin` should be used instead. ([#15720](https://github.com/craftcms/cms/pull/15720))
- Added the `serializeForm` event to `Craft.ElementEditor`. ([#15794](https://github.com/craftcms/cms/discussions/15794))
- Added the `range()` and `rangeField()` macros to `_includes/forms.twig`. ([#15972](https://github.com/craftcms/cms/pull/15972))
- Added the `fieldLayoutDesigner()` and `cardViewDesigner()` global Twig functions for control panel templates.
- Field layout designers can now be instantiated with a `withCardViewDesigner` param. ([#15283](https://github.com/craftcms/cms/pull/15283)
- Checkbox selects can now be passed a `sortable` option. ([#15963](https://github.com/craftcms/cms/pull/15963))
- Deprecated the `craft.cp.fieldLayoutDesigner()` function. The global `fieldLayoutDesigner()` function should be used instead.

### System
- `Location` headers added via `craft\web\Response::redirect()` are now set to encoded URLs. ([#15838](https://github.com/craftcms/cms/issues/15838))
- Fixed a bug where the `utils/fix-field-layout-uids` command was misidentifying missing/duplicate UUID issues.
- Fixed an error that could occur when upgrading to Craft 5, if unused field layouts contained duplicate UUIDs. ([#16032](https://github.com/craftcms/cms/issues/16032))
- Fixed a bug where Live Preview wasn’t reloading after performing bulk actions in embedded element indexes. ([#16057](https://github.com/craftcms/cms/discussions/16057))
- Fixed a bug where restoring a soft-deleted element would restore any nested elements that had been explicitly soft-deleted. ([#16059](https://github.com/craftcms/cms/issues/16059))
- Fixed a bug where orphaned field layouts weren’t getting garbage collected. ([#16032](https://github.com/craftcms/cms/issues/16032))
- Fixed a bug where the Recovery Codes slideout content overflowed its container on small screens. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Fixed a bug where entries that were soft-deleted along with their section weren’t getting restored if the section was restored.
- Fixed a bug where field types weren’t getting a chance to normalize their values when propagated to a new site for a freshly-created element, if they were marked as translatable.
- Fixed a bug where Link fields didn’t support URLs with non-ASCII characters. ([#15989](https://github.com/craftcms/cms/issues/15989))
- Fixed styling bugs. ([#16061](https://github.com/craftcms/cms/issues/16061))

## 5.4.10.1 - 2024-11-07

- Fixed a bug where it wasn’t possible to create new nested Matrix entries for global sets. ([#16041](https://github.com/craftcms/cms/issues/16041))

## 5.4.10 - 2024-11-06

- Fixed a bug where entry/category drafts weren’t retaining new parent selections.
- Fixed a bug where the “Related To” condition rule would lose track of the selected element if it belonged to a different site. ([#16005](https://github.com/craftcms/cms/pull/16005))
- Fixed a bug where the GraphQL tokens index was saying tokens never expired. ([#16029](https://github.com/craftcms/cms/issues/16029))
- Fixed a bug `craft\services\Categories::saveGroup()` and `craft\services\Tags::saveTagGroup()` weren’t respecting predefined UUID values on new models.
- Fixed an error that could occur when editing a nested Matrix entry in a slideout or full-page editor, if it was initially edited while the Matrix field was in inline-editable blocks mode.
- Fixed an error that could occur when editing an address that had provisional changes.
- Fixed a bug where address cards weren’t showing provisional changes.
- Fixed a bug where it was possible to edit nested addresses when viewing a revision. ([#15961](https://github.com/craftcms/cms/pull/15961))
- Fixed a bug where nested elements could have checkboxes and drag handles when viewing a revision. ([#15961](https://github.com/craftcms/cms/pull/15961))
- Fixed an error that could occur when bulk-editing elements from an element index, if any of them had provisional drafts. ([#15962](https://github.com/craftcms/cms/pull/15962))
- Fixed a bug where element exporting was only working as intended on the first page of results. ([#15996](https://github.com/craftcms/cms/issues/15996), [#16003](https://github.com/craftcms/cms/pull/16003))
- Fixed a bug where Number fields weren’t getting sorted properly in PostgreSQL. ([#15973](https://github.com/craftcms/cms/issues/15973))
- Fixed a bug where field conditions weren’t taking effect within Matrix fields set to inline-editable blocks mode, if `autosaveDrafts` was disabled. ([#15985](https://github.com/craftcms/cms/issues/15985))
- Fixed an error that occurred when attempting to delete a nested Matrix entry, if it had an entry type that was no longer allowed for the Matrix field. ([#15990](https://github.com/craftcms/cms/issues/15990))
- Fixed a bug where structure data wasn’t getting deleted for drafts when moving an entry out of a Structure section. ([#15949](https://github.com/craftcms/cms/issues/15949), [#16014](https://github.com/craftcms/cms/pull/16014))
- Fixed a bug where loading spinners weren’t always centered. ([#16013](https://github.com/craftcms/cms/pull/16013))
- Fixed a race condition that could break default entry creation for Matrix fields. ([#15999](https://github.com/craftcms/cms/pull/15999))
- Fixed a JavaScript error that occurred when sorting nested elements in an embedded element index, if the index was paginated. ([#16026](https://github.com/craftcms/cms/issues/16026))
- Fixed a styling bug.
- Updated Axios to 1.7.7. ([#15958](https://github.com/craftcms/cms/issues/15958))

## 5.4.9 - 2024-10-22

- The `install` command now runs through database connection setup, if Craft can’t yet connect to the database. ([#15943](https://github.com/craftcms/cms/issues/15943))
- `authorId`, `authorIds`, `authors`, and `sectionId` are now reserved field handles for entry types. ([#15923](https://github.com/craftcms/cms/issues/15923))
- Added `craft\elements\db\NestedElementQueryInterface`.
- Added `craft\services\Gc::$silent`.
- Fixed a bug where admin table header cells weren’t indicating when they were sorted. ([#15897](https://github.com/craftcms/cms/issues/15897))
- Fixed an error that occurred when creating a database backup, if the System Name contained any quote-like characters. ([#15933](https://github.com/craftcms/cms/issues/15933))
- Fixed a bug where buttons could bleed out of their containers. ([#15931](https://github.com/craftcms/cms/issues/15931), [#15946](https://github.com/craftcms/cms/pull/15946))
- Fixed a PHP error. ([#15915](https://github.com/craftcms/cms/issues/15915))
- Fixed a bug where uninstalled/missing plugins weren’t getting status indicators on the Plugins index page.
- Fixed errors that occurred when working with nested entries for a newly-added site. ([#15898](https://github.com/craftcms/cms/pull/15898))
- Fixed a bug where it wasn’t possible to scroll the section select modal when moving entries to a different section. ([#15900](https://github.com/craftcms/cms/issues/15900))
- Fixed a bug where query params in the format of `'<operator> <values>'` weren’t being parsed correctly.
- Fixed a bug `craft\services\Entries::saveSection()` and `craft\services\Volumes::saveVolume()` weren’t respecting predefined UUID values on new models.
- Fixed a bug where Addresses fields in element index view weren’t showing newly-created addresses. ([#15911](https://github.com/craftcms/cms/pull/15911)) 
- Fixed a bug where disabled Money fields were showing the clear button.
- Fixed a bug where element slideouts had a “Save” button when viewing a revision. ([#15930](https://github.com/craftcms/cms/pull/15930))
- Fixed a bug where element edit pages had a “Revert content from this revision” button for elements that didn’t support revisions. ([#15930](https://github.com/craftcms/cms/pull/15930))
- Fixed an error that occurred when loading a soft-deleted nested entry from a revision. ([#15930](https://github.com/craftcms/cms/pull/15930))
- Fixed a bug where the `entrify/tags` and `entrify/global-set` commands would prompt for the target section after one had just been created.
- Fixed a bug where `entrify` commands weren’t copying the original field instance UUIDs into newly-created entry types, causing content to appear missing. ([#15935](https://github.com/craftcms/cms/issues/15935))
- Fixed a bug where element editor slideouts could create unnecessary provisional drafts. ([#15938](https://github.com/craftcms/cms/issues/15938))
- Fixed an information disclosure vulnerability.

## 5.4.8 - 2024-10-15

- Added `craft\helpers\App::isTty()`.
- Fixed a styling issue with Color field inputs. ([#15868](https://github.com/craftcms/cms/issues/15868))
- Fixed a deprecation error. ([#15873](https://github.com/craftcms/cms/issues/15873))
- Fixed a bug where element sources weren’t keyboard-selectable. ([#15876](https://github.com/craftcms/cms/issues/15876))
- Fixed a bug where Craft wasn’t auto-detecting interactive terminals on Windows.
- Fixed a bug where element actions were allowed on nested entries when viewing a revision. ([#15879](https://github.com/craftcms/cms/pull/15879))
- Fixed a bug where element error summaries weren’t linking to recursively-nested Matrix fields properly. ([#15797](https://github.com/craftcms/cms/issues/15797))
- Fixed a bug where eager-loaded relation fields were loading all related elements across all instances of the field. ([#15890](https://github.com/craftcms/cms/issues/15890))
- Fixed a bug where expanding the site statuses UI for an entry within a slideout would remove the expand button from the main entry’s form. ([#15893](https://github.com/craftcms/cms/pull/15893))
- Fixed a privilege escalation vulnerability.

## 5.4.7.1 - 2024-10-09

- Custom field condition rules are now ignored if they reference a field with an incompatible type. ([#15850](https://github.com/craftcms/cms/issues/15850))
- Fixed an error that could occur if Hyper was installed. ([#15867](https://github.com/craftcms/cms/issues/15867))
- Fixed an error occurred when running `migrate` commands with an invalid `--plugin` option value.

## 5.4.7 - 2024-10-08

- The Plugin Store now displays plugin ratings and reviews. ([#15860](https://github.com/craftcms/cms/pull/15860))
- An `InvalidConfigException` is now thrown if the `defaultCountryCode` config setting is set to an empty string. ([#15812](https://github.com/craftcms/cms/pull/15812))
- Fixed an error that could occur when saving an element, if a Date field’s time zone input was focused.
- Fixed a bug where the time zones listed in Date fields weren’t labelled properly based on the selected date. ([#15805](https://github.com/craftcms/cms/issues/15805))
- Fixed an error that could occur if a native element property was attempted to be eager-loaded. ([#15822](https://github.com/craftcms/cms/issues/15822))
- Fixed errors that could occur if a custom source or field condition referenced a custom field whose type had changed. ([#15850](https://github.com/craftcms/cms/issues/15850))
- Fixed a bug where disclosure menus weren’t sticking to their trigger element as it was scrolled, if it was within a slideout or other inline-scrollable container. ([#15852](https://github.com/craftcms/cms/issues/15852))
- Fixed a bug where the default backup command for MySQL was exporting triggers twice. ([#15854](https://github.com/craftcms/cms/pull/15854))
- Fixed a bug where Multi-select fields were saving the selected options in the user-selected order rather than the field-defined order. ([#15857](https://github.com/craftcms/cms/issues/15857))
- Fixed a bug where field toggling wasn’t working properly for boolean menus and radio groups.
- Fixed a bug where eager-loading wasn’t working properly when multiple fields had the same handle. ([#15796](https://github.com/craftcms/cms/issues/15796))
- Fixed a bug where where required Full Name fields weren’t getting enforced for users. ([#15808](https://github.com/craftcms/cms/issues/15808))
- Fixed a bug where relation fields weren’t merging uploaded asset IDs with the existing field values. ([#15809](https://github.com/craftcms/cms/issues/15809))
- Fixed a bug where the “Add” menu within field layout designer tabs was always being positioned below the button. ([#15852](https://github.com/craftcms/cms/issues/15852))
- Fixed a bug where Number fields weren’t getting sorted properly in PostgreSQL. ([#15828](https://github.com/craftcms/cms/issues/15828))
- Fixed a SQL error that occurred when upgrading to Craft 5 on MySQL, if `sql_generate_invisible_primary_key` was enabled. ([#15853](https://github.com/craftcms/cms/issues/15853))
- Fixed a missing authorization vulnerability.

## 5.4.6 - 2024-09-27

- Improved relational fields’ drag-n-drop responsiveness in Safari. ([#15728](https://github.com/craftcms/cms/issues/15728))
- Fixed a bug where entries’ `deletedWithEntryType` values in the `entries` table weren’t getting set back to `null` after being restored.
- Fixed a bug where it wasn’t possible to discard changes for related elements via slideouts, if they didn’t exist in the primary site. ([#15798](https://github.com/craftcms/cms/issues/15798))
- Fixed an error that could occur when restoring a soft-deleted entry type and section, if any entries had been soft-deleted alongside the entry type. ([#15787](https://github.com/craftcms/cms/issues/15787))
- Fixed a bug where Tags fields weren’t working properly when their label was hidden. ([#15800](https://github.com/craftcms/cms/issues/15800))
- Fixed an information disclosure vulnerability.

## 5.4.5.1 - 2024-09-24

- Fixed a JavaScript error. ([#15784](https://github.com/craftcms/cms/issues/15784))

## 5.4.5 - 2024-09-23

- Element conditions now show rules for fields with the same name but unique handles, if the “Show field handles in edit forms” user preference is enabled. ([#15764](https://github.com/craftcms/cms/issues/15764))
- Auto-generated handles, slugs, etc. now update immediately when the source input is changed. ([#15754](https://github.com/craftcms/cms/issues/15754))
- Fixed a bug where Table fields’ Default Values table could lose existing rows if they only consisted of Dropdown columns without configured options.
- Fixed a bug where custom fields’ `required` properties were always `false`. ([#15752](https://github.com/craftcms/cms/issues/15752))
- Fixed a bug where `craft\helpers\StringHelper::toHandle()` was allowing non-alphanumeric/underscore characters through. ([#15772](https://github.com/craftcms/cms/pull/15772))
- Fixed a bug where entries were getting auto-saved while dragging elements within element select inputs.
- Fixed a bug where the `maxBackups` config setting wasn’t working. ([#15780](https://github.com/craftcms/cms/issues/15780))
- Fixed a bug where it wasn’t possible to save nested entries via the `entries/save-entry` controller action. ([#15737](https://github.com/craftcms/cms/issues/15737))
- Fixed a bug where hyperlinks in Link field inputs could wrap unnecessarily. ([#15738](https://github.com/craftcms/cms/issues/15738))
- Fixed an error that occurred when running the `entrify/global-set` command. ([#15746](https://github.com/craftcms/cms/issues/15746))
- Fixed a bug where users’ `username` values weren’t getting updated based on email address changes when `useEmailAsUsername` was enabled. ([#15758](https://github.com/craftcms/cms/issues/15758))
- Fixed a bug where the `hasAlt` asset query param wasn’t working properly. ([#15762](https://github.com/craftcms/cms/issues/15762))
- Fixed a bug where relational fields could show related elements for other field instances within element indexes. ([#15777](https://github.com/craftcms/cms/issues/15777))
- Fixed a bug where it wasn’t possible to upload files to Assets fields with dynamic subpaths. ([#15775](https://github.com/craftcms/cms/issues/15775))

## 5.4.4 - 2024-09-14

> [!IMPORTANT]  
> This update fixes a critical data deletion bug for PostgreSQL installs.

- Fixed a data deletion bug that occurred during garbage collection on PostgreSQL. ([#14891](https://github.com/craftcms/cms/issues/14891))
- Fixed a bug where image constraint labels weren’t translated within the Image Editor.
- Fixed a bug where image orientation labels weren’t getting translated for screen readers within the Image Editor.
- Fixed a PHP error. ([#14635](https://github.com/craftcms/cms/issues/14635))
- Fixed a bug where elements’ default field values weren’t getting populated on creation. ([#15706](https://github.com/craftcms/cms/issues/15706))
- Fixed a bug where URL field previews could bleed out of their container. ([#15722](https://github.com/craftcms/cms/issues/15722))

## 5.4.3 - 2024-09-11

- Updated Twig to 3.14. ([#15704](https://github.com/craftcms/cms/issues/15704))
- Fixed a bug where soft-deleted structures weren’t getting hard-deleted via garbage collection. ([#15705](https://github.com/craftcms/cms/pull/15705))
- Fixed a bug where address’ Label fields were being marked as translatable. ([#15702](https://github.com/craftcms/cms/pull/15702))
- Fixed an error that could occur when saving an entry with a Matrix field, if the nested entries didn’t have slugs.
- Fixed a bug where relation fields weren’t merging uploaded asset IDs with the existing field values. ([#15707](https://github.com/craftcms/cms/issues/15707))
- Fixed a styling issue with inline-editable Matrix block tabs. ([#15703](https://github.com/craftcms/cms/issues/15703))
- Fixed a bug where the control panel layout could shift briefly when removing an element from an element select input. ([#15712](https://github.com/craftcms/cms/issues/15712))
- Fixed an RCE vulnerability.
- Fixed an XSS vulnerability.

## 5.4.2 - 2024-09-06

- Added `craft\services\Security::isSystemDir()`.
- Fixed a bug where `craft\helpers\StringHelper::lines()` was returning an array of `Stringy\Stringy` objects, rather than strings.
- Fixed styling issues with Template field layout UI elements’ selector labels.
- Fixed a validation error that could occur when saving a relational field, if the “Maintain hierarchy” setting had been enabled but was no longer applicable. ([#15666](https://github.com/craftcms/cms/issues/15666))
- Fixed a bug where formatted addresses weren’t using the application locale consistently. ([#15668](https://github.com/craftcms/cms/issues/15668))
- Fixed a bug where Tip and Warning field layout UI elements would display in field layouts even if they had no content. ([#15681](https://github.com/craftcms/cms/issues/15681))
- Fixed an error that could occur when reverting an element’s content from a revision, if the element had been added to additional sites since the time the revision was created. ([#15679](https://github.com/craftcms/cms/issues/15679))
- Fixed a PHP error that occurred when running PHP 8.2 or 8.3.
- Fixed a bug where disabled entries became enabled when edited within Live Preview. ([#15670](https://github.com/craftcms/cms/issues/15670))
- Fixed a bug where new nested entries could get incremented slugs even if there were no elements with conflicting URIs. ([#15672](https://github.com/craftcms/cms/issues/15672))
- Fixed a bug where users’ Addresses screens were displaying addresses that belonged to the user via a custom Addresses field. ([#15678](https://github.com/craftcms/cms/issues/15678))
- Fixed a bug where Addresses fields weren’t always returning data in GraphQL.
- Fixed a bug where partial addresses weren’t getting garbage collected.
- Fixed a bug where orphaned nested addresses weren’t getting garbage collected. ([#15678](https://github.com/craftcms/cms/issues/15678))
- Fixed a bug where orphaned nested entries weren’t getting garbage collected after their field had been hard-deleted. ([#15678](https://github.com/craftcms/cms/issues/15678))
- Fixed a JavaScript error that could occur when bulk-editing elements. ([#15694](https://github.com/craftcms/cms/issues/15694))
- Fixed an information disclosure vulnerability.

## 5.4.1 - 2024-09-04

- Fixed a bug where element chips within thumbnail views weren’t getting light gray backgrounds. ([#15649](https://github.com/craftcms/cms/issues/15649))
- Fixed a bug where Link fields didn’t fully support inline editing. ([#15653](https://github.com/craftcms/cms/issues/15653))
- Fixed the loading spinner styling on element indexes. ([#15634](https://github.com/craftcms/cms/issues/15634))
- Fixed an error that could occur when saving an element. ([#15656](https://github.com/craftcms/cms/issues/15656))
- Fixed the styling of column sort indicators. ([#15655](https://github.com/craftcms/cms/issues/15655))

## 5.4.0.1 - 2024-09-03

- Fixed a PHP error that could occur on element indexes. ([#15648](https://github.com/craftcms/cms/issues/15648))

## 5.4.0 - 2024-09-03

### Content Management
- Element conditions can now have a “Not Related To” rule. ([#15496](https://github.com/craftcms/cms/pull/15496))
- Element conditions can now have a “Site Group” rule, if there are two or more site groups. ([#15625](https://github.com/craftcms/cms/discussions/15625))
- Asset chips and cards no longer include the “Replace file” action. ([#15498](https://github.com/craftcms/cms/issues/15498))
- Category slugs are now inline-editable from the Categories index page. ([#15560](https://github.com/craftcms/cms/pull/15560))
- Entry post dates, expiry dates, slugs, and authors are now inline-editable from the Entries index page. ([#15560](https://github.com/craftcms/cms/pull/15560))
- Improved Addresses field validation to be more consistent with Matrix fields.
- Entry chips and cards no longer include status indicators, if their entry type’s “Show thet Status field” setting is disabled. ([#15636](https://github.com/craftcms/cms/discussions/15636))
- Matrix and Addresses fields now show newly-created elements on first edit, rather than after they’ve been fully saved. ([#15641](https://github.com/craftcms/cms/issues/15641)) 

### Accessibility
- Improved the accessibility of Tags fields.

### Administration
- Link fields now have “Allow root-relative URLs” and “Allow anchors” settings. ([#15579](https://github.com/craftcms/cms/issues/15579))
- Custom field selectors within field layouts now display a pencil icon if their name, instructions, or handle have been overridden. ([#15597](https://github.com/craftcms/cms/discussions/15597))
- Custom field settings within field layouts now display a chip for the associated global field. ([#15619](https://github.com/craftcms/cms/pull/15619), [#15597](https://github.com/craftcms/cms/discussions/15597))
- Field layouts can now define tips and warnings that should be displayed for fields. ([#15632](https://github.com/craftcms/cms/discussions/15632))
- The Fields index page now has a “Used by” column that shows how many field layouts each field is used by. ([#14984](https://github.com/craftcms/cms/discussions/14984))
- The Entry Types index page now has a “Used by” column that lists the sections and custom fields that each entry type is used by. ([#14984](https://github.com/craftcms/cms/discussions/14984))
- Single sections can now have multiple entry types. ([#15630](https://github.com/craftcms/cms/discussions/15630))
- Increased the text size for handle buttons within admin tables.

### Development
- Added the `notRelatedTo` and `andNotRelatedTo` element query params. ([#15496](https://github.com/craftcms/cms/pull/15496))
- Added the `notRelatedTo` GraphQL element query argument. ([#15496](https://github.com/craftcms/cms/pull/15496))
- `relatedToAssets`, `relatedToCategories`, `relatedToEntries`, `relatedToTags`, and `relatedToUsers` GraphQL arguments now support passing `relatedViaField` and `relatedViaSite` keys to their criteria objects. ([#15508](https://github.com/craftcms/cms/pull/15508))
- Country field values and `craft\elements\Address::getCountry()` now return the country in the current application locale.

### Extensibility
- Added `craft\base\ApplicationTrait::getEnvId()`. ([#15313](https://github.com/craftcms/cms/issues/15313))
- Added `craft\base\ElementInterface::getRootOwner()`. ([#15534](https://github.com/craftcms/cms/discussions/15534))
- Added `craft\base\ElementInterface::showStatusIndicator()`.
- Added `craft\elements\conditions\NotRelatedToConditionRule`.
- Added `craft\elements\conditions\SiteGroupConditionRule`.
- Added `craft\gql\arguments\RelationCriteria`.
- Added `craft\gql\types\input\criteria\AssetRelation`.
- Added `craft\gql\types\input\criteria\CategoryRelation`.
- Added `craft\gql\types\input\criteria\EntryRelation`.
- Added `craft\gql\types\input\criteria\TagRelation`.
- Added `craft\gql\types\input\criteria\UserRelation`.
- Added `craft\helpers\Cp::componentPreviewHtml()`.
- Added `craft\helpers\Inflector`.
- Added `craft\helpers\Session::close()`.
- Added `craft\services\Sites::getEditableSitesByGroupId()`.
- `craft\helpers\Cp::chipHtml()` now supports a `hyperlink` option.
- `craft\helpers\Session` methods are now safe to call on console requests.
- `craft\services\Elements::saveElement()` now saves dirty fields’ content even if `$saveContent` is `false`. ([#15393](https://github.com/craftcms/cms/pull/15393))
- Deprecated `craft\db\mysql\Schema::quoteDatabaseName()`.
- Deprecated `craft\db\pgqsl\Schema::quoteDatabaseName()`.
- Deprecated `craft\helpers\ElementHelper::rootElement()`. `craft\base\ElementInterface::getRootOwner()` should be used instead.
- Added `Craft.cp.announce()`, simplifying live region announcements for screen readers. ([#15569](https://github.com/craftcms/cms/pull/15569))
- Element action menu items returned by `craft\base\Element::safeActionMenuItems()` and `destructiveActionMenuItems()` can now include a `showInChips` key to explicitly opt into/out of being shown within element chips and cards.
- Element select inputs now support `allowAdd` and `allowRemove` settings. ([#15639](https://github.com/craftcms/cms/discussions/15639))
- Control panel CSS selectors that take orientation into account now use logical properties. ([#15522](https://github.com/craftcms/cms/pull/15522))

### System
- MySQL mutex locks and PHP session names are now namespaced using the application ID combined with the environment name. ([#15313](https://github.com/craftcms/cms/issues/15313))
- Added support for “City/Town” address locality labels. ([#15585](https://github.com/craftcms/cms/pull/15585))
- Craft now sends `X-Robots-Tag: none` headers for preview requests. ([#15612](https://github.com/craftcms/cms/pull/15612), [#15586](https://github.com/craftcms/cms/issues/15586))
- `x-craft-preview` and `x-craft-live-preview` params are now hashed, and `craft\web\Request::getIsPreview()` will only return `true` if the param validates. ([#15605](https://github.com/craftcms/cms/discussions/15605))
- Generated URLs no longer include `x-craft-preview` or `x-craft-live-preview` query string params based on the requested URL, if either were set to an unverified string. ([#15605](https://github.com/craftcms/cms/discussions/15605))
- The PHP session is now closed before making API requests. ([#15643](https://github.com/craftcms/cms/issues/15643))
- Updated Twig to 3.12. ([#15568](https://github.com/craftcms/cms/discussions/15568))
- Fixed a SQL error that occurred when running the `db/convert-charset` command if there were any custom database views or sequences. ([#15598](https://github.com/craftcms/cms/issues/15598))
- Fixed a bug where `craft\helpers\Db::supportsTimeZones()` could return `false` on databases that supported time zone conversion. ([#15592](https://github.com/craftcms/cms/issues/15592))
- Fixed a bug where `null` values within associative arrays were ignored when applying project config data. ([#10512](https://github.com/craftcms/cms/issues/10512))
- Fixed a bug where tabs within field layout designers weren’t always getting positioned correctly when wrapped. ([#15590](https://github.com/craftcms/cms/issues/15590))
- Fixed a bug where editable table rows’ action buttons were misaligned for newly-created rows. ([#15602](https://github.com/craftcms/cms/issues/15602))
- Fixed a bug where relational fields’ element query results weren’t limited to the selected relations if the `id` param was overridden. ([#15570](https://github.com/craftcms/cms/issues/15570))
- Fixed a bug where ordering element queries by textual custom fields would factor in character marks. ([#15609](https://github.com/craftcms/cms/issues/15609))
- Fixed a bug where Money fields’ condition rules could display incorrect values based on a user’s formatting locale.
- Fixed an error that occurred when eager-loading user addresses. ([#15629](https://github.com/craftcms/cms/pull/15629))
- Fixed styling issues with classic Live Preview. ([#15640](https://github.com/craftcms/cms/issues/15640))
- Fixed a bug where fields were bleeding out of the content pane on smaller viewports.
- Fixed a bug where Link fields didn’t allow URLs with TLDs longer than six characters.
- Fixed a bug where hard-deleting an element wasn’t hard-deleting any nested elements as well. ([#15645](https://github.com/craftcms/cms/pull/15645))
- Fixed a bug where it wasn’t possible to hard-delete nested elements from embedded element index views. ([#15645](https://github.com/craftcms/cms/pull/15645))
- Fixed an error that occurred when calling the `users/delete-user-photo` or `users/upload-user-photo` from the front end. ([#15487](https://github.com/craftcms/cms/pull/15487))
- Fixed styling issues. ([#15537](https://github.com/craftcms/cms/pull/15537))

## 5.3.6 - 2024-08-26

- Fixed a bug where it wasn’t possible to override named transforms in GraphQL queries. ([#15572](https://github.com/craftcms/cms/issues/15572))
- Fixed a bug where address subdivision fields could be incorrectly labelled and/or populated with the wrong options. ([#15551](https://github.com/craftcms/cms/issues/15551), [#15584](https://github.com/craftcms/cms/pull/15584))
- Fixed an error that occurred if Country tables were included within element index tables or cards. ([#15583](https://github.com/craftcms/cms/issues/15583))
- Fixed a bug where `{% cache %}` tags were caching content for Live Preview requests. ([#15586](https://github.com/craftcms/cms/issues/15586))
- Fixed a bug where it wasn’t possible to remove nested entries in Matrix fields if the Min Entries setting had been reached. ([#15575](https://github.com/craftcms/cms/issues/15575))
- Fixed a bug where Matrix and Addresses fields weren’t displaying or validating unpublished drafts. ([#15536](https://github.com/craftcms/cms/issues/15536))
- Fixed a bug where element selector modals within Link fields didn’t have site selector menus. ([#15594](https://github.com/craftcms/cms/issues/15594))

## 5.3.5 - 2024-08-21

- Updated jQuery UI to 1.14.0. ([#15558](https://github.com/craftcms/cms/issues/15558))
- Fixed a bug where `craft\helpers\App::env()` and `normalizeValue()` could return incorrect results for values that looked like floats. ([#15533](https://github.com/craftcms/cms/issues/15533))
- Fixed a bug where the `users/set-password` action wasn’t respecting `redirect` params. ([#15538](https://github.com/craftcms/cms/issues/15538))
- Fixed a bug where the “Default Values” Table field setting wasn’t escaping column headings. ([#15552](https://github.com/craftcms/cms/issues/15552))
- Fixed a bug where Craft couldn’t be installed with existing project config files, if any plugins specified their schema version via `composer.json`. ([#15559](https://github.com/craftcms/cms/issues/15559))
- Fixed a bug where Money fields’ min, max, and default values weren’t being set to the correct currency. ([#15565](https://github.com/craftcms/cms/issues/15565), [#15566](https://github.com/craftcms/cms/pull/15566))
- Fixed a bug where Money fields weren’t handling negative values correctly. ([#15565](https://github.com/craftcms/cms/issues/15565), [#15567](https://github.com/craftcms/cms/pull/15567))
- Fixed a bug where PHP-originated Craft Console API requests weren’t timing out if the API was down. ([#15571](https://github.com/craftcms/cms/pull/15571))
- Fixed a bug where admin tables weren’t displaying disabled statuses. ([#15540](https://github.com/craftcms/cms/pull/15540))
- Fixed a JavaScript error that occurred when adding a row to an editable table that didn’t allow reordering rows. ([#15543](https://github.com/craftcms/cms/issues/15543))
- Fixed an error that occurred when editing an element with a Link field previously set to a URL value, if the field no longer allows URLs. ([#15542](https://github.com/craftcms/cms/issues/15542))
- Fixed an error that could occur when upgrading to Craft 5. ([#15539](https://github.com/craftcms/cms/issues/15539), [#15555](https://github.com/craftcms/cms/issues/15555))

## 5.3.4 - 2024-08-13

- Fixed a bug where the system name in the control panel’s global sidebar was getting hyperlinked even if the primary site didn’t have a URL. ([#15525](https://github.com/craftcms/cms/issues/15525))
- Fixed a bug where site crumbs on global set edit pages were including sites the user didn’t have permission to access. ([#15524](https://github.com/craftcms/cms/issues/15524))
- Fixed a bug where multi-instance relation fields could get combined field values. ([#15526](https://github.com/craftcms/cms/issues/15526))
- Fixed styling issues.

## 5.3.3 - 2024-08-12

- Fixed an error that could occur if a new element was saved recursively. ([#15517](https://github.com/craftcms/cms/issues/15517))
- Fixed a bug where plugins were being instantiated at the beginning of Craft installation requests, rather than after Craft was installed. ([#15506](https://github.com/craftcms/cms/issues/15506))
- Fixed a bug where an unhelpful error message was output when `config/general.php` returned an array with unsupported config settings. ([#15514](https://github.com/craftcms/cms/discussions/15514))

## 5.3.2 - 2024-08-10

- Added `craft\db\afterDown()`.
- Added `craft\db\afterUp()`.
- Improved the appearance of some system settings icons.
- Fixed a bug where Link fields weren’t allowing category groups to be selected, if they didn’t have a URI format for the primary site.
- Fixed an error that occurred when installing Craft in PostgreSQL. ([#15504](https://github.com/craftcms/cms/issues/15504))
- Fixed a bug where Matrix fields weren’t retaining the sort order for disabled nested entries. ([#15505](https://github.com/craftcms/cms/issues/15505))
- Fixed a bug where Link fields weren’t displaying their input if they only had one type selected, and it wasn’t URL. ([#15512](https://github.com/craftcms/cms/issues/15512))
- Fixed a bug where elements’ `searchScore` values were `null` when ordering an element query by `score`. ([#15513](https://github.com/craftcms/cms/issues/15513))
- Fixed a bug where Assets fields weren’t storing files that were uploaded to them directly on element save requests. ([#15511](https://github.com/craftcms/cms/issues/15511))

## 5.3.1 - 2024-08-07

- Fixed a bug where `craft\filters\Headers` and `craft\filters\Cors` were applied to control panel requests rather than site requests. ([#15495](https://github.com/craftcms/cms/issues/15495))
- Fixed a bug where Link fields weren’t retaining their link type-specific settings. ([#15491](https://github.com/craftcms/cms/issues/15491))

## 5.3.0.3 - 2024-08-06

- Fixed a PHP error that could occur when editing Addresses fields set to the element index view mode. ([#15486](https://github.com/craftcms/cms/issues/15486))

## 5.3.0.2 - 2024-08-06

- Fixed an error that could occur on console requests.

## 5.3.0.1 - 2024-08-06

- Fixed an error that occurred when accessing custom config settings defined in `config/custom.php`. ([#15481](https://github.com/craftcms/cms/issues/15481))
- Fixed a PHP error that could occur when editing Addresses fields. ([#15485](https://github.com/craftcms/cms/issues/15485))

## 5.3.0 - 2024-08-06

### Content Management
- Added the “Link” field type, which replaces “URL”, and can store URLs, `mailto` and `tel` URIs, and entry/asset/category relations. ([#15251](https://github.com/craftcms/cms/pull/15251), [#15400](https://github.com/craftcms/cms/pull/15400))
- Added the ability to move entries between sections that allow the same entry type, via a new “Move to…” bulk action. ([#8153](https://github.com/craftcms/cms/discussions/8153), [#14541](https://github.com/craftcms/cms/pull/14541))
- Entry and category conditions now have a “Has Descendants” rule. ([#15276](https://github.com/craftcms/cms/discussions/15276))
- “Replace file” actions now display success notices on complete. ([#15217](https://github.com/craftcms/cms/issues/15217))
- Double-clicking on folders within asset indexes and folder selection modals now navigates the index/modal into the folder. ([#15238](https://github.com/craftcms/cms/discussions/15238))
- When propagating an element to a new site, relation fields no longer copy relations for target elements that wouldn’t have been selectable from the propagated site based on the field’s “Related elements from a specific site?” and “Show the site menu” settings. ([#15459](https://github.com/craftcms/cms/issues/15459))
- Matrix fields now show validation errors when nested entries don’t validate. ([#15161](https://github.com/craftcms/cms/issues/15161), [#15165](https://github.com/craftcms/cms/pull/15165))
- Matrix fields set to inline-editable blocks view now support selecting all blocks by pressing <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>A</kbd> when a checkbox is focused. ([#15326](https://github.com/craftcms/cms/issues/15326))
- Users’ Permissions, Preferences, and Password & Verification screens now have “Save and continue editing” actions, as well as support for <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>S</kbd> keyboard shortcuts.
- User profile screens now have a “Create and set permissions” button for new users, if the current user has access to edit user permissions. ([#15356](https://github.com/craftcms/cms/pull/15356))
- User permission screens now have a “Save and send activation email” button for inactive users, if the current user has the “Administrate users” permission. ([#15356](https://github.com/craftcms/cms/pull/15356))
- Single section entries without a title are now labelled by their section’s name in the control panel.
- Double-clicking on element index rows no longer opens the element editor slideout, when inline editing is active. ([#15441](https://github.com/craftcms/cms/discussions/15441))

### Accessibility
- Improved the accessibility of two-step verification setup. ([#15229](https://github.com/craftcms/cms/pull/15229))
- The notification heading is no longer read to screen readers when no notifications are active. ([#15294](https://github.com/craftcms/cms/pull/15294))
- The login modal that appears once a user’s session has ended now has a `lang` attribute, in case it differs from the user’s preferred language.
- Improved the focus ring styling for dark buttons. ([#15364](https://github.com/craftcms/cms/pull/15364))
- Single-select element selection modals now assign `role="radio"` to listed elements’ checkboxes.
- Sortable editable table rows now have “Move up” and “Move down” disclosure menu actions. ([#15385](https://github.com/craftcms/cms/pull/15385))
- Improved the Customize Sources modal for screen readers. ([#15395](https://github.com/craftcms/cms/pull/15395))
- Improved the accessibility of icon fields. ([#15479](https://github.com/craftcms/cms/pull/15479))

### Administration
- Relation fields are now multi-instance. ([#15400](https://github.com/craftcms/cms/pull/15400))
- Relation fields now have Translation Method settings with all the usual options, replacing “Manage relations on a per-site basis” settings. ([#15400](https://github.com/craftcms/cms/pull/15400))
- Entry types are no longer required to have unique names. ([#14774](https://github.com/craftcms/cms/issues/14774), [#15438](https://github.com/craftcms/cms/pull/15438))
- Entry type selects within section and Matrix/CKEditor field settings now display entry types’ handles in addition to their names, to avoid ambiguity. ([#15438](https://github.com/craftcms/cms/pull/15438))
- The Entry Types index page now displays entry type chips in place of plain text labels, so their custom colors are shown. ([#15432](https://github.com/craftcms/cms/discussions/15432))
- The Entry Types index table can now be sorted by Name and Handle.
- The Fields index table can now be sorted by Name, Handle, and Type.
- Icon fields now have an “Include Pro icons” setting, which determines whether Font Awesome Pro icon should be selectable. ([#15242](https://github.com/craftcms/cms/issues/15242))
- New sites’ Base URL settings now default to an environment variable name based on the site name. ([#15347](https://github.com/craftcms/cms/pull/15347))
- Craft now warns against using the `@web` alias for URL settings, regardless of whether it was explicitly defined. ([#15347](https://github.com/craftcms/cms/pull/15347))
- Entry types created from Matrix block types no longer show the Slug field by default, after upgrading to Craft 5. ([#15379](https://github.com/craftcms/cms/issues/15379))
- Global sets listed within fields’ “Used by” lists now link to their settings page, rather than their edit page. ([#15423](https://github.com/craftcms/cms/discussions/15423))
- Added the `entry-types/merge` command. ([#15444](https://github.com/craftcms/cms/pull/15444))
- Added the `fields/auto-merge` command. ([#15472](https://github.com/craftcms/cms/pull/15472))`
- Added the `fields/merge` command. ([#15454](https://github.com/craftcms/cms/pull/15454))

### Development
- Added support for application-type based `general` and `db` configs (e.g. `config/general.web.php`). ([#15346](https://github.com/craftcms/cms/pull/15346))
- `general` and `db` config files can now return a callable that modifies an existing config object. ([#15346](https://github.com/craftcms/cms/pull/15346))
- Added the `lazyGqlTypes` config setting. ([#15429](https://github.com/craftcms/cms/issues/15429))
- Added the `env`, `env/set`, and `env/remove` commands. ([#15431](https://github.com/craftcms/cms/pull/15431))
- Color, Country, Email, Icon, Link, Plain Text, and Table fields’ element query params now support passing in an array with `value` and `caseInsensitive` keys. ([#15404](https://github.com/craftcms/cms/pull/15404))
- GraphQL mutations for saving drafts of nested entries are now named with `Field` after the Matrix/CKEditor field handle. ([#15269](https://github.com/craftcms/cms/issues/15269))
- The `allowedGraphqlOrigins` config setting is now deprecated. `craft\filters\Cors` should be used instead. ([#15397](https://github.com/craftcms/cms/pull/15397))
- The `permissionsPolicyHeader` config settings is now deprecated. `craft\filters\Headers` should be used instead. ([#15397](https://github.com/craftcms/cms/pull/15397))
- `{% cache %}` tags now cache any asset bundles registered within them.
- Country field values are now set to `CommerceGuys\Addressing\Country\Country` objects. ([#15455](https://github.com/craftcms/cms/issues/15455), [#15463](https://github.com/craftcms/cms/pull/15463))
- Auto-populated section and category group Template settings are now suffixed with `.twig`.
- `x-craft-preview`/`x-craft-live-preview` URL query string params are now added to generated URLs for Live Preview requests, so `craft\web\Request::getIsPreview()` continues to return `true` on subsequent pages loaded within the iframe. ([#15447](https://github.com/craftcms/cms/discussions/15447)) 

### Extensibility
- Added `craft\base\ApplicationTrait::getDb2()`. ([#15384](https://github.com/craftcms/cms/pull/15384))
- Added `craft\base\ElementInterface::addInvalidNestedElementIds()`.
- Added `craft\base\ElementInterface::getInvalidNestedElementIds()`.
- Added `craft\base\Field::EVENT_AFTER_MERGE_FROM`.
- Added `craft\base\Field::EVENT_AFTER_MERGE_INTO`.
- Added `craft\base\Field::afterMergeFrom()`. ([#15454](https://github.com/craftcms/cms/pull/15454))
- Added `craft\base\Field::afterMergeInto()`. ([#15454](https://github.com/craftcms/cms/pull/15454))
- Added `craft\base\Field::canMergeFrom()`. ([#15454](https://github.com/craftcms/cms/pull/15454))
- Added `craft\base\Field::canMergeInto()`. ([#15454](https://github.com/craftcms/cms/pull/15454))
- Added `craft\base\FieldLayoutComponent::EVENT_DEFINE_SHOW_IN_FORM`. ([#15260](https://github.com/craftcms/cms/issues/15260))
- Added `craft\base\FieldLayoutElement::$dateAdded`.
- Added `craft\base\FieldTrait::$dateDeleted`.
- Added `craft\base\Grippable`.
- Added `craft\base\MergeableFieldInterface`. ([#15454](https://github.com/craftcms/cms/pull/15454))
- Added `craft\base\RelationFieldInterface`. ([#15400](https://github.com/craftcms/cms/pull/15400))
- Added `craft\base\RelationFieldTrait`. ([#15400](https://github.com/craftcms/cms/pull/15400))
- Added `craft\config\GeneralConfig::addAlias()`. ([#15346](https://github.com/craftcms/cms/pull/15346))
- Added `craft\elements\Address::getCountry()`. ([#15463](https://github.com/craftcms/cms/pull/15463))
- Added `craft\elements\Asset::$sanitizeOnUpload`. ([#15430](https://github.com/craftcms/cms/discussions/15430))
- Added `craft\elements\Entry::isEntryTypeCompatible()`.
- Added `craft\elements\actions\MoveToSection`.
- Added `craft\enums\CmsEdition::Enterprise`.
- Added `craft\events\DefineShowFieldLayoutComponentInFormEvent`. ([#15260](https://github.com/craftcms/cms/issues/15260))
- Added `craft\events\MoveEntryEvent`.
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
- Added `craft\filters\Cors`. ([#15397](https://github.com/craftcms/cms/pull/15397))
- Added `craft\filters\Headers`. ([#15397](https://github.com/craftcms/cms/pull/15397))
- Added `craft\helpers\App::configure()`.
- Added `craft\models\FieldLayout::getAllElements()`.
- Added `craft\models\ImageTransform::$indexId`.
- Added `craft\services\Elements::ensureBulkOp()`.
- Added `craft\services\Entries::EVENT_AFTER_MOVE_TO_SECTION`.
- Added `craft\services\Entries::EVENT_BEFORE_MOVE_TO_SECTION`.
- Added `craft\services\Entries::moveEntryToSection()`.
- Added `craft\services\Fields::areFieldTypesCompatible()`.
- Added `craft\web\View::clearAssetBundleBuffer()`.
- Added `craft\web\View::startAssetBundleBuffer()`.
- `craft\helpers\DateTimeHelper::toIso8601()` now has a `$setToUtc` argument.
- `craft\helpers\UrlHelper::cpUrl()` now returns URLs based on the primary site’s base URL (if it has one), for console requests if the `baseCpUrl` config setting isn’t set, and the `@web` alias wasn’t explicitly defined. ([#15374](https://github.com/craftcms/cms/issues/15374))
- `craft\services\Config::setDotEnvVar()` now accepts `false` for its `value` argument, which removes the environment variable from the `.env` file.
- Deprecated `craft\fields\BaseRelationField::$localizeRelations`.
- Deprecated `craft\fields\Url`, which is now an alias for `craft\fields\Link`.
- Deprecated `craft\services\Relations`.
- Deprecated `craft\web\assets\elementresizedetector\ElementResizeDetectorAsset`.
- Added `Craft.EnvVarGenerator`.
- Added `Craft.endsWith()`.
- Added `Craft.removeLeft()`.
- Added `Craft.removeRight()`.
- Added `Craft.ui.addAttributes()`.
- `Craft.ElementEditor` now triggers a `checkActivity` event each time author activity is fetched. ([#15237](https://github.com/craftcms/cms/discussions/15237))
- `Craft.NestedElementManager` now triggers an `afterInit` event after initialization. ([#15470](https://github.com/craftcms/cms/issues/15470))
- `Craft.ensureEndsWith()` now has a `caseInsensitive` argument.
- `Craft.ensureStartsWith()` now has a `caseInsensitive` argument.
- `Craft.startsWith()` is no longer deprecated, and now has a `caseInsensitive` argument.
- Added `Garnish.once()`, for handling a class-level event only once.
- Checkbox selects now support passing a `targetPrefix` setting.
- Component chips now support passing a `showHandle` setting.
- Component selects now support passing a `showHandles` setting.

### System
- Added core support for SSO (Enterprise only).
- The control panel now displays Ajax response-defined error messages when provided, rather than a generic “server error” message. ([#15292](https://github.com/craftcms/cms/issues/15292))
- Craft no longer sets the `Permissions-Policy` header on control panel responses. ([#15348](https://github.com/craftcms/cms/issues/15348))
- Control panel `resize` events now use ResizeObserver.
- Twig templates no longer attempt to preload singles for global variable names. ([#15468](https://github.com/craftcms/cms/pull/15468))
- Craft no longer ensures that the `cpresources` folder is writable.
- Front-end queue runner scripts are now injected before the `</body>` tag, rather than at the end of the response HTML.
- Nested entries created for Matrix fields set to inline-editable block mode now begin life as unpublished drafts. ([#15418](https://github.com/craftcms/cms/issues/15418))
- Custom fields are now soft-deleted initially.
- `graphql/api` requests no longer update the schema’s `lastUsed` timestamp if it was already updated within the last minute. ([#15464](https://github.com/craftcms/cms/issues/15464))
- Updated Yii to 2.0.51.
- Updated yii2-debug to 2.1.25.
- Updated svg-sanitizer to 0.19.
- Fixed a bug where error messages returned by the `users/send-password-reset-email` action weren’t accounting for the `useEmailAsUsername` config setting. ([#15425](https://github.com/craftcms/cms/issues/15425))
- Fixed a bug where `$element->isNewForSite` was always `false` from fields’ `normalizeValue()` methods when propagating an element to a new site.
- Fixed a bug where `assets/generate-transforms` requests could generate the wrong transform, if another transform index with the same parameters existed. ([#15402](https://github.com/craftcms/cms/pull/15402), [#15477](https://github.com/craftcms/cms/pull/15477))
- Fixed a bug where element operations could cause deadlocks when multiple authors were working simultaneously. ([#15329](https://github.com/craftcms/cms/issues/15329))
- Fixed a bug where newly-created Matrix blocks could lose their disabled status if the owner element had validation errors and `autosaveDrafts` was disabled. ([#15418](https://github.com/craftcms/cms/issues/15418))
- Fixed a bug where customized settings for assets’ Temporary Uploads source were only being retained for the current user. ([#15424](https://github.com/craftcms/cms/issues/15424))
- Fixed a bug where it wasn’t possible to render element partial templates for assets, categories, or tags. ([#15426](https://github.com/craftcms/cms/issues/15426))

## 5.2.10 - 2024-08-05

- Fixed a bug where it wasn’t possible to render element partial templates for assets, categories, or tags. ([#15426](https://github.com/craftcms/cms/issues/15426))
- Fixed an error that could occur when deleting a nested element, if its owner wasn’t saved for the same site. ([#15290](https://github.com/craftcms/cms/issues/15290))
- Fixed a PHP error that could occur when running Codeception tests. ([#15445](https://github.com/craftcms/cms/issues/15445))
- Fixed a bug where `deleteAsset`, `deleteCategory`, `deleteEntry`, and `deleteTag` GraphQL mutations were returning `null` rather than `true` or `false`. ([#15465](https://github.com/craftcms/cms/issues/15465))
- Fixed a styling issue. ([#15473](https://github.com/craftcms/cms/issues/15473))
- Fixed a bug where `exists()` element queries weren’t working if `distinct`, `groupBy`, `having,` or `union` params were set on them during query preparation. ([#15001](https://github.com/craftcms/cms/issues/15001), [#15223](https://github.com/craftcms/cms/pull/15223))
- Fixed a bug where users’ `username` properties weren’t getting set if `useEmailAsUsername` was enabled. ([#15475](https://github.com/craftcms/cms/issues/15475))
- Fixed a bug where columns added to element queries via `EVENT_BEFORE_PREPARE` were getting overridden for all core element types except entries. ([#15446](https://github.com/craftcms/cms/pull/15446))
- Fixed a bug where the “Sign in as” user action would redirect to the control panel even if the user didn’t have permission to access the control panel. ([#15449](https://github.com/craftcms/cms/issues/15449))
- Fixed a bug where the `utils/prune-orphaned-entries` command was deleting top-level entries. ([#15458](https://github.com/craftcms/cms/issues/15458))

## 5.2.9 - 2024-07-29

- Added `craft\helpers\Money::normalizeString()`.
- Updated web-auth/webauthn-lib to 4.9. ([#15377](https://github.com/craftcms/cms/issues/15377))
- Fixed a PHP error that occurred when making a field layout component conditional on a Time or CKEditor field. ([craftcms/ckeditor#267](https://github.com/craftcms/ckeditor/issues/267))
- Fixed an error that occurred when editing a user, if the current user didn’t have permission to edit the primary site. ([#15408](https://github.com/craftcms/cms/issues/15408))
- Fixed a bug where editable tables with single-select checkbox columns weren’t deselecting the selected option automatically. ([#15415](https://github.com/craftcms/cms/issues/15415))
- Fixed a styling issue. ([#15422](https://github.com/craftcms/cms/issues/15422))
- Fixed a bug where category groups’ Template settings weren’t being auto-populated for new groups.
- Fixed a bug where content changes created via `craft\base\Element::EVENT_AFTER_SAVE` weren’t getting saved, when an element was getting fully saved from an unsaved draft state. ([#15369](https://github.com/craftcms/cms/issues/15369))
- Fixed a bug where element exports were only including the first 100 results when no elements were selected. ([#15389](https://github.com/craftcms/cms/issues/15389))
- Fixed a stying bug. ([#15405](https://github.com/craftcms/cms/issues/15405))
- Fixed a bug where custom element sources’ Sites settings were getting cleared out. ([#15406](https://github.com/craftcms/cms/issues/15406))
- Fixed an error that occurred if a custom element source wasn’t enabled for any sites. ([#15406](https://github.com/craftcms/cms/issues/15406))
- Fixed a bug where custom sources that weren’t enabled for any sites would be shown for all sites.
- Fixed a SQL error that could occur when upgrading to Craft 5. ([#15407](https://github.com/craftcms/cms/pull/15407))
- Fixed a bug where user edit forms included a Username field if had been saved to the user field layout before `useEmailAsUsername` was enabled. ([#15401](https://github.com/craftcms/cms/issues/15401))
- Fixed a bug where Assets field buttons weren’t wrapping for narrow containers. ([#15419](https://github.com/craftcms/cms/issues/15419))
- Fixed a PHP error that could occur after converting a custom field to a Money field. ([#15413](https://github.com/craftcms/cms/issues/15413))
- Fixed a bug where temp assets had a “Show in folder” action.
- Fixed a bug where edit pages didn’t have headings if the element didn’t have a title.
- Fixed a bug where tooltips for truncated element chips in the breadcrumbs were also getting truncated.
- Fixed a bug where it wasn’t possible to sort elements by custom field values in descending order. ([#15434](https://github.com/craftcms/cms/issues/15434))
- Fixed a PHP error that could occur when rendering an element partial template. ([#15426](https://github.com/craftcms/cms/issues/15426))
- Fixed a bug where scalar/single-column queries weren’t returning any results if they originated from a relation field’s value, and the field’s “Maintain hierarchy” setting was enabled. ([#15414](https://github.com/craftcms/cms/issues/15414))

## 5.2.8 - 2024-07-17

- Fixed a bug where element index result counts weren’t getting updated when the element list was refreshed but pagination was preserved. ([#15367](https://github.com/craftcms/cms/issues/15367))
- Fixed a SQL error that could occur when sorting by custom fields on MariaDB.
- Fixed a bug where embedded element indexes could include table columns for all custom fields associated with the element type. ([#15373](https://github.com/craftcms/cms/issues/15373))

## 5.2.7 - 2024-07-16

- `craft\helpers\UrlHelper::actionUrl()` now returns URLs based on the primary site’s base URL (if it has one), for console requests if the `@web` alias wasn’t explicitly defined.
- An exception is now thrown when attempting to save an entry that’s missing `sectionId` or `fieldId` + `ownerId` values. ([#15345](https://github.com/craftcms/cms/discussions/15345))
- Fixed a bug where it wasn’t possible to expand/collapse descendants of disabled table rows within element select modals. ([#15337](https://github.com/craftcms/cms/issues/15337))
- Fixed a bug where PhpStorm autocomplete wasn’t working when chaining custom field methods defined by `CustomFieldBehavior`. ([#15336](https://github.com/craftcms/cms/issues/15336))
- Fixed a bug where new nested entries created on newly-created elements weren’t getting duplicated to all other sites for the owner element. ([#15321](https://github.com/craftcms/cms/issues/15321))
- Fixed a bug where focus could jump unexpectedly when a slideout was opened. ([#15314](https://github.com/craftcms/cms/issues/15314))
- Fixed a bug where addresses were getting truncated within address cards. ([#15338](https://github.com/craftcms/cms/issues/15338))
- Fixed a bug where TOTP setup keys included an extra space at the end. ([#15349](https://github.com/craftcms/cms/issues/15349))
- Fixed a bug where input focus could automatically jump to slideout sidebars shortly after they were shown. ([#15314](https://github.com/craftcms/cms/issues/15314))
- Fixed an error that occurred if the SMTP mailer transport type was used, and the Hostname value was blank. ([#15342](https://github.com/craftcms/cms/discussions/15342))
- Fixed a bug where database DML changes weren’t getting rolled back after tests were run if the Codeception config had `transaction: true`. ([#7615](https://github.com/craftcms/cms/issues/7615))
- Fixed an error that could occur when saving recursively-nested elements. ([#15362](https://github.com/craftcms/cms/issues/15362))
- Fixed a styling issue. ([#15315](https://github.com/craftcms/cms/issues/15315))
- Fixed a bug where field status indicators within Matrix fields weren’t positioned correctly.
- Fixed a bug where Matrix changes could be lost if the `autosaveDrafts` config setting was set to `false`. ([#15353](https://github.com/craftcms/cms/issues/15353))

## 5.2.6 - 2024-07-11

> [!NOTE]
> Craft now sends no-cache headers for requests that generate/retrieve a CSRF token. If your Craft install is behind a static caching service like Cloudflare, enable the [asyncCsrfInputs](https://craftcms.com/docs/5.x/reference/config/general.html#asynccsrfinputs) config setting to avoid a significant cache hit reduction. ([#15293](https://github.com/craftcms/cms/pull/15293), [#15281](https://github.com/craftcms/cms/pull/15281))

- Craft now sends no-cache headers for any request that calls `craft\web\Request::getCsrfToken()`. ([#15293](https://github.com/craftcms/cms/pull/15293), [#15281](https://github.com/craftcms/cms/pull/15281))
- Fixed a bug where structures’ Max Levels settings weren’t being enforced when dragging elements with collapsed descendants. ([#15310](https://github.com/craftcms/cms/issues/15310))
- Fixed a bug where `craft\helpers\ElementHelper::isDraft()`, `isRevision()`, and `isDraftOrRevision()` weren’t returning `true` if a nested draft/revision element was passed in, but the root element was canonical. ([#15303](https://github.com/craftcms/cms/issues/15303))
- Fixed a bug where focus could be trapped within slideout sidebars. ([#15314](https://github.com/craftcms/cms/issues/15314))
- Fixed a bug where element slideout sidebars were included in the focus order when hidden. ([#15332](https://github.com/craftcms/cms/pull/15332))
- Fixed a bug where field status indicators weren’t visible on mobile viewports.
- Fixed a bug where sorting elements by custom field within element indexes wasn’t always working. ([#15297](https://github.com/craftcms/cms/issues/15297))
- Fixed a bug where asset bulk element actions were available when folders were selected. ([#15301](https://github.com/craftcms/cms/issues/15301))
- Fixed a bug where element thumbnails weren’t always getting loaded. ([#15299](https://github.com/craftcms/cms/issues/15299))
- Fixed an error that occurred when attempting to save a user via the <kbd>Command</kbd>/<kbd>Ctrl</kbd> + <kbd>S</kbd> keyboard shortcut within a slideout. ([#15307](https://github.com/craftcms/cms/issues/15307))
- Fixed a bug where “Delete heading” buttons within Customize Sources modals were getting text cursors. ([#15317](https://github.com/craftcms/cms/issues/15317))
- Fixed a bug where disclosure hint text wasn’t legible on hover. ([#15316](https://github.com/craftcms/cms/issues/15316))
- Fixed an error that occurred if the System Name was set to a nonexistent environment variable.
- Fixed a bug where custom table columns within element indexes weren’t getting updated automatically when table rows were refreshed.
- Fixed a bug where nested element indexes weren’t passing the `ownerId` param, when refreshing elements’ table rows.
- Fixed a bug where it wasn’t possible to tell if an element had been edited, if it was displayed within a nested element index table without a header column.
- Fixed an error that could occur if a field was removed from a field layout, if another field had been conditional based on it. ([#15328](https://github.com/craftcms/cms/issues/15328))

## 5.2.5 - 2024-07-02

- Craft now sends no-cache headers for any request that generates a CSRF token. ([#15281](https://github.com/craftcms/cms/pull/15281), [verbb/formie#1963](https://github.com/verbb/formie/issues/1963))
- Fixed a JavaScript error that occurred when creating a new custom element source, preventing the Default Sort and Default Table Columns fields from showing up.
- Fixed a bug where the control panel was getting asynchronous CSRF inputs if the `asyncCsrfInputs` config setting was enabled.
- Fixed a bug where Craft’s Twig implementation wasn’t respecting sandboxing rules for object properties. ([#15278](https://github.com/craftcms/cms/issues/15278))
- Fixed a bug where assets that the user wasn’t permitted to view could have a “Show in folder” action.
- Fixed focus management with element select inputs after elements were added or removed.
- Fixed a bug where it wasn’t possible to set `title` values on nested Matrix entries, when saving section entries via GraphQL. ([#15270](https://github.com/craftcms/cms/issues/15270))
- Fixed a SQL error that could occur if a `DECIMAL()` expression was passed into a query’s `select()` or `groupBy()` methods. ([#15271](https://github.com/craftcms/cms/issues/15271))
- Fixed a bug where the “Delete (with descendants)” element action wasn’t deleting descendants. ([#15273](https://github.com/craftcms/cms/issues/15273))
- Fixed an error that could occur when upgrading to Craft 5 if the database user didn’t have permission to disable foreign key constraints. ([#15262](https://github.com/craftcms/cms/issues/15262))

## 5.2.4.1 - 2024-06-27

- Fixed a JavaScript error. ([#15266](https://github.com/craftcms/cms/issues/15266))

## 5.2.4 - 2024-06-27

- Improved the styling of inactive users’ status indicators. ([#15195](https://github.com/craftcms/cms/issues/15195))
- Added `Garnish.once()` and `Garnish.Base::once()`, for registering event handlers that should only be triggered one time.
- Fixed a bug where Ajax requests stopped working after a user session expired and then was reauthenticated.
- Fixed an error that occurred if an element select input was initialized without a `name` value.
- Fixed a bug where Selectize inputs could be immediately focused and marked as dirty when opening an element editor slideout, if they were the first focusable element in the field layout. ([#15245](https://github.com/craftcms/cms/issues/15245))
- Fixed a bug where other author indicators weren’t shown for Craft Team.
- Fixed a bug where the Recent Entries widget wasn’t showing authors’ usernames for Craft Team.
- Fixed a bug where asset edit page URLs contained spaces if the asset filename contained spaces. ([#15236](https://github.com/craftcms/cms/issues/15236))
- Fixed a bug where element select inputs with `single` set to `true` would set existing elements’ input names ending in `[]`.
- Fixed a bug where element indexes could display “Nothing yet” at the bottom of populated table views. ([#15241](https://github.com/craftcms/cms/issues/15241))
- Fixed a bug where element edit pages initially showed the canonical element’s chip in the crumb bar, for provisional drafts. ([#15244](https://github.com/craftcms/cms/issues/15244))
- Fixed an error that occurred when opening an element’s editor slideout via its “Edit” action menu item, if the element had provisional changes. ([#15248](https://github.com/craftcms/cms/pull/15248))
- Fixed a bug where recursively-nested Matrix entries could be lost if multiple of them were edited, and not immediately saved. ([#15256](https://github.com/craftcms/cms/issues/15256))
- Fixed an error that could occur when upgrading to Craft 5 if the database user didn’t have permission to disable foreign key constraints. ([#15262](https://github.com/craftcms/cms/issues/15262))
- Fixed a bug where expanded sidebar navigations could overlap the main content on small screens. ([#15253](https://github.com/craftcms/cms/issues/15253))

## 5.2.3 - 2024-06-20

- Fixed MariaDB support. ([#15232](https://github.com/craftcms/cms/issues/15232))
- Fixed a potential vulnerability with TOTP authentication.
- Deprecated `craft\helpers\Db::prepareForJsonColumn()`.

## 5.2.2 - 2024-06-18

- Added `craft\base\conditions\BaseNumberConditionRule::$step`.
- Added `craft\helpers\Db::parseColumnPrecisionAndScale()`.
- Added `Garnish.muteResizeEvents()`.
- Fixed a JavaScript performance degradation bug. ([#14510](https://github.com/craftcms/cms/issues/14510))
- Fixed a bug where scalar element queries weren’t working if `distinct`, `groupBy`, `having,` or `union` params were set on them during query preparation. ([#15001](https://github.com/craftcms/cms/issues/15001))
- Fixed a bug where Edit Asset screens would warn about losing unsaved changes when navigating away, if the file was replaced but nothing else had changed.
- Fixed a bug where Edit Asset screens would show a notification with a “Reload” button after the file was replaced.
- Fixed a bug where Number fields’ condition rules weren’t allowing decimal values. ([#15222](https://github.com/craftcms/cms/issues/15222))
- Fixed a bug where Number field element query params didn’t respect decimal values. ([#15222](https://github.com/craftcms/cms/issues/15222))
- Fixed a bug where asset thumbnails weren’t getting updated after using the “Replace file” action. ([#15217](https://github.com/craftcms/cms/issues/15217))

## 5.2.1 - 2024-06-17

- Element index table views now show provisional drafts’ canonical elements’ values for the “Ancestors”, “Parent”, “Link”, “URI”, “Revision Notes”, “Last Edited By”, and “Drafts” columns.
- Improved the styling of disabled status indicators. ([#15195](https://github.com/craftcms/cms/issues/15195), [#15206](https://github.com/craftcms/cms/pull/15206))
- Added `craft\web\View::getModifiedDeltaNames()`.
- `craft\web\View::registerDeltaName()` now has a `$forceModified` argument.
- Fixed a bug where changed field values could be forgotten within Matrix fields, if a validation error occurred. ([#15190](https://github.com/craftcms/cms/issues/15190))
- Fixed a bug where the `graphql/create-token` command was prompting for the schema name, when it meant the token name. ([#15205](https://github.com/craftcms/cms/pull/15205))
- Fixed a bug where keyboard shortcuts weren’t getting registered properly for modals and slideouts opened via a disclosure menu. ([#15209](https://github.com/craftcms/cms/issues/15209))
- Fixed a styling issue with the global sidebar when collapsed. ([#15186](https://github.com/craftcms/cms/issues/15186))
- Fixed a bug where it wasn’t possible to query for authors via GraphQL on the Team edition. ([#15187](https://github.com/craftcms/cms/issues/15187))
- Fixed a bug where it wasn’t possible to close elevated session modals. ([#15202](https://github.com/craftcms/cms/issues/15202))
- Fixed a bug where element chips and cards were displaying provisional draft data even if the current user didn’t create the draft. ([#15208](https://github.com/craftcms/cms/issues/15208))
- Fixed a bug where element indexes weren’t displaying structured elements correctly if they had a provisional draft. ([#15214](https://github.com/craftcms/cms/issues/15214))

## 5.2.0 - 2024-06-12

### Content Management
- Live Preview now supports tabs, UI elements, and tab/field conditions. ([#15112](https://github.com/craftcms/cms/pull/15112))
- Live Preview now has a dedicated “Save” button. ([#15112](https://github.com/craftcms/cms/pull/15112))
- It’s now possible to edit assets’ alternative text from the Assets index page. ([#14893](https://github.com/craftcms/cms/discussions/14893))
- Double-clicking anywhere within a table row on an element index page will now open the element’s editor slideout. ([#14379](https://github.com/craftcms/cms/discussions/14379))
- Element index checkboxes no longer have a lag when deselected, except within element selection modals. ([#14896](https://github.com/craftcms/cms/issues/14896))
- Relational field condition rules no longer factor in the target elements’ statuses or sites. ([#14989](https://github.com/craftcms/cms/issues/14989))
- Element cards now display provisional changes, with an “Edited” label. ([#14975](https://github.com/craftcms/cms/pull/14975))
- Improved mobile styling. ([#14910](https://github.com/craftcms/cms/pull/14910))
- Improved the look of slideouts.
- Table views within element index pages are no longer scrolled directly. ([#14927](https://github.com/craftcms/cms/pull/14927))
- Improved the look of user gradicons when selected.
- “Save and continue editing” actions now restore the page’s scroll position on reload.
- “Remove” element actions within relational fields will now remove all selected elements, if the target element is selected. ([#15078](https://github.com/craftcms/cms/issues/15078))
- Action menus are now displayed within the page toolbar, rather than in the breadcrumbs. ([#14913](https://github.com/craftcms/cms/discussions/14913), [#15070](https://github.com/craftcms/cms/pull/15070))
- Site menus within element selector modals now filter out sites that don’t have any sources. ([#15091](https://github.com/craftcms/cms/discussions/15091))
- The meta sidebar toggle has been moved into the gutter between the content pane and meta sidebar. ([#15117](https://github.com/craftcms/cms/pull/15117))
- Element indexes will now show a confirmation dialog when cancelling a bulk inline edit. ([#15139](https://github.com/craftcms/cms/issues/15139), [#15142](https://github.com/craftcms/cms/pull/15142))
- Matrix fields in cards view and Addresses fields now show which nested entries/addresses contain validation errors. ([#15161](https://github.com/craftcms/cms/issues/15161))
- Nested entry edit pages now redirect to their owner element’s edit page. ([#15169](https://github.com/craftcms/cms/issues/15169))

### Accessibility
- Added the “Status” column option to category, entry, and user indexes. ([#14968](https://github.com/craftcms/cms/pull/14968))
- Element cards now display a textual status label rather than just the indicator. ([#14968](https://github.com/craftcms/cms/pull/14968))
- Darkened the color of page sidebar toggle icons to meet the minimum contrast for UI components.
- Darkened the color of context labels to meet the minimum contrast for text.
- Darkened the color of footer links to meet the minimum contrast for text.
- Set the language of the Craft edition in the footer, to improve screen reader pronunciation for non-English languages.
- The accessible name of “Select site” buttons is now translated to the current language.
- Improved the accessibility of two-step verification steps on the control panel login screen. ([#15145](https://github.com/craftcms/cms/pull/15145))
- Improved the accessibility of global nav items with subnavs. ([#15006](https://github.com/craftcms/cms/issues/15006))
- The secondary nav is now kept open during source selection for mobile viewports, preventing focus from being dropped. ([#14946](https://github.com/craftcms/cms/pull/14946))
- User edit screens’ document titles have been updated to describe the page purpose. ([#14946](https://github.com/craftcms/cms/pull/14946))
- Improved the styling of selected global nav items. ([#15061](https://github.com/craftcms/cms/pull/15061))

### Administration
- Added the `--format` option to the `db/backup` and `db/restore` commands for PostgreSQL installs. ([#14931](https://github.com/craftcms/cms/pull/14931))
- The `db/restore` command now autodetects the backup format for PostgreSQL installs, if `--format` isn’t passed. ([#14931](https://github.com/craftcms/cms/pull/14931))
- The `install` command and web-based installer now validate the existing project config files at the outset, and abort installation if there are any issues.
- The `resave/entries` command now has an `--all-sections` flag.
- The web-based installer now displays the error message when installation fails.
- Edit Entry Type pages now have a “Delete” action. ([#14983](https://github.com/craftcms/cms/discussions/14983))
- After creating a new field, field layout designers now set their search value to the new field’s name. ([#15080](https://github.com/craftcms/cms/discussions/15080))
- GraphQL schema edit pages now have a “Save and continue editing” alternate action.
- Volumes’ “Subpath” and “Transform Subpath” settings can now be set to environment variables. ([#15087](https://github.com/craftcms/cms/discussions/15087))
- The system edition can now be defined by a `CRAFT_EDITION` environment variable. ([#15094](https://github.com/craftcms/cms/discussions/15094))
- The rebrand assets path can now be defined by a `CRAFT_REBRAND_PATH` environment variable. ([#15110](https://github.com/craftcms/cms/pull/15110))

### Development
- Added the `{% expires %}` tag, which simplifies setting cache headers on the response. ([#14969](https://github.com/craftcms/cms/pull/14969))
- Added the `withCustomFields` element query param. ([#15003](https://github.com/craftcms/cms/pull/15003))
- Entry queries now support passing `*` to the `section` param, to filter the results to all section entries. ([#14978](https://github.com/craftcms/cms/discussions/14978))
- Element queries now support passing an element instance, or an array of element instances/IDs, to the `draftOf` param.
- Added `craft\elements\ElementCollection::find()`, which can return an element or elements in the collection based on a given element or ID. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- Added `craft\elements\ElementCollection::fresh()`, which reloads each of the collection elements from the database. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- The `collect()` Twig function now returns a `craft\elements\ElementCollection` instance if all of the items are elements.
- `craft\elements\ElementCollection::contains()` now returns `true` if an element is passed in and the collection contains an element with the same ID and site ID; or if an integer is passed in and the collection contains an element with the same ID. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::countBy()`, `collapse()`, `flatten()`, `keys()`, `pad()`, `pluck()`, and `zip()` now return an `Illuminate\Support\Collection` object. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::diff()` and `intersect()` now compare the passed-in elements to the collection elements by their IDs and site IDs. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::flip()` now throws an exception, as element objects can’t be used as array keys. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::map()` and `mapWithKeys()` now return an `Illuminate\Support\Collection` object, if any of the mapped values aren’t elements. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::merge()` now replaces any elements in the collection with passed-in elements, if their ID and site ID matches. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::only()` and `except()` now compare the passed-in values to the collection elements by their IDs, if an integer or array of integers is passed in. ([#15023](https://github.com/craftcms/cms/discussions/15023))
- `craft\elements\ElementCollection::unique()` now returns all elements with unique IDs, if no key is passed in. ([#15023](https://github.com/craftcms/cms/discussions/15023))

### Extensibility
- Improved type definitions for `craft\db\Query`, element queries, and `craft\elements\ElementCollection`.
- Added `craft\base\NestedElementTrait::$updateSearchIndexForOwner`.
- Added `craft\db\getBackupFormat()`.
- Added `craft\db\getRestoreFormat()`.
- Added `craft\db\setBackupFormat()`.
- Added `craft\db\setRestoreFormat()`.
- Added `craft\enums\Color::tryFromStatus()`.
- Added `craft\events\InvalidateElementcachesEvent::$element`.
- Added `craft\fields\BaseRelationField::existsQueryCondition()`.
- Added `craft\helpers\Cp::componentStatusIndicatorHtml()`.
- Added `craft\helpers\Cp::componentStatusLabelHtml()`.
- Added `craft\helpers\Cp::statusLabelHtml()`.
- Added `craft\helpers\DateTimeHelper::relativeTimeStatement()`.
- Added `craft\helpers\DateTimeHelper::relativeTimeToSeconds()`.
- Added `craft\helpers\ElementHelper::postEditUrl()`.
- Added `craft\helpers\ElementHelper::swapInProvisionalDrafts()`.
- Added `craft\helpers\StringHelper::indent()`.
- Added `craft\models\Volume::getTransformSubpath()`.
- Added `craft\models\Volume::setTransformSubpath()`.
- Added `craft\queue\Queue::getJobId()`.
- Added `craft\web\twig\SafeHtml`, which can be implemented by classes whose `__toString()` method should be considered HTML-safe by Twig.
- `craft\base\Element::defineTableAttributes()` now returns common attribute definitions used by most element types.
- `craft\elements\ElementCollection::with()` now supports collections made up of multiple element types.
- `craft\models\Volume::getSubpath()` now has a `$parse` argument.
- `craft\services\Drafts::applyDraft()` now has a `$newAttributes` argument.
- Added the `reloadOnBroadcastSave` setting to `Craft.ElementEditor`. ([#14814](https://github.com/craftcms/cms/issues/14814))
- Added the `waitForDoubleClicks` setting to `Garnish.Select`, `Craft.BaseElementIndex`, and `Craft.BaseElementIndexView`.

### System
- Improved overall system performance. ([#15003](https://github.com/craftcms/cms/pull/15003))
- Improved the performance of `exists()` element queries.
- Improved the performance of `craft\base\Element::toArray()`.
- The Debug Toolbar now pre-serializes objects stored as request parameters, fixing a bug where closures could prevent the entire Request panel from showing up. ([#14982](https://github.com/craftcms/cms/discussions/14982))
- Batched queue jobs now verify that they are still reserved before each step, and before spawning additional batch jobs. ([#14986](https://github.com/craftcms/cms/discussions/14986))
- The search keyword index is now updated for owner elements, when a nested element is saved directly which belongs to a searchable custom field. 
- Updated Yii to 2.0.50. ([#15124](https://github.com/craftcms/cms/issues/15124))
- Updated inputmask to 5.0.9.
- Fixed a bug where the `users/login` action wasn’t checking if someone was already logged in. ([#15168](https://github.com/craftcms/cms/issues/15168))
- Fixed a bug where exceptions due to missing templates weren’t being thrown when rendering an element partial. ([#15176](https://github.com/craftcms/cms/issues/15176))

## 5.1.10 - 2024-06-07

- Fixed an error that could occur if a Local filesystem wasn’t configured with a base path.
- Fixed a bug where some entries could be missing content after upgrading to Craft 5. ([#15150](https://github.com/craftcms/cms/issues/15150))
- Fixed a bug where it wasn’t always possible to add new entries to Matrix fields in inline-editable blocks view, if the field’s Max Entries setting had been reached before page load. ([#15158](https://github.com/craftcms/cms/issues/15158))
- Fixed an error that could occur when rendering the “My Drafts” widget. ([#14749](https://github.com/craftcms/cms/issues/14749))

## 5.1.9 - 2024-06-05

- Fixed a bug where the `db/backup` command could fail on Windows. ([#15090](https://github.com/craftcms/cms/issues/15090))
- Fixed an error that could occur when applying project config changes if a site was deleted. ([#14373](https://github.com/craftcms/cms/issues/14373))
- Fixed an error that could occur when creating an entry via a slideout, if the slideout was submitted before the entry was autosaved. ([#15134](https://github.com/craftcms/cms/pull/15134))
- Fixed a bug where upgrading from Craft CMS 4.4 was allowed even though the migrations assumed 4.5 or later was installed. ([#15133](https://github.com/craftcms/cms/issues/15133))
- Fixed an error that occurred when bulk inline editing an unpublished draft. ([#15138](https://github.com/craftcms/cms/issues/15138))

## 5.1.8 - 2024-06-03

- Added `craft\helpers\Gql::isIntrospectionQuery()`.
- `craft\helpers\Html::id()` now allows IDs to begin with numbers. ([#15066](https://github.com/craftcms/cms/issues/15066))
- Fixed a bug where some condition rules weren’t getting added when applying project config changes, if they depended on another component which hadn’t been added yet. ([#15037](https://github.com/craftcms/cms/issues/15037))
- Fixed a bug where entry type condition rules prefixed their option labels with section names. ([#15075](https://github.com/craftcms/cms/issues/15075))
- Fixed a bug where GraphQL queries could be misidentified as introspection queries. ([#15100](https://github.com/craftcms/cms/issues/15100))
- Fixed an error that could occur when calling `craft\base\FieldLayoutComponent::getAttributes()` if the `$elementType` property wasn’t set yet. ([#15074](https://github.com/craftcms/cms/issues/15074))
- Fixed a bug where nested entry titles weren’t getting included in the owner element’s search keywords. ([#15025](https://github.com/craftcms/cms/issues/15025))
- Fixed a bug where `craft\elements\Address::toArray()` would include a `saveOwnership` key in its response array.
- Fixed a bug where nested entry and address edit pages could have a “Delete for site” action.
- Fixed a bug where field layout designers weren’t displaying native fields in the library pane when a tab was removed that contained them. ([#15064](https://github.com/craftcms/cms/issues/15064))
- Fixed a bug where recent textual changes could be lost when creating a new inline-editable Matrix block, if the block was created before the autosave had a chance to initiate. ([#15069](https://github.com/craftcms/cms/issues/15069))
- Fixed a bug where the `users/create` command would fail without explaining why, when the maximum number of users had already been reached.
- Fixed a validation error that could occur when saving an entry on Craft Solo. ([#15082](https://github.com/craftcms/cms/issues/15082))
- Fixed an error that could occur on an element edit page, if a Matrix field’s Propagation Method was set to “Custom…”, but its Propagation Key Format wasn’t filled in.
- Fixed a bug where Matrix block invalidation errors weren’t getting grouped by block when set on the parent element, for blocks that didn’t have `uid` values. ([#15103](https://github.com/craftcms/cms/discussions/15103))
- Fixed a bug where auto-generated entry titles weren’t getting validated to ensure they weren’t too long. ([#15102](https://github.com/craftcms/cms/issues/15102))
- Fixed a bug where field conditions weren’t working reliably for nested entries within Matrix fields set to the inline-editable blocks view mode. ([#15104](https://github.com/craftcms/cms/issues/15104))
- Fixed a bug where the `serve` command could hang. ([#14977](https://github.com/craftcms/cms/issues/14977))
- Fixed a bug where nested entry edit pages would always redirect to the Entries index, even if they were nested under a different element type. ([#15101](https://github.com/craftcms/cms/issues/15101))
- Fixed an error that occurred when attempting to delete a global set without a field layout. ([#15123](https://github.com/craftcms/cms/issues/15123))

## 5.1.7 - 2024-05-25

- Scalar element queries no longer set their `$select` property to the scalar expression, fixing an error that could occur when executing scalar queries for relation fields. ([#15071](https://github.com/craftcms/cms/issues/15071))
- Fixed an error that occurred when upgrading to Craft 5 if a Matrix block type didn’t have any fields.
- Fixed an error that occurred when upgrading to Craft 5 if any Matrix block rows had invalid `primaryOwnerId` values. ([#15063](https://github.com/craftcms/cms/issues/15063))

## 5.1.6 - 2024-05-23

- Added `craft\services\Fields::getRelationalFieldTypes()`.
- Fixed a bug where `craft\helpers\Typecast::properties()` wasn’t typecasting numeric strings to ints for `int|string|null` properties. ([#14618](https://github.com/craftcms/cms/issues/14618))
- Fixed a bug where “Related To” conditions weren’t allowing entries to be selected. ([#15058](https://github.com/craftcms/cms/issues/15058))

## 5.1.5 - 2024-05-22

- Scalar element queries now set `$select` to the scalar expression, and `$orderBy`, `$limit`, and `$offset` to `null`, on the element query. ([#15001](https://github.com/craftcms/cms/issues/15001))
- Added `craft\fieldlayoutelements\TextareaField::inputTemplateVariables()`.
- Fixed a bug where `craft\helpers\Assets::prepareAssetName()` wasn’t sanitizing filenames if `$preventPluginModifications` was `true`.
- Fixed a bug where element queries’ `count()` methods were factoring in the `limit` param when searching with `orderBy` set to `score`. ([#15001](https://github.com/craftcms/cms/issues/15001))
- Fixed a bug where soft-deleted structure data associated with elements that belonged to a revision could be deleted by garbage collection. ([#14995](https://github.com/craftcms/cms/pull/14995))
- Fixed a bug where element edit pages’ scroll positions weren’t always retained when automatically refreshed.
- Fixed a bug where the `up` command could remove component name comments from the project config YAML files, for newly-added components. ([#15012](https://github.com/craftcms/cms/issues/15012))
- Fixed a bug where assets’ Alternative Text fields didn’t expand to match the content height. ([#15026](https://github.com/craftcms/cms/issues/15026))
- Fixed a bug where `craft\helpers\UrlHelper::isAbsoluteUrl()` was returning `true` for Windows file paths. ([#15043](https://github.com/craftcms/cms/issues/15043))
- Fixed an error that occurred on the current user’s Profile screen if they didn’t have permission to access the primary site. ([#15022](https://github.com/craftcms/cms/issues/15022))
- Fixed a bug where non-localizable elements’ edit screens were displaying a site breadcrumb.
- Fixed a bug where entry GraphQL queries weren’t available if only nested entry field queries were selected in the schema.
- Fixed a bug where chip labels could wrap unnecessarily. ([#15000](https://github.com/craftcms/cms/issues/15000), [#15017](https://github.com/craftcms/cms/pull/15017))
- Fixed a bug where date/time clear buttons could bleed out of their container. ([#15017](https://github.com/craftcms/cms/pull/15017))
- Fixed an error that occurred when editing an element, if any field layout conditions referenced a custom field that was no longer included in the layout. ([#14838](https://github.com/craftcms/cms/issues/14838))
- Fixed a “User not authorized to create this element.” error that could occur when creating a new entry within a Matrix field, if the field had Max Entries set. ([#15015](https://github.com/craftcms/cms/issues/15015))
- Fixed a bug where nested entries weren’t showing up within Matrix fields set to the element index view mode, when viewing entry revisions. ([#15038](https://github.com/craftcms/cms/pull/15038))
- Fixed the styling of element chips displayed within an element card. ([#15044](https://github.com/craftcms/cms/issues/15044))
- Fixed styling issues with inline-editing within element indexes. ([#15040](https://github.com/craftcms/cms/issues/15040), [#15049](https://github.com/craftcms/cms/pull/15049))
- Fixed a bug where sticky scrollbars could stop working when switching between element index sources. ([#15047](https://github.com/craftcms/cms/issues/15047))

## 5.1.4 - 2024-05-17

- Improved the performance of element indexes that contained asset thumbnails. ([#14760](https://github.com/craftcms/cms/issues/14760))
- Table views within element index pages are no longer scrolled directly. ([#14927](https://github.com/craftcms/cms/pull/14927), [#15010](https://github.com/craftcms/cms/pull/15010))
- Fixed a bug where `craft\elements\db\ElementQuery::exists()` would return `true` if `setCachedResult()` had been called, even if an empty array was passed.
- Fixed an infinite recursion bug that could occur when `craft\web\Response::redirect()` was called. ([#15014](https://github.com/craftcms/cms/pull/15014))
- Fixed a bug where `eagerly()` wasn’t working when a custom alias was passed in.
- Fixed an error that occurred on users’ Addresses screens. ([#15018](https://github.com/craftcms/cms/pull/15018))
- Fixed a bug where asset chips’ content wasn’t spanning the full width for Assets fields in large thumbnail mode. ([#14993](https://github.com/craftcms/cms/issues/14993))
- Fixed infinite scrolling on Structure element sources. ([#14992](https://github.com/craftcms/cms/issues/14992))
- Fixed right-to-left styling issues. ([#15019](https://github.com/craftcms/cms/pull/15019))

## 5.1.3 - 2024-05-14

- Fixed a SQL error that could occur when applying or rebuilding the project config.
- Fixed a bug where adjacent selected table rows were getting extra spacing in Firefox.
- Fixed a SQL error that could occur when creating revisions after garbage collection was run. ([#14309](https://github.com/craftcms/cms/issues/14309))
- Fixed a bug where the `serve` command wasn’t serving paths with non-ASCII characters. ([#14977](https://github.com/craftcms/cms/issues/14977))
- Fixed a bug where `craft\helpers\Html::explodeStyle()` and `normalizeTagAttributes()` weren’t handling styles with encoded images via `url()` properly. ([#14964](https://github.com/craftcms/cms/issues/14964))
- Fixed a bug where the `db/backup` command would fail if the destination path contained a space.
- Fixed a bug where entry selection modals could list all entries when no sources were available for the selected site. ([#14956](https://github.com/craftcms/cms/issues/14956))
- Fixed a bug where element cards could get duplicate status indicators. ([#14958](https://github.com/craftcms/cms/issues/14958))
- Fixed a bug where element chips could overflow their containers. ([#14924](https://github.com/craftcms/cms/issues/14924))
- Fixed a bug where soft-deleted elements that belonged to a revision could be deleted by garbage collection. ([#14967](https://github.com/craftcms/cms/pull/14967))
- Fixed a bug where disabled entries weren’t being displayed within Matrix fields in card view. ([#14973](https://github.com/craftcms/cms/issues/14973))
- Fixed a bug where users’ Permissions screen was inaccessible for the Team edition. ([#14976](https://github.com/craftcms/cms/issues/14976))
- Fixed a SQL error that could occur when attempting to update to Craft 5 for the second time. ([#14987](https://github.com/craftcms/cms/issues/14987))

## 5.1.2 - 2024-05-07

- Fixed a bug where the `db/backup` command would prompt for password input on PostgreSQL. ([#14945](https://github.com/craftcms/cms/issues/14945))
- Fixed a bug where pressing <kbd>Shift</kbd> + <kbd>Spacebar</kbd> wasn’t reliably opening the asset preview modal on the Assets index page. ([#14943](https://github.com/craftcms/cms/issues/14943))
- Fixed a bug where pressing <kbd>Shift</kbd> + <kbd>Spacebar</kbd> within an asset preview modal wasn’t closing the modal.
- Fixed a bug where pressing arrow keys within asset preview modals wasn’t retargeting the preview modal to adjacent assets. ([#14943](https://github.com/craftcms/cms/issues/14943))
- Fixed a bug where entry selection modals could have a “New entry” button even if there weren’t any sections enabled for the selected site. ([#14923](https://github.com/craftcms/cms/issues/14923))
- Fixed a bug where element autosaves weren’t always working if a Matrix field needed to automatically create a nested entry. ([#14947](https://github.com/craftcms/cms/issues/14947))
- Fixed a JavaScript warning. ([#14952](https://github.com/craftcms/cms/pull/14952))
- Fixed XSS vulnerabilities.

## 5.1.1 - 2024-05-02

- Fixed a bug where disclosure menus weren’t releasing their `scroll` and `resize` event listeners on hide. ([#14911](https://github.com/craftcms/cms/pull/14911), [#14510](https://github.com/craftcms/cms/issues/14510))
- Fixed a bug where it was possible to delete entries from Matrix fields which were configured to display nested entries statically. ([#14904](https://github.com/craftcms/cms/issues/14904), [#14915](https://github.com/craftcms/cms/pull/14915))
- Fixed an error that could occur when creating a nested entry in a Matrix field. ([#14915](https://github.com/craftcms/cms/pull/14915))
- Fixed a bug where Matrix fields’ “Max Entries” settings were taking the total number of nested entries across all sites into account, rather than just the nested entries for the current site. ([#14932](https://github.com/craftcms/cms/issues/14932))
- Fixed a bug where nested entry draft data could get corrupted when a draft was created for the owner element.
- Fixed a bug where Matrix and Addresses fields could show drafts of their nested elements when in card view.
- Fixed a bug where nested elements’ breadcrumbs could include the draft label, styled like it was part of the element’s title.
- Fixed a bug where action buttons might not work for nested entries in Matrix fields set to card view. ([#14915](https://github.com/craftcms/cms/pull/14915))
- Fixed the styling of tag chips within Tags fields. ([#14916](https://github.com/craftcms/cms/issues/14916))
- Fixed a bug where field layout component settings slideouts’ footers had extra padding.
- Fixed a bug where MySQL backups weren’t restorable on certain environments. ([#14925](https://github.com/craftcms/cms/pull/14925))
- Fixed a bug where `app/resource-js` requests weren’t working for guest requests. ([#14908](https://github.com/craftcms/cms/issues/14908))
- Fixed a JavaScript error that occurred after creating a new field within a field layout designer. ([#14933](https://github.com/craftcms/cms/issues/14933))

## 5.1.0 - 2024-04-30

### Content Management
- Sort options are now sorted alphabetically within element indexes, and custom fields’ options are now listed in a “Fields” group. ([#14725](https://github.com/craftcms/cms/issues/14725))
- Unselected table column options are now sorted alphabetically within element indexes.
- Table views within element index pages are now scrolled directly, so that their horizontal scrollbars are always visible without scrolling to the bottom of the page. ([#14765](https://github.com/craftcms/cms/issues/14765))
- Element tooltips now appear after a half-second delay. ([#14836](https://github.com/craftcms/cms/issues/14836))
- Thumbnails within element cards are slightly larger.
- Improved element editor page styling on mobile. ([#14898](https://github.com/craftcms/cms/pull/14898), [#14885](https://github.com/craftcms/cms/issues/14885))

### User Management
- Team edition users are no longer required to be admins.
- Added the “User Permissions” settings page for managing the permissions of non-admin Team edition users. ([#14768](https://github.com/craftcms/cms/discussions/14768))

### Administration
- Element conditions within field layout designers’ component settings now only list custom fields present in the current field layout. ([#14787](https://github.com/craftcms/cms/issues/14787))
- Improved the behavior of the URI input within Edit Route modals. ([#14884](https://github.com/craftcms/cms/issues/14884))
- The “Upgrade Craft CMS” page in the Plugin Store no longer lists unsupported editions.
- Added the `asyncCsrfInputs` config setting. ([#14625](https://github.com/craftcms/cms/pull/14625))
- Added the `backupCommandFormat` config setting. ([#14897](https://github.com/craftcms/cms/pull/14897))
- The `backupCommand` config setting can now be set to a closure, which will be passed a `mikehaertl\shellcommand\Command` object. ([#14897](https://github.com/craftcms/cms/pull/14897))
- Added the `safeMode` config setting. ([#14734](https://github.com/craftcms/cms/pull/14734))
- `resave` commands now support an `--if-invalid` option. ([#14731](https://github.com/craftcms/cms/issues/14731))
- Improved the styling of conditional tabs and UI elements within field layout designers.

### Extensibility
- Added `craft\conditions\ConditionInterface::getBuilderConfig()`.
- Added `craft\controllers\EditUserTrait`. ([#14789](https://github.com/craftcms/cms/pull/14789))
- Added `craft\controllers\UsersController::EVENT_DEFINE_EDIT_SCREENS`. ([#14789](https://github.com/craftcms/cms/pull/14789))
- Added `craft\elements\conditions\ElementConditionInterface::setFieldLayouts()`.
- Added `craft\events\DefineEditUserScreensEvent`. ([#14789](https://github.com/craftcms/cms/pull/14789))
- Added `craft\helpers\Cp::parseTimestampParam()`.
- Added `craft\models\FieldLayoutTab::labelHtml()`.
- Added `craft\services\ProjectConfig::getAppliedChanges()`. ([#14851](https://github.com/craftcms/cms/discussions/14851))
- Added `craft\web\Request::getBearerToken()`. ([#14784](https://github.com/craftcms/cms/pull/14784))
- Added `craft\db\CoalesceColumnsExpression`.
- Added `craft\db\ExpressionBuilder`.
- Added `craft\db\ExpressionInterface`.
- `craft\base\NameTrait::prepareNamesForSave()` no longer updates the name properties if `fullName`, `firstName`, and `lastName` are already set. ([#14665](https://github.com/craftcms/cms/issues/14665))
- `craft\helpers\Typecast::properties()` now typecasts numeric strings to integers, for `int|string` properties. ([#14618](https://github.com/craftcms/cms/issues/14618))
- Added `Craft.MatrixInput.Entry`. ([#14730](https://github.com/craftcms/cms/pull/14730))

### System
- Batched queue jobs now set their progress based on the total progress across all batches, rather than just the current batch. ([#14817](https://github.com/craftcms/cms/pull/14817))
- Fixed a bug where ordering by a custom field would only partially work, if the custom field was included in multiple field layouts for the resulting elements. ([#14821](https://github.com/craftcms/cms/issues/14821))
- Fixed a bug where element conditions within field layout designers’ component settings weren’t listing custom fields which were just added to the layout. ([#14787](https://github.com/craftcms/cms/issues/14787))
- Fixed a bug where asset thumbnails within element cards were blurry. ([#14866](https://github.com/craftcms/cms/issues/14866))
- Fixed a styling issue with Categories and Entries fields when “Maintain Hierarchy” was enabled.
- Fixed a bug where Delete actions weren’t working in admin tables. ([craftcms/commerce#3444](https://github.com/craftcms/commerce/issues/3444))

## 5.0.6 - 2024-04-29

- Fixed a bug where element caches weren’t getting invalidated when an element was moved within a structure. ([#14846](https://github.com/craftcms/cms/issues/14846))
- Fixed a bug where CSV’s header rows weren’t using the configured delimiter. ([#14855](https://github.com/craftcms/cms/issues/14855))
- Fixed a bug where editable table cell text styling could change after initial focus. ([#14857](https://github.com/craftcms/cms/issues/14857))
- Fixed a bug where conditions could list rules with duplicate labels.
- Fixed a bug where admin tables weren’t displaying disabled statuses. ([#14861](https://github.com/craftcms/cms/issues/14861))
- Fixed a bug where clicking on drag handles within element index tables could select the element. ([#14669](https://github.com/craftcms/cms/issues/14669))
- Fixed a bug where nested related categories and entries weren’t directly removable, and could be unintentionally overwritten, when the Categories/Entries field’s “Maintain hierarchy” setting was enabled. ([#14843](https://github.com/craftcms/cms/issues/14843), [#14872](https://github.com/craftcms/cms/issues/14872))
- Fixed a SQL error that could occur on PostgreSQL. ([#14860](https://github.com/craftcms/cms/pull/14870))
- Fixed a bug where field layout designers were showing redundant field indicators, for fields with hidden labels. ([#14859](https://github.com/craftcms/cms/issues/14859))
- Fixed a bug where field type names weren’t sorted alphabetically when editing an existing field. ([#14858](https://github.com/craftcms/cms/issues/14858))
- Fixed a JavaScript error that could occur when removing elements from an element select input. ([#14873](https://github.com/craftcms/cms/pull/14873))
- Fixed a bug where queue jobs’ progress indicators in the control panel sidebar weren’t fully cleaned up when jobs were finished. ([#14856](https://github.com/craftcms/cms/issues/14856))
- Fixed a bug where errors weren’t getting logged. ([#14863](https://github.com/craftcms/cms/issues/14863))
- Fixed a bug where asset thumbnails could have the wrong aspect ratio. ([#14866](https://github.com/craftcms/cms/issues/14866))
- Fixed an infinite recursion bug that occurred when selecting elements, if no sources were enabled for the selected site. ([#14882](https://github.com/craftcms/cms/issues/14882))

## 5.0.5 - 2024-04-23

- Fixed a bug where the Database Backup utility was present when the `backupCommand` config setting was set to `false`.
- Fixed an error that occurred when running the `db/convert-charset` command, if any tables contained `char` or `varchar` foreign key columns. ([#14815](https://github.com/craftcms/cms/issues/14815))
- Fixed a bug where parsed first/last names could have different casing than the full name that was submitted. ([#14723](https://github.com/craftcms/cms/issues/14723))
- Fixed a bug where `craft\helpers\UrlHelper::isAbsoluteUrl()` was returning `false` for URLs with schemes other than `http` or `https`, such as `mailto` and `tel`. ([#14830](https://github.com/craftcms/cms/issues/14830))
- Fixed a JavaScript error that occurred when opening Live Preview, if an Assets field’s “Upload files” button had been pressed. ([#14832](https://github.com/craftcms/cms/issues/14832))
- Fixed a bug where Twig’s spread operator (`...`) wasn’t working with attribute accessors. ([#14827](https://github.com/craftcms/cms/issues/14827))
- Fixed a bug where element selection modals were only showing the first 100 elements. ([#14790](https://github.com/craftcms/cms/issues/14790))
- Fixed a PHP error that could occur on the Dashboard if any Quick Post widgets hadn’t been saved since before Craft 1.2. ([#14794](https://github.com/craftcms/cms/issues/14794))
- Fixed a bug where double-clicking on an inline Matrix block tab would cause it to expand/collapse. ([#14791](https://github.com/craftcms/cms/issues/14791))
- Fixed a bug where site breadcrumbs weren’t getting hyperlinked for installs with multiple site groups. ([#14802](https://github.com/craftcms/cms/issues/14802))
- Fixed a bug where element conditions were allowing custom field condition rules to be selected multiple times. ([#14809](https://github.com/craftcms/cms/issues/14809))
- Fixed a bug where relational fields within nested Matrix entries weren’t getting loaded via GraphQL. ([#14819](https://github.com/craftcms/cms/issues/14819))
- Fixed an error that occurred when creating an address within an Addresses field on a secondary site. ([#14829](https://github.com/craftcms/cms/issues/14829))
- Fixed a bug where SVG element icons weren’t visible in Safari. ([#14833](https://github.com/craftcms/cms/issues/14833))
- Fixed a bug where element sources were getting text cursors on hover in Safari. ([#14833](https://github.com/craftcms/cms/issues/14833))
- Fixed a bug where “Delete custom source” buttons within Customize Sources modals were getting text cursors on hover.
- Fixed a bug where Matrix fields that weren’t set to show cards in a grid were still getting a grid view when nested entries were created for the first time. ([#14840](https://github.com/craftcms/cms/issues/14840))
- Fixed a bug where related categories and entries weren’t removable when the Categories/Entries field’s “Maintain hierarchy” setting was enabled. ([#14843](https://github.com/craftcms/cms/issues/14843))
- Fixed a bug where Categories and Entries fields were showing the “View Mode” setting when “Maintain hierarchy” was enabled, despite it having no effect. ([#14847](https://github.com/craftcms/cms/pull/14847))

## 5.0.4 - 2024-04-10

- Fixed a bug where element queries with the `relatedTo` param set to a list of element IDs were overly complex.
- Fixed a bug where redundant Matrix block revisions were getting created.
- Fixed a bug where Twig’s spread operator (`...`) wasn’t working when the `preloadSingles` config setting was enabled. ([#14783](https://github.com/craftcms/cms/issues/14783))
- Fixed a bug where Live Preview wasn’t retaining the scroll position properly. ([#14218](https://github.com/craftcms/cms/issues/14218))

## 5.0.3 - 2024-04-09

- Fixed a bug where LTR and RTL characters weren’t getting stripped from sanitized asset filenames. ([#14711](https://github.com/craftcms/cms/issues/14711))
- Fixed a bug where admin table row reordering wasn’t working in Safari. ([#14752](https://github.com/craftcms/cms/issues/14752))
- Fixed a bug where the `utils/fix-field-layout-uids` command wasn’t looking at field layouts defined with a `fieldLayout` key in the project config.
- Fixed a bug where element indexes’ View menus could show the “Sort by” field when the structure view was selected. ([#14780](https://github.com/craftcms/cms/issues/14780))
- Fixed a bug where fields with overridden handles weren’t editable from element indexes. ([#14767](https://github.com/craftcms/cms/issues/14767))
- Fixed a bug where element chips within element cards were getting action menus and drag handles within relation fields. ([#14778](https://github.com/craftcms/cms/issues/14778))
- Fixed a bug where elements could display the wrong nested field value, if the field’s handle was overridden. ([#14767](https://github.com/craftcms/cms/issues/14767))
- Fixed a bug where entries’ Title fields weren’t showing a required indicator. ([#14773](https://github.com/craftcms/cms/issues/14773))

## 5.0.2 - 2024-04-05

- Fixed a bug where `craft\helpers\ElementHelper::siteStatusesForElement()` wasn’t working for soft-deleted elements. ([#14753](https://github.com/craftcms/cms/issues/14753))
- Fixed a bug where the Queue Manager was listing delayed jobs before others. ([#14755](https://github.com/craftcms/cms/discussions/14755))
- Fixed an error that occurred when editing elements without any preview targets. ([#14754](https://github.com/craftcms/cms/issues/14754))
- Fixed a bug where it wasn’t possible to delete global sets when the CKEditor plugin was installed. ([#14757](https://github.com/craftcms/cms/issues/14757))
- Fixed a SQL error that occurred when querying for elements ordered by search score on PostgreSQL. ([#14761](https://github.com/craftcms/cms/issues/14761))

## 5.0.1 - 2024-04-03

- Fixed a “Double-instantiating a checkbox select on an element” JavaScript warning. ([#14707](https://github.com/craftcms/cms/issues/14707))
- Fixed a bug where `craft\cache\DbCache` was attempting to store values beyond the `cache.data` column’s storage capacity.
- Fixed a bug where the Updates utility could include submit buttons without labels for abandoned plugins.
- Fixed a bug where “Admin” rules were available to user conditions in Solo and Team editions.
- Fixed a bug where entries’ “View in a new tab” breadcrumb actions were linking to the canonical entry URL when editing a draft or viewing a revision. ([#14705](https://github.com/craftcms/cms/issues/14705))
- Fixed a bug where Matrix blocks without labels had extra spacing above them in Live Preview. ([#14703](https://github.com/craftcms/cms/issues/14703))
- Fixed an error that occurred if the `collation` database connection setting was set to `utf8_*` on MySQL. ([#14332](https://github.com/craftcms/cms/issues/14332))
- Fixed a bug where element cards could overflow their containers within Live Preview. ([#14710](https://github.com/craftcms/cms/issues/14710))
- Fixed a bug where links within the Queue Manager utility weren’t styled like links. ([#14716](https://github.com/craftcms/cms/issues/14716))
- Fixed a bug where tooltips within element labels caused the element title to be read twice by screen readers.
- Fixed a styling issue when editing an entry without any meta fields. ([#14721](https://github.com/craftcms/cms/issues/14721))
- Fixed a bug where the `_includes/nav.twig` template wasn’t marking nested nav items as selected. ([#14735](https://github.com/craftcms/cms/pull/14735))
- Fixed issues with menu options’ hover styles.
- Fixed a bug where double-clicking on an element’s linked label or action button would cause its slideout to open, in addition to the link/button being activated. ([#14736](https://github.com/craftcms/cms/issues/14736))
- Fixed a bug where system icons whose names ended in numbers weren’t displaying. ([#14740](https://github.com/craftcms/cms/issues/14740))
- Fixed an error that could occur when creating a passkey. ([#14745](https://github.com/craftcms/cms/issues/14745))
- Fixed a bug where the “Utilities” global nav item could get two badge counts.
- Fixed a bug where custom fields whose previous types were missing would lose their values when updating to Craft 5.
- Fixed a bug where Dropdown fields could be marked as invalid on save, if the saved value was invalid and they were initially marked as changed (to the default value) on page load. ([#14738](https://github.com/craftcms/cms/pull/14738))
- Fixed a bug where double-clicking on an element’s label within an element selection modal wasn’t selecting the element. ([#14751](https://github.com/craftcms/cms/issues/14751))

## 5.0.0 - 2024-03-26

### Content Management
- Improved global sidebar styling. ([#14281](https://github.com/craftcms/cms/pull/14281))
- The global sidebar is now collapsible. ([#14281](https://github.com/craftcms/cms/pull/14281))
- It’s now possible to expand and collapse global sidebar items without navigating to them. ([#14313](https://github.com/craftcms/cms/issues/14313), [#14321](https://github.com/craftcms/cms/pull/14321))
- Redesigned the global breadcrumb bar to include quick links to other areas of the control panel, page context menus, and action menus. ([#13902](https://github.com/craftcms/cms/pull/13902))
- All elements can now have thumbnails, provided by Assets fields. ([#12484](https://github.com/craftcms/cms/discussions/12484), [#12706](https://github.com/craftcms/cms/discussions/12706))
- Element indexes and relational fields now have the option to use card views. ([#6024](https://github.com/craftcms/cms/pull/6024))
- Element indexes now support inline editing for some custom field values.
- Asset chips with large thumbnails now truncate long titles, and make the full title visible via a tooltip on hover/focus. ([#14462](https://github.com/craftcms/cms/discussions/14462), [#14502](https://github.com/craftcms/cms/pull/14502))
- Table columns now set a max with to force long lines to be truncated or wrap. ([#14514](https://github.com/craftcms/cms/issues/14514))
- Added the “Show in folder” asset index action, available when searching across subfolders. ([#14227](https://github.com/craftcms/cms/discussions/14227))
- The view states for nested element sources are now managed independently.
- Element chips and cards now include quick action menus. ([#13902](https://github.com/craftcms/cms/pull/13902))
- Entry edit pages now include quick links to other sections’ index sources.
- Asset edit pages now include quick links to other volumes’ index sources.
- Assets’ Alternative Text fields are now translatable. ([#11576](https://github.com/craftcms/cms/issues/11576))
- Entries can now have multiple authors. ([#12380](https://github.com/craftcms/cms/pull/12380))
- Entry chips, cards, and blocks are now tinted according to their entry type’s color. ([#14187](https://github.com/craftcms/cms/pull/14187))
- Quick Post widgets now create entries via slideouts. ([#14228](https://github.com/craftcms/cms/pull/14228))
- Slideout sidebars are now always toggleable; not just when the slideout is too narrow to show the sidebar alongside the content. ([#14418](https://github.com/craftcms/cms/pull/14418))
- Element slideouts now show validation summaries at the top of each tab. ([#14436](https://github.com/craftcms/cms/pull/14436))
- Element slideouts’ “Cancel” buttons now get relabelled as “Close” when editing a provisional draft.
- The “Save as a new entry” action is now available to all users with the “Create entries” permission, and will create a new unpublished draft rather than a fully-saved entry. ([#9577](https://github.com/craftcms/cms/issues/9577), [#10244](https://github.com/craftcms/cms/discussions/10244))
- Entry conditions can now have a “Matrix field” rule. ([#13794](https://github.com/craftcms/cms/discussions/13794))
- Money field condition rules now use money inputs. ([#14148](https://github.com/craftcms/cms/pull/14148))
- Inline-editable Matrix blocks now support multiple tabs. ([#8500](https://github.com/craftcms/cms/discussions/8500), [#14139](https://github.com/craftcms/cms/issues/14139))
- Inline-editable Matrix blocks have been redesigned to be visually lighter. ([#14187](https://github.com/craftcms/cms/pull/14187))
- Inline-editable Matrix blocks now include “Open in a new tab” action items.
- Matrix fields set to the inline-editable blocks view mode no longer show inline entry-creation buttons unless there’s a single entry type. ([#14187](https://github.com/craftcms/cms/pull/14187))
- Selected elements within relational fields now include a dedicated drag handle.
- Selected assets within Assets fields no longer open the file preview modal when their thumbnail is clicked on. The “Preview file” quick action, or the <kbd>Shift</kbd> + <kbd>Spacebar</kbd> keyboard shortcut, can be used instead.
- Improved the styling of element chips.
- Improved checkbox-style deselection behavior for control panel items, to account for double-clicks.
- Table views are no longer available for element indexes on mobile.
- Added the “Address Line 3” address field. ([#14318](https://github.com/craftcms/cms/discussions/14318))
- Address conditions now have “Address Line 1”, “Address Line 2”, “Address Line 3”, “Administrative Area”, “Country”, “Dependent Locality”, “First Name”, “Full Name”, “Last Name”, “Locality”, “Organization Tax ID”, “Organization”, “Postal Code”, and “Sorting Code” rules.
- Added live conditional field support to user edit pages and inline-editable Matrix blocks. ([#14115](https://github.com/craftcms/cms/pull/14115), [#14223](https://github.com/craftcms/cms/pull/14223))
- Earth icons are now localized based on the system time zone.

### User Management
- Added two-step verification support, with built-in “Authenticator App” (TOTP) and “Recovery Codes” methods. Additional methods can be provided by plugins.
- Added a “Require Two-Step Verification” system setting, which can be set to “All users”, “Admins”, and individual user groups.
- Added passkey support (authentication via fingerprint or facial recognition).
- User account settings are now split into “Profile”, “Addresses”, and “Permissions” pages, plus “Password & Verification” and “Passkeys” pages when editing one’s own account.
- Users’ “Username”, “Full Name”, “Photo”, and “Email” native fields can now be managed via the user field layout, and now show up alongside custom fields within user slideouts.
- Users with more than 50 addresses will now display them as a paginated element index.
- New users are now created in an unpublished draft state, so adding a user photo, addresses, and permissions can each be done before the user is fully saved.
- The login page now includes a “Sign in with a passkey” button.
- The login modal and elevated session modal have been redesigned to be consistent with the login page.
- User sessions are now treated as elevated immediately after login, per the `elevatedSessionDuration` config setting.

### Accessibility
- Added the “Disable autofocus” user accessibility preference. ([#12921](https://github.com/craftcms/cms/discussions/12921))
- Improved source item navigation for screen readers. ([#12054](https://github.com/craftcms/cms/pull/12054))
- Content tab menus are now implemented as disclosure menus. ([#12963](https://github.com/craftcms/cms/pull/12963))
- Element selection modals now show checkboxes for selectable elements.
- Elements within relational fields are no longer focusable at the container level.
- Relational fields now use the proper list semantics.
- Improved the accessibility of the login page, login modal, and elevated session modal.
- Improved the accessibility of element indexes. ([#14120](https://github.com/craftcms/cms/pull/14120), [#12286](https://github.com/craftcms/cms/pull/12286))
- Selected elements within relational fields now include “Move up/down” or “Move forward/backward” in their action menus.
- Improved the accessibility of time zone fields.
- Improved the accessibility of form alternative action menus.
- Improved the accessibility of Matrix fields with the “inline-editable blocks” view mode. ([#14187](https://github.com/craftcms/cms/pull/14187))
- Improved the accessibility of the global nav. ([#14240](https://github.com/craftcms/cms/pull/14240))Improved the accessibility of the global nav. ([#14240](https://github.com/craftcms/cms/pull/14240))
- Improved the accessibility of layout tabs. ([#14215](https://github.com/craftcms/cms/pull/14215))
- Improved the accessibility of overflow tab menus. ([#14214](https://github.com/craftcms/cms/pull/14214))
- Increased the hit area for range select options.
- Improved the accessibility of the global sidebar. ([#14335](https://github.com/craftcms/cms/pull/14335))

### Administration
- Added the Team edition.
- Added the “Color” entry type setting. ([#14187](https://github.com/craftcms/cms/pull/14187))
- Added the “Icon” entry type setting. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added the “Addresses” field type. ([#11438](https://github.com/craftcms/cms/discussions/11438))
- Added the “Icon” field type. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Field layouts can now designate an Assets field as the source for elements’ thumbnails. ([#12484](https://github.com/craftcms/cms/discussions/12484), [#12706](https://github.com/craftcms/cms/discussions/12706))
- Field layouts can now choose to include previewable fields’ content in element cards. ([#12484](https://github.com/craftcms/cms/discussions/12484), [#6024](https://github.com/craftcms/cms/pull/6024))
- Field layouts can now override custom fields’ handles.
- Field Layout Designers now hide the component library sidebar in favor of “Add” disclosure menus, when they’re too narrow to show the sidebar alongside configured tabs. ([#14411](https://github.com/craftcms/cms/pull/14411))
- Most custom fields can now be included multiple times within the same field layout. ([#8497](https://github.com/craftcms/cms/discussions/8497))
- Sections now have a “Max Authors” setting. ([#12380](https://github.com/craftcms/cms/pull/12380))
- Entry types are now managed independently of sections.
- Entry types are no longer required to have a Title Format, if the Title field isn’t shown.
- Entry types now have a “Show the Slug field” setting. ([#13799](https://github.com/craftcms/cms/discussions/13799))
- Entry type and field edit pages now list their usages. ([#14397](https://github.com/craftcms/cms/pull/14397))
- Sites’ Language settings can now be set to environment variables. ([#14235](https://github.com/craftcms/cms/pull/14235), [#14135](https://github.com/craftcms/cms/discussions/14135))
- Matrix fields now manage nested entries, rather than Matrix blocks. During the upgrade, existing Matrix block types will be converted to entry types; their nested fields will be made global; and Matrix blocks will be converted to entries.
- Matrix fields now have “Entry URI Format” and “Template” settings for each site.
- Matrix fields now have a “View Mode” setting, giving admins the choice to display nested entries as cards, inline-editable blocks, or an embedded element index.
- Matrix fields now require the owner element to be saved before they can be edited.
- Matrix fields now have a “‘New’ Button Label” setting. ([#14573](https://github.com/craftcms/cms/issues/14573))
- Relational fields’ “Selection Label” setting has been relabelled as “‘Add’ Button Label”.
- Added support for inline field creation and editing within field layout designers. ([#14260](https://github.com/craftcms/cms/pull/14260))
- Layout elements within field layout designers now have action menus. ([#14260](https://github.com/craftcms/cms/pull/14260))
- The Fields and Entry Types index pages now have a search bar. ([#13961](https://github.com/craftcms/cms/discussions/13961), [#14126](https://github.com/craftcms/cms/pull/14126))
- Field types now have icons. ([#14267](https://github.com/craftcms/cms/pull/14267))
- The address field layout is now accessed via **Settings** → **Addresses**.
- Volumes now have a “Subpath” setting, and can reuse filesystems so long as the subpaths don’t overlap. ([#11044](https://github.com/craftcms/cms/discussions/11044))
- Volumes now have an “Alternative Text Translation Method” setting. ([#11576](https://github.com/craftcms/cms/issues/11576))
- Added support for defining custom locale aliases, via a new `localeAliases` config setting. ([#12705](https://github.com/craftcms/cms/pull/12705))
- Added support for element partial templates. ([#14284](https://github.com/craftcms/cms/pull/14284))
- Added the `partialTemplatesPath` config setting. ([#14284](https://github.com/craftcms/cms/pull/14284))
- Added the `tempAssetUploadFs` config setting. ([#13957](https://github.com/craftcms/cms/pull/13957))
- Removed the concept of field groups.
- Removed the “Temp Uploads Location” asset setting. ([#13957](https://github.com/craftcms/cms/pull/13957))
- Added the `utils/prune-orphaned-entries` command. ([#14154](https://github.com/craftcms/cms/pull/14154))
- `entrify/*` commands now ask if an entry type already exists for the section.
- The `resave/entries` command now accepts a `--field` option.
- The `up`, `migrate/up`, and `migrate/all` commands no longer overwrite pending project config YAML changes, if new project config changes were made by migrations.
- Removed the `--force` option from the `up` command. `--isolated=0` should be used instead. ([#14270](https://github.com/craftcms/cms/pull/14270))
- Removed the `resave/matrix-blocks` command.

### Development
- Entry type names and handles must now be unique globally, rather than just within a single section. Existing entry type names and handles will be renamed automatically where needed, to ensure uniqueness.
- Assets, categories, entries, and tags now support eager-loading paths prefixed with a field layout provider’s handle (e.g. `myEntryType:myField`).
- Element queries now have an `eagerly` param, which can be used to lazily eager-load the resulting elements for all peer elements, when `all()`, `collect()`, `one()`, `nth()`, or `count()` is called.
- Element queries now have an `inBulkOp` param, which limits the results to elements which were involved in a bulk operation. ([#14032](https://github.com/craftcms/cms/pull/14032))
- Address queries now have `addressLine1`, `addressLine2`, `addressLine3`, `administrativeArea`, `countryCode`, `dependentLocality`, `firstName`, `fullName`, `lastName`, `locality`, `organizationTaxId`, `organization`, `postalCode`, and `sortingCode` params.
- Entry queries now have `field`, `fieldId`, `primaryOwner`, `primaryOwnerId`, `owner`, `ownerId`, `allowOwnerDrafts`, and `allowOwnerRevisions` params.
- Entry queries’ `authorId` params now support passing multiple IDs prefixed with `and`, to fetch entries with multiple listed authors.
- User queries now have an `authorOf` param.
- Nested addresses are now cached by their field ID, and address queries now register cache tags based on their `field` and `fieldId` params.
- Nested entries are now cached by their field ID, and entry queries now register cache tags based on their `field` and `fieldId` params.
- GraphQL schemas can now include queries and mutations for nested entries (e.g. within Matrix or CKEditor fields) directly. ([#14366](https://github.com/craftcms/cms/pull/14366))
- Added the `fieldId`, `fieldHandle`, `ownerId`, and `sortOrder` entry GraphQL fields. ([#14366](https://github.com/craftcms/cms/pull/14366))
- Entries’ GraphQL type names are now formatted as `<entryTypeHandle>_Entry`, and are no longer prefixed with their section’s handle. (That goes for Matrix-nested entries as well.)
- Entries now have `author` and `authorIds` GraphQL field.
- Matrix fields’ GraphQL mutation types now expect nested entries to be defined by an `entries` field rather than `blocks`.
- Added the `entryType()` and `fieldValueSql()` Twig functions. ([#14557](https://github.com/craftcms/cms/discussions/14557))
- Added the `|firstWhere` and `|flatten` Twig filters.
- Removed the `craft.matrixBlocks()` Twig function. `craft.entries()` should be used instead.
- Controller actions which require a `POST` request will now respond with a 405 error code if another request method is used. ([#13397](https://github.com/craftcms/cms/discussions/13397))

### Extensibility
- Elements now store their content in an `elements_sites.content` column as JSON, rather than across multiple columns in a `content` table. ([#2009](https://github.com/craftcms/cms/issues/2009), [#4308](https://github.com/craftcms/cms/issues/4308), [#7221](https://github.com/craftcms/cms/issues/7221), [#7750](https://github.com/craftcms/cms/issues/7750), [#12954](https://github.com/craftcms/cms/issues/12954))
- Slugs are no longer required on elements that don’t have a URI format.
- Element types’ `fieldLayouts()` and `defineFieldLayouts()` methods’ `$source` arguments must now accept `null` values.
- All element types can now support eager-loading paths prefixed with a field layout provider’s handle (e.g. `myEntryType:myField`), by implementing `craft\base\FieldLayoutProviderInterface` on the field layout provider class, and ensuring that `defineFieldLayouts()` is returning field layouts via their providers.
- All core element query param methods now return `static` instead of `self`. ([#11868](https://github.com/craftcms/cms/pull/11868))
- Migrations that modify the project config no longer need to worry about whether the same changes were already applied to the incoming project config YAML files.
- Selectize menus no longer apply special styling to options with the value `new`. The `_includes/forms/selectize.twig` control panel template should be used instead (or `craft\helpers\Cp::selectizeHtml()`/`selectizeFieldHtml()`), which will append an styled “Add” option when `addOptionFn` and `addOptionLabel` settings are passed. ([#11946](https://github.com/craftcms/cms/issues/11946))
- Added the `chip()`, `customSelect()`, `disclosureMenu()`, `elementCard()`, `elementChip()`, `elementIndex()`, `iconSvg()`, and `siteMenuItems()` global functions for control panel templates.
- Added the `colorSelect`, `colorSelectField`, `customSelect`, `customSelectField`, `languageMenu`, and `languageMenuField` form macros.
- The `assets/move-asset` and `assets/move-folder` actions no longer include `success` keys in responses. ([#12159](https://github.com/craftcms/cms/pull/12159))
- The `assets/upload` controller action now includes `errors` object in failure responses. ([#12159](https://github.com/craftcms/cms/pull/12159))
- Element action triggers’ `validateSelection()` and `activate()` methods are now passed an `elementIndex` argument, with a reference to the trigger’s corresponding element index.
- Element search scores set on `craft\events\SearchEvent::$scores` by `craft\services\Search::EVENT_AFTER_SEARCH` or `EVENT_BEFORE_SCORE_RESULTS` now must be indexed by element ID and site ID (e.g. `'100-1'`).
- Added `craft\auth\methods\AuthMethodInterface`.
- Added `craft\auth\methods\BaseAuthMethod`.
- Added `craft\auth\methods\RecoveryCodes`.
- Added `craft\auth\methods\TOTP`.
- Added `craft\auth\passkeys\CredentialRepository`.
- Added `craft\base\Actionable`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\base\ApplicationTrait::$edition`.
- Added `craft\base\ApplicationTrait::getAuth()`.
- Added `craft\base\Chippable`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\base\Colorable`. ([#14187](https://github.com/craftcms/cms/pull/14187))
- Added `craft\base\CpEditable`.
- Added `craft\base\Element::EVENT_DEFINE_ACTION_MENU_ITEMS`.
- Added `craft\base\Element::EVENT_DEFINE_INLINE_ATTRIBUTE_INPUT_HTML`.
- Added `craft\base\Element::crumbs()`.
- Added `craft\base\Element::destructiveActionMenuItems()`.
- Added `craft\base\Element::inlineAttributeInputHtml()`.
- Added `craft\base\Element::render()`. ([#14284](https://github.com/craftcms/cms/pull/14284))
- Added `craft\base\Element::safeActionMenuItems()`.
- Added `craft\base\Element::shouldValidateTitle()`.
- Added `craft\base\ElementContainerFieldInterface`, which should be implemented by fields which contain nested elements, such as Matrix.
- Added `craft\base\ElementInterface::canDuplicateAsDraft()`.
- Added `craft\base\ElementInterface::getActionMenuItems()`.
- Added `craft\base\ElementInterface::getCardBodyHtml()`.
- Added `craft\base\ElementInterface::getChipLabelHtml()`.
- Added `craft\base\ElementInterface::getCrumbs()`.
- Added `craft\base\ElementInterface::getInlineAttributeInputHtml()`.
- Added `craft\base\ElementInterface::hasDrafts()`.
- Added `craft\base\ElementInterface::hasThumbs()`.
- Added `craft\base\ElementInterface::setAttributesFromRequest()`.
- Added `craft\base\ElementInterface::setAttributesFromRequest()`.
- Added `craft\base\ElementInterface::setLazyEagerLoadedElements()`.
- Added `craft\base\ElementTrait::$deletedWithOwner`.
- Added `craft\base\ElementTrait::$eagerLoadInfo`.
- Added `craft\base\ElementTrait::$elementQueryResult`.
- Added `craft\base\ElementTrait::$forceSave`.
- Added `craft\base\ElementTrait::$propagatingFrom`.
- Added `craft\base\Field::valueSql()`.
- Added `craft\base\FieldInterface::dbType()`, which defines the type(s) of values the field will store in the `elements_sites.content` column (if any).
- Added `craft\base\FieldInterface::getValueSql()`.
- Added `craft\base\FieldInterface::icon()`.
- Added `craft\base\FieldInterface::isMultiInstance()`.
- Added `craft\base\FieldInterface::queryCondition()`, which accepts an element query param value and returns the corresponding query condition.
- Added `craft\base\FieldLayoutComponent::hasSettings()`.
- Added `craft\base\FieldLayoutElement::isMultiInstance()`.
- Added `craft\base\FieldLayoutProviderInterface::getHandle()`.
- Added `craft\base\FieldTrait::$layoutElement`.
- Added `craft\base\Iconic`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\base\Identifiable`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\base\InlineEditableFieldInterface`.
- Added `craft\base\NestedElementInterface`, which should be implemented by element types which could be nested by other elements.
- Added `craft\base\NestedElementTrait`.
- Added `craft\base\Statusable`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\base\ThumbableFieldInterface`.
- Added `craft\base\Thumbable`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\base\conditions\ConditionInterface::createConditionRule()`.
- Added `craft\behaviors\EventBehavior`.
- Added `craft\controllers\EntryTypesController`.
- Added `craft\db\CallbackExpressionBuilder`.
- Added `craft\db\CallbackExpression`.
- Added `craft\db\Connection::getIsMaria()`.
- Added `craft\db\QueryParam`.
- Added `craft\db\Table::ELEMENTS_OWNERS`.
- Added `craft\db\Table::SECTIONS_ENTRYTYPES`.
- Added `craft\db\mysql\ColumnSchema::$collation`.
- Added `craft\db\mysql\QueryBuilder::jsonContains()`.
- Added `craft\db\mysql\QueryBuilder::jsonExtract()`.
- Added `craft\db\mysql\Schema::supportsMb4()`.
- Added `craft\db\pgsql\QueryBuilder::jsonContains()`.
- Added `craft\db\pgsql\QueryBuilder::jsonExtract()`.
- Added `craft\db\pgsql\Schema::supportsMb4()`.
- Added `craft\elements\Address::GQL_TYPE_NAME`.
- Added `craft\elements\Asset::gqlTypeName()`.
- Added `craft\elements\Category::gqlTypeName()`.
- Added `craft\elements\ElementCollection::render()`. ([#14284](https://github.com/craftcms/cms/pull/14284))
- Added `craft\elements\Entry::$collapsed`.
- Added `craft\elements\Entry::$dirty`.
- Added `craft\elements\Entry::gqlTypeName()`.
- Added `craft\elements\Entry::setOwner()`.
- Added `craft\elements\NestedElementManager`.
- Added `craft\elements\Tag::gqlTypeName()`.
- Added `craft\elements\User::GQL_TYPE_NAME`.
- Added `craft\elements\User::authenticateWithPasskey()`.
- Added `craft\elements\User::canRegisterUsers()`.
- Added `craft\elements\conditions\ElementConditionInterface::getFieldLayouts()`.
- Added `craft\elements\conditions\addresses\AddressLine1ConditionRule`.
- Added `craft\elements\conditions\addresses\AddressLine2ConditionRule`.
- Added `craft\elements\conditions\addresses\AddressLine3ConditionRule`.
- Added `craft\elements\conditions\addresses\AdministrativeAreaConditionRule`.
- Added `craft\elements\conditions\addresses\CountryConditionRule`.
- Added `craft\elements\conditions\addresses\DependentLocalityConditionRule`.
- Added `craft\elements\conditions\addresses\FullNameConditionRule`.
- Added `craft\elements\conditions\addresses\LocalityConditionRule`.
- Added `craft\elements\conditions\addresses\OrganizationConditionRule`.
- Added `craft\elements\conditions\addresses\OrganizationTaxIdConditionRule`.
- Added `craft\elements\conditions\addresses\PostalCodeConditionRule`.
- Added `craft\elements\conditions\addresses\SortingCodeConditionRule`.
- Added `craft\elements\conditions\entries\MatrixFieldConditionRule`.
- Added `craft\elements\db\EagerLoadInfo`.
- Added `craft\elements\db\EagerLoadPlan::$lazy`.
- Added `craft\elements\db\ElementQuery::$eagerLoadAlias`.
- Added `craft\elements\db\ElementQuery::$eagerLoadHandle`.
- Added `craft\elements\db\ElementQueryInterface::eagerly()`.
- Added `craft\elements\db\ElementQueryInterface::fieldLayouts()`.
- Added `craft\elements\db\ElementQueryInterface::prepForEagerLoading()`.
- Added `craft\elements\db\ElementQueryInterface::wasCountEagerLoaded()`.
- Added `craft\elements\db\ElementQueryInterface::wasEagerLoaded()`.
- Added `craft\enums\AttributeStatus`.
- Added `craft\enums\CmsEdition`.
- Added `craft\enums\Color`. ([#14187](https://github.com/craftcms/cms/pull/14187))
- Added `craft\enums\ElementIndexViewMode`.
- Added `craft\enums\PropagationMethod`.
- Added `craft\enums\TimePeriod`.
- Added `craft\events\BulkElementsEvent`.
- Added `craft\events\BulkOpEvent`. ([#14032](https://github.com/craftcms/cms/pull/14032))
- Added `craft\events\DefineEntryTypesForFieldEvent`.
- Added `craft\events\DefineFieldHtmlEvent::$inline`.
- Added `craft\events\DuplicateNestedElementsEvent`.
- Added `craft\events\SetEagerLoadedElementsEvent::$plan`.
- Added `craft\fieldlayoutelements\BaseField::$includeInCards`.
- Added `craft\fieldlayoutelements\BaseField::$providesThumbs`.
- Added `craft\fieldlayoutelements\BaseField::previewHtml()`.
- Added `craft\fieldlayoutelements\BaseField::previewable()`.
- Added `craft\fieldlayoutelements\BaseField::selectorIcon()`.
- Added `craft\fieldlayoutelements\BaseField::thumbHtml()`.
- Added `craft\fieldlayoutelements\BaseField::thumbable()`.
- Added `craft\fieldlayoutelements\CustomField::$handle`.
- Added `craft\fieldlayoutelements\CustomField::getOriginalHandle()`.
- Added `craft\fieldlayoutelements\TextField::inputAttributes()`.
- Added `craft\fieldlayoutelements\users\EmailField`.
- Added `craft\fieldlayoutelements\users\FullNameField`.
- Added `craft\fieldlayoutelements\users\PhotoField`.
- Added `craft\fieldlayoutelements\users\UsernameField`.
- Added `craft\fields\Addresses`.
- Added `craft\fields\Matrix::EVENT_DEFINE_ENTRY_TYPES`.
- Added `craft\fields\Matrix::getEntryTypes()`.
- Added `craft\fields\Matrix::getEntryTypesForField()`.
- Added `craft\fields\Matrix::getSupportedSitesForElement()`.
- Added `craft\fields\Matrix::setEntryTypes()`.
- Added `craft\fields\Matrix::supportedSiteIds()`.
- Added `craft\fields\Money::currencyLabel()`.
- Added `craft\fields\Money::currencyLabel()`.
- Added `craft\fields\Money::subunits()`.
- Added `craft\fields\Money::subunits()`.
- Added `craft\fields\conditions\FieldConditionRuleTrait::fieldInstances()`.
- Added `craft\fields\conditions\FieldConditionRuleTrait::setLayoutElementUid()`.
- Added `craft\fields\conditions\MoneyFieldConditionRule`.
- Added `craft\fields\conditions\MoneyFieldConditionRule`.
- Added `craft\helpers\App::isWindows()`.
- Added `craft\helpers\App::silence()`.
- Added `craft\helpers\ArrayHelper::lastValue()`.
- Added `craft\helpers\Cp::CHIP_SIZE_LARGE`.
- Added `craft\helpers\Cp::CHIP_SIZE_SMALL`.
- Added `craft\helpers\Cp::checkboxGroupFieldHtml()`.
- Added `craft\helpers\Cp::checkboxGroupHtml()`.
- Added `craft\helpers\Cp::chipHtml()`.
- Added `craft\helpers\Cp::colorSelectFieldHtml()`.
- Added `craft\helpers\Cp::customSelectFieldHtml()`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\helpers\Cp::customSelectHtml()`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\helpers\Cp::disclosureMenu()`.
- Added `craft\helpers\Cp::earthIcon()`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\helpers\Cp::elementCardHtml()`.
- Added `craft\helpers\Cp::elementChipHtml()`.
- Added `craft\helpers\Cp::elementIndexHtml()`.
- Added `craft\helpers\Cp::entryTypeSelectFieldHtml()`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\helpers\Cp::entryTypeSelectHtml()`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\helpers\Cp::fallbackIconSvg()`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\helpers\Cp::iconPickerFieldHtml()`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\helpers\Cp::iconPickerHtml()`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\helpers\Cp::iconSvg()`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\helpers\Cp::layoutElementSelectorHtml()`.
- Added `craft\helpers\Cp::menuItem()`. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Added `craft\helpers\Cp::moneyFieldHtml()`.
- Added `craft\helpers\Cp::moneyInputHtml()`.
- Added `craft\helpers\Cp::normalizeMenuItems()`.
- Added `craft\helpers\Cp::siteMenuItems()`.
- Added `craft\helpers\Db::defaultCollation()`.
- Added `craft\helpers\Db::prepareForJsonColumn()`.
- Added `craft\helpers\ElementHelper::actionConfig()`.
- Added `craft\helpers\ElementHelper::addElementEditorUrlParams()`.
- Added `craft\helpers\ElementHelper::elementEditorUrl()`.
- Added `craft\helpers\ElementHelper::renderElements()`. ([#14284](https://github.com/craftcms/cms/pull/14284))
- Added `craft\helpers\ElementHelper::rootElementIfCanonical()`.
- Added `craft\helpers\Gql::getSchemaContainedSections()`.
- Added `craft\helpers\Json::detectIndent()`.
- Added `craft\helpers\Json::encodeToFile()`.
- Added `craft\helpers\ProjectConfig::ensureAllEntryTypesProcessed()`.
- Added `craft\i18n\Locale::$aliasOf`.
- Added `craft\i18n\Locale::setDisplayName()`.
- Added `craft\log\Dispatcher::getDefaultTarget()`. ([#14283](https://github.com/craftcms/cms/pull/14283))
- Added `craft\migrations\BaseContentRefactorMigration`.
- Added `craft\models\EntryType::$color`.
- Added `craft\models\EntryType::findUsages()`.
- Added `craft\models\FieldLayout::getCardBodyFields()`.
- Added `craft\models\FieldLayout::getElementByUid()`.
- Added `craft\models\FieldLayout::getFieldById()`.
- Added `craft\models\FieldLayout::getFieldByUid()`.
- Added `craft\models\FieldLayout::getThumbField()`.
- Added `craft\models\FsListing::getAdjustedUri()`.
- Added `craft\models\Section::getCpEditUrl()`.
- Added `craft\models\Site::getLanguage()`.
- Added `craft\models\Site::setLanguage()`.
- Added `craft\models\Volume::$altTranslationKeyFormat`.
- Added `craft\models\Volume::$altTranslationMethod`.
- Added `craft\models\Volume::getSubpath()`.
- Added `craft\models\Volume::setSubpath()`.
- Added `craft\queue\BaseBatchedElementJob`. ([#14032](https://github.com/craftcms/cms/pull/14032))
- Added `craft\queue\BaseBatchedJob::after()`.
- Added `craft\queue\BaseBatchedJob::afterBatch()`.
- Added `craft\queue\BaseBatchedJob::before()`.
- Added `craft\queue\BaseBatchedJob::beforeBatch()`.
- Added `craft\services\Auth`.
- Added `craft\services\Elements::EVENT_AUTHORIZE_DUPLICATE_AS_DRAFT`.
- Added `craft\services\Elements::canDuplicateAsDraft()`.
- Added `craft\services\Entries::deleteEntryType()`.
- Added `craft\services\Entries::deleteEntryTypeById()`.
- Added `craft\services\Entries::deleteSection()`.
- Added `craft\services\Entries::deleteSectionById()`.
- Added `craft\services\Entries::getAllEntryTypes()`.
- Added `craft\services\Entries::getAllSectionIds()`.
- Added `craft\services\Entries::getAllSections()`.
- Added `craft\services\Entries::getEditableSectionIds()`.
- Added `craft\services\Entries::getEditableSections()`.
- Added `craft\services\Entries::getEntryTypeByHandle()`.
- Added `craft\services\Entries::getEntryTypeById()`.
- Added `craft\services\Entries::getEntryTypesBySectionId()`.
- Added `craft\services\Entries::getSectionByHandle()`.
- Added `craft\services\Entries::getSectionById()`.
- Added `craft\services\Entries::getSectionByUid()`.
- Added `craft\services\Entries::getSectionsByType()`.
- Added `craft\services\Entries::getTotalEditableSections()`.
- Added `craft\services\Entries::getTotalSections()`.
- Added `craft\services\Entries::refreshEntryTypes()`.
- Added `craft\services\Entries::saveSection()`.
- Added `craft\services\Fields::$fieldContext`, which replaces `craft\services\Content::$fieldContext`.
- Added `craft\services\Fields::EVENT_REGISTER_NESTED_ENTRY_FIELD_TYPES`.
- Added `craft\services\Fields::findFieldUsages()`.
- Added `craft\services\Fields::getAllLayouts()`.
- Added `craft\services\Fields::getNestedEntryFieldTypes()`.
- Added `craft\services\Gql::defineContentArgumentsForFieldLayouts()`.
- Added `craft\services\Gql::defineContentArgumentsForFields()`.
- Added `craft\services\Gql::getOrSetContentArguments()`.
- Added `craft\services\ProjectConfig::find()`.
- Added `craft\services\ProjectConfig::flush()`.
- Added `craft\services\ProjectConfig::writeYamlFiles()`.
- Added `craft\services\Sites::$maxSites`. ([#14307](https://github.com/craftcms/cms/pull/14307))
- Added `craft\services\Sites::getRemainingSites()`. ([#14307](https://github.com/craftcms/cms/pull/14307))
- Added `craft\servics\Users::canCreateUsers()`.
- Added `craft\web\Controller::asCpModal()`.
- Added `craft\web\CpModalResponseBehavior`.
- Added `craft\web\CpModalResponseFormatter`.
- Added `craft\web\CpScreenResponseBehavior::$actionMenuItems`.
- Added `craft\web\CpScreenResponseBehavior::$contextMenuItems`.
- Added `craft\web\CpScreenResponseBehavior::$selectableSites`.
- Added `craft\web\CpScreenResponseBehavior::$site`.
- Added `craft\web\CpScreenResponseBehavior::actionMenuItems()`.
- Added `craft\web\CpScreenResponseBehavior::contextMenuItems()`.
- Added `craft\web\CpScreenResponseBehavior::selectableSites()`.
- Added `craft\web\CpScreenResponseBehavior::site()`.
- Added `craft\web\Request::getQueryParamsWithoutPath()`.
- Added `craft\web\twig\variables\Cp::getEntryTypeOptions()`.
- Added `craft\base\PluginTrait::$minCmsEdition`.
- Renamed `craft\base\BlockElementInterface` to `NestedElementInterface`, and added the `getField()`, `getSortOrder()`, and `setOwner()` methods to it.
- Renamed `craft\base\Element::EVENT_SET_TABLE_ATTRIBUTE_HTML` to `EVENT_DEFINE_ATTRIBUTE_HTML`.
- Renamed `craft\base\Element::getHasCheckeredThumb()` to `hasCheckeredThumb()` and made it protected.
- Renamed `craft\base\Element::getHasRoundedThumb()` to `hasRoundedThumb()` and made it protected.
- Renamed `craft\base\Element::getThumbAlt()` to `thumbAlt()` and made it protected.
- Renamed `craft\base\Element::getThumbUrl()` to `thumbUrl()` and made it protected.
- Renamed `craft\base\Element::tableAttributeHtml()` to `attributeHtml()`.
- Renamed `craft\base\ElementInterface::getTableAttributeHtml()` to `getAttributeHtml()`.
- Renamed `craft\base\FieldInterface::valueType()` to `phpType()`.
- Renamed `craft\base\PreviewableFieldInterface::getTableAttributeHtml()` to `getPreviewHtml()`.
- Renamed `craft\base\UtilityInterface::iconPath()` to `icon()`, which can now return a system icon name. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Renamed `craft\base\conditions\BaseCondition::EVENT_REGISTER_CONDITION_RULE_TYPES` to `EVENT_REGISTER_CONDITION_RULES`.
- Renamed `craft\base\conditions\BaseCondition::conditionRuleTypes()` to `selectableConditionRules()`.
- Renamed `craft\events\BatchElementActionEvent` to `MultiElementActionEvent`.
- Renamed `craft\events\RegisterConditionRuleTypesEvent` to `RegisterConditionRulesEvent`, and its `$conditionRuleTypes` property has been renamed to `$conditionRules`.
- Renamed `craft\events\SetElementTableAttributeHtmlEvent` to `DefineAttributeHtmlEvent`.
- Renamed `craft\fields\BaseRelationField::tableAttributeHtml()` to `previewHtml()`, and it now accepts an `ElementCollection` argument, rather than `Collection`.
- Renamed `craft\fields\Matrix::$maxBlocks` to `$maxEntries`.
- Renamed `craft\fields\Matrix::$minBlocks` to `$minEntries`.
- Renamed `craft\helpers\MailerHelper\EVENT_REGISTER_MAILER_TRANSPORT_TYPES` to `EVENT_REGISTER_MAILER_TRANSPORTS`.
- Renamed `craft\log\Dispatcher::getTargets()` to `getDefaultTargets()`. ([#14283](https://github.com/craftcms/cms/pull/14283))
- Renamed `craft\services\Addresses::getLayout()` to `getFieldLayout()`.
- Renamed `craft\services\Addresses::saveLayout()` to `saveFieldLayout()`.
- Renamed `craft\services\Utilities::EVENT_REGISTER_UTILITY_TYPES` to `EVENT_REGISTER_UTILITIES`.
- Renamed `craft\web\CpScreenResponseBehavior::$additionalButtons()` and `additionalButtons()` to `$additionalButtonsHtml` and `additionalButtonsHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$content()` and `content()` to `$contentHtml` and `contentHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$contextMenu()` and `contextMenu()` to `$contextMenuHtml` and `contextMenuHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$notice()` and `notice()` to `$noticeHtml` and `noticeHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$pageSidebar()` and `pageSidebar()` to `$pageSidebarHtml` and `pageSidebarHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$sidebar()` and `sidebar()` to `$metaSidebarHtml` and `metaSidebarHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- `craft\base\ApplicationTrait::getLicensedEdition()` now returns a `craft\enums\CmsEdition` case or `null`.
- `craft\base\ApplicationTrait::requireEdition()` now accepts a `craft\enums\CmsEdition` case or an integer.
- `craft\base\ApplicationTrait::setEdition()` now accepts a `craft\enums\CmsEdition` case or an integer.
- `craft\base\BaseFsInterface::renameFile()` and `copyFile()` now have a `$config` argument. ([#14147](https://github.com/craftcms/cms/pull/14147))
- `craft\base\ConfigurableComponent::getSettings()` now converts backed enum cases to their values.
- `craft\base\Element::getCpEditUrl()` now returns a URL to `edit/<ID>` if `cpEditUrl()` returns `null`.
- `craft\base\ElementInterface::findSource()` no longer needs to specify a default value for the `context` argument.
- `craft\base\ElementInterface::getAncestors()`, `getDescendants()`, `getChildren()`, and `getSiblings()` now have `ElementQueryInterface|ElementCollection` return types, rather than `ElementQueryInterface|Collection`.
- `craft\base\ElementInterface::getEagerLoadedElementCount()` can now return `null` for counts that haven’t been eager-loaded yet.
- `craft\base\ElementInterface::getEagerLoadedElements` now has an `ElementCollection|null` return type, rather than `Collection|null`.
- `craft\base\ElementInterface::indexHtml()`’ `$showCheckboxes` argument is now `$selectable`, and it now has a `$sortable` argument.
- `craft\base\ElementInterface::modifyCustomSource()` can now set `disabled` to `true` on the source config to hide it.
- `craft\base\ElementInterface::setEagerLoadedElements()` now has a `$plan` argument, which will be set to the eager-loading plan.
- `craft\base\ElementInterface::setParent()` no longer needs to specify a default value for the `parent` argument.
- `craft\base\ElementInterface::setRevisionCreatorId()` no longer needs to specify a default value for the `creatorId` argument.
- `craft\base\ElementInterface::setRevisionNotes()` no longer needs to specify a default value for the `notes` argument.
- `craft\base\Field::inputHtml()` now has an `$inline` argument.
- `craft\base\FieldInterface::getIsTranslatable()`, `getTranslationDescription()`, `getInputHtml()`, `normalizeValue()`, `normalizeValueFromRequest()`, and `serializeValue()` no longer need to specify a default value for the `$element` argument.
- `craft\base\WidgetInterface::icon()` can now return a system icon name. ([#14169](https://github.com/craftcms/cms/pull/14169))
- `craft\behaviors\SessionBehavior::setSuccess()` and `getSuccess()` use the `success` flash key now, rather than `notice`. ([#14345](https://github.com/craftcms/cms/pull/14345))
- `craft\db\Connection::getSupportsMb4()` is now dynamic for MySQL installs, based on whether the `elements_sites` table has an `mb4` charset.
- `craft\elemens\db\ElementQueryInterface::collect()` now has an `ElementCollection` return type, rather than `Collection`.
- `craft\elements\Entry::getSection()` can now return `null`, for nested entries.
- `craft\elements\User::getAddresses()` now returns a collection.
- `craft\elements\db\ElementQuery::__toString()` now returns the class name. ([#14498](https://github.com/craftcms/cms/issues/14498))
- `craft\enums\LicenseKeyStatus` is now an enum.
- `craft\events\AuthenticateUserEvent::$password` can now be null, if the user is being authenticated with a passkey.
- `craft\fields\BaseOptionsField::$multi` and `$optgroups` properties are now static.
- `craft\fields\Matrix::$propagationMethod` now has a type of `craft\enums\PropagationMethod`.
- `craft\fields\fieldlayoutelements\BaseUiElement::selectorIcon()` can now return a system icon name. ([#14169](https://github.com/craftcms/cms/pull/14169))
- `craft\gql\mutations\Entry::createSaveMutations()` now accepts a `$section` argument.
- `craft\helpers\App::parseEnv()` now returns `null` when a missing environment variable name is passed to it. ([#14253](https://github.com/craftcms/cms/pull/14253))
- `craft\helpers\Assets::generateUrl()` no longer has an `$fs` argument. ([#14353](https://github.com/craftcms/cms/pull/14353))
- `craft\helpers\Cp::fieldHtml()` now supports a `labelExtra` config value.
- `craft\helpers\Db::parseParam()`, `parseDateParam()`, `parseMoneyParam()`, and `parseNumericParam()` now return `null` instead of an empty string if no condition should be applied.
- `craft\helpers\Html::id()` and `Craft.formatInputId()` now retain colons and periods, and ensure the string begins with a letter.
- `craft\helpers\Html::normalizeTagAttributes()` now supports a `removeClass` key.
- `craft\helpers\Html::svg()` now has a `$throwException` argument.
- `craft\helpers\Html::tag()` and `beginTag()` now ensure that the passed-in attributes are normalized.
- `craft\helpers\StringHelper::toString()` now supports backed enums.
- `craft\i18n\I18N::getPrimarySiteLocale()` is now deprecated. `craft\models\Site::getLocale()` should be used instead.
- `craft\i18n\I18N::getPrimarySiteLocaleId()` is now deprecated. `craft\models\Site::$language` should be used instead.
- `craft\models\FieldLayout::getField()` and `isFieldIncluded()` now now have a `$filter` argument rather than `$attribute`, and it can be set to a callable.
- `craft\models\Section::$propagationMethod` now has a type of `craft\enums\PropagationMethod`.
- `craft\services\AssetIndexer::indexFileByListing()` now has a `$volume` argument in place of `$volumeId`.
- `craft\services\AssetIndexer::indexFolderByListing()` now has a `$volume` argument in place of `$volumeId`.
- `craft\services\AssetIndexer::storeIndexList()` now has a `$volume` argument in place of `$volumeId`.
- `craft\services\Elements::duplicateElement()` now has an `$asUnpublishedDraft` argument, and no longer has a `$trackDuplication` argument.
- `craft\services\Elements::saveElement()` now has a `$saveContent` argument.
- `craft\services\Plugins::getPluginLicenseKeyStatus()` now returns a `craft\enums\LicenseKeyStatus` case.
- `craft\services\ProjectConfig::saveModifiedConfigData()` no longer has a `$writeExternalConfig` argument, and no longer writes out updated project config YAML files.
- `craft\services\Users::activateUser()` now has a `void` return type, and throws an `InvalidElementException` in case of failure.
- `craft\services\Users::deactivateUser()` now has a `void` return type, and throws an `InvalidElementException` in case of failure.
- `craft\services\Users::removeCredentials()` now has a `void` return type, and throws an `InvalidElementException` in case of failure.
- `craft\services\Users::shunMessageForUser()` now has a `void` return type, and throws an `InvalidElementException` in case of failure.
- `craft\services\Users::suspendUser()` now has a `void` return type, and throws an `InvalidElementException` in case of failure.
- `craft\services\Users::unlockUser()` now has a `void` return type, and throws an `InvalidElementException` in case of failure.
- `craft\services\Users::unshunMessageForUser()` now has a `void` return type, and throws an `InvalidElementException` in case of failure.
- `craft\services\Users::unsuspendUser()` now has a `void` return type, and throws an `InvalidElementException` in case of failure.
- `craft\services\Users::verifyEmailForUser()` now has a `void` return type, and throws an `InvalidElementException` in case of failure.
- `craft\web\Controller::asModelSuccess()` now includes a `modelClass` key in the response data (and `modelId` if the model implements `craft\base\Identifiable`).
- Colors defined by elements’ `statuses()` methods can now be a `craft\enums\Color` instance.
- Exception response data no longer includes an `error` key with the exception message. `message` should be used instead. ([#14346](https://github.com/craftcms/cms/pull/14346))
- Deprecated `Craft::Pro`. `craft\enums\CmsEdition::Pro` should be used instead.
- Deprecated `Craft::Solo`. `craft\enums\CmsEdition::Solo` should be used instead.
- Deprecated `craft\base\ApplicationTrait::getEdition()`. `$edition` should be used instead.
- Deprecated `craft\base\ApplicationTrait::getEditionHandle()`. `$edition` should be used instead.
- Deprecated `craft\base\ApplicationTrait::getEditionName()`. `$edition` should be used instead.
- Deprecated `craft\base\ApplicationTrait::getLicensedEditionName()`. `getLicensedEdition()` should be used instead.
- Deprecated `craft\events\DefineElementInnerHtmlEvent`.
- Deprecated `craft\events\SearchEvent::$siteId`.
- Deprecated `craft\helpers\App::editionHandle()`. `craft\enums\CmsEdition::handle()` should be used instead.
- Deprecated `craft\helpers\App::editionIdByHandle()`. `craft\enums\CmsEdition::fromHandle()` should be used instead.
- Deprecated `craft\helpers\App::editionName()`. `craft\enums\CmsEdition::name` should be used instead.
- Deprecated `craft\helpers\App::editions()`. `craft\enums\CmsEdition::cases()` should be used instead.
- Deprecated `craft\helpers\App::isValidEdition()`. `craft\enums\CmsEdition::tryFrom()` should be used instead.
- Deprecated `craft\helpers\Component::iconSvg()`. `craft\helpers\Cp::iconSvg()` and `fallbackIconSvg()` should be used instead. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Deprecated `craft\helpers\Cp::ELEMENT_SIZE_LARGE`. `CHIP_SIZE_LARGE` should be used instead.
- Deprecated `craft\helpers\Cp::ELEMENT_SIZE_SMALL`. `CHIP_SIZE_SMALL` should be used instead.
- Deprecated `craft\helpers\Cp::elementHtml()`. `elementChipHtml()` or `elementCardHtml()` should be used instead.
- Deprecated the `_elements/element.twig` control panel template. `elementChip()` or `elementCard()` should be used instead.
- Deprecated the `cp.elements.element` control panel template hook.
- Removed the `_includes/revisionmenu.twig` control panel template.
- Removed `\craft\mail\transportadapters\Gmail::$timeout`.
- Removed `\craft\mail\transportadapters\Smtp::$encryptionMethod`.
- Removed `\craft\mail\transportadapters\Smtp::$timeout`.
- Removed `craft\base\ApplicationTrait::getMatrix()`.
- Removed `craft\base\Element::$contentId`.
- Removed `craft\base\Element::ATTR_STATUS_MODIFIED`. `craft\enums\AttributeStatus::Modified` should be used instead.
- Removed `craft\base\Element::ATTR_STATUS_OUTDATED`. `craft\enums\AttributeStatus::Outdated` should be used instead.
- Removed `craft\base\ElementInterface::getContentTable()`.
- Removed `craft\base\ElementInterface::getFieldColumnPrefix()`.
- Removed `craft\base\ElementInterface::gqlMutationNameByContext()`.
- Removed `craft\base\ElementInterface::gqlTypeNameByContext()`.
- Removed `craft\base\ElementInterface::hasContent()`.
- Removed `craft\base\FieldInterface::getContentColumnType()`. `dbType()` should be implemented instead.
- Removed `craft\base\FieldInterface::getGroup()`.
- Removed `craft\base\FieldInterface::hasContentColumn()`. Fields that don’t need to store values in the `elements_sites.content` column should return `null` from `dbType()`.
- Removed `craft\base\FieldInterface::modifyElementsQuery()`. Fields can customize how their element query params are handled by implementing `queryCondition()`.
- Removed `craft\base\FieldTrait::$groupId`.
- Removed `craft\base\FieldTrait::$layoutId`.
- Removed `craft\base\FieldTrait::$sortOrder`.
- Removed `craft\base\FieldTrait::$tabId`.
- Removed `craft\base\conditions\ConditionInterface::getConditionRuleTypes()`.
- Removed `craft\controllers\Sections::actionDeleteEntryType()`.
- Removed `craft\controllers\Sections::actionEditEntryType()`.
- Removed `craft\controllers\Sections::actionEntryTypesIndex()`.
- Removed `craft\controllers\Sections::actionReorderEntryTypes()`.
- Removed `craft\controllers\Sections::actionSaveEntryType()`.
- Removed `craft\controllers\UsersController::EVENT_REGISTER_USER_ACTIONS`. `craft\base\Element::EVENT_DEFINE_ACTION_MENU_ITEMS` should be used instead.
- Removed `craft\db\Table::FIELDGROUPS`.
- Removed `craft\elements\MatrixBlock`.
- Removed `craft\elements\db\ElementQuery::$contentTable`.
- Removed `craft\elements\db\MatrixBlockQuery`.
- Removed `craft\enums\PatchManifestFileAction`.
- Removed `craft\enums\PeriodType`.
- Removed `craft\enums\PluginUpdateStatus`.
- Removed `craft\enums\VersionUpdateStatus`.
- Removed `craft\errors\MatrixBlockTypeNotFoundException`.
- Removed `craft\events\BlockTypesEvent`.
- Removed `craft\events\FieldGroupEvent`.
- Removed `craft\events\RegisterUserActionsEvent`.
- Removed `craft\fieldlayoutelements\users\AddressesField`.
- Removed `craft\fields\Matrix::EVENT_SET_FIELD_BLOCK_TYPES`.
- Removed `craft\fields\Matrix::PROPAGATION_METHOD_ALL`. `craft\enums\PropagationMethod::All` should be used instead.
- Removed `craft\fields\Matrix::PROPAGATION_METHOD_CUSTOM`. `craft\enums\PropagationMethod::Custom` should be used instead.
- Removed `craft\fields\Matrix::PROPAGATION_METHOD_LANGUAGE`. `craft\enums\PropagationMethod::Language` should be used instead.
- Removed `craft\fields\Matrix::PROPAGATION_METHOD_NONE`. `craft\enums\PropagationMethod::None` should be used instead.
- Removed `craft\fields\Matrix::PROPAGATION_METHOD_SITE_GROUP`. `craft\enums\PropagationMethod::SiteGroup` should be used instead.
- Removed `craft\fields\Matrix::contentTable`.
- Removed `craft\fields\Matrix::getBlockTypeFields()`.
- Removed `craft\fields\Matrix::getBlockTypes()`.
- Removed `craft\fields\Matrix::setBlockTypes()`.
- Removed `craft\gql\arguments\elements\MatrixBlock`.
- Removed `craft\gql\interfaces\elements\MatrixBlock`.
- Removed `craft\gql\resolvers\elements\MatrixBlock`.
- Removed `craft\gql\types\elements\MatrixBlock`.
- Removed `craft\gql\types\generators\MatrixBlockType`.
- Removed `craft\helpers\Db::GLUE_AND`, `GLUE_OR`, and `GLUE_NOT`. `craft\db\QueryParam::AND`, `OR`, and `NOT` can be used instead.
- Removed `craft\helpers\Db::extractGlue()`. `craft\db\QueryParam::extractOperator()` can be used instead.
- Removed `craft\helpers\ElementHelper::fieldColumn()`.
- Removed `craft\helpers\ElementHelper::fieldColumnFromField()`.
- Removed `craft\helpers\FieldHelper`.
- Removed `craft\helpers\Gql::canMutateEntries()`.
- Removed `craft\models\EntryType::$sectionId`.
- Removed `craft\models\EntryType::$sortOrder`.
- Removed `craft\models\EntryType::getSection()`.
- Removed `craft\models\FieldGroup`.
- Removed `craft\models\MatrixBlockType`.
- Removed `craft\models\Section::PROPAGATION_METHOD_ALL`. `craft\enums\PropagationMethod::All` should be used instead.
- Removed `craft\models\Section::PROPAGATION_METHOD_CUSTOM`. `craft\enums\PropagationMethod::Custom` should be used instead.
- Removed `craft\models\Section::PROPAGATION_METHOD_LANGUAGE`. `craft\enums\PropagationMethod::Language` should be used instead.
- Removed `craft\models\Section::PROPAGATION_METHOD_NONE`. `craft\enums\PropagationMethod::None` should be used instead.
- Removed `craft\models\Section::PROPAGATION_METHOD_SITE_GROUP`. `craft\enums\PropagationMethod::SiteGroup` should be used instead.
- Removed `craft\records\EntryType::getSection()`.
- Removed `craft\records\Field::getGroup()`.
- Removed `craft\records\Field::getOldColumnSuffix()`.
- Removed `craft\records\FieldGroup`.
- Removed `craft\records\FieldLayout::getFields()`.
- Removed `craft\records\FieldLayout::getTabs()`.
- Removed `craft\records\FieldLayoutField`.
- Removed `craft\records\FieldLayoutTab`.
- Removed `craft\records\MatrixBlockType`.
- Removed `craft\records\MatrixBlock`.
- Removed `craft\services\Content`.
- Removed `craft\services\Elements::$duplicatedElementIds`.
- Removed `craft\services\Elements::$duplicatedElementSourceIds`.
- Removed `craft\services\Fields::EVENT_AFTER_DELETE_FIELD_GROUP`.
- Removed `craft\services\Fields::EVENT_AFTER_SAVE_FIELD_GROUP`.
- Removed `craft\services\Fields::EVENT_BEFORE_APPLY_GROUP_DELETE`.
- Removed `craft\services\Fields::EVENT_BEFORE_DELETE_FIELD_GROUP`.
- Removed `craft\services\Fields::EVENT_BEFORE_SAVE_FIELD_GROUP`.
- Removed `craft\services\Fields::deleteGroup()`.
- Removed `craft\services\Fields::deleteGroupById()`.
- Removed `craft\services\Fields::getAllGroups()`.
- Removed `craft\services\Fields::getFieldIdsByLayoutIds()`.
- Removed `craft\services\Fields::getFieldsByGroupId()`.
- Removed `craft\services\Fields::getGroupById()`.
- Removed `craft\services\Fields::getGroupByUid()`.
- Removed `craft\services\Fields::getLayoutTabsById()`.
- Removed `craft\services\Fields::handleChangedGroup()`.
- Removed `craft\services\Fields::handleDeletedGroup()`.
- Removed `craft\services\Fields::saveGroup()`.
- Removed `craft\services\Fields::updateColumn()`.
- Removed `craft\services\Matrix`.
- Removed `craft\services\Plugins::setPluginLicenseKeyStatus()`.
- Removed `craft\services\ProjectConfig::PATH_MATRIX_BLOCK_TYPES`.
- Removed `craft\services\ProjectConfig::PATH_MATRIX_BLOCK_TYPES`.
- Removed `craft\services\ProjectConfig::PATH_MATRIX_BLOCK_TYPES`.
- Removed `craft\services\ProjectConfig::updateStoredConfigAfterRequest()`.
- Removed `craft\services\Sections`. Most of its methods have been moved to `craft\services\Entries`.
- Removed `craft\web\CpScreenResponseBehavior::$contextMenuHtml`. `$contextMenuItems` should be used instead.
- Removed `craft\web\CpScreenResponseBehavior::contextMenuHtml()`. `contextMenuItems()` should be used instead.
- Removed `craft\web\CpScreenResponseBehavior::contextMenuTemplate()`. `contextMenuItems()` should be used instead.
- Removed `craft\web\User::startElevatedSession()`. `login()` should be used instead.
- Removed `craft\web\twig\variables\Cp::getEntryTypeOptions()`.
- Admin tables now support client-side searching when not running in API mode. ([#14126](https://github.com/craftcms/cms/pull/14126))
- Admin tables now support appending `bodyHtml` and `headHtml` when running in API mode.
- Added `Craft.BaseElementSelectInput::defineElementActions()`.
- Added `Craft.CP::setSiteCrumbMenuItemStatus()`.
- Added `Craft.CP::showSiteCrumbMenuItem()`.
- Added `Craft.CP::updateContext()`.
- Added `Craft.CpModal`.
- Added `Craft.ElementEditor::markDeltaNameAsModified()`.
- Added `Craft.ElementEditor::setFormValue()`.
- Added `Garnish.DisclosureMenu::addGroup()`.
- Added `Garnish.DisclosureMenu::addHr()`.
- Added `Garnish.DisclosureMenu::addItem()`.
- Added `Garnish.DisclosureMenu::createItem()`.
- Added `Garnish.DisclosureMenu::getFirstDestructiveGroup()`.
- Added `Garnish.DisclosureMenu::isPadded()`.
- `Craft.appendBodyHtml()` and `appendHeadHtml()` are now promise-based, and load JavaScript resources over Ajax.

### System
- Craft now requires PHP 8.2+.
- Craft now requires MySQL 8.0.17+, MariaDB 10.4.6+, or PostgreSQL 13+.
- Craft now requires the Symfony Filesystem component directly.
- Craft now requires `bacon/bacon-qr-code`.
- Craft now requires `composer/semver` directly.
- Craft now requires `pragmarx/google2fa`.
- Craft now requires `pragmarx/recovery`.
- Craft now requires `web-auth/webauthn-lib`.
- Updated `commerceguys/addressing` to v2. ([#14318](https://github.com/craftcms/cms/discussions/14318))
- Updated `illuminate/collections` to v10.
- Updated `yiisoft/yii2-symfonymailer` to v4.
- Craft no longer requires `composer/composer`.
- New database tables now default to the `utf8mb4` charset, and the `utf8mb4_0900_ai_ci` or `utf8mb4_unicode_ci` collation, on MySQL. Existing installs should run `db/convert-charset` after upgrading, to ensure all tables have consistent charsets and collations. ([#11823](https://github.com/craftcms/cms/discussions/11823))
- The `deprecationerrors.traces`, `fieldlayouts.config`, `gqlschemas.scope`, `sections.previewTargets`, `userpreferences.preferences`, and `widgets.settings` columns are now of type `JSON` for MySQL and PostgreSQL. ([#14300](https://github.com/craftcms/cms/pull/14300))
- The `defaultTemplateExtensions` config setting now lists `twig` before `html` by default. ([#11809](https://github.com/craftcms/cms/discussions/11809))
- Improved the initial page load performance for element edit screens that contain Matrix fields.
- Improved the performance of control panel screens that include field layout designers.
- Improved the performance of autosaves for elements with newly-created Matrix entries.
- Slugs are no longer required for elements that don’t have a URI format that contains `slug`.
- Garbage collection now deletes orphaned nested entries.
- Craft now has a default limit of 100 sites, which can be increased via `craft\ervices\Sites::$maxSites` at your own peril. ([#14307](https://github.com/craftcms/cms/pull/14307))
- Fixed a bug where multi-site element queries weren’t scoring elements on a per-site basis. ([#13801](https://github.com/craftcms/cms/discussions/13801))
- Fixed an error that could occur if eager-loading aliases conflicted with native eager-loading handles, such as `author`. ([#14057](https://github.com/craftcms/cms/issues/14057))
- Fixed a bug where layout components provided by disabled plugins weren’t getting omitted. ([#14219](https://github.com/craftcms/cms/pull/14219))
- Fixed a bug where element thumbnails within hidden tabs weren’t always getting loaded when their tab was selected.
- Added an SVG icon set based on Font Awesome 6.5.1. ([#14169](https://github.com/craftcms/cms/pull/14169))
- Updated Monolog to v3.
- Updated Axios to 1.6.5.
- Updated D3 to 7.8.5.
- Updated Punycode to 2.0.1.
- Updated XRegExp to 5.1.1.
