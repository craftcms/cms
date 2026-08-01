# icon-picker

Replaces the legacy `Craft.IconPicker` jQuery widget
(`packages/craftcms-legacy/cp/src/js/IconPicker.js`) on legacy CP surfaces.

## Shape: a Vue-mount bridge, not a logic-class port

The icon picker's functionality already lives in the Vue component
`resources/js/common/form/IconPicker.vue` (used directly by the migrated
entry-types `Edit.vue`). Rather than re-porting it, this module is a thin custom
element that **mounts that Vue component** on non-Inertia surfaces:

| File | Role |
| --- | --- |
| `icon-picker.ce.ts` | `<craft-icon-picker>` — a plain custom element that `createApp(IconPicker, props).mount(this)` from its attributes on connect, and `unmount()`s on disconnect. |
| `index.ts` | `defineElement('craft-icon-picker', …)`. |

This mirrors the `auth/elevated-session` precedent (a Vue island on legacy
pages), but per-instance rather than as a singleton host.

## Why the Vue component works standalone

`IconPicker.vue`'s only `@inertiajs/vue3` dependency is `useHttp`, which already
runs outside an Inertia page (`markdown-field` uses it in plain-TS behaviors on
legacy edit pages). Everything else (`useAsyncIcon`, `Modal`/`Pane`/`CraftInput`,
the Wayfinder `IconController.pickerOptions` URL helper) is standalone.

## Attributes → props

`name`, `value` (→ `modelValue`), `free-only`, `disabled`, `label`, `error`,
`labelled-by`, `described-by`. The component owns its label (matching the modern
entry-types page), so field surfaces pass `label`; label-less table cells pass
`labelled-by`. The component renders its own `craft-input` with `hidden-input` +
`name`, so the selected value posts with the form. Model changes re-emit as a
bubbling `change` CustomEvent.

## Wiring & consumers

Imported for side effects from `resources/js/cp.ts` and `resources/js/legacy.ts`.
Consumers emit the element instead of `new Craft.IconPicker(...)`:
`_includes/forms/iconPicker.twig`, the `Craft.ui.createIconPicker` /
`createIconPickerField` factories (editable-table icon cells,
CustomizeSourcesModal). A deprecation shim for stray plugin `new
Craft.IconPicker(...)` callers lives in the yii2-adapter cpcompat bundle.

## Deferred / notes

- **`small`** (editable-table icon cells) isn't yet a component prop — cells
  render at normal size. Follow-up if the density matters.
- Each element is its own `createApp`; fine for one-off fields, one app per row
  in an editable-table icon column. Lazy-mount on first open if that ever bites.

## Verification

`vp check`. Behavior is verified live in the CP
(pick/change/remove, the search modal, and form posting of the selected icon).
