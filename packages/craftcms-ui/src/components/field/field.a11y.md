# `craft-field` accessibility

The field supplies the label, instructions, notices, and errors around a slotted control, and wires them to it. The control's own behavior is covered by its own doc, for example `input.a11y.md`.

## Requirements

### Semantics

- [x] The label is associated with a native slotted control through `for` and the control's `aria-labelledby`. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `associates the generated label with the slotted input` and `links the label into the input aria-labelledby` in `field.test.ts`.
- [x] A slotted `<label slot="label">` is associated with the control the same way. Per WCAG 1.3.1 (Info and Relationships). Verified by `adopts a slotted label element` in `field.test.ts`.
- [x] When the slotted control is itself a form control component, the label names the native control inside it rather than the wrapper. Per WCAG 4.1.2 (Name, Role, Value). Verified by `labels a nested form control instead of its wrapper` in `field.test.ts` and `labels nested native controls` in `field.browser.test.ts`.
- [x] A nested combobox's input and its listbox each get the field label as their name, without the label repeated. Per WCAG 4.1.2 (Name, Role, Value). Verified by `labels a nested combobox and its listbox once (multiple: %s)` in `field.browser.test.ts`.
- [x] The control's accessible name matches the visible label text. Per WCAG 2.5.3 (Label in Name). Verified by `labels nested native controls` in `field.browser.test.ts`.
- [x] With `fieldset`, or when the slotted element isn't a form control, the host gets `role="group"` named by the label through `aria-labelledby`, and the label drops its `for`. Unsetting `fieldset` restores the label association. Per WCAG 1.3.1 (Info and Relationships). Verified by `uses group semantics for a composite control wrapper`, `exposes group semantics on the host` and `restores the label association when fieldset is unset` in `field.test.ts`.
- [x] `label-sr-only` hides the label visually but keeps it as the control's accessible name. Per WCAG 4.1.2 (Name, Role, Value). Verified by `leaves no space above the input for a visually hidden label` in `field.browser.test.ts` and `visually hides the whole heading when the label is all it has` in `field.test.ts`.
- [ ] Field actions are grouped with `role="group"` and the name "Field actions". Per WCAG 1.3.1 (Info and Relationships). Not yet verified. `groups slotted actions` in `field.test.ts` checks the role but not the name.
- [ ] The translation indicator sits inside the label node, so its name may be folded into the control's accessible name, and it puts a button inside a `<label>`. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Forms and validation

- [x] Instructions, tip, warning, and feedback are each linked into the control's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships) and 3.3.2 (Labels or Instructions). Verified by `links help text, tip, warning and feedback to the input` in `field.test.ts`.
- [ ] In group mode, instructions, notices, and feedback are linked into the host's `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [x] `required` adds visually hidden "Required" text inside the label, and the asterisk is `aria-hidden`, so the requirement is in the label text and not conveyed by the symbol or its color alone. Removing `required` removes both. Per WCAG 1.3.1 (Info and Relationships), 1.4.1 (Use of Color) and 3.3.2 (Labels or Instructions). Verified by `renders the required text inside the label` and `removes the indicator when required is unset` in `field.test.ts`.
- [x] Errors are rendered as text in the `feedback` slot and linked to the control through `aria-describedby`. Per WCAG 3.3.1 (Error Identification). Verified by `links help text, tip, warning and feedback to the input` in `field.test.ts`.
- [ ] When the field has errors, the control is marked `aria-invalid="true"`, and the attribute is removed once they clear. Per WCAG 3.3.1 (Error Identification) and 4.1.2 (Name, Role, Value). Known gap: `has-errors` only changes styling, and nothing sets `aria-invalid`.
- [x] Tip and warning notices start with visually hidden "Tip:" and "Warning:" text, so their kind isn't carried by the callout's icon or color alone. Per WCAG 1.1.1 (Non-text Content) and 1.4.1 (Use of Color). Verified by `renders tip and warning content inside callouts` in `field.test.ts`.
- [x] Read-only state is shown as a "Read Only" text badge in the heading. Per WCAG 1.4.1 (Use of Color). Verified by `renders the badge in the heading when read-only` in `field.test.ts`.

### Announcements

- [ ] Errors that appear after the page loads are announced once, without being read again as part of the description. Per WCAG 4.1.3 (Status Messages). Known gap: `aria-live` is set on the feedback node only on the field's first `focusin` or `focusout`, and only for a feedback node present when the field connects. Errors slotted later aren't in a live region.
- [ ] The status badge's label is announced. Per WCAG 1.1.1 (Non-text Content) and 1.3.1 (Info and Relationships). Known gap: the indicator is `aria-hidden="true"`, so its visually hidden label is never read, and the status shows only as a colored bar with a hover-only `title`. `renders the status badge with a visually hidden label` in `field.test.ts` asserts that markup.

### Keyboard and focus

- [x] The translation indicator is a button named by `translation-description`. It's reachable with Tab, opens its tooltip on focus, closes it with Escape while keeping focus, and Tab then moves on to the control. Per WCAG 2.1.1 (Keyboard), 2.4.3 (Focus Order), 4.1.2 (Name, Role, Value) and 1.4.13 (Content on Hover or Focus). Verified by `exposes translation help on keyboard focus and dismisses it with Escape` in `field.browser.test.ts`.
- [x] The translation indicator is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `exposes translation help on keyboard focus and dismisses it with Escape` in `field.browser.test.ts`.

### Visual

- [ ] With errors, the label and control border switch to the danger color. Per WCAG 1.4.1 (Use of Color), as reinforcement of the error text. Known gap: the styles target `:host([has-errors='true'])`, but `has-errors` reflects as an empty attribute, so they never apply.
- [ ] The required asterisk stays visible in forced colors mode. Not yet verified.
- [ ] The status bar stays visible in forced colors mode. Known gap: it's drawn with `background-color` only, which forced colors replaces.

### Reflow and zoom

- [ ] At a 320px viewport and at 200% zoom, the heading wraps and the control shrinks to fit, with nothing clipped or overlapping. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Not yet verified. The actions group is absolutely positioned at the end of the heading, so a long label may run under it.

## Depends on usage

- 3.3.2 (Labels or Instructions): give every field a visible `label`, and use `label-sr-only` only when the control's purpose is clear without it. See [Accessibility](./Field.mdx#accessibility).
- 4.1.2 (Name, Role, Value): set `required`, `readonly`, and `disabled` on the control as well as the field. See [Accessibility](./Field.mdx#accessibility).
- 1.4.1 (Use of Color): put error text in the `feedback` slot. Setting `has-errors` alone only changes color. See [Accessibility](./Field.mdx#accessibility).
- 3.3.3 (Error Suggestion): write errors that say how to fix the value. See [Accessibility](./Field.mdx#accessibility).
- 3.3.1 (Error Identification): on submit, move focus to the first invalid control. See [Accessibility](./Field.mdx#accessibility).
- 1.3.1 (Info and Relationships): use `fieldset` when several controls answer one question. See [Accessibility](./Field.mdx#accessibility).
