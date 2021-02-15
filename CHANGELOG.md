# Release Notes for Craft CMS 4

## Unreleased

### Changed
- Craft now requires PHP 7.4 or later.
- Relational fields now load elements in the current site rather than the primary site, if the source element isn’t localizable. ([#7048](https://github.com/craftcms/cms/issues/7048))
- `craft\base\Model::setAttributes()` now normalizes date attributes into `DateTime` objects.
- `craft\base\Model::datetimeAttributes()` is now called from the constructor, instead of the `init()` method.
- Updated Twig to 3.3.
- Updated vue-autosuggest to 2.2.0.

### Deprecated
- Deprecated `craft\helpers\ArrayHelper::append()`. `array_unshift()` should be used instead.
- Deprecated `craft\helpers\ArrayHelper::prepend()`. `array_push()` should be used instead.

### Removed
- Removed the `suppressTemplateErrors` config setting.
- Removed `craft\web\twig\Template`.
- Removed `craft\web\View::$minifyCss`.
- Removed `craft\web\View::$minifyJs`.
- Removed `craft\web\View::renderTemplateMacro()`.
