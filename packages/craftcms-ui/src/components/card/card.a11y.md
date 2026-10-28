# `craft-card` accessibility

**Not for:**

- A clickable or linked block. The card has no link or button mode and never takes focus.
- A compact reference to one entity in a list. Use `craft-chip`.

## Requirements

### Semantics

- [ ] The card adds no role, landmark, or tab stop of its own, so the semantics come from its content. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [ ] A `collapsed` card hides its body and footer with `display: none`, so their content leaves the accessibility tree and the Tab order along with the screen. Per WCAG 2.4.3 (Focus Order). Not yet verified.

### Visual

- [x] A checkbox in a colored card's header matches the header's other controls: unchecked, its border takes the header's text color, and checked, it's filled with that color and its mark is cut out in the header's fill, reaching 3:1 against it, whether or not the card is active. White cards keep the accent color. Per WCAG 1.4.11 (Non-text Contrast). Verified by `colors a header checkbox like the header’s other controls in the %s theme` and `keeps the accent color for a white card’s checked checkbox` in `card.browser.test.ts`.
- [x] A plain control in an `active` card's header, such as its action menu or reorder button, hovers in a shade of the header's fill, so its icon keeps 3:1 against it. Per WCAG 1.4.11 (Non-text Contrast). Verified by `keeps a hovered header control readable on a selected card in the %s theme (%s)` in `card.browser.test.ts`.
- [ ] The text in the header, body, and footer reaches 4.5:1 against its fill in the default and `active` states, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified for every color. A black card's body, whose quiet fill is dark, is covered by `keeps a black card’s body text readable on a light page, selected or not` in `card.browser.test.ts`.
- [ ] The `active` state stays distinguishable in forced colors mode. Known gap: it's drawn with fill and border colors only, which forced colors mode replaces, so an active card looks the same as any other.

### Cover cards

- [x] A cover card's label reaches 4.5:1 against its header bar, and the checkbox and plain buttons in a header without a label reach 4.5:1 against their backing, over the image. Per WCAG 1.4.3 (Contrast (Minimum)) and 1.4.11 (Non-text Contrast). Verified by `gives a cover card’s label and backed controls 4.5:1 over the image` in `card.browser.test.ts`.
- [x] The header bar, the controls' backing, and the content's scrim are as light as they can be while bringing the semi-transparent white or black text up to 4.5:1 (the bar and backing) or 5:1 (the scrim) against the region's 10th or 90th luminance percentile, with floors of 65% for the backing and 40% for the scrim, for detail too small to show in the luminance and busy images, such as patterns, that it doesn't capture. The backing and the image behind the content are blurred, which evens out that detail. Per WCAG 1.4.3 (Contrast (Minimum)) and 1.4.11 (Non-text Contrast). Verified by `brings the label and content up to their targets over %s`, `backs the controls up to 4.5:1 where the image alone falls short`, `leaves the controls bare only where the image alone gives them 4.5:1`, and `keeps the backing at least 65%, and the scrim 40%, so a busy image doesn’t show through` in `cover.test.ts`.
- [x] White or black is picked for the header from the top of the image, and for the content from the bottom. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `reads the header from the top row and the content from the bottom row` in `cover.test.ts`.
- [x] Without luminance, the header and content are white over a heavier black. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `lays heavy black under white when the luminance is %s` in `cover.test.ts`.
- [x] The header's content and controls take a `data-theme` that matches the ink, and the thumbnail doesn't. Per WCAG 1.4.11 (Non-text Contrast). Verified by `themes a cover card’s header and body, and leaves its thumbnail alone` in `card.browser.test.ts`.
- [x] A label bar doesn't move anything in the header, so the controls stay where they are from card to card. Not a WCAG requirement. Verified by `keeps a cover card’s header controls in place when its header gets a label bar` in `card.browser.test.ts`.
- [ ] In forced colors mode, the header, body, and footer take the `Canvas` color over the image, and the card a `CanvasText` border. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified in a forced colors browser.
- [ ] Thin, bright, or dark detail (a road, a sunlit edge) can be too small to show in the luminance, so text over it can fall below 4.5:1 in places. The floors keep this to small areas: across the workbench's photos, measured pixel by pixel behind the content's text, the worst photo still gave 99% of it at least 4:1, and none of it less than 2.8:1. Those measurements were with an unblurred scrim aiming for 7:1, and haven't been repeated since the scrim blurs the image and aims for 5:1. Known gap.

### Scrolling and reflow

- [ ] At 320 CSS px, the body and the thumbnail column fit without horizontal scrolling. Per WCAG 1.4.10 (Reflow). Not yet verified: the thumbnail column is a fixed 120px.
- [ ] With `thumb-alignment="end"`, reading order still makes sense. The thumbnail comes first in the DOM but is drawn after the body. Per WCAG 1.3.2 (Meaningful Sequence). Not yet verified.

## Depends on usage

- 1.3.1 (Info and Relationships): slot a real heading into `label` when the card is a section. See [Accessibility](./Card.mdx#accessibility).
- 1.4.1 (Use of Color): `active` is fill and border color only, so mark a selection with text or a control too. See [Accessibility](./Card.mdx#accessibility).
- 4.1.2 (Name, Role, Value): `active` and `collapsed` aren't exposed, so the control that sets them exposes the state. See [Accessibility](./Card.mdx#accessibility).
- 4.1.2 (Name, Role, Value): name icon-only buttons in the `actions` slot. See [Accessibility](./Card.mdx#accessibility).
- 1.1.1 (Non-text Content): give a thumbnail that carries meaning a name, and hide one that doesn't. See [Accessibility](./Card.mdx#accessibility).
- 1.1.1 (Non-text Content): a cover image is decorative, so the card's content says what the image shows. See [Accessibility](./Card.mdx#accessibility).
