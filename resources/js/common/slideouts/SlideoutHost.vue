<script setup lang="ts">
  /**
   * Renders the open slideout stack.
   *
   * Mounted once at the app root, outside the Inertia page tree, so panels
   * survive a navigation underneath them. Teleported to `<body>` so a panel's
   * form never nests inside the base page's `<form>`.
   */
  import {computed, watch} from 'vue';
  import {onKeyStroke} from '@vueuse/core';
  import {usePage} from '@inertiajs/vue3';
  import SlideoutPanel from './SlideoutPanel.vue';
  import {closeSlideout, slideoutPanels} from './store';
  import {setAssetVersion} from './request';

  const panels = slideoutPanels();
  const page = usePage();

  // Keep the version a slideout fetch sends in step with the loaded page.
  watch(
    () => page.version,
    (version) => setAssetVersion(version ?? ''),
    {immediate: true}
  );

  const topPanel = computed(() => panels[panels.length - 1] ?? null);

  function closeTop() {
    if (topPanel.value) {
      closeSlideout(topPanel.value.id);
    }
  }

  onKeyStroke('Escape', (event) => {
    if (!topPanel.value) {
      return;
    }

    event.preventDefault();
    closeTop();
  });

  // The base page shouldn't scroll behind an open panel.
  watch(
    () => panels.length > 0,
    (open) => document.body.classList.toggle('slideout-open', open)
  );
</script>

<template>
  <Teleport to="body">
    <div
      v-if="panels.length"
      class="slideout-shade"
      @click="closeTop"
      data-slideout-shade
    ></div>
    <SlideoutPanel
      v-for="(panel, index) in panels"
      :key="panel.id"
      :instance="panel"
      :depth="index"
      :total="panels.length"
    />
  </Teleport>
</template>

<style lang="css">
  body.slideout-open {
    overflow: hidden;
  }

  .slideout-shade {
    position: fixed;
    inset: 0;
    background: rgb(0 0 0 / 40%);
    /* Matches the legacy `.slideout-shade`, so Vue and jQuery panels stack
       against each other in DOM order rather than one class always winning. */
    z-index: 100;
  }
</style>
