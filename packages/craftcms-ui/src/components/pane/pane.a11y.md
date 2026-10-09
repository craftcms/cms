# `craft-pane` accessibility

**Not for:**

- Collapsible or resizable panels. The pane has no toggle or resize handle. Use `craft-disclosure` for a section that shows and hides.

## Requirements

### Semantics

- [x] The `label` renders as a heading, an `<h2>` by default. Per WCAG 1.3.1 (Info and Relationships). Verified by `defaults to h2, below the page its own h1` and `renders the label as the heading text` in `heading-level.test.ts`.
- [x] `heading-level` sets the heading's level, clamped to `h1`–`h6`. Per WCAG 1.3.1 (Info and Relationships). Verified by `honors an explicit level`, `a pane that is the page heading can ask for h1`, and `clamps a level outside the six real headings` in `heading-level.test.ts`.

### Keyboard and focus

- [x] While its content overflows, the scroll container (not the host) takes `tabindex="0"`, so the scrolling content is reachable without a pointer. Per WCAG 2.1.1 (Keyboard). Verified by `makes the scroll container itself the tab stop` and `never asks the host to carry the tab stop` in `pane.test.ts`, and the `ScrollableCode` story.
- [x] A pane whose content fits has no tab stop or region role, and both come and go as the content grows and shrinks. Per WCAG 2.4.3 (Focus Order). Verified by `leaves a pane whose content fits out of the tab order` and `adds and drops the tab stop as the content grows and shrinks` in `pane.test.ts`.
- [x] `focus()` on the host moves focus to the scroll container while it scrolls. Per WCAG 2.1.1 (Keyboard). Verified by `forwards focus() to the scroll container` in `pane.test.ts` and the `ScrollableCode` story.
- [ ] Arrow and Page keys scroll a focused scroll container. Per WCAG 2.1.1 (Keyboard). Not yet verified: the `ScrollableCode` story sets `scrollTop` from script rather than pressing a key.
- [x] A focused scroll container shows an outline drawn inside its border, so an ancestor can't clip it. Per WCAG 2.4.7 (Focus Visible). Verified by `draws the focus ring on the scroll container, not the host` in `pane.test.ts`.
- [ ] The focus outline reaches 3:1 against the pane's fill, for every appearance and variant, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] A focused element in a scrolling pane isn't hidden under the sticky header or footer. Per WCAG 2.4.11 (Focus Not Obscured (Minimum)). Not yet verified.

### Scroll region name

- [x] The overflowing scroll container is a `role="region"` named by `label`, then by the host's `aria-label`, then by a generic "Scrollable region". Per WCAG 4.1.2 (Name, Role, Value). Verified by `exposes the scroll container as a labeled region`, `names the region after the pane label`, and `names the region after the host aria-label` in `pane.test.ts`, and the `ScrollableCode` story.

### Visual

- [ ] Text reaches 4.5:1 against the fill of every appearance and the `error` and `code` variants, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.
- [ ] In forced colors mode, the pane's edge and the header and footer dividers stay visible. Not yet verified.

### Scrolling and reflow

- [ ] At 320 CSS px, content wider than the pane is reachable rather than cut off. Per WCAG 1.4.10 (Reflow). Not yet verified: a pane that doesn't clamp its height uses `overflow: clip`, so an unbreakable string or wide table with no scroll container of its own is clipped.

## Depends on usage

- 4.1.2 (Name, Role, Value): set `label` or `aria-label` on a pane that may scroll, so its region isn't one of several named "Scrollable region". See [Accessibility](./Pane.mdx#accessibility).
- 1.3.1 (Info and Relationships): set `heading-level` to fit the page's outline. See [Accessibility](./Pane.mdx#accessibility).
- 1.4.1 (Use of Color): the `error` variant's color only reinforces an error, so say what's wrong in the pane's text. See [Accessibility](./Pane.mdx#accessibility).
- 1.4.10 (Reflow): give wide content its own scroll container or let it wrap. See [Accessibility](./Pane.mdx#accessibility).
