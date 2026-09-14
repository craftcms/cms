<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {ref} from 'vue';
  import ActivityTimeline from '@/modules/activity/components/ActivityTimeline.vue';
  import RevisionsList from '@/modules/elements/components/RevisionsList.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';

  defineProps<{
    payload: ElementEditPayload;
    activityTimelineVersion: number;
    pane?: boolean;
  }>();

  const activityTabOpen = ref(false);

  function onSelectedChanged(event: Event): void {
    activityTabOpen.value =
      (event.target as {selectedIndex?: number} | null)?.selectedIndex === 1;
  }
</script>

<template>
  <craft-tabs
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
