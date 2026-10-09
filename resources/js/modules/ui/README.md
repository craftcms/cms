# ui

Compatibility shim that repoints the legacy `Craft.ui.create*` factories at
the modern `@craftcms/ui` creators, so legacy call sites produce web-component
markup without being touched.

## Shape

Unlike the other modules here, this isn't a full port of a legacy file — the
canonical implementations live in `@craftcms/ui`
(`packages/craftcms-ui/src/utilities/create.ts`, exported from the package
root):

- `createButton(config)` → `<craft-button>`
- `createSubmitButton(config)` → accent submit `<craft-button>`
- `createPasteButton(config)` → "Paste elements" `<craft-button>`

Each returns the **element** (not jQuery) and accepts the legacy config keys
(`class`, `html`, `spinner`, `toggle`, `controls`, `data`, …) plus the modern
component options (`variant`, `appearance`, `size`, `loading`). **New code
imports these directly** and migrates `Craft.ui.create*` call sites over
time.

This module patches the corresponding `Craft.ui` methods to delegate there
while keeping the legacy call-site contract:

- returns a jQuery collection;
- the label is a slotted `<span class="label">` (so `.find('.label')` works);
- a `class`-attribute observer mirrors the legacy style classes onto the
  component's properties: `disabled`/`loading` → the properties,
  `submit` → `variant="accent"`, `secondary` → `appearance="outline"`,
  `small`/`big` → `size`. The classes stay on the element (some double as
  selectors); sync is one-way, class list → properties.
- `Craft.ui` may be assigned before or after this module runs (Vite entry vs.
  fragment-injected legacy bundle); a property setter on `Craft.ui` patches
  the object whenever the legacy `UI.js` assigns it.

## Not (yet) covered

- All other `Craft.ui.create*` factories (text/select/checkbox/date/… fields)
  still produce legacy markup. Extend the same pattern: add a creator to
  `@craftcms/ui`, patch the method here.
- Legacy `data-icon` attribute styling and the `dashed`/`wrap` button classes
  have no `craft-button` equivalent yet; call sites relying on them keep the
  classes but lose their legacy CSS once the legacy stylesheet goes away.
- `config.spinner` is accepted and ignored — `craft-button` has a built-in
  spinner driven by `loading`.
