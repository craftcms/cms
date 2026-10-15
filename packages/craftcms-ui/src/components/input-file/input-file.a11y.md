# `craft-input-file` accessibility

Shares `HasLabel` with `craft-input`; see `input.a11y.md` for label, help text and error requirements. Built on Lion's file input rather than `CraftInput`: the native file input is hidden and taken out of the Tab order, and a `craft-button` opens the file dialog instead. This file covers that button, the selected-file list and the drop zone.

## Requirements

### Semantics

- [x] A native `<input type="file">` holds the value, so the selection submits with the form and the browser's own file dialog is used. Per WCAG 4.1.2 (Name, Role, Value). Verified by `renders a native file input` in `input-file.test.ts`.
- [x] The file dialog opens from a real button: a `craft-button` with `type="button"`, which doesn't submit the form. Per WCAG 2.1.1 (Keyboard) and 4.1.2 (Name, Role, Value). Verified by `supplies its own button for the file dialog` in `input-file.test.ts`.
- [x] The `<label>`'s `for` points at the native file input, so clicking the label opens the file dialog. Per WCAG 1.3.1 (Info and Relationships). Verified by `wires the label to the control` in `input-file.test.ts`.
- [x] `multiple` is passed to the native input, so the dialog allows more than one file. Per WCAG 4.1.2 (Name, Role, Value). Verified by `passes multiple through to the native input` in `input-file.test.ts`.
- [ ] The button's accessible name is its own text followed by the field label ("Select file Upload file"), through an `aria-labelledby` Lion copies from the native input. Per WCAG 4.1.2 (Name, Role, Value) and 2.5.3 (Label in Name). Not yet verified.
- [ ] Help text and validation messages are referenced by the button's `aria-describedby`, copied from the native input. Per WCAG 1.3.1 (Info and Relationships) and 3.3.1 (Error Identification). Not yet verified.
- [ ] A disabled control is announced as disabled. Per WCAG 4.1.2 (Name, Role, Value). Known gap: `disabled` disables the native input (`disables the native input` in `input-file.test.ts`), but not the `craft-button`, which stays in the Tab order and is announced as an ordinary button that does nothing.
- [ ] Each selected file's remove button is named after its file ("Remove photo.jpg file"), so several remove buttons can be told apart. Per WCAG 4.1.2 (Name, Role, Value) and 2.4.6 (Headings and Labels). Not yet verified.
- [ ] The preview thumbnail beside each file name has `alt=""`, since the name beside it already identifies the file. Per WCAG 1.1.1 (Non-text Content). Not yet verified.

### Keyboard and focus

- [ ] The native file input has `tabindex="-1"`, and the button is the control's only tab stop until files are chosen. Per WCAG 2.4.3 (Focus Order). Not yet verified.
- [ ] Enter and Space on the button open the file dialog. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] Removing a file moves focus to a sensible place, such as the next remove button or the select button. Per WCAG 2.4.3 (Focus Order). Known gap: the removed button leaves the DOM and nothing receives focus, so focus falls back to the page.
- [ ] With `enable-drop-zone`, the select button stays inside the drop zone, so dragging files is never the only way to add them. Per WCAG 2.5.7 (Dragging Movements). Not yet verified.

### Forms and validation

- [ ] Choosing files is announced: the new list, or at least how many files are selected. Per WCAG 4.1.3 (Status Messages). Known gap: the selected-file list has no live region, and the button's text doesn't change.
- [ ] A file rejected by the `accept` or file-size validator shows its message under that file, in an `aria-live` region, and the field reports the error in `feedback`. Per WCAG 3.3.1 (Error Identification) and 4.1.3 (Status Messages). Not yet verified.

### Visual

- [ ] Each remove button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified. It's a `small` icon `craft-button`.
- [ ] The selected-file item border reaches 3:1 against the surface in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. It uses `--c-color-neutral-border-quiet`.
- [ ] In forced colors mode the select button, remove buttons and drop zone border stay visible. Not yet verified.

### Reflow and zoom

- [ ] A long file name wraps rather than pushing the list past its container at a 320px viewport, and its remove button stays reachable. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): say which file types and sizes are accepted in help text, since `accept` only filters the dialog. See [Accessibility](./InputFile.mdx#accessibility).
- 2.5.3 (Label in Name): a replacement `file-select-button` keeps visible text and the field label in its name. See [Accessibility](./InputFile.mdx#accessibility).
