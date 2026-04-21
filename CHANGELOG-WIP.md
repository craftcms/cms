# Release Notes for Craft CMS 4.18 (WIP)

### Development
- Added `craft\filters\SecFetchSiteFilter` for request origin verification. ([#18641](https://github.com/craftcms/cms/pull/18641))
- `dataUrl()` is no longer allowed in sandboxed Twig environments by default.

### System
- Fixed a [moderate-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) path traversal vulnerability. (GHSA-287w-mxq6-x2cp)
