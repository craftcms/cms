<script setup lang="ts">
  import {attrs, t} from '@craftcms/cp';
  import {computed} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import Text from '@/common/components/Text.vue';
  import Select from '@/common/form/Select.vue';
  import Empty from '@/common/components/Empty.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import LoadingSkeleton from '@/common/components/LoadingSkeleton.vue';

  // Cards mode rows carry `{id, cardHtml}`, but the index page types its rows as
  // an open record (shared with the table view), so accept that shape here.
  type CardElement = Record<any, any>;

  const props = withDefaults(
    defineProps<{
      // The shared TanStack table instance (selection + pagination state). Cards
      // and the table view bind the same instance, so selection tracks elements
      // across a view switch.
      table: any;
      data?: Array<CardElement>;
      selectable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
      from?: number;
      to?: number;
      total?: number;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: Array<number>;
    }>(),
    {
      data: () => [],
      selectable: false,
      loading: false,
      enableAdjustPageSize: false,
      pageSizeOptions: () => [50, 100, 250],
    }
  );

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  // Roughly match the number of skeleton cards to what's currently shown (the
  // previous page stays in `data` during a refetch), clamped so a large page
  // size doesn't render an excessive placeholder.
  const skeletonCount = computed(() => {
    const size =
      props.data?.length || props.table.getState().pagination.pageSize || 6;
    return Math.min(Math.max(size, 3), 12);
  });

  // Look up the table row for a card so selection reads/writes the same model
  // the table view uses (keyed by element id via `getRowId`).
  function rowFor(id: number | string) {
    return props.table.getRow(String(id));
  }

  // craft-checkbox (Lion) fires `model-value-changed` on programmatic `.checked`
  // updates too, so only act when the event value differs from current state —
  // see the matching guard in AdminTable.
  function onToggleAllSelected(event: Event) {
    if (readOnly.value) {
      return;
    }
    const checked = (event.target as HTMLInputElement).checked;
    if (checked !== props.table.getIsAllRowsSelected()) {
      props.table.toggleAllRowsSelected(checked);
    }
  }

  function onToggleCardSelected(id: number | string, event: Event) {
    if (readOnly.value) {
      return;
    }
    const row = rowFor(id);
    const checked = (event.target as HTMLInputElement).checked;
    if (checked !== row.getIsSelected()) {
      row.toggleSelected(checked);
    }
  }

  const pageIndexProxy = computed({
    get() {
      return props.table.getState().pagination.pageIndex + 1;
    },
    set(newValue) {
      if (newValue) {
        props.table.setPageIndex(parseInt(String(newValue)) - 1);
      }
    },
  });

  const pageSizeProxy = computed({
    get() {
      return props.table.getState().pagination.pageSize;
    },
    set(newValue) {
      if (newValue) {
        props.table.setPageSize(parseInt(String(newValue)));
      }
    },
  });

  const showPagination = computed(() => props.table.getPageCount() > 1);
  const showPageSize = computed(() => props.enableAdjustPageSize);
  const showDisplayedRows = computed(
    () => props.from && props.to && props.total
  );
  const showFooter = computed(
    () => showPagination.value || showPageSize.value || showDisplayedRows.value
  );
</script>

<template>
  <div class="cp-table-wrapper">
    <div class="cp-table-header" v-if="$slots['table-header']">
      <slot name="table-header"></slot>
    </div>

    <div class="cp-table-body" :aria-busy="loading ? 'true' : undefined">
      <LoadingSkeleton v-if="loading" variant="cards" :count="skeletonCount" />
      <template v-else-if="data!.length > 0">
        <div class="card-grid-header" v-if="selectable">
          <craft-checkbox
            label-sr-only
            .checked="table.getIsAllRowsSelected()"
            .indeterminate="table.getIsSomeRowsSelected()"
            .disabled="readOnly"
            @model-value-changed="onToggleAllSelected"
          >
            <label slot="label">{{ t('Select all') }}</label>
          </craft-checkbox>
        </div>

        <ul class="card-grid">
          <li
            v-for="element in data"
            :key="element.id"
            :data-id="element.id"
            :class="{element: true, sel: rowFor(element.id)?.getIsSelected()}"
          >
            <craft-card
              v-bind="attrs(element.cardAttributes, {exclude: ['class']})"
              :active="rowFor(element.id)?.getIsSelected()"
            >
              <div slot="header">
                <div class="flex gap-2 items-center">
                  <craft-checkbox
                    v-if="selectable"
                    label-sr-only
                    .checked="rowFor(element.id)?.getIsSelected()"
                    .disabled="readOnly || !rowFor(element.id)?.getCanSelect()"
                    @model-value-changed="
                      onToggleCardSelected(element.id, $event)
                    "
                  >
                    <label slot="label">{{ t('Select') }}</label>
                  </craft-checkbox>
                  <DynamicHtmlRenderer :html="element.cardHeaderHtml" />
                </div>
              </div>
              <DynamicHtmlRenderer :html="element.cardContentHtml" />
              <DynamicHtmlRenderer
                :html="element.cardFooterHtml"
                slot="footer"
              />
            </craft-card>
          </li>
        </ul>
      </template>
      <template v-else>
        <slot name="empty">
          <Empty :label="t('No results')" icon="empty-set" />
        </slot>
      </template>
    </div>

    <div class="cp-table-footer" v-if="showFooter">
      <div>
        <Text
          v-if="showDisplayedRows"
          template="{from} – {to} of {total, plural, =1{# item} other{# items}}"
          :params="{from: from ?? 0, to: to ?? 0, total: total ?? 0}"
        />
      </div>
      <div class="flex gap-1">
        <template v-if="showPagination">
          <craft-button
            type="button"
            @click="table.previousPage()"
            :disabled="!table.getCanPreviousPage()"
            icon
            size="small"
          >
            <craft-icon
              name="chevron-left"
              :label="t('Previous page')"
            ></craft-icon>
          </craft-button>
          <div class="flex items-center gap-1 mx-2">
            Page
            <craft-input
              type="text"
              v-model="pageIndexProxy"
              maxlength="3"
              :label="t('Current page')"
              label-sr-only
              center
              size="small"
            >
            </craft-input>
            of
            {{ table.getPageCount() }}
          </div>
          <craft-button
            type="button"
            @click="table.nextPage()"
            :disabled="!table.getCanNextPage()"
            size="small"
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
        <template v-if="showPageSize">
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
</template>

<style scoped lang="scss">
  .card-grid-header {
    padding: var(--c-spacing-md);
    background-color: var(--c-color-neutral-fill-quiet);
    border-block-end: 1px solid var(--c-color-neutral-border-quiet);
  }

  .card-grid {
    padding: var(--c-spacing-md);
  }

  .cp-table-wrapper {
    overflow-y: clip;
  }

  .card-grid > li {
    position: relative;
  }

  // Selection checkbox overlays the card's top-start corner; the card itself is
  // display-only (rendered server-side without a Garnish checkbox).
  .card-select {
    position: absolute;
    inset-block-start: var(--c-spacing-sm);
    inset-inline-start: var(--c-spacing-sm);
    z-index: 1;
  }
</style>
