# Release Notes for Craft CMS 4.5 (WIP)

### Content Management
- Table fields can now have a “Row heading” column. ([#13231](https://github.com/craftcms/cms/pull/13231))
- Table fields now have a “Static Rows” setting. ([#13231](https://github.com/craftcms/cms/pull/13231))
- Table fields no longer show a heading row, if all heading values are blank. ([#13231](https://github.com/craftcms/cms/pull/13231))
- Element slideouts now show their sidebar content full-screen for elements without a field layout, rather than having an empty body. ([#13056](https://github.com/craftcms/cms/pull/13056), [#13053](https://github.com/craftcms/cms/issues/13053))
- Relational fields no longer track the previously-selected element(s) when something outside the field is clicked on. ([#13123](https://github.com/craftcms/cms/issues/13123))
- Element indexes now use field layouts’ overridden field labels, if all field layouts associated with an element source use the same label. ([#8903](https://github.com/craftcms/cms/discussions/8903))
- Improved the styling and max height of Selectize inputs. ([#13065](https://github.com/craftcms/cms/discussions/13065), [#13176](https://github.com/craftcms/cms/pull/13176))
- Selectize inputs now support click-and-drag selection. ([#13273](https://github.com/craftcms/cms/discussions/13273))
- Selectize single-select inputs now automatically select the current value on focus. ([#13273](https://github.com/craftcms/cms/discussions/13273)) 

### Accessibility
- Image assets’ thumbnails and `<img>` tags generated via `craft\element\Asset::getImg()` no longer use the assets’ titles as `alt` fallback values. ([#12854](https://github.com/craftcms/cms/pull/12854))
- Element index pages now have visually-hidden “Sources” headings for screen readers. ([#12961](https://github.com/craftcms/cms/pull/12961))
- Element metadata fields now have visually-hidden “Metadata” headings for screen readers. ([#12961](https://github.com/craftcms/cms/pull/12961))
- Structure elements within element indexes now convey their levels to screen readers. ([#13020](https://github.com/craftcms/cms/pull/13020))
- Non-image asset thumbnails in the control panel now have `alt` attributes set to the file extension. ([#12724](https://github.com/craftcms/cms/pull/12724))
- Improved copy-text buttons for screen readers. ([#13073](https://github.com/craftcms/cms/pull/13073))
- Improved the contrast of asset file type icons. ([#13262](https://github.com/craftcms/cms/pull/13262))

### Administration
- Custom element sources can now be configured to only appear for certain sites. ([#13344](https://github.com/craftcms/cms/discussions/13344))
- The “My Account” page no longer shows a “Require a password reset on next login” checkbox.
- The Asset Indexes utility no longer shows the “Cache remote images” option on ephemeral environments. ([#13202](https://github.com/craftcms/cms/issues/13202))
- It’s now possible to configure UK addresses to show a “County” field. ([#13361](https://github.com/craftcms/cms/pull/13361))

### Development
- Added a new `_globals` global Twig variable for front-end templates, which can be used to store custom values in a global scope. ([#13050](https://github.com/craftcms/cms/pull/13050), [#12951](https://github.com/craftcms/cms/discussions/12951))
- The `|replace` Twig filter now supports passing in a hash with regular expression keys. ([#12956](https://github.com/craftcms/cms/issues/12956))
- `{% exit %}` tags now support passing a message after the status code. ([#13166](https://github.com/craftcms/cms/discussions/13166))
- Built-in element types’ GraphQL queries now support passing `null` to `relatedToAssets`, `relatedToEntries`, `relatedToUsers`, `relatedToCategories`, `relatedToTags`, and `relatedToAll` arguments. ([#7954](https://github.com/craftcms/cms/issues/7954))
- Elements now include custom field values when being iterated over, and when being merged. ([#13009](https://github.com/craftcms/cms/issues/13009))
- Dropdown and Radio Buttons fields now have a “Column Type” setting, which will be set to `varchar` for existing fields, and defaults to “Automatic” for new fields. ([#13025](https://github.com/craftcms/cms/pull/13025), [#12954](https://github.com/craftcms/cms/issues/12954))

### Extensibility
- Filesystem types can now register custom file uploaders. ([#13313](https://github.com/craftcms/cms/pull/13313))
- When applying a draft, the canonical elements’ `getDirtyAttributes()` and `getDirtyFields()` methods now return the attribute names and field handles that were modified on the draft for save events. ([#12967](https://github.com/craftcms/cms/issues/12967))
- Added `craft\addresses\SubdivisionRepository`. ([#13361](https://github.com/craftcms/cms/pull/13361))
- Added `craft\base\ElementInterface::getThumbSvg()`. ([#13262](https://github.com/craftcms/cms/pull/13262))
- Added `craft\base\ElementInterface::setDirtyFields()`.
- Added `craft\base\ElementInterface::setFieldValueFromRequest()`. ([#12935](https://github.com/craftcms/cms/issues/12935))
- Added `craft\base\FieldInterface::normalizeValueFromRequest()`. ([#12935](https://github.com/craftcms/cms/issues/12935))
- Added `craft\base\FieldLayoutProviderInterface`. ([#13250](https://github.com/craftcms/cms/pull/13250))
- Added `craft\base\FsInterface::getShowHasUrlSetting()`. ([#13224](https://github.com/craftcms/cms/pull/13224))
- Added `craft\base\FsInterface::getShowUrlSetting()`. ([#13224](https://github.com/craftcms/cms/pull/13224))
- Added `craft\base\FsTrait::$showHasUrlSetting`. ([#13224](https://github.com/craftcms/cms/pull/13224))
- Added `craft\base\FsTrait::$showUrlSetting`. ([#13224](https://github.com/craftcms/cms/pull/13224))
- Added `craft\controllers\AssetsControllerTrait`.
- Added `craft\elements\db\ElementQuery::EVENT_BEFORE_POPULATE_ELEMENT`.
- Added `craft\events\DefineAddressSubdivisionsEvent`. ([#13361](https://github.com/craftcms/cms/pull/13361))
- Added `craft\gql\GqlEntityRegistry::getOrCreate()`. ([#13354](https://github.com/craftcms/cms/pull/13354))
- Added `craft\helpers\Assets::iconSvg()`.
- Added `craft\helpers\StringHelper::escapeShortcodes()`. ([#12935](https://github.com/craftcms/cms/issues/12935))
- Added `craft\helpers\StringHelper::unescapeShortcodes()`. ([#12935](https://github.com/craftcms/cms/issues/12935))
- Added `craft\models\FieldLayout::$provider`. ([#13250](https://github.com/craftcms/cms/pull/13250))
- Added `craft\services\Addresses::$formatter`, which can be used to override the default address formatter. ([#13242](https://github.com/craftcms/cms/pull/13242), [#12615](https://github.com/craftcms/cms/discussions/12615))
- Added `craft\services\Addresses::EVENT_DEFINE_ADDRESS_SUBDIVISIONS`. ([#13361](https://github.com/craftcms/cms/pull/13361))
- Added `craft\services\Addresses::defineAddressSubdivisions()`. ([#13361](https://github.com/craftcms/cms/pull/13361))
- Added `craft\web\CpScreenResponseBehavior::$pageSidebar`, `pageSidebar()`, and `pageSidebarTemplate()`. ([#13019](https://github.com/craftcms/cms/pull/13019), [#12795](https://github.com/craftcms/cms/issues/12795))
- Added `craft\web\CpScreenResponseBehavior::$slideoutBodyClass`.
- `craft\helpers\Cp::selectizeFieldHtml()`, `selectizeHtml()`, and `_includes/forms/selectize.twig` now support a `multi` param. ([#13176](https://github.com/craftcms/cms/pull/13176))
- `craft\services\Assets::getRootFolderByVolumeId()` now ensures the root folder actually exists, and caches its results internally, improving performance. ([#13297](https://github.com/craftcms/cms/issues/13297))
- `craft\services\Assets::getThumbUrl()` now has an `$iconFallback` argument, which can be set to `false` to prevent a file icon URL from being returned as a fallback for assets that don’t have image thumbnails.
- `craft\validators\UniqueValidator` now supports setting an additional filter via the `filter` property. ([#12941](https://github.com/craftcms/cms/pull/12941))
- `craft\web\UrlManager` no longer triggers its `EVENT_REGISTER_CP_URL_RULES` and `EVENT_REGISTER_SITE_URL_RULES` events until the request is ready to be routed, making it safe to call `UrlManager::addRules()` from plugin/module constructors. ([#13109](https://github.com/craftcms/cms/issues/13109))
- Deprecated `craft\helpers\ArrayHelper::firstKey()`. `array_key_first()` should be used instead.
- Deprecated `craft\helpers\Assets::iconPath()`. `craft\helpers\Assets::iconSvg()` or `craft\elements\Asset::getThumbSvg()` should be used instead.
- Deprecated `craft\helpers\Assets::iconUrl()`.
- Deprecated `craft\helpers\UrlHelper::buildQuery()`. `http_build_query()` should be used instead.
- Deprecated `craft\services\Volumes::ensureTopFolder()`. `craft\services\Assets::getRootFolderByVolumeId()` should be used instead.
- Added `Craft.BaseUploader`. ([#13313](https://github.com/craftcms/cms/pull/13313))
- Added `Craft.createUploader()`. ([#13313](https://github.com/craftcms/cms/pull/13313))
- Added `Craft.registerUploaderClass()`. ([#13313](https://github.com/craftcms/cms/pull/13313))

### System
- Added support for setting environmental values in a “secrets” PHP file, identified by a `CRAFT_SECRETS_PATH` environment variable. ([#13283](https://github.com/craftcms/cms/pull/13283)) 
- All generated URL param characters are now properly encoded. ([#12796](https://github.com/craftcms/cms/issues/12796))
- `migrate` commands besides `migrate/create` no longer create the migration directory if it doesn’t exist yet. ([#12732](https://github.com/craftcms/cms/pull/12732))
- When `content` table columns are resized, if any existing values are too long, all column data is now backed up into a new table, and the overflowing values are set to `null`. ([#13025](https://github.com/craftcms/cms/pull/13025))
- When `content` table columns are renamed, if an existing column with the same name already exists, the original column data is now backed up into a new table and then deleted from the `content` table. ([#13025](https://github.com/craftcms/cms/pull/13025))
- Plain Text and Table fields no longer convert emoji to shortcodes on PostgreSQL.
- Improved GraphQL performance. ([#13354](https://github.com/craftcms/cms/pull/13354))
- Fixed a bug where Plain Text and Table fields were converting posted shortcode-looking strings to emoji. ([#12935](https://github.com/craftcms/cms/issues/12935))
- Fixed a bug where `craft\elements\Asset::getUrl()` was returning invalid URLs for GIF and SVG assets within filesystems without base URLs, if the `transformGifs` or `transformSvgs` config settings were disabled. ([#13306](https://github.com/craftcms/cms/issues/13306))
- Fixed a bug where the GraphQL API wasn’t enforcing schema site selections for the requested site. ([#13346](https://github.com/craftcms/cms/pull/13346))
- Updated Selectize to 0.15.2. ([#13273](https://github.com/craftcms/cms/discussions/13273))
