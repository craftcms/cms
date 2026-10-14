# `craft-timeline-item` accessibility

## Requirements

### Semantics

- [ ] The host has no role of its own and adds no list, list item or article semantics, so the element around it supplies the structure. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [ ] The marker region is `aria-hidden="true"`, so the marker icon is never announced and adds no unnamed image to the accessibility tree. Per WCAG 1.1.1 (Non-text Content). Not yet verified.
- [ ] The regions render in a fixed order (heading, metadata, body, footer), and the reading order follows that rendered order rather than the order of the light DOM children. Per WCAG 1.3.2 (Meaningful Sequence). Not yet verified.

### Visual

- [ ] The metadata and footer text, drawn in `--c-text-quiet` at `--c-text-xs`, reach 4.5:1 against the surface in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified.
- [ ] At 320 CSS px wide, the item causes no horizontal scrolling. Per WCAG 1.4.10 (Reflow). Not yet verified. The metadata doesn't wrap (`white-space: nowrap`) and sits in an `auto` grid column beside the heading, so long metadata can push past the item's edge instead of moving below the heading.
