# Auth method setup

The user-security auth-method flows: the method listing on the password/2FA
screens, the per-method setup slideouts, and the recovery-codes generator.

- **`AuthMethodSetup`** — boots the `#auth-method-setup` listing: wires each
  method's Set up button, opens the setup slideout behind an elevated-session
  check, and can `refresh()` the listing after a method is added or removed.
  Templates construct it (`new Craft.AuthMethodSetup({onRefresh})`) and keep
  the instance at `Craft.authMethodSetup` so setup screens can trigger a
  refresh. Fires a `refresh` event alongside the `onRefresh` setting.
- **`AuthMethodSetup.Slideout`** (`AuthMethodSetupSlideout`) — the setup
  slideout, a subclass of the slideout module's `Slideout`. Setup templates
  reach it via `Craft.Slideout.instances[containerId]` and call
  `showSuccess()` when their flow completes.
- **`RecoveryCodesSetup`** — drives the recovery-codes screen inside that
  slideout: generates codes, lists them in the success state, and offers a
  download. Booted by its setup template with the slideout's container id.

`Craft.WebAuthnSetup` (the passkey ceremony) and the other `Craft.*` calls
remain page-global seams. The module stylesheet (`auth.scss`) ships with the
CP bundles; there's no separate asset bundle.

## No custom element

The listing is booted from `{% js %}` template code because its `onRefresh`
callback can't be expressed as an element attribute; the slideout contents
are dynamic. So there's no `.ce.ts` / `defineElement()` registration.

## Files

- `auth-method-setup.ts` — `AuthMethodSetup`, `AuthMethodSetupSettings`.
- `auth-method-setup-slideout.ts` — `AuthMethodSetupSlideout`.
- `recovery-codes-setup.ts` — `RecoveryCodesSetup`.
- `auth.scss` — the listing/slideout styles.
- `index.ts` — assigns `Craft.AuthMethodSetup` / `Craft.RecoveryCodesSetup`
  and imports the stylesheet. Imported for its side effects from
  `resources/js/cp.ts` and `resources/js/legacy.ts`.
