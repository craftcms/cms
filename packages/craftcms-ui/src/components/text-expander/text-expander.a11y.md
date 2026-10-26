# `craft-text-expander` accessibility

Adapts the WAI-ARIA APG [Combobox](https://www.w3.org/WAI/ARIA/apg/patterns/combobox/) pattern, as list autocomplete with automatic selection, to a free-text field: typing a trigger character (such as `@` or `#`) opens a listbox of suggestions, and committing one replaces only the trigger and query, not the field's whole value. It deliberately keeps the field's native textbox role (see Semantics), so it departs from the APG pattern there.

**Not for** (use another component instead):

- A field whose whole value is chosen from a list, with or without free text. Use `craft-combobox`.
- Choosing one value from a fixed list, with no typing. Use `craft-select`, or `craft-select-rich` when the options need markup.
- A list of commands or actions, rather than text to insert. Use `craft-action-menu`.

A change that needs any of these is a different pattern. Don't add a mode to `craft-text-expander` for it.

## Requirements

### Semantics

- [x] The field keeps its native textbox role, gets `aria-autocomplete="list"`, and gets `aria-controls` naming the listbox, appended to any `aria-controls` it already had. Every attribute the expander sets is restored when it's removed. Per WCAG 4.1.2 (Name, Role, Value). Verified by `binds by id and restores the target accessibility attributes` and `preserves textarea semantics` in `text-expander.test.ts`.
- [ ] Known deviation from the APG pattern: the field has no `role="combobox"` and no `aria-expanded`, since `aria-expanded` isn't valid on a textbox. A `role="combobox"` already on the field is removed. A text input also gets `aria-haspopup="listbox"`, which a textarea doesn't. Screen reader users therefore get no expanded or collapsed state on the field itself, and rely on the live region and `aria-activedescendant` instead. `preserves textarea semantics` asserts the missing `role` and `aria-expanded` on a textarea. Nothing asserts `aria-haspopup` on an input.
- [x] The popup is a `role="listbox"` named by the active trigger's `label`, falling back to "Suggestions". Per WCAG 4.1.2 (Name, Role, Value) and 1.3.1 (Info and Relationships). Verified by `labels the listbox for the active trigger, falling back to a default label` and `accepts static trigger configuration from JSON` in `text-expander.test.ts`.
- [x] Each suggestion is a `craft-option` whose accessible name is its label, followed by its hint when there is one, so a rich `renderOption` row is still announced by its label. Per WCAG 4.1.2 (Name, Role, Value) and 2.5.3 (Label in Name). Verified by `renders option hints and includes them in accessible names` in `text-expander.test.ts`, and by the `TextInput` story, which finds the option by role and name.

### Keyboard and focus

- [x] Focus stays in the text field throughout: the active option is exposed with `aria-activedescendant` on the field, not by moving focus. Per WCAG 2.1.1 (Keyboard) and 4.1.2 (Name, Role, Value). Verified by `returns to the input at list boundaries and leaves Tab behavior untouched` in `text-expander.test.ts`.
- [x] The first suggestion is active as soon as the list opens, including after it closes and reopens. Per WCAG 2.1.1 (Keyboard). Verified by `selects the first option after the popup closes and reopens` in `text-expander.test.ts` and the `TextInput` story.
- [x] Down and Up move the active option. Past either end, the active option is cleared and the caret is back in the text, rather than wrapping. Per WCAG 2.1.1 (Keyboard). Verified by `returns to the input at list boundaries and leaves Tab behavior untouched` in `text-expander.test.ts`.
- [x] Enter inserts the active suggestion in place of the trigger and query, fires `input` before `craft-select`, and doesn't reach handlers outside the field (such as a form's submit). Per WCAG 2.1.1 (Keyboard). Verified by `selects the first option with Enter and emits input before selection` in `text-expander.test.ts`, the `TextInput` story, and `keeps caret measurements from obscuring a slotted textarea while expanding and typing` in `text-expander.browser.test.ts`, which also asserts focus is still on the textarea and typing continues after the insertion.
- [x] Tab doesn't commit a suggestion and isn't prevented, so it moves focus as usual, and leaving the field closes the list. Per WCAG 2.1.2 (No Keyboard Trap) and 3.2.2 (On Input). Verified by `returns to the input at list boundaries and leaves Tab behavior untouched` in `text-expander.test.ts`.
- [ ] Escape closes the list without changing the text. Per WCAG 2.1.1 (Keyboard) and 3.2.2 (On Input). Partly verified: `closes the popup with Escape` in `text-expander.test.ts` asserts the list closes, but not that the field's value is unchanged.
- [ ] While the list is open in a textarea, Enter inserts the suggestion instead of a new line. Closing the list with Escape first is the only way to type a new line straight after a matching query. Not yet verified that this is discoverable.
- [x] Suggestions don't open during IME composition or over a text selection, so they don't interrupt input methods. Per WCAG 3.2.2 (On Input). Verified by `does not activate during composition or over a selection` in `text-expander.test.ts`.

### Announcements

- [x] When a query matches nothing, "No suggestions" is announced through a polite live region, and the region clears itself after five seconds. Per WCAG 4.1.3 (Status Messages). Verified by `clears the live region after the announcement timeout elapses` in `text-expander.test.ts`.
- [x] When the list closes, "Suggestions collapsed", or "{label} suggestions collapsed" for a labeled trigger, is announced. Per WCAG 4.1.3 (Status Messages). Verified by `announces the active trigger label when the popover closes` in `text-expander.test.ts`.
- [ ] "Loading" is announced while an asynchronous source is fetching. Per WCAG 4.1.3 (Status Messages). Partly verified: `debounces async sources and discards stale results` in `text-expander.test.ts` asserts "Loading" appears in the shadow root, which also matches the visible loading row, so it doesn't prove the live region carried it.
- [ ] Known gap: opening the list, and how many suggestions matched, aren't announced. A screen reader user learns the list opened only from the first option becoming active. Per WCAG 4.1.3 (Status Messages).
- [ ] Known gap: when an asynchronous source fails, the list closes with no announcement and only `craft-error` is fired. Per WCAG 4.1.3 (Status Messages).

### Pointer

- [x] Clicking or tapping a suggestion inserts it without moving focus out of the field. Per WCAG 2.5.2 (Pointer Cancellation) for touch, which commits on `pointerup`. Verified by `selects rich options with a pointer without moving focus` and `selects options with touch input` in `text-expander.test.ts`.
- [x] Clicking outside closes the list. Verified by `closes the popup when clicking outside` in `text-expander.test.ts`.
- [ ] Each suggestion is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.

### Visual

- [ ] The active suggestion is marked by the `craft-option` loud fill, with its text and hint at 4.5:1 against it, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)) and 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] An inactive option's hint reaches 4.5:1. The hint is drawn at 75% opacity of the text color (`color-mix(… transparent 25%)` in `option.styles.ts`), so its contrast depends on the surface. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.
- [ ] The active suggestion stays distinguishable in forced colors mode. It's marked by a background fill only, which forced colors replaces, so it may vanish. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [x] The hidden mirror used to measure the caret position never paints over the field while the list opens and the person keeps typing. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Verified by `keeps caret measurements from obscuring a slotted textarea while expanding and typing` in `text-expander.browser.test.ts`.
- [ ] The popup, anchored to the caret, doesn't cover the line being typed. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Not yet verified.
- [ ] The popup fits a 320px viewport: it's capped at `min(360px, 100vw - spacing)` wide and 320px tall, scrolling its list vertically. Long hints are truncated visually, but the full hint stays in each option's accessible name. Per WCAG 1.4.10 (Reflow). Not yet verified at 320px or at 400% zoom.

## Depends on usage

- 4.1.2 (Name, Role, Value): give the field a programmatic label, since the expander adds none. See [Accessibility](./TextExpander.mdx#accessibility).
- 3.3.2 (Labels or Instructions): give the field a visible label, and name the trigger characters in it or in help text. See [Accessibility](./TextExpander.mdx#accessibility).
