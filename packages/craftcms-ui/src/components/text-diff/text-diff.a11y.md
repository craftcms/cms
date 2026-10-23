# `craft-text-diff` accessibility

Its button follows the WAI-ARIA APG [Disclosure (Show/Hide)](https://www.w3.org/WAI/ARIA/apg/patterns/disclosure/) pattern: it shows and hides the unchanged text around the changes.

**Not for** (use another component instead):

- Showing or hiding content other than the text being compared (→ `craft-disclosure`).

## Requirements

### Semantics

- [x] Deleted text is in a `<del>` element, and inserted text is in an `<ins>` element, each starting with hidden text, "Deleted:" or "Inserted:", so screen readers that don't announce those elements still say which is which. Per WCAG 1.3.1 (Info and Relationships). Verified by `marks deleted and inserted text, naming each for screen readers` in `text-diff.test.ts`.
- [x] Highlighted passages are in `<mark>` elements. Per WCAG 1.3.1 (Info and Relationships). Verified by `marks highlighted passages` in `text-diff.test.ts`.
- [x] Each collapsed stretch shows an ellipsis that's hidden from screen readers, and hidden text saying "Unchanged text" in its place. Per WCAG 1.3.1 (Info and Relationships). Verified by `names collapsed stretches for screen readers` in `text-diff.test.ts`.

### Use of color

- [x] Deleted text is struck through, and inserted text is underlined, so the change doesn't rely on color alone. Per WCAG 1.4.1 (Use of Color). Verified by `strikes through deleted text and underlines inserted text, with forced colors %s` in `text-diff.browser.test.ts`.

### Keyboard and focus

- [x] The button that shows the collapsed text is a `craft-button`, reachable and activated from the keyboard. Per WCAG 2.1.1 (Keyboard). Verified by `reaches the button that shows the collapsed text, and uses it, from the keyboard` in `text-diff.browser.test.ts`.
- [x] The button carries `aria-expanded`, `false` while text is collapsed and `true` once all of it shows, and `aria-controls` naming the text. Per WCAG 4.1.2 (Name, Role, Value). Verified by `shows all of the collapsed text, and only the changes again, from its button` in `text-diff.test.ts`.
- [x] The button is only shown when collapsing hides text, so it never does nothing. Verified by `only shows its button when collapsing hides text` in `text-diff.test.ts`.
- [x] The button's accessible name ends with the `label`, so several diffs' buttons can be told apart. Per WCAG 2.4.6 (Headings and Labels). Verified by `shows all of the collapsed text, and only the changes again, from its button` in `text-diff.test.ts`.

### Color and contrast

- [x] Deleted and inserted text reach 4.5:1 against their backgrounds, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `sets deleted and inserted text at 4.5:1 against their backgrounds` in `text-diff.browser.test.ts`.
- [x] A highlighted passage's underline reaches 3:1 against the default, raised and sunken surfaces, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Verified by `draws the highlight’s underline at 3:1 against the %s surface` in `text-diff.browser.test.ts`.
- [x] In forced-colors mode, deleted and inserted text stay distinguishable through their strikethrough and underline. Per WCAG 1.4.1 (Use of Color). Verified by `strikes through deleted text and underlines inserted text, with forced colors %s` in `text-diff.browser.test.ts`.

## Depends on usage

- 2.4.6 (Headings and Labels): set `label` when a page shows several collapsible diffs. See [Accessibility](./TextDiff.mdx#accessibility).
