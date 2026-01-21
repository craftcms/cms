<script setup lang="ts">
  import CpLink from '@/components/CpLink.vue';

  withDefaults(
    defineProps<{
      items: Array<{
        url?: string | null;
        label: string;
      }>;
      separator?: string;
    }>(),
    {
      separator: '/',
    }
  );
</script>

<template>
  <ul class="breadcrumbs">
    <li
      v-for="(item, idx) in items"
      :key="idx"
      :class="{
        'breadcrumb-item': true,
        'breadcrumb-item--active': idx === items.length - 1,
      }"
    >
      <template v-if="item.url">
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
