# `craft-chip` accessibility

## Requirements

### Semantics

- [x] Custom `prefix` content (a selection checkbox, a badge) doesn't hide the chip's thumbnail or status indicator, so the thumbnail's alternative text and the status indicator's label stay in the accessibility tree. Per WCAG 1.1.1 (Non-text Content). Verified by `shows the thumbnail and status alongside custom prefix content` in `chip.browser.test.ts`.
- [x] The `selectable` checkbox takes its accessible name from `select-label`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `labels the checkbox from select-label` in `chip.test.ts`.
- [ ] Without `select-label`, the checkbox falls back to the translated name "Select" rather than having no name. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Visual

- [x] A plain or outline chip without a color of its own takes the theme’s default text color, so its label keeps its contrast against the transparent chip’s surroundings in dark mode. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `takes the theme’s text color when it has no fill of its own` in `chip.browser.test.ts`.
- [x] A label too long for a narrow parent truncates inside the chip instead of pushing the status or suffix out of it, so they stay in view. Per WCAG 1.4.10 (Reflow). Verified by `truncates the label rather than outgrowing a narrow parent` in `chip.browser.test.ts`.
- [ ] A plain chip, which has no border, keeps a transparent inset outline, so it still has a visible edge in forced colors, and a selected one colors that outline with the selection color, so its selection doesn't rest on the fill alone. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified: the test this cited no longer exists.
- [x] A label link in a white or plain chip is told apart from plain text by color alone, and only underlined when hovered, unless the user turns on the "Underline links" preference. This is the CP's accepted approach to WCAG 1.4.1 for links (see `.github/instructions/a11y.instructions.md`). `does not underline a label link, nested or slotted directly` in `chip.browser.test.ts` covers the default. Any other chip (a `data-color` other than `white`, or any `variant`, without the `plain` appearance) underlines its links in its own text color instead, since blue would clash with its fill or fall short of 4.5:1 against it. `gives a non-white, non-plain chip’s links its text color and an underline until hovered` in `chip-link.browser.test.ts` covers it.
- [ ] The `selectable` checkbox is at least 24 by 24 CSS px, or has that much clear space around it. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified. The checkbox is the browser's default size.

## Depends on usage

- 4.1.2 (Name, Role, Value): name every button or link in the `suffix`, such as a remove button. See [Accessibility](./Chip.mdx#accessibility).
- 2.4.3 (Focus Order): move focus somewhere sensible when a remove action takes the chip out. See [Accessibility](./Chip.mdx#accessibility).
- 1.1.1 (Non-text Content): give a thumbnail that repeats the label `alt=""`. See [Accessibility](./Chip.mdx#accessibility).
- 1.4.1 (Use of Color): pair a meaningful status dot with text or a `label`, as `indicator.a11y.md` describes. See [Accessibility](./Chip.mdx#accessibility).
