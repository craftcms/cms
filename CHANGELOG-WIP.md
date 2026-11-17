# Release Notes for Craft CMS 5.13 (WIP)

### Addresses

- United Kingdom administrative areas (counties) now come from `commerceguys/addressing`, and are stored by their ISO codes rather than their names. Existing UK addresses are migrated automatically, and Administrative Area condition rules that reference county names are normalized to the new codes. ([#19488](https://github.com/craftcms/cms/pull/19488))
- `craft\services\Addresses::getUsedFields()` and `getUsedSubdivisionFields()` now remove duplicate fields added by event handlers. ([#19488](https://github.com/craftcms/cms/pull/19488))

### Administration

- Craft no longer bundles Composer. Installing, updating, and removing Composer packages now requires Composer 2.2 or later to be installed and available in the PHP process’s `PATH` environment variable. Control panel package installation and updating are disabled when Composer is unavailable; update information and management of already-downloaded plugins remain available.
- Terminal commands shown in the control panel and console output for the current environment now use `ddev craft` and `ddev composer` when Craft is running in DDEV, including the Plugin Store’s installation instructions and the OAuth signing key instructions on the **Settings** → **MCP** and **My Account** → **Tokens** screens. The Plugin Store no longer shows separate “Shell” and “DDEV” tabs. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Commands suggested by the `entrify/*`, `entry-types/merge`, `fields/merge`, `fields/auto-merge`, `setup`, `setup/welcome`, and `update` console commands now use `ddev craft` when Craft is running in DDEV, and `php craft` otherwise. ([#20702](https://github.com/craftcms/cms/pull/20702))

### Assets

- Assets can now be uploaded straight to filesystems that accept direct uploads, such as Craft Cloud’s, so files don’t pass through Craft’s request handling or its request size limits. Uploads are started with the `uploads/start` action, sent to the returned URL, and completed with `uploads/complete`.
- The `assets_upload_url` MCP tool now accepts an optional `size` argument, and returns `headers` that must be sent with the upload. Its upload URLs send files straight to the temporary asset upload filesystem when it accepts direct uploads.
- Asset indexes, Assets fields and the “Replace file” action upload files this way for volumes whose filesystem implements `craft\base\TemporaryUploadUrlInterface`.
- Guests can upload assets when a `craft\services\Uploads::EVENT_AUTHORIZE_UPLOAD` event handler allows them. Guests can start 60 uploads per minute per IP address, unless the `defaultRateLimit` config setting is disabled.

### Development

- Added the `craftCommand()` Twig function. ([#20487](https://github.com/craftcms/cms/pull/20487))
- GraphQL API requests can now pass a GraphQL token via an `X-Craft-Gql-Token` header. When the `Authorization` header also contains a user’s OAuth access token or personal access token, the GraphQL token determines the schema, and the user is logged in for the request, so queries with `withProvisionalDrafts` swap in their provisional drafts. ([#20587](https://github.com/craftcms/cms/issues/20587))

### Extensibility

- Added `craft\services\Composer::MIN_VERSION`, `getIsAvailable()`, `getIsAvailableForUi()`, and `getUnavailableReason()`.
- Added `craft\helpers\App::composerCommand()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\App::craftCommand()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\App::isDdev()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\ElementHelper::getProvisionalDraftUser()`. ([#20587](https://github.com/craftcms/cms/issues/20587))
- Added `craft\base\ApplicationTrait::getUploads()`.
- Added `craft\base\TemporaryUploadUrlInterface`.
- Added `craft\controllers\UploadsController`.
- Added `craft\events\AuthorizeUploadEvent`.
- Added `craft\services\Uploads`.
- Added `craft\uploads\AssetUploadHandler`.
- Added `craft\uploads\StagedUpload`.
- Added `craft\uploads\TemporaryUploadHandler`.
- Added `craft\uploads\UploadHandlerInterface`.
- Added `Craft.UploadSessionUploader`.
- `Craft.createUploader()` now returns a `Craft.UploadSessionUploader` for filesystems that implement `craft\base\TemporaryUploadUrlInterface`.
- Removed the `mcp/upload` action. MCP upload URLs now use `uploads/transfer`.

### Performance

- Improved the performance of element queries that use `unique()` on PostgreSQL. ([#20675](https://github.com/craftcms/cms/pull/20675))

### Dependencies

- Updated `commerceguys/addressing` to v2.3. ([#19488](https://github.com/craftcms/cms/pull/19488))
