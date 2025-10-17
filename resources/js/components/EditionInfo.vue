<script setup lang="ts">
  import useCraftData from '@/composables/useCraftData';
  import {computed} from 'vue';

  const {app} = useCraftData();

  const fullEditionName = computed(() => {
    return `${app.edition} Edition`;
  });
</script>

<template>
  <div class="tw:flex tw:justify-center tw:py-4 tw:px-2 tw:text-muted">
    <div>
      <span lang="en" class="tw:flex tw:items-center tw:gap-2">
        Craft CMS
        <span class="edition-logo">
          <span aria-hidden="true">{{ app.edition }}</span>
          <span class="tw:sr-only">{{ fullEditionName }}</span>
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
