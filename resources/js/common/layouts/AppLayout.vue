<script setup lang="ts">
  import {t} from '@craftcms/cp/utilities/translate';
  import {computed, watch} from 'vue';
  import {Head, type InertiaForm, usePage} from '@inertiajs/vue3';
  import Breadcrumbs from '@/common/components/Breadcrumbs.vue';
  import CalloutReadOnly from '@/common/components/CalloutReadOnly.vue';
  import CpSidebar from '@/common/components/CpSidebar.vue';
  import DebugPanel from '@/common/components/DebugPanel.vue';
  import FlashMessages from '@/common/components/FlashMessages.vue';
  import FormActions from '@/common/components/FormActions.vue';
  import LayoutSlotOutlet from '@/common/components/LayoutSlotOutlet.vue';
  import LiveRegion from '@/common/components/LiveRegion.vue';
  import Pane from '@/common/components/Pane.vue';
  import SecondaryNav from '@/common/components/SecondaryNav.vue';
  import SystemInfo from '@/common/components/SystemInfo.vue';
  import UserMenu from '@/common/components/UserMenu.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import ElevatedSessionHost from '@/modules/auth/elevated-session/ElevatedSessionHost.vue';
  import {useActionRedirect} from '@/common/composables/useActionRedirect';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import {useAppendHtml} from '@/common/composables/useAppendHtml';
  import {useFlash} from '@/common/composables/useFlash';
  import {useGlobalSidebar} from '@/common/composables/useGlobalSidebar';
  import {hasLayoutSlot} from '@/common/composables/layoutSlots';
  import type {
    ActionItem,
    ActionItemButton,
    FormSaveOptions,
  } from '@/common/types';

  type DefaultFormAction = 'saveAndContinueEditing';

  export interface AppLayoutProps {
    title?: string;
    debug?: any;
    fullWidth?: boolean;
    form?: InertiaForm<any> | null;
    defaultFormActions?: Array<DefaultFormAction>;
    formActions?: Array<ActionItem>;
    formAdditionalActions?: Array<ActionItem>;
    formAdditionalButtons?: Array<ActionItemButton>;
    additionalSkipLinks?: Array<{label: string; url: string}>;
  }

  const emit = defineEmits<{
    (e: 'save', options?: FormSaveOptions): void;
  }>();

  const props = withDefaults(defineProps<AppLayoutProps>(), {
    fullWidth: false,
    form: null,
    defaultFormActions: () => ['saveAndContinueEditing'],
    formAdditionalButtons: () => [],
  });

  /**
   * The layout's extension points, mirroring Craft 5's `_layouts/cp.twig`
   * blocks and variables (noted per slot).
   */
  const slots = defineSlots<{
    /** Page content inside the content column. Craft 5: `block content`. */
    default?: () => any;
    /** Replaces the entire main column: breadcrumb bar, page header, and content. Craft 5: `block main`. */
    main?: () => any;
    /** Replaces the breadcrumb bar. Default renders the `crumbs` page prop and the `context-menu` slot. */
    breadcrumbs?: () => any;
    /** Extra controls next to the breadcrumbs, e.g. a site picker. Craft 5: `contextMenu`. */
    'context-menu'?: () => any;
    /** Replaces the page header (title through action buttons). Pass empty content to hide it. Craft 5: `block header` / `showHeader`. */
    header?: () => any;
    /** Replaces the default `<h1>` page title. Craft 5: `block pageTitle`. */
    title?: () => any;
    /** Status badges next to the title. Craft 5: `#revision-indicators`. */
    'title-badge'?: () => any;
    /** Controls between the title and the action buttons. Craft 5: `toolbar`. */
    toolbar?: () => any;
    /** Replaces the whole action-buttons area, including the form save UI. Craft 5: `actionButton`. */
    actions?: () => any;
    /** Extra buttons before the form save UI. Craft 5: `additionalButtons`. */
    'additional-buttons'?: () => any;
    /** Replaces the save button while keeping the form action menu. Craft 5: `block submitButton`. */
    'submit-button'?: () => any;
    /** Replaces the default form error summary. Craft 5: `errorSummary`. */
    'error-summary'?: () => any;
    /** Status notice at the top of the content column. Craft 5: `contentNotice`. */
    'content-notice'?: () => any;
    /** Tabs above the content. Craft 5: `tabs`. */
    tabs?: () => any;
    /** Left column beside the content. Defaults to a secondary nav built from the `subnav` page prop. Craft 5: `sidebar`. */
    sidebar?: () => any;
    /** Extra controls below the default secondary nav. */
    'subnav-actions'?: () => any;
    /** Bottom of the content column (pagination, meta info, …). Craft 5: `footer` (content pane). */
    'content-footer'?: () => any;
    /** Right details column beside the content. Craft 5: `details`. */
    details?: () => any;
    /** Global page footer. */
    footer?: () => any;
  }>();

  const page = usePage<{
    title: string;
    readOnly?: boolean;
    crumbs?: Array<{
      url?: string;
      label: string;
    }> | null;
    subnav?: Array<CraftCms.Cms.Cp.Data.NavItem>;
  }>();

  // Page chrome from props and shared page data.
  const pageTitle = computed(() => props.title?.trim() ?? page.props.title);
  const crumbs = computed(() => page.props.crumbs ?? null);
  const subnav = computed(() => page.props.subnav ?? []);
  const readOnly = computed(() => Boolean(page.props.readOnly));

  // Which optional layout regions are in play — filled either by an inline
  // slot or by a page-side <LayoutSlot> teleport. These computeds may only
  // toggle visibility (v-show) and classes, never remove an outlet's
  // wrapper from the DOM: teleport targets must persist.
  const hasContextMenu = computed(
    () => Boolean(slots['context-menu']) || hasLayoutSlot('context-menu')
  );
  const hasToolbar = computed(
    () => Boolean(slots.toolbar) || hasLayoutSlot('toolbar')
  );
  const hasContentNotice = computed(
    () => Boolean(slots['content-notice']) || hasLayoutSlot('content-notice')
  );
  const hasContentFooter = computed(
    () => Boolean(slots['content-footer']) || hasLayoutSlot('content-footer')
  );
  const hasDetails = computed(
    () => Boolean(slots.details) || hasLayoutSlot('details')
  );
  const hasSidebar = computed(
    () =>
      Boolean(slots.sidebar) ||
      Boolean(slots['subnav-actions']) ||
      hasLayoutSlot('sidebar') ||
      hasLayoutSlot('subnav-actions') ||
      subnav.value.length > 0
  );

  const skipLinks = computed(() => [
    {label: t('Skip to main section'), url: '#main'},
    ...(hasSidebar.value
      ? [{label: t('Skip to secondary navigation'), url: '#secondary-nav'}]
      : []),
    ...(props.additionalSkipLinks ?? []),
  ]);

  const {
    isLargeScreen,
    sidebar: globalSidebar,
    toggle: toggleSidebar,
    close: closeSidebar,
    icon: sidebarIcon,
    width: sidebarWidth,
  } = useGlobalSidebar();

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

  // Bridge `@craftcms/cp` action redirects into Inertia SPA visits.
  useActionRedirect();
</script>

<template>
  <Head :title="pageTitle" />
  <LiveRegion></LiveRegion>
  <div class="cp">
    <header class="cp__header">
      <a
        v-for="link in skipLinks"
        :key="link.url"
        :href="link.url"
        class="skip-link skip-link--global"
        >{{ link.label }}</a
      >
      <div class="flex gap-2 p-2">
        <craft-button
          icon
          type="button"
          appearance="plain"
          @click="toggleSidebar"
          v-if="!isLargeScreen"
          ref="sidebarToggle"
        >
          <craft-icon
            :name="sidebarIcon"
            :label="t('Toggle menu')"
          ></craft-icon>
        </craft-button>
        <SystemInfo v-if="isLargeScreen" />

        <div class="ml-auto"></div>
        <craft-button icon appearance="plain" type="button">
          <craft-icon name="search" :label="t('Search')"></craft-icon>
        </craft-button>
        <UserMenu />
      </div>
      <FlashMessages />
    </header>
    <div class="cp__sidebar">
      <CpSidebar
        :mode="globalSidebar.mode"
        :visibility="globalSidebar.visibility"
        @close="closeSidebar"
      />
    </div>
    <div class="cp__main">
      <slot name="main">
        <slot name="breadcrumbs">
          <div
            class="px-4 py-2 border-b border-b-neutral-border-quiet flex flex-nowrap items-center gap-2"
            v-show="crumbs || hasContextMenu"
          >
            <Breadcrumbs v-if="crumbs" :items="crumbs" />
            <div v-show="hasContextMenu" class="context-menu-container">
              <LayoutSlotOutlet name="context-menu">
                <slot name="context-menu"></slot>
              </LayoutSlotOutlet>
            </div>
          </div>
        </slot>
        <main id="main" tabindex="-1" class="pb-2xl">
          <component
            :is="form ? 'form' : 'div'"
            method="post"
            @submit.prevent="save()"
          >
            <slot name="header">
              <div :class="{container: true, 'container--full': fullWidth}">
                <div class="index-grid index-grid--header">
                  <div class="index-grid__aside">
                    <LayoutSlotOutlet name="title">
                      <slot name="title">
                        <h1 class="text-xl">{{ pageTitle }}</h1>
                      </slot>
                    </LayoutSlotOutlet>
                    <LayoutSlotOutlet name="title-badge">
                      <slot name="title-badge"></slot>
                    </LayoutSlotOutlet>
                  </div>

                  <div class="index-grid__main">
                    <div
                      v-show="hasToolbar"
                      id="toolbar"
                      class="flex items-center gap-2"
                    >
                      <LayoutSlotOutlet name="toolbar">
                        <slot name="toolbar"></slot>
                      </LayoutSlotOutlet>
                    </div>

                    <LayoutSlotOutlet name="actions">
                      <slot name="actions">
                        <slot name="additional-buttons"></slot>

                        <FormActions
                          v-if="form"
                          :form="form"
                          :action-items="formActionItems"
                          :additional-actions="formAdditionalActions"
                          :additional-buttons="formAdditionalButtons"
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
            <div :class="{container: true, 'container--full': fullWidth}">
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
                class="content-layout"
                :class="{
                  'content-layout--sidebar': hasSidebar,
                  'content-layout--details': hasDetails,
                }"
              >
                <aside
                  v-show="hasSidebar"
                  id="secondary-nav"
                  tabindex="-1"
                  class="content-layout__sidebar"
                >
                  <LayoutSlotOutlet name="sidebar">
                    <slot name="sidebar">
                      <!-- The subnav-actions outlet lives inside this
                        fallback, so a page must not teleport `sidebar` and
                        `subnav-actions` at the same time. -->
                      <SecondaryNav :items="subnav">
                        <template #actions>
                          <LayoutSlotOutlet name="subnav-actions">
                            <slot name="subnav-actions"></slot>
                          </LayoutSlotOutlet>
                        </template>
                      </SecondaryNav>
                    </slot>
                  </LayoutSlotOutlet>
                </aside>
                <div class="content-layout__main">
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
                <aside v-show="hasDetails">
                  <Pane appearance="raised">
                    <div class="details">
                      <LayoutSlotOutlet name="details">
                        <slot name="details"></slot>
                      </LayoutSlotOutlet>
                    </div>
                  </Pane>
                </aside>
              </div>
            </div>
          </component>
        </main>
      </slot>
    </div>
    <div class="cp__footer">
      <footer>
        <div :class="{container: true, 'container--full': fullWidth}">
          <LayoutSlotOutlet name="footer">
            <slot name="footer"></slot>
          </LayoutSlotOutlet>
        </div>
      </footer>
    </div>
  </div>

  <DebugPanel v-if="debug" :data="debug" />
  <ElevatedSessionHost />
</template>

<style scoped lang="css">
  .cp {
    display: grid;
  }

  .cp__main {
    container-type: size;
  }

  .cp__header {
    --c-color-focus-outline: var(--color-blue-200);
    color: var(--color-slate-200);
    background-color: var(--color-slate-950);
  }

  .container {
    max-width: var(--global-content-width);
    margin: 0 auto;
    padding-inline: var(--c-spacing-lg);
  }

  .container--full {
    max-width: none;
  }

  .content-layout {
    display: grid;
    gap: var(--c-spacing-md);

    @container (width >= 768px) {
      align-items: start;

      &.content-layout--details {
        grid-template-columns: minmax(0, 1fr) clamp(12rem, 20%, 16rem);
      }

      &.content-layout--sidebar {
        grid-template-columns:
          clamp(calc(120rem / 16), 20%, calc(220rem / 16))
          minmax(0, 1fr);
      }

      &.content-layout--sidebar.content-layout--details {
        grid-template-columns:
          clamp(calc(120rem / 16), 20%, calc(220rem / 16))
          minmax(0, 1fr)
          clamp(12rem, 20%, 16rem);
      }
    }
  }

  main {
    padding-block-end: var(--c-spacing-xl);
  }

  .content-layout__main {
    display: grid;
    gap: var(--c-spacing-md);
    align-content: start;
  }

  .content-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: var(--c-spacing-md);
    margin-block-start: var(--c-spacing-md);
  }

  .details {
    display: grid;
    gap: var(--c-spacing-md);
  }

  @media screen and (min-width: 1024px) {
    .cp {
      grid-template-columns: v-bind(sidebarWidth) minmax(0, 1fr);
      grid-template-areas: 'header header' 'sidebar main';
      grid-template-rows: auto 1fr;
      min-height: 100vh;
      position: fixed;
      inset: 0;
      width: 100%;
      height: 100%;
    }

    .cp__header {
      grid-area: header;
    }

    .cp__sidebar {
      grid-area: sidebar;
    }

    .cp__main {
      grid-area: main;
      overflow: auto;
    }
  }
</style>
