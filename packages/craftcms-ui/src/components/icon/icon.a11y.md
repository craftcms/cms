# `craft-icon` accessibility

## Requirements

### Semantics

- [x] An icon without a `label` is decorative: the host is `aria-hidden` and has no role. Per WCAG 1.1.1 (Non-text Content). Verified by `is aria-hidden without a label` in `icon.test.ts`.
- [x] An icon with a `label` is a named image: the host has `role="img"` and the label as its `aria-label`, with no `aria-hidden`. Per WCAG 1.1.1 (Non-text Content) and 4.1.2 (Name, Role, Value). Verified by `exposes role=img and aria-label with a label` in `icon.test.ts`.
- [ ] Setting, changing, or clearing `label` after the first render switches the host between named image and `aria-hidden` to match. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

### Visual

- [x] An icon fetched by `name` has its SVG filled with `currentColor`, so it takes the color of the surrounding text, including the system text color in forced colors mode. Per WCAG 1.4.11 (Non-text Contrast). Verified by `renders the fetched svg with fill=currentColor` in `icon.test.ts`.
- [ ] A `badge` icon's glyph reaches 3:1 against its fill, for every `data-color`, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced colors mode, a `badge` keeps its border and its glyph stays visible. Not yet verified.

## Depends on usage

- 1.1.1 (Non-text Content): set `label` when the icon says something no nearby text does, and leave it off when the text already says it. See [Accessibility](./Icon.mdx#accessibility).
- 1.4.1 (Use of Color): a `badge` color only reinforces a status, so say the status in text or in the `label` too. See [Accessibility](./Icon.mdx#accessibility).
- 1.4.11 (Non-text Contrast): draw a slotted `<svg>` with `currentColor`, since the component doesn't recolor it. See [Accessibility](./Icon.mdx#accessibility).
