<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {useElementVisibility} from '@vueuse/core';
  import {computed, ref, useTemplateRef, watch} from 'vue';
  import Select from '@/common/form/Select.vue';
  import PluginGrid from './PluginGrid.vue';
  import {
    usePluginIndex,
    type PluginIndexSort,
  } from '../composables/usePluginIndex';
  import {useCoreData} from '../composables/usePluginStoreData';
  import type {PluginIndexFilter} from '../api';
  import type {SortDirection} from '../types';

  const {filter, sortable = true} = defineProps<{
    filter: PluginIndexFilter;
    /** Featured sections come in the order they were curated in. */
    sortable?: boolean;
  }>();

  const {coreData} = useCoreData();

  const chosenSort = ref<PluginIndexSort | null>(null);

  /** The API's first sort option is the default one. */
  const sort = computed<PluginIndexSort | null | false>(() => {
    if (!sortable) {
      return false;
    }

    if (chosenSort.value) {
      return chosenSort.value;
    }

    const [orderBy, direction] =
      Object.entries(coreData.value?.sortOptions ?? {})[0] ?? [];

    return orderBy && direction ? {orderBy, direction} : null;
  });

  const {plugins, loading, pending, hasMore, failed, loadMore} = usePluginIndex(
    () => filter,
    sort
  );

  const attributeOptions = computed(() =>
    Object.keys(coreData.value?.sortOptions ?? {}).map((value) => ({
      value,
      label:
        {
          popularity: t('Popularity'),
          dateUpdated: t('Last Update'),
          name: t('Name'),
        }[value] ?? value,
    }))
  );

  const directionOptions = [
    {value: 'asc', label: t('Ascending')},
    {value: 'desc', label: t('Descending')},
  ];

  function setOrderBy(orderBy: string | number): void {
    const direction = coreData.value?.sortOptions[orderBy] ?? 'asc';
    chosenSort.value = {orderBy: String(orderBy), direction};
  }

  function setDirection(direction: string | number): void {
    if (sort.value) {
      chosenSort.value = {
        orderBy: sort.value.orderBy,
        direction: direction as SortDirection,
      };
    }
  }

  const sentinel = useTemplateRef<HTMLElement>('sentinel');
  const nearEnd = useElementVisibility(sentinel, {rootMargin: '300px'});

  // Re-checked after every page, so a short page that leaves the end in view
  // keeps loading until the view fills up.
  watch([nearEnd, loading], ([visible, busy]) => {
    if (visible && !busy && hasMore.value) {
      void loadMore();
    }
  });
</script>

<template>
  <div class="plugin-index">
    <div class="plugin-index__header">
      <slot name="header"></slot>
      <div v-if="sort" class="flex items-center gap-sm">
        <craft-spinner v-if="loading && !pending"></craft-spinner>
        <Select
          small
          :label="t('Sort by')"
          label-sr-only
          :options="attributeOptions"
          :model-value="sort.orderBy"
          @update:model-value="setOrderBy"
        />
        <Select
          small
          :label="t('Direction')"
          label-sr-only
          :options="directionOptions"
          :model-value="sort.direction"
          @update:model-value="setDirection"
        />
      </div>
    </div>

    <PluginGrid :plugins="plugins" />

    <p v-if="failed" class="plugin-index__error">
      {{ t('Couldn’t get plugins.') }}
    </p>
    <craft-empty
      v-else-if="!loading && !hasMore && plugins.length === 0"
      icon="plug"
      :label="t('No results.')"
    ></craft-empty>

    <div ref="sentinel" class="flex justify-center p-lg">
      <craft-spinner v-if="loading"></craft-spinner>
    </div>
  </div>
</template>

<style scoped>
  .plugin-index__header {
    display: flex;
    flex-wrap: wrap;
    gap: var(--c-spacing-md);
    align-items: center;
    justify-content: space-between;
    padding-block-end: var(--c-spacing-md);
    border-block-end: 1px solid var(--c-color-border-quiet);
  }

  .plugin-index__error {
    color: var(--c-color-danger-on-quiet);
  }
</style>
