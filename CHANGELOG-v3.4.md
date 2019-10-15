# Running Release Notes for Craft 3.4

### Added
- Added the `verifyEmailPath` config setting.
- Added the `{% requireGuest %}` tag, which redirects a user to the path specified by the `postLoginRedirect` config setting if they’re already logged in. ([#5015](https://github.com/craftcms/cms/pull/5015))
- Added `craft\events\DefineGqlTypeFieldsEvent`.
- Added `craft\events\DefineGqlValidationRulesEvent`.
- Added `craft\events\RegisterGqlPermissionsEvent`.
- Added `craft\gql\TypeManager`.
- Added `craft\services\Gql::getValidationRules()`.
- Added `craft\web\Controller::requireGuest()`.
- Added `craft\web\User::guestRequired()`.
- Added `craft\web\twig\nodes\RequireGuestNode`.
- Added `craft\web\twig\tokenparsers\RequireGuestTokenParser`.
- Added `craft\web\twig\variables\Paginate::getDynamicRangeUrls()`, making it easy to create Google-style pagination links. ([#5005](https://github.com/craftcms/cms/issues/5005))

### Changed
- Sections’ entry URI format settings are now shown when running Craft in headless mode. ([#4934](https://github.com/craftcms/cms/issues/4934))
- The “Primary entry page” preview target is now user-customizable alongside all other preview targets in sections’ settings. ([#4520](https://github.com/craftcms/cms/issues/4520))
- Control panel requests are now always set to the primary site, regardless of the URL they were accessed from.
- Plain Text fields can now specify a maximum size in bytes. ([#5099](https://github.com/craftcms/cms/issues/5099))
- Plain Text fields’ Column Type settings now have an “Automatic” option, which is selected by default for new fields. ([#5099](https://github.com/craftcms/cms/issues/5099))
- Set Password and Verify Email links now use the `setPasswordPath` and `verifyEmailPath` config settings. ([#4925](https://github.com/craftcms/cms/issues/4925))
- `resave/*` commands now have an `--update-search-index` argument (defaults to `false`). ([#4840](https://github.com/craftcms/cms/issues/4840))
- Plugins can now modify the GraphQL schema by listening for the `defineGqlTypeFields` event.
- Plugins can now modify the GraphQL permissions by listening for the `registerGqlPermissions` event.
- Full GraphQL schema is now always generated when `devMode` is set to `true`.
- `craft\services\Elements::saveElement()` now has an `$updateSearchIndex` argument (defaults to `true`). ([#4840](https://github.com/craftcms/cms/issues/4840))
- `craft\services\Elements::resaveElements()` now has an `$updateSearchIndex` argument (defaults to `false`). ([#4840](https://github.com/craftcms/cms/issues/4840))
