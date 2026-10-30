# `craft-shortcut` accessibility

## Requirements

### Semantics

- [x] On Windows, Linux and unknown platforms the modifiers are spelled out as text (`Ctrl`, `Super`, `Alt`, `Shift`), which screen readers read as words. Per WCAG 1.1.1 (Non-text Content). Verified by `spells the modifiers out elsewhere` and `falls back to Ctrl for an unknown platform` in `shortcut.test.ts`.
- [x] The key itself comes from the default slot as text. Per WCAG 1.3.1 (Info and Relationships). Verified by `renders the key from the default slot` in `shortcut.test.ts`.
- [x] On a Mac, the ⌥, ⇧ and ⌘ symbols have a text alternative ("Option", "Shift", "Command"), with the symbols hidden from assistive technology. Per WCAG 1.1.1 (Non-text Content). Verified by `names the Mac symbols for assistive technology` in `shortcut.test.ts`.
- [x] Each key is marked up as keyboard input, in a `<kbd>` of its own. Per WCAG 1.3.1 (Info and Relationships). Verified by `puts each key in a keycap of its own` in `shortcut.test.ts`.

### Visual

- [ ] The shortcut text meets 4.5:1 against the key cap fill, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.
- [ ] The text scales with its surroundings and stays readable at 200% zoom. Per WCAG 1.4.4 (Resize Text). Not yet verified.
- [ ] In forced colors mode, the key cap outline and text stay visible. Not yet verified.

## Depends on usage

- 1.1.1 (Non-text Content): pair the shortcut with a text label that names the action. See [Accessibility](./Shortcut.mdx#accessibility).
- 4.1.2 (Name, Role, Value): expose a bound shortcut with `aria-keyshortcuts` on the control it triggers. See [Accessibility](./Shortcut.mdx#accessibility).
