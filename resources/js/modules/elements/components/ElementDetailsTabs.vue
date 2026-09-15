<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {ref, watch} from 'vue';
  import ActivityTimeline from '@/modules/activity/components/ActivityTimeline.vue';
  import RevisionsList from '@/modules/elements/components/RevisionsList.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';

  const props = defineProps<{
    payload: ElementEditPayload;
    activityTimelineVersion: number;
    pane?: boolean;
    /**
     * How much room the editor body has. The column folds itself away when
     * that runs short — see {@link COLLAPSE_WIDTH}.
     */
    availableWidth?: number;
  }>();

  const activityTabOpen = ref(false);

  /**
   * The details column stops being worth its track once the editor body gets
   * narrow, so it folds down to its rail and hands the width back.
   *
   * `collapsed` on `craft-tabs` is reflected output, not an input — selection
   * is what drives it, so this sets `selectedIndex`. The width is measured on
   * the body rather than the viewport, because the global sidebar and a
   * slideout both take from the same space; the parent owns that element, so
   * it does the measuring and passes the number down.
   */
  const COLLAPSE_WIDTH = 880;
  const tabs = ref<(HTMLElement & {selectedIndex: number}) | null>(null);
  /** Whether the last collapse was ours, so a deliberate one is left alone. */
  let collapsedByWidth = false;

  watch([() => props.availableWidth, tabs], ([width, element]) => {
    // 0 while the element is still being measured — not a real narrow body.
    if (!element || !width) {
      return;
    }

    if (width < COLLAPSE_WIDTH) {
      if (element.selectedIndex >= 0) {
        element.selectedIndex = -1;
        collapsedByWidth = true;
      }

      return;
    }

    if (collapsedByWidth && element.selectedIndex < 0) {
      element.selectedIndex = 0;
    }

    collapsedByWidth = false;
  });

  function onSelectedChanged(event: Event): void {
    activityTabOpen.value =
      (event.target as {selectedIndex?: number} | null)?.selectedIndex === 1;
  }
</script>

<template>
  <craft-tabs
    ref="tabs"
    size="small"
    placement="inline-end"
    collapsible
    @selected-changed="onSelectedChanged"
  >
    <craft-tab slot="tab">
      <craft-icon name="circle-info" :label="t('Info')" />
    </craft-tab>
    <div slot="panel">
      <slot name="info" />
    </div>

    <craft-tab v-if="payload.activityTimelineUrl" slot="tab">
      <craft-icon name="wave-pulse" :label="t('Activity')" />
    </craft-tab>
    <div v-if="payload.activityTimelineUrl" slot="panel">
      <component
        :is="pane ? 'craft-pane' : 'div'"
        :appearance="pane ? 'plain' : undefined"
      >
        <div
          v-if="pane"
          slot="header"
          class="px-2 py-1 border-b border-b-(--c-color-neutral-border-quiet)"
        >
          <h3 slot="title" class="text-xs/4">{{ t('Activity') }}</h3>
        </div>
        <ActivityTimeline
          :active="activityTabOpen"
          :url="payload.activityTimelineUrl"
          :element-type="payload.elementType"
          :element-id="payload.canonicalId"
          :site-id="payload.siteId"
          :page-url="payload.activityPageUrl"
          :refresh-token="activityTimelineVersion"
        />
      </component>
    </div>

    <craft-tab slot="tab">
      <craft-icon name="clock-rotate-left" :label="t('Revisions')" />
    </craft-tab>
    <div slot="panel">
      <component
        :is="pane ? 'craft-pane' : 'div'"
        :appearance="pane ? 'plain' : undefined"
      >
        <div
          v-if="pane"
          slot="header"
          class="px-2 py-1 border-b border-b-(--c-color-neutral-border-quiet)"
        >
          <h3 slot="title" class="text-xs/4">{{ t('Revisions') }}</h3>
        </div>
        <RevisionsList :items="payload.contextMenu?.items ?? []" />
      </component>
    </div>
  </craft-tabs>
</template>

<style scoped>
  craft-tabs::part(base) {
    gap: var(--c-spacing-sm);
  }

  craft-tabs::part(strip) {
    border: 0;
  }

  craft-tab {
    display: grid;
    width: var(--c-size-touch-target);
    padding: 0;
    place-items: center;
    border: 1px solid transparent;
    border-radius: var(--c-radius-md);
    aspect-ratio: 1;
    background-color: white;
  }

  craft-tab[selected='true'] {
    border-color: var(--c-color-neutral-border-normal);
    background-color: var(--c-color-neutral-fill-normal);
    color: var(--c-color-neutral-on-normal);

    &::after {
      display: none;
    }
  }
</style>
