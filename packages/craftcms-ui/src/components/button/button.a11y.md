# `craft-button` accessibility

## Requirements

- [x] Visible focus indicator via `:focus-visible` pseudo-class. This includes:
  - An offset outline to maintain visibility against the button background color.
  - An outline color that uses the adaptive color token (`--c-color-focus-outline`) for visibility in both light and dark modes.
- [x] A loading message is announced via a built-in live region when the loading spinner is toggled on
