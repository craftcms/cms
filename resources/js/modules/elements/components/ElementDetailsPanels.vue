<script setup lang="ts">
  /**
   * An element editor's details panels: Info, the field layout's sidebar
   * tabs, Activity, Revisions, and any registered with
   * `Cp.$elementDetailsPanels`, each added to the screen with `ScreenPanel`.
   */
  import {t} from '@craftcms/ui';
  import {computed} from 'vue';
  import {
    elementDetailsPanelRegistry,
    type ElementDetailsPanelDescriptor,
  } from '@/bootstrap/element-details-panels';
  import ScreenPanel from '@/common/components/ScreenPanel.vue';
  import {useScreenPanels} from '@/common/composables/screenPanels';
  import ElementActivityTimeline from '@/modules/elements/components/ElementActivityTimeline.vue';
  import ElementActivityTimelineActions from '@/modules/elements/components/ElementActivityTimelineActions.vue';
  import RevisionsList from '@/modules/elements/components/RevisionsList.vue';
  import ElementRevisionsActions from '@/modules/elements/components/ElementRevisionsActions.vue';
  import type {
    ElementEditPayload,
    ElementEditPayloadUpdater,
    ElementFormActionSubmitter,
  } from '@/modules/elements/composables/useElementEditor';
  import type {SidebarTabNode} from '@/modules/ui/sidebarTabs';

  const props = defineProps<{
    payload: ElementEditPayload;
    activityTimelineVersion: number;
    updatePayload: ElementEditPayloadUpdater;
    submitAction: ElementFormActionSubmitter;
    /** Field layout tabs shown as panels, after Info. */
    sidebarTabs?: SidebarTabNode[];
  }>();

  const screenPanels = useScreenPanels();

  const corePanels: ElementDetailsPanelDescriptor[] = [
    {
      id: 'activity',
      label: t('Activity'),
      icon: 'wave-pulse',
      component: ElementActivityTimeline,
      headerActionsComponent: ElementActivityTimelineActions,
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
      headerActionsComponent: ElementRevisionsActions,
      order: 20,
      props: ({payload}) => ({payload}),
    },
  ];

  /** The panels rendered from a component: Craft's and registered ones. */
  const componentPanels = computed(() =>
    [...corePanels, ...elementDetailsPanelRegistry.panels].filter(
      (panel) => panel.visible?.(props.payload) ?? true
    )
  );

  function componentProps(
    panel: ElementDetailsPanelDescriptor
  ): Record<string, unknown> {
    return (
      panel.props?.({
        payload: props.payload,
        active: screenPanels?.openPanelId.value === panel.id,
        refreshToken: props.activityTimelineVersion,
        updatePayload: props.updatePayload,
        submitAction: props.submitAction,
      }) ?? {}
    );
  }

  function sidebarTabPanelId(tab: SidebarTabNode): string {
    return `layout-tab-${tab.uid}`;
  }

  /** Opens a panel, moving focus into it. */
  function select(panelId: string): void {
    screenPanels?.open(panelId);
  }

  defineExpose({select});
</script>

<template>
  <ScreenPanel id="info" :label="t('Info')" icon="circle-info" :order="0">
    <slot name="info" />
  </ScreenPanel>

  <!-- Between Info (0) and Activity (10), in layout order. Without an icon
       of its own, a tab shows its name's first letter. -->
  <ScreenPanel
    v-for="(tab, index) in sidebarTabs ?? []"
    :id="sidebarTabPanelId(tab)"
    :key="tab.uid"
    :label="tab.props.label"
    :icon="tab.props.icon ?? 'sidebar-flip'"
    :initial-icon="!tab.props.icon"
    :order="1 + index / 100"
  >
    <slot name="sidebar-tab" :tab="tab" />
  </ScreenPanel>

  <ScreenPanel
    v-for="panel in componentPanels"
    :id="panel.id"
    :key="panel.id"
    :label="panel.label"
    :icon="panel.icon"
    :order="panel.order"
    :status="panel.status?.(payload) ?? null"
  >
    <component :is="panel.component" v-bind="componentProps(panel)" />
    <template v-if="panel.headerActionsComponent" #header-actions>
      <component
        :is="panel.headerActionsComponent"
        v-bind="componentProps(panel)"
      />
    </template>
  </ScreenPanel>
</template>
