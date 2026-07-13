# Elevated session

Forces the user to (re)confirm their password before a sensitive change goes
through (toggling admin, granting new permissions/groups, changing a password,
revealing a GraphQL token, etc.).

Split along the same lines as other modules — a main controller
class, a custom element that boots it, and an `index.ts` that shims the modern
code onto the legacy `Craft.*` globals — plus the reactive manager + Vue modal
that back the confirmation UI.

## Files

- `elevated-session-form.ts` — the **main class**, `ElevatedSessionForm`. Guards a
  `<form>`: on submit, if any watched input changed, it holds the native submit,
  requires an elevated session via the manager, then re-submits. Dependency-free
  DOM (`requestSubmit` / `SubmitEvent`); the only jQuery seam is reading a Craft
  password field's swapped input.
- `elevated-session-form.ce.ts` — the **custom element**,
  `<craft-elevated-session-form>`. Boots `ElevatedSessionForm` around the `<form>`
  it wraps (optional `inputs` JSON-array attribute), so Twig can emit it instead
  of a `{% js %}` boot.
- `index.ts` — the **shim**. Assigns `window.Craft.ElevatedSessionForm` and
  `window.Craft.elevatedSessionManager` (a `LegacyElevatedSessionManager` adapter
  over the modern manager) so existing `new Craft.ElevatedSessionForm(...)`
  callers keep working, and `defineElement('craft-elevated-session-form', …)`.
  Also exports `mountElevatedSessionHost()` and the `usePasswordConfirmation()`
  composable. Imported for side effects from `resources/js/cp.ts`; the host is
  mounted from `resources/js/legacy.ts`.
- `manager.ts` — `ElevatedSessionManager`, a reactive (Vue) manager. Talks to the
  `users/confirm-password` endpoint, coalesces concurrent `require()` calls, and
  drives the modal's state. `run()` also retries a callback once on a `423`.
- `ElevatedSessionHost.vue` — the Vue modal that renders the `craft-login-form`
  (with 2FA / passkey fallbacks) when the manager needs confirmation. Mounted once
  into `<body>`.

## Two seams, one manager

There are two ways to require elevation, both routed through the single reactive
`elevatedSessionManager`:

- **Legacy DOM forms** — `ElevatedSessionForm` / `<craft-elevated-session-form>`,
  used by `EmailField.php` and the `_password` / `_team` / `_permissions` Twig
  screens through the `Craft.ElevatedSessionForm` shim.
- **Inertia/Vue** — `useSettingsSave({passwordConfirmation})` and direct
  `elevatedSessionManager.run(...)` calls (e.g. `SignInProviders.vue`,
  `tokens/Edit.vue`).

The server is the source of truth: `RequireConfirmedPassword` /
`ConfirmsPasswords::requireConfirmedPassword()` returns `423` when elevation is
missing, so the client guard is UX, not enforcement.
