<script setup lang="ts">
  import CpLink from '@/common/components/CpLink.vue';
  import DynamicHtmlRenderer from '@/common/components/DynamicHtmlRenderer.vue';
  import {t} from '@craftcms/ui';

  withDefaults(
    defineProps<{
      items: Array<{
        url?: string | null;
        label?: string | null;
        /** Server-rendered crumb content, e.g. an element chip. */
        html?: string | null;
      }>;
      separator?: string;
    }>(),
    {
      separator: '/',
    }
  );
</script>

<template>
  <nav :aria-label="t('Breadcrumbs')">
    <ul class="breadcrumbs">
      <li
        v-for="(item, idx) in items"
        :key="idx"
        :class="{
          'breadcrumb-item': true,
          'breadcrumb-item--active': idx === items.length - 1,
        }"
      >
        <template v-if="item.html">
          <DynamicHtmlRenderer :html="item.html" />
        </template>
        <template v-else-if="item.url">
          <CpLink :href="item.url">{{ item.label }}</CpLink>
        </template>
        <template v-else>
          {{ item.label }}
        </template>

        <span class="separator" v-if="idx < items.length - 1">{{
          separator
        }}</span>
      </li>
    </ul>
  </nav>
</template>

<style scoped lang="scss">
  .breadcrumbs {
    display: flex;
  }

  .breadcrumb-item {
  }

  .breadcrumb-item--active {
    font-weight: bold;
    color: currentColor;
  }

  .separator {
    padding: 0 var(--c-spacing-md);
  }
</style>
