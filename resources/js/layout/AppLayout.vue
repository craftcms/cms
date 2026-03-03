<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import SystemInfo from '@/components/SystemInfo.vue';
  import {computed, reactive, ref, watch, useTemplateRef} from 'vue';
  import CpSidebar from '@/components/CpSidebar.vue';
  import {useMediaQuery} from '@vueuse/core';
  import {Head, usePage} from '@inertiajs/vue3';
  import VarDump from '@/components/VarDump.vue';
  import Breadcrumbs from '@/components/Breadcrumbs.vue';
  import {useAnnouncer} from '@/composables/useAnnouncer';
  import LiveRegion from '@/components/LiveRegion.vue';

  const props = withDefaults(
    defineProps<{
      title?: string;
      debug?: any;
      fullWidth?: boolean;
      additionalSkipLinks?: Array<{label: string; url: string}>;
    }>(),
    {fullWidth: false, crumbs: () => []}
  );

  const page = usePage<{
    flash: {
      success: string | null;
      error: string | null;
    };
    crumbs?: Array<{
      url?: string;
      label: string;
    }> | null;
  }>();
  const errorFlash = computed(() => page.props.flash?.error);
  const successFlash = computed(() => page.props.flash?.success);
  const crumbs = computed(() => page.props.crumbs ?? null);
  const skipLinks = computed(() => [
    {label: t('Skip to main section'), url: '#main'},
    ...(props.additionalSkipLinks ?? []),
  ]);
  const sidebarToggle = useTemplateRef('sidebarToggle');
  const {announcement} = useAnnouncer();

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
  <Head :title="title" />
  <LiveRegion :debug="true"></LiveRegion>
  <div class="cp">
    <header class="cp__header">
      <a
        v-for="link in skipLinks"
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
          <craft-icon :name="sidebarIcon"></craft-icon>
        </craft-button>
        <SystemInfo v-if="isLargeScreen" />

        <div class="ml-auto"></div>
        <craft-button icon appearance="plain">
          <craft-icon name="search"></craft-icon>
        </craft-button>
      </div>
      <!-- TODO: this is just temporary placement -->
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
          <div class="px-4 py-2 border-b border-b-border-subtle" v-if="crumbs">
            <Breadcrumbs :items="crumbs" />
          </div>
        </slot>
        <main id="main" tabindex="-1">
          <slot name="header">
            <div :class="{container: true, 'container--full': fullWidth}">
              <div class="index-grid index-grid--header">
                <div class="index-grid__aside">
                  <slot name="title">
                    <h1 class="text-xl">{{ title }}</h1>
                  </slot>
                  <slot name="title-badge"></slot>
                </div>

                <div class="index-grid__main">
                  <slot name="actions"></slot>
                </div>
              </div>
            </div>
          </slot>
          <div :class="{container: true, 'container--full': fullWidth}">
            <slot></slot>
          </div>
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
