<script setup lang="ts">
  import useCraftData from '@/common/composables/useCraftData';
  import {computed} from 'vue';

  const {app, cpUrl} = useCraftData();

  const fullEditionName = computed(() => {
    return `${app.edition.name} Edition`;
  });
</script>

<template>
  <div
    class="cp:flex cp:flex-col cp:items-center cp:justify-center cp:py-4 cp:px-2 text-quiet cp:gap-1"
  >
    <div lang="en" class="cp:flex cp:items-center cp:gap-2">
      Craft CMS {{ app.version }}
    </div>
    <a :href="`${cpUrl}/plugin-store/upgrade-craft`" class="edition-logo">
      <span aria-hidden="true">{{ app.edition.name }}</span>
      <span class="cp:sr-only">{{ fullEditionName }}</span>
    </a>
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
    padding-block: 3px;
    padding-inline: 3px 1px;
    line-height: 1;
    font-weight: 600;
    letter-spacing: 2px;
    text-transform: uppercase;
    text-align: center;
    text-decoration: none;
    color: inherit;

    @media (hover: hover) {
      &:hover {
        background-color: var(--c-color-accent-fill-loud);
        color: var(--c-color-accent-on-loud);
        border-color: var(--c-color-accent-border-loud);
      }
    }
  }
</style>
