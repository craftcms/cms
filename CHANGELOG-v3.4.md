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
- Set Password and Verify Email links now use the `setPasswordPath` and `verifyEmailPath` config settings. ([#4925](https://github.com/craftcms/cms/issues/4925))
- Plugins can now modify the GraphQL schema by listening for the `defineGqlTypeFields` event.
- Plugins can now modify the GraphQL permissions by listening for the `registerGqlPermissions` event.
- Full GraphQL schema is now always generated when `devMode` is set to `true`.