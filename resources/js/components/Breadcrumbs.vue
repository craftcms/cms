<script setup lang="ts">
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
    <li v-for="(item, idx) in items" :key="idx" class="breadcrumb-item">
      <template v-if="item.url">
        <a :href="item.url">{{ item.label }}</a>
      </template>
      <template v-else>
        {{ item.label }}
      </template>

      <span class="separator" v-if="idx < items.length - 1">{{ separator }}</span>
    </li>
  </ul>
</template>

<style scoped lang="scss">
  .breadcrumbs {
    display: flex;
  }

  .breadcrumb-item {
  }

  .separator {
    padding: 0 var(--c-spacing-md);
  }
</style>
