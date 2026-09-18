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
  import ContentSidebar from './page/ContentSidebar.vue';
  import ScreenOverlays from './page/ScreenOverlays.vue';
  import ScreenSkipLinks from './page/ScreenSkipLinks.vue';
  import {useDetailsResizer} from './page/useDetailsResizer';
  import type {ScreenProps, ScreenSlots} from './types';
  import {useScreenRegions} from './useScreenRegions';
  import CpContainer from '@/common/components/CpContainer.vue';

  const emit = defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  const props = withDefaults(defineProps<ScreenProps>(), {
    form: null,
    defaultFormActions: () => ['saveAndContinueEditing'],
    formAdditionalButtons: () => [],
    contentMaxWidth: false,
    centerContent: false,
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

  const detailsResizer = useDetailsResizer({
    column: () => detailsColumn.value?.$el,
    layoutWidth: contentLayoutWidth,
    hasSidebar,
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
  <div class="bg-header">
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
                    <ContentSidebar
                      :visible="hasSidebar"
                      :subnav="subnav"
                      :subnav-actions="subnavActions"
                    >
                      <template
                        v-for="name in sidebarSlots"
                        :key="name"
                        #[name]
                      >
                        <slot :name="name"></slot>
                      </template>
                    </ContentSidebar>

                    <div
                      class="cp-content__main"
                      :class="{
                        'cp-content__main--constrained': contentConstrained,
                        'cp-content__main--centered': centerContent,
                        'cp-content__main--last-child': !hasDetails,
                      }"
                      :style="contentMainStyle"
                    >
                      <slot name="content-toolbar">
                        <CpContainer v-show="hasToolbar">
                          <div
                            class="border-b border-b-quiet py-1 divide flex justify-between items-center min-h-(--global-header-height)"
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
                        <div id="cp-content-header">
                          <CpContainer class="pt-xl pb-md">
                            <div class="flex items-center justify-between">
                              <LayoutSlotOutlet name="title">
                                <slot name="title">
                                  <h1 class="text-xl">{{ title }}</h1>
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
                      <div>
                        <slot></slot>
                      </div>
                      <div class="sticky bottom-0 z-sticky bg-default mt-lg">
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
                          :read-only="readOnly"
                          :form="form"
                          :default-form-actions="defaultFormActions"
                          :form-actions="formActions"
                          :form-additional-actions="formAdditionalActions"
                          :form-additional-buttons="formAdditionalButtons"
                          :submit-button-label="submitButtonLabel"
                          :contained="centerContent"
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

                    <ContentDetails
                      ref="detailsColumn"
                      :visible="hasDetails"
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
                </form>
              </main>
            </slot>
          </div>

          <footer class="cp-page__footer">
            <LayoutSlotOutlet name="page-footer">
              <div class="cp-container">
                <slot name="page-footer"></slot>
              </div>
            </LayoutSlotOutlet>
          </footer>
        </div>
      </div>
    </div>
  </div>

  <ScreenOverlays :debug="debug" />
</template>

<style scoped lang="css">
  .cp {
    display: grid;
    background-color: var(--c-surface-sunken);
    border-start-start-radius: calc(var(--c-spacing-md) + var(--c-radius-md));
    border-start-end-radius: calc(var(--c-spacing-md) + var(--c-radius-md));
    overflow: clip;

    @media screen and (min-width: 768px) {
      grid-template-columns: auto minmax(0, 1fr);
    }
  }

  /* `inline-size`, not `size`: size containment resolves the height from the
   container, which collapses to nothing once the grid row stops supplying
   one. */
  .cp__main {
    container-type: inline-size;
    container-name: cp-main;
  }

  main {
    height: 100%;
  }

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
    --cp-content-details-max: 50%;
    --cp-content-details-track: min(
      var(--cp-content-details-width),
      var(--cp-content-details-max)
    );

    background-color: var(--c-surface-default);
    display: grid;
    height: 100%;

    @container (width >= 768px) {
      &.cp-content--details {
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
        --cp-content-details-max: 40%;

        grid-template-columns:
          var(--cp-content-sidebar-width)
          minmax(0, 1fr)
          var(--cp-content-details-track);
      }
    }
  }

  .cp-content--details:has(.cp-content__details craft-tabs[collapsed]) {
    --cp-content-details-track: auto;

    .cp-content__details {
      border-inline-start: none;
    }
  }

  .cp-content__details {
    border-inline-start: 1px solid var(--c-color-border-quiet);
  }

  .cp-content__sidebar {
    border-inline-end: 1px solid var(--c-color-border-quiet);
    background-color: var(--c-surface-default);
  }

  .cp-content__notices {
    display: grid;
    gap: var(--c-spacing-sm);
  }

  .cp-content__main {
    display: flex;
    flex-direction: column;
    height: 100%;
    background-color: var(--c-surface-default);

    &.cp-content__main--last-child {
      border-start-end-radius: calc(var(--c-spacing-sm) + var(--c-radius-md));
    }
  }

  .cp-content__main--constrained {
    max-inline-size: var(--cp-content-max-width);
  }

  .cp-content__main--constrained:not(.cp-content__main--centered) {
    border-inline-end: 1px solid var(--c-color-border-quiet);
  }

  /* Auto margins stop a grid item stretching, so it takes the full width back
   explicitly and lets the max width cap it. */
  .cp-content__main--centered {
    inline-size: 100%;
    margin-inline: auto;
  }

  /* `:deep()` because slotted content carries the page's scope, and items need
   `min-width: 0` to shrink below min-content inside the `minmax(0, 1fr)`
   track. */
  .cp-content__main > :deep(*) {
    min-width: 0;
  }

  .cp-main {
    height: 100%;
  }
</style>
