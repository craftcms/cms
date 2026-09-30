<script setup lang="ts">
  import {computed} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import Text from '@/common/components/Text.vue';
  import Select from '@/common/form/Select.vue';

  const props = withDefaults(
    defineProps<{
      pageIndex: number;
      pageSize: number;
      pageCount: number;
      paginated?: boolean;
      from?: number;
      to?: number;
      total?: number;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: number[];
    }>(),
    {
      paginated: true,
      enableAdjustPageSize: false,
      pageSizeOptions: () => [50, 100, 250],
    }
  );
  const emit = defineEmits<{
    'page-change': [index: number];
    'page-size-change': [size: number];
  }>();
  const pageIndexProxy = computed({
    get: () => props.pageIndex + 1,
    set: (value) => {
      const index = Number(value) - 1;
      if (Number.isInteger(index) && index >= 0 && index < props.pageCount)
        emit('page-change', index);
    },
  });
  const pageSizeProxy = computed({
    get: () => props.pageSize,
    set: (value) => emit('page-size-change', Number(value)),
  });
  const showPagination = computed(() => props.paginated && props.pageCount > 1);
  const showDisplayedRows = computed(
    () => props.total != null && props.from != null && props.to != null
  );
  const pageSizeLabel = t('Items per page');
</script>

<template>
  <div class="flex justify-between items-center w-full">
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
          @click="emit('page-change', pageIndex - 1)"
          .disabled="pageIndex <= 0"
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
          />
          {{ t('of') }}
          {{ pageCount }}
        </div>
        <craft-button
          type="button"
          @click="emit('page-change', pageIndex + 1)"
          .disabled="pageIndex >= pageCount - 1"
          size="small"
          :variant="ButtonVariant.Plain"
          icon
        >
          <craft-icon name="chevron-right" :label="t('Next page')"></craft-icon>
        </craft-button>
      </template>
    </div>
    <div class="flex gap-2 items-center">
      <template v-if="enableAdjustPageSize">
        <span aria-hidden="true">{{ pageSizeLabel }}</span>
        <Select
          small
          :label="pageSizeLabel"
          label-sr-only
          :options="pageSizeOptions!"
          v-model="pageSizeProxy"
          class="w-auto"
        />
      </template>
    </div>
  </div>
</template>
