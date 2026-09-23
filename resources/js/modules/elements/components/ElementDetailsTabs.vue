<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {elementDetailsTabRegistry} from '@/bootstrap/element-details-tabs';
  import DetailsTabs, {
    type DetailsTab,
  } from '@/common/components/DetailsTabs.vue';
  import ElementActivityTimeline from '@/modules/elements/components/ElementActivityTimeline.vue';
  import RevisionsList from '@/modules/elements/components/RevisionsList.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';

  type ElementDetailsTab = DetailsTab & {
    visible?: (payload: ElementEditPayload) => boolean;
  };

  const props = defineProps<{
    payload: ElementEditPayload;
    activityTimelineVersion: number;
    pane?: boolean;
  }>();

  const coreTabs: ElementDetailsTab[] = [
    {
      id: 'info',
      label: t('Info'),
      icon: 'circle-info',
      order: 0,
      slot: 'info',
    },
    {
      id: 'activity',
      label: t('Activity'),
      icon: 'wave-pulse',
      component: ElementActivityTimeline,
      order: 10,
      visible: (payload) => payload.activityTimelineUrl !== null,
    },
    {
      id: 'revisions',
      label: t('Revisions'),
      icon: 'clock-rotate-left',
      component: RevisionsList,
      order: 20,
    },
  ];

  const visibleTabs = computed<ElementDetailsTab[]>(() =>
    [...coreTabs, ...elementDetailsTabRegistry.tabs].filter(
      (tab) => tab.visible?.(props.payload) ?? true
    )
  );

  function componentProps(activeTabId: string | null): Record<string, unknown> {
    return {
      payload: props.payload,
      activeTabId,
      refreshToken: props.activityTimelineVersion,
    };
  }
</script>

<template>
  <DetailsTabs
    :tabs="visibleTabs"
    :component-props="componentProps"
    id-prefix="element-details-tab"
  >
    <template #info>
      <slot name="info" />
    </template>
  </DetailsTabs>
</template>
