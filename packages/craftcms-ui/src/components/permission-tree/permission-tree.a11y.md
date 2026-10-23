# `craft-permission-tree` accessibility

No documented pattern fits. It's nested lists of `craft-checkbox` controls, grouped under headings, where each checkbox is a permission of its own. It isn't an APG Tree View: nothing expands or collapses, and there's no arrow-key navigation. A parent isn't a "select all" for its children either, so it has no mixed state.

**Not for:**

- A flat set of options. Use `craft-checkbox-group`.
- A parent checkbox that stands for its children, with a mixed state. Use `craft-checkbox-indeterminate`.

## Requirements

### Semantics

- [ ] Each permission is a native checkbox named by its `label`. Per WCAG 4.1.2 (Name, Role, Value). Not yet verified.
- [ ] A permission's `info` and `warning` are its checkbox's help text, referenced by `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships). Not yet verified.
- [ ] Nested permissions are a list inside their parent's list item, so the hierarchy is exposed and not shown by indentation alone. Per WCAG 1.3.1 (Info and Relationships). Not yet verified. Known gap: every permission gets a `<ul>` of its own, so siblings are announced as separate one-item lists rather than one list with a count.
- [ ] Each group is a `<section>` introduced by its heading. Per WCAG 1.3.1 (Info and Relationships). Known gap: the heading is always an `<h3>`, whatever level the page around it needs.
- [ ] Each group's "Select all" or "Deselect all" button is described by the group heading, so it says which group it acts on. Per WCAG 2.4.6 (Headings and Labels). Not yet verified.
- [x] A locked permission is shown checked and disabled. Per WCAG 4.1.2 (Name, Role, Value). Verified by `renders groups, locks inherited permissions, and clears nested selections` in `permission-tree.test.ts`.
- [ ] Why a permission is locked, or why a nested permission is unavailable until its parent is granted, is given in text. Per WCAG 1.3.1 (Info and Relationships) and 3.3.2 (Labels or Instructions). Known gap: both are only disabled, with nothing to explain why.

### Keyboard and focus

- [ ] Tab moves through the "Select all" buttons and enabled checkboxes in reading order, and Space toggles a checkbox. Per WCAG 2.1.1 (Keyboard) and 2.4.3 (Focus Order). Not yet verified.
- [ ] Focus stays on the checkbox that was toggled when the nested permissions under it become available or unavailable. Per WCAG 2.4.3 (Focus Order). Not yet verified.

### Visual

- [x] Its checkboxes are drawn like every other `craft-checkbox`, with the same size, border and checked fill, rather than as native checkboxes, so they share the checkbox's contrast and focus treatment. Per WCAG 1.4.11 (Non-text Contrast). Verified by `draws its checkboxes like other checkboxes` in `permission-tree.browser.test.ts`.
- [ ] Disabled permissions are distinguishable from enabled ones by more than color. Per WCAG 1.4.1 (Use of Color). Not yet verified.

### Reflow and zoom

- [ ] Indentation grows by one line height per level, and deep nesting still fits a 320px viewport with labels and help text wrapping. Per WCAG 1.4.10 (Reflow). Not yet verified.

## Depends on usage

- 2.4.6 (Headings and Labels): give every group a `heading`, since a group without one gets no heading and no "Select all" button. See [Accessibility](./PermissionTree.mdx#accessibility).
- 3.3.2 (Labels or Instructions): say on the page why locked permissions are fixed, such as which group grants them. See [Accessibility](./PermissionTree.mdx#accessibility).
