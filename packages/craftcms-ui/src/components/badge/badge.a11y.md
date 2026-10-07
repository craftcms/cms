# `craft-badge` accessibility

## Requirements

- [x] The default prefix indicator is decorative: it has no role or name, so the badge's status is announced once, by its label. Verified by `badge.browser.test.ts`'s "leaves the default indicator unnamed, so the label is announced once".
- [x] A slotted `prefix` that says something the label doesn't, such as a labeled `craft-icon`, keeps its own accessible name. Verified by the `CustomPrefix` story's `play()` in `badge.stories.ts`.
- [x] The label reaches 4.5:1 against the badge surface for every `Color` value, in the light and dark themes, per WCAG 1.4.3 Contrast (Minimum). Verified by `badge.browser.test.ts`'s "sets the label at 4.5:1 against the badge for every fill".
- [x] The default indicator reaches 3:1 against the badge surface for every `Color` value, in the light and dark themes, per WCAG 1.4.11 Non-text Contrast. Verified by `badge.browser.test.ts`'s "draws the indicator at 3:1 against the badge for every fill".
- [ ] In forced-colors mode, the badge keeps a visible edge.
- [x] `no-prefix` removes the indicator from the badge rather than hiding it visually. Verified by `badge.test.ts`'s "leaves out the prefix with no-prefix".
- [x] A labeled badge carries its status in its text, so the indicator's color is never the only cue (WCAG 1.4.1 Use of Color). Verified by `badge.test.ts`'s "renders a region once content arrives for it", which shows the label slot rendering once the badge has text.
