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

- [x] At 320px wide and at 400% zoom, the trail fits its container without scrolling the page sideways. Per WCAG 1.4.10 (Reflow) and 1.4.4 (Resize Text). Crumbs that don't fit collapse into a menu, and the current page truncates when it alone is too wide. Verified by `collapses from the middle, keeping the first and last crumbs` and `truncates the last crumb when nothing else fits beside it` in `breadcrumbs.browser.test.ts`.
- [x] Crumbs collapsed out of the trail get `hidden`, leaving the accessibility tree, and stay reachable from the keyboard through a `craft-action-menu` named "More items", whose items are links to the same places, followed by the items of any menu slotted beside a collapsed crumb. Per WCAG 2.1.1 (Keyboard) and 1.4.10 (Reflow). Verified by `follows the collapsed crumb’s own link from the menu` and `keeps a collapsed crumb’s own menu, linked or not, in the overflow menu` in `breadcrumbs.browser.test.ts`.
- [ ] A choice selected in a collapsed crumb's menu, such as the current site, is shown as selected in the overflow menu. Per WCAG 1.3.1 (Info and Relationships). Known gap: it's marked `aria-current`, but it has no checkmark, which `craft-action-item` draws only for `type="checkbox"`, a type `craft-action-menu`'s item descriptors can't set.
- [x] The menu comes between the first crumb and the rest in focus and reading order, where it sits on screen. Per WCAG 2.4.3 (Focus Order) and 1.3.2 (Meaningful Sequence). Verified by `puts the menu between the first crumb and the rest in focus order` in `breadcrumbs.browser.test.ts`.

## Depends on usage

- 2.4.6 (Headings and Labels): give each trail a distinct `label` when a page has more than one. See [Accessibility](./Breadcrumbs.mdx#accessibility).
- 1.3.1 (Info and Relationships): put the current page last, with no `href`. See [Accessibility](./Breadcrumbs.mdx#accessibility).
