# Release Notes for Craft CMS 5.10 (WIP)

### Content Management
- Collapsed Matrix blocks now show their entries’ UI labels as preview text, whenever possible. ([#18484](https://github.com/craftcms/cms/discussions/18484))
- Element-level actions within nested element management fields (Matrix, Addresses, etc.) now consistently affect all selected elements, when performed on a selected element. ([#18561](https://github.com/craftcms/cms/pull/18561))
- Addresses fields now have a “Copy all addresses” field-level action. ([#18561](https://github.com/craftcms/cms/pull/18561))
- Matrix fields’ “Expand”, “Collapse”, and “Copy” field-level actions now always affect all nested entries, regardless of whether any entries are selected. ([#18561](https://github.com/craftcms/cms/pull/18561))
- Matrix fields no longer have “Duplicate” and “Delete” field-level actions. ([#18561](https://github.com/craftcms/cms/pull/18561))
- Number fields now show their selected currency beside their input, if their Preview Format setting is set to “As currency values”. ([#18498](https://github.com/craftcms/cms/pull/18498))
- Color field previews are now blank for fields without a value. ([#18614](https://github.com/craftcms/cms/issues/18614))
- Text condition rules now have “does not equal” operators.
- Editable table columns now set `min-width` styles based on their configured widths, if set. ([#18534](https://github.com/craftcms/cms/issues/18534))
- Entry post dates are no longer automatically set until the entry is fully saved as enabled. ([#18642](https://github.com/craftcms/cms/pull/18642))
- Address edit screens now have “Field settings” action menu items. ([#18544](https://github.com/craftcms/cms/discussions/18544))
- Asset edit screens now have “Volume settings” and “Filesystem settings” action menu items. ([#18544](https://github.com/craftcms/cms/discussions/18544))
- Entries’ “Entry type settings” and “Section settings” action menu items are now only shown for element edit screens’ primary action menus.
- Category indexes can now have “Group” columns. ([#18553](https://github.com/craftcms/cms/discussions/18553))
- Element slideouts now automatically refresh when the same element is updated in another tab/slideout. ([#18625](https://github.com/craftcms/cms/pull/18625))
- Added the “Time Zone” user preference. ([#8518](https://github.com/craftcms/cms/discussions/8518))
- Element indexes now automatically refresh after duplicating elements and the queue is completed, if there’s an active search term. ([#18636](https://github.com/craftcms/cms/issues/18636))
- Timestamps in the control panel now include their time zone abbreviation. ([#18639](https://github.com/craftcms/cms/pull/18639))

### Administration
- Time fields’ “Max Time” settings can now be set to an earlier time than “Min Time”, for overnight time ranges. ([#18575](https://github.com/craftcms/cms/pull/18575))
- Newlines in system message bodies are now replaced with `<br>` tags. ([#18058](https://github.com/craftcms/cms/discussions/18058))
- Added the `--to-default` option to `resave` commands. ([#18522](https://github.com/craftcms/cms/pull/18522))

### Development
- Added the `heading()`/`h()` and `h1()`…`h6()` Twig functions. ([#18524](https://github.com/craftcms/cms/pull/18524))
- The `tag()` function now accepts a string for its second argument. ([#18524](https://github.com/craftcms/cms/pull/18524))
- The `|time` and `|datetime` Twig filters now have `$withTimeZone` arguments. ([#18639](https://github.com/craftcms/cms/pull/18639))
- The `|timestamp` filter now returns the current time, if applied to a `null`/empty string value. ([#18642](https://github.com/craftcms/cms/pull/18642))
- `delete` GraphQL queries now have a `hardDelete` argument. ([#18511](https://github.com/craftcms/cms/pull/18511))
- Entry `postDate` values are now `null` on creation, rather than set to the `dateCreated` value. ([#18642](https://github.com/craftcms/cms/pull/18642))
- Assets’ `url` GraphQL fields’ `immediately` arguments are no longer deprecated. ([#18581](https://github.com/craftcms/cms/issues/18581))
- `craft\fields\data\LinkData::getUrl()` now has an `$anyStatus` argument, which can be set to `false` to prevent a value from being returned if a disabled/pending/expired element is linked. ([#18527](https://github.com/craftcms/cms/issues/18527))

### Extensibility
- Added `craft\base\DefaultableFieldInterface`. ([#18522](https://github.com/craftcms/cms/pull/18522))
- Added `craft\base\ElementInterface::setDirtyFieldTracking()`.
- Added `craft\elements\PopulateElementEvent::$content`.
- Added `craft\validators\TimeValidator::$outOfRange`. ([#18575](https://github.com/craftcms/cms/pull/18575))
- Added `Craft.CpScreenSlideout::reload()`. ([#18625](https://github.com/craftcms/cms/pull/18625))
- `craft\elements\PopulateElementEvent::$row` no longer includes `fieldValues` or `generatedFieldValues` keys.
- `craft\helpers\DateTimeHelper::timeZoneAbbreviation()` is no longer deprecated, and now has a `$date` argument.
- `craft\i18n\Formatter::asTime()` and `asDatetime()` now have `$withTimeZone` arguments. ([#18639](https://github.com/craftcms/cms/pull/18639))
- `Craft.CP` now triggers a `queueCompleted` event when the last queue job is completed.

### System
- Improve the image quality of WEBP transforms, when `optimizeImageFilesize` is disabled. ([#18635](https://github.com/craftcms/cms/pull/18635))
- Updated Twig to 3.24. ([#18259](https://github.com/craftcms/cms/discussions/18259), [#18454](https://github.com/craftcms/cms/issues/18454))
- Fixed a bug where nested entries weren’t getting loaded with their content, if they had an entry type that was no longer allowed by their Matrix field.
- Fixed the wording of the validation error when saving a nested entry with an invalid entry type. ([#18506](https://github.com/craftcms/cms/issues/18506))
