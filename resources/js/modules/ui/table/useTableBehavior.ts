import {computed, onBeforeUnmount} from 'vue';
import {isRecord} from '../runtime';
import {useOptionalUiRenderer} from '../renderer/context';

export function useTableBehavior(path: () => string[], keyed: () => boolean) {
  const renderer = useOptionalUiRenderer();
  const unregister = renderer?.registerBehavior(path(), {
    comparisonValue: (value) =>
      keyed() && isRecord(value) ? Object.keys(value) : undefined,
  });

  onBeforeUnmount(() => unregister?.());

  return {
    pending: computed(() => renderer?.pending.value ?? false),
    errorsCleared: computed(
      () => renderer?.childErrorsCleared(path()) ?? false
    ),
    clearErrors: () => renderer?.clearChildErrors(path()),
  };
}
