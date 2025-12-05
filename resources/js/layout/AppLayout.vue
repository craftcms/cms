<script setup lang="ts">
  import SystemInfo from '@/components/SystemInfo.vue';
  import {computed, reactive, watch} from 'vue';
  import CpSidebar from '@/components/CpSidebar.vue';
  import {useMediaQuery} from '@vueuse/core';
  import {Head} from '@inertiajs/vue3';

  defineProps<{
    title: string;
  }>();

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
            <div class="pb-2 pt-4 px-4 flex justify-between items-center">
              <slot name="title">
                <h1 class="text-xl">{{ title }}</h1>
              </slot>

              <div class="flex gap-2 items-center">
                <slot name="actions"></slot>
              </div>
            </div>
          </slot>
          <slot></slot>
        </main>
      </slot>
    </div>
    <div class="cp__footer">
      <footer>
        <slot name="footer"></slot>
      </footer>
    </div>
  </div>
</template>

<style scoped lang="css">
  .cp {
    display: grid;
  }

  .cp__header {
    color: var(--color-slate-200);
    background-color: var(--color-slate-950);
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
