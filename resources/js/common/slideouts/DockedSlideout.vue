<script setup lang="ts">
  /**
   * A CP screen rendered as a slideout would render it, but docked in place
   * rather than stacked over the page: no shade, focus trap or Escape of its
   * own, which belong to whatever it's docked in.
   *
   * It reports through the same slideout controller, so its screen closes
   * and saves as it would in a panel. Key it by `href` to load another screen.
   */
  import {reactive, useId, useTemplateRef, watch} from 'vue';
  import {setFocusWithin} from '@craftcms/garnish';
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import {provideSlideoutScreen} from './screenContext';
  import {loadInto} from './store';
  import type {SlideoutInstance, SlideoutSaveResult} from './types';

  const props = defineProps<{
    href: string;
    /** Moves focus into the screen once it has loaded. */
    focusOnLoad?: boolean;
  }>();

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'saved', result: SlideoutSaveResult): void;
  }>();

  const id = `docked-slideout-${useId()}`;

  const instance = reactive<SlideoutInstance>({
    id,
    containerId: id,
    width: null,
    href: props.href,
    component: null,
    props: {},
    loading: true,
    error: null,
    opener: null,
    onSaved: null,
    onClosed: null,
  });

  provideSlideoutScreen(() => instance, {
    close: () => emit('close'),
    reload: () => loadInto(instance),
    saved: (result = {}) => {
      emit('saved', result);

      return true;
    },
  });

  void loadInto(instance);

  const root = useTemplateRef<HTMLElement>('root');

  watch(
    () => instance.component,
    (component) => {
      if (component && props.focusOnLoad && root.value) {
        setFocusWithin(root.value);
      }
    },
    {flush: 'post'}
  );
</script>

<template>
  <div ref="root" class="docked-slideout">
    <div v-if="instance.loading" class="docked-slideout__status">
      <craft-spinner></craft-spinner>
    </div>
    <div v-else-if="instance.error" class="docked-slideout__status">
      {{ instance.error }}
    </div>
    <AppLayout v-else-if="instance.component">
      <component :is="instance.component" v-bind="instance.props" />
    </AppLayout>
  </div>
</template>

<style scoped>
  .docked-slideout {
    /* The shell lays itself out against the slideout container, as it does in
       a panel. */
    container: slideout / inline-size;
    display: flex;
    flex-direction: column;
    block-size: 100%;
    min-inline-size: 0;
    overflow: hidden;
    background: var(--c-surface-default);
  }

  /* Focus is only parked here when the screen has nothing to focus, as it is
     on a slideout panel, so there's nothing to ring. */
  .docked-slideout:focus-visible {
    outline: none;
  }

  .docked-slideout__status {
    display: flex;
    flex: 1;
    align-items: center;
    justify-content: center;
    padding: var(--c-spacing-xl, 2rem);
  }
</style>
