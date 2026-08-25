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
    /** Bare action path; `actionClient` expands it against the CP trigger. */
    action: string;
    initial: ContentIndexData;
    params: Record<string, unknown>;
    disabledElementIds?: number[];
  }>();

  const emit = defineEmits<{
    (event: 'selection-change', elements: SelectedElement[]): void;
    (event: 'choose', elements: SelectedElement[]): void;
  }>();

  const index = useModalElementIndex({
    action: props.action,
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

  // Switching source replaces the whole result set, so anything picked from
  // the old one is no longer on screen to un-pick.
  watch(
    () => elementIndex.source?.key,
    () => clearSelection()
  );

  defineExpose({selectedElements, hasSelection, clearSelection});
</script>

<template>
  <div class="modal-element-index">
    <nav
      v-if="elementIndex.sources.length > 1"
      class="modal-element-index__sidebar"
      :aria-label="t('Sources')"
    >
      <!--
        The visitor is what keeps a source click inside the modal. Without it
        `ElementSources` runs an Inertia visit, which navigates the page behind
        it — the route below exists only so the nav items render as real links.
      -->
      <ElementSources
        :sources="elementIndex.sources"
        :route="{url: () => ''}"
        :index-visitor="index.visitor"
        :active-source="elementIndex.source?.key"
        :view-mode="viewState.mode !== 'table' ? viewState.mode : null"
      />
    </nav>

    <div class="modal-element-index__main">
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

<style lang="scss" scoped>
  .modal-element-index {
    display: grid;
    grid-template-columns: clamp(12rem, 15%, 14rem) 1fr;
  }

  .modal-element-index__sidebar {
  }

  .modal-element-index__main {
  }
</style>
