<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import {computed, nextTick, shallowRef, useTemplateRef, watch} from 'vue';
  import type {Component} from 'vue';
  import {
    elementDetailsTabRegistry,
    type ElementDetailsTabDescriptor,
  } from '@/bootstrap/element-details-tabs';
  import ElementActivityTimeline from '@/modules/elements/components/ElementActivityTimeline.vue';
  import RevisionsList from '@/modules/elements/components/RevisionsList.vue';
  import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';
  import {useScreenDetailsOverlay} from '@/common/composables/screen';

  type ElementDetailsTab = Omit<ElementDetailsTabDescriptor, 'component'> & {
    component?: Component;
    slot?: string;
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
    ([...coreTabs, ...elementDetailsTabRegistry.tabs] as ElementDetailsTab[])
      .filter((tab) => tab.visible?.(props.payload) ?? true)
      .sort(
        (firstTab, secondTab) => (firstTab.order ?? 0) - (secondTab.order ?? 0)
      )
  );

  /**
   * The column folds to its tab rail once the shell overlays it. The width that
   * happens at is the shell's call, so this follows the flag rather than
   * measuring.
   *
   * `collapsed` on `craft-tabs` is reflected output, not an input — selection
   * drives it, so this sets `selectedIndex`.
   */
  const overlaid = useScreenDetailsOverlay();
  const tabs = useTemplateRef<
    HTMLElement & {selectedIndex: number; open(): void; close(): void}
  >('tabs');
  const selectedTabId = shallowRef<string | null>('info');
  /** Whether the last collapse was ours, so a deliberate one is left alone. */
  let collapsedByShell = false;

  watch([() => overlaid?.value ?? false, tabs], ([isOverlaid, element]) => {
    if (!element) {
      return;
    }

    if (isOverlaid) {
      if (element.selectedIndex >= 0) {
        element.selectedIndex = -1;
        collapsedByShell = true;
      }

      return;
    }

    if (collapsedByShell && element.selectedIndex < 0) {
      element.selectedIndex = 0;
    }

    collapsedByShell = false;
  });

  watch(
    [tabs, visibleTabs],
    async ([element, tabs]) => {
      if (!element || !selectedTabId.value) {
        return;
      }

      const selectedIndex = tabs.findIndex(
        (tab) => tab.id === selectedTabId.value
      );
      const fallbackIndex = 0;
      const nextSelectedIndex =
        selectedIndex < 0 ? fallbackIndex : selectedIndex;

      if (selectedIndex < 0) {
        selectedTabId.value = tabs[fallbackIndex]?.id ?? null;
      }

      await nextTick();
      if (element.selectedIndex !== nextSelectedIndex) {
        element.selectedIndex = nextSelectedIndex;
      }
    },
    {flush: 'post'}
  );

  function componentProps(): Record<string, unknown> {
    return {
      payload: props.payload,
      activeTabId: selectedTabId.value,
      refreshToken: props.activityTimelineVersion,
    };
  }

  function toggleDetails(): void {
    if (selectedTabId.value) {
      tabs.value?.close();
    } else {
      tabs.value?.open();
    }
  }

  function onSelectedChanged(event: Event): void {
    const selectedIndex = (event.target as {selectedIndex?: number} | null)
      ?.selectedIndex;
    selectedTabId.value =
      selectedIndex === undefined || selectedIndex < 0
        ? null
        : (visibleTabs.value[selectedIndex]?.id ?? null);
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
    <craft-tab
      v-for="tab in visibleTabs"
      :id="`element-details-tab-${tab.id}`"
      :key="tab.id"
      slot="tab"
    >
      <craft-icon :name="tab.icon" :label="tab.label" />
    </craft-tab>
    <div v-for="tab in visibleTabs" :key="tab.id" slot="panel">
      <div
        class="py-1 px-lg border-b border-b-quiet flex justify-between items-center min-h-(--cp-header-height)"
      >
        <h3 class="text-md/4">{{ tab.label }}</h3>

        <craft-button
          type="button"
          icon="x"
          :aria-label="t('Close {tab}', {tab: tab.label})"
          variant="plain"
          size="small"
          @click="tabs?.close()"
          flush="inline-end"
        ></craft-button>
      </div>
      <slot v-if="tab.slot" :name="tab.slot" />
      <div v-else class="p-lg">
        <component
          v-if="tab.component"
          :is="tab.component"
          v-bind="componentProps()"
        />
      </div>
    </div>
  </craft-tabs>
</template>

<style scoped>
  craft-tabs::part(base) {
    gap: 0;
  }

  craft-tabs::part(strip) {
    /* The rail the collapsed panel folds to. Sized explicitly because the
       preflight's border-box doesn't cross the shadow boundary, so the border
       would otherwise widen it past the variable. */
    box-sizing: border-box;
    inline-size: var(--cp-rail-width);
    padding: var(--c-spacing-md);
    border-inline-start: 1px solid var(--c-color-border-quiet);
    background-color: var(--c-surface-sunken);
  }

  craft-tab {
    display: grid;
    width: var(--c-size-touch-target);
    padding: 0;
    place-items: center;
    border: 1px solid transparent;
    border-radius: var(--c-radius-md);
    aspect-ratio: 1;
    background-color: var(--c-surface-raised);
    border: 1px solid var(--c-color-border-quiet);
  }

  craft-tab[selected='true'] {
    border-color: var(--c-color-accent-border-normal);
    background-color: var(--c-color-accent-fill-quiet);
    color: var(--c-color-accent-on-quiet);

    &::after {
      display: none;
    }
  }
</style>
