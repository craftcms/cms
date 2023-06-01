# Release Notes for Craft CMS 5.0 (WIP)

### Content Management

### Accessibility
- Improved source item navigation for screen readers. ([#12054](https://github.com/craftcms/cms/pull/12054))
- Content tab menus are now implemented as disclosure menus. ([#12963](https://github.com/craftcms/cms/pull/12963))

### Administration
- Entry types are now managed independently of sections.
- Added support for defining custom locale aliases, via a new `localeAliases` config setting. ([#12705](https://github.com/craftcms/cms/pull/12705))
- `entrify/*` commands now ask if an entry type already exists for the section.

### Development
- Entry type names and handles must now be unique globally, rather than just within a single section. Existing entry type names and handles will be renamed automatically where needed, to ensure uniqueness.
- Entries’ GraphQL type names are no longer prefixed with their section’s handle.

### Extensibility
- Elements now store their content in an `elements_sites.content` column as JSON, rather than across multiple columns in a `content` table. ([#2009](https://github.com/craftcms/cms/issues/2009), [#4308](https://github.com/craftcms/cms/issues/4308), [#7221](https://github.com/craftcms/cms/issues/7221), [#7750](https://github.com/craftcms/cms/issues/7750), [#12954](https://github.com/craftcms/cms/issues/12954))
- The control panel now defines new CSS variables for orange, green, and violet colors. Existing color palette CSS variables have been updated to match the Tailwind 3 color palette.
- All core element query param methods now return `static` instead of `self`. ([#11868](https://github.com/craftcms/cms/pull/11868))
- Selectize menus no longer apply special styling to options with the value `new`. The `_includes/forms/selectize.twig` control panel template should be used instead (or `craft\helpers\Cp::selectizeHtml()`/`selectizeFieldHtml()`), which will append an styled “Add” option when `addOptionFn` and `addOptionLabel` settings are passed. ([#11946](https://github.com/craftcms/cms/issues/11946))
- The `assets/move-asset` and `assets/move-folder` actions no longer include `success` keys in responses. ([#12159](https://github.com/craftcms/cms/pull/12159))
- The `assets/upload` controller action now includes `errors` object in failure responses. ([#12159](https://github.com/craftcms/cms/pull/12159))
- Added `craft\base\FieldInterface::dbType()`, which defines the type(s) of values the field will store in the `elements_sites.content` column (if any).
- Added `craft\base\FieldInterface::getQueryCondition()`, which accepts an element query param value and returns the corresponding query condition.
- Added `craft\base\FieldInterface::getValueSql()`.
- Added `craft\controllers\EntryTypesController`.
- Added `craft\db\Connection::getIsMaria()`.
- Added `craft\db\mysql\ColumnSchema::$collation`.
- Added `craft\db\mysql\QueryBuilder::jsonContains()`.
- Added `craft\db\mysql\QueryBuilder::jsonExtract()`.
- Added `craft\db\mysql\Schema::supportsMb4()`.
- Added `craft\db\pgsql\QueryBuilder::jsonContains()`.
- Added `craft\db\pgsql\QueryBuilder::jsonExtract()`.
- Added `craft\db\pgsql\Schema::supportsMb4()`.
- Added `craft\db\QueryParam`.
- Added `craft\db\Table::SECTIONS_ENTRYTYPES`.
- Added `craft\elements\db\ElementQueryInterface::fieldLayouts()`
- Added `craft\helpers\Db::defaultCollation()`.
- Added `craft\helpers\Db::prepareForJsonColumn()`.
- Added `craft\helpers\Gql::getSchemaContainedSections()`.
- Added `craft\helpers\ProjectConfig::ensureAllEntryTypesProcessed()`.
- Added `craft\i18n\Locale::$aliasOf`.
- Added `craft\i18n\Locale::setDisplayName()`.
- Added `craft\migrations\BaseContentRefactorMigration`.
- Added `craft\models\Section::getCpEditUrl()`.
- Added `craft\services\Fields::$fieldContext`, which replaces `craft\services\Content::$fieldContext`.
- Added `craft\services\Gql::getOrSetContentArguments()`.
- Added `craft\services\Gql::defineContentArgumentsForFieldLayouts()`.
- Added `craft\services\Gql::defineContentArgumentsForFields()`.
- Added `craft\web\twig\variables\Cp::getEntryTypeOptions()`.
- Renamed `craft\base\FieldInterface::valueType()` to `phpType()`.
- Renamed `craft\web\CpScreenResponseBehavior::$additionalButtons()` and `additionalButtons()` to `$additionalButtonsHtml` and `additionalButtonsHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$content()` and `content()` to `$contentHtml` and `contentHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$contextMenu()` and `contextMenu()` to `$contextMenuHtml` and `contextMenuHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$notice()` and `notice()` to `$noticeHtml` and `noticeHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$pageSidebar()` and `pageSidebar()` to `$pageSidebarHtml` and `pageSidebarHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- Renamed `craft\web\CpScreenResponseBehavior::$sidebar()` and `sidebar()` to `$metaSidebarHtml` and `metaSidebarHtml()`. ([#13037](https://github.com/craftcms/cms/pull/13037))
- `craft\db\Connection::getSupportsMb4()` is now dynamic for MySQL installs, based on whether the `elements_sites` table has an `mb4` charset.
- `craft\fields\BaseOptionsField::$multi` and `$optgroups` properties are now static.
- `craft\gql\mutations\Entry::createSaveMutations()` now accepts a `$section` argument.
- `craft\helpers\Db::parseParam()`, `parseDateParam()`, `parseMoneyParam()`, and `parseNumericParam()` now return `null` instead of an empty string if no condition should be applied.
- `craft\i18n\I18N::getPrimarySiteLocale()` is now deprecated. `craft\models\Site::getLocale()` should be used instead.
- `craft\i18n\I18N::getPrimarySiteLocaleId()` is now deprecated. `craft\models\Site::$language` should be used instead.
- Removed `craft\base\Element::$contentId`.
- Removed `craft\base\ElementInterface::getContentTable()`.
- Removed `craft\base\ElementInterface::getFieldColumnPrefix()`.
- Removed `craft\base\ElementInterface::gqlMutationNameByContext()`.
- Removed `craft\base\ElementInterface::hasContent()`.
- Removed `craft\base\FieldInterface::getContentColumnType()`. `dbType()` should be implemented instead.
- Removed `craft\base\FieldInterface::hasContentColumn()`. Fields that don’t need to store values in the `elements_sites.content` column should return `null` from `dbType()`.
- Removed `craft\base\FieldInterface::modifyElementsQuery()`. Fields can customize how their element query params are handled by implementing `getQueryCondition()`.
- Removed `craft\elements\db\ElementQuery::$contentTable`.
- Removed `craft\helpers\Db::extractGlue()`. `craft\db\QueryParam::extractOperator()` can be used instead.
- Removed `craft\helpers\Db::GLUE_AND`, `GLUE_OR`, and `GLUE_NOT`. `craft\db\QueryParam::AND`, `OR`, and `NOT` can be used instead.
- Removed `craft\helpers\ElementHelper::fieldColumn()`.
- Removed `craft\helpers\ElementHelper::fieldColumnFromField()`.
- Removed `craft\helpers\FieldHelper`.
- Removed `craft\helpers\Gql::canMutateEntries()`.
- Removed `craft\models\EntryType::$sectionId`.
- Removed `craft\models\EntryType::$sortOrder`.
- Removed `craft\models\EntryType::getSection()`.
- Removed `craft\records\EntryType::getSection()`.
- Removed `craft\services\Content`.
- Removed `craft\services\Fields::updateColumn()`.
- Removed `craft\services\Matrix::defineContentTableName()`.
- Removed `craft\services\Sections::reorderEntryTypes()`.
- Removed `craft\controllers\Sections::actionEntryTypesIndex()`.
- Removed `craft\controllers\Sections::actionEditEntryType()`.
- Removed `craft\controllers\Sections::actionSaveEntryType()`.
- Removed `craft\controllers\Sections::actionReorderEntryTypes()`.
- Removed `craft\controllers\Sections::actionDeleteEntryType()`.

### System
- Craft now requires PHP 8.1 or later.
- New database tables now default to the `utf8mb4` charset, and the `utf8mb4_0900_ai_ci` or `utf8mb4_unicode_ci` collation, on MySQL. Existing installs should run `db/convert-charset` after upgrading, to ensure all tables have consistent charsets and collations. ([#11823](https://github.com/craftcms/cms/discussions/11823))
- The `defaultTemplateExtensions` config setting now lists `twig` before `html` by default. ([#11809](https://github.com/craftcms/cms/discussions/11809))
