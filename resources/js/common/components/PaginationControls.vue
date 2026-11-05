<script setup lang="ts">
  import {computed, onBeforeUnmount, shallowRef, watch} from 'vue';
  import {ButtonVariant, t} from '@craftcms/ui';
  import Text from '@/common/components/Text.vue';
  import Select from '@/common/form/Select.vue';

  const props = withDefaults(
    defineProps<{
      pageIndex: number;
      pageSize: number;
      pageCount: number;
      paginated?: boolean;
      from?: number | null;
      to?: number | null;
      total?: number;
      enableAdjustPageSize?: boolean;
      pageSizeOptions?: number[];
      disabled?: boolean;
      /** Leaves out the item count and page size, for the page controls alone. */
      controlsOnly?: boolean;
      /** What one item is called in the count, such as “entry”. */
      itemLabel?: string;
      /** What several items are called in the count, such as “entries”. */
      itemsLabel?: string;
      /** The page size menu’s label, such as “Entries per Page”. */
      pageSizeLabel?: string;
    }>(),
    {
      paginated: true,
      enableAdjustPageSize: false,
      pageSizeOptions: () => [50, 100, 250],
      disabled: false,
      controlsOnly: false,
      itemLabel: () => t('item'),
      itemsLabel: () => t('items'),
      pageSizeLabel: () => t('Items per Page'),
    }
  );
  const emit = defineEmits<{
    'page-change': [index: number];
    'page-size-change': [size: number];
    /** A new page number is being typed, before it’s committed. */
    'page-input': [];
  }>();

  /** How long typing pauses before a typed page number is committed. */
  const PAGE_INPUT_DELAY = 500;

  // What the page input shows: the typed number while it's being entered, and
  // the current page otherwise. It stays editable while a page loads.
  const pageDraft = shallowRef(String(props.pageIndex + 1));
  let commitTimer: ReturnType<typeof setTimeout> | null = null;

  watch(
    () => props.pageIndex,
    (index) => {
      if (commitTimer === null) {
        pageDraft.value = String(index + 1);
      }
    }
  );

  function onPageInput(event: Event): void {
    pageDraft.value = (event.target as HTMLInputElement).value;
    emit('page-input');

    if (commitTimer !== null) {
      clearTimeout(commitTimer);
    }
    commitTimer = setTimeout(commitPage, PAGE_INPUT_DELAY);
  }

  function commitPage(): void {
    if (commitTimer !== null) {
      clearTimeout(commitTimer);
      commitTimer = null;
    }

    const index = Number(pageDraft.value.trim()) - 1;

    if (Number.isInteger(index) && index >= 0 && index < props.pageCount) {
      if (index !== props.pageIndex) {
        emit('page-change', index);
      }
      return;
    }

    pageDraft.value = String(props.pageIndex + 1);
  }

  onBeforeUnmount(() => {
    if (commitTimer !== null) {
      clearTimeout(commitTimer);
    }
  });
  const pageSizeProxy = computed({
    get: () => props.pageSize,
    set: (value) => emit('page-size-change', Number(value)),
  });
  const showPagination = computed(() => props.paginated && props.pageCount > 1);
  const showDisplayedRows = computed(
    () => props.total != null && props.from != null && props.to != null
  );
</script>

<template>
  <div :class="{'flex justify-between items-center w-full': !controlsOnly}">
    <div v-if="!controlsOnly">
      <Text
        v-if="showDisplayedRows"
        template="{from} – {to} of {total, plural, =1{# {item}} other{# {items}}}"
        :params="{
          from: from ?? 0,
          to: to ?? 0,
          total: total ?? 0,
          item: itemLabel,
          items: itemsLabel,
        }"
      />
    </div>
    <div class="flex gap-sm">
      <template v-if="showPagination">
        <craft-button
          type="button"
          @click="emit('page-change', pageIndex - 1)"
          .disabled="disabled || pageIndex <= 0"
          :variant="ButtonVariant.Plain"
          icon
          size="small"
        >
          <craft-icon
            name="chevron-left"
            :label="t('Previous page')"
          ></craft-icon>
        </craft-button>
        <div class="flex items-center gap-sm mx-md">
          {{ t('Page') }}
          <craft-input
            type="text"
            inputmode="numeric"
            :value="pageDraft"
            @input="onPageInput"
            @change="commitPage"
            @keydown.enter="commitPage"
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
          .disabled="disabled || pageIndex >= pageCount - 1"
          size="small"
          :variant="ButtonVariant.Plain"
          icon
        >
          <craft-icon name="chevron-right" :label="t('Next page')"></craft-icon>
        </craft-button>
      </template>
    </div>
    <div v-if="!controlsOnly" class="flex gap-md items-center">
      <template v-if="enableAdjustPageSize">
        <span aria-hidden="true">{{ pageSizeLabel }}</span>
        <Select
          small
          :label="pageSizeLabel"
          label-sr-only
          :options="pageSizeOptions!"
          v-model="pageSizeProxy"
          :disabled="disabled"
          class="w-auto"
        />
      </template>
    </div>
  </div>
</template>
