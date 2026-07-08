import {computed, reactive, useTemplateRef, watch} from 'vue';
import {useMediaQuery} from '@vueuse/core';

/**
 * State for the CP's global sidebar: docked and always visible on large
 * screens, floating and hidden behind a toggle button below that.
 *
 * The component calling this must put `ref="sidebarToggle"` on its toggle
 * button so focus can be restored when the floating sidebar closes.
 */
export function useGlobalSidebar() {
  const isLargeScreen = useMediaQuery('(min-width: 1024px)');
  const toggleButton = useTemplateRef<HTMLButtonElement>('sidebarToggle');

  const sidebar = reactive<{
    mode: 'docked' | 'floating';
    visibility: 'hidden' | 'visible';
  }>({
    mode: 'floating',
    visibility: 'hidden',
  });

  watch(
    isLargeScreen,
    (value) => {
      if (value) {
        sidebar.mode = 'docked';
        sidebar.visibility = 'visible';
      } else {
        sidebar.mode = 'floating';
        sidebar.visibility = 'hidden';
      }
    },
    {immediate: true}
  );

  function toggle() {
    sidebar.visibility =
      sidebar.visibility === 'visible' ? 'hidden' : 'visible';
  }

  function close() {
    sidebar.visibility = 'hidden';
    toggleButton.value?.focus();
  }

  const icon = computed(() =>
    sidebar.visibility === 'visible' ? 'x' : 'bars'
  );

  const width = computed(() => {
    if (sidebar.mode === 'docked') {
      return sidebar.visibility === 'visible'
        ? 'var(--global-sidebar-width)'
        : '0';
    }

    return 'auto';
  });

  return {isLargeScreen, sidebar, toggle, close, icon, width};
}
