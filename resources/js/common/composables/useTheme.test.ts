import {effectScope, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {useTheme} from './useTheme';

const page = reactive<{props: Record<string, any>}>({props: {}});

vi.mock('@inertiajs/vue3', () => ({usePage: () => page}));

let systemIsDark = false;
const listeners = new Set<(event: MediaQueryListEvent) => void>();

function setSystemDark(dark: boolean) {
  systemIsDark = dark;
  listeners.forEach((listener) =>
    listener({matches: dark} as MediaQueryListEvent)
  );
}

beforeEach(() => {
  vi.spyOn(window, 'matchMedia').mockImplementation(
    (query: string) =>
      ({
        media: query,
        get matches() {
          return systemIsDark;
        },
        addEventListener: (_: string, listener: never) =>
          listeners.add(listener),
        removeEventListener: (_: string, listener: never) =>
          listeners.delete(listener),
      }) as unknown as MediaQueryList
  );
});

afterEach(() => {
  delete document.documentElement.dataset.theme;
  page.props = {};
  systemIsDark = false;
  listeners.clear();
  vi.restoreAllMocks();
});

const theme = () => document.documentElement.getAttribute('data-theme');

function run(preference?: string) {
  page.props = {craft: {theme: preference}};
  const scope = effectScope();
  scope.run(() => useTheme());

  return scope;
}

it('applies the dark theme when it’s chosen', () => {
  const scope = run('dark');

  expect(theme()).toBe('dark');

  scope.stop();
});

it('follows the system theme when none is given', () => {
  systemIsDark = true;
  const scope = run();

  expect(theme()).toBe('dark');

  scope.stop();
});

it('leaves the light theme unmarked', () => {
  systemIsDark = true;
  const scope = run('light');

  expect(theme()).toBeNull();

  scope.stop();
});

it('follows the system theme as it changes', async () => {
  systemIsDark = true;
  const scope = run('system');

  expect(theme()).toBe('dark');

  setSystemDark(false);
  await nextTick();

  expect(theme()).toBeNull();

  setSystemDark(true);
  await nextTick();

  expect(theme()).toBe('dark');

  scope.stop();
  expect(listeners.size).toBe(0);
});

it('follows the preference from page to page', async () => {
  const scope = run('dark');

  page.props = {craft: {theme: 'light'}};
  await nextTick();

  expect(theme()).toBeNull();

  scope.stop();
});

it('reports the theme it shows to the server', async () => {
  systemIsDark = true;
  const scope = run('system');

  expect(document.cookie).toContain('CraftTheme=dark');

  setSystemDark(false);
  await nextTick();

  expect(document.cookie).toContain('CraftTheme=light');

  scope.stop();
});
