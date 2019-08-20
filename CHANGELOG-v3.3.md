# Running Release Notes for Craft CMS 3.3

## Added
- Added the `tag()` Twig function, which renders a complete HTML tag.
- Added the `|attr` Twig filter, which modifies the attributes on an HTML tag. ([#4660](https://github.com/craftcms/cms/issues/4660))
- Added the `|append` and `|prepend` Twig filters, which add new HTML elements as children of an HTML tag. ([#3937](https://github.com/craftcms/cms/issues/3937))
- Added the `purgeStaleUserSessionDuration` config setting.
- Admin users can now opt into getting the full stack trace view when an uncaught exception occurs when Dev Mode isn’t enabled. ([#4765](https://github.com/craftcms/cms/issues/4765))
- Control Panel subnav items can now have badge counts. ([#4756](https://github.com/craftcms/cms/issues/4756))
- Added `craft\helpers\Html::appendToTag()`.
- Added `craft\helpers\Html::modifyTagAttributes()`.
- Added `craft\helpers\Html::normalizeTagAttributes()`.
- Added `craft\helpers\Html::parseTag()`.
- Added `craft\helpers\Html::parseTagAttributes()`.
- Added `craft\helpers\Html::prependToTag()`.

## Changed
- Global set reference tags can now refer to the global set by its handle. ([#4645](https://github.com/craftcms/cms/issues/4645))
- The `_layouts/forms/field.html` template now supports `label`, `instructions`, `tip`, `warning`, and `input` blocks that can be overridden when including the template with an `{% embed %}` tag.
