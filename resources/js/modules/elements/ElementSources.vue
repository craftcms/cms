<script setup lang="ts">
  import {index} from '@routes/cp/content/index.js';
  import {Link} from '@inertiajs/vue3';
  import useCraftData from '@/common/composables/useCraftData';

  type Source = any;

  withDefaults(
    defineProps<{
      sources: Array<Source>;
      activeSource?: string | null;
    }>(),
    {activeSource: null}
  );

  const {site} = useCraftData();
</script>

<template>
  <craft-nav-list>
    <template v-for="(source, idx) in sources" :key="source.key">
      <template v-if="source.type === 'heading'">
        {{ source.heading }}
      </template>
      <template v-else>
        <Link
          as="craft-nav-item"
          :href="
            index(
              {page: 'entries'},
              {query: {source: source.key, site: site?.handle}}
            )
          "
          preserve-state
          :active="source.key === activeSource"
        >
          {{ source.label }}
        </Link>
      </template>
    </template>
  </craft-nav-list>
  <ul></ul>
</template>

<style scoped lang="scss"></style>
