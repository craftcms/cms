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
- Queue jobs’ Job Data is now syntax-highlighted in the Queue Manager utility.
- The Project Config utility’s “Loaded Project Config Data” is now syntax-highlighted.
- The global Settings page now has an “Extend” section, which contains Plugins and MCP.
- Assets settings are now in the “Content” section of the global Settings page, and the “Media” section has been removed.
- Filesystem settings are now within Assets settings, at Settings → Assets → Filesystems.
- Added Settings → MCP, where administrators can enable the authenticated and public MCP servers, set their endpoints, and approve the capabilities and content that the public server exposes. ([#20145](https://github.com/craftcms/cms/pull/20145), [#20189](https://github.com/craftcms/cms/pull/20189))
- Added the `oauth/generate-keys` command, which generates the keys used to sign OAuth access tokens. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added support for `config/mcp.php` and `config/oauth.php` config files. ([#20145](https://github.com/craftcms/cms/pull/20145))

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
- Added an authenticated HTTP MCP server, which MCP clients can connect to after a user signs in and authorizes them via OAuth. Clients act as that user, within their permissions and the access level they grant (read only, read and write, or full access for admins). ([#20145](https://github.com/craftcms/cms/pull/20145), [#20189](https://github.com/craftcms/cms/pull/20189))
- The authenticated MCP server’s endpoints default to `/{cpTrigger}/mcp` and `/{cpTrigger}/mcp/auth`, or `/mcp` and `/mcp/auth` when public registration is enabled. Endpoints within the control panel can only be used by users with control panel access. ([#20189](https://github.com/craftcms/cms/pull/20189))
- Added a public HTTP MCP server, which exposes only the capabilities and content approved from Settings → MCP, without signing in. Its endpoint defaults to `/mcp`. ([#20145](https://github.com/craftcms/cms/pull/20145), [#20189](https://github.com/craftcms/cms/pull/20189))
- Added the `mcp/serve` command, which runs a stdio MCP server acting as a given user. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added an OAuth authorization server, with discovery metadata, dynamic client registration, PKCE, and refresh token rotation, which the MCP servers and GraphQL API use. ([#20145](https://github.com/craftcms/cms/pull/20145))
- GraphQL API requests can now be authenticated with OAuth access tokens, granting access to the content the authorizing user has permission to access. ([#20147](https://github.com/craftcms/cms/pull/20147))
- GraphQL schema components can now support authenticated requests via an `authenticated` key on their config. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added the `currentUser` GraphQL query, which returns the user that authenticated the request. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added the My Account → Tokens screen, where users can create personal access tokens for the APIs that offer them (such as the GraphQL API), rename them, and revoke tokens and the apps they’ve authorized. ([#20147](https://github.com/craftcms/cms/pull/20147))

### Extensibility

- Addresses now support drafts.
- Element fixtures no longer create revisions by default. Set their `createRevisions` property to `true` to retain the previous behavior. ([#19626](https://github.com/craftcms/cms/pull/19626))
- Added `craft\base\ApplicationTrait::getOAuth()`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\base\ApplicationTrait::getPersonalAccessTokens()`. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\base\Element::showSitesField()`. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Added `craft\base\Element::sitesFieldHtml()`. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Added `craft\base\FieldTrait::$tip`.
- Added `craft\base\FieldTrait::$warning`.
- Added `craft\base\InputSchemaFieldInterface`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\base\NestedElementTrait::$touchOwnersOnSave`.
- Added `craft\base\Renderable`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Added `craft\config\McpConfig`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\config\OAuthConfig`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\elements\Asset::KIND_MARKDOWN`. ([#20089](https://github.com/craftcms/cms/pull/20089))
- Added `craft\elements\db\EagerLoadPlan::$configureQuery`. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\elements\db\NestedElementQueryTrait::mustHaveField()`.
- Added `craft\elements\db\NestedElementQueryTrait::mustHaveOwner()`.
- Added `craft\events\RegisterOAuthResourcesEvent`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\fieldlayoutelements\BaseField::defaultTip()`.
- Added `craft\fieldlayoutelements\BaseField::defaultWarning()`.
- Added `craft\gql\base\ElementMutationResolver::requireUserAuthorization()`. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\gql\GqlInputSchema`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\gql\queries\Settings`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\CategoryGroup`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\EntryType`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\Section`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\Site`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\TagGroup`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\UserGroup`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\gql\types\Volume`. ([#20055](https://github.com/craftcms/cms/pull/20055))
- Added `craft\helpers\Gql::getSchemaUser()`. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\helpers\Template::render()`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Added `craft\i18n\Locale::languageId()`.
- Added the `craft\mcp` and `craft\oauth` namespaces. ([#20145](https://github.com/craftcms/cms/pull/20145), [#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\models\GqlSchema::$user`. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\models\Volume::$propagationMethod`. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Added `craft\services\Config::CATEGORY_MCP`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\services\Config::CATEGORY_OAUTH`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\services\Config::getMcp()`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\services\Config::getOAuth()`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\services\Gql::getOAuthResource()`. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\services\Gql::getSchemaForUser()`. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\services\Gql::SCOPE_READ`. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\services\Gql::SCOPE_WRITE`. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\services\Images::getSupportsBmp()`.
- Added `craft\services\Images::getSupportsJxl()`.
- Added `craft\services\Mcp`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\services\OAuth`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\services\PersonalAccessTokens`. ([#20147](https://github.com/craftcms/cms/pull/20147))
- Added `craft\test\ElementFixtureTrait`. ([#19626](https://github.com/craftcms/cms/pull/19626))
- Added `craft\utilities\ClearCaches::clear()`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- Added `craft\web\assets\highlight\HighlightAsset`. ([#20088](https://github.com/craftcms/cms/pull/20088))
- Plugins can register MCP capabilities via `craft\services\Mcp::EVENT_REGISTER_CAPABILITY_TYPES`, OAuth resources via `craft\services\OAuth::EVENT_REGISTER_RESOURCES`, and personal access token scopes via `craft\services\PersonalAccessTokens::registerScopeGroup()`. ([#20145](https://github.com/craftcms/cms/pull/20145), [#20147](https://github.com/craftcms/cms/pull/20147))
- Plain Text, Email, Link, Number, Money, Lightswitch, Date, Time, Color, Country, Table, and option fields now implement `craft\base\InputSchemaFieldInterface`. ([#20145](https://github.com/craftcms/cms/pull/20145))
- `Illuminate\Support\Collection` now has a `render()` macro, which replaces `craft\elements\ElementCollection::render()`. ([#19988](https://github.com/craftcms/cms/pull/19988))
- `craft\helpers\ElementHelper::normalizeSlug()` now has a `$language` argument, which defaults to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- `craft\helpers\StringHelper::toLowerCase()`, `::toTitleCase()`, and `::toUpperCase()` now have `$language` arguments, which default to the current application language. ([#19558](https://github.com/craftcms/cms/pull/19558))
- `craft\services\Assets::getAssetById()` now returns the asset in another site if it doesn’t exist in the current site, when no site ID is passed. ([#20060](https://github.com/craftcms/cms/pull/20060))
- `elements/save-draft` action responses now include a `canDeleteForSite` key. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Deprecated `craft\helpers\ElementHelper::renderElements()`. `craft\helpers\Template::render()` should be used instead. ([#19988](https://github.com/craftcms/cms/pull/19988))
- Deprecated `craft\web\assets\prismjs\PrismJsAsset`. `craft\web\assets\highlight\HighlightAsset` should be used instead. `PrismJsAsset` now registers a `Prism` shim backed by TanStack Highlight, which supports Prism’s commonly-used APIs, but not grammar customization, hooks, or plugins. ([#20088](https://github.com/craftcms/cms/pull/20088))

### System

- Added support for JPEG XL (`.jxl`) images, when ImageMagick supports it. JPEG XL images can now be uploaded, transformed, and set as a transform’s output format.
- BMP images can now be transformed and edited in the Image Editor.
- Added the “Markdown” file kind, and `md` and `markdown` are now included in the default `allowedFileExtensions` config setting value. Markdown files can be previewed with syntax highlighting. ([#20089](https://github.com/craftcms/cms/pull/20089))
- MySQL database backups and restores now carry over SSL/TLS settings from the system’s MySQL option files (e.g. `ssl-ca` or `disable-ssl-verify-server-cert`), if the database connection doesn’t have any `PDO::MYSQL_ATTR_SSL_*` attributes set. ([#19366](https://github.com/craftcms/cms/pull/19366))
- MySQL database backups and restores now respect the `PDO::MYSQL_ATTR_SSL_CAPATH` and `PDO::MYSQL_ATTR_SSL_CIPHER` database connection attributes. ([#19366](https://github.com/craftcms/cms/pull/19366))
- Craft can now determine the dimensions of BMP and JPEG XL images on remote volumes without downloading them.
- Saving nested elements on multi-site installs now executes fewer database queries. ([#20090](https://github.com/craftcms/cms/pull/20090))
- Search indexing now executes fewer database queries. ([#20090](https://github.com/craftcms/cms/pull/20090))
- User permission checks are now faster for users with many permissions. ([#20149](https://github.com/craftcms/cms/pull/20149))
- Removed support for the non-standard `ED256` passkey algorithm. ([#19701](https://github.com/craftcms/cms/pull/19701))
- Updated Axios to 1.20.0. 
- Updated Twig to 3.30.
- Updated Fabric.js to v7. ([#19628](https://github.com/craftcms/cms/pull/19628))
- Asset previews and the Project Config utility now use TanStack Highlight for syntax highlighting, rather than Prism. ([#20088](https://github.com/craftcms/cms/pull/20088))
- Fixed a bug where entry and address indexes weren’t showing any results if they had a “Field” condition rule set to “is empty”.
- Fixed a bug where `craft\services\Elements::deleteElementsForSite()` would remove elements’ site rows without deleting the elements, on single-site installs. ([#20060](https://github.com/craftcms/cms/pull/20060))
- Fixed a bug where saving a deeply-nested element on its own wouldn’t update its owners’ `dateUpdated` timestamps, which could cause new revisions to reuse stale nested content. ([#19594](https://github.com/craftcms/cms/issues/19594))
- Fixed a bug where deleting an element for a site while editing a provisional draft would delete the provisional draft for all sites. ([#20061](https://github.com/craftcms/cms/pull/20061))
- Craft now requires `league/oauth2-server`, `mcp/sdk`, and `symfony/finder`. ([#20145](https://github.com/craftcms/cms/pull/20145))
