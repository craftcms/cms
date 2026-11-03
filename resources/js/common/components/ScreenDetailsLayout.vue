<script setup lang="ts">
  /**
   * Content with a details column beside it, its rail kept with its panels:
   * a slideout's body, and the editor pane of a preview.
   *
   * The column folds to its rail, and only its panels lift out over the
   * content when there's no room to seat them beside it.
   */
  import {provide, useTemplateRef} from 'vue';
  import {useElementSize} from '@vueuse/core';
  import DetailsPanels from '@/common/components/DetailsPanels.vue';
  import {
    ScreenDetailsOverlayKey,
    ScreenDetailsRailKey,
  } from '@/common/composables/screen';
  import type {ScreenPanels} from '@/common/composables/screenPanels';
  import {useDetailsOverlay} from '@/common/composables/useDetailsOverlay';

  withDefaults(
    defineProps<{
      /** The panels the column shows. */
      panels: ScreenPanels;
      idPrefix: string;
      showDetails: boolean;
      /** Without panels, the column shows the `details` slot instead. */
      hasPanels?: boolean;
    }>(),
    {hasPanels: true}
  );

  defineSlots<{
    default(): unknown;
    details?(): unknown;
  }>();

  const root = useTemplateRef<HTMLElement>('root');
  const content = useTemplateRef<HTMLElement>('content');
  const details = useTemplateRef<HTMLElement>('details');

  // The overlay threshold lives in the stylesheet below; this reads it back.
  // Re-read as the layout's width changes, which is what the container query
  // measures. The column's own width won't do: folded to its rail, it holds
  // still as the layout is resized, so it would never unfold again.
  const {width} = useElementSize(root);
  provide(
    ScreenDetailsOverlayKey,
    useDetailsOverlay(() => details.value, width)
  );
  // The rail stays beside its panels, even over a page that has a rail of
  // its own.
  provide(ScreenDetailsRailKey, null);

  defineExpose({content, details});
</script>

<template>
  <div ref="root" class="screen-details-layout">
    <div ref="content" class="screen-details-layout__content">
      <slot></slot>
    </div>

    <!-- v-show, not v-if: the outlet is a teleport target and must stay in
      the DOM so page content can mount before registration flips the
      details on. -->
    <aside
      v-show="showDetails"
      ref="details"
      class="screen-details-layout__details"
    >
      <DetailsPanels
        v-if="hasPanels"
        :registry="panels"
        :id-prefix="idPrefix"
      />
      <slot v-else name="details"></slot>
    </aside>
  </div>
</template>

<style scoped lang="css">
  .screen-details-layout {
    /* What the column's container query measures. */
    container: screen-details-layout / inline-size;
    display: flex;
    /* One layout at every width: the details column collapses to its rail
       rather than dropping below the content. */
    flex-direction: row;
    flex: 1;
    min-height: 0;
    /* The positioning context for the overlaid column, which is why the content
       scrolls and not this — an absolutely positioned child of a scroll
       container scrolls away with it. */
    position: relative;
    overflow: hidden;
    background-color: var(--c-surface-overlay);
  }

  .screen-details-layout__content {
    display: grid;
    gap: var(--c-spacing-md, 1rem);
    align-content: start;
    flex: 1;
    min-width: 0;
    min-height: 0;
    overflow-y: auto;
  }

  .screen-details-layout__details {
    display: grid;
    gap: var(--c-spacing-md, 1rem);
    border-inline-start: 1px solid var(--c-color-border-quiet);
    align-content: stretch;
    align-self: stretch;

    /* Overlaid by default, the layout being narrower than the threshold more
       often than not. Only the panels lift out; the rail stays in the flow.
       The width itself is in the query below. */
    --cp-details-overlay: 1;

    /* The panels anchor to this, above the scrim so the rail stays lit. */
    position: relative;
    z-index: var(--c-layer-sticky);

    &:has(.details-panels__rail[data-open='false']) {
      border-inline-start-color: transparent;
    }
  }

  /* The panels, and only the panels, sit over the content. Flat rather than
     nested in the rule above, where `:deep()` loses its parent selector and
     would reach every panel container inside. */
  .screen-details-layout__details :deep(.details-panels__content) {
    position: absolute;
    inset-block: 0;
    /* The rail's leading edge: at 0 it would open on top of the rail. */
    inset-inline-end: 100%;
    z-index: var(--c-layer-sticky);
    /* `cqi`, not a percentage: percentages resolve against the rail this hangs
       off rather than against the content. */
    inline-size: clamp(calc(300rem / 16), 80cqi, calc(400rem / 16));
    max-inline-size: 80cqi;
    background-color: var(--c-surface-overlay);
    border-inline-start: 1px solid var(--c-color-border-quiet);
    box-shadow: var(--c-shadow-overlay);
  }

  /* Wide enough to seat the column in the flow beside the content. */
  @container screen-details-layout (width > 700px) {
    /* Sized by its panels plus the rail, so closing it hands the room back. */
    .screen-details-layout__details {
      --cp-details-overlay: 0;

      flex: 0 0 auto;
    }

    /* Room of its own, so the panels stay in the flow. */
    .screen-details-layout__details :deep(.details-panels__content) {
      position: static;
      inline-size: calc(350rem / 16);
      min-inline-size: 0;
      max-inline-size: none;
      border-inline-start: 0;
      box-shadow: none;
    }
  }
</style>
