# field-toggle

Ports `Craft.FieldToggle` out of the legacy jQuery bundle
(`packages/craftcms-legacy/cp/src/js/FieldToggle.js`).

A **toggle** control shows/hides **target** fields based on its value:

- Toggle types: checkbox/radio/`role="switch"`, `<select>`, boolean menu
  (`data-boolean-menu`), `<button>`/`<a>` (a disclosure), and fieldset.
- Targets: `data-target`, `data-reverse-target` (shown when the toggle is off),
  or `data-target-prefix` + the toggle's value (select/boolean/prefixed cases).
- Reveal/collapse is height-animated.

## Shape

Logic-class + global-shim port (no custom element — it's booted imperatively):

| File | Role |
| --- | --- |
| `field-toggle.ts` | The engine on `@craftcms/garnish` `Base`. Setup is in `init()`, guarded by `new.target`. |
| `support.ts` | A `WeakMap<Element, FieldToggle>` — the instance registry (the jQuery-free mirror of `.data('fieldtoggle')`). |
| `index.ts` | Assigns `window.Craft.FieldToggle`. |

Instantiation is unchanged: the still-legacy `$.fn.fieldtoggle` plugin and the
`Craft.initUiElements` `.fieldtoggle` sweep (both in `cp/src/js/Craft.js`), plus
`new Craft.FieldToggle(...)` in `UI.js` / `LinkField` / `ElementDeletionManager`,
all boot via the assigned global.

## jQuery + global seams

The engine is DOM-based; the legacy velocity height animation is reimplemented on
the **Web Animations API**. jQuery survives only at the **`.data('fieldtoggle')`**
back-reference (and the `.data('selectize')` read) — a legacy coordination
contract that still-jQuery readers depend on: `LinkField`, and `craft-switch`'s
`__syncToggleTargets` guard (`jq(btn).data('fieldtoggle')`, which defers the
switch reveal to a bound FieldToggle). The instance is also stored in the
`support.ts` WeakMap, which the radio-group refresh uses jQuery-free. `Craft`
(config) and `Garnish` (the `activate` custom event) remain page globals.

## Related implementations (not consolidated here)

The reveal feature also lives in `craft-switch` (`__syncToggleTargets`, the switch
case on Inertia surfaces where the `.fieldtoggle` sweep doesn't run) and
`craft-disclosure` (the button/disclosure case, via `aria-controls` + `data-state`).
This port keeps `Craft.FieldToggle` as the full multi-type implementation; those
two remain for their surfaces (craft-switch already defers to a bound FieldToggle).

## Verification

`vp check`. Behavior (each toggle type reveals/hides
and animates its targets) is verified live in the CP.
