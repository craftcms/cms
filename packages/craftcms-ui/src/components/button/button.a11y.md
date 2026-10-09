# `craft-button` accessibility

## Requirements

- [x] Visible focus indicator via `:focus-visible` pseudo-class. This includes:
  - An offset outline to maintain visibility against the button background color.
  - An outline color that uses the adaptive color token (`--c-color-focus-outline`) for visibility in both light and dark modes.
- [x] With `href` set, the button is a single, keyboard-reachable tab stop: Tab moves focus to its inner `<a>`, and the next Tab moves past it. The host gets `role="presentation"` and no `tabindex`, because a negative `tabindex` on a shadow host drops the anchor from the Tab order. Verified by the `link keyboard focus` case in `button.browser.test.ts` and the `link semantics` cases in `button.test.ts`.
  - A disabled button with `href` renders no anchor and is not a tab stop. Verified by `keeps disabled+href non-focusable` in `button.test.ts`.
- [x] A loading message is announced via a built-in live region when the loading spinner is toggled on
- [x] A disabled button is muted rather than recolored, so it keeps its variant's contrast ratio at 50% opacity. Disabled controls are exempt from WCAG 1.4.3, but the state is conveyed by `disabled`/`aria-disabled` rather than by the dimming alone. Verified by the `[disabled]` cases in `button.browser.test.ts`.
- [x] `size="xsmall"` draws below 24px but keeps a `--c-size-touch-target-sm` (24×24) hit area, meeting WCAG 2.5.8 Target Size (Minimum). Verified by the `[size=xsmall]` cases in `button.browser.test.ts`, which hit-test just outside the visible box on the `plain` variant — the one that otherwise switches the sizer off.
  - The expanded area overlaps whatever sits within 12px of the button's center, so space xsmall buttons accordingly rather than butting them against other targets.
