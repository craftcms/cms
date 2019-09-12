# Running Release Notes for Craft 3.4

### Added
- Added the `verifyEmailPath` config setting.

### Changed
- Control panel requests are now always set to the primary site, regardless of the URL they were accessed from.
- Set Password and Verify Email links now use the `setPasswordPath` and `verifyEmailPath` config settings. ([#4925](https://github.com/craftcms/cms/issues/4925))
