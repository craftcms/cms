# `craft-avatar` accessibility

**Not for:**

- A user photo or an object's artwork. `craft-avatar` draws initials only and never loads an image. Use `craft-thumbnail`.

## Requirements

### Semantics

- [x] An avatar with a `label` is a named image: its SVG has `role="img"` and a `<title>` holding the label, so it's announced as the person's name rather than as its initials, which are presentational children of the image. Per WCAG 1.1.1 (Non-text Content) and 4.1.2 (Name, Role, Value). Verified by `becomes a named image once it has a label` in `avatar.test.ts`.
- [x] An avatar without a `label` is `aria-hidden`, with no role and no `<title>`, so its question-mark fallback isn't announced as an unnamed image. Per WCAG 1.1.1 (Non-text Content). Verified by `is hidden from assistive technology without a label` in `avatar.test.ts`.

### Visual

- [ ] The initials reach 4.5:1 against the disc in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Known gap: the disc is always `white` with the gradient at 25% opacity on top, while the initials take the inherited text color, so in the dark theme light initials sit on a near-white disc. `--c-avatar-color-text` is declared but nothing reads it.
- [ ] In forced colors mode, the disc keeps a visible edge and the initials stay visible. Not yet verified.
- [ ] Initials for a label of many words stay inside the disc. Not yet verified: one letter is drawn per word, at a fixed size, so a long name can overflow the circle and be clipped.

## Depends on usage

- 1.1.1 (Non-text Content): set `label` when nothing nearby names the person, and leave it off when the same name is shown next to the avatar. See [Accessibility](./Avatar.mdx#accessibility).
- 1.4.3 (Contrast (Minimum)): check custom gradient colors and the text color keep the initials at 4.5:1. See [Accessibility](./Avatar.mdx#accessibility).
