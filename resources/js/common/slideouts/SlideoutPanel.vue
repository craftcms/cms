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
  import {
    computed,
    onBeforeUnmount,
    onMounted,
    ref,
    provide,
    useTemplateRef,
    watch,
  } from 'vue';
  import {
    addModalAttributes,
    ESC_KEY,
    releaseFocusWithin,
    setFocusWithin,
    trapFocusWithin,
  } from '@craftcms/garnish';
  import AppLayout from '@/common/layouts/AppLayout.vue';
  import SlideoutScreen from '@/common/layouts/screens/SlideoutScreen.vue';
  import {
    createScreenPropsStore,
    provideScreenContext,
    ScreenPagePropsKey,
    ScreenPropsStoreKey,
    ScreenShellKey,
  } from '@/common/composables/screen';
  import {
    registerPanel,
    uiLayerManager,
    unregisterPanel,
    type StackedPanel,
  } from './panel-stack';
  import {closeSlideout, notifySlideoutSaved, reloadSlideout} from './store';
  import {SlideoutControllerKey, type SlideoutInstance} from './types';

  const props = defineProps<{
    instance: SlideoutInstance;
  }>();

  provide(ScreenShellKey, SlideoutScreen);
  provide(ScreenPropsStoreKey, createScreenPropsStore());
  provideScreenContext('slideout');

  // Without this the shell would read its chrome off `usePage()` — the page
  // *behind* the slideout — and show that screen's title and edit URL.
  provide(ScreenPagePropsKey, () => props.instance.props);

  provide(SlideoutControllerKey, {
    get instance() {
      return props.instance;
    },
    close: (options?: {force?: boolean}) =>
      closeSlideout(props.instance.id, options),
    reload: () => reloadSlideout(props.instance.id),
    saved: (result) => notifySlideoutSaved(props.instance.id, result),
  });

  /**
   * Which edge the panel is anchored to.
   *
   * Mirrors Craft 5: the offset is applied to the side *opposite* the one
   * panels slide in from, so `Craft.slideoutPosition` flips it.
   */
  const positionProp = computed(() =>
    Object.getOwnPropertyDescriptor(window.Craft, 'slideoutPosition')?.value ===
    'start'
      ? 'inset-inline-end'
      : 'inset-inline-start'
  );

  const panelEl = useTemplateRef<HTMLElement>('panel');

  /**
   * Where this panel sits, driven by the shared stack rather than by this
   * component's position in the Vue list — a legacy jQuery slideout can be
   * open at the same time, and only the stack sees both.
   *
   * Starts offscreen so the panel transitions in: {@link registerPanel}
   * forces a reflow before positioning it, so the browser has the `100vw`
   * start value to animate from.
   */
  const offset = ref('100vw');

  /**
   * The stack's view of this panel. Its `position()` mirrors Craft 5's
   * `Slideout.updateStyles()`, where the offset was `45 * ((total - i) /
   * total)vw` for panels 55% wide — `45` being the viewport left over beside
   * one. That's derived from the width here rather than hard-coded, so
   * changing `--slideout-width` keeps the geometry correct.
   *
   * Spreading the stack across the leftover space rather than stepping by a
   * constant means it never runs off the edge however deep it goes; each
   * panel just peeks out a little less.
   */
  const stackPanel: StackedPanel = {
    get element() {
      return panelEl.value!;
    },
    position(index: number, total: number) {
      offset.value = `calc((100vw - var(--slideout-panel-width)) * ${
        (index + 1) / total
      })`;
    },
    handleShadeClick() {
      closeSlideout(props.instance.id);
    },
  };

  onMounted(() => {
    const el = panelEl.value!;

    addModalAttributes(el);
    trapFocusWithin(el);

    // Joining the layer manager rather than listening for Escape on the
    // window is what keeps a Vue panel in the right order against legacy
    // slideouts, modals, and menus: only the topmost layer's shortcuts fire,
    // so one Escape press closes one thing.
    uiLayerManager().addLayer(el);
    uiLayerManager().registerShortcut(ESC_KEY, () => {
      closeSlideout(props.instance.id);
    });

    // After `addLayer`, which the stack relies on to work out which container
    // to leave visible to assistive technology.
    registerPanel(stackPanel);

    // A locally-built panel (see `openSlideoutWith`) arrives with its component
    // already set, so the watcher below never fires for it.
    if (props.instance.component) {
      setFocusWithin(el);
    }
  });

  onBeforeUnmount(() => {
    const el = panelEl.value!;

    // By element, not the bare pop: with two slideout stacks interleaving,
    // this panel isn't necessarily the topmost layer. Before `unregisterPanel`,
    // which restores the background layers based on what's still open.
    uiLayerManager().removeLayer(el);
    unregisterPanel(stackPanel);
    releaseFocusWithin(el);
  });

  // The screen arrives a request after the panel does, so focus can't be
  // placed until there's something in it to focus.
  watch(
    () => props.instance.component,
    (component) => {
      if (component && panelEl.value) {
        setFocusWithin(panelEl.value);
      }
    }
  );
</script>

<template>
  <!-- `data-slideout-id` is how the store works out which panel something was
    opened from, so it can nest below it rather than replace it. -->
  <div
    ref="panel"
    class="slideout-panel"
    :data-slideout-id="instance.id"
    :style="{
      ...(instance.width ? {'--slideout-width': instance.width} : {}),
      [positionProp]: offset,
    }"
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
    /* Change every slideout at once by setting `--slideout-width` on `:root`,
       or one of them with `openSlideout(url, {width: '40rem'})`. Must be a
       length, not a percentage — the stack offset subtracts it from `100vw`.
       Resolved into a local so the fallback lives in exactly one place; the
       offset `calc()` reads this too. */
    --slideout-panel-width: var(--slideout-width, 55vw);

    position: fixed;
    inset-block: 0;
    /* The inline offset is set per-panel from `positionProp`/`offset`. */
    width: var(--slideout-panel-width);
    /* Lets the shell lay itself out against the panel's width rather than the
       viewport's — the panel is far narrower, and `--slideout-width` is
       configurable, so a media query would be measuring the wrong thing. */
    container: slideout / inline-size;
    display: flex;
    flex-direction: column;
    background: var(--c-surface-default, #fff);
    box-shadow: 0 0 16px rgb(0 0 0 / 15%);
    overflow: hidden;
    /* Same as the legacy `.slideout-container`. */
    z-index: 100;
    /* Leading corners only — the trailing edge meets the viewport. */
    border-start-start-radius: var(--c-radius-lg, 0.5rem);
    border-end-start-radius: var(--c-radius-lg, 0.5rem);

    /* Both sides, because `positionProp` picks one at runtime. Without this
       the outer panel would jump aside rather than visibly slide out from
       behind the new one. */
    @media screen and (prefers-reduced-motion: no-preference) {
      transition:
        inset-inline-start linear 250ms,
        inset-inline-end linear 250ms;
    }
  }

  @media screen and (max-width: 640px) {
    .slideout-panel {
      /* Full-width sheet on small screens, matching the legacy slideout —
         there isn't room to peek, so stacked panels sit on top of each other.
         `!important` because the inline offset is set per-panel. */
      inset-inline: 0 !important;
      width: auto !important;
      inset-block-start: 15vh;
      border-start-end-radius: var(--c-radius-lg, 0.5rem);
      border-end-start-radius: 0;
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
