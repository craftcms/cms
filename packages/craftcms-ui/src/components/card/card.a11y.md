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

- [x] A cover card's text reaches 4.5:1 over any image, whether or not it has loaded: the header bar, the content's scrim, and the footer are a fixed black or white that brings the semi-transparent ink to 4.5:1 over any pixel, so the contrast doesn't depend on the image's luminance. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `gives %s-theme text at least 4.5:1 over any pixel` and `uses the fixed fills over %s` in `cover.test.ts`, and `gives every pixel behind a cover card’s text 4.5:1, and behind its controls 3:1 (%s, label %s)` in `card.browser.test.ts`.
- [x] A cover card's header controls reach 3:1 over any image, resting or hovered: a header without text gives its checkbox and plain buttons a backing, and a hovered or pressed button's backing only gets stronger. Per WCAG 1.4.11 (Non-text Contrast). Verified by `gives %s-theme controls at least 3:1 over any pixel, resting or hovered` in `cover.test.ts`, and `backs a cover card’s plain buttons more strongly when they’re hovered` and `gives every pixel behind a cover card’s text 4.5:1, and behind its controls 3:1 (%s, label %s)` in `card.browser.test.ts`.
- [x] Every line of a cover card's text sits where its fill is at full strength, inside the header bar or below the scrim's fade, at narrow widths, with text enlarged to 400%, and with text spacing overridden. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `keeps every line of a cover card’s text and its controls where its fill is at full strength (%ipx wide, %s text, text spacing %s)` in `card.browser.test.ts`.
- [x] A focused control in a cover card gets a black and white ring, so one of its colors reaches 3:1 against any image. Per WCAG 1.4.11 (Non-text Contrast) and 2.4.7 (Focus Visible). Verified by `gives a cover card’s focused %s a black and white ring inside the card` in `card.browser.test.ts`.
- [x] White or black is picked for the header from the top of the image, and for the content from the bottom, and white without luminance. Per WCAG 1.4.3 (Contrast (Minimum)). Verified by `reads the header from the top row and the content from the bottom row` and `picks white when the luminance is %s` in `cover.test.ts`.
- [x] The header's content and controls take a `data-theme` that matches the ink, and the thumbnail doesn't. Per WCAG 1.4.11 (Non-text Contrast). Verified by `themes a cover card’s header and body, and leaves its thumbnail alone` in `card.browser.test.ts`.
- [x] A label bar doesn't move anything in the header, so the controls stay where they are from card to card. Not a WCAG requirement. Verified by `keeps a cover card’s header controls in place when its header gets a label bar` in `card.browser.test.ts`.
- [ ] In forced colors mode, the header, body, and footer take the `Canvas` color over the image, and the card a `CanvasText` border. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified in a forced colors browser.

### Scrolling and reflow

- [ ] At 320 CSS px, the body and the thumbnail column fit without horizontal scrolling. Per WCAG 1.4.10 (Reflow). Not yet verified: the thumbnail column is a fixed 120px.
- [ ] A cover card grows to fit its content, so nothing is cut off with text enlarged or its spacing overridden. Per WCAG 1.4.4 (Resize Text) and 1.4.12 (Text Spacing). Growth is covered by `grows a cover card past 4:3 when its content needs more room` in `card.browser.test.ts`; not yet verified with text enlarged or its spacing overridden.
- [ ] With `thumb-alignment="end"`, reading order still makes sense. The thumbnail comes first in the DOM but is drawn after the body. Per WCAG 1.3.2 (Meaningful Sequence). Not yet verified.

## Depends on usage

- 1.3.1 (Info and Relationships): slot a real heading into `label` when the card is a section. See [Accessibility](./Card.mdx#accessibility).
- 1.4.1 (Use of Color): `active` is fill and border color only, so mark a selection with text or a control too. See [Accessibility](./Card.mdx#accessibility).
- 4.1.2 (Name, Role, Value): `active` and `collapsed` aren't exposed, so the control that sets them exposes the state. See [Accessibility](./Card.mdx#accessibility).
- 4.1.2 (Name, Role, Value): name icon-only buttons in the `actions` slot. See [Accessibility](./Card.mdx#accessibility).
- 1.1.1 (Non-text Content): give a thumbnail that carries meaning a name, and hide one that doesn't. See [Accessibility](./Card.mdx#accessibility).
- 1.1.1 (Non-text Content): a cover image is decorative, so the card's content says what the image shows. See [Accessibility](./Card.mdx#accessibility).
