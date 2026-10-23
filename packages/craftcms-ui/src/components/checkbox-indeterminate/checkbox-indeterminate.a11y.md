# `craft-checkbox-indeterminate` accessibility

Builds on `craft-checkbox`; see `checkbox.a11y.md` for the label, keyboard, focus and visual requirements it shares. Follows the WAI-ARIA APG [Mixed-State Checkbox](https://www.w3.org/WAI/ARIA/apg/patterns/checkbox/examples/checkbox-mixed/) example, using the native input's `indeterminate` property rather than `aria-checked="mixed"`. Lion's `LionCheckboxIndeterminate` derives the state from the nested `craft-checkbox`es.

**Not for:**

- A checkbox that shows a partial state you set yourself, with no nested children. Use `craft-checkbox` with `indeterminate`.
- An on/off setting that takes effect as soon as it's toggled. Use `craft-switch`.

## Requirements

### Semantics

- [ ] The parent's native input is checked when every child is, unchecked when none are, and has `indeterminate` set when only some are, so it's announced as checked, not checked or mixed. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified. `can be put into the indeterminate state` in `checkbox-indeterminate.test.ts` checks the host property, not the native input, and the `IndeterminateState` and `AllSelected` stories have no `play()` assertion.
- [ ] The parent's input takes its accessible name from the parent's own `label` slot, without the children's labels folded in. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified. `takes its own label from the label slot` in `checkbox-indeterminate.test.ts` checks the slotted label's text, not the computed name.
- [ ] The children sit in a `role="list"` container after the parent, and each child host gets `role="listitem"`, so the set is announced with its size. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [ ] The parent doesn't reference the children it controls (no `aria-controls`), unlike the APG example. Per WCAG 1.3.1 (Info and Relationships). Known gap: the relationship is conveyed only by DOM order and the list.

### Keyboard and focus

- [ ] Activating the parent with Space or a click checks every enabled child when it's unchecked or mixed, and unchecks every enabled child when it's checked. Per WCAG 2.1.1 (Keyboard). Not yet verified. With Lion's `mixed-state` attribute, activation instead cycles unchecked, mixed (the last partial selection) and checked, as the APG example does.
- [ ] Toggling a child updates the parent's state at once, without moving focus. Per WCAG 3.2.2 (On Input) and 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] Tab order runs from the parent through each child in DOM order. Per WCAG 2.4.3 (Focus Order). Not yet verified.

### Visual

- [ ] The mixed state is drawn as the native dash, a different shape from the check mark and the empty box, and stays visible in forced colors mode. Per WCAG 1.4.1 (Use of Color). Not yet verified.
- [ ] The children are indented under the parent, so the relationship is visible as well as programmatic. Per WCAG 1.3.1 (Info and Relationships). Not yet verified. The component's styles set the children's `padding-inline-start` to 0, overriding Lion's indent.

## Depends on usage

- 3.3.2 (Labels or Instructions): label the parent with what the set contains, such as "All entry types", rather than "Select all". See [Accessibility](./CheckboxIndeterminate.mdx#accessibility).
- 1.3.1 (Info and Relationships): place it inside a `craft-checkbox-group` with a label that names the question. See [Accessibility](./CheckboxIndeterminate.mdx#accessibility).
