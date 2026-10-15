# `craft-switch-button` accessibility

The control inside `craft-switch`; see `switch.a11y.md` for the pattern. This file covers only what the button renders itself.

## Requirements

### Semantics

- [ ] The host carries `role="switch"` and is focusable. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [x] `aria-checked` follows the state: `false` when off and `true` when on, and `true` wins when `checked` and `indeterminate` are both set. Per WCAG 4.1.2 (Name, Role, Value). Verified by `reports the off state`, `reports the on state` and `prefers checked over mixed` in `switch-button.test.ts`.
- [ ] The indeterminate state is exposed to assistive technology. Per WCAG 4.1.2 (Name, Role, Value). Known gap: `indeterminate` sets `aria-checked="mixed"`, which `role="switch"` doesn't support, so it's announced as off. `reports the mixed state` in `switch-button.test.ts` asserts the unsupported value.
- [ ] `disabled` sets `aria-disabled="true"` and removes the button from the Tab order. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Keyboard and focus

- [ ] Space and Enter toggle the button on key release, and Space doesn't scroll the page. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] A keyboard-focused button shows the global focus ring at 3:1 against the surface, in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified. Lion also draws a pale `#bde4ff` outline on the thumb on any focus, including a click.

### Visual

- [ ] On and off differ by more than color: the thumb sits at the end of the track and shows a checkmark when on, and sits at the start when off. Per WCAG 1.4.1 (Use of Color). Not yet verified.
- [x] Indeterminate is reflected as an attribute, so the thumb is drawn centered rather than at either end. Per WCAG 1.4.1 (Use of Color). Verified by `reflects indeterminate so the stylesheet can center the thumb` in `switch-button.test.ts`.
- [ ] The off track's border and the on track's fill reach 3:1 against the surface, and the thumb reaches 3:1 against the track, in both states and in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. The off track's border uses `--c-form-control-border-color`, which is close to the threshold.
- [ ] In forced colors mode, the track, the thumb, its position and the on-state checkmark stay visible. Not yet verified. The checkmark is drawn with `background-color` through a mask, which forced colors may replace with the canvas color.
- [ ] The button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). The medium size is 24px tall. Known gap: the small size is 16px tall.
