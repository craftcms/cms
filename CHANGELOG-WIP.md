# Release Notes for Craft CMS 5.13 (WIP)

### Addresses

- United Kingdom administrative areas (counties) now come from `commerceguys/addressing`, and are stored by their ISO codes rather than their names. Existing UK addresses are migrated automatically, and Administrative Area condition rules that reference county names are normalized to the new codes. ([#19488](https://github.com/craftcms/cms/pull/19488))
- `craft\services\Addresses::getUsedFields()` and `getUsedSubdivisionFields()` now remove duplicate fields added by event handlers. ([#19488](https://github.com/craftcms/cms/pull/19488))

### Administration

- Terminal commands shown in the control panel and console output for the current environment now use `ddev craft` and `ddev composer` when Craft is running in DDEV, including the Plugin Store’s installation instructions and the OAuth signing key instructions on the **Settings** → **MCP** and **My Account** → **Tokens** screens. The Plugin Store no longer shows separate “Shell” and “DDEV” tabs. ([#20487](https://github.com/craftcms/cms/pull/20487))

### MCP

- MCP upload URLs from `assets_upload_url` now send files directly to the temporary asset upload filesystem’s storage when it supports that, avoiding request size limits, such as on Craft Cloud. Its response now includes `headers`, which clients must send with the file. ([#20630](https://github.com/craftcms/cms/pull/20630))

### Development

- Added the `craftCommand()` Twig function. ([#20487](https://github.com/craftcms/cms/pull/20487))

### Extensibility

- Added `craft\base\DirectUploadFsInterface`, which filesystems can implement to accept files sent directly to their storage. ([#20630](https://github.com/craftcms/cms/pull/20630))
- Added `craft\helpers\App::composerCommand()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\App::craftCommand()`. ([#20487](https://github.com/craftcms/cms/pull/20487))
- Added `craft\helpers\App::isDdev()`. ([#20487](https://github.com/craftcms/cms/pull/20487))

### Dependencies

- Updated `commerceguys/addressing` to v2.3. ([#19488](https://github.com/craftcms/cms/pull/19488))
