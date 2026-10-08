<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, useTemplateRef} from 'vue';
  import {
    elementDetailsPanelRegistry,
    type ElementDetailsPanelDescriptor,
  } from '@/bootstrap/element-details-panels';
  import DetailsPanels, {
    type DetailsPanel,
  } from '@/common/components/DetailsPanels.vue';
  import ElementActivityTimeline from '@/modules/elements/components/ElementActivityTimeline.vue';
  import RevisionsList from '@/modules/elements/components/RevisionsList.vue';
  import type {
    ElementEditPayload,
    ElementEditPayloadUpdater,
    ElementFormActionSubmitter,
  } from '@/modules/elements/composables/useElementEditor';

  type ElementDetailsPanel = DetailsPanel &
    Pick<
      ElementDetailsPanelDescriptor,
      'visible' | 'status' | 'props' | 'headerActionsComponent'
    >;

  const props = defineProps<{
    payload: ElementEditPayload;
    activityTimelineVersion: number;
    updatePayload: ElementEditPayloadUpdater;
    submitAction: ElementFormActionSubmitter;
    syncLocationHash?: boolean;
  }>();

  const corePanels: ElementDetailsPanel[] = [
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

  const visiblePanels = computed<ElementDetailsPanel[]>(() =>
    (
      [
        ...corePanels,
        ...elementDetailsPanelRegistry.panels,
      ] as ElementDetailsPanel[]
    )
      .filter((panel) => panel.visible?.(props.payload) ?? true)
      .map((panel) => ({
        ...panel,
        statusData: panel.status?.(props.payload) ?? null,
      }))
  );

  const detailsPanels = useTemplateRef<{select(panelId: string): void}>(
    'detailsPanels'
  );

  function componentProps(
    panel: DetailsPanel,
    activePanelId: string | null
  ): Record<string, unknown> {
    const context = {
      payload: props.payload,
      active: activePanelId === panel.id,
      refreshToken: props.activityTimelineVersion,
      updatePayload: props.updatePayload,
      submitAction: props.submitAction,
    };

    return (panel as ElementDetailsPanel).props?.(context) ?? {};
  }

  function select(panelId: string): void {
    detailsPanels.value?.select(panelId);
  }

  defineExpose({select});
</script>

<template>
  <DetailsPanels
    ref="detailsPanels"
    :panels="visiblePanels"
    :component-props="componentProps"
    id-prefix="element-details-panel"
    :sync-location-hash="syncLocationHash"
  >
    <template #info>
      <slot name="info" />
    </template>
  </DetailsPanels>
</template>
