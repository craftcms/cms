# Release Notes for Craft CMS 5.2 (WIP)

### Content Management
- Element index checkboxes no longer have a lag when deselected, except within element selection modals. ([#14896](https://github.com/craftcms/cms/issues/14896))
- Improved mobile styling. ([#14910](https://github.com/craftcms/cms/pull/14910))
- Improved the look of slideouts.
- Table views within element index pages are no longer scrolled directly. ([#14927](https://github.com/craftcms/cms/pull/14927))

### Accessibility
- The secondary nav is now kept open during source selection for mobile viewports, preventing focus from being dropped. ([#14946](https://github.com/craftcms/cms/pull/14946))
- User edit screens’ document titles have been updated to describe the page purpose. ([#14946](https://github.com/craftcms/cms/pull/14946))

### Extensibility
- Added the `waitForDoubleClicks` setting to `Garnish.Select`, `Craft.BaseElementIndex`, and `Craft.BaseElementIndexView`.
