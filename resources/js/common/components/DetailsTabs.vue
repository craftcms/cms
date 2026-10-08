<script setup lang="ts">
  /**
   * The tabbed details column: a rail of icon tabs whose panels fold away to
   * the rail. Tabs render either a named slot or a component, so a page can
   * mix its own markup with registered tabs.
   */
  import {useEventListener} from '@vueuse/core';
  import {computed, nextTick, shallowRef, useTemplateRef, watch} from 'vue';
  import type {Component} from 'vue';
  import {
    useIsSlideout,
    useScreenDetailsOverlay,
    useScreenDetailsRail,
  } from '@/common/composables/screen';
  import DetailsTabPanel from './DetailsTabPanel.vue';

  export interface DetailsTab {
    id: string;
    label: string;
    icon: string;
    order?: number;
    /** A slot to render as the panel. */
    slot?: string;
    /** A component to render as the panel, when there's no slot. */
    component?: Component;
    /** Optional controls rendered at the end of the panel header. */
    headerActionsComponent?: Component;
    /** A status shown in the panel header. */
    statusData?: DetailsTabStatus | null;
  }

  export interface DetailsTabStatus {
    label: string;
    indicator: string;
  }

  const props = withDefaults(
    defineProps<{
      tabs: DetailsTab[];
      /** Props for a component tab, given the tab and the selected tab's ID. */
      componentProps?: (
        tab: DetailsTab,
        activeTabId: string | null
      ) => Record<string, unknown>;
      idPrefix?: string;
      /** Whether the selected tab is mirrored in the URL hash. */
      syncLocationHash?: boolean;
    }>(),
    {idPrefix: 'details-tab', syncLocationHash: false}
  );

  const visibleTabs = computed<DetailsTab[]>(() =>
    [...props.tabs].sort(
      (firstTab, secondTab) => (firstTab.order ?? 0) - (secondTab.order ?? 0)
    )
  );

  /**
   * Where the strip goes when the shell keeps it apart from the panels. The
   * panels then stay here as plain sections, which the strip drives in its
   * external-panel mode.
   */
  const rail = useScreenDetailsRail();

  function panelId(tab: DetailsTab): string {
    return `${props.idPrefix}-${tab.id}-panel`;
  }

  /**
   * The column folds to its tab rail once the shell overlays it. The width that
   * happens at is the shell's call, so this follows the flag rather than
   * measuring.
   *
   * `collapsed` on `craft-tabs` is reflected output, not an input — selection
   * drives it, so this sets `selectedIndex`.
   */
  const overlaid = useScreenDetailsOverlay();
  const tabsElement = useTemplateRef<
    HTMLElement & {
      selectedIndex: number;
      open(): void;
      close(): void;
      refresh(): void;
    }
  >('tabs');
  // A slideout opens on its content; the column starts folded to the rail.
  const startsFolded = useIsSlideout();
  const selectedTabId = shallowRef<string | null>(
    startsFolded
      ? null
      : props.syncLocationHash && window.location.hash
        ? window.location.hash.slice(1)
        : (visibleTabs.value[0]?.id ?? null)
  );
  if (startsFolded) {
    watch(
      tabsElement,
      (element) => {
        if (element) {
          element.selectedIndex = -1;
        }
      },
      {once: true}
    );
  }

  /** Whether the last collapse was ours, so a deliberate one is left alone. */
  let collapsedByShell = false;

  watch(
    [() => overlaid?.value ?? false, tabsElement],
    ([isOverlaid, element]) => {
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
    }
  );

  watch(
    [tabsElement, visibleTabs, selectedTabId],
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

      // Folded by the shell, which runs first on mount: keeping the selection
      // in step mustn't open it back over the content. `select()` opens it
      // itself when that's what's wanted.
      if (overlaid?.value && element.selectedIndex < 0) {
        collapsedByShell = true;
        return;
      }

      if (element.selectedIndex !== nextSelectedIndex) {
        element.selectedIndex = nextSelectedIndex;
      }
    },
    {flush: 'post'}
  );

  // Panels added after the strip wired the rest aren't wired yet.
  watch(
    visibleTabs,
    async () => {
      if (rail) {
        await nextTick();
        tabsElement.value?.refresh();
      }
    },
    {flush: 'post'}
  );

  function onSelectedChanged(event: Event): void {
    const selectedIndex = (event.target as {selectedIndex?: number} | null)
      ?.selectedIndex;
    selectedTabId.value =
      selectedIndex === undefined || selectedIndex < 0
        ? null
        : (visibleTabs.value[selectedIndex]?.id ?? null);

    updateLocationHash();
  }

  function select(tabId: string): void {
    if (!visibleTabs.value.some((tab) => tab.id === tabId)) {
      return;
    }

    selectedTabId.value = tabId;
    if (tabsElement.value) {
      tabsElement.value.selectedIndex = visibleTabs.value.findIndex(
        (tab) => tab.id === tabId
      );
    }
    updateLocationHash();
  }

  function updateLocationHash(): void {
    if (props.syncLocationHash && selectedTabId.value) {
      const url = new URL(window.location.href);
      url.hash = selectedTabId.value;
      window.history.replaceState(window.history.state, '', url);
    }
  }

  useEventListener(window, 'hashchange', () => {
    if (!props.syncLocationHash) {
      return;
    }

    const tabId = window.location.hash.slice(1);
    if (visibleTabs.value.some((tab) => tab.id === tabId)) {
      selectedTabId.value = tabId;
    }
  });

  defineExpose({select});
</script>

<template>
  <template v-if="rail">
    <Teleport defer :to="rail">
      <craft-tabs
        ref="tabs"
        class="details-tabs__rail"
        placement="inline-end"
        collapsible
        @craft-tab-show="onSelectedChanged"
      >
        <craft-tab
          v-for="tab in visibleTabs"
          :id="`${idPrefix}-${tab.id}`"
          :key="tab.id"
          slot="tab"
          :controls="panelId(tab)"
        >
          <craft-icon :name="tab.icon" :label="tab.label" />
        </craft-tab>
      </craft-tabs>
      <craft-tooltip
        v-for="tab in visibleTabs"
        :key="tab.id"
        :for="`${idPrefix}-${tab.id}`"
        placement="left"
        invoker-relation="label"
      >
        {{ tab.label }}
      </craft-tooltip>
    </Teleport>
    <div class="details-tabs__panels">
      <section
        v-for="tab in visibleTabs"
        :id="panelId(tab)"
        :key="tab.id"
        class="hidden"
      >
        <DetailsTabPanel
          :tab="tab"
          :component-props="componentProps?.(tab, selectedTabId) ?? {}"
          @close="tabsElement?.close()"
        >
          <slot v-if="tab.slot" :name="tab.slot" />
        </DetailsTabPanel>
      </section>
    </div>
  </template>
  <craft-tabs
    v-else
    ref="tabs"
    placement="inline-end"
    collapsible
    @craft-tab-show="onSelectedChanged"
  >
    <craft-tab
      v-for="tab in visibleTabs"
      :id="`${idPrefix}-${tab.id}`"
      :key="tab.id"
      slot="tab"
    >
      <craft-icon :name="tab.icon" :label="tab.label" />
    </craft-tab>
    <div v-for="tab in visibleTabs" :key="tab.id" slot="panel">
      <DetailsTabPanel
        :tab="tab"
        :component-props="componentProps?.(tab, selectedTabId) ?? {}"
        @close="tabsElement?.close()"
      >
        <slot v-if="tab.slot" :name="tab.slot" />
      </DetailsTabPanel>
    </div>
  </craft-tabs>
  <template v-if="!rail">
    <craft-tooltip
      v-for="tab in visibleTabs"
      :key="tab.id"
      :for="`${idPrefix}-${tab.id}`"
      placement="left"
      invoker-relation="label"
    >
      {{ tab.label }}
    </craft-tooltip>
  </template>
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

  /* The rail sits on the page background, and holds no panels. */
  .details-tabs__rail::part(strip) {
    border-inline-start: none;
    background-color: transparent;
  }

  .details-tabs__rail::part(panels) {
    display: none;
  }

  .details-tabs__panels {
    block-size: 100%;
    overflow-y: auto;
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

  craft-tab[aria-selected='true'] {
    border-color: var(--c-color-accent-border-normal);
    background-color: var(--c-color-accent-fill-quiet);
    color: var(--c-color-accent-on-quiet);

    &::after {
      display: none;
    }
  }
</style>
