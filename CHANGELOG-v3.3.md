# Running Release Notes for Craft CMS 3.3

## Added
- Added the `tag()` Twig function, which renders a complete HTML tag.
- Added the `|attr` Twig filter, which modifies the attributes on an HTML tag. ([#4660](https://github.com/craftcms/cms/issues/4660))
- Added the `|append` and `|prepend` Twig filters, which add new HTML elements as children of an HTML tag. ([#3937](https://github.com/craftcms/cms/issues/3937))
- Added the `purgeStaleUserSessionDuration` config setting.
- Admin users can now opt into getting the full stack trace view when an uncaught exception occurs when Dev Mode isn’t enabled. ([#4765](https://github.com/craftcms/cms/issues/4765))
- Admin users can now opt into having Twig templates profiled when Dev Mode inn’t enabled.
- Control Panel subnav items can now have badge counts. ([#4756](https://github.com/craftcms/cms/issues/4756))
- Added `craft\helpers\Html::appendToTag()`.
- Added `craft\helpers\Html::modifyTagAttributes()`.
- Added `craft\helpers\Html::normalizeTagAttributes()`.
- Added `craft\helpers\Html::parseTag()`.
- Added `craft\helpers\Html::parseTagAttributes()`.
- Added `craft\helpers\Html::prependToTag()`.
- Added `craft\helpers\Template::beginProfile()`.
- Added `craft\helpers\Template::endProfile()`.
- Added `craft\web\twig\nodevisitors\Profiler`.
- Added `craft\web\twig\nodes\ProfileNode`.

## Changed
- Global set reference tags can now refer to the global set by its handle. ([#4645](https://github.com/craftcms/cms/issues/4645))
- Improved Twig template profiling to include blocks and macros.
- Twig template profiling no longer occurs when Dev Mode isn’t enabled, unless an admin user is logged in and has opted into it.
- The `_layouts/forms/field.html` template now supports `label`, `instructions`, `tip`, `warning`, and `input` blocks that can be overridden when including the template with an `{% embed %}` tag.
- Craft no longer overrides the base Twig template class, unless the now-deprecated `` config setting is enabled. ([#4755](https://github.com/craftcms/cms/issues/4755))

## Deprecated
- Deprecated the `suppressTemplateErrors` config setting.
- Deprecated `craft\web\twig\Template`.
