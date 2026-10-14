# `craft-button` accessibility

Follows the WAI-ARIA APG [Button](https://www.w3.org/WAI/ARIA/apg/patterns/button/) pattern, including toggle buttons when `toggle` is set. With `href`, it's a link instead.

**Not for:**

- An on/off setting that takes effect straight away. Use `craft-switch`.
- Choosing one value from a set. Use `craft-radio-group`.
- Switching between views where one is always showing. Use `craft-tabs`.

## Requirements

### Semantics

- [x] Without `href`, the host is `role="button"` in the Tab order, and `type` defaults to `button`, so it submits a form only when `type="submit"` is set. Per WCAG 4.1.2 (Name, Role, Value). Verified by `keeps Lion button semantics when there is no href` and `honors an explicit type="submit"` in `button.test.ts`.
- [x] With `href`, the button renders a real `<a href>` and the host gets `role="presentation"`, so it's announced as a link, not a button. Per WCAG 4.1.2 (Name, Role, Value). Verified by `renders a real anchor wrapping the content when href is set` and `is a presentation host and not a tab stop in link mode` in `button.test.ts`.
- [x] Adding or removing `href` at runtime switches the host between link and button semantics, and restores an explicit `type="submit"`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `re-syncs host state when href is added after connect`, `restores button semantics when href is removed at runtime` and `restores an explicit type="submit" when href is removed` in `button.test.ts`.
- [x] With `toggle`, `aria-pressed` follows `active`. The button never changes `active` itself; it fires a cancelable `craft-toggle`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `derives aria-pressed from active` and `reports the state being asked for without changing active itself` in `button.test.ts`.
- [x] Without `toggle`, the button leaves an `aria-pressed` set by its owner alone, such as the one `craft-button-group` sets. Per WCAG 4.1.2 (Name, Role, Value). Verified by `leaves aria-pressed alone on a button that is not a toggle` in `button.test.ts`.
- [x] A disabled button is exposed as disabled with `aria-disabled="true"`. With `href`, it renders no anchor and stays a button. Per WCAG 4.1.2 (Name, Role, Value). Verified by `treats disabled+href as an inert button, not a link` and `can remain focusable when disabled` in `button.test.ts`.
- [x] A button with no accessible name is flagged with the `a11y-error` outline once it's visible. A labeled button or link isn't flagged, and a button hidden on first render isn't judged until it's shown. Per WCAG 4.1.2 (Name, Role, Value). Verified by `flags an accessible-name error for a nameless button` and `does not flag an accessible-name error for a labeled link` in `button.test.ts`, and `flags an unnamed button that is visible from the start`, `still flags an unnamed button once it is shown` and `does not flag a labeled button that was hidden when it first rendered` in `button.browser.test.ts`.
- [ ] The `icon` attribute renders a decorative icon, hidden from assistive technology, so it adds nothing to the name. Per WCAG 1.1.1 (Non-text Content). Not yet verified.

### Keyboard and focus

- [x] With `href` set, the button is a single tab stop: Tab moves focus to its inner `<a>`, and the next Tab moves past it. The host gets no `tabindex`, because a negative `tabindex` on a shadow host drops the anchor from the Tab order. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Verified by `is a single tab stop on its anchor` in `button.browser.test.ts` and `is a presentation host and not a tab stop in link mode` in `button.test.ts`.
- [x] A disabled button leaves the Tab order, with or without `href`, unless `focusable-when-disabled` keeps it there. Per WCAG 2.4.3 (Focus Order). Verified by `leaves a disabled non-link button non-focusable (tabIndex -1)`, `keeps disabled+href non-focusable (tabIndex -1)` and `can remain focusable when disabled` in `button.test.ts`.
- [ ] Enter and Space activate a button, and Enter activates a link. Per WCAG 2.1.1 (Keyboard). Lion handles the keys; not yet verified.
- [ ] A keyboard-focused button shows a `:focus-visible` outline, offset from the button and colored with `--c-color-focus-outline`, at 3:1 against the surface in the light and dark themes. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.

### Status

- [ ] Turning `loading` on announces "Loading" through the button's own `role="status"` live region, cleared after 5 seconds. The string is translated. Per WCAG 4.1.3 (Status Messages). Not yet verified.

### Visual

- [x] A disabled button is muted to 50% opacity rather than recolored, so it keeps its variant's colors. Disabled controls are exempt from WCAG 1.4.3 (Contrast (Minimum)), and the state is conveyed by `disabled` or `aria-disabled`, not by the dimming alone. Per WCAG 1.4.1 (Use of Color). Verified by `mutes a disabled button` and `keeps the variant’s own fill rather than repainting it` in `button.browser.test.ts`.
- [ ] The `link` variant is underlined by default, so it isn't marked by color alone among other text. Per WCAG 1.4.1 (Use of Color). Not yet verified.
- [ ] Variant text and borders meet 4.5:1 and 3:1 in every state, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)) and 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced colors mode, the button's boundary, focus outline and `active` state stay visible. Not yet verified.

### Target size

- [x] `size="xsmall"` draws below 24px but keeps a `--c-size-touch-target-sm` (24×24) hit area, even on the `plain` variant, which otherwise switches the sizer off. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `draws smaller than the minimum target size` and `still answers a click across the full target area` in `button.browser.test.ts`. The expanded area overlaps whatever sits within 12px of the button's center.
- [x] A link button's click area defaults to 44px wide, can be narrowed from an ancestor, and keeps the 24px touch target at `size="xsmall"`. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `defaults to a 44px click area`, `takes its click area from an ancestor` and `keeps the smaller touch target for extra-small links` in `button.browser.test.ts`.

## Depends on usage

- 4.1.2 (Name, Role, Value): name an icon-only button with `aria-label`, or a `label` on its icon. See [Accessibility](./Button.mdx#accessibility).
- 1.1.1 (Non-text Content): slot a labeled icon, or add `sr-only` text, when the icon means something the label doesn't. See [Accessibility](./Button.mdx#accessibility).
- 1.4.1 (Use of Color): say what a `danger` button does in its label, not only with its color. See [Accessibility](./Button.mdx#accessibility).
- 2.5.8 (Target Size (Minimum)): leave room around `xsmall` buttons, since their hit area spills past the visible box. See [Accessibility](./Button.mdx#accessibility).
- 4.1.2 (Name, Role, Value): use `href` for anything that goes to a URL, not a click handler. See [Accessibility](./Button.mdx#accessibility).
