# Garnish Changelog

## 0.1.48

### Changed
- Modals now remove their shades from the DOM when destroyed.
- HUDs now remove their containers and shades from the DOM when destroyed.
- Improved <kbd>ESC</kbd> key handling for menus.
- Context menus and disclosure menus now trigger `show` and `hide` events.

## 0.1.47

### Added
- Added `Garnish.DisclosureMenu`, for cases where a menu is used to show/hide _content_, as opposed to acting like a form `<select>`.

### Changed
- Renamed `Garnish.Menu` to `Garnish.CustomSelect`. (`Garnish.Menu` still exists as a deprecated alias.)

## 0.1.46

### Changed
- `Garnish.NiceText` will now submit the closest form when <kbd>Ctrl</kbd>/<kbd>Command</kbd> + <kbd>Return</kbd> is pressed. ([craftcms/cms#7999](https://github.com/craftcms/cms/issues/7999))

## 0.1.45

### Fixed
- Fixed a JavaScript error that could occur with `Garnish.Select`.

## 0.1.44

### Changed
- `Garnish.Select` now prevents the browser from scrolling to newly-focused items unless the focus was given via a keyboard event. ([craftcms/cms#7940](https://github.com/craftcms/cms/issues/7940))

## 0.1.43

### Added
- Added `Garnish.NiceText.charsLeftHtml()`, which can be overridden to customize the HTML used to display the remaining allowed characters. ([#8](https://github.com/pixelandtonic/garnishjs/pull/8))

## 0.1.42

### Changed
- `Garnish.Menu` options are now selectable via the Space key.

## 0.1.41

### Removed
- Removed `Garnish.Pill`. ([craftcms/cms#7705](https://github.com/craftcms/cms/issues/7705))

## 0.1.40

### Fixed
- Fixed a bug where `Garnish.isCtrlKeyPressed()` would return `false` on Windows browsers if both `ev.ctrlKey` and `ev.altKey` were both `true`. 

## 0.1.38

### Changed
- Improved `Garnish.ShortcutManager`.

## 0.1.37

### Fixed
- Fixed an issue where `Garnish.MenuBtn` didn’t have the proper ARIA attributes.

## 0.1.36

### Fixed
- Fixed a “Can’t remove the base layer.” error that could be thrown if a modal, HUD, or menu was hidden before it was shown.

## 0.1.35

### Added
- Added `Garnish.ShortcutManager`.
- Added the `hideOnEsc` and `hideOnShadeClick` settings to `Garnish.HUD`.

### Deprecated
- Deprecated `Garnish.EscManager`. Use `Garnish.ShortcutManager` instead.

## 0.1.34

### Fixed
- Fixed a bug where drag helpers weren’t getting set to the correct width and height if the dragged element had `box-sizing: border-box`.

## 0.1.33

### Changed
- `Garnish.Menu` objects now trigger a `show` event when the menu in shown.

## 0.1.32

### Changed
- It’s now possible to pass a `Garnish.Menu` object as a second argument when creating a new `Garnish.MenuBtn` object.
- Menus no longer close automatically when the trigger element is blurred, if the focus is changed to an input within the menu.  

## 0.1.31

### Fixed
- Fixed a bug where HUDs could be positioned incorrectly when first opened. ([craftcms/cms#5004](https://github.com/craftcms/cms/issues/5004))

## 0.1.30

### Fixed
- Fixed a bug where the scroll container could be changed when selecting `Garnish.Select` items, if the scroll container was something besides the window. ([craftcms/cms#3762](https://github.com/craftcms/cms/issues/3762)) 

## 0.1.29

### Fixed
- Fixed a bug where it wasn’t possible to close HUDs on computers with touchscreens. ([craftcms/cms#3343](https://github.com/craftcms/cms/issues/3343))

## 0.1.28

### Fixed
- Fixed a bug where elements with `overflow: auto` could not be scrolled while dragging an element over them. ([craftcms/cms#2340](https://github.com/craftcms/cms/issues/2340))

## 0.1.27

### Fixed
- Fixed a bug where HUDs could end up at the top of the page if closed and reopened without scrolling or resizing the window. ([craftcms/cms#3220](https://github.com/craftcms/cms/issues/3220))

## 0.1.26

### Fixed
- Fixed an infinite loop bug that could occur if a modal had a nested element with a `resize` event listener. 

## 0.1.25

### Fixed
- Fixed a JavaScript error that occurred when removing event listeners via `Garnish.Base::removeListener()` or `removeAllListeners()`.

## 0.1.24

### Fixed
- Fixed a bug where Garnish.Select instances would try to reestablish window focus on the virtually focused item on self-destruct. ([craftcms/cms#2964](https://github.com/craftcms/cms/issues/2964))

## 0.1.23

### Changed
- Menus that are too tall to fit in the current viewport are now scrollable. ([craftcms/cms#2942](https://github.com/craftcms/cms/issues/2942))
- Menus now reposition themselves as the window is scrolled. 

### Fixed
- Fixed a bug where HUDs weren’t automatically resizing/repositioning themselves.

## 0.1.22 - 2018-04-07

### Added
- Added support for class-level events via `Garnish.on()` and `Garnish.off()`.

### Changed
- `Garnish.HUD` instances are now accessible via `.data('hud')` on their container element. 

## 0.1.21 - 2018-03-29

### Changed
- `Garnish.Select` will no longer toggle focus on an item when `spacebar` is pressed, if the `shift` key is down.
- `Garnish.Select` will now trigger a `focusItem` event when an item is focused.
- `Garnish.Select` will now keep track of the focused item via a `$focusedItem` property.
- Event handlers registered with `Garnish.addListener()` can now return `false` to cancel the event.

## 0.1.20 - 2018-01-20

### Fixed
- Fixed a bug where HUDs could get themselves into an infinite repositioning loop.

## 0.1.19 - 2017-07-19

### General
- Stability improvements.

## 0.1.18 - 2017-05-02

### Fixed
- Fixed a bug where HUDs where briefly showing up in the top left corner of the window before getting repositioned. 

## 0.1.17 - 2017-03-21

### Fixed
- Fixed a potential infinite HUD resize loop in IE11.

## 0.1.16 - 2017-03-14

### Fixed
- Fixed a bug where NiceText objects’ staging areas were getting a `&nbps;` entity appended rather than `&nbsp;`. (AugustMiller)

## 0.1.15 - 2017-02-22

### Changed
- Modals no longer automatically update their position when they change size.

### Fixed
- Fixed a bug where modals would get caught in infinite resize handling loops.
- Fixed a bug where modals could be initialized with the wrong size when fading in.

## 0.1.14 - 2017-02-22

### Changed
- Modals and HUDs now trigger `updateSizeAndPosition` events when their size/position changes.

## 0.1.13 - 2017-02-16

### Changed
- Modals now automatically update their position when they change size. 

## 0.1.12 - 2017-01-30

### Fixed
- Fixed a bug where an infinite event loop could be caused when opening an HUD.  

## 0.1.11 - 2017-01-04

### Fixed
- Fixed a “Garnish is not defined” error.

## 0.1.10 - 2017-01-04

### Changed
- Garnish no longer has jQuery as a Bower dependency.

## 0.1.9 - 2016-12-19 

### Fixed
- Fixed a bug where HUDs weren’t factoring in window/trigger padding when calculating the max size of the HUD alongside the trigger element

## 0.1.8 - 2016-12-07

### Changed
- Relaxed the dependencies’ version requirements

## 0.1.7 - 2016-12-07

### Removed
- Removed touch support from `Garnish.BaseDrag`

## 0.1.6 - 2016-11-25

### Added
- Added touch support to `Garnish.BaseDrag`

### Changed
- Updated gulp-sourcemaps dependency to 1.9.1

## 0.1.5 - 2016-11-19

### Fixed
- HUDs now have their size and position updated on window resize

## 0.1.4 - 2016-09-02

### Added
- Added bower as an NPM dependency
- Added element-resize-detector 1.1.7 bower dependency
- Added jquery 2.2.1 bower dependency
- Added velocity 1.2.3 bower dependency
- Added jquery-touch-events 1.0.5 bower dependency
- Added gulp docs task for generating documentation with JSDoc

### Fixed
- Fixed a bug where the listener on the `click` event on the HUD’s `$shade` was not working properly with a `taphold` event defined on the same element

## 0.1.3 - 2016-08-30

### Fixed
- Fixed a bug where NiceText wasn’t accounting for trailing newlines when approximating the input height

## 0.1.2 - 2016-08-26

### Fixed
- Fixed a bug where clicking on a Menu option could hide the menu before the option had a chance to activate

## 0.1.1 - 2016-08-25

### Fixed
- Improved NiceText’s input height approximation logic
- Fixed a bug where NiceText was not ensuring the associated input was still visible before recalculating its height
