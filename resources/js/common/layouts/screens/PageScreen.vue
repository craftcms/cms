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
  import {computed, provide, useTemplateRef} from 'vue';
  import {Head, usePage} from '@inertiajs/vue3';
  import {
    useElementBounding,
    useElementSize,
    useWindowSize,
  } from '@vueuse/core';
  import {useVisibleHeight} from '@/common/composables/useVisibleHeight';
  import {useDebugBarHeight} from '@/common/composables/useDebugBarHeight';
  import {useDetailsOverlay} from '@/common/composables/useDetailsOverlay';
  import CalloutReadOnly from '@/common/components/CalloutReadOnly.vue';
  import CpSidebar from '@/common/components/CpSidebar.vue';
  import CpTopBar from '@/common/components/CpHeaderBar.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import type {BreadcrumbItem} from '@/common/components/Breadcrumbs.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import {useActionRedirect} from '@/common/composables/useActionRedirect';
  import {useAppendHtml} from '@/common/composables/useAppendHtml';
  import {useFieldHighlight} from '@/common/composables/useFieldHighlight';
  import {provideLayoutSlotRegistry} from '@/common/composables/layoutSlots';
  import {
    provideScreenContext,
    ScreenContentWidthKey,
    ScreenDetailsOverlayKey,
    ScreenDetailsRailKey,
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
  import {DEFAULT_FORM_ACTIONS} from './formActionItems';
  import CpContainer from '@/common/components/CpContainer.vue';
  import {navItemActions} from '@/common/composables/navActions';
  import SecondaryNav from '@/common/components/SecondaryNav.vue';

  const emit = defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  const props = withDefaults(defineProps<ScreenProps>(), {
    form: null,
    defaultFormActions: () => DEFAULT_FORM_ACTIONS,
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
    'primary-action',
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
    primaryAction?: string | null;
    crumbs?: Array<BreadcrumbItem> | null;
    subnav?: Array<CraftCms.Cms.Cp.Data.NavItem>;
  }>();

  // Page chrome from props and shared page data.
  const pageTitle = computed(() => props.title?.trim() ?? page.props.title);
  // A page that knows its own nav — an index listing its sources — sets it
  // through `useAppLayout`; everything else takes the server's page prop.
  const subnav = computed(() => props.subnav ?? page.props.subnav ?? []);

  // The secondary nav's trail joins the crumbs, so location reads the same
  // with or without the nav on screen, and each level brings its switcher. The
  // server's own switchers take their menus from the main nav, so a source's
  // switcher is the same on its index and on the pages beneath it.
  const {nav, siteCrumb} = useCraftData();
  const crumbs = computed<Array<BreadcrumbItem> | null>(() => {
    const merged = withSubnavCrumbs(
      withNavCrumbMenus(page.props.crumbs ?? [], nav.value ?? [], page.url),
      subnav.value
    );

    // The site leads the trail on every screen, not just the ones that know
    // they're site-specific: which site you're editing frames everything
    // below it. Only present on a multi-site install.
    //
    // A screen that writes its own stands down the shared one — an element
    // editor's lists just the sites that element propagates to, which is the
    // better answer there.
    const ownsSiteCrumb = merged.some((crumb) => crumb.id === 'site-crumb');
    const trail =
      siteCrumb.value && !ownsSiteCrumb ? [siteCrumb.value, ...merged] : merged;

    return trail.length > 0 ? trail : null;
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
  const hasTabs = computed(() => regions.has('content-tabs'));
  // Decided here rather than inside `ContentFooter`, so the sticky wrapper
  // around it and the notices can be hidden together rather than left
  // standing empty.
  const hasFooter = computed(
    () =>
      Boolean(props.form) ||
      Boolean(props.fullPageForm) ||
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

  // The top bar scrolls away with the page, so the sidebar and the details
  // pane are only as tall as the viewport below whatever's still showing of it.
  const topBar = useTemplateRef<{$el: HTMLElement}>('topBar');
  const topBarVisibleHeight = useVisibleHeight(() => topBar.value?.$el);
  // The details pane and the sidebar both stop short of Laravel Debugbar.
  const debugBarHeight = useDebugBarHeight();
  const contentLayout = useTemplateRef<HTMLElement>('contentLayout');
  const detailsColumn = useTemplateRef<{$el: HTMLElement}>('detailsColumn');
  const {width: contentLayoutWidth} = useElementSize(contentLayout);
  // Where the details column sits in the viewport: how far down it starts until
  // the pane sticks, and how much room its end leaves at the bottom once the
  // page is scrolled to it. The pane is sized to fit between the two.
  // The `aside`, which spans the row, rather than the pane's own contents,
  // whose height follows from this.
  const detailsAside = useTemplateRef<HTMLElement>('detailsAside');
  const {top: detailsColumnTop, bottom: detailsColumnBottom} =
    useElementBounding(detailsAside);
  const {height: windowHeight} = useWindowSize();

  const pageScreenStyle = computed(() => ({
    '--cp-top-bar-visible-height': `${topBarVisibleHeight.value}px`,
    '--cp-details-offset': `${Math.max(0, detailsColumnTop.value)}px`,
    '--cp-details-end-gap': `${Math.max(
      0,
      windowHeight.value -
        (debugBarHeight.value ?? 0) -
        detailsColumnBottom.value
    )}px`,
    ...(debugBarHeight.value === null
      ? {}
      : {'--cp-debug-bar-height': `${debugBarHeight.value}px`}),
  }));

  // Lets content decide when it's cramped — the element details panels fold away
  // below a certain width.
  provide(ScreenContentWidthKey, contentLayoutWidth);
  const detailsOverlaid = useDetailsOverlay(
    () => contentLayout.value,
    contentLayoutWidth
  );
  provide(ScreenDetailsOverlayKey, detailsOverlaid);
  // The details rail renders beside the panel.
  provide(ScreenDetailsRailKey, '#cp-details-rail');

  const detailsResizer = useDetailsResizer({
    column: () => detailsColumn.value?.$el,
    layoutWidth: contentLayoutWidth,
    hasSidebar,
    overlaid: detailsOverlaid,
  });

  function save(options?: FormSaveOptions) {
    emit('save', options);
  }

  /**
   * A Vue page drives saving through its Inertia form, so the native submit is
   * cancelled. A bridged legacy screen has no such form: it posts to the
   * current URL, and the hidden `action` input in its own content names the
   * controller action — the same contract Craft 5's page form used.
   */
  function onSubmit(event: Event): void {
    if (props.form) {
      event.preventDefault();
      save();
    }
  }

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
    :style="pageScreenStyle"
  >
    <CpTopBar
      ref="topBar"
      :crumbs="crumbs"
      :has-context-menu="hasContextMenu"
    />
    <div class="cp">
      <div class="cp__sidebar">
        <!-- No props: the sidebar reads the shared store directly, and renders
        the toggle that writes to it. -->
        <CpSidebar />
      </div>
      <div class="cp__main">
        <div class="cp-page">
          <div class="cp-page__header"></div>
          <div class="cp-page__main">
            <div
              class="cp-body"
              :class="{
                'cp-body--sidebar': hasSidebar,
                'cp-body--details': hasDetails,
              }"
            >
              <!-- Wraps the `page-main` slot, so a page that takes it over still
              sits in the panel. -->
              <div class="cp-body__panel">
                <slot name="page-main">
                  <main id="main" tabindex="-1">
                    <form
                      method="post"
                      accept-charset="UTF-8"
                      novalidate
                      :id="legacyIds ? 'main-form' : undefined"
                      @submit="onSubmit"
                      class="cp-main"
                    >
                      <div
                        ref="contentLayout"
                        :id="legacyIds ? 'page-container' : undefined"
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
                            :class="{
                              'cp-content-view': true,
                              'cp-content-view--constrained':
                                contentConstrained,
                            }"
                          >
                            <slot name="content-toolbar">
                              <CpContainer v-show="hasToolbar">
                                <div
                                  class="border-b border-b-quiet py-1 divide flex justify-between items-center min-h-(--cp-header-height)"
                                >
                                  <LayoutSlotOutlet name="content-toolbar">
                                    <div
                                      :id="legacyIds ? 'toolbar' : undefined"
                                      class="flex gap-2 items-center"
                                    >
                                      <LayoutSlotOutlet
                                        name="content-toolbar-meta"
                                      >
                                        <slot
                                          name="content-toolbar-meta"
                                        ></slot>
                                      </LayoutSlotOutlet>
                                    </div>

                                    <div class="flex gap-2 items-center">
                                      <LayoutSlotOutlet
                                        name="content-toolbar-actions"
                                      >
                                        <slot
                                          name="content-toolbar-actions"
                                        ></slot>
                                      </LayoutSlotOutlet>
                                    </div>
                                  </LayoutSlotOutlet>
                                </div>
                              </CpContainer>
                            </slot>

                            <slot name="content-header">
                              <div id="cp-content-header" class="pt-lg pb-md">
                                <CpContainer>
                                  <div
                                    class="flex flex-wrap gap-md items-center justify-between"
                                  >
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

                            <CpContainer v-show="hasTabs">
                              <div>
                                <LayoutSlotOutlet name="content-tabs">
                                  <slot name="content-tabs"></slot>
                                </LayoutSlotOutlet>
                              </div>
                            </CpContainer>
                            <!-- `#content` is how the legacy element editor finds its form
                            host: `$('#content').find('craft-entry-field-layout-form')`.
                            Without it `formHost` is undefined and every field-layout
                            refresh throws, which stopped a nested element — an address,
                            say — from ever being added on a bridged screen. -->
                            <div v-if="legacyIds" id="content" class="contents">
                              <slot></slot>
                            </div>
                            <slot v-else></slot>
                          </div>
                          <!-- Outside the content view, so its rule spans the pane even when
                          the view is constrained; the row itself keeps to the content's
                          column through `contained`. -->
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
                            <div class="cp-content__footer">
                              <ContentFooter
                                v-show="hasFooter"
                                :read-only="readOnly"
                                :form="form"
                                :default-form-actions="defaultFormActions"
                                :form-actions="formActions"
                                :form-additional-actions="formAdditionalActions"
                                :form-additional-buttons="formAdditionalButtons"
                                :full-page-form="fullPageForm"
                                :submit-button-label="submitButtonLabel"
                                :primary-action-html="page.props.primaryAction"
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
                        <!-- Paired ids for the legacy editor's `$('#details .details')`. -->
                        <aside
                          ref="detailsAside"
                          v-show="hasDetails"
                          :id="legacyIds ? 'details' : undefined"
                          class="cp-content__details"
                        >
                          <div
                            class="cp-content__details-pane"
                            :class="{details: legacyIds}"
                          >
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
              <div
                v-show="hasDetails"
                id="cp-details-rail"
                class="cp-details-rail"
              ></div>
            </div>
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
Shell
 */
  /* The top bar keeps its height and the shell takes at least the rest. Docked,
     the sidebar's own height does this; floating, nothing else would. */
  .page-screen {
    display: flex;
    flex-direction: column;
    min-height: calc(100dvh - var(--cp-debug-bar-height, 0px));
  }

  .cp {
    flex: 1;
    display: grid;
    /* The floating sidebar's row stays empty, so the main row takes the room. */
    grid-template-rows: auto 1fr;
    background-color: var(--c-surface-sunken);

    @media (width >= var(--breakpoint-lg)) {
      grid-template-rows: none;
      grid-template-columns: auto minmax(0, 1fr);
    }
  }

  .cp__main {
    container: cp-shell / inline-size;
  }

  /* No taller than the viewport, either. */
  .page-screen--fill-viewport {
    height: calc(100dvh - var(--cp-debug-bar-height, 0px));

    .cp {
      display: flex;
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
Body: the inset panel holding `page-main`, and the details rail beside it
 */
  .cp-body {
    height: 100%;
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    padding-block: var(--cp-body-inset);
  }

  .cp-body__panel {
    margin-inline-end: var(--cp-body-inset);
    background-color: var(--c-surface-default);
    border: var(--cp-body-border-width) solid
      var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-xl);
    box-shadow: var(--shadow-xs), var(--shadow-lg);
    overflow: clip;
  }

  /* The details rail stands in for the trailing inset. */
  .cp-body--details .cp-body__panel {
    margin-inline-end: 0;
  }

  main,
  .cp-main {
    height: 100%;
  }

  .cp-details-rail {
    position: sticky;
    /* Sticky makes the rail a stacking context, so its tab tooltips can only
       clear the details pane beside it if the rail does too. */
    z-index: var(--c-layer-sticky);
    inset-block-start: 0;
    align-self: start;
    border-block-start: 1px solid transparent;
  }

  /**
Content: the secondary nav, content, and details panes
 */
  .cp-content {
    /* Sizes the details pane's `cqi` caps to the panel, not the details rail beside it. */
    container: cp-content / inline-size;
    height: 100%;
    max-width: 100vw;
    display: grid;
    /* Three columns at every width; the nav spans them while it's stacked above
       the content, and an area nothing occupies collapses to nothing. */
    grid-template-areas: 'sidebar sidebar sidebar' '. main details';
    /* The nav's row, empty or not, keeps to its content; the rest goes below. */
    grid-template-rows: auto 1fr;
    grid-template-columns:
      auto
      minmax(var(--cp-content-main-min), 1fr)
      var(--cp-content-details-track);
    /* Zero until a panel claims the column. */
    --cp-content-details-track: 0;
    /* Flipped by the fold queries below; `useDetailsOverlay` reads it back. */
    --cp-details-overlay: 0;
    /* The column is an inline-size container, so it can't size to its contents:
       every state needs a definite width. The resizer overrides this inline. */
    --cp-content-details-width: min(90vw, calc(400rem / 16));
    /* How far the panel gives before it folds to the rail instead. */
    --cp-content-details-min: calc(280rem / 16);
    /* A floor only matters against a panel; the fold queries drop it back to
       nothing once the panel is overlaying rather than sharing the row. */
    --cp-content-main-min: 0px;

    @media (width >= var(--breakpoint-lg)) {
      grid-template-areas: 'sidebar main details';
      grid-template-rows: none;
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

  .cp-body:has(.cp-details-rail [data-open='false']) .cp-content--details {
    /* Definite widths, not `max-content` — see the container note above. The
       track goes with it, or its range would keep reserving the minimum. */
    --cp-content-details-width: 0px !important;
    --cp-content-details-track: 0px !important;
    /* Nothing to drag a width against while it's folded to the rail. */
    --resize-handle-display: none;

    .cp-content__details {
      border-inline-start: none;
      box-shadow: none;
    }
  }

  .cp-content__sidebar {
    grid-area: sidebar;

    /* Only beside the content; collapsed to a menu above it, there's nothing
       to divide it from. */
    @media (width >= var(--breakpoint-lg)) {
      border-inline-end: 1px solid var(--c-color-border-quiet);
    }
  }

  .cp-content--sidebar .cp-content__sidebar {
    min-width: calc(250rem / 16);
    border-block-end: 1px solid var(--c-color-border-quiet);
    padding: var(--c-spacing-md);
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

  /* Runs from wherever the pane starts to the viewport's bottom, stopping
     short only once the page's end brings the panel's own bottom into view. */
  .cp-content__details-pane {
    position: sticky;
    inset-block-start: 0;
    height: calc(
      100dvh - var(--cp-debug-bar-height, 0px) - var(--cp-details-offset, 0px) -
        var(--cp-details-end-gap, 0px)
    );
  }

  .cp-content__footer {
    display: grid;
    align-content: center;
    border-block-start: 1px solid var(--c-color-border-quiet);
    padding-block: var(--c-spacing-md);
    padding-inline: var(--cp-container-padding);
    min-height: var(--cp-footer-height);
  }

  /* Once the global nav stops docking beside it, the panel runs edge to edge.
     The border goes transparent rather than away, so the panel's width
     doesn't move when it flips. */
  @media (width < var(--breakpoint-lg)) {
    .cp-body {
      --cp-body-inset: 0px;
    }

    .cp-body__panel {
      border-color: transparent;
      border-radius: 0;
      box-shadow: none;
    }

    /* The border still divides the panel from the details rail. */
    .cp-body--details .cp-body__panel {
      border-inline-end-color: var(--c-color-neutral-border-quiet);
    }
  }

  /**
Folds
 */
  /*
 * Below the sum of the content's floor (600px, `--cp-content-main-min`) and the
 * panel's (280px) one of them would be squeezed past it, so the panel folds to
 * its rail and opens over the content instead. The panel can't query its own
 * width, so these query the shell: the sum plus the 50px details rail and the
 * panel's two border pixels. A query condition can't read a custom property,
 * so the sum is written out; keep it in step with the floors and with the
 * wider fold below.
 */
  @container cp-shell (width < 932px) {
    .cp-content--details {
      --cp-details-overlay: 1;
      /* Nothing else shares the row, so the content keeps no floor of its own. */
      --cp-content-main-min: 0px;
      /* No column is reserved, so the panel opens over the content. */
      --cp-content-details-track: 0px !important;
      /* Past the sidebar variant's tighter cap, which is for sharing the row. */
      --cp-content-details-max: 80cqi !important;
    }

    /* No track to stretch to, so the panel names its own width and hangs off
       the trailing edge. */
    .cp-content--details > .cp-content__details {
      inline-size: var(--cp-content-details-width);
      justify-self: end;
      box-shadow: var(--c-shadow-md);
    }
  }

  @media (width >= var(--breakpoint-lg)) {
    /* With the global nav docked, the panel still floats only while there'd be
       room for a details pane beside the content, on every page alike. Below
       that it goes edge to edge as it does below this breakpoint, keeping a
       dividing line beside the sidebar. CpSidebar mirrors this width to drop
       the inset from its own top padding. */
    @container cp-shell (width < 932px) {
      .cp-body {
        --cp-body-inset: 0px;
      }

      .cp-body__panel {
        border-color: transparent;
        border-inline-start-color: var(--c-color-neutral-border-quiet);
        border-radius: 0;
        box-shadow: none;
      }

      .cp-body--details .cp-body__panel {
        border-inline-end-color: var(--c-color-neutral-border-quiet);
      }
    }

    /* The same fold 250px sooner, for the one case where a third column shares
       the row: a secondary nav beside the content, counted from its minimum. */
    @container cp-shell (width < 1182px) {
      .cp-content--sidebar.cp-content--details {
        --cp-details-overlay: 1;
        --cp-content-main-min: 0px;
        --cp-content-details-track: 0px !important;
        --cp-content-details-max: 80cqi !important;
      }

      .cp-content--sidebar.cp-content--details > .cp-content__details {
        inline-size: var(--cp-content-details-width);
        justify-self: end;
        box-shadow: var(--c-shadow-md);
      }
    }
  }

  /* Nothing to drag a column against while the panel overlays the content, so
     the handle goes and opening takes most of the width. The collapsed rule is
     more specific, so the rail still wins until something opens it. */
  @container cp-shell (width < 820px) {
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
    /* Lets what's inside (e.g. the element index toolbar) respond to the room
       the content actually has, rather than the viewport. */
    container: cp-content-view / inline-size;
  }

  .cp-content-view--constrained {
    /* The default lives in `cp.css`; a page passing a length overrides it
       inline through `contentMaxWidth`. */
    max-width: var(--cp-content-max-width);
    margin-block: 0;
    margin-inline: auto;
  }
</style>
