# `craft-indicator` accessibility

## Requirements

- [x] A dot with a `label` is a named image (`role="img"`, with the label as its `aria-label`), and a dot without one exposes no role or name, so it isn't announced as an unnamed image. Verified by `indicator.test.ts`'s "is not an image without a label" and "becomes a named image once it has a label".
- [x] Every palette and variant fill, drawn `solid` (the default), reaches 3:1 against the default surface, the sunken surface and the selected-row surface (`--c-color-accent-fill-quiet`), in the light and dark themes, per WCAG 1.4.11 Non-text Contrast. Verified by `indicator.browser.test.ts`'s "draws every solid fill at 3:1 against …" cases.
- [x] A `white` or `black` dot, which would otherwise disappear against a surface of its own color, defaults to `outline-fill`, and its ring reaches 3:1 against the dot: dark around white, white around black. Verified by `indicator.test.ts`'s "defaults a %s fill to the %s appearance" and "marks a black fill so its outline can be white", and by `indicator.browser.test.ts`'s "rings a %s dot at 3:1 against its own fill".
- [x] An `outline` dot is a hollow ring in the fill color, and the ring reaches 3:1 against the same surfaces as a solid dot. Filled and hollow dots differ in shape as well as color. Verified by `indicator.browser.test.ts`'s "draws every hollow ring at 3:1 against …" cases.
- [ ] The "use shapes" user preference reaches `craft-indicator`, so a dot can differ by shape as well as color (WCAG 1.4.1). Known gap: it doesn't today.
- [ ] In forced-colors mode, the dot keeps a visible edge.
- [ ] A dot on a loud surface stays visible. Known gap: on `--c-color-neutral-fill-loud`, such as a highlighted or checked `craft-option`, every solid dot falls to about 1:1. The option's text still carries the meaning, so this isn't a 1.4.11 failure, but the dot disappears from the row a keyboard user is on.

## Depends on usage

- 1.4.1 (Use of Color): pair a meaningful dot with visible text, or vary `appearance`. See [Accessibility](./Indicator.mdx#accessibility).
- 4.1.2 (Name, Role, Value): set `label` when the dot is the only cue for a status. See [Accessibility](./Indicator.mdx#accessibility).
- 1.4.11 (Non-text Contrast): check a custom `fill` color reaches 3:1 against its surface. See [Accessibility](./Indicator.mdx#accessibility).
