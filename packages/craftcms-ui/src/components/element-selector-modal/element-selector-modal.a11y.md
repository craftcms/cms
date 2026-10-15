# `craft-element-selector-modal` accessibility

Built on `craft-dialog`; see `dialog.a11y.md` for the dialog pattern. This file covers only what the selector adds: the heading, the footer buttons, and the busy and loading states.

The element index is slotted in, so this component doesn't own the results list. Selection state, keyboard selection within the list, and announcements of results and selection count belong to the index, such as `ElementPicker` in `resources/js/modules/element-selector-modal/ElementSelectorModal.vue`.

## Requirements

### Semantics

- [x] The dialog is labeled by its heading through `aria-labelledby`. With `show-title` off, the heading is visually hidden but stays in the accessibility tree, so the dialog keeps its name. Per WCAG 4.1.2 (Name, Role, Value). Verified by `keeps the heading in the a11y tree when the title is hidden` in `element-selector-modal.test.ts`.
- [ ] The dialog has a name even when `label` is empty. Per WCAG 4.1.2 (Name, Role, Value). Known gap: with an empty `label`, no heading renders and the dialog has no name at all.
- [ ] The heading is an `h1`, since the modal is the page's main content while it's open. Per WCAG 1.3.1 (Info and Relationships). Not yet verified: the tests read the title's text, not its tag.
- [x] The Select and Cancel buttons have visible text labels, falling back to translated "Select" and "Cancel", and they can be replaced with labels that name the action ("Add", "Move"). Per WCAG 2.4.6 (Headings and Labels) and 2.5.3 (Label in Name). Verified by `uses the given labels, falling back to defaults` in `element-selector-modal.test.ts`.
- [x] Select is disabled until there is a selection to accept, and the disabled state is exposed. Per WCAG 4.1.2 (Name, Role, Value). Verified by `disables Select until can-submit is set` in `element-selector-modal.test.ts` and the `BoundToAController` story.

### Keyboard and focus

- [x] Opened through a controller, the dialog is modal (`:modal`, in the top layer), so focus stays inside it and the page behind is inert. Per WCAG 2.4.3 (Focus Order). Verified by the `BoundToAController` story.
- [ ] On open, focus moves to the heading (`tabindex="-1"`), so a screen reader announces what's being selected, and the next Tab reaches the index's first control in reading order. Focus doesn't go to the search field, which would skip anything before it, such as the status selector. Per WCAG 2.4.3 (Focus Order) and 1.3.2 (Meaningful Sequence). Known gap: the `h1` isn't focusable and the component sets no initial focus, so the browser focuses the first focusable element instead. With `show-title` off, the focused heading is visually hidden. A sighted keyboard user then sees no focus position until the next Tab.
- [x] There's no close button. Cancel dismisses the modal, and when a controller is bound, the request goes to the controller, which can refuse it. Per WCAG 2.1.1 (Keyboard). Verified by `has no header or close button when the title is hidden`, `emits craft-cancel and closes when Cancel is clicked`, and `routes Cancel to the controller rather than closing itself` in `element-selector-modal.test.ts`.
- [ ] Escape takes the same dismissal path as Cancel. Per WCAG 2.1.1 (Keyboard). Not yet verified for this component.
- [x] While a submit is in flight, Cancel refuses to dismiss, and the index and secondary actions are `inert`, so the selection can't change mid-save. Per WCAG 4.1.2 (Name, Role, Value). Verified by `disables both buttons and marks the index inert` and `refuses a Cancel click` in `element-selector-modal.test.ts`, and the `BoundToAController` story.
- [ ] Focus isn't lost when the body turns `inert`. Choosing an element from inside the index, such as by double-clicking or pressing Enter on a row, submits while focus is in the body, which then becomes inert. Per WCAG 2.4.3 (Focus Order). Not yet verified.

### Announcements

- [ ] Loading the index is announced. Per WCAG 4.1.3 (Status Messages). Known gap: the body's `craft-spinner` gets no slotted text, so it's silent to screen readers. `shows a spinner while loading with no index yet` in `element-selector-modal.test.ts` checks only that the spinner renders.
- [ ] A submit in flight is announced. The Select button's `loading` state relies on `craft-button`'s live region (see `button.a11y.md`). Per WCAG 4.1.3 (Status Messages). Not yet verified in this component.

### Visual and reflow

- [x] The surface never grows past 90% of the viewport's height. A long result list scrolls inside the body. Per WCAG 1.4.10 (Reflow). Verified by the `ManyResults` story. No test asserts that the footer buttons stay on screen.
- [ ] At 320 CSS px wide, the modal fits the viewport without horizontal scrolling. Per WCAG 1.4.10 (Reflow). Not yet verified.
