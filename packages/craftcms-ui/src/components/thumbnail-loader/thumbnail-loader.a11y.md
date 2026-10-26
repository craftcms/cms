# `craft-thumbnail-loader` accessibility

A light-DOM controller that lazy-loads the thumb placeholders it wraps. It renders nothing of its own. For a single image in a component, see [`craft-thumbnail`](../thumbnail/thumbnail.a11y.md).

## Requirements

- [x] The element has no shadow root, so it adds no content, roles or text to the accessibility tree. Per WCAG 1.3.1 (Info and Relationships). Verified by `has no shadow root` in `thumbnail-loader.test.ts`.
- [x] The markup it wraps stays in place, so the structure and names around each thumbnail are unchanged. Per WCAG 1.3.1 (Info and Relationships). Verified by `leaves the markup it wraps in place` in `thumbnail-loader.test.ts`.
- [ ] Each loaded `<img>` takes its text alternative from the placeholder's `data-alt`, and is decorative (`alt=""`) without one. Per WCAG 1.1.1 (Non-text Content). Not yet verified.
- [ ] Loading isn't announced, so a list of thumbnails filling in adds no screen-reader noise. Per WCAG 4.1.3 (Status Messages). Not yet verified.

## Depends on usage

- 1.1.1 (Non-text Content): set `data-alt` on a placeholder whose image says something the surrounding text doesn't. See [Accessibility](./ThumbnailLoader.mdx#accessibility).
