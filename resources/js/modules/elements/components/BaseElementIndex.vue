<script setup lang="ts">
  import {computed, ref, watch} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import type {Table} from '@tanstack/vue-table';
  import {ButtonVariant, t} from '@craftcms/ui';
  import Text from '@/common/components/Text.vue';
  import Select from '@/common/form/Select.vue';
  import BulkActionsBar from '@/modules/elements/components/BulkActionsBar.vue';
  import {useElementIndexSelection} from '@/modules/elements/composables/useElementIndexSelection';
  import type {BulkActionItem} from '@/modules/elements/types/actions';

  const props = withDefaults(
    defineProps<{
      table: Table<any>;
      selectable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
      from?: number;
      to?: number;
      total?: number;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: Array<number>;
      actions?: Array<BulkActionItem> | null;
      elementType?: string;
      source?: string | null;
      context?: string;
    }>(),
    {
      selectable: false,
      loading: false,
      enableAdjustPageSize: false,
      pageSizeOptions: () => [50, 100, 250],
      actions: () => [],
      source: null,
      context: 'index',
    }
  );

  const emit = defineEmits<{'action-performed': []}>();

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  const {
    selectedIds,
    hasSelection,
    showBulkActions,
    bulkActionsActive,
    clearSelection,
  } = useElementIndexSelection(() => props.table, {
    selectable: () => props.selectable,
    readOnly,
    actions: () => props.actions,
  });

  function onActionPerformed() {
    clearSelection();
    emit('action-performed');
  }

  // --- Pagination footer proxies (moved verbatim from AdminTable) ---
  const pageIndexProxy = computed({
    get: () => props.table.getState().pagination.pageIndex + 1,
    set: (v) => {
      if (v) props.table.setPageIndex(parseInt(String(v)) - 1);
    },
  });
  const pageSizeProxy = computed({
    get: () => props.table.getState().pagination.pageSize,
    set: (v) => {
      if (v) props.table.setPageSize(parseInt(String(v)));
    },
  });
  const showPagination = computed(() => props.table.getPageCount() > 1);
  const showPageSize = computed(() => props.enableAdjustPageSize);
  const showDisplayedRows = computed(
    () => props.from && props.to && props.total
  );
  const showFooter = computed(
    () =>
      showPagination.value ||
      showPageSize.value ||
      showDisplayedRows.value ||
      (showBulkActions.value && hasSelection.value)
  );

  // --- ARIA live region ---
  const liveMessage = ref('');
  watch(
    () => props.loading,
    (isLoading, was) => {
      if (isLoading) liveMessage.value = t('Loading…');
      else if (was && props.total != null)
        liveMessage.value = t('{total, plural, =1{# item} other{# items}}', {
          total: props.total ?? 0,
        });
    }
  );
  watch(selectedIds, (ids) => {
    liveMessage.value = ids.length
      ? t('{num, plural, =1{# item selected} other{# items selected}}', {
          num: ids.length,
        })
      : t('Selection cleared');
  });
</script>

<template>
  <div class="element-index">
    <div class="element-index__header" v-if="$slots.header">
      <slot name="header"></slot>
    </div>

    <div class="element-index__navbar" v-if="$slots.navbar">
      <slot name="navbar"></slot>
    </div>

    <div class="element-index__body" :aria-busy="loading ? 'true' : undefined">
      <slot name="body"></slot>
    </div>

    <div class="element-index__footer" ref="indexFooter" v-if="showFooter">
      <BulkActionsBar
        v-if="showBulkActions && hasSelection"
        :selected-ids="selectedIds"
        :actions="actions"
        :element-type="elementType ?? ''"
        :source="source"
        :context="context"
        @performed="onActionPerformed"
        @clear="clearSelection"
      />
      <div
        v-show="!(showBulkActions && hasSelection)"
        class="flex justify-between items-center w-full"
      >
        <div>
          <Text
            v-if="showDisplayedRows"
            template="{from} – {to} of {total, plural, =1{# item} other{# items}}"
            :params="{from: from ?? 0, to: to ?? 0, total: total ?? 0}"
          />
        </div>
        <div class="flex gap-1">
          <template v-if="showPagination && !bulkActionsActive">
            <craft-button
              type="button"
              @click="table.previousPage()"
              :disabled="!table.getCanPreviousPage()"
              :variant="ButtonVariant.Plain"
              icon
              size="small"
            >
              <craft-icon
                name="chevron-left"
                :label="t('Previous page')"
              ></craft-icon>
            </craft-button>
            <div class="flex items-center gap-1 mx-2">
              {{ t('Page') }}
              <craft-input
                type="text"
                v-model="pageIndexProxy"
                maxlength="3"
                :label="t('Current page')"
                label-sr-only
                center
                size="small"
                style="width: 4ch"
              />
              {{ t('of') }}
              {{ table.getPageCount() }}
            </div>
            <craft-button
              type="button"
              @click="table.nextPage()"
              :disabled="!table.getCanNextPage()"
              size="small"
              :variant="ButtonVariant.Plain"
              icon
            >
              <craft-icon
                name="chevron-right"
                :label="t('Next page')"
              ></craft-icon>
            </craft-button>
          </template>
        </div>
        <div class="flex gap-2 items-center">
          <template v-if="showPageSize && !bulkActionsActive">
            {{ t('Items per page:') }}
            <Select
              small
              :options="pageSizeOptions!"
              v-model="pageSizeProxy"
              class="w-auto"
            />
          </template>
        </div>
      </div>
    </div>

    <span class="sr-only" role="status" aria-live="polite">{{
      liveMessage
    }}</span>
  </div>
</template>

<style scoped lang="scss">
  .element-index {
    overflow-y: clip;
  }

  .element-index__header,
  .element-index__navbar,
  .element-index__footer {
    background-color: var(--c-color-neutral-fill-quiet);
    padding: var(--c-spacing-md);
  }

  .element-index__body {
    overflow-x: auto;
  }

  .element-index__footer {
    position: sticky;
    bottom: 0;
    z-index: var(--c-z-sticky);
    display: flex;
    align-items: center;
    border-block-start: 1px solid var(--c-color-neutral-border-quiet);
    min-height: calc(50rem / 16);
  }
</style>
