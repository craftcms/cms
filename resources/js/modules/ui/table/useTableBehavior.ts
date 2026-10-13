import {computed, inject, onBeforeUnmount} from 'vue';
import {UiControlBehaviors, UiErrors, UiPending, isRecord} from '../runtime';

export function useTableBehavior(path: () => string[], keyed: () => boolean) {
  const register = inject(UiControlBehaviors, undefined);
  const pending = inject(UiPending, undefined);
  const errors = inject(UiErrors, undefined);
  const unregister = register?.(path(), {
    comparisonValue: (value) =>
      keyed() && isRecord(value) ? Object.keys(value) : undefined,
  });

  onBeforeUnmount(() => unregister?.());

  return {
    pending: computed(() => pending?.value ?? false),
    errorsCleared: computed(() => errors?.childrenCleared(path()) ?? false),
    clearErrors: () => errors?.clearChildren(path()),
  };
}
