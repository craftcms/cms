<script setup lang="ts">
  /**
   * The full-page CP shell, reached through `AppLayout`, which picks between
   * this and `SlideoutScreen`. Both implement `ScreenSlots`/`ScreenProps`.
   *
   * The outer chrome is fixed; the main region belongs to `page-main`, whose
   * fallback is the inner chrome (page header, error summary, content/details
   * columns). Pages fill that inner chrome through slots and `LayoutSlot`s.
   * The regions themselves live in `./page/`; this component lays them out and
   * forwards each one its slots.
   *
   * The document scrolls, not the main column, so `CpSidebar` is a sticky,
   * viewport-tall flex child of `.cp__main`.
   */
  import {computed, provide, useTemplateRef, watch} from 'vue';
  import {Head, usePage} from '@inertiajs/vue3';
  import {useElementSize} from '@vueuse/core';
  import {useDetailsOverlay} from '@/common/composables/useDetailsOverlay';
  import CalloutReadOnly from '@/common/components/CalloutReadOnly.vue';
  import CpSidebar from '@/common/components/CpSidebar.vue';
  import CpTopBar from '@/common/components/CpTopBar.vue';
  import FlashMessages from '@/common/components/FlashMessages.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import type {BreadcrumbItem} from '@/common/components/Breadcrumbs.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import {useActionRedirect} from '@/common/composables/useActionRedirect';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {useAppendHtml} from '@/common/composables/useAppendHtml';
  import {useFlash} from '@/common/composables/useFlash';
  import {useFieldHighlight} from '@/common/composables/useFieldHighlight';
  import {provideLayoutSlotRegistry} from '@/common/composables/layoutSlots';
  import {
    provideScreenContext,
    ScreenContentWidthKey,
    ScreenDetailsOverlayKey,
    ScreenShellKey,
  } from '@/common/composables/screen';
  import {
    withNavCrumbMenus,
    withSubnavCrumbs,
  } from '@/common/composables/subnavCrumbs';
  import useCraftData from '@/common/composables/useCraftData';
  import type {FormSaveOptions} from '@/common/types';
  import PassthroughScreen from './PassthroughScreen.vue';
  import ContentDetails from './page/ContentDetails.vue';
  import ContentFooter from './page/ContentFooter.vue';
  import ScreenOverlays from './page/ScreenOverlays.vue';
  import ScreenSkipLinks from './page/ScreenSkipLinks.vue';
  import {useDetailsResizer} from './page/useDetailsResizer';
  import type {ScreenProps, ScreenSlots} from './types';
  import {useScreenRegions} from './useScreenRegions';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {navItemActions} from '@/common/composables/navActions';
  import SecondaryNav from '@/common/components/SecondaryNav.vue';

  const emit = defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  const props = withDefaults(defineProps<ScreenProps>(), {
    form: null,
    defaultFormActions: () => ['saveAndContinueEditing'],
    formAdditionalButtons: () => [],
    contentMaxWidth: false,
    fillViewport: false,
  });

  const slots = defineSlots<ScreenSlots>();

  // The slots each region renders, forwarded only when the page filled them
  // so the regions' own fallbacks still apply.
  const HEADER_SLOTS = ['title'] as const;
  const FOOTER_SLOTS = [
    'content-footer',
    'additional-buttons',
    'submit-button',
  ] as const;
  const SIDEBAR_SLOTS = ['content-sidebar', 'subnav-actions'] as const;

  const headerSlots = computed(() =>
    HEADER_SLOTS.filter((name) => slots[name])
  );
  const footerSlots = computed(() =>
    FOOTER_SLOTS.filter((name) => slots[name])
  );
  const sidebarSlots = computed(() =>
    SIDEBAR_SLOTS.filter((name) => slots[name])
  );

  const registry = provideLayoutSlotRegistry();
  const regions = useScreenRegions(slots, registry);
  provideScreenContext('page');

  // Deep links like `#form-maintenanceMode` point at a field on a long form.
  useFieldHighlight();

  // An inline `<AppLayout>` inside this shell renders transparently rather
  // than stacking a second one.
  provide(ScreenShellKey, PassthroughScreen);

  const page = usePage<{
    title: string;
    readOnly?: boolean;
    crumbs?: Array<BreadcrumbItem> | null;
    subnav?: Array<CraftCms.Cms.Cp.Data.NavItem>;
  }>();

  // Page chrome from props and shared page data.
  const pageTitle = computed(() => props.title?.trim() ?? page.props.title);
  const subnav = computed(() => page.props.subnav ?? []);

  // The secondary nav's trail joins the crumbs, so location reads the same
  // with or without the nav on screen, and each level brings its switcher. The
  // server's own switchers take their menus from the main nav, so a source's
  // switcher is the same on its index and on the pages beneath it.
  const {nav} = useCraftData();
  const crumbs = computed<Array<BreadcrumbItem> | null>(() => {
    const merged = withSubnavCrumbs(
      withNavCrumbMenus(page.props.crumbs ?? [], nav.value ?? []),
      subnav.value
    );

    return merged.length > 0 ? merged : null;
  });
  const readOnly = computed(() => Boolean(page.props.readOnly));

  const hasContextMenu = computed(() => regions.has('context-menu'));
  const hasNotices = computed(() => regions.has('content-notices'));
  // Hidden rather than removed while empty, so its outlets stay in the DOM for
  // page content to teleport into.
  const hasToolbar = computed(
    () =>
      regions.has('content-toolbar') ||
      regions.has('content-toolbar-meta') ||
      regions.has('content-toolbar-actions')
  );
  const hasDetails = computed(() => regions.has('content-details'));
  // Decided here rather than inside `ContentFooter`, so the sticky wrapper
  // around it and the notices can be hidden together rather than left
  // standing empty.
  const hasFooter = computed(
    () =>
      Boolean(props.form) ||
      regions.has('content-footer') ||
      regions.has('additional-buttons')
  );
  const contentConstrained = computed(() => Boolean(props.contentMaxWidth));
  const contentMainStyle = computed(() =>
    typeof props.contentMaxWidth === 'string'
      ? {'--cp-content-max-width': props.contentMaxWidth}
      : undefined
  );
  const hasSidebar = computed(
    () =>
      regions.has('content-sidebar') ||
      regions.has('subnav-actions') ||
      (props.subnavActions?.length ?? 0) > 0 ||
      subnav.value.length > 0
  );

  const contentLayout = useTemplateRef<HTMLElement>('contentLayout');
  const detailsColumn = useTemplateRef<{$el: HTMLElement}>('detailsColumn');
  const {width: contentLayoutWidth} = useElementSize(contentLayout);

  // Lets content decide when it's cramped — the element details tabs fold away
  // below a certain width.
  provide(ScreenContentWidthKey, contentLayoutWidth);
  const detailsOverlaid = useDetailsOverlay(
    () => contentLayout.value,
    contentLayoutWidth
  );
  provide(ScreenDetailsOverlayKey, detailsOverlaid);

  const detailsResizer = useDetailsResizer({
    column: () => detailsColumn.value?.$el,
    layoutWidth: contentLayoutWidth,
    hasSidebar,
    overlaid: detailsOverlaid,
  });

  function save(options?: FormSaveOptions) {
    emit('save', options);
  }

  // Announce flash messages to screen readers.
  const {announce} = useAnnouncer();
  const {errorFlash, successFlash} = useFlash();
  watch(successFlash, (newMessage) => announce(newMessage));
  watch(errorFlash, (newMessage) => announce(newMessage));

  useAppendHtml();

  // Bridge `@craftcms/ui` action redirects into Inertia SPA visits.
  useActionRedirect();
</script>

<template>
  <Head :title="pageTitle" />
  <ScreenSkipLinks
    :has-sidebar="hasSidebar"
    :additional-skip-links="additionalSkipLinks"
  />
  <div
    :class="{'page-screen': true, 'page-screen--fill-viewport': fillViewport}"
  >
    <CpTopBar :crumbs="crumbs" :has-context-menu="hasContextMenu" />
    <div class="cp">
      <div class="cp__sidebar">
        <!-- No props: the sidebar reads the shared store directly, and renders
        the toggle that writes to it. -->
        <CpSidebar />
      </div>
      <div class="cp__main">
        <div class="cp-page">
          <div class="cp-page__header">
            <FlashMessages />
          </div>
          <div class="cp-page__main">
            <slot name="page-main">
              <main id="main" tabindex="-1">
                <form
                  method="post"
                  @submit.prevent="form && save()"
                  class="cp-main"
                >
                  <LayoutSlotOutlet name="error-summary">
                    <slot name="error-summary">
                      <ErrorSummary
                        v-if="form && form.hasErrors"
                        :errors="form.errors"
                      />
                    </slot>
                  </LayoutSlotOutlet>
                  <CalloutReadOnly v-if="readOnly" />
                  <div
                    ref="contentLayout"
                    class="cp-content"
                    :class="{
                      'cp-content--sidebar': hasSidebar,
                      'cp-content--details': hasDetails,
                    }"
                    :style="detailsResizer.style.value"
                  >
                    <div
                      v-show="hasSidebar"
                      id="content-sidebar"
                      tabindex="-1"
                      class="cp-content__sidebar"
                    >
                      <LayoutSlotOutlet name="content-sidebar">
                        <slot name="content-sidebar">
                          <!-- The subnav-actions outlet lives inside this fallback, so a page can
                            teleport `content-sidebar` or `subnav-actions`, never both. -->
                          <SecondaryNav
                            :items="navItemActions(subnav)"
                            :actions="subnavActions"
                          >
                            <template #actions>
                              <LayoutSlotOutlet name="subnav-actions">
                                <slot name="subnav-actions"></slot>
                              </LayoutSlotOutlet>
                            </template>
                          </SecondaryNav>
                        </slot>
                      </LayoutSlotOutlet>
                    </div>

                    <div
                      id="content-main"
                      class="cp-content__main"
                      :style="contentMainStyle"
                    >
                      <div
                        :class="{
                          'cp-content-view': true,
                          'cp-content-view--constrained': contentConstrained,
                        }"
                      >
                        <slot name="content-toolbar">
                          <CpContainer v-show="hasToolbar">
                            <div
                              class="border-b border-b-quiet py-1 divide flex justify-between items-center min-h-(--cp-header-height)"
                            >
                              <LayoutSlotOutlet name="content-toolbar">
                                <div class="flex gap-2 items-center">
                                  <LayoutSlotOutlet name="content-toolbar-meta">
                                    <slot name="content-toolbar-meta"></slot>
                                  </LayoutSlotOutlet>
                                </div>

                                <div class="flex gap-2 items-center">
                                  <LayoutSlotOutlet
                                    name="content-toolbar-actions"
                                  >
                                    <slot name="content-toolbar-actions"></slot>
                                  </LayoutSlotOutlet>
                                </div>
                              </LayoutSlotOutlet>
                            </div>
                          </CpContainer>
                        </slot>

                        <slot name="content-header">
                          <div id="cp-content-header" class="pt-lg pb-md">
                            <CpContainer>
                              <div class="flex items-center justify-between">
                                <LayoutSlotOutlet name="title">
                                  <slot name="title">
                                    <h1 class="text-xl">{{ pageTitle }}</h1>
                                  </slot>
                                </LayoutSlotOutlet>

                                <div class="flex gap-2 items-center">
                                  <LayoutSlotOutlet name="content-actions">
                                    <slot name="content-actions"></slot>
                                  </LayoutSlotOutlet>
                                </div>
                              </div>
                            </CpContainer>
                          </div>
                        </slot>

                        <LayoutSlotOutlet name="content-tabs">
                          <slot name="content-tabs"></slot>
                        </LayoutSlotOutlet>
                        <slot></slot>
                        <div
                          v-show="hasNotices || hasFooter"
                          class="sticky bottom-0 z-sticky bg-default/70 backdrop-blur-md mt-lg"
                        >
                          <!-- `#content-notice` is where legacy `Craft.cp.$noticeContainer`
                        puts its notices, the legacy element editor's included. -->
                          <div
                            v-show="hasNotices"
                            id="content-notice"
                            class="cp-content__notices"
                            role="status"
                          >
                            <LayoutSlotOutlet name="content-notices">
                              <slot name="content-notices"></slot>
                            </LayoutSlotOutlet>
                          </div>
                          <ContentFooter
                            v-show="hasFooter"
                            class="cp-content__footer"
                            :read-only="readOnly"
                            :form="form"
                            :default-form-actions="defaultFormActions"
                            :form-actions="formActions"
                            :form-additional-actions="formAdditionalActions"
                            :form-additional-buttons="formAdditionalButtons"
                            :submit-button-label="submitButtonLabel"
                            :save-disabled="saveDisabled"
                            :contained="contentConstrained"
                            @save="save"
                          >
                            <template
                              v-for="name in footerSlots"
                              :key="name"
                              #[name]
                            >
                              <slot :name="name"></slot>
                            </template>
                          </ContentFooter>
                        </div>
                      </div>
                    </div>
                    <aside v-show="hasDetails" class="cp-content__details">
                      <div class="sticky top-0 h-screen">
                        <ContentDetails
                          ref="detailsColumn"
                          :resizer="detailsResizer"
                        >
                          <template
                            v-if="slots['content-details']"
                            #content-details
                          >
                            <slot name="content-details"></slot>
                          </template>
                        </ContentDetails>
                      </div>
                    </aside>
                  </div>
                </form>
              </main>
            </slot>
          </div>

          <footer class="cp-page__footer">
            <LayoutSlotOutlet name="page-footer">
              <CpContainer>
                <slot name="page-footer"></slot>
              </CpContainer>
            </LayoutSlotOutlet>
          </footer>
        </div>
      </div>
    </div>
  </div>

  <ScreenOverlays :debug="debug" />
</template>

<style scoped lang="postcss">
  /**
Main App shell
 */
  .cp {
    display: grid;
    background-color: var(--c-surface-sunken);
    border-start-start-radius: var(--c-radius-xl);
    border-start-end-radius: var(--c-radius-xl);
    overflow: clip;

    @media (width >= var(--breakpoint-lg)) {
      grid-template-columns: auto minmax(0, 1fr);
    }
  }

  .cp__main {
    container: cp-main / inline-size;
  }

  main,
  .cp-main {
    height: 100%;
  }

  .page-screen {
    background-color: var(--c-surface-header);
  }

  /* The top bar keeps its height and the shell takes the rest. */
  .page-screen--fill-viewport {
    display: flex;
    flex-direction: column;
    height: calc(100dvh - var(--cp-debug-bar-height, 0px));

    .cp {
      display: flex;
      flex: 1;
      min-height: 0;
    }

    .cp__main {
      flex: 1;
      min-width: 0;
    }
  }

  /**
Page
 */
  .cp-page {
    height: 100%;
    display: grid;
    grid-template-rows: auto minmax(0, 1fr) auto;
  }

  .cp-page__footer {
    position: sticky;
    inset-block-end: 0;
  }

  /**
Content
 */
  .cp-content {
    background-color: var(--c-surface-default);
    display: grid;
    height: 100%;
    max-width: 100vw;
    /* Three columns at every width; the nav spans them while it's stacked above
       the content, and an area nothing occupies collapses to nothing. */
    grid-template-areas: 'sidebar sidebar sidebar' '. main details';
    grid-template-columns:
      auto
      minmax(var(--cp-content-main-min), 1fr)
      var(--cp-content-details-track);
    /* Zero until a panel claims the column. */
    --cp-content-details-track: 0;
    /* Flipped by the fold queries below; `useDetailsOverlay` reads it back. */
    --cp-details-overlay: 0;
    /* Just the tab rail, for when the panels are folded away. */
    --cp-content-details-rail: var(--cp-rail-width);
    /* The column is an inline-size container, so it can't size to its contents:
       every state needs a definite width. The resizer overrides this inline. */
    --cp-content-details-width: clamp(
      var(--cp-content-details-rail),
      90vw,
      calc(400rem / 16)
    );
    /* How far the panel gives before it folds to the rail instead. */
    --cp-content-details-min: calc(280rem / 16);
    /* A floor only matters against a panel; the fold queries drop it back to
       nothing once the panel is overlaying rather than sharing the row. */
    --cp-content-main-min: 0px;
    @media (width >= var(--breakpoint-lg)) {
      grid-template-areas: 'sidebar main details';
    }
  }

  /* Kept out of `.cp-content`'s nesting so the fold queries below, which match
     on one class, can still drop these. */
  .cp-content--details {
    --cp-content-main-min: calc(600rem / 16);
    /* The panel's width while it shares the row. A range, so the column gives
       ground as the page narrows rather than pushing the content past its floor. */
    --cp-content-details-track: minmax(
      var(--cp-content-details-min),
      var(--cp-content-details-width)
    );
  }

  .cp-content--sidebar.cp-content--details {
    /* Less of it to go round with the secondary nav taking a share too. */
    --cp-content-details-max: 40cqi;
  }

  .cp-content--sidebar .cp-content__sidebar {
    min-width: calc(250rem / 16);
    border-block-end: 1px solid var(--c-color-border-quiet);
    padding: var(--c-spacing-md);
  }

  .cp-content--details:has(.cp-content__details craft-tabs[collapsed]) {
    /* Definite widths, not `max-content` — see the container note above. The
       track goes with it, or its range would keep reserving the minimum. */
    --cp-content-details-width: var(--cp-content-details-rail) !important;
    --cp-content-details-track: var(--cp-content-details-rail) !important;
    /* Nothing to drag a width against while it's folded to the rail. */
    --resize-handle-display: none;

    .cp-content__details {
      border-inline-start: none;
    }
  }

  .cp-content__sidebar {
    grid-area: sidebar;
    border-inline-end: 1px solid var(--c-color-border-quiet);
  }

  .cp-content__main {
    grid-area: main;
    overflow: clip;
  }

  .cp-content__details {
    grid-area: details;
    z-index: var(--c-layer-sticky);
    border-inline-start: 1px solid var(--c-color-border-quiet);
    background-color: var(--c-surface-default);
    /* A dragged width outlives the layout it was dragged in, so cap it here. */
    max-inline-size: var(--cp-content-details-max, 90cqi);
    container: content-details / inline-size;
    /* No width of its own while it has a column: stretching to the track is what
       lets the track's range shrink it, and it survives the containment above. */
    justify-self: stretch;
  }

  .cp-content__footer {
    min-height: var(--cp-footer-height);
    display: grid;
    align-content: center;
    border-block-start: 1px solic var(--c-color-border-quiet);
    padding-block: var(--c-spacing-md);
  }

  /*
 * Below the sum of the content's floor (600px, `.cp-content-view`) and the
 * panel's (280px) one of them would be squeezed past it, so the panel folds to
 * its rail and opens over the content instead. A query condition can't read a
 * custom property, so the sum is written out; keep it in step with the floors
 * and with the wider fold below.
 */
  @container cp-main (width < 880px) {
    .cp-content--details {
      --cp-details-overlay: 1;
      /* Only the rail is beside the content, so it keeps no floor of its own. */
      --cp-content-main-min: 0px;
      /* Only the rail is reserved, so the panel opens over the content. */
      --cp-content-details-track: var(--cp-content-details-rail) !important;
      /* Past the sidebar variant's tighter cap, which is for sharing the row. */
      --cp-content-details-max: 80cqi !important;
    }

    /* No track to stretch to, so the panel names its own width and hangs off
       the trailing edge. */
    .cp-content--details > .cp-content__details {
      inline-size: var(--cp-content-details-width);
      justify-self: end;
    }
  }

  /* The same fold 250px sooner, for the one case where a third column shares
     the row: a secondary nav beside the content, counted from its minimum. */
  @media (width >= var(--breakpoint-lg)) {
    @container cp-main (width < 1130px) {
      .cp-content--sidebar.cp-content--details {
        --cp-details-overlay: 1;
        --cp-content-main-min: 0px;
        --cp-content-details-track: var(--cp-content-details-rail) !important;
        --cp-content-details-max: 80cqi !important;
      }

      .cp-content--sidebar.cp-content--details > .cp-content__details {
        inline-size: var(--cp-content-details-width);
        justify-self: end;
      }
    }
  }

  /* Nothing to drag a column against while the panel overlays the content, so
     the handle goes and opening takes most of the width. The collapsed rule is
     more specific, so the rail still wins until something opens it. */
  @container cp-main (width < 768px) {
    .cp-content--details {
      --resize-handle-display: none;
      --cp-content-details-max: 90cqi !important;
      --cp-content-details-width: 90cqi !important;
    }
  }

  /**
Content view
 */
  .cp-content-view {
    @media (width >= var(--breakpoint-md)) {
      /* The content's half of the fold sum above. */
      min-width: calc(600rem / 16);
    }
  }

  .cp-content-view--constrained {
    /* The default lives in `cp.css`; a page passing a length overrides it
       inline through `contentMaxWidth`. */
    max-width: var(--cp-content-max-width);
    margin: 0 auto;
  }
</style>
