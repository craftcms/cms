# Release notes for Craft CMS 4.11 (WIP)

### Content Management
- Entry and category conditions now have a “Has Descendants” rule. ([#15276](https://github.com/craftcms/cms/discussions/15276))
- “Replace file” actions now display success notices on complete. ([#15217](https://github.com/craftcms/cms/issues/15217))
- Double-clicking on folders within asset indexes and folder selection modals now navigates the index/modal into the folder. ([#15238](https://github.com/craftcms/cms/discussions/15238))

### Administration
- New sites’ Base URL settings now default to an environment variable name based on the site name. ([#15347](https://github.com/craftcms/cms/pull/15347))
- Craft now warns against using the `@web` alias for URL settings, regardless of whether it was explicitly defined. ([#15347](https://github.com/craftcms/cms/pull/15347))
- The `up` command now runs `project-config/apply` regardless of whether the `dateModified` value had changed. ([#15322](https://github.com/craftcms/cms/issues/15322), [#15357](https://github.com/craftcms/cms/issues/15357))

### Development
- Added the `withCustomFields` element query param.
- Added support for application-type based `general` and `db` configs (e.g. `config/general.web.php`). ([#15346](https://github.com/craftcms/cms/pull/15346))
- `general` and `db` config files can now return a callable that modifies an existing config object. ([#15346](https://github.com/craftcms/cms/pull/15346))

### Extensibility
- Added `craft\config\GeneralConfig::addAlias()`. ([#15346](https://github.com/craftcms/cms/pull/15346))
- Added `Craft.EnvVarGenerator`.
- Deprecated `craft\web\assets\elementresizedetector\ElementResizeDetectorAsset`.

### System
- Improved the performance of element indexes in structure view.
- The control panel now displays Ajax response-defined error messages when provided, rather than a generic “server error” message. ([#15292](https://github.com/craftcms/cms/issues/15292))
- Craft no longer sets the `Permissions-Policy` header on control panel responses. ([#15348](https://github.com/craftcms/cms/issues/15348))
- Control panel `resize` events now use ResizeObserver.
- Craft no longer ensures that the `cpresources` folder is writable.
