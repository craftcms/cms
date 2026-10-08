<script setup lang="ts">
  /**
   * The details column: a rail of icon buttons, each disclosing one panel. At
   * most one panel is open, and closing it folds the column to the rail. Panels
   * render either a named slot or a component, so a page can mix its own markup
   * with registered panels.
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
  import DetailsPanelContent from './DetailsPanelContent.vue';

  export interface DetailsPanel {
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
    statusData?: DetailsPanelStatus | null;
  }

  export interface DetailsPanelStatus {
    label: string;
    indicator: string;
  }

  const props = withDefaults(
    defineProps<{
      panels: DetailsPanel[];
      /** Props for a component panel, given the panel and the open panel's ID. */
      componentProps?: (
        panel: DetailsPanel,
        activePanelId: string | null
      ) => Record<string, unknown>;
      idPrefix?: string;
      /** Whether the open panel is mirrored in the URL hash. */
      syncLocationHash?: boolean;
    }>(),
    {idPrefix: 'details-panel', syncLocationHash: false}
  );

  const visiblePanels = computed<DetailsPanel[]>(() =>
    [...props.panels].sort(
      (firstPanel, secondPanel) =>
        (firstPanel.order ?? 0) - (secondPanel.order ?? 0)
    )
  );

  /** Where the rail goes when the shell keeps it apart from the panels. */
  const rail = useScreenDetailsRail();

  function triggerId(panelId: string): string {
    return `${props.idPrefix}-${panelId}`;
  }

  function panelElementId(panelId: string): string {
    return `${triggerId(panelId)}-panel`;
  }

  function headingId(panelId: string): string {
    return `${panelElementId(panelId)}-heading`;
  }

  function isVisible(panelId: string): boolean {
    return visiblePanels.value.some((panel) => panel.id === panelId);
  }

  const initialHash =
    props.syncLocationHash && window.location.hash
      ? window.location.hash.slice(1)
      : null;
  const openPanelId = shallowRef<string | null>(
    initialHash && isVisible(initialHash)
      ? initialHash
      : (visiblePanels.value[0]?.id ?? null)
  );

  // An open panel that goes away falls back to the first.
  watch(visiblePanels, (panels) => {
    if (
      openPanelId.value &&
      !panels.some((panel) => panel.id === openPanelId.value)
    ) {
      openPanelId.value = panels[0]?.id ?? null;
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
        if (openPanelId.value !== null) {
          openPanelId.value = null;
          closedByShell = true;
        }

        return;
      }

      if (closedByShell && openPanelId.value === null) {
        openPanelId.value = visiblePanels.value[0]?.id ?? null;
      }

      closedByShell = false;
    },
    {immediate: true}
  );

  /** Focuses an element once the panel visibility it depends on has rendered. */
  function focusAfterRender(id: string): void {
    void nextTick(() => document.getElementById(id)?.focus());
  }

  function open(panelId: string, withFocus: boolean): void {
    openPanelId.value = panelId;
    closedByShell = false;
    updateLocationHash();

    if (withFocus) {
      focusAfterRender(headingId(panelId));
    }
  }

  function close(panelId: string, returnFocus: boolean): void {
    if (openPanelId.value !== panelId) {
      return;
    }

    openPanelId.value = null;

    if (returnFocus) {
      focusAfterRender(triggerId(panelId));
    }
  }

  /**
   * The trigger whose click is being handled. Recorded on the way down, before
   * `craft-disclosure` toggles itself, so the `craft-show` that follows knows a
   * person opened the panel rather than the hash or the shell.
   */
  let clickedPanelId: string | null = null;

  function onShow(panelId: string): void {
    const withFocus = clickedPanelId === panelId;
    clickedPanelId = null;

    // `craft-disclosure` reopens its target as it disconnects, so a removed
    // panel announces itself one last time.
    if (openPanelId.value !== panelId && isVisible(panelId)) {
      open(panelId, withFocus);
    }
  }

  function onPanelKeydown(event: KeyboardEvent, panelId: string): void {
    if (event.key !== 'Escape' || event.defaultPrevented) {
      return;
    }

    event.stopPropagation();
    close(panelId, true);
  }

  function select(panelId: string): void {
    if (isVisible(panelId)) {
      open(panelId, true);
    }
  }

  function updateLocationHash(): void {
    if (props.syncLocationHash && openPanelId.value) {
      const url = new URL(window.location.href);
      url.hash = openPanelId.value;
      window.history.replaceState(window.history.state, '', url);
    }
  }

  useEventListener(window, 'hashchange', () => {
    if (!props.syncLocationHash) {
      return;
    }

    const panelId = window.location.hash.slice(1);
    if (isVisible(panelId)) {
      open(panelId, false);
    }
  });

  defineExpose({select});
</script>

<template>
  <div class="details-panels">
    <!-- Panels come first: each trigger finds its panel by id when it connects. -->
    <div class="details-panels__content" :hidden="openPanelId === null">
      <section
        v-for="panel in visiblePanels"
        :id="panelElementId(panel.id)"
        :key="panel.id"
        :aria-labelledby="headingId(panel.id)"
        :hidden="openPanelId !== panel.id"
        @keydown="onPanelKeydown($event, panel.id)"
      >
        <DetailsPanelContent
          :panel="panel"
          :heading-id="headingId(panel.id)"
          :component-props="componentProps?.(panel, openPanelId) ?? {}"
          @close="close(panel.id, true)"
        >
          <slot v-if="panel.slot" :name="panel.slot" />
        </DetailsPanelContent>
      </section>
    </div>
    <Teleport defer :to="rail ?? 'body'" :disabled="!rail">
      <div
        class="details-panels__rail"
        :class="{'details-panels__rail--detached': rail}"
        role="group"
        :aria-label="t('Details')"
        :data-open="String(openPanelId !== null)"
      >
        <craft-disclosure
          v-for="panel in visiblePanels"
          :key="panel.id"
          :state="openPanelId === panel.id ? 'expanded' : 'collapsed'"
          @click.capture="clickedPanelId = panel.id"
          @craft-show="onShow(panel.id)"
          @craft-hide="close(panel.id, false)"
        >
          <button
            :id="triggerId(panel.id)"
            type="button"
            class="details-panels__trigger"
            :aria-controls="panelElementId(panel.id)"
          >
            <craft-icon :name="panel.icon" :label="panel.label" />
          </button>
        </craft-disclosure>
      </div>
      <craft-tooltip
        v-for="panel in visiblePanels"
        :key="panel.id"
        :for="triggerId(panel.id)"
        placement="left"
        invoker-relation="label"
      >
        {{ panel.label }}
      </craft-tooltip>
    </Teleport>
  </div>
</template>

<style scoped>
  .details-panels {
    display: flex;
    block-size: 100%;
  }

  .details-panels__content {
    flex: 1;
    min-inline-size: 0;
    block-size: 100%;
    overflow-y: auto;

    > section {
      --c-focus-outline-offset: calc(var(--c-focus-outline-width) * -1);
    }
  }

  .details-panels__content[hidden],
  .details-panels__content > section[hidden] {
    display: none;
  }

  .details-panels__rail {
    display: flex;
    flex-direction: column;
    gap: var(--c-spacing-md);
    box-sizing: border-box;
    inline-size: var(--cp-rail-width);
    padding: var(--c-spacing-md);
    border-inline-start: 1px solid var(--c-color-border-quiet);
    background-color: var(--c-surface-sunken);
  }

  .details-panels__rail--detached {
    border-inline-start: none;
    background-color: transparent;
  }

  .details-panels__trigger {
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

  .details-panels__trigger[aria-expanded='true'] {
    border-color: var(--c-color-accent-border-normal);
    background-color: var(--c-color-accent-fill-quiet);
    color: var(--c-color-accent-on-quiet);
  }

  @media (forced-colors: active) {
    .details-panels__trigger[aria-expanded='true'] {
      border-color: Highlight;
      background-color: Highlight;
      color: HighlightText;
    }
  }
</style>
