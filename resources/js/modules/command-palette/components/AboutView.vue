<script setup lang="ts">
  /**
   * The palette’s “About Craft CMS” view. Craft’s version and edition come
   * with the page; its trials and updates are fetched, as they aren’t.
   */
  import {actionClient} from '@craftcms/ui';
  import {onMounted, ref} from 'vue';
  import AboutCraftController from '@actions/App/AboutCraftController';
  import AboutCraft from '@/common/components/AboutCraft.vue';
  import type {AboutCraftInfo} from '@/common/components/about-craft';
  import useCraftData from '@/common/composables/useCraftData';
  import type {CommandPaletteViewProps} from '../types';

  defineProps<Partial<CommandPaletteViewProps>>();

  defineEmits<{
    (event: 'back'): void;
  }>();

  const {app} = useCraftData();

  const info = ref<AboutCraftInfo>({
    version: app.value.version,
    edition: app.value.edition.name,
    trial: null,
    updates: null,
  });

  onMounted(async () => {
    try {
      const {data} = await actionClient.get<AboutCraftInfo>(
        AboutCraftController.url()
      );
      info.value = data;
    } catch {
      // The version and edition are already showing, without the trials.
    }
  });
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
