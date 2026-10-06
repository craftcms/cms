# `craft-indicator` accessibility

## Requirements

- [x] A dot with a `label` is a named image (`role="img"`, with the label as its `aria-label`), and a dot without one exposes no role or name, so it isn't announced as an unnamed image. Verified by `indicator.test.ts`'s "is not an image without a label" and "becomes a named image once it has a label".
- [ ] A dot that is the only cue for a status gets a `label` from its consumer. The element chip's status dot is the main case.
  - The server-rendered dot is verified by `tests/Unit/Cp/StatusHtmlTest.php`, which asserts its `Status: …` label.
  - The Vue element chip (`resources/js/modules/elements/components/ElementChips.vue`) sets the same label, but no test covers it.
- [x] Every palette and variant fill, drawn `solid` (the default), reaches 3:1 against the default surface, the sunken surface and the selected-row surface (`--c-color-accent-fill-quiet`), in the light and dark themes, per WCAG 1.4.11 Non-text Contrast. Verified by `indicator.browser.test.ts`'s "draws every solid fill at 3:1 against …" cases.
- [x] A `white` or `black` dot, which would otherwise disappear against a surface of its own colour, defaults to `outline-fill`, and its ring reaches 3:1 against the dot: dark around white, white around black. Verified by `indicator.test.ts`'s "defaults a %s fill to the %s appearance" and "marks a black fill so its outline can be white", and by `indicator.browser.test.ts`'s "rings a %s dot at 3:1 against its own fill".
- [x] An `outline` dot is a hollow ring in the fill colour, and the ring reaches 3:1 against the same surfaces as a solid dot. Filled and hollow dots differ in shape as well as colour. Verified by `indicator.browser.test.ts`'s "draws every hollow ring at 3:1 against …" cases.
- [ ] Status isn't conveyed by colour alone (WCAG 1.4.1). The indicator can't meet this on its own: its consumer pairs the dot with visible text or a `label`, or varies `appearance`. The "use shapes" user preference doesn't reach `craft-indicator`.
- [ ] In forced-colors mode, the dot keeps a visible edge.
- [ ] A dot on a loud surface stays visible. Known gap: on `--c-color-neutral-fill-loud`, such as a highlighted or checked `craft-option`, every solid dot falls to about 1:1. The option's text still carries the meaning, so this isn't a 1.4.11 failure, but the dot disappears from the row a keyboard user is on.
- [ ] A custom CSS colour passed to `fill`, such as a plugin's status colour, reaches 3:1 against its surface. The consumer is responsible for this, since a solid dot has no ring to fall back on.
