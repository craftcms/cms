# Release Notes for Craft CMS 5.12 (WIP)

### Content Management

- Addresses within Addresses fields can now be edited from drafts of their owner elements, by users who can save the drafts but not the canonical owner elements. ([#19881](https://github.com/craftcms/cms/issues/19881))
- Element edit pages now have a “Sites” pane in their sidebars on multi-site installs, in place of the “Status” pane, which lists the sites the element is saved to (that the user can edit), with status toggles and links to the element in each site, and lets authors add the element to other sites. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Element editor slideouts can now be switched to other sites, by clicking on a site name within the “Sites” pane. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Sites that are added to elements within the “Sites” pane can now be removed again. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Element edit pages’ sidebar pane headings now line up with the labels within the panes. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Assets are now initially saved to the site they were uploaded from. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Assets in volumes set to let each asset choose which sites it should be saved to now have a “Sites” pane in their edit pages’ sidebars. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Assets in volumes set to let each asset choose which sites it should be saved to can now be deleted for individual sites, as long as they exist in another site. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Deleting an element for the only site it exists in now soft-deletes it, unless it’s a draft or nested element. ([#20060](https://github.com/craftcms/cms/pull/20060))

### Administration

- Custom fields now have “Default Tip” and “Default Warning” settings. ([#19976](https://github.com/craftcms/cms/pull/19976))
- Volumes now have a “Propagation Method” setting, which can be set to “Let each asset choose which sites it should be saved to”. New volumes default to this, as do existing volumes on single-site installs. Existing volumes on multi-site installs continue to save assets to all sites. ([#20060](https://github.com/craftcms/cms/pull/20060))
- A rate limit is now enforced for `users/login`, `auth/verify-totp`, and `auth/verify-recovery-code` requests. ([#20054](https://github.com/craftcms/cms/pull/20054))
- Added the `defaultRateLimit` and `defaultRateLimitDuration` config settings, which configure the rate limits for `users/login`, `users/send-password-reset-email`, `auth/verify-totp`, and `auth/verify-recovery-code` requests. ([#20054](https://github.com/craftcms/cms/pull/20054))

### Development

- The `capitalize`, `lower`, `title`, and `upper` Twig filters now have `language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- Added the `project-config/check` command, which checks project config schema compatibility without applying changes.
- Added the `{% render %}` Twig tag. ([#19988](https://github.com/craftcms/cms/pull/19988))
- `{% render %}` tags and `render()` methods are allowed within the Twig sandbox. ([#19988](https://github.com/craftcms/cms/pull/19988))
- GraphQL schemas can now include read-only components for sites, sections, entry types, volumes, user groups, category groups, and tag groups. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added the `sites`, `site`, `sections`, `section`, `entryTypes`, `entryType`, `volumes`, `volume`, `userGroups`, `userGroup`, `categoryGroups`, `categoryGroup`, `tagGroups`, and `tagGroup` GraphQL queries, which are available when the schema includes the corresponding components. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Elements now have `site` fields in GraphQL, when the schema includes the “Query for sites” component. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Entries now have `section` and `type` fields in GraphQL, when the schema includes the “Query for sections” and “Query for entry types” components. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Assets now have `volume` fields in GraphQL, when the schema includes the “Query for volumes” component. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Users now have `groups` fields in GraphQL, when the schema includes the “Query for user groups” component. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Categories and tags now have `group` fields in GraphQL, when the schema includes the “Query for category groups” and “Query for tag groups” components. ([#20055](https://github.com/craftcms/cms/pull/20055))

### Extensibility

- Addresses now support drafts.
- Element fixtures no longer create revisions by default. Set their `createRevisions` property to `true` to retain the previous behavior. ([#19626](https://github.com/craftcms/cms/pull/19626))
- Added `craft\base\Element::hasCustomPropagation()`. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Added `craft\base\Element::showSitesField()`. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Added `craft\base\Element::sitesFieldHtml()`. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Added `craft\base\FieldTrait::$tip`.
- Added `craft\base\FieldTrait::$warning`.
- Added `craft\base\NestedElementTrait::$touchOwnersOnSave`.
- Added `craft\base\Renderable`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Added `craft\elements\db\NestedElementQueryTrait::mustHaveField()`.
- Added `craft\elements\db\NestedElementQueryTrait::mustHaveOwner()`.
- Added `craft\fieldlayoutelements\BaseField::defaultTip()`.
- Added `craft\fieldlayoutelements\BaseField::defaultWarning()`.
- Added `craft\gql\queries\Settings`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\CategoryGroup`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\EntryType`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\Section`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\Site`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\TagGroup`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\UserGroup`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\Volume`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\helpers\Template::render()`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Added `craft\i18n\Locale::languageId()`.
- Added `craft\models\Volume::$propagationMethod`. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Added `craft\services\Images::getSupportsBmp()`.
- Added `craft\services\Images::getSupportsJxl()`.
- Added `craft\test\ElementFixtureTrait`. ([#19626](https://github.com/craftcms/cms/pull/19626))
- `Illuminate\Support\Collection` now has a `render()` macro, which replaces `craft\elements\ElementCollection::render()`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- `craft\helpers\ElementHelper::normalizeSlug()` now has a `$language` argument, which defaults to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- `craft\helpers\StringHelper::toLowerCase()`, `::toTitleCase()`, and `::toUpperCase()` now have `$language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- `craft\services\Assets::getAssetById()` now returns the asset in another site if it doesn’t exist in the current site, when no site ID is passed. ([#20060](https://github.com/craftcms/cms/pull/20060))
- `elements/save-draft` action responses now include a `canDeleteForSite` key. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Deprecated `craft\helpers\ElementHelper::renderElements()`. `craft\helpers\Template::render()` should be used instead. ([#19988](https://github.com/craftcms/cms/pull/19988))

### System

- Added support for JPEG XL (`.jxl`) images, when ImageMagick supports it. JPEG XL images can now be uploaded, transformed, and set as a transform’s output format.
- BMP images can now be transformed and edited in the Image Editor.
- MySQL database backups and restores now carry over SSL/TLS settings from the system’s MySQL option files (e.g. `ssl-ca` or `disable-ssl-verify-server-cert`), if the database connection doesn’t have any `PDO::MYSQL_ATTR_SSL_*` attributes set. ([#19366](https://github.com/craftcms/cms/pull/19366))
- MySQL database backups and restores now respect the `PDO::MYSQL_ATTR_SSL_CAPATH` and `PDO::MYSQL_ATTR_SSL_CIPHER` database connection attributes. ([#19366](https://github.com/craftcms/cms/pull/19366))
- Craft can now determine the dimensions of BMP and JPEG XL images on remote volumes without downloading them.
- Removed support for the non-standard `ED256` passkey algorithm. ([#19701](https://github.com/craftcms/cms/pull/19701))
- Updated Axios to 1.20.0. 
- Updated Twig to 3.30.
- Updated Fabric.js to v7. ([#19628](https://github.com/craftcms/cms/pull/19628))
- Fixed a bug where entry and address indexes weren’t showing any results if they had a “Field” condition rule set to “is empty”.
- Fixed a bug where `craft\services\Elements::deleteElementsForSite()` would remove elements’ site rows without deleting the elements, on single-site installs. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Fixed a bug where saving a deeply-nested element on its own wouldn’t update its owners’ `dateUpdated` timestamps, which could cause new revisions to reuse stale nested content. ([#19594](https://github.com/craftcms/cms/issues/19594))
- Fixed a bug where deleting an element for a site while editing a provisional draft would delete the provisional draft for all sites. ([#20061](https://github.com/craftcms/cms/pull/20061))
