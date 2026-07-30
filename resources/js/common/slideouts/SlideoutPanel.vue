<script setup lang="ts">
  /**
   * One open slideout.
   *
   * Provides the slideout shell, so the `AppLayout` the page renders inside
   * resolves to `SlideoutScreen` instead of `PageScreen`, and a scoped props
   * store so `useAppLayout()` configures this panel rather than the base page.
   *
   * The page component is wrapped in `AppLayout` here because
   * `resolveInertiaPage()` returns the bare component — Inertia only attaches
   * the default layout for components it renders itself.
   */
  import {computed, provide} from 'vue';
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import SlideoutScreen from '@/common/layouts/screens/SlideoutScreen.vue';
  import {
    createScreenPropsStore,
    provideScreenContext,
    ScreenPropsStoreKey,
    ScreenShellKey,
  } from '@/common/composables/screen';
  import {closeSlideout, reloadSlideout} from './store';
  import {SlideoutControllerKey, type SlideoutInstance} from './types';

  const props = defineProps<{
    instance: SlideoutInstance;
    /** Stack depth, 0 = outermost. Drives the inset offset. */
    depth: number;
    total: number;
  }>();

  provide(ScreenShellKey, SlideoutScreen);
  provide(ScreenPropsStoreKey, createScreenPropsStore());
  provideScreenContext('slideout');

  provide(SlideoutControllerKey, {
    get instance() {
      return props.instance;
    },
    close: () => closeSlideout(props.instance.id),
    reload: () => reloadSlideout(props.instance.id),
  });

  // Stacked panels step in from the edge so the one underneath stays visible.
  const offset = computed(() => `${(props.total - props.depth - 1) * 3}rem`);
</script>

<template>
  <!-- `data-slideout-id` is how the store works out which panel something was
    opened from, so it can nest below it rather than replace it. -->
  <div
    class="slideout-panel"
    :data-slideout-id="instance.id"
    :style="{'--slideout-offset': offset}"
  >
    <div v-if="instance.loading" class="slideout-panel__status">
      <craft-spinner></craft-spinner>
    </div>
    <div v-else-if="instance.error" class="slideout-panel__status">
      {{ instance.error }}
    </div>
    <AppLayout v-else-if="instance.component">
      <component :is="instance.component" v-bind="instance.props" />
    </AppLayout>
  </div>
</template>

<style scoped lang="css">
  .slideout-panel {
    position: fixed;
    inset-block: 0;
    inset-inline-end: 0;
    inset-inline-start: max(0px, calc(100vw - 60rem - var(--slideout-offset)));
    display: flex;
    flex-direction: column;
    background: var(--c-surface-default, #fff);
    box-shadow: -2px 0 16px rgb(0 0 0 / 15%);
    overflow: hidden;
    /* Same as the legacy `.slideout-container`. */
    z-index: 100;
  }

  @media screen and (max-width: 640px) {
    .slideout-panel {
      /* Bottom sheet on small screens, matching the legacy slideout. */
      inset-inline-start: 0;
      inset-block-start: 15vh;
    }
  }

  .slideout-panel__status {
    display: flex;
    align-items: center;
    justify-content: center;
    flex: 1;
    padding: var(--c-spacing-xl, 2rem);
  }
</style>
