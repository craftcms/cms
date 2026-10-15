# `craft-badge-indicator` accessibility

A static marker, with an optional count, that sits beside or inside the thing it points at. Unlike `craft-badge`, it has no visible label of its own.

**Not for:**

- Naming a status in place. Use `craft-badge`.
- A status dot with no count. Use `craft-indicator` or `craft-status`.

## Requirements

### Semantics

- [x] Without `alt-text`, the marker exposes no role, so it isn't announced as an unnamed image. Per WCAG 1.1.1 (Non-text Content). Verified by `is not an image without alt text` in `badge-indicator.test.ts`.
- [x] With `alt-text`, the marker is a named image (`role="img"`), labeled by visually hidden text holding `alt-text`. Per WCAG 1.1.1 (Non-text Content) and 4.1.2 (Name, Role, Value). Verified by `becomes a named image once it has alt text` in `badge-indicator.test.ts`.
- [x] Each instance has a unique `id`, so the element it marks can reference it with `aria-describedby`. Per WCAG 1.3.1 (Info and Relationships). Verified by `gives each instance an id, so its label can be referenced` in `badge-indicator.test.ts`.
- [ ] A count is followed by `badge-count-suffix` as visually hidden text, so it's read with its units ("3 unread messages") rather than as a bare number. Per WCAG 1.3.1 (Info and Relationships). Not yet verified: no test asserts the hidden text.
- [ ] A count isn't lost when `alt-text` is also set. Per WCAG 1.3.1 (Info and Relationships). Known gap: `role="img"` makes the count and suffix presentational, and the name comes from `alt-text` alone, so the number isn't announced.
- [ ] An `aria-describedby` on the marked element that points at the host's `id` yields the marker's text: the count, its suffix or `alt-text`. Per WCAG 1.3.1 (Info and Relationships). Not yet verified. That text lives in the shadow root, and the same `id` is repeated on the inner `part="badge"` element, whose label `id` a light-DOM reference can't reach.

### Counts

- [x] A count of zero or less isn't shown, so the marker stays a dot rather than reporting "0". Per WCAG 1.3.1 (Info and Relationships). Verified by `stays a dot at zero` in `badge-indicator.test.ts`.
- [x] A count above `max` (99 by default) is shown as `99+`. Per WCAG 1.4.10 (Reflow). Verified by `caps the count at 99+` in `badge-indicator.test.ts`.
- [x] With `max` set to `Infinity`, the whole count is shown, formatted for the page's language, and the marker grows to fit it. Per WCAG 1.3.1 (Info and Relationships). Verified by `shows the whole count, formatted, when uncapped` in `badge-indicator.test.ts`.
- [ ] `99+` is read sensibly ("99 plus …"). Per WCAG 1.3.1 (Info and Relationships). Not yet verified with a screen reader. The exact count isn't available as hidden text.
- [ ] A change to the count isn't announced. The marker has no live region, so a count that updates while the page is open is silent. Per WCAG 4.1.3 (Status Messages). Not yet verified.

### Visual

- [ ] The count reaches 4.5:1 against the marker's fill for every `variant`, in the light and dark themes. Per WCAG 1.4.3 (Contrast (Minimum)). Not yet verified. By the token values, `primary` and `secondary` sit just above 4.5:1 and `inverse` well above. `secondary` keeps the `info` text color on the `warning` fill.
- [ ] The marker's fill reaches 3:1 against the default surface, for `primary` and `secondary`. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. `inverse` (`--c-color-neutral-fill-normal`) is meant for a dark surface and falls well below 3:1 on the default one.
- [ ] The marker's 2px ring, in `--c-surface-default` (or `--badge-border-color`), separates it from what it overlaps. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified on a non-default surface.
- [ ] In forced-colors mode, the marker stays visible. Its fill is replaced by a system color, so a bare dot may show only as its ring. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.

### Reflow and zoom

- [ ] The count scales with text size and stays inside the marker at 200%. Per WCAG 1.4.4 (Resize Text). Not yet verified. The marker's minimum size is set in `rem`.

## Depends on usage

- 1.1.1 (Non-text Content): set `alt-text`, or reference the marker from the element it marks, unless adjacent text already says what it means. See [Accessibility](./BadgeIndicator.mdx#accessibility).
- 1.3.1 (Info and Relationships): set `badge-count-suffix` whenever `badge-count` is set. See [Accessibility](./BadgeIndicator.mdx#accessibility).
- 4.1.3 (Status Messages): announce a count that changes while the page is open through a live region of its own. See [Accessibility](./BadgeIndicator.mdx#accessibility).
- 1.4.11 (Non-text Contrast): use `inverse` only on a dark or colored surface. See [Accessibility](./BadgeIndicator.mdx#accessibility).
