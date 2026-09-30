<script setup lang="ts">
  import {computed, ref, watch} from 'vue';
  import {t} from '@craftcms/ui';
  import PaginationControls from '@/common/components/PaginationControls.vue';
  import {TableSpacing} from '@/common/types';
  import ElementTable from '@/modules/elements/index/components/ElementTable.vue';
  import ElementCards from '@/modules/elements/components/ElementCards.vue';
  import ElementThumbs from '@/modules/elements/components/ElementThumbs.vue';
  import ElementIndexToolbar from '@/modules/elements/index/components/ElementIndexToolbar.vue';
  import type {ElementIndexView} from '@/modules/elements/index/types/model';
  import type {StructureMove} from '@/modules/elements/index/composables/useElementIndexStructure';
  import type {ElementIndexItemBehavior} from '@/modules/elements/types/item-behavior';

  const props = withDefaults(
    defineProps<{
      view: ElementIndexView;
      contained?: boolean;
      footerActive?: boolean;
      itemBehavior?: ElementIndexItemBehavior;
      showSites?: boolean;
      enableAdjustPageSize?: boolean;
      withBottomBorder?: boolean;
    }>(),
    {
      contained: false,
      footerActive: false,
      enableAdjustPageSize: false,
      withBottomBorder: true,
    }
  );
  const emit = defineEmits<{
    'site-change': [handle: string];
    dblclick: [event: MouseEvent];
  }>();
  const {
    elementIndex,
    table,
    data,
    search,
    status,
    conditions,
    mode,
    sortField,
    sortDirection,
    tableColumns,
    columnOptions,
    visibleViewModes,
    loading,
    submit,
    reorder,
    structureView,
    toggleStructure,
    selection,
  } = props.view;
  const structure = props.view.structure;
  const activeSiteHandle = computed(
    () =>
      elementIndex.sites.find((site) => site.id === elementIndex.siteId)
        ?.handle ?? null
  );

  const showFooter = computed(
    () =>
      props.footerActive ||
      props.enableAdjustPageSize ||
      elementIndex.pagination.total > 0 ||
      table.getPageCount() > 1
  );
  const liveMessage = ref('');
  watch(loading, (isLoading, wasLoading) => {
    if (isLoading) liveMessage.value = t('Loading…');
    else if (wasLoading)
      liveMessage.value = t('{total, plural, =1{# item} other{# items}}', {
        total: elementIndex.pagination.total,
      });
  });
  watch(selection.selectedIds, (ids) => {
    liveMessage.value = ids.length
      ? t('{num, plural, =1{# item selected} other{# items selected}}', {
          num: ids.length,
        })
      : t('Selection cleared');
  });

  function moveRow(id: string | number, move: StructureMove): void {
    void structure?.moveRow(id, move);
  }
</script>

<template>
  <div class="element-index" :class="{'element-index--contained': contained}">
    <div class="element-index__header">
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
        :view-modes="visibleViewModes"
        :column-options="columnOptions"
        :sort-options="elementIndex.sortOptions"
        :sites="showSites ? elementIndex.sites : undefined"
        :site-handle="showSites ? activeSiteHandle : undefined"
        @submit="submit"
        @reorder="reorder"
        @site-change="emit('site-change', $event)"
      >
        <template #actions v-if="$slots['toolbar-actions']">
          <slot name="toolbar-actions" :element-index="elementIndex" />
        </template>
        <template #search-options v-if="$slots['search-options']">
          <slot name="search-options" />
        </template>
      </ElementIndexToolbar>
    </div>
    <div class="element-index__navbar" v-if="$slots.navbar">
      <slot name="navbar" />
    </div>
    <div class="element-index__body" :aria-busy="loading ? 'true' : undefined">
      <div @dblclick="emit('dblclick', $event)">
        <ElementCards
          v-if="mode === 'cards'"
          :selection="selection.selection"
          :read-only="selection.readOnly.value"
          :data="data"
          :selectable="true"
          :loading="loading"
          :item-behavior="itemBehavior"
        />
        <ElementThumbs
          v-else-if="mode === 'thumbs'"
          :selection="selection.selection"
          :read-only="selection.readOnly.value"
          :data="data"
          :selectable="true"
          :loading="loading"
          :item-behavior="itemBehavior"
        />
        <ElementTable
          v-else
          :table="table"
          :selection="selection"
          :selectable="true"
          :loading="loading"
          :spacing="TableSpacing.Spacious"
          :item-behavior="itemBehavior"
          :with-bottom-border="withBottomBorder"
          :structure="structure !== undefined && mode === 'structure'"
          :is-row-collapsed="structureView.isCollapsed"
          :is-row-pending="structureView.isPending"
          :reorderable="structure?.reorderable ?? false"
          :can-move-row="structure?.canMoveRow"
          @toggle-structure="toggleStructure"
          @move-structure-row="moveRow"
        />
      </div>
    </div>
    <div class="element-index__footer" v-if="showFooter">
      <slot v-if="footerActive" name="footer" />
      <PaginationControls
        v-else
        :page-index="table.getState().pagination.pageIndex"
        :page-size="table.getState().pagination.pageSize"
        :page-count="table.getPageCount()"
        :paginated="
          Boolean(
            table.options.manualPagination ||
            table.options.getPaginationRowModel
          )
        "
        :from="elementIndex.pagination.from"
        :to="elementIndex.pagination.to"
        :total="elementIndex.pagination.total"
        :enable-adjust-page-size="enableAdjustPageSize"
        @page-change="table.setPageIndex"
        @page-size-change="table.setPageSize"
      />
    </div>
    <span class="sr-only" role="status" aria-live="polite">{{
      liveMessage
    }}</span>
  </div>
</template>

<style scoped lang="scss">
  .element-index--contained {
    display: flex;
    flex-direction: column;
    min-height: 0;
    overflow: hidden;

    .element-index__body {
      flex: 1 1 auto;
      min-height: 0;
      overflow: auto;
    }
    .element-index__header,
    .element-index__navbar,
    .element-index__footer {
      flex: none;
    }
  }

  .element-index {
    // Keep wide tables from stretching the grid track and trapping the sticky footer.
    min-width: 0;
  }

  .element-index__header,
  .element-index__navbar,
  .element-index__body,
  .element-index__footer {
    padding-inline: var(--cp-container-padding);
  }

  .element-index__header {
    margin-block-end: var(--c-spacing-md);
  }

  .element-index__body {
    overflow-x: auto;
  }

  .element-index__footer {
    position: sticky;
    inset-block-end: 0;
    z-index: 1;
    display: flex;
    align-items: center;
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    min-height: var(--cp-footer-height);
    background-color: var(--c-surface-default);
  }
</style>
