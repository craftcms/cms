<script setup lang="ts">
  import {attrs, t} from '@craftcms/cp';
  import {computed, ref} from 'vue';
  import {usePage} from '@inertiajs/vue3';
  import Empty from '@/common/components/Empty.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {useElementIndexSelection} from '@/modules/elements/composables/useElementIndexSelection';

  type CardElement = Record<any, any>;

  const props = withDefaults(
    defineProps<{
      table: any;
      data?: Array<CardElement>;
      selectable?: boolean;
      readOnly?: boolean;
      loading?: boolean;
    }>(),
    {data: () => [], selectable: false, loading: false},
  );

  const page = usePage<{readOnly: boolean}>();
  const readOnly = computed(() => props.readOnly ?? page.props.readOnly);

  const {onToggleAllSelected, selectRow} = useElementIndexSelection(
    () => props.table,
    {selectable: () => props.selectable, readOnly, actions: () => []},
  );

  function rowFor(id: number | string) {
    return props.table.getRow(String(id));
  }

  const pendingShiftKey = ref(false);
  function rememberShift(event: MouseEvent) {
    pendingShiftKey.value = event.shiftKey;
  }
</script>

<template>
  <div class="grid place-items-center min-h-50" v-if="loading">
    <craft-spinner></craft-spinner>
  </div>
  <template v-else-if="data!.length > 0">
    <div class="card-grid-header" v-if="selectable">
      <craft-checkbox
        label-sr-only
        .checked="table.getIsAllRowsSelected()"
        .indeterminate="table.getIsSomeRowsSelected()"
        .disabled="readOnly"
        @model-value-changed="onToggleAllSelected(($event.target as HTMLInputElement).checked)"
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
                @click="rememberShift($event)"
                @model-value-changed="
                  selectRow(rowFor(element.id), {
                    checked: ($event.target as HTMLInputElement).checked,
                    shiftKey: pendingShiftKey,
                  })
                "
              >
                <label slot="label">{{ t('Select') }}</label>
              </craft-checkbox>
              <DynamicHtmlRenderer :html="element.cardHeaderHtml" />
            </div>
          </div>
          <DynamicHtmlRenderer :html="element.cardContentHtml" />
          <DynamicHtmlRenderer :html="element.cardFooterHtml" slot="footer" />
        </craft-card>
      </li>
    </ul>
  </template>
  <template v-else>
    <slot name="empty">
      <Empty :label="t('No results')" icon="empty-set" />
    </slot>
  </template>
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

  .card-grid > li {
    position: relative;
  }
</style>
