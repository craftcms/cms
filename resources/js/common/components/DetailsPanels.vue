<script setup lang="ts">
  /**
   * A screen's details column: a rail of icon buttons, each disclosing one
   * panel. At most one panel is open, and closing it folds the column to the
   * rail. Each screen renders one, for the panels in its registry, which
   * pages and the shell fill with `ScreenPanel`.
   *
   * On a full page the rail is teleported away from the panels, so opening a
   * panel from it moves focus to the panel's heading, and Escape or the
   * panel's close button hands focus back to the rail.
   */
  import '@craftcms/ui/components/disclosure/disclosure';
  import {t} from '@craftcms/ui';
  import {useEventListener, useLocalStorage} from '@vueuse/core';
  import {nextTick, onBeforeUnmount, onMounted, watch} from 'vue';
  import type {ComponentPublicInstance} from 'vue';
  import {
    useIsSlideout,
    useScreenDetailsOverlay,
    useScreenDetailsRail,
  } from '@/common/composables/screen';
  import type {ScreenPanels} from '@/common/composables/screenPanels';
  import DetailsPanelContent from './DetailsPanelContent.vue';
  import {initialOf} from '@/common/utils/initialIcon';
  import type {DetailsPanel} from './detailsPanels';

  export type {DetailsPanel, DetailsPanelStatus} from './detailsPanels';

  const props = withDefaults(
    defineProps<{
      /** The screen's panels. */
      registry: ScreenPanels;
      idPrefix?: string;
      /** Whether the open panel is mirrored in the URL hash. */
      syncLocationHash?: boolean;
    }>(),
    {idPrefix: 'details-panel', syncLocationHash: false}
  );

  const visiblePanels = props.registry.panels;
  const openPanelId = props.registry.openPanelId;

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

  function findPanel(panelId: string | null): DetailsPanel | undefined {
    return visiblePanels.value.find((panel) => panel.id === panelId);
  }

  function isVisible(panelId: string): boolean {
    return findPanel(panelId) !== undefined;
  }

  /**
   * The panel a column opens on: the page's first, so a panel that stays from
   * page to page doesn't take over the page's details, or that one when the
   * page has none.
   */
  function defaultPanelId(): string | null {
    return (
      visiblePanels.value.find((panel) => !panel.persistent)?.id ??
      visiblePanels.value[0]?.id ??
      null
    );
  }

  /**
   * Slideouts don't mirror the open panel in the URL hash, so they remember
   * the panel the person last left open, or that they folded the column,
   * and open the next slideout the same way. A remembered panel this element
   * doesn't have falls back to the first.
   */
  const isSlideout = useIsSlideout();
  const slideoutPanelId = isSlideout
    ? useLocalStorage<string | null>('Slideout.detailsPanel', null)
    : null;

  /**
   * Whether the open panel was opened on purpose: by the person, the URL
   * hash, or a request, rather than by default.
   */
  let openedOnPurpose = false;

  /** The panel to open on when a page's panels arrive. */
  function initialPanelId(): string | null {
    // One that stays from page to page, opened on purpose, stays open.
    if (openedOnPurpose && findPanel(openPanelId.value)?.persistent) {
      return openPanelId.value;
    }

    openedOnPurpose = false;

    if (slideoutPanelId) {
      const remembered = slideoutPanelId.value;

      if (remembered === null) {
        return null;
      }

      return isVisible(remembered) ? remembered : defaultPanelId();
    }

    const hash =
      props.syncLocationHash && window.location.hash
        ? window.location.hash.slice(1)
        : null;

    return hash && isVisible(hash) ? hash : defaultPanelId();
  }

  openPanelId.value = initialPanelId();

  // Each page's panels open as they would on a fresh page.
  watch(props.registry.pageGeneration, () => {
    openPanelId.value = overlaid?.value ? null : initialPanelId();
  });

  // An open panel that goes away falls back to the page's first, or folds
  // the column when the page has none. Until a panel is opened on purpose,
  // the column follows the page's first, as panels arrive in turn.
  watch(visiblePanels, (panels) => {
    if (
      openPanelId.value &&
      !panels.some((panel) => panel.id === openPanelId.value)
    ) {
      openPanelId.value = panels.some((panel) => !panel.persistent)
        ? defaultPanelId()
        : null;
    } else if (openPanelId.value !== null && !openedOnPurpose) {
      openPanelId.value = initialPanelId();
    }
  });

  /**
   * The column folds to its rail once the shell overlays it. The width that
   * happens at is the shell's call, so this follows the flag rather than
   * measuring.
   *
   * A panel the shell closed for lack of room comes back once there's room
   * again; one the person closed stays closed. So this remembers which panel
   * the shell closed, and forgets it as soon as the person opens or closes a
   * panel themselves.
   */
  const overlaid = useScreenDetailsOverlay();
  let panelClosedByShell: string | null = null;

  watch(
    () => overlaid?.value ?? false,
    (isOverlaid) => {
      if (isOverlaid) {
        if (openPanelId.value !== null) {
          panelClosedByShell = openPanelId.value;
          openPanelId.value = null;
        }

        return;
      }

      if (panelClosedByShell !== null && openPanelId.value === null) {
        openPanelId.value = isVisible(panelClosedByShell)
          ? panelClosedByShell
          : defaultPanelId();
      }

      panelClosedByShell = null;
    },
    {immediate: true}
  );

  /** Focuses an element once the panel visibility it depends on has rendered. */
  function focusAfterRender(id: string): void {
    void nextTick(() => document.getElementById(id)?.focus());
  }

  function focusPanel(panelId: string): void {
    const panel = findPanel(panelId);

    void nextTick(() =>
      (
        panel?.initialFocus?.() ?? document.getElementById(headingId(panelId))
      )?.focus()
    );
  }

  function open(panelId: string, withFocus: boolean): void {
    openPanelId.value = panelId;
    openedOnPurpose = true;
    panelClosedByShell = null;
    updateLocationHash();

    if (slideoutPanelId) {
      slideoutPanelId.value = panelId;
    }

    if (withFocus) {
      focusPanel(panelId);
    }
  }

  function close(panelId: string, returnFocus: boolean): void {
    if (openPanelId.value !== panelId) {
      return;
    }

    openPanelId.value = null;
    panelClosedByShell = null;

    if (slideoutPanelId) {
      slideoutPanelId.value = null;
    }

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

  function rememberClick(panelId: string): void {
    clickedPanelId = panelId;
  }

  /**
   * Set once the column starts going away. Every disclosure then disconnects
   * at once.
   */
  let unmounting = false;

  onMounted(() =>
    props.registry.attach({
      open: (panelId, withFocus) => {
        if (isVisible(panelId)) {
          open(panelId, withFocus);
        }
      },
      close,
    })
  );

  onBeforeUnmount(() => {
    unmounting = true;
    props.registry.attach(null);
  });

  function onShow(panelId: string): void {
    const withFocus = clickedPanelId === panelId;
    clickedPanelId = null;

    // `craft-disclosure` reopens its target as it disconnects, so a removed
    // panel announces itself one last time. When all of them disconnect, the
    // last one would otherwise win the URL hash the next page opens.
    if (unmounting) {
      return;
    }

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

  /**
   * Keeps the element a panel's content or header actions go in. One function
   * per panel, so re-rendering doesn't swap the ref, which would let go of the
   * element and move the panel's content out and back.
   */
  type PlaceholderRef = (
    element: Element | ComponentPublicInstance | null
  ) => void;

  const placeholders = new Map<string, PlaceholderRef>();

  function placeholder(
    elements: Map<string, HTMLElement>,
    panelId: string
  ): PlaceholderRef {
    const key = `${elements === props.registry.bodies ? 'body' : 'header'}:${panelId}`;
    let ref = placeholders.get(key);

    if (!ref) {
      ref = (element) => {
        if (element instanceof HTMLElement) {
          elements.set(panelId, element);
        } else if (elements.get(panelId)) {
          elements.delete(panelId);
        }
      };
      placeholders.set(key, ref);
    }

    return ref;
  }
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
          :header-ref="placeholder(registry.headers, panel.id)"
          :body-ref="placeholder(registry.bodies, panel.id)"
          @close="close(panel.id, true)"
        />
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
          @click.capture="rememberClick(panel.id)"
          @craft-show="onShow(panel.id)"
          @craft-hide="close(panel.id, false)"
        >
          <button
            :id="triggerId(panel.id)"
            type="button"
            class="details-panels__trigger"
            :aria-controls="panelElementId(panel.id)"
          >
            <template v-if="panel.initialIcon">
              <span class="initial-icon" aria-hidden="true">{{
                initialOf(panel.label)
              }}</span>
              <span class="sr-only">{{ panel.label }}</span>
            </template>
            <craft-icon v-else :name="panel.icon" :label="panel.label" />
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

    > section {
      display: flex;
      flex-direction: column;
      block-size: 100%;
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
