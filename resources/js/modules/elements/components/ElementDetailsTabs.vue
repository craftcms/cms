<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, useTemplateRef} from 'vue';
  import {
    elementDetailsTabRegistry,
    type ElementDetailsTabDescriptor,
  } from '@/bootstrap/element-details-tabs';
  import DetailsTabs, {
    type DetailsTab,
  } from '@/common/components/DetailsTabs.vue';
  import ElementActivityTimeline from '@/modules/elements/components/ElementActivityTimeline.vue';
  import RevisionsList from '@/modules/elements/components/RevisionsList.vue';
  import type {
    ElementEditPayload,
    ElementEditPayloadUpdater,
    ElementFormActionSubmitter,
  } from '@/modules/elements/composables/useElementEditor';

  type ElementDetailsTab = DetailsTab &
    Pick<
      ElementDetailsTabDescriptor,
      'visible' | 'status' | 'props' | 'headerActionsComponent'
    >;

  const props = defineProps<{
    payload: ElementEditPayload;
    activityTimelineVersion: number;
    updatePayload: ElementEditPayloadUpdater;
    submitAction: ElementFormActionSubmitter;
    syncLocationHash?: boolean;
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
      props: ({payload, active, refreshToken}) => ({
        payload,
        active,
        refreshToken,
      }),
    },
    {
      id: 'revisions',
      label: t('Revisions'),
      icon: 'clock-rotate-left',
      component: RevisionsList,
      order: 20,
      props: ({payload}) => ({payload}),
    },
  ];

  const visibleTabs = computed<ElementDetailsTab[]>(() =>
    ([...coreTabs, ...elementDetailsTabRegistry.tabs] as ElementDetailsTab[])
      .filter((tab) => tab.visible?.(props.payload) ?? true)
      .map((tab) => ({
        ...tab,
        statusData: tab.status?.(props.payload) ?? null,
      }))
  );

  const detailsTabs = useTemplateRef<{select(tabId: string): void}>(
    'detailsTabs'
  );

  function componentProps(
    tab: DetailsTab,
    activeTabId: string | null
  ): Record<string, unknown> {
    const context = {
      payload: props.payload,
      active: activeTabId === tab.id,
      refreshToken: props.activityTimelineVersion,
      updatePayload: props.updatePayload,
      submitAction: props.submitAction,
    };

    return (tab as ElementDetailsTab).props?.(context) ?? {};
  }

  function select(tabId: string): void {
    detailsTabs.value?.select(tabId);
  }

  defineExpose({select});
</script>

<template>
  <DetailsTabs
    ref="detailsTabs"
    :tabs="visibleTabs"
    :component-props="componentProps"
    id-prefix="element-details-tab"
    :sync-location-hash="syncLocationHash"
  >
    <template #info>
      <slot name="info" />
    </template>
  </DetailsTabs>
</template>
