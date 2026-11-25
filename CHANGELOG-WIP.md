# Release Notes for Craft CMS 5.13 (WIP)

### Addresses

- United Kingdom administrative areas (counties) now come from `commerceguys/addressing`, and are stored by their ISO codes rather than their names. Existing UK addresses are migrated automatically, and Administrative Area condition rules that reference county names are normalized to the new codes. ([#19488](https://github.com/craftcms/cms/pull/19488))
- `craft\services\Addresses::getUsedFields()` and `getUsedSubdivisionFields()` now remove duplicate fields added by event handlers. ([#19488](https://github.com/craftcms/cms/pull/19488))

### Administration

- Craft no longer bundles Composer. Installing, updating, and removing Composer packages now requires Composer 2.2 or later to be installed and available in the PHP process’s `PATH` environment variable. Control panel package installation and updating are disabled when Composer is unavailable; update information and management of already-downloaded plugins remain available.
- Terminal commands shown in the control panel and console output for the current environment now use `ddev craft` and `ddev composer` when Craft is running in DDEV, including the Plugin Store’s installation instructions and the OAuth signing key instructions on the **Settings** → **MCP** and **My Account** → **Tokens** screens. The Plugin Store no longer shows separate “Shell” and “DDEV” tabs. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Commands suggested by the `entrify/*`, `entry-types/merge`, `fields/merge`, `fields/auto-merge`, `setup`, `setup/welcome`, and `update` console commands now use `ddev craft` when Craft is running in DDEV, and `php craft` otherwise. ([#20702](https://github.com/craftcms/cms/pull/20702))
- Added the “HTML Purifier caches” option to the Caches utility and the `clear-caches/html-purifier` command. ([#20861](https://github.com/craftcms/cms/pull/20861))

### Assets

- Assets can now be uploaded straight to filesystems that accept direct uploads, such as Craft Cloud’s, so files don’t pass through Craft’s request handling or its request size limits. Uploads are started with the `uploads/start` action, sent to the returned URL, and completed with `uploads/complete`.
- The `assets_upload_url` MCP tool now accepts an optional `size` argument, and returns `headers` that must be sent with the upload. Its upload URLs send files straight to the temporary asset upload filesystem when it accepts direct uploads.
- Asset indexes, Assets fields and the “Replace file” action upload files this way for volumes whose filesystem implements `craft\base\TemporaryUploadUrlInterface`.
- Front-end forms can upload files the same way by registering the `craft\web\assets\uploads\UploadsAsset` asset bundle and adding a `data-upload-session` attribute to file inputs. Chosen files are uploaded right away and replaced with hidden inputs holding the new asset IDs.
- Guests can upload assets when a `craft\services\Uploads::EVENT_AUTHORIZE_UPLOAD` event handler allows them. Guests can start 60 uploads per minute per IP address, unless the `defaultRateLimit` config setting is disabled.

### Control Panel

- The control panel is now available in Croatian, and its Indonesian, Kyrgyz, and Welsh translations are now complete. Previously, most of their strings were shown in English.

### Development

- Front-end forms no longer need CSRF tokens. Requests that browsers mark with a `Sec-Fetch-Site: same-origin` header, or without `Sec-Fetch-Site` and with an `Origin` header matching the site’s scheme and host, are accepted without one. ([#21020](https://github.com/craftcms/cms/pull/21020))
- Added the `csrfOriginOnly` config setting. When enabled, CSRF tokens are never checked and `csrfInput()` outputs nothing, so pages that call it can be cached. ([#21020](https://github.com/craftcms/cms/pull/21020))
- Added the `craftCommand()` Twig function. ([#20487](https://github.com/craftcms/cms/pull/20487))
- GraphQL API requests can now pass a GraphQL token via an `X-Craft-Gql-Token` header. When the `Authorization` header also contains a user’s OAuth access token or personal access token, the GraphQL token determines the schema, and the user is logged in for the request, so queries with `withProvisionalDrafts` swap in their provisional drafts. ([#20587](https://github.com/craftcms/cms/issues/20587))
- The authenticated MCP server now supports HTTP basic auth. When a `craft\filters\BasicHttpAuthLogin` filter applies to it, either site-wide or via `filters` in `config/mcp.php`, clients authenticate with a username and password instead of an access token. ([#20844](https://github.com/craftcms/cms/issues/20844))
- Added the `element_sources_get` and `element_sources_save` MCP tools, for reading and saving element index sidebars.
- Added the `condition_schema` MCP tool, which lists the rules a condition accepts for custom sources, field layout visibility, and relation field selection.
- MCP tools now reject condition rules that Craft would otherwise drop when saving field layouts and relation fields.
- MCP tools now return field settings as plain objects, and accept field layouts in the shape `configuration_get` returns them.

### Extensibility

- Added `craft\services\Composer::MIN_VERSION`, `getIsAvailable()`, `getIsAvailableForUi()`, and `getUnavailableReason()`.
- Added `craft\helpers\App::composerCommand()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\App::craftCommand()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\App::isDdev()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\ElementHelper::getProvisionalDraftUser()`. ([#20587](https://github.com/craftcms/cms/issues/20587))
- Added `craft\helpers\HtmlPurifier::enableDefinitionCache()`. ([#20861](https://github.com/craftcms/cms/pull/20861))
- Added `craft\services\Path::getHtmlPurifierCachePath()`. ([#20861](https://github.com/craftcms/cms/pull/20861))
- Added `craft\base\ApplicationTrait::getUploads()`.
- Added `craft\base\TemporaryUploadUrlInterface`.
- Added `craft\controllers\UploadsController`.
- Added `craft\events\AuthorizeUploadEvent`.
- Added `craft\services\Uploads`.
- Added `craft\uploads\AssetUploadHandler`.
- Added `craft\uploads\StagedUpload`.
- Added `craft\uploads\TemporaryUploadHandler`.
- Added `craft\uploads\UploadHandlerInterface`.
- Added `craft\web\assets\uploads\UploadsAsset`.
- Added `Craft.UploadSessionUploader` and `Craft.Uploads.FileUpload`.
- `Craft.createUploader()` now returns a `Craft.UploadSessionUploader` for filesystems that implement `craft\base\TemporaryUploadUrlInterface`.
- Removed the `mcp/upload` action. MCP upload URLs now use `uploads/transfer`.

### Performance

- Improved the performance of address queries filtered by owner on PostgreSQL. ([#20834](https://github.com/craftcms/cms/issues/20834))
- Improved the performance of element queries that use `unique()` on PostgreSQL. ([#20675](https://github.com/craftcms/cms/pull/20675))
- Requests now read only the parts of the project config they use from the cache, rather than the whole config, which cuts cache traffic on sites with a networked cache such as Redis or Valkey. After a project config change, only one request rewrites the cached config. ([#20809](https://github.com/craftcms/cms/pull/20809))
- `craft\helpers\HtmlPurifier::process()` now caches HTML Purifier’s HTML definition when it’s passed a config array or no config, rather than rebuilding it on every call. HTML Purifier’s definition caches are now stored in `storage/runtime/htmlpurifier/`. ([#20847](https://github.com/craftcms/cms/issues/20847))

### System

- Fixed a bug where eager-loading maps that covered more than one element type, or were returned as a list of maps, only loaded the elements from one of them.

### Dependencies

- Updated `commerceguys/addressing` to v2.3. ([#19488](https://github.com/craftcms/cms/pull/19488))
