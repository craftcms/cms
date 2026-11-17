import {usePage} from '@inertiajs/vue3';
import {getCurrentScope, onScopeDispose, ref, watchEffect} from 'vue';
import type {CraftData} from './useCraftData';

/**
 * Tells the server which theme is showing, matching `Theme::COOKIE`, since it
 * can't see the operating system's setting behind the System theme.
 */
const THEME_COOKIE = 'CraftTheme';

/**
 * Remembers the signed-in user's preference, matching
 * `Theme::PREFERENCE_COOKIE`, so logged-out pages such as the login page keep
 * showing the theme the last user chose.
 */
const PREFERENCE_COOKIE = 'CraftThemePreference';

function setCookie(name: string, value: string): void {
  document.cookie = `${name}=${value}; path=/; max-age=31536000; samesite=lax${location.protocol === 'https:' ? '; secure' : ''}`;
}

/**
 * Applies the control panel theme as `data-theme="dark"` on `<html>`, when the
 * user chose Dark, or System — the default — and the operating system is dark.
 * Logged-out pages get the last signed-in user's preference from the server.
 * Read from the shared page props, so saving the preference takes effect
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

    setCookie(THEME_COOKIE, dark ? 'dark' : 'light');

    if (page.props?.craft?.currentUser) {
      setCookie(PREFERENCE_COOKIE, theme);
    }
  });
}
