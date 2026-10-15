# `craft-breadcrumbs` accessibility

Follows the WAI-ARIA APG [Breadcrumb](https://www.w3.org/WAI/ARIA/apg/patterns/breadcrumb/) pattern: a named `nav` landmark holding an ordered list of links to the current page's ancestors, with the current page marked `aria-current="page"` and the separators hidden from assistive technology.

**Not for:**

- The main or section navigation of the control panel. Use `craft-nav-list`.
- Switching between views of the same content. Use `craft-tabs`.
- A list of actions or links behind a button. Use `craft-action-menu`.

## Requirements

### Semantics

- [x] The trail is a `nav` landmark named by `label`, applied as `aria-label`, which defaults to a translated "Breadcrumbs". Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Verified by `is a named navigation landmark` and `takes a label of its own` in `breadcrumbs.test.ts`.
- [ ] The crumbs are an ordered list, so a screen reader announces how many there are and where each sits. Per WCAG 1.3.1 (Info and Relationships). Known deviation: the items are slotted straight into the `nav`, with no `ol` or `li`.
- [ ] The last crumb is marked `aria-current="page"`, and every other crumb loses it when the trail changes. Per WCAG 1.3.1 (Info and Relationships) and 4.1.2 (Name, Role, Value). Not yet verified. The attribute goes on the `craft-breadcrumb-item` host, not on its link, so a last crumb that keeps an `href` exposes a link with no current state.
- [x] The default separator's template is hidden with `hidden` and `aria-hidden="true"`, so it isn't read out. Per WCAG 1.3.1 (Info and Relationships). Verified by `keeps the separator out of the accessibility tree` in `breadcrumbs.test.ts`.
- [ ] The separators drawn between crumbs aren't read out, and the last crumb gets none. Per WCAG 1.3.1 (Info and Relationships). Not yet verified. Each clone goes into an item's `separator` slot, which `craft-breadcrumb-item` hides.

### Reflow and zoom

- [ ] At 320px wide and at 400% zoom, the trail fits its container without scrolling the page sideways. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Known gap: collapsing isn't implemented (`adjustOverflow()` is a TODO), crumbs don't wrap, and each crumb is `white-space: nowrap`, so a deep trail overflows.
- [ ] Crumbs collapsed out of the trail stay reachable from the keyboard, in one activation, through a menu that follows `craft-action-menu`'s behavior. Per WCAG 2.1.1 (Keyboard) and 1.4.10 (Reflow). Not yet verified. There's no collapse menu yet.

## Depends on usage

- 2.4.6 (Headings and Labels): give each trail a distinct `label` when a page has more than one. See [Accessibility](./Breadcrumbs.mdx#accessibility).
- 1.3.1 (Info and Relationships): put the current page last, with no `href`. See [Accessibility](./Breadcrumbs.mdx#accessibility).
