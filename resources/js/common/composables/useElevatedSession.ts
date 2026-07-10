import {
  ElevatedSessionCancelled,
  requireElevatedSession,
} from '@/modules/elevated-session/elevated-session';

/**
 * Vue-facing wrapper over the elevated-session primitive — the Inertia-friendly
 * counterpart to the legacy `Craft.ElevatedSessionForm`. Where the DOM-form
 * controller intercepts a native submit, Vue screens `await` this before
 * dispatching an Inertia visit.
 *
 * ```ts
 * const {requireElevatedSession} = useElevatedSession();
 * await requireElevatedSession(); // resolves once elevated, rejects on cancel
 * form.submit(...);
 * ```
 *
 * `useSettingsSave` uses this internally via its `elevatedFields` option; call it
 * directly for one-off actions (e.g. revealing a token).
 */
export function useElevatedSession() {
  return {requireElevatedSession, ElevatedSessionCancelled};
}
