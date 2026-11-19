# `craft-media-item` accessibility

## Requirements

### Semantics

- [x] The host and its regions have no role of their own and add no list, list item or article semantics, so the element around it supplies the structure. Per WCAG 1.3.1 (Info and Relationships). Verified by `adds no role of its own` in `media-item.browser.test.ts`.
- [x] The `icon` drawn in place of a missing thumbnail is hidden from screen readers, so it adds no unnamed image to the accessibility tree. Per WCAG 1.1.1 (Non-text Content). Verified by `draws the icon in place of a missing thumbnail, hidden from screen readers` in `media-item.browser.test.ts`.
- [x] The regions render in a fixed order (thumbnail, heading, supporting text, suffix), and the reading order follows that rendered order rather than the order of the light DOM children. Per WCAG 1.3.2 (Meaningful Sequence). Verified by `renders its regions in a fixed order, whatever the order of its children` in `media-item.browser.test.ts`.
- [x] A region with nothing slotted into it isn't rendered, so it adds no empty element to the reading order. Per WCAG 1.3.1 (Info and Relationships). Verified by `renders no region for an empty slot` in `media-item.browser.test.ts`.

### Visual

- [ ] The supporting text, drawn in `--c-text-quiet` at `--c-text-sm`, reaches 4.5:1 against the surface in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.

### Reflow and zoom

- [x] Without `truncate`, a long heading wraps rather than pushing past the item's edge. Per WCAG 1.4.10 (Reflow). Verified by `wraps a long heading rather than overflowing a narrow container` in `media-item.browser.test.ts`.
- [ ] With `truncate`, all of the heading and supporting text can be read at 320 CSS px wide. Per WCAG 1.4.10 (Reflow). Known gap: text past the item's edge is cut off with an ellipsis. `keeps the heading and body to one line each when truncated` in `media-item.browser.test.ts` covers the cut-off.

## Depends on usage

- 1.3.1 (Info and Relationships): place items in a list, or in elements that give each one its role. See [Accessibility](./MediaItem.mdx#accessibility).
- 1.1.1 (Non-text Content): give a slotted thumbnail image `alt=""` when the heading already names it, and a text alternative when it says more. See [Accessibility](./MediaItem.mdx#accessibility).
- 1.4.10 (Reflow): use `truncate` only where the full text can be reached another way, such as on the page the heading links to. See [Accessibility](./MediaItem.mdx#accessibility).
