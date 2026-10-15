# `craft-option` accessibility

Used inside `craft-select-rich` and `craft-combobox`; see their docs for the pattern. `craft-select-color` and `craft-text-expander` use it too. This file covers only the option itself.

## Requirements

### Semantics

- [ ] The host carries `role="option"`. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] The host carries `aria-selected` matching its checked state, `true` or `false`. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] `disabled` sets `aria-disabled="true"`, and clicking a disabled option doesn't select it. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] The accessible name is computed from the slotted content, followed by the `hint` and the `suffix` content. Per WCAG 4.1.2 (Name, Role, Value) and 2.5.3 (Label in Name). Not yet verified.
- [ ] A `craft-icon` without a `label` inside the option stays `aria-hidden`, so it's left out of the name. Per WCAG 1.1.1 (Non-text Content). Not yet verified.

### Visual

- [ ] The highlighted option looks different from the selected option. Per WCAG 2.4.7 (Focus Visible). Known gap: `active` and `checked` share the same `--c-color-neutral-fill-loud` background, so while the highlight is on another option, the two can't be told apart.
- [ ] The highlighted and selected states are marked by more than a background color. Per WCAG 1.4.1 (Use of Color) and 1.4.11 (Non-text Contrast). Not yet verified. There is no border, check mark or other shape cue.
- [ ] Label and hint text reach 4.5:1 against the option at rest, on hover, highlighted and selected, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified. At rest the hint is `currentColor` mixed with 25% transparency, so its contrast depends on the background.
- [ ] In forced colors mode the highlighted and selected options stay visible. Not yet verified. The states are background colors only, with no `forced-colors` rule, so they're likely lost.
- [ ] A dot on a highlighted or selected option stays visible. Known gap; see the loud-surface item in `indicator.a11y.md`.
- [ ] Every option is at least 24 CSS px tall. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.

### Reflow and zoom

- [ ] The label wraps rather than overflowing at a 320px viewport and at 200% zoom. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Not yet verified.
- [ ] A long `hint` stays readable. Per WCAG 1.4.10 (Reflow). Known gap: the hint is kept to one line and truncated with an ellipsis, and the cut-off text can't be revealed.

## Depends on usage

- 1.1.1 (Non-text Content): hide decorative artwork in an option and give informative artwork a text alternative. See [Accessibility](./Option.mdx#accessibility).
- 1.4.1 (Use of Color): put the meaning of a status dot or colored icon in the option's text. See [Accessibility](./Option.mdx#accessibility).
- 2.5.3 (Label in Name): put the visible label first, before decoration and the `hint`. See [Accessibility](./Option.mdx#accessibility).
