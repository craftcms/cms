# Release Notes for Craft CMS 5

## Unreleased

- `craft\helpers\UrlHelper::actionUrl()` now returns URLs based on the primary site’s base URL (if it has one), for console requests if the `@web` alias wasn’t explicitly defined.
- An exception is now thrown when attempting to save an entry that’s missing `sectionId` or `fieldId` + `ownerId` values. ([#15345](https://github.com/craftcms/cms/discussions/15345))
- Fixed a bug where it wasn’t possible to expand/collapse descendants of disabled table rows within element select modals. ([#15337](https://github.com/craftcms/cms/issues/15337))
- Fixed a bug where PhpStorm autocomplete wasn’t working when chaining custom field methods defined by `CustomFieldBehavior`. ([#15336](https://github.com/craftcms/cms/issues/15336))
- Fixed a bug where new nested entries created on newly-created elements weren’t getting duplicated to all other sites for the owner element. ([#15321](https://github.com/craftcms/cms/issues/15321))
- Fixed a bug where addresses were getting truncated within address cards. ([#15338](https://github.com/craftcms/cms/issues/15338))
- Fixed a bug where TOTP setup keys included an extra space at the end. ([#15349](https://github.com/craftcms/cms/issues/15349))
- Fixed a bug where input focus could automatically jump to slideout sidebars shortly after they were shown. ([#15314](https://github.com/craftcms/cms/issues/15314))
- Fixed an error that occurred if the SMTP mailer transport type was used, and the Hostname value was blank. ([#15342](https://github.com/craftcms/cms/discussions/15342))
- Fixed a bug where database DML changes weren’t getting rolled back after tests were run if the Codeception config had `transaction: true`. ([#7615](https://github.com/craftcms/cms/issues/7615))
- Fixed an error that could occur when saving recursively-nested elements. ([#15362](https://github.com/craftcms/cms/issues/15362))
- Fixed a styling issue. ([#15315](https://github.com/craftcms/cms/issues/15315))

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
