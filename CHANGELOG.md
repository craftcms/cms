# Release Notes for Craft CMS 4

## Unreleased

### Added
- Added `craft\behaviors\SessionBehavior::getError()`.
- Added `craft\behaviors\SessionBehavior::getNotice()`.

### Changed
- Craft now requires PHP 7.4 or later.
- Relational fields now load elements in the current site rather than the primary site, if the source element isn’t localizable. ([#7048](https://github.com/craftcms/cms/issues/7048))
- `craft\base\Model::setAttributes()` now normalizes date attributes into `DateTime` objects.
- `craft\base\Model::datetimeAttributes()` is now called from the constructor, instead of the `init()` method.
- `craft\services\UserPermissions::getAllPermissions()` and `getAssignablePermissions()` now return permission groups as arrays with `heading` and `permission` sub-keys, fixing a bug where two groups with the same heading would conflict with each other. ([#7771](https://github.com/craftcms/cms/issues/7771))
- Updated Twig to 3.3.
- Updated vue-autosuggest to 2.2.0.

### Deprecated
- Deprecated `craft\helpers\ArrayHelper::append()`. `array_unshift()` should be used instead.
- Deprecated `craft\helpers\ArrayHelper::prepend()`. `array_push()` should be used instead.

### Removed
- Removed the `suppressTemplateErrors` config setting.
- Removed the `craft.categoryGroups` Twig variable.
- Removed the `craft.config` Twig variable.
- Removed the `craft.deprecator` Twig variable.
- Removed the `craft.elementIndexes` Twig variable.
- Removed the `craft.emailMessages` Twig variable.
- Removed the `craft.feeds` Twig variable.
- Removed the `craft.fields` Twig variable.
- Removed the `craft.globals` Twig variable.
- Removed the `craft.i18n` Twig variable.
- Removed the `craft.request` Twig variable.
- Removed the `craft.sections` Twig variable.
- Removed the `craft.session` Twig variable.
- Removed the `craft.systemSettings` Twig variable.
- Removed the `craft.userGroups` Twig variable.
- Removed the `craft.userPermissions` Twig variable.
- Removed `craft\web\View::$minifyCss`.
- Removed `craft\web\View::$minifyJs`.
- Removed `craft\web\View::renderTemplateMacro()`.
- Removed `craft\web\twig\Template`.
- Removed `craft\web\twig\variables\CategoryGroups`.
- Removed `craft\web\twig\variables\Config`.
- Removed `craft\web\twig\variables\Deprecator`.
- Removed `craft\web\twig\variables\ElementIndexes`.
- Removed `craft\web\twig\variables\EmailMessages`.
- Removed `craft\web\twig\variables\Feeds`.
- Removed `craft\web\twig\variables\Fields`.
- Removed `craft\web\twig\variables\Globals`.
- Removed `craft\web\twig\variables\I18N`.
- Removed `craft\web\twig\variables\Request`.
- Removed `craft\web\twig\variables\Sections`.
- Removed `craft\web\twig\variables\SystemSettings`.
- Removed `craft\web\twig\variables\UserGroups`.
- Removed `craft\web\twig\variables\UserPermissions`.
- Removed `craft\web\twig\variables\UserSession`.
