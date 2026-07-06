<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import {ref, watch} from 'vue';
  import {useDebounceFn} from '@vueuse/core';
  import CraftInput from '@craftcms/cp/vue/CraftInput.vue';
  import CraftSelect from '@craftcms/cp/vue/CraftSelect.vue';
  import type {ElementIndexSite, ElementIndexStatus} from '../types';

  const props = defineProps<{
    searchTerm: string | null;
    statuses: Array<ElementIndexStatus>;
    selectedStatus: string | null;
    sites: Array<ElementIndexSite>;
    selectedSiteId: number | null;
  }>();

  const emit = defineEmits<{
    (e: 'update:search', value: string): void;
    (e: 'update:status', value: string): void;
    (e: 'update:site', value: string): void;
  }>();

  const search = ref(props.searchTerm ?? '');
  const status = ref(props.selectedStatus ?? '');
  const site = ref(
    props.selectedSiteId !== null ? String(props.selectedSiteId) : ''
  );

  const emitSearch = useDebounceFn(
    () => emit('update:search', search.value),
    500
  );

  watch(status, (value) => emit('update:status', value));
  watch(site, (value) => emit('update:site', value));
</script>

<template>
  <div class="flex flex-wrap gap-2 items-end">
    <CraftInput
      class="flex-1 min-w-48"
      name="search"
      :label="t('Search')"
      v-model="search"
      label-sr-only
      @input="emitSearch"
    />

    <CraftSelect
      v-if="statuses.length"
      name="status"
      :label="t('Status')"
      label-sr-only
      v-model="status"
    >
      <select slot="input">
        <option value="">{{ t('All') }}</option>
        <option
          v-for="option in statuses"
          :key="option.value"
          :value="option.value"
        >
          {{ option.label }}
        </option>
      </select>
    </CraftSelect>

    <CraftSelect
      v-if="sites.length > 1"
      name="site"
      :label="t('Site')"
      label-sr-only
      v-model="site"
    >
      <select slot="input">
        <option
          v-for="option in sites"
          :key="option.id"
          :value="String(option.id)"
        >
          {{ option.name }}
        </option>
      </select>
    </CraftSelect>
  </div>
</template>
