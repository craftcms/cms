# Release Notes for Craft CMS 4.18 (WIP)

### Development
- Added `craft\filters\SecFetchSiteFilter` for request origin verification. ([#18641](https://github.com/craftcms/cms/pull/18641))

### Extensibility
- Removed `craft\controllers\AppController::actionResourceJs()`. ([#18559](https://github.com/craftcms/cms/pull/18559))

### System
- Cross-domain script tags added by JavaScript are now loaded directly, rather than via a proxy. ([#18559](https://github.com/craftcms/cms/pull/18559))
- Fixed a [moderate-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) JavaScript injection vulnerability. (GHSA-c55v-343g-5xff)
