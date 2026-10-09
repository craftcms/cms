# `craft-status` accessibility

## Requirements

### Semantics

- [x] A dot with neither `status` nor `label` exposes no role or name, so it isn't announced as an unnamed image. Per WCAG 4.1.2 (Name, Role, Value). Verified by `is not an image when it has no status and no label` in `status.test.ts`.
- [x] A dot with a `status` is a named image (`role="img"`), named "Status: {status}" through `aria-label`. Per WCAG 1.1.1 (Non-text Content) and 4.1.2 (Name, Role, Value). Verified by `announces the status when one is set` in `status.test.ts`.
- [x] An explicit `label` replaces the derived name. Per WCAG 4.1.2 (Name, Role, Value). Verified by `prefers an explicit label over the status name` in `status.test.ts`.
- [ ] The derived name is translated in full. Per WCAG 3.1.2 (Language of Parts). Known gap: the "Status: {status}" template is translated, but the state is inserted as its raw key, so a non-English page reads "live" or "pending" in English.
- [ ] A dot beside visible text that already names the state can be hidden from assistive technology. Per WCAG 1.1.1 (Non-text Content). Known gap: any `status` gives the dot a name, and an empty `label` falls back to the derived one, so the state is read twice.

### Visual

- [ ] States differ by more than color. Per WCAG 1.4.1 (Use of Color). Known gap: every state is the same filled circle.
- [ ] Each state's fill or border reaches 3:1 against the default, sunken and selected-row surfaces, in the light and dark themes. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced-colors mode, the dot keeps a visible edge. Not yet verified. All states would then look the same, so the name is the only cue.

## Depends on usage

- 1.4.1 (Use of Color): show the state in visible text where the dot isn't enough on its own. See [Accessibility](./Status.mdx#accessibility).
- 4.1.2 (Name, Role, Value): set `label` when "Status: {status}" isn't what a person needs to hear. See [Accessibility](./Status.mdx#accessibility).
