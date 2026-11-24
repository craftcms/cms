# `craft-status` accessibility

## Requirements

### Semantics

- [x] A dot with neither `status` nor `label` exposes no role or name, so it isn't announced as an unnamed image. Per WCAG 4.1.2 (Name, Role, Value). Verified by `is not an image when it has no status and no label` in `status.test.ts`.
- [x] A dot with a `status` is a named image (`role="img"`), named "Status: {status}" through `aria-label`. Per WCAG 1.1.1 (Non-text Content) and 4.1.2 (Name, Role, Value). Verified by `announces the status when one is set` in `status.test.ts`.
- [x] An explicit `label` replaces the derived name. Per WCAG 4.1.2 (Name, Role, Value). Verified by `prefers an explicit label over the status name` in `status.test.ts`.
- [ ] The derived name is translated in full. Per WCAG 3.1.2 (Language of Parts). Known gap: the "Status: {status}" template is translated, but the state is inserted as its raw key, so a non-English page reads "live" or "pending" in English.
- [ ] A dot beside visible text that already names the state can be hidden from assistive technology. Per WCAG 1.1.1 (Non-text Content). Known gap: any `status` gives the dot a name, and an empty `label` falls back to the derived one, so the state is read twice.

### Visual

- [x] With the "Use shapes to represent statuses" preference on (`use-shapes` on `<body>`), `pending` is a triangle and `expired` a square, so those states differ by more than color. With it off, every state is a circle. Per WCAG 1.4.1 (Use of Color). Verified by `draws a %s dot as a %s with the use-shapes preference on` and `draws a %s dot as a circle with the use-shapes preference off` in `status.browser.test.ts`.
- [x] Each state's fill or border reaches 3:1 against the default, white, sunken, page and details surfaces, selected and hovered rows, and tinted card headers, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Verified by `draws the %s dot at 3:1 on every surface its colors are made for` in `status.browser.test.ts`.
- [x] In a selected card's header, whose fill can be any mid-tone, the dot's border takes the header's text color and reaches 3:1. Per WCAG 1.4.11 (Non-text Contrast). Verified by `rings the %s dot in a selected card header so it reaches 3:1` in `status.browser.test.ts`.
- [ ] With the "Use shapes to represent statuses" preference on, a `pending` triangle in a selected card's header reaches 3:1. Per WCAG 1.4.11 (Non-text Contrast). Known gap: the triangle's shape cuts off its border except along the bottom edge, and its fill alone doesn't reach 3:1 against the header.
- [ ] In forced-colors mode, the dot keeps a visible edge. Not yet verified. All states would then look the same, so the name is the only cue.

## Depends on usage

- 1.4.1 (Use of Color): show the state in visible text beside the dot. Only `pending` and `expired` have shapes of their own. See [Accessibility](./Status.mdx#accessibility).
- 1.4.11 (Non-text Contrast): a dot with no text beside it must reach 3:1 against what's behind it. See [Accessibility](./Status.mdx#accessibility).
- 4.1.2 (Name, Role, Value): set `label` when "Status: {status}" isn't what a person needs to hear. See [Accessibility](./Status.mdx#accessibility).
