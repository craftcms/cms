<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import CpLink from '@/common/components/CpLink.vue';
  import type {ElementIndexSource} from '../types';

  defineOptions({
    name: 'SourceList',
  });

  defineProps<{
    sources: Array<ElementIndexSource>;
    selected: string | null;
    sourceUrl: (key: string) => string;
  }>();
</script>

<template>
  <craft-nav-list :aria-label="t('Sources')">
    <template v-for="(source, index) in sources" :key="source.key ?? index">
      <li v-if="source.type === 'heading'" class="nav-heading">
        <span class="nav-heading-label">{{ source.heading }}</span>
      </li>
      <template v-else>
        <CpLink
          as="craft-nav-item"
          :href="sourceUrl(source.key!)"
          :active="source.key === selected"
          block
          flush
        >
          {{ source.label }}
          <span
            v-if="source.badgeCount != null"
            class="badge"
            aria-hidden="true"
          >
            {{ source.badgeCount }}
          </span>
        </CpLink>
        <SourceList
          v-if="source.nested?.length"
          :sources="source.nested"
          :selected="selected"
          :source-url="sourceUrl"
          class="ps-4"
        />
      </template>
    </template>
  </craft-nav-list>
</template>

<style scoped lang="scss">
  .nav-heading-label {
    display: block;
    padding-block: var(--c-spacing-sm);
    color: var(--c-text-quiet);
    font-weight: 600;
  }
</style>
