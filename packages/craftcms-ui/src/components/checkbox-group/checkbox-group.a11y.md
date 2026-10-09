# `craft-checkbox-group` accessibility

Follows the WAI-ARIA APG [Checkbox](https://www.w3.org/WAI/ARIA/apg/patterns/checkbox/) pattern for a set of checkboxes: a `role="group"` container named by a visible label. Each box follows `checkbox.a11y.md`. Lion's `FormGroupMixin` wires the group's label, help text and feedback to the host. `HasLabel` only reflects a `has-label` attribute for spacing; see `input.a11y.md`. Inside `craft-field`, the field draws the label and instructions; see `field.a11y.md`.

**Not for:**

- Picking exactly one of several options. Use `craft-radio-group`.
- Picking from a long list of options. Use `craft-select-rich` or `craft-combobox`.
- A single on/off setting that takes effect as soon as it's toggled. Use `craft-switch`.

## Requirements

### Semantics

- [ ] The host has `role="group"`, named by its label through `aria-labelledby`. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] `disabled` disables every checkbox in the group, including ones added later. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [x] A server-rendered group adopts the `name` on its inputs instead of stripping it, so the boxes keep posting as one field. Per WCAG 4.1.2 (Name, Role, Value). Verified by `adopts the group name from the slotted inputs instead of wiping theirs` and `aggregates SSR checked state into the group modelValue` in `checkbox-group.test.ts`.

### Forms and validation

- [ ] Help text, from the `help-text` slot, is referenced by the host's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships) and 3.3.2 (Labels or Instructions). Not yet verified.
- [ ] A `Required` validator ("choose at least one") marks the group as required. Per WCAG 3.3.2 (Labels or Instructions). Known gap: Lion sets `aria-required` only on inputs and compatible roles, and `group` isn't one, so the requirement isn't exposed programmatically.
- [ ] While a group-level error is shown, the host carries `aria-invalid="true"`, and the message in the `feedback` slot is referenced by its `aria-describedby`. Per WCAG 3.3.1 (Error Identification). Not yet verified.
- [ ] Group-level errors are announced when they appear, through the `feedback` slot's live region. Per WCAG 4.1.3 (Status Messages). Not yet verified.

### Keyboard and focus

- [ ] Each checkbox is its own Tab stop, in DOM order. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.

### Visual

- [ ] With an error, the label switches to the danger color as reinforcement of the message. Per WCAG 1.4.1 (Use of Color). Not yet verified.
- [ ] The checkboxes are spaced so each box and label is a 24×24 CSS px target, or clears its neighbors. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified. The rows use `--c-spacing-sm` as their gap, not the `--c-checkbox-group-spacing` token.

## Depends on usage

- 3.3.2 (Labels or Instructions): give the group a visible label that names the question the boxes answer. See [Accessibility](./CheckboxGroup.mdx#accessibility).
- 3.3.2 (Labels or Instructions): say in the label or help text when at least one option is required, since the group can't expose it. See [Accessibility](./CheckboxGroup.mdx#accessibility).
- 1.4.1 (Use of Color): show a message in `feedback` when the group is invalid, not just the color change. See [Accessibility](./CheckboxGroup.mdx#accessibility).
- 3.3.3 (Error Suggestion): write group errors that say how to fix the choice. See [Accessibility](./CheckboxGroup.mdx#accessibility).
