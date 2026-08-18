<script setup lang="ts">
  import {t} from '@craftcms/ui';
  import SystemInfo from '@/common/components/SystemInfo.vue';
  import MainNav from '@/common/components/MainNav.vue';
  import EditionInfo from '@/common/components/EditionInfo.vue';
  import DevModeIndicator from '@/common/components/DevModeIndicator.vue';
  import {computed, nextTick, watch} from 'vue';

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
  <nav
    class="cp-sidebar"
    :data-visibility="visibility"
    :data-mode="mode"
    :aria-label="t('Primary')"
  >
    <template v-if="visibility === 'visible'">
      <div class="cp-sidebar__header">
        <div class="sidebar-header" v-if="mode !== 'docked'">
          <SystemInfo />
          <div class="ml-auto"></div>
          <craft-button size="small" icon @click="emit('close')" type="button">
            <craft-icon
              name="x"
              style="font-size: 0.7em"
              :label="t('Close')"
            ></craft-icon>
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
    height: 100dvh;
    width: var(--global-sidebar-width);
    display: flex;
    flex-direction: column;
    inset-block-start: 0;
    flex: 0 0 auto;
  }

  .cp-sidebar[data-mode='docked'] {
    transform: none;
    position: sticky;
    inset-block-start: 0;
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

  .cp-sidebar__header {
    flex: 0 0 auto;
  }

  .cp-sidebar__body {
    padding-block: var(--c-spacing-md);
    padding-inline: var(--c-spacing-md);
    flex: 1 1 auto;
    min-height: 0;
  }

  .cp-sidebar__footer {
    flex: 0 0 auto;
    position: sticky;
    inset-block-end: 0;
    background-color: inherit;
  }

  .sidebar-header {
    padding-block: var(--c-spacing-md);
    padding-inline: var(--c-spacing-md);
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .cp-sidebar__body {
    overflow-y: auto;
    scrollbar-gutter: stable;
  }
</style>
