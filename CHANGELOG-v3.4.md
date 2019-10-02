# Running Release Notes for Craft 3.4

### Added
- Added the `verifyEmailPath` config setting.

### Changed
- Sections’ entry URI format settings are now shown when running Craft in headless mode. ([#4934](https://github.com/craftcms/cms/issues/4934))
- The “Primary entry page” preview target is now user-customizable alongside all other preview targets in sections’ settings. ([#4520](https://github.com/craftcms/cms/issues/4520))
- Control panel requests are now always set to the primary site, regardless of the URL they were accessed from.
- Set Password and Verify Email links now use the `setPasswordPath` and `verifyEmailPath` config settings. ([#4925](https://github.com/craftcms/cms/issues/4925))
