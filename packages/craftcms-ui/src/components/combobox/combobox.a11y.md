# `craft-combobox` accessibility

Follows the WAI-ARIA APG [Combobox](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/) pattern, as in the [Editable Combobox With List Autocomplete](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/examples/combobox-autocomplete-list/) example: typing filters a listbox popup (`aria-autocomplete="list"`), and in single-choice mode the first match becomes active automatically (automatic selection). By default the value must be one of the options; with `requireOptionMatch` set to `false` the field also accepts free text. `multiple-choice` switches to manual selection and shows each selected value as a removable token before the textbox, which the APG example doesn't cover.

Shares `HasLabel` with `craft-input`; see `input.a11y.md` for label, help text and error requirements. Options are `craft-option`; see `option.a11y.md`.

**Not for:**

- Picking from a short, fixed list without typing. Use `craft-select`, or `craft-select-rich` when the options need markup.
- Inserting suggestions into free text after a trigger character such as `@`. Use `craft-text-expander`.
- A list of actions or links. Use `craft-action-menu`.
- Search results that go somewhere, run an action or move focus when chosen. Choosing an option only sets the field's value.

A change that needs any of these is a different pattern. Don't add a mode to `craft-combobox` for it.

## Requirements

### Semantics

- [x] The textbox and the `role="combobox"` element take their accessible name from the label, including a label drawn by a wrapping `craft-field`. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Verified by `receives its accessible label from the Form field` in `combobox-multiple.browser.test.ts`.
- [ ] The `role="combobox"` element carries `aria-expanded`, `aria-haspopup="listbox"` and `aria-controls` naming the listbox; the textbox carries `aria-autocomplete="list"`. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] Known deviation from the APG pattern: in Chromium, Lion puts `role="combobox"`, `aria-expanded` and `aria-controls` on a wrapper `div` around the textbox (the ARIA 1.1 pattern), while `aria-autocomplete` and `aria-activedescendant` stay on the textbox. Other browsers get the ARIA 1.2 shape, with everything on the textbox. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified with screen readers.
- [ ] The listbox is named by the same label as the textbox, including a host `aria-labelledby` or a `craft-field` label. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] Known deviation: Lion wraps the slotted listbox in a `role="dialog"` named "Combobox options popup", which isn't part of the pattern and may be announced on opening. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] In `multiple-choice` mode the listbox carries `aria-multiselectable="true"`. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] Known gap: optgroup headings are rendered `aria-hidden="true"` with no `role="group"`, so screen reader users don't hear which group an option belongs to. Per WCAG 1.3.1 (Info and Relationships).
- [ ] The chevron and the selected option's prefix icon are decorative and hidden from assistive technology. Per WCAG 1.1.1 (Non-text Content). Not yet verified.

### Keyboard and focus

- [ ] Focus stays in the textbox while the list is open: the active option is exposed with `aria-activedescendant` on the textbox, not by moving focus. Per WCAG 2.1.1 (Keyboard) and 4.1.2 (Name, Role, Value). Not yet verified.
- [x] Down or Up opens the list even when the textbox is empty. Per WCAG 2.1.1 (Keyboard). Verified by `opens on ArrowDown even when empty and not showAllOnEmpty` in `combobox.test.ts`.
- [ ] Alt+Down opens the list without moving the active option. Per WCAG 2.1.1 (Keyboard). Not yet verified; nothing handles Alt separately, so it behaves like Down.
- [ ] Down and Up move the active option, wrapping at both ends and skipping disabled options. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [ ] Home and End move the caret in the textbox rather than the active option, as the APG example does. Per WCAG 2.1.1 (Keyboard). Not yet verified.
- [x] In single-choice mode the first match is active as soon as a query is typed, and Enter selects it, shows its label in the textbox and closes the list. Per WCAG 2.1.1 (Keyboard). Verified by `selects the filtered option when Enter is pressed for %s` in `combobox.test.ts`.
- [ ] Known deviation: Escape always empties the textbox as well as closing the list (Lion's `_listboxOnKeyDown`), so typed text is lost. The APG example closes the list first and keeps the text, clearing it only on a second Escape. Per WCAG 2.1.1 (Keyboard) and 3.2.2 (On Input). Not yet verified.
- [ ] Tab closes the list without selecting the active option, and moves focus as usual. Per WCAG 2.1.2 (No Keyboard Trap). Not yet verified.
- [ ] Selecting an option with a pointer returns focus to the textbox. Per WCAG 2.4.3 (Focus Order). Not yet verified.
- [x] Clicking the chevron focuses the textbox and opens or closes the list, as a select's arrow does, so pointer users aren't left with a dead target beside the textbox. The chevron stays hidden from assistive technology, since Down and Up already open the list. Per WCAG 2.5.8 (Target Size (Minimum)) and 3.2.4 (Consistent Identification). Verified by `opens and closes its options from the chevron, focusing the textbox` and `gives the whole end of the field beside the clear button to the chevron, with a text cursor` in `combobox-options.browser.test.ts`.
- [x] Typing announces `input` but commits nothing; `change` fires only when an option is chosen or the field is left. Per WCAG 3.2.2 (On Input). Verified by `emits native input while typing and change when the field is left` in `combobox.test.ts`.
- [ ] A keyboard-focused textbox shows the focus ring at 3:1 against the surface, and in `multiple-choice` mode the ring is drawn around the whole token area. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified.

### Multiple choice

- [x] Down and Enter add the active option, skipping options already selected, and the textbox empties for the next query. Per WCAG 2.1.1 (Keyboard). Verified by `skips selected options when continuing keyboard selection` in `combobox-multiple.browser.test.ts`.
- [x] A selected option leaves the list, so it's offered only as a token. Per WCAG 1.3.1 (Info and Relationships). Verified by `selects option values and excludes selected options` in `combobox-multiple.browser.test.ts`.
- [x] With free text allowed, Enter adds the typed text as a value. Per WCAG 2.1.1 (Keyboard). Verified by `commits free text on Enter before emitting change` in `combobox-multiple.browser.test.ts`.
- [x] Each token has a remove button named "Remove {label}", and `clearable` adds a "Clear" button; activating either removes the values. Per WCAG 4.1.2 (Name, Role, Value) and 2.5.3 (Label in Name). Verified by `removes tokens and clears all values through buttons` in `combobox-multiple.browser.test.ts`.
- [x] Backspace in an empty textbox removes the last token. Per WCAG 2.1.1 (Keyboard). Verified by `announces token removal once as a user change with updated submission values` in `combobox-multiple.browser.test.ts`.
- [ ] Removing a token, with its button or Backspace, moves focus to the textbox. Per WCAG 2.4.3 (Focus Order). Not yet verified.
- [x] A read-only combobox renders no remove buttons and ignores Backspace. Per WCAG 4.1.2 (Name, Role, Value). Verified by `submits read-only values without allowing edits` in `combobox-multiple.browser.test.ts`.
- [ ] Known gap: removing a token isn't announced. The remove button disappears and focus moves to the textbox, so a screen reader user hears only the textbox. Per WCAG 4.1.3 (Status Messages).

### Announcements

- [ ] Known gap: how many options match, and that none match, aren't announced. There is no live region; the only count is the `aria-setsize` read out with the active option. Per WCAG 4.1.3 (Status Messages).
- [ ] Known gap: the "Showing {shown} of {total}" footer shown when matches exceed `limit` is `aria-hidden="true"`, so screen reader users aren't told the list is incomplete. Per WCAG 1.3.1 (Info and Relationships) and 4.1.3 (Status Messages).

### Zoom and reflow

- [x] The open list stays within the viewport: it's at least as wide as the field, and no wider than 22.5rem unless the field is, so long labels and hints truncate rather than push it off-screen. Per WCAG 1.4.10 (Reflow). Verified by `keeps the list within the viewport when an option is long, $expected` in `combobox-options.browser.test.ts`.

### Pointer

- [ ] The clear button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.
- [ ] Each token's remove button is at least 24×24 CSS px, or has room around it. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified; the unpadded "×" glyph is likely smaller.

### Visual

- [ ] The active option and selected values are styled by `craft-option`; see `option.a11y.md` for their contrast and forced colors requirements. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified in the combobox.
- [ ] The textbox border, token fill and popup border reach 3:1 against the surface, and the footer text (`--c-text-quiet`) reaches 4.5:1, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast) and 1.4.3 (Contrast (Minimum)). Not yet verified.
- [ ] In forced colors mode the textbox border, token boundaries and popup border stay visible. Not yet verified.
- [ ] The popup opens below the field, or flips above it, without covering the textbox. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Not yet verified.
- [ ] In single-choice mode a long value doesn't run under the clear button. Per WCAG 1.4.10 (Reflow). Not yet verified; the textbox reserves end padding for the chevron only.

### Reflow and zoom

- [ ] At a 320px viewport the field, its tokens and the popup fit without horizontal scrolling, and long labels and tokens wrap. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): give the combobox a visible label, and don't use `placeholder` as the label. See [Accessibility](./Combobox.mdx#accessibility).
- 3.3.2 (Labels or Instructions): say in the label or help text whether values outside the list are accepted. See [Accessibility](./Combobox.mdx#accessibility).
- 2.5.3 (Label in Name): a host `aria-labelledby` or `aria-label` must contain the visible label text. See [Accessibility](./Combobox.mdx#accessibility).
- 1.4.1 (Use of Color): an option's `indicator` color only reinforces its label, so the label must say what the color means. See [Accessibility](./Combobox.mdx#accessibility).
