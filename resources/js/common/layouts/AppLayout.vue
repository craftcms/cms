<script setup lang="ts">
  import SystemInfo from '@/common/components/SystemInfo.vue';
  import {t} from '@craftcms/cp/utilities/translate.ts.mjs';
  import {computed, reactive, ref, useSlots, useTemplateRef, watch} from 'vue';
  import CpSidebar from '@/common/components/CpSidebar.vue';
  import {useMediaQuery} from '@vueuse/core';
  import {Head, type InertiaForm, usePage} from '@inertiajs/vue3';
  import VarDump from '@/common/components/VarDump.vue';
  import Breadcrumbs from '@/common/components/Breadcrumbs.vue';
  import {useAnnouncer} from '@/common/composables/useAnnouncer';
  import LiveRegion from '@/common/components/LiveRegion.vue';
  import {useAppendHtml} from '@/common/composables/useAppendHtml';
  import ActionMenu from '@/common/components/ActionMenu.vue';
  import type {ActionItem} from '@/common/types';
  import {useFlash} from '@/common/composables/useFlash';
  import InlineFlash from '@/common/components/InlineFlash.vue';
  import ErrorSummary from '@/common/form/ErrorSummary.vue';
  import CalloutReadOnly from '@/common/components/CalloutReadOnly.vue';
  import UserMenu from '@/common/components/UserMenu.vue';
  import FlashMessages from '@/common/components/FlashMessages.vue';
  import Pane from '@/common/components/Pane.vue';

  interface SaveOptions {
    redirect?: boolean;
  }

  type DefaultFormAction = 'saveAndContinueEditing';

  export interface AppLayoutProps {
    title?: string;
    debug?: any;
    fullWidth?: boolean;
    form?: InertiaForm<any> | null;
    defaultFormActions?: Array<DefaultFormAction>;
    formActions?: Array<ActionItem>;
    formAdditionalActions?: Array<any>;
    additionalSkipLinks?: Array<{label: string; url: string}>;
  }

  const emit = defineEmits<{
    (e: 'save', options?: Partial<SaveOptions>): void;
  }>();
  const props = withDefaults(defineProps<AppLayoutProps>(), {
    fullWidth: false,
    crumbs: () => [],
    form: null,
    defaultFormActions: () => ['saveAndContinueEditing'],
  });

  const page = usePage<{
    title: string;
    readOnly: boolean;
    crumbs?: Array<{
      url?: string;
      label: string;
    }> | null;
  }>();

  const {errorFlash, successFlash} = useFlash();
  const slots = useSlots();
  const crumbs = computed(() => page.props.crumbs ?? null);
  const formActionItems = computed(() => [
    ...props.defaultFormActions.map(defaultFormActionItem),
    ...(props.formActions ?? []),
  ]);
  const skipLinks = computed(() => [
    {label: t('Skip to main section'), url: '#main'},
    ...(props.additionalSkipLinks ?? []),
  ]);
  const readOnly = computed(() => page.props.readOnly);
  const hasDetails = computed(() => Boolean(slots.details));
  const sidebarToggle = useTemplateRef('sidebarToggle');
  const {announcement, announce} = useAnnouncer();

  watch(successFlash, (newMessage) => announce(newMessage));
  watch(errorFlash, (newMessage) => announce(newMessage));

  useAppendHtml();

  const state = reactive<{
    sidebar: {
      mode: 'docked' | 'floating';
      visibility: 'hidden' | 'visible';
    };
  }>({
    sidebar: {
      mode: 'floating',
      visibility: 'hidden',
    },
  });

  const isLargeScreen = useMediaQuery('(min-width: 1024px)');
  const debugOpen = ref(false);
  const pageTitle = computed(() => props.title?.trim() ?? page.props.title);

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
    if (state.sidebar.visibility === 'visible') {
      state.sidebar.visibility = 'hidden';
    } else {
      state.sidebar.visibility = 'visible';
    }
  }

  function closeSidebar() {
    state.sidebar.visibility = 'hidden';
    (sidebarToggle.value as HTMLButtonElement).focus();
  }

  const sidebarIcon = computed(() => {
    return state.sidebar.visibility === 'visible' ? 'x' : 'bars';
  });

  function defaultFormActionItem(action: DefaultFormAction): ActionItem {
    if (action === 'saveAndContinueEditing') {
      return {
        label: t('Save and continue editing'),
        onClick: () => emit('save', {redirect: false}),
        shortcut: 'S',
      };
    }

    throw new Error(`Unknown default form action: ${action}`);
  }

  const sidebarWidth = computed(() => {
    if (state.sidebar.mode === 'docked') {
      return state.sidebar.visibility === 'visible'
        ? 'var(--global-sidebar-width)'
        : '0';
    }
    return 'auto';
  });
</script>

<template>
  <Head :title="pageTitle" />
  <LiveRegion :debug="true"></LiveRegion>
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
        :mode="state.sidebar.mode"
        :visibility="state.sidebar.visibility"
        @close="closeSidebar"
      />
    </div>
    <div class="cp__main">
      <slot name="main">
        <slot name="breadcrumbs">
          <div
            class="px-4 py-2 border-b border-b-neutral-border-quiet"
            v-if="crumbs"
          >
            <Breadcrumbs :items="crumbs" />
          </div>
        </slot>
        <main id="main" tabindex="-1">
          <component
            :is="form ? 'form' : 'div'"
            method="post"
            @submit.prevent="emit('save')"
          >
            <slot name="header">
              <div :class="{container: true, 'container--full': fullWidth}">
                <div class="index-grid index-grid--header">
                  <div class="index-grid__aside">
                    <slot name="title">
                      <h1 class="text-xl">{{ pageTitle }}</h1>
                    </slot>
                    <slot name="title-badge"></slot>
                  </div>

                  <div class="index-grid__main">
                    <slot name="actions">
                      <template v-if="form">
                        <InlineFlash
                          :is-active="form.recentlySuccessful || form.hasErrors"
                        />

                        <div
                          v-if="!readOnly"
                          class="flex items-center justify-end gap-2"
                        >
                          <craft-button-group v-if="formActionItems.length">
                            <craft-button
                              type="submit"
                              variant="accent"
                              :loading="form.processing"
                            >
                              {{ t('Save') }}
                            </craft-button>
                            <ActionMenu
                              icon="chevron-down"
                              :actions="formActionItems"
                            >
                              <template #invoker="{label}">
                                <craft-button
                                  slot="invoker"
                                  variant="accent"
                                  type="button"
                                  icon
                                >
                                  <craft-icon
                                    name="chevron-down"
                                    :label="label"
                                  ></craft-icon>
                                </craft-button>
                              </template>
                            </ActionMenu>
                          </craft-button-group>

                          <craft-button
                            v-else
                            type="submit"
                            variant="accent"
                            :loading="form.processing"
                          >
                            {{ t('Save') }}
                          </craft-button>

                          <ActionMenu
                            v-if="formAdditionalActions?.length"
                            :actions="formAdditionalActions"
                          />
                        </div>
                      </template>
                    </slot>
                  </div>
                </div>
              </div>
            </slot>
            <div :class="{container: true, 'container--full': fullWidth}">
              <ErrorSummary
                v-if="form && form.hasErrors"
                :errors="form.errors"
              />
              <template v-if="readOnly">
                <CalloutReadOnly />
              </template>
              <template v-if="hasDetails">
                <div class="content-with-details">
                  <div>
                    <slot></slot>
                  </div>
                  <aside>
                    <Pane appearance="raised">
                      <div class="details">
                        <slot name="details"></slot>
                      </div>
                    </Pane>
                  </aside>
                </div>
              </template>
              <slot v-else></slot>
            </div>
          </component>
        </main>
      </slot>
    </div>
    <div class="cp__footer">
      <footer>
        <div :class="{container: true, 'container--full': fullWidth}">
          <slot name="footer"></slot>
        </div>
      </footer>
    </div>
  </div>

  <template v-if="debug">
    <div class="fixed bottom-2 right-2 flex gap-2 justify-end items-center p-2">
      <div class="bg-blue-50 border border-blue-500 py-1 px-4 rounded">
        {{ announcement ?? 'No announcement' }}
      </div>

      <div>
        <VarDump
          :data="debug"
          class="max-h-[50vh] max-w-[600px] overflow-scroll absolute transform -translate-full"
          v-if="debugOpen"
        />
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

<style scoped lang="css">
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

  .content-with-details {
    display: grid;
    gap: var(--c-spacing-md);

    @container (width >= 768px) {
      grid-template-columns: minmax(0, 1fr) clamp(12rem, 20%, 16rem);
      align-items: start;
    }
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
