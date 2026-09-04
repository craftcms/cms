<script setup lang="ts">
  /**
   * The full-page CP shell: global header, sidebar, breadcrumbs, page header,
   * content/details columns, footer.
   *
   * The only full-page shell — reached through `AppLayout`, which picks between
   * this and `SlideoutScreen`. Both implement `ScreenSlots`/`ScreenProps`.
   *
   * The outer chrome (header, sidebar, footer) is fixed; everything inside the
   * main region is the `main` slot's, and a page that wants to own the whole
   * thing — the element editor does — fills that slot instead of `default`.
   * Its fallback is the standard inner chrome: breadcrumb bar, page header,
   * error summary and the content/details columns.
   *
   * The document scrolls, not the main column: the header travels up and off,
   * while `CpSidebar` is a sticky, viewport-tall flex child of `.cp__main` and
   * stays put.
   */
  import {t} from '@craftcms/ui/utilities/translate';
  import {computed, provide, useId, useTemplateRef, watch} from 'vue';
  import {Head, usePage} from '@inertiajs/vue3';
  import {useElementSize} from '@vueuse/core';
  import CalloutReadOnly from '@/common/components/CalloutReadOnly.vue';
  import CpSidebar from '@/common/components/CpSidebar.vue';
  import DebugPanel from '@/common/components/DebugPanel.vue';
  import FlashMessages from '@/common/components/FlashMessages.vue';
  import FormActions from '@/common/components/FormActions.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import LiveRegion from '@/common/components/LiveRegion.vue';
  import ResizeHandle from '@/common/components/ResizeHandle.vue';
  import PassthroughScreen from './PassthroughScreen.vue';
  import SecondaryNav from '@/common/components/SecondaryNav.vue';
  import SlideoutHost from '@/common/slideouts/SlideoutHost.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import ElevatedSessionHost from '@/modules/auth/elevated-session/ElevatedSessionHost.vue';
  import {useActionRedirect} from '@/common/composables/useActionRedirect';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {useAppendHtml} from '@/common/composables/useAppendHtml';
  import {useFlash} from '@/common/composables/useFlash';
  import {useGlobalSidebar} from '@/common/composables/useGlobalSidebar';
  import useCraftData from '@/common/composables/useCraftData';
  import {useFieldHighlight} from '@/common/composables/useFieldHighlight';
  import {useResizable} from '@/common/composables/useResizable';
  import {provideLayoutSlotRegistry} from '@/common/composables/layoutSlots';
  import {
    provideScreenContext,
    ScreenShellKey,
  } from '@/common/composables/screen';
  import type {ActionItem, FormSaveOptions} from '@/common/types';
  import type {DefaultFormAction, ScreenProps, ScreenSlots} from './types';
  import CpTopBar from '@/common/components/CpTopBar.vue';
  import type {BreadcrumbItem} from '@/common/components/Breadcrumbs.vue';
  import {navItemActions} from '@/common/composables/navActions';
  import {withSubnavCrumbs} from '@/common/composables/subnavCrumbs';

  /** Resize bounds for the details column, in px — 12rem to 30rem. */
  const DETAILS_MIN_WIDTH = 192;
  const DETAILS_MAX_WIDTH = 480;

  const emit = defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  const props = withDefaults(defineProps<ScreenProps>(), {
    form: null,
    defaultFormActions: () => ['saveAndContinueEditing'],
    formAdditionalButtons: () => [],
  });

  const slots = defineSlots<ScreenSlots>();

  const registry = provideLayoutSlotRegistry();
  provideScreenContext('page');
  const craftData = useCraftData();

  // Deep links like `#form-maintenanceMode` point at a field on a long form.
  useFieldHighlight();

  // A page rendering `<AppLayout>` inline inside this shell shouldn't stack a
  // second one — it renders transparently instead.
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

  // The secondary nav's trail joins the crumbs, so where you are reads the
  // same whether or not the nav is on screen — and each level brings the
  // switcher menu an element index's source crumb has.
  const crumbs = computed<Array<BreadcrumbItem> | null>(() => {
    const merged = withSubnavCrumbs(page.props.crumbs ?? [], subnav.value);

    return merged.length > 0 ? merged : null;
  });
  const readOnly = computed(() => Boolean(page.props.readOnly));

  // Which optional layout regions are in play — filled either by an inline
  // slot or by a page-side <LayoutSlot> teleport. These computeds may only
  // toggle visibility (v-show) and classes, never remove an outlet's
  // wrapper from the DOM: teleport targets must persist.
  const hasContextMenu = computed(
    () => Boolean(slots['context-menu']) || registry.has('context-menu')
  );
  const hasToolbar = computed(
    () => Boolean(slots.toolbar) || registry.has('toolbar')
  );
  const hasContentNotice = computed(
    () => Boolean(slots['content-notice']) || registry.has('content-notice')
  );
  const hasContentFooter = computed(
    () => Boolean(slots['content-footer']) || registry.has('content-footer')
  );
  const hasDetails = computed(
    () => Boolean(slots.details) || registry.has('details')
  );
  const hasSidebar = computed(
    () =>
      Boolean(slots.contentSidebar) ||
      Boolean(slots['subnav-actions']) ||
      (props.subnavActions?.length ?? 0) > 0 ||
      registry.has('sidebar') ||
      registry.has('subnav-actions') ||
      subnav.value.length > 0
  );

  const skipLinks = computed(() => [
    {label: t('Skip to main section'), url: '#main'},
    ...(hasSidebar.value
      ? [{label: t('Skip to secondary navigation'), url: '#secondary-nav'}]
      : []),
    ...(props.additionalSkipLinks ?? []),
  ]);

  // A floating sidebar takes its own toggle off-canvas with it, leaving no way
  // back in — so the shell renders one. A docked sidebar collapses to a rail
  // and keeps its toggle, so it doesn't need this.
  const {
    sidebar: globalSidebar,
    toggle: toggleSidebar,
    toggleButton,
    width: sidebarWidth,
    icon: toggleIcon,
  } = useGlobalSidebar();

  /** Registers the reopen button so focus can return to it when the sidebar hides. */
  function registerToggle(el: Element | null): void {
    toggleButton.value = el as HTMLElement | null;
  }

  // The details column is user-resizable. The width lands on
  // `--cp-content-details-width`, which `.cp-content` uses for its
  // trailing grid track, so leaving it unset keeps the stylesheet's
  // responsive default. The name is deliberately not `--details-width`:
  // legacy `_cp.scss` already publishes one of those globally.
  const contentLayout = useTemplateRef<HTMLElement>('contentLayout');
  const detailsColumn = useTemplateRef<HTMLElement>('detailsColumn');
  const {width: contentLayoutWidth} = useElementSize(contentLayout);

  // Ceiling on the details column so a wide drag — or a width restored from
  // storage at a narrower viewport — can never squeeze the main column off the
  // page. Mirrors the `min()` cap on the grid track, and stays put during a
  // drag because it keys off the layout rather than the columns inside it.
  const detailsMaxWidth = computed(() => {
    if (!contentLayoutWidth.value) {
      return DETAILS_MAX_WIDTH;
    }

    const share = contentLayoutWidth.value * (hasSidebar.value ? 0.4 : 0.5);

    return Math.max(
      DETAILS_MIN_WIDTH,
      Math.min(DETAILS_MAX_WIDTH, Math.round(share))
    );
  });

  const detailsResizer = useResizable({
    target: detailsColumn,
    edge: 'inline-start',
    minWidth: DETAILS_MIN_WIDTH,
    maxWidth: detailsMaxWidth,
    cssVariable: '--cp-content-details-width',
    storageKey: 'AppLayout.detailsWidth',
  });

  // `aria-controls` needs a real id, and `details` is taken: legacy CSS pins
  // `#details` to 350px, which would override the grid track and push the
  // column off the page.
  const detailsId = `cp-content-details-${useId()}`;

  const formActionItems = computed(() => [
    ...props.defaultFormActions.map(defaultFormActionItem),
    ...(props.formActions ?? []),
  ]);

  function defaultFormActionItem(action: DefaultFormAction): ActionItem {
    if (action === 'saveAndContinueEditing') {
      return {
        label: t('Save and continue editing'),
        onClick: () => save({redirect: false}),
        shortcut: 'S',
      };
    }

    throw new Error(`Unknown default form action: ${action}`);
  }

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
  <div>
    <LiveRegion />
    <!-- Focus lands here on Inertia navigation; see
    `handleAccessibleRouting` in bootstrap/cp.ts. -->
    <span id="route-focus-anchor" tabindex="-1" class="sr-only"></span>
    <a
      v-for="link in skipLinks"
      :key="link.url"
      :href="link.url"
      class="skip-link skip-link--global"
      >{{ link.label }}</a
    >
  </div>
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
          <slot name="main">
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
                <template v-if="readOnly">
                  <CalloutReadOnly />
                </template>
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
                    <LayoutSlotOutlet name="sidebar">
                      <slot name="sidebar">
                        <!-- The subnav-actions outlet lives inside this
                        fallback, so a page must not teleport `sidebar` and
                        `subnav-actions` at the same time. -->
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
                  <div class="cp-content__main">
                    <slot name="header">
                      <div id="cp-content-header">
                        <div
                          class="flex gap-2 items-center justify-between pt-3 p-2"
                        >
                          <LayoutSlotOutlet name="title">
                            <slot name="title">
                              <h1 class="text-xl">{{ pageTitle }}</h1>
                            </slot>
                          </LayoutSlotOutlet>
                          <LayoutSlotOutlet name="title-badge">
                            <slot name="title-badge"></slot>
                          </LayoutSlotOutlet>
                          <div
                            v-show="hasToolbar"
                            id="toolbar"
                            class="flex items-center gap-2"
                          >
                            <LayoutSlotOutlet name="toolbar">
                              <slot name="toolbar"></slot>
                            </LayoutSlotOutlet>
                          </div>

                          <div class="flex gap-2 items-center">
                            <LayoutSlotOutlet name="actions">
                              <slot name="actions">
                                <slot name="additional-buttons"></slot>

                                <FormActions
                                  v-if="form"
                                  :form="form"
                                  :action-items="formActionItems"
                                  :additional-actions="formAdditionalActions"
                                  :additional-buttons="formAdditionalButtons"
                                  :submit-label="submitButtonLabel"
                                  :read-only="readOnly"
                                >
                                  <template
                                    v-if="slots['submit-button']"
                                    #submit-button
                                  >
                                    <slot name="submit-button"></slot>
                                  </template>
                                </FormActions>
                              </slot>
                            </LayoutSlotOutlet>
                          </div>
                        </div>
                      </div>
                    </slot>

                    <div
                      v-show="hasContentNotice"
                      id="content-notice"
                      role="status"
                    >
                      <LayoutSlotOutlet name="content-notice">
                        <slot name="content-notice"></slot>
                      </LayoutSlotOutlet>
                    </div>
                    <LayoutSlotOutlet name="tabs">
                      <slot name="tabs"></slot>
                    </LayoutSlotOutlet>
                    <slot></slot>
                    <div v-show="hasContentFooter" class="content-footer">
                      <LayoutSlotOutlet name="content-footer">
                        <slot name="content-footer"></slot>
                      </LayoutSlotOutlet>
                    </div>
                  </div>
                  <!-- v-show, not v-if: the aside hosts a LayoutSlotOutlet
                  teleport target, which must stay in the DOM so page-side
                  <LayoutSlot> content can mount before registration flips
                  hasDetails. -->
                  <aside
                    v-show="hasDetails"
                    ref="detailsColumn"
                    class="cp-content__details"
                  >
                    <ResizeHandle
                      class="cp-details-resize-handle"
                      :resizer="detailsResizer"
                      :label="t('Resize details')"
                      :controls="detailsId"
                    />
                    <div :id="detailsId" class="cp-details">
                      <LayoutSlotOutlet name="details">
                        <slot name="details"></slot>
                      </LayoutSlotOutlet>
                    </div>
                  </aside>
                </div>
              </form>
            </main>
          </slot>
        </div>

        <footer class="cp-page__footer">
          <LayoutSlotOutlet name="footer">
            <div class="cp-container">
              <slot name="footer"></slot>
            </div>
          </LayoutSlotOutlet>
        </footer>
      </div>
    </div>
  </div>

  <DebugPanel v-if="debug" :data="debug" />
  <ElevatedSessionHost />
  <!-- Hosted here rather than in `AppLayout`: exactly one full-page shell
    exists per page, so panels can't be double-rendered by a page that also
    renders `<AppLayout>` inline. -->
  <SlideoutHost />
</template>

<style scoped lang="css">
  /**
CP STYLES (global shell)
 */
  .cp {
    display: grid;

    @media screen and (min-width: 768px) {
      grid-template-columns: auto minmax(0, 1fr);
    }
  }

  /* The document scrolls, so this row lays out but never clips: the sidebar is
   a sticky, viewport-tall flex child that catches at the top while the header
   scrolls away above it. `align-items: start` keeps the sidebar at its own
   100dvh instead of stretching it to the content's height.

   `inline-size`, not `size`: size containment resolves the height from the
   container rather than its contents, which collapsed to nothing the moment
   the grid row stopped supplying one. The container queries below only ask
   about width. */
  .cp__main {
    container-type: inline-size;
    container-name: cp-main;
  }

  /**
PAGE STYLES
 */

  .cp-page {
    height: 100%;
    display: grid;
    grid-template-rows: auto 1fr auto;
  }

  .cp-page__footer {
    position: sticky;
    inset-block-end: 0;
  }

  .cp-content {
    --cp-content-details-width: clamp(12rem, 20%, 16rem);
    --cp-content-sidebar-width: clamp(
      calc(120rem / 16),
      20%,
      calc(220rem / 16)
    );

    /* Hard ceiling on the details track, so a width restored from storage at a
       wider viewport can't run the layout off the page. `useResizable` clamps
       to the same share, so the drag stops where the column does. */
    --cp-content-details-max: 50%;
    --cp-content-details-track: min(
      var(--cp-content-details-width),
      var(--cp-content-details-max)
    );

    display: grid;
    height: 100%;

    @container (width >= 768px) {
      //align-items: start;

      .cp-content--details {
        grid-template-columns:
          minmax(0, 1fr)
          var(--cp-content-details-track);
      }

      &.cp-content--sidebar {
        grid-template-columns:
          var(--cp-content-sidebar-width)
          minmax(0, 1fr);
      }

      &.cp-content--sidebar.cp-content--details {
        /* Three columns share the width, so the details column gets less. */
        --cp-content-details-max: 40%;

        grid-template-columns:
          var(--cp-content-sidebar-width)
          minmax(0, 1fr)
          var(--cp-content-details-track);
      }
    }
  }

  .cp-content__details-column {
    position: relative;
    container-type: inline-size;
  }

  /* Sits in the gutter between the content and the details column. Only the
     wide layout has a details track to resize, so the handle stays hidden
     until the columns actually split. */
  .cp-content__details-resize-handle {
    --resize-handle-display: none;

    /* Named, unlike the query on `.cp-content` above: the handle sits
       inside `.cp-content__details-column`, which is itself an inline-size
       container, so an anonymous query here would ask the details column
       whether it's 768px wide — which it never is — instead of asking the
       layout whether it has split into columns. */
    @container cp-main (width >= 768px) {
      --resize-handle-display: flex;

      /* Centered in the gutter: back off half the gap, then half the handle. */
      inset-inline-start: calc(var(--c-spacing-md) / -2 - 6px);
    }
  }

  .cp-content__main {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    align-content: start;
    height: 100%;
  }

  .cp-content__sidebar {
  }

  /* Wide content — a many-columned table, a long code block — sets a min-content
   floor that otherwise pushes this column past its track and out of the
   layout. The track is already minmax(0, 1fr); items need min-width: 0 too,
   since `auto` refuses to shrink below min-content. Letting them shrink is
   what lets their own overflow containers (.element-index__body) scroll.
   `:deep()` because these are slotted from the page component, so they carry
   its scope id rather than this one's. */
  .cp-content__main > :deep(*) {
    min-width: 0;
  }

  .content-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--c-spacing-md);
    margin-block-start: var(--c-spacing-md);
  }

  .cp-main {
    height: 100%;
  }

  .cp-details {
    display: grid;
    gap: var(--c-spacing-md);
  }
</style>
