# Release Notes for Craft CMS 5.10 (WIP)

### Content Management
- Collapsed Matrix blocks now show their entries’ UI labels as preview text, whenever possible. ([#18484](https://github.com/craftcms/cms/discussions/18484))
- Element-level actions within nested element management fields (Matrix, Addresses, etc.) now consistently affect all selected elements, when performed on a selected element. ([#18561](https://github.com/craftcms/cms/pull/18561))
- Elements within Matrix and Addresses fields now have “Paste above” actions when a compatible element is copied. ([#17406](https://github.com/craftcms/cms/discussions/17406))
- Elements now keep track of the index page’s URL their edit page was linked to from, and explicitly redirect back to that page after save, rather than always redirecting to the referrer. ([#18680](https://github.com/craftcms/cms/pull/18680))
- Addresses fields now have a “Copy all addresses” field-level action. ([#18561](https://github.com/craftcms/cms/pull/18561))
- Matrix fields’ “Expand”, “Collapse”, and “Copy” field-level actions now always affect all nested entries, regardless of whether any entries are selected. ([#18561](https://github.com/craftcms/cms/pull/18561))
- Matrix fields no longer have “Duplicate” and “Delete” field-level actions. ([#18561](https://github.com/craftcms/cms/pull/18561))
- Number fields now show their selected currency beside their input, if their Preview Format setting is set to “As currency values”. ([#18498](https://github.com/craftcms/cms/pull/18498))
- Color field previews are now blank for fields without a value. ([#18614](https://github.com/craftcms/cms/issues/18614))
- Text condition rules now have “does not equal”, “is one of” and “is not one of” operators.
- Numeric condition rules now have “is one of” and “is not one of” operators. ([#18734](https://github.com/craftcms/cms/pull/18734))
- Editable table columns now set `min-width` styles based on their configured widths, if set. ([#18534](https://github.com/craftcms/cms/issues/18534))
- Entry post dates are no longer automatically set until the entry is fully saved as enabled. ([#18642](https://github.com/craftcms/cms/pull/18642))
- Element edit screens now have a “Save as a new draft” action when editing an explicitly-created draft. ([#18722](https://github.com/craftcms/cms/pull/18722))
- Address edit screens now have “Field settings” action menu items. ([#18544](https://github.com/craftcms/cms/discussions/18544))
- Asset edit screens now have “Volume settings” and “Filesystem settings” action menu items. ([#18544](https://github.com/craftcms/cms/discussions/18544))
- Entries’ “Entry type settings” and “Section settings” action menu items are now only shown for element edit screens’ primary action menus.
- Category indexes can now have “Group” columns. ([#18553](https://github.com/craftcms/cms/discussions/18553))
- Element slideouts now automatically refresh when the same element is updated in another tab/slideout. ([#18625](https://github.com/craftcms/cms/pull/18625))
- Added the “Time Zone” user preference. ([#8518](https://github.com/craftcms/cms/discussions/8518))
- Element indexes now automatically refresh after duplicating elements and the queue is completed, if there’s an active search term. ([#18636](https://github.com/craftcms/cms/issues/18636))
- Timestamps in the control panel now include their time zone abbreviation. ([#18639](https://github.com/craftcms/cms/pull/18639))
- Generated field values are no longer truncated within element cards. ([#18646](https://github.com/craftcms/cms/discussions/18646))
- Assets’ Alternative Text values are now automatically set on upload, based on descriptive text data found in the uploaded file’s metadata. ([#18744](https://github.com/craftcms/cms/pull/18744))
- When deleting elements, a modal window is now shown alerting the user of any potential issues, such as existing relationships. ([#18728](https://github.com/craftcms/cms/pull/18728))
- “Verification Code” and “Recovery Code” forms no longer get auto-submitted when entering a value.

### Administration
- It’s now possible to replace the selected custom field for existing field layout elements. ([#18814](https://github.com/craftcms/cms/pull/18814))
- Sections now have a “Min Authors” setting. ([#18662](https://github.com/craftcms/cms/pull/18662))
- Time fields’ “Max Time” settings can now be set to an earlier time than “Min Time”, for overnight time ranges. ([#18575](https://github.com/craftcms/cms/pull/18575))
- Component chips within component select inputs now have “Replace” actions.
- Newlines in system message bodies are now replaced with `<br>` tags. ([#18058](https://github.com/craftcms/cms/discussions/18058))
- Added the `--to-default` option to `resave` commands. ([#18522](https://github.com/craftcms/cms/pull/18522))
- Added the `--method` option to the `users/remove-2fa` command. ([#18732](https://github.com/craftcms/cms/pull/18732))

### Development
- Added the `heading()`/`h()` and `h1()`…`h6()` Twig functions. ([#18524](https://github.com/craftcms/cms/pull/18524))
- The `tag()` Twig function now accepts a string for its second argument. ([#18524](https://github.com/craftcms/cms/pull/18524))
- The `|default` Twig filter and `is empty` Twig test now treat all `yii\base\Model` instances as not empty. ([#18727](https://github.com/craftcms/cms/issues/18727))
- The `|time` and `|datetime` Twig filters now have `$withTimeZone` arguments. ([#18639](https://github.com/craftcms/cms/pull/18639))
- The `|timestamp` Twig filter now returns the current time, if applied to a `null`/empty string value. ([#18642](https://github.com/craftcms/cms/pull/18642))
- `delete` GraphQL queries now have a `hardDelete` argument. ([#18511](https://github.com/craftcms/cms/pull/18511))
- Entry `postDate` values are now `null` on creation, rather than set to the `dateCreated` value. ([#18642](https://github.com/craftcms/cms/pull/18642))
- Assets’ `url` GraphQL fields’ `immediately` arguments are no longer deprecated. ([#18581](https://github.com/craftcms/cms/issues/18581))
- JSON fields now support array values in POST data. ([#18705](https://github.com/craftcms/cms/pull/18705))
- Added `craft\filters\SecFetchSiteFilter` for request origin verification. ([#18641](https://github.com/craftcms/cms/pull/18641))
- `craft\fields\data\LinkData::getUrl()` now has an `$anyStatus` argument, which can be set to `false` to prevent a value from being returned if a disabled/pending/expired element is linked. ([#18527](https://github.com/craftcms/cms/issues/18527))
- Markdown parsing now respects the first number of ordered lists. ([#18671](https://github.com/craftcms/cms/issues/18671))

### Extensibility
- Added `craft\base\DefaultableFieldInterface`. ([#18522](https://github.com/craftcms/cms/pull/18522))
- Added `craft\base\Element::EVENT_DEFINE_DELETION_BLOCKERS`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Added `craft\base\ElementActionInterface::getTriggerId()`.
- Added `craft\base\ElementInterface::deletionBlockers()`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Added `craft\base\ElementInterface::setDirtyFieldTracking()`.
- Added `craft\elements\PopulateElementEvent::$content`.
- Added `craft\elements\db\ElementQuery::$activeQuery`.
- Added `craft\elements\db\ElementQueryInterface::collectIds()`.
- Added `craft\elements\deletionblockers\BaseDeletionBlocker`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Added `craft\elements\deletionblockers\DeletionBlockerInterface`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Added `craft\elements\deletionblockers\EntryAuthorsBlocker`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Added `craft\elements\deletionblockers\RelationDeletionBlocker`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Added `craft\errors\FieldNotFoundException::$fieldId`.
- Added `craft\events\DefineElementDeletionBlockersEvent`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Added `craft\fieldlayoutelements\CustomField::setFieldId()`.
- Added `craft\helpers\Html::jsWithVars()`.
- Added `craft\helpers\Markdown`. ([#18671](https://github.com/craftcms/cms/issues/18671))
- Added `craft\models\Section::$minAuthors`. ([#18662](https://github.com/craftcms/cms/pull/18662))
- Added `craft\queue\jobs\ReplaceRelations`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Added `craft\services\Elements::REF_TAG_PATTERN`.
- Added `craft\services\Entries::reassignEntries()`.
- Added `craft\validators\TimeValidator::$outOfRange`. ([#18575](https://github.com/craftcms/cms/pull/18575))
- Added `Craft.CpScreenSlideout::reload()`. ([#18625](https://github.com/craftcms/cms/pull/18625))
- Added `Craft.ElementDeletionManager`.
- `craft\elements\PopulateElementEvent::$row` no longer includes `fieldValues` or `generatedFieldValues` keys.
- `craft\helpers\DateTimeHelper::timeZoneAbbreviation()` is no longer deprecated, and now has a `$date` argument.
- `craft\i18n\Formatter::asTime()` and `asDatetime()` now have `$withTimeZone` arguments. ([#18639](https://github.com/craftcms/cms/pull/18639))
- Removed `craft\controllers\AppController::actionResourceJs()`. ([#18559](https://github.com/craftcms/cms/pull/18559))
- `Craft.CP` now triggers a `queueCompleted` event when the last queue job is completed.
- Deprecated `craft\controllers\UsersController::EVENT_DEFINE_CONTENT_SUMMARY`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Deprecated `craft\elements\User::$inheritorOnDelete`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Deprecated `craft\elements\actions\DeleteUsers`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Deprecated `craft\events\DefineUserContentSummaryEvent`. ([#18728](https://github.com/craftcms/cms/pull/18728))
- Deprecated `Craft.DeleteUserModal`. ([#18728](https://github.com/craftcms/cms/pull/18728))

### System
- Improve the image quality of WEBP transforms, when `optimizeImageFilesize` is disabled. ([#18635](https://github.com/craftcms/cms/pull/18635))
- Cross-domain script tags added by JavaScript are now loaded directly, rather than via a proxy. ([#18559](https://github.com/craftcms/cms/pull/18559))
- Updated Twig to 3.24. ([#18259](https://github.com/craftcms/cms/discussions/18259), [#18454](https://github.com/craftcms/cms/issues/18454))
- Updated bacon/bacon-qr-code to 3.x. ([#18742](https://github.com/craftcms/cms/discussions/18742))
- Updated the built-in composer.phar to 2.9.7. ([#18761](https://github.com/craftcms/cms/issues/18761))
- Fixed a bug where nested entries weren’t getting loaded with their content, if they had an entry type that was no longer allowed by their Matrix field.
- Fixed the wording of the validation error when saving a nested entry with an invalid entry type. ([#18506](https://github.com/craftcms/cms/issues/18506))
- Fixed a bug where relation fields’ element query params weren’t limiting results based on the query’s target site(s). ([#18781](https://github.com/craftcms/cms/issues/18781))
- Fixed a [high-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) potential RCE vulnerability. (GHSA-f74w-488g-8x5r)
- Fixed a [moderate-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) JavaScript injection vulnerability. (GHSA-c55v-343g-5xff)
