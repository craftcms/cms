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
  const cards = computed(() =>
    markInvalidElements(props.control.cards, invalidElementIds.value)
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
