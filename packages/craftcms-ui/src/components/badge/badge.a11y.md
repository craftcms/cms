# `craft-badge` accessibility

A static, non-interactive status pill. It builds on `craft-indicator` for its default prefix; see `indicator.a11y.md` for the dot itself.

**Not for:**

- A marker or count that points at something else, such as unread items on a nav item or button. Use `craft-badge-indicator`.
- A clickable or removable item. Use `craft-chip` or `craft-button`.

## Requirements

### Semantics

- [x] The default prefix indicator is decorative: it has no role or name, so the badge's status is announced once, by its label. Per WCAG 1.1.1 (Non-text Content). Verified by `leaves the default indicator unnamed, so the label is announced once` in `badge.browser.test.ts`.
- [x] A slotted `prefix` that says something the label doesn't, such as a labeled `craft-icon`, keeps its own accessible name. Per WCAG 1.1.1 (Non-text Content). Verified by the `CustomPrefix` story's `play()` in `badge.stories.ts`.
- [x] `no-prefix` removes the indicator from the badge rather than hiding it visually. Per WCAG 1.3.1 (Info and Relationships). Verified by `leaves out the prefix with no-prefix` in `badge.test.ts`.
- [x] The label slot renders as soon as text arrives, including text added after the first render, so the status is visible and in the accessibility tree. Per WCAG 1.3.1 (Info and Relationships). Verified by `renders a region once content arrives for it` in `badge.test.ts`.
- [ ] The badge has no role, takes no focus and exposes no state, so it's read as plain text in place. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Visual

- [x] The label reaches 4.5:1 against the badge surface for every `Color` value, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `sets the label at 4.5:1 against the badge for every fill` in `badge.browser.test.ts`.
- [x] The default indicator reaches 3:1 against the badge surface for every `Color` value, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Verified by `draws the indicator at 3:1 against the badge for every fill` in `badge.browser.test.ts`.
- [x] A link in the label of a tinted badge (any but white, gray, slate, or black) takes the badge's text color and an underline until hovered, since blue would clash with its fill. A neutral badge's links keep the CP's default styling (see `.github/instructions/a11y.instructions.md`) at 4.5:1 or more against its fill, and a black badge's take the dark theme's. Per WCAG 1.4.1 (Use of Color) and 1.4.3 (Contrast (Minimum)). Verified by `gives a tinted badge’s links its text color and an underline until hovered` in `badge-link.browser.test.ts`.
- [ ] The badge's 1px border isn't needed to identify it, since the text carries the meaning, so it isn't held to 3:1 against the page. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified that no fill depends on it.
- [ ] In forced-colors mode, the badge keeps a visible edge: its 1px border is forced to a system color. Not yet verified.

### Reflow and zoom

- [ ] The label stays readable at 200% text size and a 320px viewport. Per WCAG 1.4.4 (Resize Text) and 1.4.10 (Reflow). Not yet verified. A labeled badge has a fixed `block-size` (`--c-size-control-sm`), and its text is trimmed to cap height, so wrapped text would overflow the pill.

## Depends on usage

- 1.4.1 (Use of Color): give every badge visible text that names the status, so `fill` only reinforces it. See [Accessibility](./Badge.mdx#accessibility).
- 1.1.1 (Non-text Content): give a slotted `prefix` its own accessible name when it says something the label doesn't. See [Accessibility](./Badge.mdx#accessibility).
