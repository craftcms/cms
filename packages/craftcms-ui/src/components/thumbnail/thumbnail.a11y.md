# `craft-thumbnail` accessibility

## Requirements

- [x] An animated (GIF/WEBP) source is frozen to a static frame instead of autoplaying indefinitely, per WCAG 2.2.2 (Pause, Stop, Hide). Verified by the `Animated` story in `thumbnail.stories.ts`, which asserts the cover canvas renders for an animated source.
- [x] Freezing doesn't depend solely on the server-rendered `animated` attribute — a `.gif`/`.webp` `src`/`srcset` is treated as animated too, so thumbnails rendered without that attribute (e.g. hand-authored markup) still freeze. Verified by the `Animated` story (one thumbnail uses the attribute, the other a `.gif`-suffixed `src` with no attribute) and by `thumbnail.test.ts`'s extension-fallback cases.
- [x] The underlying `<img>`'s accessible name (`alt`) stays exposed to assistive technology once frozen — the cover canvas sits visually on top of it but never replaces or hides it from the accessibility tree. Verified by the `Animated` story via `computeAccessibleName()`.
- [x] The cover canvas itself is excluded from the accessibility tree (`aria-hidden="true"`), since it duplicates the underlying image purely for visual purposes. Verified by the `Animated` story and by `thumbnail.test.ts`.
