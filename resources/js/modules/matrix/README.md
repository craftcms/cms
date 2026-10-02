# Matrix input module

Modern TypeScript port of the legacy `Craft.MatrixInput` /
`Craft.MatrixInput.Entry` (from `packages/craftcms-legacy/matrix/`), following
the shared module pattern (see `../listbox/README.md`):

- `matrix-input.ts` — the `MatrixInput` controller class (on
  `@craftcms/garnish` `Base`): add-entry buttons and XHR block rendering,
  drag-sort, multi-select, copy/paste, max-entries gating.
- `matrix-entry.ts` — the per-`.matrixblock` `MatrixEntry` controller:
  collapse/expand with preview text and localStorage persistence, the block
  action menu, enable/disable/move/duplicate/copy/delete, and conditional
  field-layout updates (`elements/update-field-layout`). Form tabs are owned by
  `FormRenderer`.
- `matrix-input.ce.ts` — the `<craft-matrix-input>` custom element, for
  markup-driven boots (config via `entry-types` / `input-name-prefix` /
  `settings` attributes).
- `support.ts` — `WeakMap` registries replacing the legacy
  `$container.data('matrix')` / `$container.data('entry')`.
- `index.ts` — registers the element and assigns `window.Craft.MatrixInput`
  (constructor-compatible, statics included, plus `.Entry`) for the
  PHP-emitted boot script in `Matrix::blockInputHtml()` and flash JS.

## Legacy interop (`interop.ts`)

Matrix fields render on legacy-stack pages and cooperate with widgets that
have no jQuery-free ports yet. All of those seams are typed and centralized in
`interop.ts`; each should disappear as its widget gets its own port:

- `Garnish.Select` (block multi-select), `Craft.FormObserver`, and
  `Craft.ElementEditor` — reached through the page
  globals.
- Server-initialized `Garnish.DisclosureMenu` instances (action menus, the
  add-entry menu button) — read from jQuery data.
- The instances also mirror themselves into jQuery data (`matrix` / `entry`)
  because PHP-emitted snippets (expand/collapse-all in `Matrix.php`) and
  legacy code read them from there.

## Deviations from the legacy implementation

- Velocity animations → Web Animations API, honoring `prefers-reduced-motion`.
- The titlebar `doubletap` Garnish event → native `dblclick` (which covers
  double-tap in modern browsers).
- The `delete` teardown event is dispatched as a native bubbling event;
  jQuery-*triggered* `delete` events from legacy code won't reach the native
  listener (none currently exist outside this module).

## Shipping

The module is loaded by both entrypoints (`cp.ts` / `legacy.ts`).
`MatrixAsset` no longer registers the legacy webpack bundle, whose source
remains at `packages/craftcms-legacy/matrix/` for reference until the
remaining interop seams are ported.
