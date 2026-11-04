import {onBeforeUnmount, reactive, readonly} from 'vue';
import {isRecord} from '../runtime';
import type {UiChangeKind, UiPayload, UiValue, UiValues} from '../types';

type Refresh = (values: UiValues, scope?: string[]) => Promise<UiPayload>;

/**
 * Asks the server to re-render a UI scope after its values change: typing
 * waits a second for a pause, anything else asks straight away. Only the
 * latest request for a scope is applied, and nothing is applied once the
 * owner unmounts.
 */
export function useUiRefresh(options: {
  refresh: () => Refresh | undefined;
  disabled: () => boolean;
  /** The scope's current values, detached from the live ones. */
  snapshot: (scope: string[]) => UiValue;
  /** How the scope's values compare, so an unchanged scope isn't asked for again. */
  fingerprint: (values: UiValues, scope: string[]) => string;
  apply: (refreshed: UiPayload, scope: string[]) => void;
}) {
  const timers = new Map<string, ReturnType<typeof setTimeout>>();
  const versions = new Map<string, number>();
  const active = new Map<string, {field: string; version: number}>();
  const refreshingFields = reactive(new Set<string>());
  const lastValues = new Map<string, string>();

  onBeforeUnmount(() => {
    timers.forEach(clearTimeout);
    // Abandons any refresh still in flight, so its answer isn't applied.
    versions.clear();
  });

  /** Schedules a refresh of `scope` for an edit to the control at `fieldPath`. */
  function schedule(
    scope: string[],
    fieldPath: string[],
    kind: UiChangeKind
  ): void {
    if (options.disabled() || !options.refresh()) {
      return;
    }

    const key = JSON.stringify(scope);
    versions.set(key, (versions.get(key) ?? 0) + 1);
    clearTimeout(timers.get(key));

    if (kind === 'discrete') {
      void request(scope, fieldPath);

      return;
    }

    timers.set(
      key,
      setTimeout(() => request(scope, fieldPath), 1000)
    );
  }

  async function request(scope: string[], fieldPath: string[]): Promise<void> {
    if (options.disabled()) return;

    const snapshot = options.snapshot(scope);

    if (!isRecord(snapshot)) {
      throw new Error(`UI scope [${scope.join('.')}] must contain an object.`);
    }

    const key = JSON.stringify(scope);
    const serialized = options.fingerprint(snapshot, scope);

    if (serialized === lastValues.get(key)) {
      return;
    }

    lastValues.set(key, serialized);
    const version = (versions.get(key) ?? 0) + 1;
    versions.set(key, version);
    const field = JSON.stringify(fieldPath);
    const previous = active.get(key);

    if (previous) {
      refreshingFields.delete(previous.field);
    }

    active.set(key, {field, version});
    refreshingFields.add(field);

    try {
      const refreshed = await options.refresh()!(snapshot, scope);

      if (version === versions.get(key)) {
        options.apply(refreshed, scope);
      }
    } catch {
      lastValues.delete(key);
      // The current presentation and values are already the last valid state.
    } finally {
      if (active.get(key)?.version === version) {
        active.delete(key);
        refreshingFields.delete(field);
      }
    }
  }

  /** Records the values `scope` was last rendered with. */
  function seed(scope: string[], fingerprint: string): void {
    lastValues.set(JSON.stringify(scope), fingerprint);
  }

  /**
   * Abandons every pending and in-flight refresh. Their answers would describe
   * values that are being thrown away.
   */
  function cancel(): void {
    timers.forEach(clearTimeout);
    timers.clear();
    versions.clear();
    active.clear();
    refreshingFields.clear();
    lastValues.clear();
  }

  return {
    /** Control paths, as JSON, whose changes have a refresh in flight. */
    refreshingFields: readonly(refreshingFields),
    schedule,
    seed,
    cancel,
  };
}
