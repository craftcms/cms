import {
  computed,
  onUnmounted,
  shallowRef,
  type ComputedRef,
  type Ref,
} from 'vue';
import type {
  ElementInfo,
  ElementSelectorController,
  ElementSelectorState,
} from '@craftcms/ui';

export interface UseElementSelectorController {
  state: Ref<ElementSelectorState>;
  open: ComputedRef<boolean>;
  busy: ComputedRef<boolean>;
  loading: ComputedRef<boolean>;
  canSubmit: ComputedRef<boolean>;
  indexBody: ComputedRef<ElementSelectorState['indexBody']>;
  disabledElementIds: ComputedRef<number[]>;
  selection: ComputedRef<readonly ElementInfo[]>;
  submit: () => void;
  cancel: () => void;
}

/**
 * Mirrors an {@link ElementSelectorController}'s state into Vue reactivity.
 *
 * The core is a plain emitter with no Vue import — that is what lets the web
 * component bind to the same instance — so the bridge lives here. Each `change`
 * carries a fresh frozen snapshot, which is why a `shallowRef` is enough and
 * deliberately preferable: nothing wraps the controller's own objects in a
 * reactive proxy, so the value a Vue template reads is the same object the web
 * component reads.
 *
 * Subscribes immediately rather than in `onMounted`, so a controller that is
 * already open when the component is created renders correctly on first paint.
 */
export function useElementSelectorController(
  controller: ElementSelectorController
): UseElementSelectorController {
  const state = shallowRef<ElementSelectorState>(controller.state);
  const off = controller.on('change', (next) => (state.value = next));

  onUnmounted(off);

  return {
    state,
    open: computed(() => state.value.open),
    busy: computed(() => state.value.busy),
    loading: computed(() => state.value.loading),
    canSubmit: computed(() => state.value.canSubmit),
    indexBody: computed(() => state.value.indexBody),
    disabledElementIds: computed(() => [...state.value.disabledElementIds]),
    selection: computed(() => state.value.selection),
    submit: () => void controller.submit(),
    cancel: () => controller.cancel(),
  };
}
