# Release Notes for Craft CMS 5.9 (WIP)

### Content Management
- Chips and cards are generally no longer hyperlinked. ([#17591](https://github.com/craftcms/cms/pull/17591))

### Accessibility
- Improved the accessibility of the Orientation setting within the Image Editor’s crop tool. ([#17690](https://github.com/craftcms/cms/pull/17690))

### Administration
- Users’ User Groups settings now show a component select input, and support inline group editing/creation on environments that allow administrative changes.
- Control panel-defined routes now have action menus with “Move up”/“Move down” actions. ([#17706](https://github.com/craftcms/cms/pull/17706))

### Extensibility
- Added `craft\web\twig\nodes\BaseNode`.
- Added `Craft.BaseElementIndex::asyncSelectDefaultSource()`.
- Added `Craft.BaseElementIndex::asyncSelectSource()`.
- Added `Craft.BaseElementIndex::asyncSelectSourceByKey()`.
- Added `Craft.BaseElementIndex::ensureSourceAttributeInfo()`.
- Deprecated `Craft.BaseElementIndex::selectDefaultSource()`.
- Deprecated `Craft.BaseElementIndex::selectSource()`.
- Deprecated `Craft.BaseElementIndex::selectSourceByKey()`.

### System
- Improved element index performance. ([#17557](https://github.com/craftcms/cms/pull/17557))
- Updated Twig to 3.21. ([#17603](https://github.com/craftcms/cms/discussions/17603))
