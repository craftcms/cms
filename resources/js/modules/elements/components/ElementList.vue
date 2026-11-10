<script setup lang="ts">
  import {computed} from 'vue';
  import type {ChipThumbSizeValue} from '@craftcms/ui';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementChips from '@/modules/elements/components/ElementChips.vue';
  import ElementThumbs from '@/modules/elements/components/ElementThumbs.vue';
  import {
    useSelectable,
    type Selectable,
  } from '@/common/composables/useSelectable';
  import type {ElementListViewMode} from '@/modules/elements/composables/useElementList';
  import type {ListElement} from '@/modules/elements/types/element-data';

  const props = withDefaults(
    defineProps<{
      /** The elements to draw, in display order. */
      data?: Array<ListElement>;
      viewMode: ElementListViewMode;
      /** The chips' or thumbnail tiles' thumbnail size; each body has its own default. */
      thumbSize?: ChipThumbSizeValue;
      /**
       * Selection, usually from `useElementList`. Pass one to read or change
       * what's selected; without one, the list keeps its own.
       */
      selection?: Selectable<any>;
      selectable?: boolean;
      selectAll?: boolean;
      readOnly?: boolean;
      loading?: boolean;
      sortable?: boolean;
    }>(),
    {
      data: () => [],
      selectable: false,
      selectAll: true,
      readOnly: false,
      loading: false,
      sortable: false,
    }
  );

  const ownSelection = useSelectable({
    ids: () => props.data.map((element) => element.id),
  });
  const activeSelection = computed(() => props.selection ?? ownSelection);

  // Thumbs link any tile with a `url`. In a relational field, clicking an
  // element selects or edits it, so the tiles shouldn't navigate away.
  const thumbsData = computed(() =>
    props.data.map(({url: _url, ...element}) => element)
  );

  const emit = defineEmits<{
    (event: 'edit', element: ListElement): void;
    (event: 'reorder', startIndex: number, finishIndex: number): void;
  }>();

  type ElementSlotProps = {element: ListElement; index: number};

  defineSlots<{
    /** Controls for each element: a chip's trailing actions, or a card's or tile's action area. */
    actions?(props: ElementSlotProps): unknown;
    /** Before each chip's label. List views only. */
    prefix?(props: ElementSlotProps): unknown;
    /** After each chip. List views only. */
    append?(props: ElementSlotProps): unknown;
    /** Shown in card and thumbnail views when there are no elements. */
    empty?(): unknown;
  }>();

  /** Cards and thumbs pass their own element shape; slots get the one we were given. */
  function slotProps(index: number): ElementSlotProps {
    return {element: props.data[index]!, index};
  }

  /** Cards and thumbs report their own element shape; edit the one we were given. */
  function edit(id: string | number): void {
    const element = props.data.find((candidate) => candidate.id === id);

    if (element) {
      emit('edit', element);
    }
  }
</script>

<template>
  <ElementCards
    v-if="viewMode === 'cards' || viewMode === 'cards-grid'"
    :data="data"
    :selection="activeSelection"
    :selectable="selectable"
    :select-all="selectAll"
    :read-only="readOnly"
    :loading="loading"
    :single-column="viewMode === 'cards'"
    :sortable="sortable"
    @edit="(element) => edit(element.id)"
    @reorder="
      (startIndex, finishIndex) => $emit('reorder', startIndex, finishIndex)
    "
  >
    <template #actions="{index}"
      ><slot name="actions" v-bind="slotProps(index)"></slot
    ></template>
    <template #empty><slot name="empty"></slot></template>
  </ElementCards>

  <ElementThumbs
    v-else-if="viewMode === 'thumbs'"
    :data="thumbsData"
    :selection="activeSelection"
    :selectable="selectable"
    :select-all="selectAll"
    :read-only="readOnly"
    :loading="loading"
    :sortable="sortable"
    :thumb-size="thumbSize"
    :fallback-color="null"
    @edit="(element) => edit(element.id)"
    @reorder="
      (startIndex, finishIndex) => $emit('reorder', startIndex, finishIndex)
    "
  >
    <template #actions="{index}"
      ><slot name="actions" v-bind="slotProps(index)"></slot
    ></template>
    <template #empty><slot name="empty"></slot></template>
  </ElementThumbs>

  <ElementChips
    v-else
    :data="data"
    :selection="activeSelection"
    :selectable="selectable"
    :read-only="readOnly"
    :inline="viewMode === 'list-inline'"
    :thumb-size="thumbSize"
    :sortable="sortable"
    @edit="$emit('edit', $event)"
    @reorder="
      (startIndex, finishIndex) => $emit('reorder', startIndex, finishIndex)
    "
  >
    <template #prefix="{element, index}"
      ><slot
        name="prefix"
        :element="element as ListElement"
        :index="index"
      ></slot
    ></template>
    <template #append="{element, index}"
      ><slot
        name="append"
        :element="element as ListElement"
        :index="index"
      ></slot
    ></template>
    <template #suffix="{element, index}"
      ><slot
        name="actions"
        :element="element as ListElement"
        :index="index"
      ></slot
    ></template>
  </ElementChips>
</template>
