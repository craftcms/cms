# Release Notes for Craft CMS 5.2 (WIP)

### Content Management
- Element index checkboxes no longer have a lag when deselected, except within element selection modals. ([#14896](https://github.com/craftcms/cms/issues/14896))
- Improved mobile styling. ([#14910](https://github.com/craftcms/cms/pull/14910))
- Improved the look of slideouts.
- Table views within element index pages are no longer scrolled directly. ([#14927](https://github.com/craftcms/cms/pull/14927))

### Accessibility
- Darkened the color of page sidebar toggle icons to meet the minimum contrast for UI components.
- Darkened the color of context labels to meet the minimum contrast for text.
- Darkened the color of footer links to meet the minimum contrast for text.
- Set the language of the Craft edition in the footer, to improve screen reader pronunciation for non-English languages.
- The accessible name of “Select site” buttons is now translated to the current language.

### Extensibility
- Added the `waitForDoubleClicks` setting to `Garnish.Select`, `Craft.BaseElementIndex`, and `Craft.BaseElementIndexView`.
