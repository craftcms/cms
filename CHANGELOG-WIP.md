# Release Notes for Craft CMS 5.5 (WIP)

### Content Management
- Improved the styling of element cards with thumbnails. ([#15692](https://github.com/craftcms/cms/pull/15692), [#15673](https://github.com/craftcms/cms/issues/15673))
- Elements within element selection inputs now have “Replace” actions.
- Address index tables can now include “Country” columns.

### Accessibility
- Improved the control panel for screen readers. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Improved keyboard control. ([#15665](https://github.com/craftcms/cms/pull/15665))
- Improved the color contrast of required field indicators. ([#15665](https://github.com/craftcms/cms/pull/15665))

### Administration
- All relation fields can now be selected as field layouts’ thumbnail providers. ([#15651](https://github.com/craftcms/cms/discussions/15651))
- Added the “Markdown” field layout UI element type. ([#15674](https://github.com/craftcms/cms/pull/15674), [#15664](https://github.com/craftcms/cms/discussions/15664))

### Extensibility
- Added `craft\base\RequestTrait::getIsWebRequest()`. ([#15690](https://github.com/craftcms/cms/pull/15690))
- Added `craft\helpers\StringHelper::firstLine()`.

### System
- Fixed a bug where the Recovery Codes slideout content overflowed its container on small screens. ([#15665](https://github.com/craftcms/cms/pull/15665))
