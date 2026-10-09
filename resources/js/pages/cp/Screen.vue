<script setup lang="ts">
  /**
   * The fallback screen for `CpScreenResponse`s that have no `inertiaPage()`.
   *
   * Draws the server-rendered HTML fragments the response already carries —
   * the same ones the legacy jQuery slideout consumes — into the shell's
   * slots. That means every CP screen works in a Vue slideout before it's been
   * ported to a real Vue page, and porting one is just adding `inertiaPage()`.
   */
  import '../../../css/legacy.css';
  import {computed, onBeforeUnmount, onMounted, watch} from 'vue';
  import CpContainer from '@/common/components/CpContainer.vue';
  import HtmlFragmentRenderer from '@/common/components/HtmlFragmentRenderer.vue';
  import LayoutSlot from '@/common/components/LayoutSlot.vue';
  import {useScreenContentReady} from '@/common/composables/screen';
  import {useAppLayout} from '@/common/composables/useAppLayout';

  const props = defineProps<{
    content?: string | null;
    details?: string | null;
    tabs?: string | null;
    contentNotice?: string | null;
    errorSummary?: string | null;
    toolbar?: string | null;
    sidebar?: string | null;
    bodyClass?: Array<string> | string | null;
    headHtml?: string | null;
    bodyHtml?: string | null;
  }>();

  /**
   * Only the main content carries `headHtml`/`bodyHtml`: those are per-response,
   * not per-fragment, and appending them once avoids loading a screen's assets
   * several times over.
   */
  const contentFragment = computed(() =>
    props.content
      ? {
          html: props.content,
          headHtml: props.headHtml ?? '',
          bodyHtml: props.bodyHtml ?? '',
        }
      : null
  );

  const fragment = (html?: string | null) =>
    html ? {html, headHtml: '', bodyHtml: ''} : null;

  /**
   * Screens whose behavior is driven from JS — the element editor, notably —
   * wait on this before wiring themselves up.
   *
   * Every fragment has to be in the document first, not just the content one:
   * the element editor takes over the tabs, which land in their own outlet on
   * their own schedule. So the signal is held back until each renderer that's
   * going to report in has.
   */
  const signalReady = useScreenContentReady();

  const expected = computed(
    () =>
      [
        props.tabs,
        props.contentNotice,
        props.errorSummary,
        props.toolbar,
        props.sidebar,
        props.details,
        contentFragment.value,
      ].filter(Boolean).length
  );

  let ready = 0;

  /**
   * Releases legacy ready-JS queued by the `LegacyReadyShim`.
   *
   * The shim only exists on bridged screens, whose markup Vue mounts after the
   * document has parsed — so `DOMContentLoaded` has already been and gone by
   * the time a plugin's boot code could have found its elements. Calling this
   * once every fragment is in the DOM is that moment.
   */
  /**
   * Re-points `Craft.cp` at the shell's DOM before any ready-JS runs.
   *
   * `Craft.cp` caches these when `cp.js` loads — `CP.js` does
   * `this.$main = $('#main')` and `this.$primaryForm = $('#main-form')` — which
   * on a bridged screen is before Vue has mounted anything, so both cached empty
   * sets and stayed that way.
   *
   * Everything downstream keys off them. `Craft.ElementEditor` decides
   * `isFullPage` by testing its container against exactly these two, and from
   * that derives its content container, its sidebar and its form host. With both
   * empty, a full-page editor behaved like a slideout: no form host, so every
   * field-layout refresh threw and a nested element — an address, say — could
   * never be added.
   *
   * Only filled in while still empty, so a screen that set them itself keeps
   * what it chose.
   */
  function resyncLegacyCp(): void {
    const craft = (window as any).Craft;
    const jquery = (window as any).jQuery;

    if (!craft?.cp || !jquery) {
      return;
    }

    if (!craft.cp.$main?.length) {
      craft.cp.$main = jquery('#main');
    }

    if (!craft.cp.$primaryForm?.length) {
      craft.cp.$primaryForm = jquery('#main-form');
    }
  }

  function releaseLegacyReadyJs(): void {
    resyncLegacyCp();
    (window as any).Craft?.flushReady?.();
  }

  function fragmentReady(): void {
    if (++ready >= expected.value) {
      releaseLegacyReadyJs();
      signalReady();
    }
  }

  /**
   * Legacy screen JS finds its working area by the ids `_layouts/cp` used to
   * put on every page — `Craft.createElementIndex()` is handed
   * `$('#page-container')` with `toolbarSelector: '#toolbar'`. The shell puts
   * them back for this screen only.
   */
  useAppLayout({legacyIds: true});

  /**
   * Marks the document while unported markup is on screen.
   *
   * This screen renders server-rendered legacy HTML rather than Vue components,
   * so styles sometimes need to target that context specifically. The class is
   * the hook for it, and it's deliberately distinct from the `cp-legacy` that
   * `base.twig` puts on Twig-rendered pages — that marks a legacy *document*,
   * this marks legacy content inside the new shell.
   */
  const LEGACY_CONTEXT_CLASS = 'cp-legacy-screen';

  /**
   * The classes this screen puts on `<body>`: the legacy-context marker above,
   * plus whatever the screen itself asked for through `bodyClass`.
   */
  const bodyClasses = computed(() => {
    const value = props.bodyClass ?? [];
    const asked = Array.isArray(value) ? value : value.split(/\s+/);

    return [...new Set([LEGACY_CONTEXT_CLASS, ...asked.filter(Boolean)])];
  });

  let appliedBodyClasses: Array<string> = [];

  watch(
    bodyClasses,
    (classes) => {
      document.body.classList.remove(...appliedBodyClasses);
      document.body.classList.add(...classes);
      appliedBodyClasses = classes;
    },
    {immediate: true}
  );

  onMounted(() => {
    if (!expected.value) {
      releaseLegacyReadyJs();
      signalReady();
    }
  });

  onBeforeUnmount(() => {
    document.body.classList.remove(...appliedBodyClasses);
    appliedBodyClasses = [];
  });

  /**
   * Boots the legacy tab controller over the injected tabs fragment.
   *
   * `Craft.Tabs` is never auto-initialized — it's constructed explicitly, the
   * way `cp-screen-slideout` does for the same fragment. Without this the tab
   * strip renders but selecting a tab does nothing, because nothing is
   * toggling `.hidden` on the panes the legacy content declares.
   */
  let tabManager: any = null;

  function bootTabs(element: HTMLElement): void {
    fragmentReady();

    const Tabs = (window as any).Craft?.Tabs;

    if (!Tabs) {
      return;
    }

    const pane = (element.querySelector('.pane-tabs') ??
      element) as HTMLElement;

    tabManager = new Tabs(pane);

    const paneFor = (ev: any): HTMLElement | null => {
      const href = ev?.$tab?.attr?.('href');

      return href ? document.querySelector<HTMLElement>(href) : null;
    };

    tabManager.on('deselectTab', (ev: any) =>
      paneFor(ev)?.classList.add('hidden')
    );
    tabManager.on('selectTab', (ev: any) =>
      paneFor(ev)?.classList.remove('hidden')
    );
  }

  onBeforeUnmount(() => {
    tabManager?.destroy?.();
    tabManager = null;
  });
</script>

<template>
  <LayoutSlot v-if="tabs" name="content-tabs">
    <HtmlFragmentRenderer :fragment="fragment(tabs)" @ready="bootTabs" />
  </LayoutSlot>

  <LayoutSlot v-if="contentNotice" name="content-notices">
    <HtmlFragmentRenderer
      :fragment="fragment(contentNotice)"
      @ready="fragmentReady"
    />
  </LayoutSlot>

  <LayoutSlot v-if="errorSummary" name="error-summary">
    <HtmlFragmentRenderer
      :fragment="fragment(errorSummary)"
      @ready="fragmentReady"
    />
  </LayoutSlot>

  <LayoutSlot v-if="toolbar" name="content-toolbar-meta">
    <HtmlFragmentRenderer
      :fragment="fragment(toolbar)"
      @ready="fragmentReady"
    />
  </LayoutSlot>

  <!-- `#sidebar.sidebar` came from the document's own wrapper, not from the
       fragment, and legacy JS looks for `.sidebar:first` — an element index
       finds its source nav and `#source-actions` inside it. -->
  <LayoutSlot v-if="sidebar" name="content-sidebar">
    <HtmlFragmentRenderer
      id="sidebar"
      class="sidebar"
      :fragment="fragment(sidebar)"
      @ready="fragmentReady"
    />
  </LayoutSlot>

  <LayoutSlot v-if="details" name="content-details">
    <HtmlFragmentRenderer
      :fragment="fragment(details)"
      @ready="fragmentReady"
    />
  </LayoutSlot>

  <!-- The shell's default content slot is bare, so a page supplies its own
       gutters. Legacy markup can't, hence the container here — without it the
       screen's content runs to the edges while the rest of the CP is inset. -->
  <CpContainer v-if="contentFragment">
    <HtmlFragmentRenderer :fragment="contentFragment" @ready="fragmentReady" />
  </CpContainer>
</template>
