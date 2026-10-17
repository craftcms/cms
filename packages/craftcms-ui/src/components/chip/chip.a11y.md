# `craft-chip` accessibility

## Requirements

### Semantics

- [x] Custom `prefix` content (a selection checkbox, a badge) doesn't hide the chip's thumbnail or status indicator, so the thumbnail's alternative text and the status indicator's label stay in the accessibility tree. Per WCAG 1.1.1 (Non-text Content). Verified by `shows the thumbnail and status alongside custom prefix content` in `chip.browser.test.ts`.
- [x] The `selectable` checkbox takes its accessible name from `select-label`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `labels the checkbox from select-label` in `chip.test.ts`.
- [ ] Without `select-label`, the checkbox falls back to the translated name "Select" rather than having no name. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Visual

- [x] A plain or outline chip without a color of its own takes the theme’s default text color, so its label keeps its contrast against the transparent chip’s surroundings in dark mode. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `takes the theme’s text color when it has no fill of its own` in `chip.browser.test.ts`.
- [x] A colored chip's text reaches 4.5:1 against its fill, which is a light wash of its color, or its color's normal fill while selected, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `keeps colored chips’ text at 4.5:1, selected or not, in the %s theme` in `chip.browser.test.ts`.
- [x] A colored chip's `selectable` checkbox takes the chip's text color, like its other controls: unchecked as its border, and checked as its fill, with the mark cut out so the chip shows through. A white chip's checkbox keeps the standard border and accent fill. Per WCAG 1.4.11 (Non-text Contrast). Verified by `draws a colored chip’s selection checkbox in its text color, with the mark cut out once checked` and `keeps the standard checkbox colors on a white chip` in `chip.browser.test.ts`.
- [x] Status content, such as the draft icon, takes the theme's default text color rather than the chip's color, which is reserved for the chip's own icon, so a draft isn't mistaken for part of a colored category. A solid chip's status keeps its contrasting text color. Per WCAG 1.4.1 (Use of Color). Verified by `colors only the chip’s own icon with its color, not a %s chip’s status` in `chip.browser.test.ts`.
- [x] A label too long for a narrow parent truncates inside the chip instead of pushing the status or suffix out of it, so they stay in view. Per WCAG 1.4.10 (Reflow). Verified by `truncates the label rather than outgrowing a narrow parent` in `chip.browser.test.ts`.
- [ ] A plain chip, which has no border, keeps a transparent inset outline, so it still has a visible edge in forced colors, and a selected one colors that outline with the selection color, so its selection doesn't rest on the fill alone. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified: the test this cited no longer exists.
- [x] A label link in a white, gray, slate, black, or plain chip is told apart from plain text by color alone, and only underlined when hovered, unless the user turns on the "Underline links" preference. This is the CP's accepted approach to WCAG 1.4.1 for links (see `.github/instructions/a11y.instructions.md`). `does not underline a label link, nested or slotted directly` in `chip.browser.test.ts` covers the default. A black chip's contents are themed dark, so its links take the dark theme's color, at 4.5:1 or more against its fill. Any other chip (another `data-color`, or any `variant`, without the `plain` appearance) underlines its links in its own text color instead, since blue would clash with its fill. `gives a colored, non-plain chip’s links its text color and an underline until hovered` in `chip-link.browser.test.ts` covers both.
- [x] The `selectable` checkbox, at 14 by 14 CSS px, has clear space around it: a 24 CSS px circle centered on it doesn't reach the label. Per WCAG 2.5.8 (Target Size (Minimum)). Verified by `sizes the selection checkbox at 14px, with 24px of clear space around it` in `chip.browser.test.ts`.

## Depends on usage

- 4.1.2 (Name, Role, Value): name every button or link in the `suffix`, such as a remove button. See [Accessibility](./Chip.mdx#accessibility).
- 2.4.3 (Focus Order): move focus somewhere sensible when a remove action takes the chip out. See [Accessibility](./Chip.mdx#accessibility).
- 1.1.1 (Non-text Content): give a thumbnail that repeats the label `alt=""`. See [Accessibility](./Chip.mdx#accessibility).
- 1.4.1 (Use of Color): pair a meaningful status dot with text or a `label`, as `indicator.a11y.md` describes. See [Accessibility](./Chip.mdx#accessibility).
