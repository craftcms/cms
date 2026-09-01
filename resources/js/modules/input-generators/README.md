# input-generators

Ports the legacy input-generator cluster out of the jQuery bundle:

- `packages/craftcms-legacy/cp/src/js/BaseInputGenerator.js`
- `packages/craftcms-legacy/cp/src/js/HandleGenerator.js`
- `packages/craftcms-legacy/cp/src/js/SlugGenerator.js`
- `packages/craftcms-legacy/cp/src/js/UriFormatGenerator.js`
- `packages/craftcms-legacy/cp/src/js/DynamicGenerator.js`

A generator watches a **source** input and writes a generated value into a
**target** input as the user types (e.g. Name → Handle / Slug / URI format),
until the target is edited directly or the form is submitted.

## Relationship to `useInputGenerator` and the `@craftcms/ui` transforms

There are two orchestrators for the same idea, split by reactivity model:

- **`useInputGenerator`** (`resources/js/common/composables`) — the **Vue**
  version (`watch`/`ref`), used by the migrated settings `Edit.vue` pages.
- **`BaseInputGenerator`** (here) — the **DOM** version (jQuery-free), for the
  legacy `new Craft.HandleGenerator('#name', '#handle')` boots on Twig pages that
  aren't Vue yet. A Vue composable can't watch a raw DOM input, so this
  orchestrator is still needed for those transitional surfaces. It accepts the
  same argument forms the legacy class did — a selector string, an element, or an
  array-like (NodeList / array / **jQuery object**) — resolving them via plain
  DOM (`querySelectorAll` + `Array.from`) so jQuery callers keep working without
  the module importing jQuery.

Crucially, both share the **same transform functions** from
`@craftcms/ui/utilities/string` — `toHandle`, `toUriFormat`, `asciiString`
(and `toEnvVar`). The subclasses here only wire jQuery source/target
orchestration around those; they do **not** reimplement the transforms. So a
Twig page and its eventual Vue replacement generate identical values.

Exception: **slug** generation has no shared transform (it depends on the
page-global `XRegExp` and live `Craft.*` config, which don't belong in the
component package), so `slug-generator.ts` keeps that transform inline while
still reusing `asciiString`.

## Shape (differs from the standard module pattern)

These classes link **two existing inputs** rather than wrapping their own
server-rendered markup, so there is **no `.ce.ts` custom element** and **no
`support.ts` back-reference** (the legacy base never stored a `.data()` handle).
The port is just the logic classes plus the `index.ts` global shim:

| File | Role |
| --- | --- |
| `base-input-generator.ts` | The engine (source/target/form listening, `updateTarget`), on `@craftcms/garnish` `Base`. Setup is in `init()`, called from the constructor only for the leaf class via a `new.target` guard. |
| `handle-generator.ts` / `slug-generator.ts` / `uri-format-generator.ts` / `dynamic-generator.ts` | Subclasses overriding `generateTargetValue()`. |
| `index.ts` | Assigns `window.Craft.<Generator>` for all five so existing `new Craft.HandleGenerator(...)` boots keep working, and re-exports the classes. |

Instantiation is unchanged — every consumer still does
`new Craft.HandleGenerator('#name', '#handle')`, whether from a Twig `{% js %}`
block, a PHP-emitted `{% js %}` (e.g. `TitleField`), or a modern TS module
(`editable-table`, `generated-fields`). The port swaps the implementation, not
the surface, so no templates change.

## No jQuery; remaining global seams

The module is **jQuery-free** — the base orchestrator uses plain DOM
(`querySelectorAll`, `element.value`, `closest`, `dispatchEvent`, layout-based
visibility) and `@craftcms/garnish` `Base`'s jQuery-free listener registry.
`Craft.selectFullValue` was inlined so the base doesn't even touch `Craft`.

The only remaining page globals (`declare const … : any`) are in the subclasses,
and neither is jQuery:

- **`Craft`** — live config only (`Craft.handleCasing`, `Craft.slugWordSeparator`,
  `Craft.limitAutoSlugsToAscii`, `Craft.allowUppercaseInSlug`).
- **`XRegExp`** — slug unicode word matching.

String transforms (`asciiString`, `toHandle`, `toUriFormat`) come from
`@craftcms/ui`.

## Plugin extension

No `compatify`: nothing in core subclasses these via legacy `.extend()`. Plugins
that need custom generation use `Craft.DynamicGenerator(source, target, cb)` — a
`BaseInputGenerator` that delegates `generateTargetValue` to a callback. (This is
why `DynamicGenerator` is kept rather than removed.)

## Wiring

Imported for side effects from both `resources/js/cp.ts` (modern surfaces) and
`resources/js/legacy.ts` (legacy Twig pages), since the boots happen on both.

## Verification

`vp check`. Behavior (Name → Handle/Slug/URI as you
type, on the settings pages and in editable tables) is verified live in the CP.
