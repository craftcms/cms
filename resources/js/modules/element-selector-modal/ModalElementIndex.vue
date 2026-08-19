<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {watch} from 'vue';
  import ElementSources from '@/modules/elements/ElementSources.vue';
  import BaseElementIndex from '@/modules/elements/components/BaseElementIndex.vue';
  import DataTable from '@/modules/elements/components/DataTable.vue';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementThumbs from '@/modules/elements/components/ElementThumbs.vue';
  import ElementIndexToolbar from '@/modules/elements/components/ElementIndexToolbar.vue';
  import {TableSpacing} from '@/common/types';
  import type {ContentIndexData} from '@/modules/elements/composables/useContentIndexData';
  import {
    useModalElementIndex,
    type SelectedElement,
  } from './useModalElementIndex';

  const props = defineProps<{
    url: string;
    initial: ContentIndexData;
    params: Record<string, unknown>;
    disabledElementIds?: number[];
  }>();

  const emit = defineEmits<{
    (event: 'selection-change', elements: SelectedElement[]): void;
    (event: 'choose', elements: SelectedElement[]): void;
  }>();

  const index = useModalElementIndex({
    url: props.url,
    initial: props.initial,
    params: props.params,
    disabledElementIds: () => props.disabledElementIds ?? [],
  });

  const {
    elementIndex,
    table,
    viewState,
    conditions,
    search,
    status,
    columnOptions,
    tableColumns,
    reorder,
    sortField,
    sortDirection,
    mode,
    loading,
    selectedElements,
    hasSelection,
    clearSelection,
  } = index;

  // The modal's Select button and its enabled state live outside Vue, so the
  // selection is pushed out rather than read in.
  watch(selectedElements, (elements) => emit('selection-change', elements), {
    deep: true,
  });

  /**
   * `ElementSources` renders real links, because on a page picking a source IS a
   * navigation. In a modal it must not be — following one would replace the page
   * behind the modal — so the click is intercepted and turned into a load.
   */
  function onSourceClick(event: MouseEvent): void {
    const link = (event.target as HTMLElement | null)?.closest?.('a[href]');

    if (!link) {
      return;
    }

    event.preventDefault();

    const source = new URL(
      link.getAttribute('href')!,
      window.location.origin
    ).searchParams.get('source');

    if (source) {
      clearSelection();
      index.query.value = {...index.query.value, source};
      void index.load(index.query.value);
    }
  }

  defineExpose({selectedElements, hasSelection, clearSelection});
</script>

<template>
  <div class="modal-element-index flex h-full">
    <nav
      v-if="elementIndex.sources.length > 1"
      class="modal-element-index__sidebar sidebar"
      :aria-label="t('Sources')"
      @click="onSourceClick"
    >
      <ElementSources
        :sources="elementIndex.sources"
        :route="{url: () => ''}"
        :active-source="elementIndex.source?.key"
        :view-mode="viewState.mode !== 'table' ? viewState.mode : null"
      />
    </nav>

    <div class="modal-element-index__main content flex-grow">
      <BaseElementIndex
        :table="table"
        :selectable="true"
        :loading="loading"
        :from="elementIndex.pagination.from"
        :to="elementIndex.pagination.to"
        :total="elementIndex.pagination.total"
        :element-type="elementIndex.elementType"
        :source="elementIndex.source?.key"
        context="modal"
      >
        <template #header>
          <ElementIndexToolbar
            v-model:search="search"
            v-model:status="status"
            v-model:conditions="conditions"
            v-model:mode="mode"
            v-model:sort-field="sortField"
            v-model:sort-direction="sortDirection"
            v-model:table-columns="tableColumns"
            :processing="loading"
            :status-options="elementIndex.statusOptions"
            :view-modes="elementIndex.viewModes"
            :column-options="columnOptions"
            :sort-options="elementIndex.sortOptions"
            @reorder="reorder"
          />
        </template>
        <template #body>
          <!-- Double-click chooses, matching the legacy modal's doubletap. -->
          <div @dblclick="emit('choose', selectedElements)">
            <ElementCards
              v-if="mode === 'cards'"
              :table="table"
              :data="elementIndex.data"
              :selectable="true"
              :loading="loading"
            />
            <ElementThumbs
              v-else-if="mode === 'thumbs'"
              :table="table"
              :data="elementIndex.data"
              :selectable="true"
              :loading="loading"
            />
            <DataTable
              v-else
              :table="table"
              :selectable="true"
              :loading="loading"
              :spacing="TableSpacing.Spacious"
            />
          </div>
        </template>
      </BaseElementIndex>
    </div>
  </div>
</template>
