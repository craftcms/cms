<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import SystemInfo from '@/components/SystemInfo.vue';
  import {computed, reactive, ref, watch} from 'vue';
  import CpSidebar from '@/components/CpSidebar.vue';
  import {useMediaQuery} from '@vueuse/core';
  import {Head, usePage} from '@inertiajs/vue3';
  import VarDump from '@/components/VarDump.vue';

  withDefaults(
    defineProps<{
      title?: string;
      debug?: any;
      fullWidth?: boolean;
    }>(),
    {fullWidth: false}
  );

  const page = usePage<{
    flash: {
      success: string | null;
      error: string | null;
    };
  }>();
  const errorFlash = computed(() => page.props.flash?.error);
  const successFlash = computed(() => page.props.flash?.success);

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
  <div class="cp">
    <div class="cp__header">
      <div class="flex gap-2 p-2">
        <craft-button
          icon
          type="button"
          appearance="plain"
          @click="toggleSidebar"
          v-if="!isLargeScreen"
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
    </div>
    <div class="cp__sidebar">
      <CpSidebar
        :mode="state.sidebar.mode"
        :visibility="state.sidebar.visibility"
        @close="state.sidebar.visibility = 'hidden'"
      />
    </div>
    <div class="cp__main">
      <slot name="main">
        <main>
          <slot name="header">
            <div :class="{container: true, 'container--full': fullWidth}">
              <div class="flex justify-between items-center pt-4 pb-2">
                <slot name="title">
                  <h1 class="text-xl">{{ title }}</h1>
                </slot>

                <div class="flex gap-2 items-center">
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
    <div class="fixed bottom-2 right-2 max-w-[600px]">
      <div class="absolute top-2 right-2" v-if="debugOpen">
        <craft-button
          icon
          size="small"
          type="button"
          @click="debugOpen = false"
        >
          <craft-icon :label="t('Close Debug panel')" name="x"></craft-icon>
        </craft-button>
      </div>
      <div v-else>
        <craft-button type="button" @click="debugOpen = true" icon>
          <craft-icon
            name="code"
            :label="t('Show debug variables')"
          ></craft-icon>
        </craft-button>
      </div>
      <VarDump
        :data="debug"
        class="max-h-[50vh] overflow-scroll"
        v-if="debugOpen"
      />
    </div>
  </template>
</template>

<style scoped lang="css">
  .cp {
    display: grid;
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
    }

    .cp__header {
      grid-area: header;
    }

    .cp__sidebar {
      grid-area: sidebar;
    }

    .cp__main {
      grid-area: main;
    }
  }
</style>
