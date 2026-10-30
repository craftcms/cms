# `craft-option` accessibility

Used inside `craft-select-rich` and `craft-combobox`; see their docs for the pattern. `craft-select-color` and `craft-text-expander` use it too. This file covers only the option itself, and `craft-option-group`, which groups options under a label.

## Requirements

### Semantics

- [x] The host has the `option` role, and is exposed as selected or not selected to match its checked state. Per WCAG 4.1.2 (Name, Role, Value). Verified by `exposes each option with its selected state` in `option.browser.test.ts`.
- [x] A `disabled` option is exposed as unavailable, and clicking it doesn't select it. Per WCAG 4.1.2 (Name, Role, Value). Verified by `exposes a disabled option as unavailable, and ignores clicks on it` in `option.browser.test.ts`.
- [x] The accessible name is computed from the slotted content, followed by the `hint` and the `suffix` content. Per WCAG 4.1.2 (Name, Role, Value) and 2.5.3 (Label in Name). Verified by `takes its name from its content, then its hint, then its suffix, leaving out an unlabeled icon` in `option.browser.test.ts`.
- [x] A `craft-icon` without a `label` inside the option stays `aria-hidden`, so it's left out of the name. Per WCAG 1.1.1 (Non-text Content). Verified by `takes its name from its content, then its hint, then its suffix, leaving out an unlabeled icon` in `option.browser.test.ts`.

### Visual

- [ ] The highlighted option looks different from the selected option. Per WCAG 2.4.7 (Focus Visible). Known gap: `active` and `checked` share the same `--c-color-neutral-fill-loud` background, so while the highlight is on another option, the two can't be told apart.
- [ ] The highlighted and selected states are marked by more than a background color. Per WCAG 1.4.1 (Use of Color) and 1.4.11 (Non-text Contrast). Known gap: there is no border, outline, check mark or other shape cue.
- [x] Label and hint text reach 4.5:1 against the option at rest, on hover, highlighted and selected, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `draws the label at 4.5:1 and the hint at 4.5:1, at rest, hovered, highlighted and selected` in `option.browser.test.ts`.
- [ ] In forced colors mode the highlighted and selected options stay visible. Known gap: the states are background colors only, with no `forced-colors` rule, so both paint the same `Canvas` as an option at rest.
- [ ] A dot on a highlighted or selected option stays visible. Known gap; see the loud-surface item in `indicator.a11y.md`.
- [x] Every option is at least 24 CSS px tall. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `makes every option at least 24 pixels tall` in `option.browser.test.ts`.

### Reflow and zoom

- [x] The label wraps rather than overflowing at a 320px viewport and at 200% zoom. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Verified by `wraps a long label rather than overflowing at %s` in `option.browser.test.ts`.
- [ ] A long `hint` stays readable. Per WCAG 1.4.10 (Reflow). Known gap: the hint is kept to one line and truncated with an ellipsis, and the cut-off text can't be revealed.

### Groups

- [x] `craft-option-group` has the `group` role, is named by its `label`, and holds its options; changing `label` renames it. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `is a group named by its label, holding its options` and `renames the group when its label changes` in `option-group.browser.test.ts`.
- [x] The group's label reaches 4.5:1 against the listbox in the light and dark themes, and stays visible in forced colors mode. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `draws its label at 4.5:1 against the listbox` and `keeps its label visible in forced colors mode` in `option-group.browser.test.ts`.

## Depends on usage

- 1.1.1 (Non-text Content): hide decorative artwork in an option and give informative artwork a text alternative. See [Accessibility](./Option.mdx#accessibility).
- 1.4.1 (Use of Color): put the meaning of a status dot or colored icon in the option's text. See [Accessibility](./Option.mdx#accessibility).
- 2.5.3 (Label in Name): put the visible label first, before decoration and the `hint`. See [Accessibility](./Option.mdx#accessibility).
