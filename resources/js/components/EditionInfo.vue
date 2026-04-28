<script setup lang="ts">
  import useCraftData from '@/composables/useCraftData';
  import {computed} from 'vue';

  const {app} = useCraftData();

  const fullEditionName = computed(() => {
    return `${app.edition.name} Edition`;
  });
</script>

<template>
  <div class="flex justify-center py-4 px-2 text-muted">
    <div>
      <span lang="en" class="flex items-center gap-2">
        Craft CMS
        <span class="edition-logo">
          <span aria-hidden="true">{{ app.edition.name }}</span>
          <span class="sr-only">{{ fullEditionName }}</span>
        </span>
        {{ app.version }}
      </span>
    </div>
  </div>
</template>

<style scoped lang="css">
  .edition-logo {
    user-select: none;
    border: 1px solid currentColor;
    border-radius: 3px;
    display: inline-flex;
    box-sizing: content-box;
    font-size: 11px;
    padding-block: 6px;
    padding-inline: 7px 5px;
    line-height: 8px;
    font-weight: 600;
    letter-spacing: 1.7px;
    text-transform: uppercase;
  }
</style>
