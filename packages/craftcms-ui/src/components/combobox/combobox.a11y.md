# `craft-combobox` accessibility

Follows the WAI-ARIA APG [Combobox](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/) pattern, as in the [Editable Combobox With List Autocomplete](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/examples/combobox-autocomplete-list/) example: typing filters a listbox popup (`aria-autocomplete="list"`), and in single-choice mode the first match becomes active automatically (automatic selection). By default the value must be one of the options; with `requireOptionMatch` set to `false` the field also accepts free text. `multiple-choice` switches to manual selection and shows each selected value as a removable token before the textbox, which the APG example doesn't cover.

Shares `FormControl` with `craft-input`; see `input.a11y.md` for label, help text and error requirements. Options are `craft-option`; see `option.a11y.md`.

**Not for:**

- Picking from a short, fixed list without typing. Use `craft-select`, or `craft-select-rich` when the options need markup.
- Inserting suggestions into free text after a trigger character such as `@`. Use `craft-text-expander`.
- A list of actions or links. Use `craft-action-menu`.
- Search results that go somewhere or run an action when chosen. Build the [Editable Combobox With List Autocomplete](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/examples/combobox-autocomplete-list/) pattern in place, with any per-result actions after the listbox.

A change that needs any of these is a different pattern. Don't add a mode to `craft-combobox` for it.

## Requirements

### Semantics

- [x] The textbox takes its accessible name from the label, including a label drawn by a wrapping `craft-field`. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Verified by `receives its accessible label from the Form field` in `combobox-multiple.browser.test.ts`.
- [x] The textbox carries `aria-expanded`, `aria-haspopup="listbox"`, `aria-controls` naming the listbox, and `aria-autocomplete="list"`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `exposes an expandable combobox that autocompletes from the listbox it controls` in `combobox-a11y.browser.test.ts`.
- [x] The textbox itself is the combobox, as in ARIA 1.2: it carries the role, `aria-expanded`, `aria-controls`, `aria-autocomplete` and `aria-activedescendant` in every browser. Per WCAG 4.1.2 (Name, Role, Value). Verified by `exposes an expandable combobox that autocompletes from the listbox it controls` and `keeps focus in the textbox and points it at the active option` in `combobox-a11y.browser.test.ts`. Not yet verified with screen readers.
- [x] The listbox is named by the same label as the textbox, including a host `aria-labelledby` or a `craft-field` label. Per WCAG 4.1.2 (Name, Role, Value). Verified by `names its listbox after the label from %s` in `combobox-a11y.browser.test.ts`.
- [ ] The popup around the listbox adds no role of its own, so opening it announces only the listbox. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified with screen readers.
- [x] In `multiple-choice` mode the listbox carries `aria-multiselectable="true"`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `marks its listbox multiselectable in multiple-choice mode` in `combobox-a11y.browser.test.ts`.
- [x] Each option group is a `role="group"` named by its visible label, with its options inside it, as in the APG [Listbox with Grouped Options](https://www.w3.org/WAI/ARIA/apg/patterns/listbox/examples/listbox-grouped/) example. Groups are `craft-option-group`; see `option.a11y.md` for the group itself, including its label's contrast. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `names each option group after its label and exposes its options in it (multiple choice: %s)` in `combobox-a11y.browser.test.ts`.
- [x] Down and Up move through options across groups, and Enter selects the active one. Per WCAG 2.1.1 (Keyboard). Verified by `moves the active option across groups and selects it with Enter` in `combobox-a11y.browser.test.ts`.
- [x] The chevron and the selected option's prefix icon are decorative and hidden from assistive technology. Per WCAG 1.1.1 (Non-text Content). Verified by `keeps the chevron and the selected option’s icon out of the accessibility tree` in `combobox-a11y.browser.test.ts`.
- [x] In `multiple-choice` mode the selected values are a list, one item per token, in the order they were chosen. Per WCAG 1.3.1 (Info and Relationships). Verified by `lists the selected values, in the order they were chosen` in `combobox-a11y.browser.test.ts`.
- [x] In `multiple-choice` mode the textbox is named by the label and then the selected values, so focusing it says what's already chosen. The name follows the values as they're added and removed, works with a wrapping `craft-field` label, and leaves out the tokens' "Remove" description. Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Verified by `names the combobox by the label and then the selected values, leaving out “Remove”`, `names the textbox by a wrapping field’s label and then its selected values` and `renames the textbox as values are added and removed` in `combobox-a11y.browser.test.ts`.
- [x] A `multiple-choice` token shows its option's icon or dot before the label. Both are decorative: the icon is hidden from assistive technology and the dot has no role or name, so the token is announced by its label alone. Per WCAG 1.1.1 (Non-text Content). Verified by `shows each selected option’s icon or dot on its token, hidden from assistive technology` in `combobox-multiple.browser.test.ts`.

### Keyboard and focus

- [x] Focus stays in the textbox while the list is open: the active option is exposed with `aria-activedescendant` on the textbox, not by moving focus. Per WCAG 2.1.1 (Keyboard) and 4.1.2 (Name, Role, Value). Verified by `keeps focus in the textbox and points it at the active option` in `combobox-a11y.browser.test.ts`.
- [x] Down or Up opens the list even when the textbox is empty. Per WCAG 2.1.1 (Keyboard). Verified by `opens on ArrowDown even when empty and not showAllOnEmpty` in `combobox.test.ts`.
- [x] Alt+Down opens the list without moving the active option. Per WCAG 2.1.1 (Keyboard). Verified by `opens on Alt+Down without moving the active option` in `combobox-a11y.browser.test.ts`.
- [x] Down and Up move the active option, wrapping at both ends. Per WCAG 2.1.1 (Keyboard). Verified by `moves the active option with Down and Up, wrapping at both ends` in `combobox-a11y.browser.test.ts`.
- [x] Down and Up stop on disabled options, which carry `aria-disabled="true"` and can't be selected, so they stay discoverable, as the APG's [Focusability of disabled controls](https://www.w3.org/WAI/ARIA/apg/practices/keyboard-interface/#focusabilityofdisabledcontrols) recommends for listbox options. Per WCAG 2.1.1 (Keyboard) and 4.1.2 (Name, Role, Value). Verified by `stops on a disabled option, exposed as unavailable, without selecting it` in `combobox-a11y.browser.test.ts`.
- [x] Home and End move the caret in the textbox rather than the active option, as the APG example does. Per WCAG 2.1.1 (Keyboard). Verified by `moves the caret, not the active option, with Home and End` in `combobox-a11y.browser.test.ts`.
- [x] In single-choice mode the first match is active as soon as a query is typed, and Enter selects it, shows its label in the textbox and closes the list. Per WCAG 2.1.1 (Keyboard). Verified by `selects the filtered option when Enter is pressed for %s` in `combobox.test.ts`.
- [ ] Escape closes the list and keeps the textbox's value: typed free text stays, and with `require-option-match` an unmatched query goes back to the selected option's label. With the list closed, Escape is left to the page, so a surrounding dialog can close. Per WCAG 2.1.1 (Keyboard) and 3.2.2 (On Input). Not yet verified.
- [x] Tab closes the list without selecting the active option, and moves focus as usual. Per WCAG 2.1.2 (No Keyboard Trap). Verified by `closes on Tab without selecting the active option, and moves focus on` in `combobox-a11y.browser.test.ts`.
- [x] Selecting an option with a pointer returns focus to the textbox. Per WCAG 2.4.3 (Focus Order). Verified by `returns focus to the textbox after an option is picked with a pointer (multiple choice: %s)` in `combobox-a11y.browser.test.ts`.
- [x] Clicking the chevron focuses the textbox and opens or closes the list, as a select's arrow does, so pointer users aren't left with a dead target beside the textbox. The chevron stays hidden from assistive technology, since Down and Up already open the list. Per WCAG 2.5.8 (Target Size (Minimum)) and 3.2.4 (Consistent Identification). Verified by `opens and closes its options from the chevron, focusing the textbox` and `gives the whole end of the field beside the clear button to the chevron, with a text cursor` in `combobox-options.browser.test.ts`.
- [x] Typing announces `input` but commits nothing; `change` fires only when an option is chosen or the field is left. Per WCAG 3.2.2 (On Input). Verified by `emits native input while typing and change when the field is left` in `combobox.test.ts`.
- [x] A keyboard-focused textbox shows the focus ring at 3:1 against the surface, and in `multiple-choice` mode one ring is drawn around the whole token area, with none on the textbox inside it. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Verified by `shows a 3:1 focus ring around the field when focused from the keyboard (multiple choice: %s)` and `draws one focus ring, around the field, when the multiple-choice textbox is focused` in `combobox-a11y.browser.test.ts`.
- [x] A keyboard-focused token shows its own 2px focus ring, the only ring in the field, at 3:1 against the field, in the light and dark themes, and keeps it in forced colors mode. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Verified by `shows a 3:1 focus ring on a token focused from the keyboard`, `rings only the token, not the field, when a token is focused` and `keeps the focus ring on a token in forced colors mode` in `combobox-a11y.browser.test.ts`.

### Multiple choice

- [x] Down and Enter add the active option, skipping options already selected, and the textbox empties for the next query. Per WCAG 2.1.1 (Keyboard). Verified by `skips selected options when continuing keyboard selection` in `combobox-multiple.browser.test.ts`.
- [x] A selected option leaves the list, so it's offered only as a token. Per WCAG 1.3.1 (Info and Relationships). Verified by `selects option values and excludes selected options` in `combobox-multiple.browser.test.ts`.
- [x] With free text allowed, Enter adds the typed text as a value. Per WCAG 2.1.1 (Keyboard). Verified by `commits free text on Enter before emitting change` in `combobox-multiple.browser.test.ts`.
- [x] Each token is a button named by its visible label and described by a hidden "Remove", and `clearable` adds a "Clear" button; activating either removes the values. Per WCAG 4.1.2 (Name, Role, Value) and 2.5.3 (Label in Name). Verified by `removes tokens and clears all values through buttons` in `combobox-multiple.browser.test.ts` and `names the combobox by the label and then the selected values, leaving out “Remove”` in `combobox-a11y.browser.test.ts`.
- [x] Backspace in an empty textbox removes the last token. Per WCAG 2.1.1 (Keyboard). Verified by `announces token removal once as a user change with updated submission values` in `combobox-multiple.browser.test.ts`.
- [x] Removing a token, with its button or Backspace, moves focus to the textbox. Per WCAG 2.4.3 (Focus Order). Verified by `focuses the textbox after a token is removed with %s` in `combobox-a11y.browser.test.ts`.
- [x] A read-only combobox renders no remove buttons and ignores Backspace. Per WCAG 4.1.2 (Name, Role, Value). Verified by `submits read-only values without allowing edits` in `combobox-multiple.browser.test.ts`.
- [x] Removing values, with a token's remove button, Backspace or the clear button, announces which were removed ("Removed Cherry", "Removed Apple and Cherry") through the polite live region, since the list of values announces additions only. Per WCAG 4.1.3 (Status Messages). Verified by `announces which values were removed with %s` in `combobox-a11y.browser.test.ts`.

### Announcements

- [x] Announcements go through a polite, atomic live region that's rendered empty before anything is announced, and clears itself after five seconds. Per WCAG 4.1.3 (Status Messages). Verified by `renders an empty polite live region before anything is announced` and `clears the live region after the announcement timeout elapses` in `combobox-a11y.browser.test.ts`.
- [x] Opening the list, and typing that changes the matches, announces how many options match ("4 results"), counting every match even when the list is cut short at `limit`. Per WCAG 4.1.3 (Status Messages). Verified by `announces how many options there are when the list opens (multiple choice: %s)`, `announces the new count as typing filters the options` and `announces every match, not just those shown, when the list is cut short` in `combobox-a11y.browser.test.ts`.
- [x] In `multiple-choice` mode the list of selected values is a polite live region (`aria-atomic="false"`), present and empty before anything is chosen, so each value is announced as it's added. Per WCAG 4.1.3 (Status Messages). Verified by `announces each value as it’s added, through a polite list that’s there before anything is chosen` in `combobox-a11y.browser.test.ts`.
- [x] When nothing matches, "No results" is announced. Per WCAG 4.1.3 (Status Messages). Verified by `announces when no options match` in `combobox-a11y.browser.test.ts`.
- [ ] Known gap: the "Showing {shown} of {total}" footer shown when matches exceed `limit` is `aria-hidden="true"`, so screen reader users aren't told the list is incomplete. Per WCAG 1.3.1 (Info and Relationships) and 4.1.3 (Status Messages).

### Zoom and reflow

- [x] The open list stays within the viewport: it's at least as wide as the field, and no wider than 22.5rem unless the field is, so long labels and hints truncate rather than push it off-screen. Per WCAG 1.4.10 (Reflow). Verified by `keeps the list within the viewport when an option is long, $expected` in `combobox-options.browser.test.ts`.

### Pointer

- [x] The clear button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `makes the clear button at least 24 by 24 pixels` in `combobox-a11y.browser.test.ts`.
- [x] Each token's remove button is at least 24×24 CSS px, or has room around it. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `makes each token’s remove button at least 24 by 24 pixels, or leaves room for one` in `combobox-a11y.browser.test.ts`.

### Visual

- [x] The active and selected options are styled by `craft-option`; see `option.a11y.md` for their contrast and forced colors requirements. Per WCAG 1.4.11 (Non-text Contrast). Verified by `marks only the selected option with a check mark, hidden from assistive technology` and `keeps the highlighted and selected options visible in forced colors mode` in `option.browser.test.ts`.
- [x] The textbox border and popup border reach 3:1 against the surface, and the footer text (`--c-text-quiet`) reaches 4.5:1 against the popup, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast) and 1.4.3 (Contrast (Minimum)). Verified by `draws the textbox and popup borders at 3:1, and the footer text at 4.5:1 inside the popup (multiple choice: %s)` in `combobox-a11y.browser.test.ts`.
- [x] In forced colors mode the textbox border and popup border stay visible. Verified by `keeps the textbox border and popup border in forced colors mode` in `combobox-a11y.browser.test.ts`.
- [x] The popup opens below the field, or flips above it, without covering the textbox. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Verified by `opens the popup %s the field without covering the textbox` in `combobox-a11y.browser.test.ts`.
- [ ] In single-choice mode a long value doesn't run under the clear button. Per WCAG 1.4.10 (Reflow). Known gap: the textbox reserves end padding for the chevron only, so the text runs under the clear button.

### Reflow and zoom

- [x] At a 320px viewport the field, its tokens and the popup fit without horizontal scrolling, and long labels and tokens wrap. Per WCAG 1.4.10 (Reflow). Verified by `fits a 320 pixel viewport, wrapping long tokens and labels, with the popup open` in `combobox-a11y.browser.test.ts`.

## Depends on usage

- 3.3.2 (Labels or Instructions): give the combobox a visible label, and don't use `placeholder` as the label. See [Accessibility](./Combobox.mdx#accessibility).
- 3.3.2 (Labels or Instructions): say in the label or help text whether values outside the list are accepted. See [Accessibility](./Combobox.mdx#accessibility).
- 2.5.3 (Label in Name): a host `aria-labelledby` or `aria-label` must contain the visible label text. See [Accessibility](./Combobox.mdx#accessibility).
- 1.4.1 (Use of Color): an option's `indicator` color only reinforces its label, so the label must say what the color means. See [Accessibility](./Combobox.mdx#accessibility).
