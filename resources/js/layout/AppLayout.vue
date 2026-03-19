<script setup lang="ts">
  import SystemInfo from '@/components/SystemInfo.vue';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {computed, reactive, ref, useTemplateRef, watch, provide} from 'vue';
  import CpSidebar from '@/components/CpSidebar.vue';
  import {useMediaQuery} from '@vueuse/core';
  import {Head, usePage} from '@inertiajs/vue3';
  import VarDump from '@/components/VarDump.vue';
  import Breadcrumbs from '@/components/Breadcrumbs.vue';
  import {useAnnouncer} from '@/composables/useAnnouncer';
  import LiveRegion from '@/components/LiveRegion.vue';
  import useCraftData from '@/composables/useCraftData';
  import type {Tab, FormAction, Crumb} from '@/types/layout';

  const props = withDefaults(
    defineProps<{
      title?: string;
      debug?: any;
      fullWidth?: boolean;
      // Full page form support
      fullPageForm?: boolean;
      showHeader?: boolean;
      saveShortcut?: boolean;
      saveShortcutRedirect?: string | false;
      retainScrollOnSaveShortcut?: boolean;
      formActions?: FormAction[];
      submitButtonLabel?: string;
      mainAttributes?: Record<string, any>;
      mainFormAttributes?: Record<string, any>;
    }>(),
    {
      fullWidth: false,
      fullPageForm: false,
      showHeader: true,
      saveShortcut: true,
      saveShortcutRedirect: false,
      retainScrollOnSaveShortcut: false,
      submitButtonLabel: undefined,
    }
  );

  const emit = defineEmits<{
    (e: 'submit', event: SubmitEvent): void;
  }>();

  const {app, currentUser} = useCraftData();

  const page = usePage<{
    flash: {
      success: string | null;
      error: string | null;
    };
    crumbs?: Crumb[] | null;
    alerts?: Array<string | {content: string; showIcon?: boolean}>;
    tabs?: Tab[];
    trialInfo?: {
      message: string;
      cartUrl: string;
    } | null;
    canUpgradeEdition?: boolean;
    isTrial?: boolean;
  }>();

  const errorFlash = computed(() => page.props.flash?.error);
  const successFlash = computed(() => page.props.flash?.success);
  const crumbs = computed(() => page.props.crumbs ?? null);
  const alerts = computed(() => page.props.alerts ?? []);
  const tabs = computed(() => {
    const pageTabs = page.props.tabs ?? [];
    return pageTabs.length > 1 ? pageTabs : null;
  });
  const trialInfo = computed(() => page.props.trialInfo ?? null);
  const canUpgradeEdition = computed(
    () => page.props.canUpgradeEdition ?? false
  );
  const isTrial = computed(() => page.props.isTrial ?? false);

  const sidebarToggle = useTemplateRef('sidebarToggle');
  const {announcement, announce} = useAnnouncer();

  watch(successFlash, (newMessage) => announce(newMessage));
  watch(errorFlash, (newMessage) => announce(newMessage));

  const state = reactive<{
    sidebar: {
      mode: 'docked' | 'floating';
      visibility: 'hidden' | 'visible';
    };
    detailsVisible: boolean;
    contentSidebarVisible: boolean;
  }>({
    sidebar: {
      mode: 'floating',
      visibility: 'hidden',
    },
    detailsVisible: true,
    contentSidebarVisible: true,
  });

  const isLargeScreen = useMediaQuery('(min-width: 1024px)');
  const debugOpen = ref(false);
  const activeTab = ref<string | null>(null);

  // Initialize active tab
  watch(
    tabs,
    (newTabs) => {
      if (newTabs && newTabs.length > 0 && !activeTab.value) {
        activeTab.value = newTabs[0].id;
      }
    },
    {immediate: true}
  );

  watch(
    isLargeScreen,
    (value) => {
      if (value) {
        state.sidebar.mode = 'docked';
        state.sidebar.visibility = 'visible';
      } else {
        state.sidebar.mode = 'floating';
        state.sidebar.visibility = 'hidden';
      }
    },
    {immediate: true}
  );

  function toggleSidebar() {
    state.sidebar.visibility =
      state.sidebar.visibility === 'visible' ? 'hidden' : 'visible';
  }

  function closeSidebar() {
    state.sidebar.visibility = 'hidden';
    (sidebarToggle.value as HTMLButtonElement)?.focus();
  }

  function toggleDetails() {
    state.detailsVisible = !state.detailsVisible;
  }

  function toggleContentSidebar() {
    state.contentSidebarVisible = !state.contentSidebarVisible;
  }

  function setActiveTab(tabId: string) {
    activeTab.value = tabId;
  }

  function handleFormSubmit(event: SubmitEvent) {
    emit('submit', event);
  }

  const sidebarIcon = computed(() => {
    return state.sidebar.visibility === 'visible' ? 'x' : 'bars';
  });

  const sidebarWidth = computed(() => {
    if (state.sidebar.mode === 'docked') {
      return state.sidebar.visibility === 'visible'
        ? 'var(--global-sidebar-width)'
        : '0';
    }
    return 'auto';
  });

  // Provide active tab state for child components
  provide('activeTab', activeTab);
  provide('setActiveTab', setActiveTab);

  // Form actions computed
  const safeActions = computed(() =>
    (props.formActions ?? []).filter((a) => !a.destructive)
  );
  const destructiveActions = computed(() =>
    (props.formActions ?? []).filter((a) => a.destructive)
  );
  const hasFormActions = computed(
    () => safeActions.value.length > 0 || destructiveActions.value.length > 0
  );
</script>

<template>
  <Head :title="title" />
  <LiveRegion :debug="true"></LiveRegion>

  <!-- Skip Links -->
  <nav class="skip-links" aria-label="Skip links">
    <a href="#main" class="skip-link">{{ t('Skip to main content') }}</a>
    <a v-if="$slots.sidebar" href="#sidebar" class="skip-link">{{
      t('Skip to sidebar')
    }}</a>
  </nav>

  <div class="cp" v-bind="$attrs">
    <div class="cp__header">
      <!-- Alerts -->
      <div v-if="alerts.length > 0" id="alerts" class="alerts">
        <ul>
          <li v-for="(alert, idx) in alerts" :key="idx" class="alert-item">
            <div class="alert-content">
              <span
                v-if="typeof alert === 'string' || alert.showIcon !== false"
                class="alert-icon"
                :aria-label="t('Error')"
              >
                <craft-icon name="triangle-exclamation"></craft-icon>
              </span>
              <span
                v-html="typeof alert === 'string' ? alert : alert.content"
              ></span>
            </div>
          </li>
        </ul>
      </div>

      <!-- Global Header Bar -->
      <div class="global-header" role="region" :aria-label="t('My Account')">
        <div class="global-header__left">
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

          <!-- Context Menu -->
          <div
            v-if="$slots.contextMenu"
            id="context-menu-container"
            class="context-menu-container"
          >
            <slot name="contextMenu"></slot>
          </div>
        </div>

        <div class="global-header__right">
          <!-- Announcements button -->
          <button
            type="button"
            id="announcements-btn"
            class="btn hidden"
            :title="t('What\'s New')"
          >
            <span class="visually-hidden">{{ t("What's New") }}</span>
            <craft-icon name="gift"></craft-icon>
          </button>

          <craft-button icon appearance="plain">
            <craft-icon name="search" :label="t('Search')"></craft-icon>
          </craft-button>

          <!-- Account dropdown -->
          <div class="account-toggle-wrapper">
            <craft-action-menu>
              <craft-button
                slot="invoker"
                id="user-info"
                :aria-label="t('My Account')"
                :title="t('My Account')"
                type="button"
                icon
                appearance="plain"
              >
                <slot name="userPhoto">
                  <craft-icon name="user"></craft-icon>
                </slot>
              </craft-button>

              <div slot="content">
                <div class="account-menu-header">
                  <slot name="accountMenuHeader">
                    <div v-if="currentUser">
                      <div>{{ currentUser.username }}</div>
                      <div class="text-sm text-muted">
                        {{ currentUser.email }}
                      </div>
                    </div>
                  </slot>
                </div>
                <hr />
                <craft-action-item href="/myaccount">
                  {{ t('My Account') }}
                </craft-action-item>
                <hr />
                <craft-action-item href="/logout">
                  {{ t('Sign out') }}
                </craft-action-item>
              </div>
            </craft-action-menu>
          </div>
        </div>
      </div>

      <!-- Flash Messages -->
      <template v-if="errorFlash">
        <craft-callout variant="danger" rounded="none">{{
          errorFlash
        }}</craft-callout>
      </template>
      <template v-if="successFlash">
        <craft-callout variant="success" rounded="none">{{
          successFlash
        }}</craft-callout>
      </template>
    </div>

    <div class="cp__sidebar">
      <CpSidebar
        :mode="state.sidebar.mode"
        :visibility="state.sidebar.visibility"
        @close="closeSidebar"
      />
    </div>

    <div class="cp__main">
      <slot name="main">
        <main role="main" tabindex="-1" v-bind="mainAttributes">
          <!-- Full Page Form wrapper (optional) -->
          <component
            :is="fullPageForm ? 'form' : 'div'"
            v-bind="
              fullPageForm
                ? {
                    method: 'post',
                    'accept-charset': 'UTF-8',
                    novalidate: true,
                    'data-saveshortcut': saveShortcut,
                    'data-confirm-unload': true,
                    ...mainFormAttributes,
                  }
                : {}
            "
            @submit.prevent="
              fullPageForm ? handleFormSubmit($event) : undefined
            "
          >
            <slot name="breadcrumbs">
              <div
                class="px-4 py-2 border-b border-b-neutral-border-quiet"
                v-if="crumbs && !$slots.headerBreadcrumbs"
              >
                <Breadcrumbs :items="crumbs" />
              </div>
            </slot>

            <slot name="header" v-if="showHeader">
              <div :class="{container: true, 'container--full': fullWidth}">
                <div class="page-header">
                  <div class="page-header__title">
                    <slot name="title">
                      <h1
                        v-if="title"
                        class="text-xl screen-title"
                        :title="title"
                      >
                        {{ title }}
                      </h1>
                    </slot>
                    <slot name="title-badge"></slot>
                    <div id="revision-indicators"></div>
                  </div>

                  <!-- Toolbar -->
                  <div v-if="$slots.toolbar" id="toolbar" class="toolbar">
                    <slot name="toolbar"></slot>
                  </div>

                  <div class="page-header__actions">
                    <slot name="actions">
                      <!-- Additional Buttons -->
                      <slot name="additionalButtons"></slot>

                      <!-- Action Button (default for forms) -->
                      <slot name="actionButton">
                        <template v-if="fullPageForm">
                          <craft-button-group>
                            <slot name="submitButton">
                              <craft-button type="submit" variant="primary">
                                {{ submitButtonLabel ?? t('Save') }}
                              </craft-button>
                            </slot>
                            <template v-if="hasFormActions">
                              <craft-action-menu>
                                <craft-button
                                  slot="invoker"
                                  variant="primary"
                                  type="button"
                                  icon
                                >
                                  <craft-icon
                                    name="chevron-down"
                                    :label="t('More actions')"
                                  ></craft-icon>
                                </craft-button>
                                <div slot="content">
                                  <template
                                    v-for="action in safeActions"
                                    :key="action.label"
                                  >
                                    <craft-action-item
                                      :data-action="action.action"
                                      :data-redirect="action.redirect"
                                      :data-confirm="action.confirm"
                                    >
                                      {{ action.label }}
                                      <craft-shortcut
                                        v-if="action.shortcut"
                                        slot="suffix"
                                        class="ml-2"
                                      >
                                        {{ action.shift ? 'Shift+' : '' }}S
                                      </craft-shortcut>
                                    </craft-action-item>
                                  </template>
                                  <hr
                                    v-if="
                                      safeActions.length > 0 &&
                                      destructiveActions.length > 0
                                    "
                                  />
                                  <template
                                    v-for="action in destructiveActions"
                                    :key="action.label"
                                  >
                                    <craft-action-item
                                      class="error"
                                      :data-action="action.action"
                                      :data-redirect="action.redirect"
                                      :data-confirm="action.confirm"
                                    >
                                      {{ action.label }}
                                    </craft-action-item>
                                  </template>
                                </div>
                              </craft-action-menu>
                            </template>
                          </craft-button-group>
                        </template>
                      </slot>

                      <!-- Additional Action Menu -->
                      <slot name="actionMenu"></slot>
                    </slot>
                  </div>
                </div>
              </div>
            </slot>

            <!-- Main Content Area -->
            <div :class="{container: true, 'container--full': fullWidth}">
              <div
                :class="[
                  'main-content',
                  {
                    'has-sidebar': $slots.sidebar,
                    'has-details': $slots.details,
                  },
                ]"
              >
                <!-- Content Sidebar (left) -->
                <div
                  v-if="$slots.sidebar"
                  id="sidebar-container"
                  class="sidebar-container"
                >
                  <div
                    id="sidebar-toggle-container"
                    class="sidebar-toggle-container"
                  >
                    <button
                      type="button"
                      id="sidebar-toggle"
                      class="btn menubtn chromeless"
                      aria-controls="sidebar-container"
                      :aria-expanded="state.contentSidebarVisible"
                      @click="toggleContentSidebar"
                    >
                      {{
                        state.contentSidebarVisible
                          ? t('Hide sidebar')
                          : t('Show sidebar')
                      }}
                    </button>
                  </div>
                  <div
                    v-show="state.contentSidebarVisible"
                    id="sidebar"
                    class="sidebar"
                  >
                    <slot name="sidebar"></slot>
                  </div>
                </div>

                <!-- Content Container -->
                <div tabindex="-1">
                  <!-- Error Summary -->
                  <slot name="errorSummary"></slot>

                  <!-- Content Pane -->
                  <div id="content" class="content-pane">
                    <!-- Content Header (tabs, notices) -->
                    <header
                      v-if="$slots.contentNotice || tabs"
                      id="content-header"
                      class="pane-header"
                    >
                      <div
                        v-if="$slots.contentNotice"
                        id="content-notice"
                        role="status"
                      >
                        <slot name="contentNotice"></slot>
                      </div>

                      <!-- Tabs -->
                      <div v-if="tabs" id="tabs" class="tabs">
                        <ul class="tab-list" role="tablist">
                          <li
                            v-for="tab in tabs"
                            :key="tab.id"
                            :class="[
                              'tab',
                              tab.class,
                              {
                                'is-active': activeTab === tab.id,
                                'has-error': tab.hasError,
                              },
                            ]"
                            :style="{
                              display:
                                tab.visible === false ? 'none' : undefined,
                            }"
                          >
                            <component
                              :is="tab.url ? 'a' : 'button'"
                              :href="tab.url"
                              :type="tab.url ? undefined : 'button'"
                              role="tab"
                              :aria-selected="activeTab === tab.id"
                              :aria-controls="`tab-${tab.id}`"
                              @click="!tab.url && setActiveTab(tab.id)"
                            >
                              {{ tab.label }}
                            </component>
                          </li>
                        </ul>
                      </div>
                    </header>

                    <!-- Main Content Slot -->
                    <slot></slot>

                    <!-- Content Footer -->
                    <div
                      v-if="$slots.contentFooter"
                      id="content-footer"
                      class="content-footer"
                    >
                      <slot name="contentFooter"></slot>
                    </div>
                  </div>
                </div>

                <!-- Details Toggle -->
                <div
                  v-if="$slots.details"
                  id="details-toggle-wrapper"
                  class="details-toggle-wrapper"
                  tabindex="-1"
                >
                  <button
                    type="button"
                    id="details-toggle"
                    class="details-toggle"
                    :aria-expanded="state.detailsVisible"
                    aria-controls="details-container"
                    @click="toggleDetails"
                  >
                    <span class="details-toggle__inner">
                      <craft-icon
                        :name="
                          state.detailsVisible ? 'angle-right' : 'angle-left'
                        "
                        aria-hidden="true"
                      ></craft-icon>
                      <span class="visually-hidden">{{
                        t('Toggle details sidebar')
                      }}</span>
                    </span>
                  </button>
                </div>

                <!-- Details Sidebar (right) -->
                <div
                  v-if="$slots.details"
                  class="details-container"
                  tabindex="-1"
                  :data-state="state.detailsVisible ? 'expanded' : 'collapsed'"
                >
                  <div v-show="state.detailsVisible" id="details">
                    <div class="details">
                      <slot name="details"></slot>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </component>
        </main>
      </slot>
    </div>

    <div class="cp__footer">
      <footer>
        <!-- Trial Info -->
        <div v-if="trialInfo" id="trial-info" class="trial-info">
          <span>
            {{ trialInfo.message }}
            <a :href="trialInfo.cartUrl" target="_blank" class="go">
              {{ t('Buy now') }}
            </a>
          </span>
        </div>

        <div :class="{container: true, 'container--full': fullWidth}">
          <slot name="footer"></slot>
        </div>
      </footer>
    </div>
  </div>

  <!-- Debug Panel -->
  <template v-if="debug">
    <div class="debug-panel">
      <div class="debug-announcement">
        {{ announcement ?? 'No announcement' }}
      </div>

      <div>
        <VarDump :data="debug" class="debug-dump" v-if="debugOpen" />
        <template v-if="debugOpen">
          <craft-button icon type="button" @click="debugOpen = false">
            <craft-icon :label="t('Close Debug panel')" name="x"></craft-icon>
          </craft-button>
        </template>
        <template v-else>
          <craft-button type="button" @click="debugOpen = true" icon>
            <craft-icon
              name="code"
              :label="t('Show debug variables')"
            ></craft-icon>
          </craft-button>
        </template>
      </div>
    </div>
  </template>
</template>

<style scoped lang="scss">
  .cp {
    display: grid;
  }

  .cp__main {
    container-type: size;
    padding-block-end: var(--c-spacing-2xl);
  }

  .cp__header {
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

  @media screen and (min-width: 1024px) {
    .cp {
      grid-template-columns: v-bind(sidebarWidth) minmax(0, 1fr);
      grid-template-areas: 'header header' 'sidebar main' 'sidebar footer';
      grid-template-rows: auto 1fr auto;
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

    .cp__footer {
      grid-area: footer;
    }
  }
  //
  //// Skip Links
  //.skip-links {
  //  position: absolute;
  //  top: 0;
  //  left: 0;
  //  z-index: 9999;
  //}
  //
  //.skip-link {
  //  position: absolute;
  //  top: -100%;
  //  left: 0;
  //  padding: var(--c-spacing-sm) var(--c-spacing-md);
  //  background-color: var(--c-color-primary-fill);
  //  color: white;
  //  text-decoration: none;
  //  border-radius: 0 0 var(--c-radius-md) 0;
  //  transition: top 0.2s ease-in-out;
  //
  //  &:focus {
  //    top: 0;
  //    outline: 2px solid var(--c-color-focus);
  //    outline-offset: 2px;
  //  }
  //}
  //
  //// Alerts
  //.alerts {
  //  background-color: var(--red-050, #fef2f2);
  //  border-left: 6px solid var(--error-color, #dc2626);
  //  color: var(--error-color, #dc2626);
  //}
  //
  //.alert-item {
  //  display: flex;
  //  min-height: var(--header-height, 48px);
  //  padding: var(--c-spacing-md);
  //}
  //
  //.alert-content {
  //  display: flex;
  //  align-items: center;
  //  gap: var(--c-spacing-sm);
  //}
  //
  //.alert-icon {
  //  flex-shrink: 0;
  //}

  // Global Header
  .global-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    gap: var(--c-spacing-sm);
  }

  .global-header__left {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
    flex: 1;
    min-width: 0;
  }

  .global-header__right {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .account-toggle-wrapper {
    position: relative;
  }

  .account-menu-header {
    padding: var(--c-spacing-md);
  }

  // Page Header
  .page-header {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: var(--c-spacing-md);
    padding-block: var(--c-spacing-md);
  }

  .page-header__title {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .page-header__actions {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  .screen-title {
    font-weight: 600;
    margin: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .toolbar {
    display: flex;
    align-items: center;
    gap: var(--c-spacing-sm);
  }

  // Main Content Layout
  .main-content {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--c-spacing-md);
  }

  .main-content.has-sidebar {
    grid-template-columns: auto 1fr;
  }

  .main-content.has-details {
    grid-template-columns: 1fr auto auto;
  }

  .main-content.has-sidebar.has-details {
    grid-template-columns: auto 1fr auto auto;
  }

  @media screen and (max-width: 768px) {
    .main-content {
      grid-template-columns: 1fr !important;
    }

    .sidebar-toggle-container {
      display: block;
    }
  }

  // Sidebar Container (content sidebar)
  .sidebar-container {
    min-width: 200px;
    max-width: 300px;
  }

  .sidebar-toggle-container {
    display: none;
  }

  .sidebar {
    padding: var(--c-spacing-md);
    background-color: var(--c-surface-overlay);
    border-radius: var(--c-radius-md);
  }

  // Content Container
  .content-container {
    flex: 1;
    min-width: 0;
  }

  .content-pane {
    background-color: var(--c-surface-overlay, white);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    box-shadow: var(--c-shadow-sm);
  }

  .pane-header {
    padding: var(--c-spacing-md);
    border-bottom: 1px solid var(--c-color-neutral-border-quiet);
  }

  .content-footer {
    display: flex;
    justify-content: space-between;
    padding: var(--c-spacing-md);
    border-top: 1px solid var(--c-color-neutral-border-quiet);
  }

  // Tabs
  .tabs {
    margin-top: var(--c-spacing-sm);
  }

  .tab-list {
    display: flex;
    gap: var(--c-spacing-md);
    list-style: none;
    margin: 0;
    padding: 0;
    border-bottom: 1px solid var(--c-color-neutral-border-quiet);
  }

  .tab {
    margin-bottom: -1px;

    a,
    button {
      display: block;
      padding: var(--c-spacing-sm) var(--c-spacing-md);
      border-bottom: 2px solid transparent;
      color: var(--c-color-text-muted);
      text-decoration: none;
      cursor: pointer;
      background: none;
      border: none;
      font: inherit;

      &:hover {
        color: var(--c-color-text);
        border-bottom-color: var(--c-color-neutral-border);
      }
    }

    &.is-active {
      a,
      button {
        color: var(--c-color-text);
        border-bottom-color: var(--c-color-primary-fill);
      }
    }

    &.has-error {
      a,
      button {
        color: var(--c-color-danger-fill);
      }
    }
  }

  // Details Toggle
  .details-toggle-wrapper {
    display: flex;
    align-items: flex-start;
  }

  .details-toggle {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 48px;
    background: var(--c-surface-overlay);
    border: 1px solid var(--c-color-neutral-border-quiet);
    border-radius: var(--c-radius-md);
    cursor: pointer;

    &:hover {
      background: var(--c-color-neutral-fill-quiet);
    }
  }

  .details-toggle__inner {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  // Details Container
  .details-container {
    width: calc(280rem / 16);
    min-width: 200px;
    max-width: 280px;
  }

  .details-container[data-state='collapsed'] {
    display: none;
  }

  .details {
    padding: var(--c-spacing-md);
  }

  // Global Footer
  .cp__footer {
    background-color: var(--c-surface-overlay);
    border-top: 1px solid var(--c-color-neutral-border-quiet);
  }

  .trial-info {
    padding: var(--c-spacing-sm) var(--c-spacing-md);
    text-align: center;
    background-color: var(--yellow-050, #fefce8);
    border-bottom: 1px solid var(--c-color-neutral-border-quiet);
  }

  .app-info {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: var(--c-spacing-md);
    padding: var(--c-spacing-sm);
    font-size: var(--c-font-size-sm);
    color: var(--c-color-text-muted);
  }

  .edition-logo {
    user-select: none;
    border: 1px solid currentColor;
    border-radius: 3px;
    display: inline-flex;
    box-sizing: content-box;
    font-size: 11px;
    padding-block: 4px;
    padding-inline: 5px 3px;
    line-height: 8px;
    font-weight: 600;
    letter-spacing: 1.5px;
    text-transform: uppercase;
  }

  // Utility classes
  .visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
  }

  .go::after {
    content: ' \2192';
  }

  // Debug Panel
  .debug-panel {
    position: fixed;
    bottom: var(--c-spacing-sm);
    right: var(--c-spacing-sm);
    display: flex;
    gap: var(--c-spacing-sm);
    justify-content: flex-end;
    align-items: center;
    padding: var(--c-spacing-sm);
    z-index: 1000;
  }

  .debug-announcement {
    background-color: var(--blue-050, #eff6ff);
    border: 1px solid var(--blue-500, #3b82f6);
    padding: var(--c-spacing-xs) var(--c-spacing-md);
    border-radius: var(--c-radius-md);
  }

  .debug-dump {
    max-height: 50vh;
    max-width: 600px;
    overflow: auto;
    position: absolute;
    bottom: 100%;
    right: 0;
    background: white;
    border: 1px solid var(--c-color-neutral-border);
    border-radius: var(--c-radius-md);
    box-shadow: var(--c-shadow-lg);
    margin-bottom: var(--c-spacing-sm);
  }
</style>
