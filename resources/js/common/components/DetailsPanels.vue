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
  import '@craftcms/ui/components/dialog/dialog';
  import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    useTemplateRef,
    watch,
  } from 'vue';
  import type {ComponentPublicInstance} from 'vue';
  import {
    useIsSlideout,
    useScreenDetailsOverlay,
    useScreenDetailsRail,
  } from '@/common/composables/screen';
  import type {ScreenPanels} from '@/common/composables/screenPanels';
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

  /**
   * On a page, a panel that overlays the content is modal: the panels move
   * into a `craft-dialog`, which makes the rest of the page inert, stops it
   * scrolling, and closes on Escape or a click outside. A slideout's panels
   * stay as they are.
   */
  const isModal = computed(() => !isSlideout && (overlaid?.value ?? false));
  const dialogBody = useTemplateRef<HTMLElement>('dialogBody');

  const openPanel = computed(() => findPanel(openPanelId.value));

  /** A panel to move focus into once the dialog has opened, and taken focus itself. */
  let focusOnDialogShow: string | null = null;

  // Menus and popovers in a panel fire `craft-show` and `craft-hide` too, and
  // theirs bubble up through the dialog, so only the dialog's own count.
  function onDialogShow(event: Event): void {
    if (event.target !== event.currentTarget) {
      return;
    }

    const panelId = focusOnDialogShow;
    focusOnDialogShow = null;

    if (panelId !== null) {
      focusPanelNow(panelId);
    }
  }

  /**
   * The dialog closed itself, on Escape or a click outside. It hands focus
   * back to where it was when it opened.
   */
  function onDialogHide(event: Event): void {
    if (event.target !== event.currentTarget) {
      return;
    }

    if (openPanelId.value !== null) {
      close(openPanelId.value, false);
    }
  }

  /** The panel last opened, whose trigger takes focus when nothing else can. */
  let lastOpenPanelId: string | null = null;

  watch(openPanelId, (panelId) => {
    if (panelId !== null) {
      lastOpenPanelId = panelId;
    }
  });

  /**
   * Where focus goes as the dialog closes: back to what opened it, or, when
   * that wasn't something focusable, such as a tap that doesn't focus on iOS,
   * to the panel's trigger.
   */
  function dialogReturnFocus(opener: HTMLElement | null): HTMLElement | null {
    if (opener && opener !== document.body && opener.isConnected) {
      return opener;
    }

    return lastOpenPanelId
      ? document.getElementById(triggerId(lastOpenPanelId))
      : null;
  }

  // Leaving the dialog for the column, as the page widens, keeps focus in the
  // open panel.
  watch(isModal, async (modal, wasModal) => {
    if (modal || !wasModal || openPanelId.value === null) {
      return;
    }

    await nextTick();

    if (!document.activeElement || document.activeElement === document.body) {
      focusPanelNow(openPanelId.value);
    }
  });
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

  function focusPanelNow(panelId: string): void {
    const panel = findPanel(panelId);

    (
      panel?.initialFocus?.() ??
      (panel && document.getElementById(panel.headingId))
    )?.focus();
  }

  function focusPanel(panelId: string): void {
    // The dialog moves focus into itself as it opens, so this waits for that.
    if (isModal.value) {
      focusOnDialogShow = panelId;

      return;
    }

    void nextTick(() => focusPanelNow(panelId));
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
   * Keeps the element a panel goes in. One function per panel, so re-rendering
   * doesn't swap the ref, which would let go of the element and move the
   * panel out and back.
   */
  type SectionRef = (element: Element | ComponentPublicInstance | null) => void;

  const sectionRefs = new Map<string, SectionRef>();

  function sectionRef(panelId: string): SectionRef {
    let ref = sectionRefs.get(panelId);

    if (!ref) {
      ref = (element) => {
        if (element instanceof HTMLElement) {
          props.registry.sections.set(panelId, element);
        } else if (props.registry.sections.get(panelId)) {
          props.registry.sections.delete(panelId);
        }
      };
      sectionRefs.set(panelId, ref);
    }

    return ref;
  }
</script>

<template>
  <div class="details-panels">
    <!-- Panels come first: each trigger finds its panel by id when it connects.
         While modal, they're moved into the dialog rather than re-rendered, so
         the content each `ScreenPanel` moved into them stays put. -->
    <Teleport :to="dialogBody ?? 'body'" :disabled="!isModal || !dialogBody">
      <div
        class="details-panels__content"
        :hidden="openPanelId === null || undefined"
      >
        <section
          v-for="panel in visiblePanels"
          :id="panelElementId(panel.id)"
          :key="panel.id"
          :ref="sectionRef(panel.id)"
          :aria-labelledby="panel.headingId"
          :hidden="openPanelId !== panel.id"
          @keydown="onPanelKeydown($event, panel.id)"
        ></section>
      </div>
    </Teleport>
    <craft-dialog
      v-if="isModal"
      class="details-panels__dialog"
      :label="openPanel?.label ?? ''"
      no-header
      close-on-outside-click
      .opened="openPanelId !== null"
      .returnFocus="dialogReturnFocus"
      @craft-show="onDialogShow"
      @craft-hide="onDialogHide"
    >
      <div ref="dialogBody" class="details-panels__dialog-body"></div>
    </craft-dialog>
    <!-- Open, the column folds out for the panel; in the dialog, it stays
         folded. -->
    <Teleport defer :to="rail ?? 'body'" :disabled="!rail">
      <div
        class="details-panels__rail"
        :class="{'details-panels__rail--detached': rail}"
        role="group"
        :aria-label="t('Details')"
        :data-open="String(openPanelId !== null && !isModal)"
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

  /* A sheet along the inline end, as tall as what's on screen, set in from
     the edges it sits against. */
  .details-panels__dialog {
    --_inset: var(--c-spacing-md);
    --c-dialog-inline-size: min(calc(90vw - var(--_inset)), calc(400rem / 16));
    --c-dialog-min-inline-size: 0;
    --c-dialog-max-inline-size: calc(90vw - var(--_inset));
    --c-dialog-block-size: calc(
      var(--cp-visible-viewport-height, 100dvh) -
        var(--cp-debug-bar-height, 0px) - 2 * var(--_inset)
    );
    --c-dialog-max-block-size: var(--c-dialog-block-size);
  }

  /*
    With the keyboard open, iOS pans the screen behind the dialog with any
    drag, however the page is locked, and too early for a script to stop it.
    No touch on the dialog or its backdrop pans; scroll areas inside are scroll
    containers of their own, so dragging in them still scrolls them.
  */
  .details-panels__dialog::part(dialog) {
    touch-action: none;
    margin-block: calc(var(--cp-visible-viewport-top, 0px) + var(--_inset)) auto;
    margin-inline: auto var(--_inset);
  }

  /*
    The page dims itself instead: with the keyboard open, iOS shows the page
    past where a backdrop reaches, through its translucent toolbar.
  */
  .details-panels__dialog::part(dialog)::backdrop {
    background-color: transparent;
  }

  .details-panels__dialog::part(body) {
    display: flex;
    padding: 0;
    overflow: hidden;
  }

  .details-panels__dialog-body {
    display: flex;
    flex: 1;
    min-inline-size: 0;
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
    padding-block-start: calc(var(--c-spacing-md) + 2px);
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
