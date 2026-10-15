# `craft-tab` accessibility

Used inside `craft-tabs`; see `tabs.a11y.md` for the pattern.

The roles, `aria-selected`, roving tabindex, keyboard navigation, focus ring, selected bar, and target size are set or verified by `craft-tabs` and live in its doc.

## Requirements

- [ ] A disabled tab is exposed as disabled to assistive technology with `aria-disabled="true"`, not only dimmed and skipped. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] The tab's accessible name comes from its slotted content alone. The hidden copy of the label that reserves the selected width stays out of the accessibility tree, so the name is read once. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] A disabled tab takes no pointer events, so a click can't select it. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.

## Depends on usage

- 4.1.2 (Name, Role, Value): give an icon-only tab a name. See [Accessibility](./Tab.mdx#accessibility).
