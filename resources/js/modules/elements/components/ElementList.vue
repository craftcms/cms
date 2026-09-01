<script setup lang="ts">
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementChips from '@/modules/elements/components/ElementChips.vue';
  import ElementThumbs from '@/modules/elements/components/ElementThumbs.vue';
  import type {Selectable} from '@/common/composables/useSelectable';
  import type {ElementListViewMode} from '@/modules/elements/composables/useElementList';

  /**
   * The element data a list renders. Which parts are needed depends on the view
   * mode — card modes want the `card*` parts, thumbs wants `thumbHtml` — and the
   * server only renders the ones the active mode uses.
   */
  interface ListElement {
    id: number;
    label?: string;
    siteId?: number | string | null;
    status?: {fill: string; label: string; draft: boolean} | null;
    url?: string | null;
    cardAttributes?: Record<
      string,
      string | number | boolean | null | undefined
    >;
    cardHeaderHtml?: string;
    cardThumbHtml?: string;
    thumbAlignment?: string;
    cardContentHtml?: string;
    cardFooterHtml?: string;
    thumbHtml?: string;
  }

  withDefaults(
    defineProps<{
      /** The elements to draw, in display order. */
      data?: Array<ListElement>;
      viewMode: ElementListViewMode;
      /** Selection, usually from `useElementList`. */
      selection: Selectable<any>;
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

  defineEmits<{
    (event: 'edit', element: ListElement): void;
    (event: 'reorder', startIndex: number, finishIndex: number): void;
  }>();
</script>

<template>
  <ElementCards
    v-if="viewMode === 'cards' || viewMode === 'cards-grid'"
    :data="data"
    :selection="selection"
    :selectable="selectable"
    :select-all="selectAll"
    :read-only="readOnly"
    :loading="loading"
    :single-column="viewMode === 'cards'"
    :sortable="sortable"
    @reorder="
      (startIndex, finishIndex) => $emit('reorder', startIndex, finishIndex)
    "
  >
    <template #actions="slotProps"
      ><slot name="actions" v-bind="slotProps"></slot
    ></template>
    <template #empty><slot name="empty"></slot></template>
  </ElementCards>

  <ElementThumbs
    v-else-if="viewMode === 'thumbs'"
    :data="data"
    :selection="selection"
    :selectable="selectable"
    :select-all="selectAll"
    :read-only="readOnly"
    :loading="loading"
    :sortable="sortable"
    @reorder="
      (startIndex, finishIndex) => $emit('reorder', startIndex, finishIndex)
    "
  >
    <template #actions="slotProps"
      ><slot name="actions" v-bind="slotProps"></slot
    ></template>
    <template #empty><slot name="empty"></slot></template>
  </ElementThumbs>

  <ElementChips
    v-else
    :data="data"
    :selection="selection"
    :selectable="selectable"
    :read-only="readOnly"
    :inline="viewMode === 'list-inline'"
    :sortable="sortable"
    @edit="$emit('edit', $event)"
    @reorder="
      (startIndex, finishIndex) => $emit('reorder', startIndex, finishIndex)
    "
  >
    <template #prefix="slotProps"
      ><slot name="prefix" v-bind="slotProps"></slot
    ></template>
    <template #append="slotProps"
      ><slot name="append" v-bind="slotProps"></slot
    ></template>
    <template #suffix="slotProps"
      ><slot name="actions" v-bind="slotProps"></slot
    ></template>
  </ElementChips>
</template>
