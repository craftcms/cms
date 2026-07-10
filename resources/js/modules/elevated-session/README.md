# Elevated session

An Inertia-friendly, modern port of Craft's `Craft.ElevatedSessionForm` — the
guard that forces the user to (re)confirm their password before a sensitive
change is saved (toggling admin, granting new permissions/groups, changing a
password, revealing a GraphQL token, etc.).

## The two seams

Elevation has two moving parts. Only the **form** guard is ported here; the
**manager** stays legacy and is reused:

- **Manager** — `Craft.elevatedSessionManager` (still legacy, in
  `packages/craftcms-legacy/cp/src/js/ElevatedSessionManager.js`). It checks the
  remaining elevated-session time (`users/get-elevated-session-timeout`) and, if
  needed, shows the login modal — including 2FA, passkeys, and impersonation.
  Re-implementing that in Vue would be a large, risky duplication, so both the
  DOM controller and the Inertia flow delegate to it.
- **Form guard** — decides *when* elevation is required (which inputs/fields
  changed) and holds the submit until it's granted. This is what's ported.

## What's here

- `elevated-session.ts` — `requireElevatedSession()`, a promise wrapper over the
  manager. Resolves once elevated, rejects with `ElevatedSessionCancelled` if the
  modal is dismissed. Resolves as a no-op when the manager isn't on the page (the
  server still enforces elevation, so the request just 403s if truly required).
  The shared core for both seams below.
- `elevated-session-form.ts` — the `ElevatedSessionForm` class, a faithful port
  of the legacy `Craft.ElevatedSessionForm` onto `@craftcms/garnish` `Base`.
  Watches a list of input selectors on a `<form>`; on submit, if any changed, it
  holds the native submit and requires an elevated session first. Serves the
  still-legacy Twig/PHP screens (`_password.twig`, `_team.twig`, the field-layout
  email element, the GraphQL schema/token and user-group controllers).
- `elevated-session-form.ce.ts` — `<craft-elevated-session-form>`, a self-booting
  custom element wrapping a `<form>`, with an optional `inputs` JSON-array
  attribute. Lets Twig emit the guard declaratively instead of a `{% js %}` boot.
- `support.ts` — the `formElevatedSessions` WeakMap (form → instance back-ref +
  double-init guard).
- `index.ts` — assigns `window.Craft.ElevatedSessionForm` (the shim keeping the
  legacy `new Craft.ElevatedSessionForm(...)` callers working) and
  `defineElement('craft-elevated-session-form', …)`. Imported for side effects
  from `resources/js/cp.ts`.

## Inertia side

Vue/Inertia screens don't submit a DOM `<form>`, so the class guard can't hook
them. Instead:

- `@/common/composables/useElevatedSession` exposes the same
  `requireElevatedSession()` promise for one-off actions (see
  `pages/users/SignInProviders.vue`, `pages/graphql/tokens/Edit.vue`).
- `useSettingsSave(form, action, {elevatedFields: [...]})` snapshots those
  fields' initial values and, on save, requires an elevated session before
  `form.submit()` if any changed — the Inertia equivalent of the watched-input
  list. `pages/users/Permissions.vue` uses
  `elevatedFields: ['admin', 'groups', 'permissions']`. Pass `elevatedFields:
  'all'` instead to require elevation whenever the form is dirty (backed by
  Inertia's `form.isDirty`), rather than naming specific fields.

The server remains the source of truth: `update()` actions call
`ConfirmsPasswords::requireConfirmedPassword()`, which 403s without a confirmed
password. The client guard is UX (prompt up front) — not the enforcement.

## Legacy callers that must keep working

Via the `Craft.ElevatedSessionForm` shim, unchanged:

- `src/FieldLayout/LayoutElements/Users/EmailField.php`
- `src/Http/Controllers/Settings/Users/UserGroupsController.php`
- `src/Http/Controllers/Gql/{SchemasController,TokensController}.php`
- `resources/templates/users/_password.twig`,
  `resources/templates/users/_permissions.twig`,
  `resources/templates/settings/users/groups/_team.twig`

## Deferred

Behavioral/browser verification (the login modal round-trip, 2FA/passkey users,
and the legacy Twig callers) is left to manual testing.
