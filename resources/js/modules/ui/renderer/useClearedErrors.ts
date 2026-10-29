import {computed, reactive} from 'vue';
import type {UiPayload} from '../types';
import {isWithin} from './uiTree';

/**
 * Errors a control has dismissed for everything inside it — a table whose
 * rows were reordered, say, so its per-cell errors no longer point at the
 * right cells. They stay hidden until new errors arrive.
 */
export function useClearedErrors(errors: () => UiPayload['errors']) {
  const clearedScopes = reactive(new Map<string, string[]>());
  const effectiveErrors = computed(() =>
    errors().filter(
      (error) =>
        ![...clearedScopes.values()].some(
          (scope) =>
            error.path.length > scope.length && isWithin(error.path, scope)
        )
    )
  );

  return {
    effectiveErrors,
    clearChildren(path: string[]): void {
      clearedScopes.set(JSON.stringify(path), [...path]);
    },
    childrenCleared(path: string[]): boolean {
      return clearedScopes.has(JSON.stringify(path));
    },
    /** Shows the dismissed errors within `scope` again, or all of them. */
    restore(scope?: string[]): void {
      if (!scope) {
        clearedScopes.clear();

        return;
      }

      for (const [key, path] of clearedScopes) {
        if (isWithin(path, scope)) clearedScopes.delete(key);
      }
    },
  };
}
