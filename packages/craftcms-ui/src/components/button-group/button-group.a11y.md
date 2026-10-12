# `craft-button-group` accessibility

Without `name`, the group only joins its buttons visually; each one follows `button.a11y.md`. With `name`, its buttons become toggle buttons in the WAI-ARIA APG [Button](https://www.w3.org/WAI/ARIA/apg/patterns/button/) pattern, marked with `aria-pressed`.

**Not for:**

- Choosing one value, announced as a radio group with arrow-key navigation. Use `craft-radio-group`.
- Switching between panels where one is always showing. Use `craft-tabs`.
- A toolbar with one Tab stop and arrow keys between its controls. No component implements the APG [Toolbar](https://www.w3.org/WAI/ARIA/apg/patterns/toolbar/) pattern yet; raise it rather than adding a mode here.

## Requirements

### Semantics

- [ ] Without `name`, the host has no role, so its buttons are announced as independent buttons. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [x] In single-select mode, each button's `aria-pressed` follows the selected `value`, including a selection given in markup with `active`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `adopts its value from a child marked active in markup` in `button-group.test.ts` and the `SelectionFromMarkup` story.
- [ ] In single-select mode, the roles fit together. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Known gap: the host gets `role="radiogroup"`, but its children stay buttons with `aria-pressed` rather than `role="radio"` with `aria-checked`. A radio group must own radios, so screen readers announce a group with no options in it.
- [ ] In `multiple` mode, the host is `role="group"` and each button's `aria-pressed` follows its `active` state. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified. `toggles multiple selected values` in `button-group.test.ts` checks `active` but not `aria-pressed`.
- [ ] In selection mode, only the group's own options get `aria-pressed`. Per WCAG 4.1.2 (Name, Role, Value). Known gap: every `craft-button` descendant gets it, including a nested action menu's invoker, which is then announced as an unpressed toggle.
- [ ] The group has an accessible name in selection mode. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Known gap: there's no `label` property; the name comes only from `aria-label` or `aria-labelledby` on the host.

### Keyboard and focus

- [ ] Every button stays its own Tab stop, operated with Enter and Space. The group adds no arrow-key navigation. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] Pressing a button changes the selection without moving focus, and focus stays on the pressed button. Per WCAG 2.4.3 (Focus Order) and 3.2.2 (On Input). Not yet verified.

### Visual

- [ ] The selected button is marked by an inset shadow as well as its fill and text color. Per WCAG 1.4.1 (Use of Color) and 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] The selected state stays visible in forced colors mode. Not yet verified.
- [ ] Each button is at least 24×24 CSS px, since buttons sit 1px apart and get no spacing exception. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified. An `xsmall` button's expanded hit area overlaps its neighbors.

## Depends on usage

- 4.1.2 (Name, Role, Value): name the group with `aria-label` or `aria-labelledby` in selection mode. See [Accessibility](./ButtonGroup.mdx#accessibility).
- 4.1.2 (Name, Role, Value): name each icon-only button. See [Accessibility](./ButtonGroup.mdx#accessibility).
