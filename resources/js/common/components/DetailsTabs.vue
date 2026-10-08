<script setup lang="ts">
  /**
   * The details column: a rail of icon buttons, each disclosing one panel. At
   * most one panel is open, and closing it folds the column to the rail. Panels
   * render either a named slot or a component, so a page can mix its own markup
   * with registered tabs.
   *
   * On a full page the rail is teleported away from the panels, so opening a
   * panel from it moves focus to the panel's heading, and Escape or the
   * panel's close button hands focus back to the rail.
   */
  import '@craftcms/ui/components/disclosure/disclosure';
  import {t} from '@craftcms/ui';
  import {useEventListener} from '@vueuse/core';
  import {computed, nextTick, shallowRef, watch} from 'vue';
  import type {Component} from 'vue';
  import {
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
      /** Props for a component tab, given the tab and the open tab's ID. */
      componentProps?: (
        tab: DetailsTab,
        activeTabId: string | null
      ) => Record<string, unknown>;
      idPrefix?: string;
      /** Whether the open tab is mirrored in the URL hash. */
      syncLocationHash?: boolean;
    }>(),
    {idPrefix: 'details-tab', syncLocationHash: false}
  );

  const visibleTabs = computed<DetailsTab[]>(() =>
    [...props.tabs].sort(
      (firstTab, secondTab) => (firstTab.order ?? 0) - (secondTab.order ?? 0)
    )
  );

  /** Where the rail goes when the shell keeps it apart from the panels. */
  const rail = useScreenDetailsRail();

  function triggerId(tabId: string): string {
    return `${props.idPrefix}-${tabId}`;
  }

  function panelId(tabId: string): string {
    return `${triggerId(tabId)}-panel`;
  }

  function headingId(tabId: string): string {
    return `${panelId(tabId)}-heading`;
  }

  function isVisible(tabId: string): boolean {
    return visibleTabs.value.some((tab) => tab.id === tabId);
  }

  const initialHash =
    props.syncLocationHash && window.location.hash
      ? window.location.hash.slice(1)
      : null;
  const openTabId = shallowRef<string | null>(
    initialHash && isVisible(initialHash)
      ? initialHash
      : (visibleTabs.value[0]?.id ?? null)
  );

  // A tab that goes away takes its panel with it; fall back to the first.
  watch(visibleTabs, (tabs) => {
    if (openTabId.value && !tabs.some((tab) => tab.id === openTabId.value)) {
      openTabId.value = tabs[0]?.id ?? null;
    }
  });

  /**
   * The column folds to its rail once the shell overlays it. The width that
   * happens at is the shell's call, so this follows the flag rather than
   * measuring, and only reopens a panel the shell closed itself.
   */
  const overlaid = useScreenDetailsOverlay();
  let closedByShell = false;

  watch(
    () => overlaid?.value ?? false,
    (isOverlaid) => {
      if (isOverlaid) {
        if (openTabId.value !== null) {
          openTabId.value = null;
          closedByShell = true;
        }

        return;
      }

      if (closedByShell && openTabId.value === null) {
        openTabId.value = visibleTabs.value[0]?.id ?? null;
      }

      closedByShell = false;
    },
    {immediate: true}
  );

  /** Focuses an element once the panel visibility it depends on has rendered. */
  function focusAfterRender(id: string): void {
    void nextTick(() => document.getElementById(id)?.focus());
  }

  function open(tabId: string, withFocus: boolean): void {
    openTabId.value = tabId;
    closedByShell = false;
    updateLocationHash();

    if (withFocus) {
      focusAfterRender(headingId(tabId));
    }
  }

  function close(tabId: string, returnFocus: boolean): void {
    if (openTabId.value !== tabId) {
      return;
    }

    openTabId.value = null;

    if (returnFocus) {
      focusAfterRender(triggerId(tabId));
    }
  }

  /**
   * The trigger whose click is being handled. Recorded on the way down, before
   * `craft-disclosure` toggles itself, so the `craft-show` that follows knows a
   * person opened the panel rather than the hash or the shell.
   */
  let clickedTabId: string | null = null;

  function onShow(tabId: string): void {
    const withFocus = clickedTabId === tabId;
    clickedTabId = null;

    // `craft-disclosure` reopens its target as it disconnects, so a removed
    // tab announces itself one last time.
    if (openTabId.value !== tabId && isVisible(tabId)) {
      open(tabId, withFocus);
    }
  }

  function onPanelKeydown(event: KeyboardEvent, tabId: string): void {
    if (event.key !== 'Escape' || event.defaultPrevented) {
      return;
    }

    event.stopPropagation();
    close(tabId, true);
  }

  function select(tabId: string): void {
    if (isVisible(tabId)) {
      open(tabId, true);
    }
  }

  function updateLocationHash(): void {
    if (props.syncLocationHash && openTabId.value) {
      const url = new URL(window.location.href);
      url.hash = openTabId.value;
      window.history.replaceState(window.history.state, '', url);
    }
  }

  useEventListener(window, 'hashchange', () => {
    if (!props.syncLocationHash) {
      return;
    }

    const tabId = window.location.hash.slice(1);
    if (isVisible(tabId)) {
      open(tabId, false);
    }
  });

  defineExpose({select});
</script>

<template>
  <div class="details-tabs">
    <!-- Panels come first: each trigger finds its panel by id when it connects. -->
    <div class="details-tabs__panels" :hidden="openTabId === null">
      <section
        v-for="tab in visibleTabs"
        :id="panelId(tab.id)"
        :key="tab.id"
        :aria-labelledby="headingId(tab.id)"
        :hidden="openTabId !== tab.id"
        @keydown="onPanelKeydown($event, tab.id)"
      >
        <DetailsTabPanel
          :tab="tab"
          :heading-id="headingId(tab.id)"
          :component-props="componentProps?.(tab, openTabId) ?? {}"
          @close="close(tab.id, true)"
        >
          <slot v-if="tab.slot" :name="tab.slot" />
        </DetailsTabPanel>
      </section>
    </div>
    <Teleport defer :to="rail ?? 'body'" :disabled="!rail">
      <div
        class="details-tabs__rail"
        :class="{'details-tabs__rail--detached': rail}"
        role="group"
        :aria-label="t('Details')"
        :data-open="String(openTabId !== null)"
      >
        <craft-disclosure
          v-for="tab in visibleTabs"
          :key="tab.id"
          :state="openTabId === tab.id ? 'expanded' : 'collapsed'"
          @click.capture="clickedTabId = tab.id"
          @craft-show="onShow(tab.id)"
          @craft-hide="close(tab.id, false)"
        >
          <button
            :id="triggerId(tab.id)"
            type="button"
            class="details-tabs__trigger"
            :aria-controls="panelId(tab.id)"
          >
            <craft-icon :name="tab.icon" :label="tab.label" />
          </button>
        </craft-disclosure>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
  .details-tabs {
    display: flex;
    block-size: 100%;
  }

  .details-tabs__panels {
    flex: 1;
    min-inline-size: 0;
    block-size: 100%;
    overflow-y: auto;

    > section {
      --c-focus-outline-offset: calc(var(--c-focus-outline-width) * -1);
    }
  }

  .details-tabs__panels[hidden],
  .details-tabs__panels > section[hidden] {
    display: none;
  }

  .details-tabs__rail {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    box-sizing: border-box;
    inline-size: var(--cp-rail-width);
    padding: var(--c-spacing-md);
    border-inline-start: 1px solid var(--c-color-border-quiet);
    background-color: var(--c-surface-sunken);
  }

  .details-tabs__rail--detached {
    border-inline-start: none;
    background-color: transparent;
  }

  .details-tabs__trigger {
    display: grid;
    inline-size: var(--c-size-touch-target);
    aspect-ratio: 1;
    padding: 0;
    place-items: center;
    border: 1px solid var(--c-color-border-quiet);
    border-radius: var(--c-radius-md);
    background-color: var(--c-surface-raised);
    color: inherit;
    cursor: pointer;
  }

  .details-tabs__trigger[aria-expanded='true'] {
    border-color: var(--c-color-accent-border-normal);
    background-color: var(--c-color-accent-fill-quiet);
    color: var(--c-color-accent-on-quiet);
  }

  @media (forced-colors: active) {
    .details-tabs__trigger[aria-expanded='true'] {
      border-color: Highlight;
      background-color: Highlight;
      color: HighlightText;
    }
  }
</style>
