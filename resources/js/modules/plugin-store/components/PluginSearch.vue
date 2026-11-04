<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import CraftInputSearch from '@craftcms/ui/vue/CraftInputSearch.vue';
  import {router, usePage} from '@inertiajs/vue3';
  import {ref} from 'vue';
  import {search as searchRoute} from '@actions/PluginStore/PluginStoreController';

  const page = usePage<{query?: string}>();
  const query = ref(page.props.query ?? '');

  function search(): void {
    const q = query.value.trim();

    if (q) {
      router.visit(searchRoute(undefined, {query: {q}}));
    }
  }
</script>

<template>
  <form role="search" @submit.prevent="search">
    <CraftInputSearch
      v-model="query"
      :label="t('Search plugins')"
      label-sr-only
      :placeholder="t('Search plugins')"
      autocomplete="off"
    />
  </form>
</template>
