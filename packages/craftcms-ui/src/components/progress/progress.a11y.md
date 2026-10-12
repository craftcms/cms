# `craft-progress` accessibility

A circular progress indicator drawn on a `<canvas>`, using the WAI-ARIA [`progressbar`](https://www.w3.org/TR/wai-aria-1.2/#progressbar) role. It shows the progress of a task, not a static measurement, so it isn't a [Meter](https://www.w3.org/WAI/ARIA/apg/patterns/meter/).

**Not for** (use another component instead):

- Progress with room for a bar or a visible count (→ `craft-progress-bar`).
- Work whose progress can't be measured at all (→ `craft-spinner`).

## Requirements

### Semantics

- [x] The canvas is a `progressbar` with `aria-valuemin="0"`, `aria-valuemax="100"` and the current value as `aria-valuenow`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `is a progressbar with the full range declared` in `progress.test.ts`.
- [x] The progressbar takes its accessible name from `label`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `names itself` in `progress.test.ts`.
- [x] The value is also written as hidden text ("60%"), since the drawn arc has no text of its own. Per WCAG 1.1.1 (Non-text Content). Verified by `announces the percentage in text` in `progress.test.ts`.
- [x] An indeterminate indicator (`progress="-1"`) gives "Loading" as its hidden text instead of a number. Per WCAG 1.1.1 (Non-text Content). Verified by `announces loading when there is no value yet` in `progress.test.ts`.
- [ ] An indeterminate indicator has no `aria-valuenow`. Per WCAG 4.1.2 (Name, Role, Value). Known gap: it renders an empty `aria-valuenow=""` rather than removing the attribute, and `announces loading when there is no value yet` in `progress.test.ts` asserts that empty value.
- [x] A failed indicator gives "Failed" as its hidden text. Per WCAG 1.1.1 (Non-text Content). Verified by `announces a failure` in `progress.test.ts`.
- [ ] A failed indicator exposes the failure on the progressbar itself. Per WCAG 4.1.2 (Name, Role, Value). Known gap: the progressbar keeps its last `aria-valuenow`, and "Failed" lives only in the sibling hidden text.

### Color and contrast

- [ ] A failed indicator is distinguishable from a complete one by more than color. Per WCAG 1.4.1 (Use of Color). Known gap: a failed arc differs from a full one only by color.
- [ ] The progress arc reaches 3:1 against the track arc and the surface. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. The track and fail colors default to fixed hex values (`#a3afbb`, `#da5a47`), not theme tokens.
- [ ] In forced-colors mode, the arc stays visible against the system background. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. Forced colors doesn't recolor canvas pixels, so the hex track and fail colors are drawn as authored.

### Motion

- [ ] With `prefers-reduced-motion: reduce`, an indeterminate indicator draws a static arc, and value changes and completion skip the arc and fade animations. Per WCAG 2.3.3 (Animation from Interactions). Not yet verified. The preference is read once on connect, so a change made later isn't picked up.
- [ ] The indeterminate animation, which runs until the value is known, can be stopped. Per WCAG 2.2.2 (Pause, Stop, Hide). Known gap: only the reduced-motion preference stops it.

## Depends on usage

- 1.1.1 (Non-text Content): keep visible text beside the arc that says what is progressing, and whether it failed. See [Accessibility](./Progress.mdx#accessibility).
- 4.1.3 (Status Messages): announce completion or failure through a live region; the component has none. See [Accessibility](./Progress.mdx#accessibility).
- 1.4.11 (Non-text Contrast): check custom `color`, `bg-color` and `fail-color` values reach 3:1. See [Accessibility](./Progress.mdx#accessibility).
