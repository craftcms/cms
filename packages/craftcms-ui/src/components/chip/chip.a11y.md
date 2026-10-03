# `craft-chip` accessibility

## Requirements

- [x] Custom `prefix` content (a selection checkbox, a badge) doesn't hide the chip's thumbnail or status indicator, so the thumbnail's alternative text and the status indicator's label stay in the accessibility tree. Verified by `chip.browser.test.ts`'s "shows the thumbnail and status alongside custom prefix content".
- [x] A plain or outline chip without a colour of its own takes the theme’s default text colour, so its label keeps its contrast against the transparent chip’s surroundings in dark mode. Verified by `chip.browser.test.ts`’s "takes the theme’s text color when it has no fill of its own".
- [x] A plain chip, which has no border, keeps a transparent inset outline, so it still has a visible edge in forced colors, and a selected one colours that outline with the selection colour, so its selection doesn't rest on the fill alone. Verified by `chip.browser.test.ts`'s "gives a plain chip an edge for forced colors, colored when selected, without changing its size".
