<script setup lang="ts">
  import {ButtonVariant, t} from '@craftcms/ui';
  import SystemInfo from '@/common/components/SystemInfo.vue';
  import MainNav from '@/common/components/MainNav.vue';
  import EditionInfo from '@/common/components/EditionInfo.vue';
  import {computed, nextTick, useTemplateRef, watch} from 'vue';
  import {useGlobalSidebar} from '@/common/composables/useGlobalSidebar';
  import {type CraftData} from '@/common/composables/useCraftData';
  import {usePage} from '@inertiajs/vue3';
  import {cpBreakpoints} from '@/common/composables/useCpBreakpoints';

  const isSmall = cpBreakpoints.smaller('sm');

  // Mode and visibility come from the shared store rather than from props: this
  // component renders the toggle that changes them, so taking them as props too
  // would give the same state two sources of truth.
  const {sidebar, collapsed} = useGlobalSidebar();
  const shouldManageFocus = computed(() => sidebar.mode === 'floating');

  watch(
    () => sidebar.visibility,
    async (visibility) => {
      if (shouldManageFocus.value && visibility === 'visible') {
        await nextTick();
        const sidebar = document.querySelector<HTMLElement>('.cp-sidebar');
        const firstFocusable = sidebar?.querySelector<HTMLElement>(
          'button, [href], [tabindex]:not([tabindex="-1"])'
        );
        firstFocusable?.focus();
      }
    }
  );

  const {toggle: toggleSidebar, icon} = useGlobalSidebar();

  const collapseItem = useTemplateRef<HTMLElement>('collapseItem');

  // The item re-renders as a different element when the nav collapses, which
  // would otherwise drop focus to the page.
  async function toggleCollapsed() {
    toggleSidebar();
    await nextTick();
    await (
      collapseItem.value as
        | (HTMLElement & {updateComplete?: Promise<unknown>})
        | null
    )?.updateComplete;
    collapseItem.value?.focus();
  }
</script>

<template>
  <nav
    class="cp-sidebar"
    :data-visibility="sidebar.visibility"
    :data-mode="sidebar.mode"
    :class="{'cp-sidebar--collapsed': collapsed}"
    :inert="sidebar.mode === 'floating' && sidebar.visibility === 'hidden'"
    :aria-label="t('Primary')"
  >
    <div class="cp-sidebar__header" v-if="isSmall">
      <SystemInfo />
      <craft-button
        id="sidebar-toggle"
        type="button"
        size="small"
        icon="x"
        :variant="ButtonVariant.Plain"
        @click="toggleSidebar"
        :aria-label="t('Toggle menu')"
      >
      </craft-button>
    </div>
    <div class="cp-sidebar__body">
      <!-- Floating, the sidebar overlays the page and there's nowhere for a
        flyout to go, so every branch expands in place instead. -->
      <MainNav
        :icon-only="collapsed"
        :mode="sidebar.mode === 'floating' ? 'inline' : 'trail'"
      />
    </div>
    <div v-if="sidebar.mode === 'docked'" class="cp-sidebar__footer">
      <craft-nav-list>
        <craft-nav-item
          ref="collapseItem"
          :button="true"
          :icon="icon"
          :icon-only="collapsed || undefined"
          @click="toggleCollapsed"
        >
          {{ collapsed ? t('Expand') : t('Collapse') }}
        </craft-nav-item>
      </craft-nav-list>
    </div>
  </nav>
</template>

<style scoped lang="scss">
  .cp-sidebar {
    z-index: var(--global-sidebar-z-index);
    height: 100dvh;
    width: var(--global-sidebar-width);
    display: flex;
    flex-direction: column;
    inset-block-start: 0;
    flex: 0 0 auto;
    border-inline-end: 1px solid var(--c-color-border-quiet);
    overflow: clip;
  }

  .cp-sidebar[data-mode='docked'] {
    height: calc(100dvh - var(--cp-debug-bar-height, 0px));
    transform: none;
    position: sticky;
    inset-block-start: 0;
  }

  .cp-sidebar[data-mode='floating'] {
    z-index: var(--c-layer-overlay);
    position: fixed;
    inset-block-start: 0;
    inset-block-end: 0;
    inset-inline-start: 0;
    inset-inline-end: auto;
    border-radius: 0 var(--c-radius-md) var(--c-radius-md) 0;
    transform: translateX(0);
    width: clamp(calc(240rem / 16), 60dvw, calc(320rem / 16));
    transition: transform 200ms cubic-bezier(0, 0.55, 0.45, 1);
    background-color: var(--c-surface-overlay);
    box-shadow: var(--c-shadow-overlay);
  }

  /* Only a floating sidebar leaves; a docked one narrows to the icon rail. */
  .cp-sidebar[data-mode='floating'][data-visibility='hidden'] {
    transform: translateX(-100%);
  }

  .cp-sidebar--collapsed {
    width: var(--global-sidebar-collapsed-width);
  }

  .cp-sidebar__header {
    display: flex;
    justify-content: space-between;
    padding: var(--c-spacing-md);
    flex: 0 0 auto;
  }

  .cp-sidebar__body {
    padding-block: var(--c-spacing-md);
    padding-inline: var(--c-spacing-md);
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    scrollbar-gutter: stable;
  }

  .cp-sidebar__footer {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    margin-block-start: auto;
    padding-block: var(--c-spacing-md);
    padding-inline: var(--c-spacing-md);
    min-height: calc(62rem / 16);
    position: sticky;
    z-index: 1;
    inset-block-end: var(--cp-debug-bar-height, 0px);
    background-color: var(--c-surface-sunken);
    border-block-start: 1px solid var(--c-color-border-quiet);
  }
</style>
