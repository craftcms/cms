# Release notes for Craft CMS 5.6 (WIP)

### Accessibility
- Improved the accessibility of Checkboxes and Radio Buttons fields that allow custom options. ([#16080](https://github.com/craftcms/cms/pull/16080))

### Development
- Added the ability to specify a nested element’s owner element type. ([#16133](https://github.com/craftcms/cms/pull/16133))
- Added support for fallback element partial templates, e.g. `_partials/entry.twig` as opposed to `_partials/entry/typeHandle.twig`. ([#16125](https://github.com/craftcms/cms/pull/16125))

### Extensibility
- Added `craft\base\NestedElementTrait::$ownerElementType`.
