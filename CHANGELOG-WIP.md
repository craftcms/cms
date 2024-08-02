# Release notes for Craft CMS 4.11 (WIP)

### Content Management
- Entry and category conditions now have a “Has Descendants” rule. ([#15276](https://github.com/craftcms/cms/discussions/15276))
- “Replace file” actions now display success notices on complete. ([#15217](https://github.com/craftcms/cms/issues/15217))
- Double-clicking on folders within asset indexes and folder selection modals now navigates the index/modal into the folder. ([#15238](https://github.com/craftcms/cms/discussions/15238))

### Administration
- Added the `env`, `env/set`, and `env/remove` commands. ([#15431](https://github.com/craftcms/cms/pull/15431))
- New sites’ Base URL settings now default to an environment variable name based on the site name. ([#15347](https://github.com/craftcms/cms/pull/15347))
- Craft now warns against using the `@web` alias for URL settings, regardless of whether it was explicitly defined. ([#15347](https://github.com/craftcms/cms/pull/15347))

### Development
- Added the `withCustomFields` element query param.
- Added support for application-type based `general` and `db` configs (e.g. `config/general.web.php`). ([#15346](https://github.com/craftcms/cms/pull/15346))
- `general` and `db` config files can now return a callable that modifies an existing config object. ([#15346](https://github.com/craftcms/cms/pull/15346))
- Added the `lazyGqlTypes` config setting. ([#15429](https://github.com/craftcms/cms/issues/15429))
- The `allowedGraphqlOrigins` config setting is now deprecated. `craft\filters\Cors` should be used instead. ([#15397](https://github.com/craftcms/cms/pull/15397))
- The `permissionsPolicyHeader` config settings is now deprecated. `craft\filters\Headers` should be used instead. ([#15397](https://github.com/craftcms/cms/pull/15397))
- `{% cache %}` tags now cache any asset bundles registered within them.
- Country field values are now set to `CommerceGuys\Addressing\Country\Country` objects. ([#15455](https://github.com/craftcms/cms/issues/15455), [#15463](https://github.com/craftcms/cms/pull/15463))
- Auto-populated section and category group Template settings are now suffixed with `.twig`.
- `x-craft-preview`/`x-craft-live-preview` URL query string params are now added to generated URLs for Live Preview requests, so `craft\web\Request::getIsPreview()` continues to return `true` on subsequent pages loaded within the iframe. ([#15447](https://github.com/craftcms/cms/discussions/15447)) 

### Extensibility
- Added `craft\config\GeneralConfig::addAlias()`. ([#15346](https://github.com/craftcms/cms/pull/15346))
- Added `craft\elements\Address::getCountry()`. ([#15463](https://github.com/craftcms/cms/pull/15463))
- Added `craft\elements\Asset::$sanitizeOnUpload`. ([#15430](https://github.com/craftcms/cms/discussions/15430))
- Added `craft\filters\Cors`. ([#15397](https://github.com/craftcms/cms/pull/15397))
- Added `craft\filters\Headers`. ([#15397](https://github.com/craftcms/cms/pull/15397))
- Added `craft\helpers\App::configure()`.
- Added `craft\web\View::clearAssetBundleBuffer()`.
- Added `craft\web\View::startAssetBundleBuffer()`.
- Added `Craft.EnvVarGenerator`.
- `craft\helpers\UrlHelper::cpUrl()` now returns URLs based on the primary site’s base URL (if it has one), for console requests if the `baseCpUrl` config setting isn’t set, and the `@web` alias wasn’t explicitly defined. ([#15374](https://github.com/craftcms/cms/issues/15374))
- `craft\services\Config::setDotEnvVar()` now accepts `false` for its `value` argument, which removes the environment variable from the `.env` file.
- Deprecated `craft\web\assets\elementresizedetector\ElementResizeDetectorAsset`.

### System
- Improved the performance of element indexes in structure view.
- The control panel now displays Ajax response-defined error messages when provided, rather than a generic “server error” message. ([#15292](https://github.com/craftcms/cms/issues/15292))
- Craft no longer sets the `Permissions-Policy` header on control panel responses. ([#15348](https://github.com/craftcms/cms/issues/15348))
- Control panel `resize` events now use ResizeObserver.
- Craft no longer ensures that the `cpresources` folder is writable.
- Front-end queue runner scripts are now injected before the `</body>` tag, rather than at the end of the response HTML.
- Updated Yii to 2.0.51.
- Updated yii2-debug to 2.1.25.
- Updated svg-sanitizer to 0.19.
- Updated Axios to 0.28.1. ([#15448](https://github.com/craftcms/cms/issues/15448))
- Fixed a bug where error messages returned by the `users/send-password-reset-email` action weren’t accounting for the `useEmailAsUsername` config setting. ([#15425](https://github.com/craftcms/cms/issues/15425))
