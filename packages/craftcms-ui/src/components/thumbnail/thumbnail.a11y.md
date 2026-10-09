# `craft-thumbnail` accessibility

## Requirements

### Motion

- [x] An animated (GIF/WEBP) source is frozen to a static frame instead of autoplaying indefinitely. Per WCAG 2.2.2 (Pause, Stop, Hide). Verified by `freezes an animated source instead of autoplaying, via the attribute or a .gif/.webp extension` in `thumbnail.browser.test.ts`, which asserts the cover canvas renders for an animated source.
- [x] Freezing doesn't depend solely on the server-rendered `animated` attribute: a `.gif`/`.webp` `src`/`srcset` is treated as animated too, so thumbnails rendered without that attribute (e.g. hand-authored markup) still freeze. Per WCAG 2.2.2 (Pause, Stop, Hide). Verified by `freezes an animated source instead of autoplaying, via the attribute or a .gif/.webp extension` in `thumbnail.browser.test.ts` (one thumbnail uses the attribute, another a `.gif`-suffixed `src` with no attribute) and by `freezes based on %s, without the animated attribute` in `thumbnail.test.ts`.
- [x] A thumbnail that was hidden when its image loaded still freezes once it becomes visible. Per WCAG 2.2.2 (Pause, Stop, Hide). Verified by `freezes once a thumbnail becomes visible, even if it was hidden when its image loaded` in `thumbnail.browser.test.ts`.

### Semantics

- [x] `alt` is passed through to the underlying `<img>`. Per WCAG 1.1.1 (Non-text Content). Verified by `renders an image from src` in `thumbnail.test.ts`.
- [x] The underlying `<img>`'s accessible name (`alt`) stays exposed to assistive technology once frozen. The cover canvas sits visually on top of it but never replaces or hides it from the accessibility tree. Per WCAG 1.1.1 (Non-text Content). Verified by `freezes an animated source instead of autoplaying, via the attribute or a .gif/.webp extension` in `thumbnail.browser.test.ts` via `computeAccessibleName()`.
- [x] The cover canvas itself is excluded from the accessibility tree (`aria-hidden="true"`), since it duplicates the underlying image purely for visual purposes. Per WCAG 1.1.1 (Non-text Content). Verified by `freezes an animated source instead of autoplaying, via the attribute or a .gif/.webp extension` in `thumbnail.browser.test.ts` and by `freezes when the animated attribute is set, without altering the image` in `thumbnail.test.ts`.
- [x] The loading placeholder is a CSS background on the thumbnail box, so it adds nothing to the accessibility tree and leaves the image's accessible name alone. Per WCAG 1.1.1 (Non-text Content). Verified by `doesn’t change the image’s accessible name` in `thumbnail.test.ts`.
- [ ] Without an `alt`, the image renders `alt=""`, so it's treated as decorative rather than announced by its file name. Per WCAG 1.1.1 (Non-text Content). Not yet verified.

## Depends on usage

- 1.1.1 (Non-text Content): describe an image that carries meaning in `alt`, and leave `alt` empty when it repeats a label beside it. See [Accessibility](./Thumbnail.mdx#accessibility).
