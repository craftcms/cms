<script setup lang="ts">
  /**
   * The palette’s “About Craft CMS” view. Craft’s version and edition come
   * with the page; its trials and updates are fetched, as they aren’t.
   */
  import {onMounted} from 'vue';
  import AboutCraft from '@/common/components/AboutCraft.vue';
  import useAboutCraftInfo from '@/common/composables/useAboutCraftInfo';
  import type {CommandPaletteViewProps} from '../types';

  defineProps<Partial<CommandPaletteViewProps>>();

  defineEmits<{
    (event: 'back'): void;
  }>();

  const {info, load} = useAboutCraftInfo();

  onMounted(load);
</script>

<template>
  <AboutCraft :info="info" class="command-palette-about" />
</template>

<style scoped>
  /* Kept to a readable width in the wide palette. Its lines reach past the
     view's padding to its edges. */
  .command-palette-about {
    --about-craft-max-inline-size: 30rem;
    --about-craft-bleed: var(--c-modal-padding);
  }
</style>
