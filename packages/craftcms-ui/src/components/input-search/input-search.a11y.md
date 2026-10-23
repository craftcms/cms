# `craft-input-search` accessibility

Extends `craft-input`; see `input.a11y.md` for label, help text, validation and focus requirements.

The subclass adds a search icon in the `prefix` slot, which stands in for the visible label, and a clear button inside the input, at its end, while the field has a value.

## Requirements

### Labels

- [x] Without a label of its own, the field is named "Search", and its label text is visually hidden: the search icon at its start is the visible label, and it stays while text is entered. Per WCAG 3.3.2 (Labels or Instructions) and 2.5.3 (Label in Name). Verified by `names the field "Search" and shows a search icon by default` in `input-search.test.ts` and `names the input with its visually hidden label` in `input-search.browser.test.ts`.
- [x] A label it's given replaces the default name. Per WCAG 4.1.2 (Name, Role, Value). Verified by `uses a label it’s given` in `input-search.test.ts`.
- [x] The search icon is hidden from assistive technology, since the field's name already says "Search". Per WCAG 1.1.1 (Non-text Content). Verified by `keeps the search icon out of the accessibility tree` in `input-search.test.ts`.

- [x] The search icon sits inside the input, so clicking it focuses the input, as clicking a label would, and the input's focus ring encloses it. Per WCAG 2.4.7 (Focus Visible). Verified by `puts the search icon inside the input, passing clicks through to it` in `input-search.browser.test.ts`.

### Clear button

- [x] The clear button is only present while the field has a value, is named "Clear search", and is a `type="button"`, so pressing it doesn't submit the form. Per WCAG 4.1.2 (Name, Role, Value) and 3.2.2 (On Input). Verified by `shows a named clear button only while the field has a value` in `input-search.test.ts`.
- [x] The clear button sits inside the input, ahead of any `suffix` content, so it follows the input in reading and Tab order. Per WCAG 1.3.2 (Meaningful Sequence) and 2.4.3 (Focus Order). Verified by `puts the clear button ahead of other suffix content in reading order` in `input-search.test.ts` and `puts the clear button inside the input, ahead of other suffix content, which fills its own segment` in `input-search.browser.test.ts`.
- [x] Pressing the clear button empties the field and moves focus to the input, so focus isn't lost when the button goes away. Per WCAG 2.4.3 (Focus Order). Verified by `clears the value and focuses the input when the clear button is pressed` in `input-search.test.ts`.
- [x] The clear button sits inside the input, so the input's own focus ring goes around it, outside the field when there's no `suffix` content, and inset when there is. Per WCAG 2.4.7 (Focus Visible). Verified by `puts the clear button inside the input, within its outer focus ring` and `keeps other suffix content in its own segment, with an inset focus ring` in `input-search.browser.test.ts`.
- [x] There's no clear button while the field is disabled or read-only. Per WCAG 4.1.2 (Name, Role, Value). Verified by `doesn’t offer a clear button when disabled or read-only` in `input-search.test.ts`.
- [x] The clear button is at least 24×24 CSS px. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `gives the clear button a target of at least 24×24 CSS px` in `input-search.browser.test.ts`.

### Keyboard

- [x] Pressing Escape in a field with a value empties it, without the key also reaching anything around the field, such as a dialog that would close. Per WCAG 2.1.1 (Keyboard). Verified by `clears the value on Escape` and `handles Escape before listeners on the input that empty it, even after moving` in `input-search.test.ts`.

### Visual

- [x] The search and clear icons reach 3:1 against the surface in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Verified by `sets the search and clear icons at 3:1 against the surface in the %s theme` in `input-search.browser.test.ts`.
- [ ] The search and clear icons stay visible in forced colors mode. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.

## Depends on usage

- 3.3.2 (Labels or Instructions): when the field searches something specific, label it with what it searches, such as "Search entries". See [Accessibility](./InputSearch.mdx#accessibility).
- 1.3.1 (Info and Relationships): put a page's main search in a `role="search"` region. See [Accessibility](./InputSearch.mdx#accessibility).
