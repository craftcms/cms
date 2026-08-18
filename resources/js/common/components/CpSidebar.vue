<script setup lang="ts">
import { ButtonVariant, t } from "@craftcms/ui";
import SystemInfo from "@/common/components/SystemInfo.vue";
import MainNav from "@/common/components/MainNav.vue";
import EditionInfo from "@/common/components/EditionInfo.vue";
import DevModeIndicator from "@/common/components/DevModeIndicator.vue";
import { computed, nextTick, watch } from "vue";
import { useGlobalSidebar } from "@/common/composables/useGlobalSidebar";

// Mode and visibility come from the shared store rather than from props: this
// component renders the toggle that changes them, so taking them as props too
// would give the same state two sources of truth.
const { sidebar, toggle, icon } = useGlobalSidebar();

const shouldManageFocus = computed(() => sidebar.mode === "floating");

watch(
  () => sidebar.visibility,
  async (visibility) => {
    if (shouldManageFocus.value && visibility === "visible") {
      await nextTick();
      const el = document.querySelector(".cp-sidebar") as HTMLElement | null;
      const firstFocusable = el?.querySelector(
        'button, [href], [tabindex]:not([tabindex="-1"])',
      ) as HTMLElement | undefined;
      firstFocusable?.focus();
    }
  },
);
</script>

<template>
  <nav
    class="cp-sidebar"
    :data-visibility="sidebar.visibility"
    :data-mode="sidebar.mode"
    :aria-label="t('Primary')"
  >
    <template v-if="sidebar.visibility === 'visible'">
      <div class="cp-sidebar__header">
        <div class="sidebar-header">
          <SystemInfo />

          <craft-button
            type="button"
            size="small"
            :icon="icon"
            :variant="ButtonVariant.Outline"
            @click="toggle"
            :aria-label="t('Toggle menu')"
          >
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
  background-color: white;
  overflow: clip;
  margin-inline-end: var(--c-spacing-md);
  box-shadow: var(--c-shadow-md);
}

.cp-sidebar[data-mode="docked"] {
  transform: none;
  position: sticky;
  inset-block-start: 0;
}

.cp-sidebar[data-mode="floating"] {
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

.cp-sidebar[data-visibility="hidden"] {
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
