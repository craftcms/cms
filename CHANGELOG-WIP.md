# Release Notes for Craft CMS 5.10 (WIP)

### Content Management
- Number fields now show their selected currency beside their input, if their Preview Format setting is set to “As currency values”. ([#18498](https://github.com/craftcms/cms/pull/18498))

### Administration
- Newlines in system message bodies are now replaced with `<br>` tags. ([#18058](https://github.com/craftcms/cms/discussions/18058))
- Added the `--to-default` option to `resave` commands. ([#18522](https://github.com/craftcms/cms/pull/18522))

### Development
- Added the `heading()`/`h()` and `h1()`…`h6()` Twig functions. ([#18524](https://github.com/craftcms/cms/pull/18524))
- The `tag()` function now accepts a string for its second argument. ([#18524](https://github.com/craftcms/cms/pull/18524)) 
- `delete` GraphQL queries now have a `hardDelete` argument. ([#18511](https://github.com/craftcms/cms/pull/18511))

### Extensibility
- Added `craft\base\DefaultableFieldInterface`. ([#18522](https://github.com/craftcms/cms/pull/18522))
- Added `craft\elements\PopulateElementEvent::$content`.
- `craft\elements\PopulateElementEvent::$row` no longer includes `fieldValues` or `generatedFieldValues` keys.

### System
- Updated Twig to 3.23. ([#18259](https://github.com/craftcms/cms/discussions/18259))
- Fixed a bug where nested entries weren’t getting loaded with their content, if they had an entry type that was no longer allowed by their Matrix field.
- Fixed the wording of the validation error when saving a nested entry with an invalid entry type. ([#18506](https://github.com/craftcms/cms/issues/18506))
