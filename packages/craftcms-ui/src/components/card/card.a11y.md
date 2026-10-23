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

### Scrolling and reflow

- [ ] At 320 CSS px, the body and the thumbnail column fit without horizontal scrolling. Per WCAG 1.4.10 (Reflow). Not yet verified: the thumbnail column is a fixed 120px.
- [ ] With `thumb-alignment="end"`, reading order still makes sense. The thumbnail comes first in the DOM but is drawn after the body. Per WCAG 1.3.2 (Meaningful Sequence). Not yet verified.

## Depends on usage

- 1.3.1 (Info and Relationships): slot a real heading into `label` when the card is a section. See [Accessibility](./Card.mdx#accessibility).
- 1.4.1 (Use of Color): `active` is fill and border color only, so mark a selection with text or a control too. See [Accessibility](./Card.mdx#accessibility).
- 4.1.2 (Name, Role, Value): `active` and `collapsed` aren't exposed, so the control that sets them exposes the state. See [Accessibility](./Card.mdx#accessibility).
- 4.1.2 (Name, Role, Value): name icon-only buttons in the `actions` slot. See [Accessibility](./Card.mdx#accessibility).
- 1.1.1 (Non-text Content): give a thumbnail that carries meaning a name, and hide one that doesn't. See [Accessibility](./Card.mdx#accessibility).
