# `craft-breadcrumb-item` accessibility

Used inside `craft-breadcrumbs`; see `breadcrumbs.a11y.md` for the pattern.

## Requirements

### Semantics

- [x] A crumb with `href` renders a native `<a href>`, so it's exposed as a link. Per WCAG 4.1.2 (Name, Role, Value). Verified by `renders a link when href is set` in `breadcrumb-item.test.ts`.
- [x] A crumb without `href` renders a `<span>`, never a link or other interactive element. Per WCAG 4.1.2 (Name, Role, Value). Verified by `renders a span label without href` in `breadcrumb-item.test.ts`.
- [ ] Content in the `separator` slot isn't read out. Per WCAG 1.3.1 (Info and Relationships). Not yet verified. The `<slot>` carries `aria-hidden="true"`; `has a separator slot for breadcrumbs to fill` in `breadcrumb-item.test.ts` checks the slot is filled, not that it's hidden.

### Keyboard and focus

- [ ] A linked crumb is in the Tab order and follows its link on Enter; a crumb without `href` isn't a Tab stop. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.
- [ ] A keyboard-focused link shows a visible ring at 3:1 against the surface. Per WCAG 2.4.7 (Focus Visible) and 1.4.11 (Non-text Contrast). Not yet verified. The item sets no focus style, so the ring is the browser default.

### Visual

- [ ] A linked crumb carries a persistent underline, removed on hover, so it can be told apart from the current-page crumb by more than weight. Per WCAG 1.4.1 (Use of Color). Known gap: links have no underline until hovered and inherit the text color, so a link and the current page differ only by font weight (medium vs. light). A persistent underline was added and later reverted.
- [ ] A linked crumb is at least 24×24 CSS px, or a 24px circle centered on it clears every neighboring crumb. Per WCAG 2.5.8 (Target Size (Minimum)). Not yet verified.
- [ ] In forced colors mode, links, the current-page crumb, separators and the focus ring stay visible. Not yet verified. The item sets no forced-colors styles and inherits its colors.

### Reflow and zoom

- [ ] A long label fits a 320px-wide container without scrolling the page sideways. Per WCAG 1.4.10 (Reflow). Known gap: the host is `white-space: nowrap`, so a label never wraps.

## Depends on usage

- 1.1.1 (Non-text Content): give an icon in the `prefix` or `suffix` slot a label when it adds meaning. See [Accessibility](./BreadcrumbItem.mdx#accessibility).
