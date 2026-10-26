# `craft-progress-bar` accessibility

A linear progress bar using the WAI-ARIA [`progressbar`](https://www.w3.org/TR/wai-aria-1.2/#progressbar) role. It shows the progress of a task, not a static measurement, so it isn't a [Meter](https://www.w3.org/WAI/ARIA/apg/patterns/meter/).

**Not for** (use another component instead):

- Progress in a small, fixed space, such as inside a button or beside a row (→ `craft-progress`).
- Work whose progress can't be measured at all (→ `craft-spinner`).

## Requirements

### Semantics

- [x] The track is a `progressbar` with `aria-valuemin="0"`, `aria-valuemax="100"` and the current percentage as `aria-valuenow`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `is a progressbar with the full range declared` in `progress-bar.test.ts`.
- [x] The progressbar takes its accessible name from `label`. Per WCAG 4.1.2 (Name, Role, Value). Verified by `names itself, so a bar on its own is not anonymous` in `progress-bar.test.ts`.
- [x] A `pending` bar removes `aria-valuenow` instead of reporting a value it doesn't have. Per WCAG 4.1.2 (Name, Role, Value). Verified by `reports no value while pending` in `progress-bar.test.ts`.
- [x] The fill width matches the reported value, including a value derived from `processed` and `total`. Per WCAG 1.3.1 (Info and Relationships). Verified by `fills to the progress value` and `derives progress from processed and total` in `progress-bar.test.ts`.
- [ ] With `show-status`, the visible status and the hidden percentage text aren't both read. Per WCAG 1.3.1 (Info and Relationships). Known gap: the hidden "50%" is always rendered, so a screen reader reads "25 / 50" and then "50%".

### Color and contrast

- [ ] The fill and the track's border reach 3:1 against the surface. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified.
- [ ] In forced-colors mode, the fill stays visible. Per WCAG 1.4.11 (Non-text Contrast). Not yet verified. The fill is a `background-color`, which forced colors replaces with the system background, so only the border would remain.

### Motion

- [ ] With `prefers-reduced-motion: reduce`, the pending stripes stop moving and `smooth` transitions are switched off. Per WCAG 2.3.3 (Animation from Interactions). Not yet verified.
- [ ] The pending animation, which loops until a value is set, can be stopped. Per WCAG 2.2.2 (Pause, Stop, Hide). Known gap: only the reduced-motion preference stops it.

## Depends on usage

- 1.1.1 (Non-text Content): turn on `show-status`, or show the progress as text nearby. See [Accessibility](./ProgressBar.mdx#accessibility).
- 4.1.3 (Status Messages): announce completion or failure through a live region; the component has none. See [Accessibility](./ProgressBar.mdx#accessibility).
- 1.4.11 (Non-text Contrast): check custom `--c-progress-bar-fill-color` and `--c-progress-bar-track-color` values reach 3:1. See [Accessibility](./ProgressBar.mdx#accessibility).
