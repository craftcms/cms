# Release Notes for Craft CMS 4.18 (WIP)

### Development
- The `|default` Twig filter and `is empty` Twig test now treat all `yii\base\Model` instances as not empty. ([#18727](https://github.com/craftcms/cms/issues/18727)) 
- Added `craft\filters\SecFetchSiteFilter` for request origin verification. ([#18641](https://github.com/craftcms/cms/pull/18641))

### Extensibility
- Removed `craft\controllers\AppController::actionResourceJs()`. ([#18559](https://github.com/craftcms/cms/pull/18559))

### System
- Cross-domain script tags added by JavaScript are now loaded directly, rather than via a proxy. ([#18559](https://github.com/craftcms/cms/pull/18559))
- Updated the built-in composer.phar to 2.9.7. ([#18761](https://github.com/craftcms/cms/issues/18761))
- Fixed a [moderate-severity](https://github.com/craftcms/cms/security/policy#severity--remediation) JavaScript injection vulnerability. (GHSA-c55v-343g-5xff)
