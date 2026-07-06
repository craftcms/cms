<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import IndexLayout from '@/common/layouts/IndexLayout.vue';
  import CpLink from '@/common/components/CpLink.vue';
  import ElementIndex from '@/modules/element-index/components/ElementIndex.vue';
  import SourceList from '@/modules/element-index/components/SourceList.vue';
  import {useElementIndexQuery} from '@/modules/element-index/composables/useElementIndexQuery';
  import type {PaginationData, SortItem} from '@/common/types';
  import type {
    ElementIndexColumn,
    ElementIndexElement,
    ElementIndexSite,
    ElementIndexSortOption,
    ElementIndexSource,
    ElementIndexStatus,
  } from '@/modules/element-index/types';

  defineProps<{
    title: string;
    sources: Array<ElementIndexSource>;
    selectedSource: string | null;
    columns: Array<ElementIndexColumn>;
    sortOptions: Array<ElementIndexSortOption>;
    sort: Array<SortItem>;
    elements: Array<ElementIndexElement>;
    pagination: PaginationData | null;
    searchTerm: string | null;
    sites: Array<ElementIndexSite>;
    selectedSiteId: number | null;
    statuses: Array<ElementIndexStatus>;
    selectedStatus: string | null;
    canCreate: boolean;
    newEntryUrl: string | null;
  }>();

  const {selectSource} = useElementIndexQuery();
</script>

<template>
  <IndexLayout :title="title">
    <template #actions>
      <CpLink
        v-if="canCreate && newEntryUrl"
        appearance="button"
        variant="accent"
        icon="plus"
        :href="newEntryUrl"
        :inertia="false"
      >
        {{ t('New entry') }}
      </CpLink>
    </template>

    <template #interior-nav>
      <SourceList
        :sources="sources"
        :selected="selectedSource"
        @select="selectSource"
      />
    </template>

    <ElementIndex
      :columns="columns"
      :sort-options="sortOptions"
      :sort="sort"
      :elements="elements"
      :pagination="pagination"
      :search-term="searchTerm"
      :statuses="statuses"
      :selected-status="selectedStatus"
      :sites="sites"
      :selected-site-id="selectedSiteId"
      :element-type-plural-name="t('Entries')"
    >
      <template #empty-actions>
        <CpLink
          v-if="canCreate && newEntryUrl"
          appearance="button"
          icon="plus"
          :href="newEntryUrl"
          :inertia="false"
        >
          {{ t('New entry') }}
        </CpLink>
      </template>
    </ElementIndex>
  </IndexLayout>
</template>
