<script setup lang="ts">
  import {t} from '@craftcms/cp';
  import SystemInfo from '@/components/SystemInfo.vue';
  import MainNav from '@/components/MainNav.vue';
  import EditionInfo from '@/components/EditionInfo.vue';
  import DevModeIndicator from '@/components/DevModeIndicator.vue';
  import {watch, computed, nextTick} from 'vue';

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'dock'): void;
  }>();

  const {mode = 'floating', visibility = 'hidden'} = defineProps<{
    mode: 'docked' | 'floating' | 'collapsed';
    visibility: 'hidden' | 'visible';
  }>();

  const shouldManageFocus = computed(() => {
    return mode === 'floating';
  });

  watch(
    () => visibility,
    async (newVal) => {
      if (shouldManageFocus.value && newVal === 'visible') {
        await nextTick();
        const sidebar = document.querySelector('.cp-sidebar') as HTMLElement;
        const firstFocusable = sidebar.querySelector(
          'button, [href], [tabindex]:not([tabindex="-1"])'
        ) as HTMLElement;
        firstFocusable?.focus();
      }
    }
  );
</script>

<template>
  <nav class="cp-sidebar" :data-visibility="visibility" :data-mode="mode" aria-labelledby="cp-sidebar-label">
    <span id="cp-sidebar-label" aria-hidden class="sr-only">{{ t('Primary') }}</span>
    <template v-if="visibility === 'visible'">
      <div class="cp-sidebar__header">
        <div class="sidebar-header" v-if="mode !== 'docked'">
          <SystemInfo />
          <div class="ml-auto"></div>
          <craft-button size="small" icon @click="emit('close')" type="button">
            <craft-icon name="x" style="font-size: 0.7em"></craft-icon>
          </craft-button>
        </div>
      </div>
      <div class="cp-sidebar__body">
        <MainNav />
      </div>
      <div class="cp-sidebar__footer">
        <EditionInfo />
        <DevModeIndicator />
      </div>
    </template>
  </nav>
</template>

<style scoped lang="scss">
  .cp-sidebar {
    height: 100%;
    width: var(--global-sidebar-width);
    background-color: var(--c-bg-overlay);
    display: grid;
    grid-template-rows: minmax(0, auto) 1fr minmax(0, auto);
  }

  .cp-sidebar[data-mode='docked'] {
    position: relative;
    transform: 0;
  }

  .cp-sidebar[data-mode='floating'] {
    position: fixed;
    inset-block-start: 0;
    inset-block-end: 0;
    inset-inline-start: 0;
    inset-inline-end: auto;
    border-radius: 0 var(--c-radius-md) var(--c-radius-md) 0;
    box-shadow: var(--c-shadow-lg);
    transform: translateX(0);
    max-width: 90%;
    z-index: 100;
    transition: transform 200ms cubic-bezier(0, 0.55, 0.45, 1);
  }

  .cp-sidebar[data-visibility='hidden'] {
    transform: translateX(-100%);
  }

  .cp-sidebar__body {
    padding-block: var(--c-spacing-md);
    padding-inline: var(--c-spacing-md);
  }

  .sidebar-header {
    padding-block: var(--c-spacing-md);
    padding-inline: var(--c-spacing-md);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .cp-sidebar__body {
    overflow-y: scroll;
    background:
      /* Shadow Cover TOP */
      linear-gradient(white 30%, rgba(255, 255, 255, 0)) center top,
      /* Shadow Cover BOTTOM */
      linear-gradient(rgba(255, 255, 255, 0), white 70%) center bottom,
      /* Shadow TOP */
      linear-gradient(to bottom, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0)) center
        top,
      /* Shadow BOTTOM */
      linear-gradient(to top, rgba(0, 0, 0, 0.1), rgba(0, 0, 0, 0)) center
        bottom;
    background-repeat: no-repeat;
    background-size:
      100% 2.5rem,
      100% 2.5rem,
      100% 0.5rem,
      100% 0.5rem;
    background-attachment: local, local, scroll, scroll;
  }
</style>
