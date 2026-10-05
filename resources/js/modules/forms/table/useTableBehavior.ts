import {computed, inject, onBeforeUnmount} from 'vue';
import {
  FormControlBehaviors,
  FormErrors,
  FormPending,
  isRecord,
} from '../runtime';

export function useTableBehavior(path: () => string[], keyed: () => boolean) {
  const register = inject(FormControlBehaviors, undefined);
  const pending = inject(FormPending, undefined);
  const errors = inject(FormErrors, undefined);
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
