# Release Notes for Craft CMS 5.13 (WIP)

### Addresses

- United Kingdom administrative areas (counties) now come from `commerceguys/addressing`, and are stored by their ISO codes rather than their names. Existing UK addresses are migrated automatically, and Administrative Area condition rules that reference county names are normalized to the new codes. ([#19488](https://github.com/craftcms/cms/pull/19488))
- `craft\services\Addresses::getUsedFields()` and `getUsedSubdivisionFields()` now remove duplicate fields added by event handlers. ([#19488](https://github.com/craftcms/cms/pull/19488))

### Administration

- Craft no longer bundles Composer. Installing, updating, and removing Composer packages now requires Composer 2.2 or later to be installed and available in the PHP process’s `PATH` environment variable. Control panel package installation and updating are disabled when Composer is unavailable; update information and management of already-downloaded plugins remain available.
- Terminal commands shown in the control panel and console output for the current environment now use `ddev craft` and `ddev composer` when Craft is running in DDEV, including the Plugin Store’s installation instructions and the OAuth signing key instructions on the **Settings** → **MCP** and **My Account** → **Tokens** screens. The Plugin Store no longer shows separate “Shell” and “DDEV” tabs. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Commands suggested by the `entrify/*`, `entry-types/merge`, `fields/merge`, `fields/auto-merge`, `setup`, `setup/welcome`, and `update` console commands now use `ddev craft` when Craft is running in DDEV, and `php craft` otherwise. ([#20702](https://github.com/craftcms/cms/pull/20702))

### Development

- Added the `craftCommand()` Twig function. ([#20487](https://github.com/craftcms/cms/pull/20487))
- GraphQL API requests can now pass a GraphQL token via an `X-Craft-Gql-Token` header. When the `Authorization` header also contains a user’s OAuth access token or personal access token, the GraphQL token determines the schema, and the user is logged in for the request, so queries with `withProvisionalDrafts` swap in their provisional drafts. ([#20587](https://github.com/craftcms/cms/issues/20587))
- Link fields can now be eager-loaded, including nested paths such as `linkField.image`. ([#20842](https://github.com/craftcms/cms/pull/20842))

### Extensibility

- Added `craft\services\Composer::MIN_VERSION`, `getIsAvailable()`, `getIsAvailableForUi()`, and `getUnavailableReason()`.
- Added `craft\helpers\App::composerCommand()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\App::craftCommand()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\App::isDdev()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\ElementHelper::getProvisionalDraftUser()`. ([#20587](https://github.com/craftcms/cms/issues/20587))
- `craft\fields\Link` now implements `craft\base\EagerLoadingFieldInterface`. ([#20842](https://github.com/craftcms/cms/pull/20842))
- Added `craft\fields\linktypes\BaseElementLinkType::eagerLoadingMaps()`. ([#20842](https://github.com/craftcms/cms/pull/20842))

### Performance

- Improved the performance of address queries filtered by owner on PostgreSQL. ([#20834](https://github.com/craftcms/cms/issues/20834))
- Improved the performance of element queries that use `unique()` on PostgreSQL. ([#20675](https://github.com/craftcms/cms/pull/20675))
- Requests now read only the parts of the project config they use from the cache, rather than the whole config, which cuts cache traffic on sites with a networked cache such as Redis or Valkey. After a project config change, only one request rewrites the cached config. ([#20809](https://github.com/craftcms/cms/pull/20809))
- Link fields now fetch the linked elements of all elements from the same query together, the first time one is needed. ([#20842](https://github.com/craftcms/cms/pull/20842))

### Dependencies

- Updated `commerceguys/addressing` to v2.3. ([#19488](https://github.com/craftcms/cms/pull/19488))

### Bug Fixes

- Fixed a bug where eager-loading a Link field replaced its value with an empty element collection. ([#20842](https://github.com/craftcms/cms/pull/20842))
- Fixed a bug where only the first eager-loading map was used when a field returned several. ([#20842](https://github.com/craftcms/cms/pull/20842))
