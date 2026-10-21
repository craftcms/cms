<script setup lang="ts">
  import {useEventListener} from '@vueuse/core';
  import {computed, inject, ref, shallowRef, watch} from 'vue';
  import {useSelectable} from '@/common/composables/useSelectable';
  import {NestedOwnerEditorKey} from '@/modules/elements/nested-owner';
  import NestedElementsCards from './NestedElementsCards.vue';
  import {useNestedElementsControl} from './nested-elements-context';
  import {
    markInvalidElements,
    type NestedElementsProps,
  } from './nested-elements';
  import {
    useNestedElementOperations,
    type NestedElementOperations,
  } from './useNestedElementOperations';

  const props = defineProps<{
    control: NestedElementsProps;
    editable: boolean;
  }>();

  const owner = inject(NestedOwnerEditorKey, null);
  const controlContext = useNestedElementsControl();
  const container = ref<HTMLElement>();
  const invalidElementIds = ref<number[]>([]);
  const manager = computed(() => props.control.manager);
  /**
   * A reorder being saved, shown until it's done. Payloads that arrive in the
   * meantime are put in its order too, as preparing the owner re-renders it
   * before the reorder is saved; any card it doesn't name goes last.
   */
  const previewedOrder = shallowRef<number[] | null>(null);
  const orderedCards = computed(() => {
    const order = previewedOrder.value;

    if (!order) {
      return props.control.cards;
    }

    const position = (id: number): number => {
      const index = order.indexOf(id);
      return index < 0 ? order.length : index;
    };

    return [...props.control.cards].sort(
      (a, b) => position(a.id) - position(b.id)
    );
  });
  const cards = computed(() =>
    markInvalidElements(orderedCards.value, invalidElementIds.value)
  );
  const editable = computed(() => props.editable);
  const offset = shallowRef(0);
  const count = computed(() => cards.value.length);
  const canReorder = computed(
    () => props.editable && Boolean(manager.value?.sortable)
  );
  let operations!: NestedElementOperations;
  const selection = useSelectable<number>({
    ids: () => cards.value.map((element) => element.id),
    enabled: editable,
    readOnly: () => !props.editable || operations.busy.value,
  });
  operations = useNestedElementOperations({
    elements: cards,
    previewOrder: (ids) => (previewedOrder.value = ids),
    offset,
    canReorder,
    selection,
    count,
    refresh: async () => {},
    manager,
    owner,
    editable,
    controlPath: controlContext.path,
    container,
    markModified: controlContext.markModified,
  });
  watch(cards, () => selection.prune());

  useEventListener(window, 'craft:nested-validation', (event: Event) => {
    if (
      event.target instanceof Element &&
      event.target.contains(container.value ?? null)
    ) {
      invalidElementIds.value = (
        event as CustomEvent<{ids: number[]}>
      ).detail.ids;
    }
  });
</script>

<template>
  <div ref="container" tabindex="-1" @focus.self="operations.onContainerFocus">
    <div v-if="operations.error.value" role="alert">
      {{ operations.error.value }}
    </div>
    <NestedElementsCards
      :cards="cards"
      :manager="manager"
      :selection="selection"
      :single-column="control.viewMode === 'cards'"
      :editable="editable"
      :operations="operations"
      :quick-edit="operations.quickEdit"
      :edit-opener="container"
    />
  </div>
</template>
