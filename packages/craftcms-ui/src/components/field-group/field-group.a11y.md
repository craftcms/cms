# `craft-field-group` accessibility

A layout grid for fields. It adds no role or name, so it doesn't group fields for assistive technology.

**Not for:**

- Naming a set of related fields: use `craft-field` with `fieldset`.
- A set of options to choose from: use `craft-checkbox-group` or `craft-radio-group`.

## Requirements

### Semantics

- [ ] The group adds no role or accessible name, and leaves its children's semantics unchanged. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [x] Children are placed in DOM order, without `order` or grid areas, so reading and focus order match the visual order. Per WCAG 1.3.2 (Meaningful Sequence) and 2.4.3 (Focus Order). Verified for intrinsic-width children by `uses intrinsic widths when space allows and stacks them when it does not` in `field-group.browser.test.ts`.
- [ ] Inside a required `fieldset` field, nested required fields hide only their decorative asterisk and keep their visually hidden "Required" text. Per WCAG 1.3.1 (Info and Relationships) and 3.3.2 (Labels or Instructions). Not yet verified.

### Visual

- [x] In a `separated` group, column hairlines are drawn with CSS borders on pseudo-elements, so they add nothing to the accessibility tree, aren't announced, and keep a visible system color in forced colors mode. They appear only between fields that share a row. Per WCAG 1.3.1 (Info and Relationships). Verified by `draws a hairline between separated fields that share a row, and only there` in `field-group.browser.test.ts`.

### Reflow and zoom

- [x] Intrinsic-width children stack onto separate rows when the group narrows. Per WCAG 1.4.10 (Reflow). Verified by `uses intrinsic widths when space allows and stacks them when it does not` in `field-group.browser.test.ts`.
- [ ] Children with a `width-*` class take a full row below 25rem of container width, and every child can shrink below its content width, so nothing overflows at a 320px viewport or at 200% zoom. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Not yet verified.

## Depends on usage

- 1.3.1 (Info and Relationships): wrap fields that answer one question in a `craft-field` with `fieldset` to give them a group name. See [Accessibility](./FieldGroup.mdx#accessibility).
- 1.3.2 (Meaningful Sequence): put children in the order they should be read. See [Accessibility](./FieldGroup.mdx#accessibility).
