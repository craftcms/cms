| `observe.ts` | Binds toggles as they join the page. |
| `observe.ts` | Binds toggles as they join the page. |
| `index.ts` | Assigns `window.Craft.FieldToggle`. |# field-toggle

Ports `Craft.FieldToggle` out of the legacy jQuery bundle
(`packages/craftcms-legacy/cp/src/js/FieldToggle.js`).

A **toggle** control shows/hides **target** fields based on its value:

- Toggle types: checkbox/radio/`role="switch"`, `<select>`, boolean menu
  (`data-boolean-menu`), `<button>`/`<a>` (a disclosure), and fieldset.
- Targets: `data-target`, `data-reverse-target` (shown when the toggle is off),
  or `data-target-prefix` + the toggle's value (select/boolean/prefixed cases).
- Reveal/collapse is height-animated.

## Shape

Logic-class + global-shim port (no custom element; `observe.ts` binds it):

| File | Role |
| --- | --- |
| `field-toggle.ts` | The engine on `@craftcms/garnish` `Base`. Setup is in `init()`, guarded by `new.target`. |
| `support.ts` | A `WeakMap<Element, FieldToggle>` — the instance registry (the jQuery-free mirror of `.data('fieldtoggle')`). |
| `observe.ts` | Binds toggles as they join the page. |
| `index.ts` | Assigns `window.Craft.FieldToggle`. |

`observe.ts` binds a FieldToggle to every `.fieldtoggle` on the page, and to each
one added later, so inserted markup needs no `Craft.initUiElements()` call.
`Cp.init()` starts it. Toggles already bound (by `$.fn.fieldtoggle`,
`new Craft.FieldToggle(...)` in `UI.js`, or `LinkField`) are left alone.

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

The reveal feature also lives in `craft-switch` (`__syncToggleTargets`, for a switch
with no bound FieldToggle) and
`craft-disclosure` (the button/disclosure case, via `aria-controls` + `data-state`).
This port keeps `Craft.FieldToggle` as the full multi-type implementation; those
two remain for their surfaces (craft-switch already defers to a bound FieldToggle).

## Verification

`vp check`. Behavior (each toggle type reveals/hides
and animates its targets) is verified live in the CP.
