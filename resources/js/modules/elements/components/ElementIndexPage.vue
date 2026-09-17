<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import BaseElementIndex from '@/modules/elements/components/BaseElementIndex.vue';
  import DataTable from '@/modules/elements/components/DataTable.vue';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementIndexToolbar from '@/modules/elements/components/ElementIndexToolbar.vue';
  import {useElementIndexPage} from '@/modules/elements/composables/useElementIndexPage';
  import {useElementQuickEdit} from '@/modules/elements/composables/useElementQuickEdit';
  import type {ElementIndexRoute} from '@/modules/elements/composables/useElementIndexVisits';
  import {TableSpacing} from '@/common/types';
  import ElementThumbs from '@/modules/elements/components/ElementThumbs.vue';
  import {ref} from 'vue';
  import CustomizeSourcesModal from '@/modules/elements/components/customize-sources/CustomizeSourcesModal.vue';
  import {useNavItemAction} from '@/common/composables/useNavItemActions';
  import CpContainer from '@/common/components/CpContainer.vue';

  const props = defineProps<{
    /** The page's index route — the one per-page piece of the pipeline. */
    route: ElementIndexRoute;
    /** Overrides the pinned first column (defaults to the element's title). */
    pinnedColumn?: {key: string; label: string};
    /**
     * Offers Customize Sources from the nav item this index lives under. Opt-in
     * rather than automatic, since not every index that uses this page should
     * offer it.
     */
    customizableSources?: boolean;
  }>();

  const page = useElementIndexPage({
    route: props.route,
    pinnedColumn: props.pinnedColumn,
  });

  // Double-click an element to edit it in a slideout.
  const quickEdit = useElementQuickEdit();

  const {
    elementIndex,
    elementTable,
    viewState,
    conditions,
    filters,
    columnOptions,
    tableColumns,
    reorder,
    sortField,
    sortDirection,
    mode,
    loading,
    visibleViewModes,
    onActionPerformed,
  } = page;

  const customizeSourcesActive = ref(false);

  // The sources are edited from the nav now rather than from a sidebar on the
  // page, so the page lends the nav the gear that opens the editor.
  if (props.customizableSources) {
    useNavItemAction(() => props.route.url(), {
      label: t('Customize sources'),
      icon: 'gear',
      onClick: () => (customizeSourcesActive.value = true),
    });
  }
</script>

<template>
  <LayoutSlot v-if="$slots.actions" name="content-actions">
    <!-- Type-specific page actions (e.g. a New Entry or Upload button). -->
    <slot name="actions" :element-index="elementIndex" />
  </LayoutSlot>

  <BaseElementIndex
    :table="elementTable"
    :selectable="true"
    :loading="loading"
    :from="elementIndex.pagination.from"
    :to="elementIndex.pagination.to"
    :total="elementIndex.pagination.total"
    :enable-adjust-page-size="true"
    :actions="elementIndex.actions"
    :element-type="elementIndex.elementType"
    :source="elementIndex.source?.key"
    :context="elementIndex.context"
    @action-performed="onActionPerformed"
  >
    <template #header>
      <ElementIndexToolbar
        v-model:search="filters.form.search"
        v-model:status="filters.form.status"
        v-model:conditions="conditions"
        :processing="filters.form.processing"
        :status-options="elementIndex.statusOptions"
        :view-modes="visibleViewModes"
        :column-options="columnOptions"
        :sort-options="elementIndex.sortOptions"
        v-model:mode="mode"
        v-model:sort-field="sortField"
        v-model:sort-direction="sortDirection"
        v-model:table-columns="tableColumns"
        @submit="filters.submit"
        @reorder="reorder"
      >
        <template #actions>
          <!-- Type-specific actions that belong with the list itself, such
              as the entries index's New Entry button. -->
          <slot name="toolbar-actions" :element-index="elementIndex" />
        </template>
      </ElementIndexToolbar>
    </template>
    <template #navbar>
      <slot name="navbar"></slot>
    </template>
    <template #body="{selection}">
      <!-- Delegated so every view mode gets double-click-to-edit without
            any of them knowing about it, matching Craft 5's element container
            listener. -->
      <div @dblclick="quickEdit.onDblClick">
        <ElementCards
          v-if="mode === 'cards'"
          :selection="selection"
          :data="elementIndex.data"
          :selectable="true"
          :loading="loading"
        />
        <ElementThumbs
          v-else-if="mode === 'thumbs'"
          :selection="selection"
          :data="elementIndex.data"
          :selectable="true"
          :loading="loading"
        />
        <DataTable
          v-else
          :table="elementTable"
          :selectable="true"
          :loading="loading"
          :full-width="true"
          :spacing="TableSpacing.Spacious"
        />
      </div>
    </template>
  </BaseElementIndex>

  <CustomizeSourcesModal
    :is-active="customizeSourcesActive"
    :element-type="elementIndex.elementType"
    :page="elementIndex.page"
    :source-key="elementIndex.source?.key"
    @close="customizeSourcesActive = false"
  />
</template>

<style scoped lang="scss">
  // The pane is a grid item in the content layout; with the default
  // min-width: auto it grows to fit a wide table and overflows the container.
  // Letting it shrink lets the element-index body's `overflow-x: auto` scroll
  // the table instead.
  craft-pane {
    min-width: 0;
  }
</style>
