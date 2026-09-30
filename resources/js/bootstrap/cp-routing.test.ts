import {afterEach, expect, it, vi} from 'vite-plus/test';
import {useAnnouncer} from '@/common/composables/useAnnouncer';

vi.mock('@inertiajs/vue3', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@inertiajs/vue3')>()),
  createInertiaApp: vi.fn(async () => {}),
}));
vi.mock('./cp-app', () => ({
  config: {
    initialize: vi.fn(),
    get: (_key: string, fallback: unknown) => fallback,
  },
  queue: {initialize: vi.fn()},
  installCpApp: vi.fn(),
}));
vi.mock('./icons', () => ({configureIcons: vi.fn()}));
vi.mock('./inertia-pages', () => ({
  inertiaPageRegistry: {},
  resolveInertiaPage: vi.fn(),
}));
vi.mock('./components', () => ({cpComponentRegistry: {}}));
vi.mock('./element-details-tabs', () => ({elementDetailsTabRegistry: {}}));
vi.mock('@/common/layouts/AppLayout.vue', () => ({default: {}}));
vi.mock('@/common/slideouts', () => ({registerSlideoutGlobals: vi.fn()}));

const {default: Cp} = await import('./cp');

afterEach(() => {
  document.body.innerHTML = '';
  window.history.replaceState({}, '', '/');
  vi.useRealTimers();
  vi.unstubAllGlobals();
});

it('keeps focus for the first query change and moves it on page navigation', async () => {
  vi.useFakeTimers();
  vi.stubGlobal('Craft', {});
  window.history.replaceState({}, '', '/admin/content/entries');
  document.body.innerHTML =
    '<h1 id="route-focus-anchor" tabindex="-1">Entries</h1><input name="search">';
  const input = document.querySelector('input')!;
  const anchor = document.querySelector('h1')!;
  const {announcement} = useAnnouncer();
  announcement.value = null;
  await Cp.start();
  input.focus();
  input.value = 'needle';
  input.setSelectionRange(3, 3);

  document.dispatchEvent(
    new CustomEvent('inertia:navigate', {
      detail: {
        page: {
          url: '/admin/content/entries?search=needle',
          props: {title: 'Entries'},
        },
      },
    })
  );

  expect(document.activeElement).toBe(input);
  expect(input.selectionStart).toBe(3);
  expect(announcement.value).toBeNull();

  document.dispatchEvent(
    new CustomEvent('inertia:navigate', {
      detail: {page: {url: '/admin/assets', props: {title: 'Assets'}}},
    })
  );

  expect(document.activeElement).toBe(anchor);
  expect(announcement.value).not.toBeNull();
});
