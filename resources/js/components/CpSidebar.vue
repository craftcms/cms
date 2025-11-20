<script setup lang="ts">
  import SystemInfo from '@/components/SystemInfo.vue';
  import MainNav from '@/components/MainNav.vue';
  import EditionInfo from '@/components/EditionInfo.vue';
  import DevModeIndicator from '@/components/DevModeIndicator.vue';

  const emit = defineEmits<{
    (e: 'close'): void;
    (e: 'dock'): void;
  }>();
  withDefaults(
    defineProps<{
      mode: 'docked' | 'floating' | 'collapsed';
      visibility: 'hidden' | 'visible';
    }>(),
    {
      mode: 'floating',
      visibility: 'hidden',
    }
  );
</script>

<template>
  <div class="cp-sidebar" :data-visibility="visibility" :data-mode="mode">
    <div class="cp-sidebar__header">
      <div class="sidebar-header" v-if="mode !== 'docked'">
        <SystemInfo />
        <div class="tw:ml-auto"></div>
        <craft-button size="small" icon @click="emit('close')">
          <craft-icon name="x" style="font-size: 0.7em"></craft-icon>
        </craft-button>
      </div>
    </div>
    <aside class="cp-sidebar__body">
      <MainNav />
    </aside>
    <div class="cp-sidebar__footer">
      <EditionInfo />
      <DevModeIndicator />
    </div>
  </div>
</template>

<style scoped lang="scss">
  .cp-sidebar {
    height: 100%;
    width: var(--cp-sidebar-width);
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
