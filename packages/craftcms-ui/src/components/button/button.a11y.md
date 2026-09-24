# `craft-button` accessibility

## Requirements

- [x] Visible focus indicator via `:focus-visible` pseudo-class. This includes:
  - An offset outline to maintain visibility against the button background color.
  - An outline color that uses the adaptive color token (`--c-color-focus-outline`) for visibility in both light and dark modes.
- [x] A loading message is announced via a built-in live region when the loading spinner is toggled on
- [x] `size="xsmall"` draws below 24px but keeps a `--c-size-touch-target-sm` (24×24) hit area, meeting WCAG 2.5.8 Target Size (Minimum). Verified by the `[size=xsmall]` cases in `button.browser.test.ts`, which hit-test just outside the visible box on the `plain` variant — the one that otherwise switches the sizer off.
  - The expanded area overlaps whatever sits within 12px of the button's centre, so space xsmall buttons accordingly rather than butting them against other targets.
