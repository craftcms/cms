import {usePage} from '@inertiajs/vue3';
import {getCurrentScope, onScopeDispose, ref, watchEffect} from 'vue';
import type {CraftData} from './useCraftData';

/**
 * Applies the control panel theme as `data-theme="dark"` on `<html>`, when the
 * user chose Dark, or System — the default, and what logged-out pages use — and
 * the operating system is dark. Read from the shared page props, so saving the preference takes effect
 * without a full reload, and System follows the operating system as it changes.
 */
export function useTheme(): void {
  const page = usePage<{craft?: CraftData}>();
  const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');
  const systemIsDark = ref(darkQuery.matches);
  const onSystemChange = (event: MediaQueryListEvent) => {
    systemIsDark.value = event.matches;
  };

  darkQuery.addEventListener('change', onSystemChange);
  if (getCurrentScope()) {
    onScopeDispose(() =>
      darkQuery.removeEventListener('change', onSystemChange)
    );
  }

  watchEffect(() => {
    const theme = page.props?.craft?.theme ?? 'system';
    const dark = theme === 'dark' || (theme === 'system' && systemIsDark.value);

    if (dark) {
      document.documentElement.dataset.theme = 'dark';
    } else {
      delete document.documentElement.dataset.theme;
    }
  });
}
