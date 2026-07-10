/**
 * Elevated session primitive — a promise wrapper over the legacy
 * `Craft.elevatedSessionManager`.
 *
 * The manager (still legacy JS, {@link ElevatedSessionManager.js}) owns the
 * timeout check + the login modal, including 2FA / passkeys / impersonation, so
 * both the DOM-form controller ({@link ElevatedSessionForm}) and the Inertia
 * save flow ({@link useElevatedSession}) route through it rather than
 * reimplementing that UI.
 *
 * The manager exposes a callback API; this wraps it in a promise:
 * - resolves once the user has an elevated session (immediately if one is still
 *   valid, otherwise after the login modal succeeds),
 * - rejects with an {@link ElevatedSessionCancelled} if the user dismisses the
 *   modal.
 *
 * When the manager isn't on the page (it's assigned by the legacy CP bundle),
 * this resolves so the caller proceeds — the server still enforces elevation via
 * `ConfirmsPasswords::requireConfirmedPassword()`, which returns a 403.
 */
export class ElevatedSessionCancelled extends Error {
  constructor() {
    super('Elevated session was cancelled.');
    this.name = 'ElevatedSessionCancelled';
  }
}

interface ElevatedSessionManagerLike {
  fetchingTimeout: boolean;
  requireElevatedSession(
    onSuccess: () => void,
    onCancel?: () => void,
    minSafeElevatedSessionTimeout?: number
  ): void | Promise<void>;
}

function manager(): ElevatedSessionManagerLike | undefined {
  return (window as any).Craft?.elevatedSessionManager;
}

/**
 * Ensure the user has an elevated session, showing the login modal if needed.
 *
 * @param minSafeElevatedSessionTimeout Minimum seconds that must remain on an
 *   existing elevated session for it to be reused without re-prompting.
 */
export function requireElevatedSession(
  minSafeElevatedSessionTimeout?: number
): Promise<void> {
  const mgr = manager();

  // No manager on the page — let the caller proceed; the server enforces.
  if (!mgr) {
    return Promise.resolve();
  }

  return new Promise((resolve, reject) => {
    mgr.requireElevatedSession(
      () => resolve(),
      () => reject(new ElevatedSessionCancelled()),
      minSafeElevatedSessionTimeout
    );
  });
}

/**
 * Whether the manager is mid-flight fetching the elevated-session timeout. The
 * DOM-form controller uses this to ignore re-entrant submits, mirroring the
 * legacy guard.
 */
export function isFetchingElevatedTimeout(): boolean {
  return manager()?.fetchingTimeout === true;
}
