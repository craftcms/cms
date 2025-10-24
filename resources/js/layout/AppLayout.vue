<script setup lang="ts">
  import MainNav from '@/components/MainNav.vue';
  import SystemInfo from '@/components/SystemInfo.vue';
  import {computed, reactive, ref, watch} from 'vue';
  import CpSidebar from '@/components/CpSidebar.vue';
  import VarDump from '@/components/VarDump.vue';
  import {useMediaQuery} from '@vueuse/core';

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
        ? 'var(--cp-sidebar-width)'
        : '0';
    }
    return 'auto';
  });
</script>

<template>
  <div class="cp">
    <div class="cp__header">
      <div class="tw:flex tw:gap-2">
        <craft-button
          icon
          appearance="plain"
          @click="toggleSidebar"
          v-if="!isLargeScreen"
        >
          <craft-icon :name="sidebarIcon"></craft-icon>
        </craft-button>
        <SystemInfo v-if="isLargeScreen" />

        <div class="tw:ml-auto"></div>
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
      <main>
        <div
          class="tw:pb-2 tw:pt-4 tw:px-4 tw:flex tw:justify-between tw:items-center"
        >
          <slot name="title">
            <h1 class="tw:text-xl">{{ title }}</h1>
          </slot>

          <div class="tw:flex tw:gap-2">
            <slot name="actions"></slot>
          </div>
        </div>
        <slot></slot>
      </main>
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
    padding: var(--c-spacing-md);
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
