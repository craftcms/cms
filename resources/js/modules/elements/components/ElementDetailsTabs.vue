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

  type ElementDetailsTab = Omit<ElementDetailsTabDescriptor, 'component'> & {
    component?: Component;
    slot?: string;
  };

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
  const tabs = useTemplateRef<HTMLElement & {selectedIndex: number}>('tabs');
  const selectedTabId = shallowRef<string | null>('info');
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
      <slot v-if="tab.slot" :name="tab.slot" />
      <component
        v-else
        :is="pane ? 'craft-pane' : 'div'"
        :appearance="pane ? 'plain' : undefined"
      >
        <div
          v-if="pane"
          slot="header"
          class="px-2 py-1 border-b border-b-(--c-color-neutral-border-quiet)"
        >
          <h3 slot="title" class="text-xs/4">{{ tab.label }}</h3>
        </div>
        <component
          v-if="tab.component"
          :is="tab.component"
          v-bind="componentProps()"
        />
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
