import {setIconResolver} from '@craftcms/ui/utilities/icons';
import {nothing} from 'lit';
import {computed, createApp, h, nextTick, ref, type App} from 'vue';
import {afterEach, beforeAll, expect, it, vi} from 'vite-plus/test';
import {page} from 'vite-plus/test/browser/context';
import ScreenPanel from '@/common/components/ScreenPanel.vue';
import {screenPanels} from '@/common/composables/screenPanels';
import PageScreen from './PageScreen.vue';
import '../../../../css/cp.css';

const {Stub} = await vi.hoisted(async () => {
  const {h} = await import('vue');

  return {Stub: {render: () => h('div')}};
});

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  Head: Stub,
  usePage: () => ({url: '/admin/dashboard', props: {title: 'Dashboard'}}),
}));
vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({
    nav: computed(() => []),
    siteCrumb: computed(() => null),
    currentUser: computed(() => null),
  }),
}));
vi.mock('@/common/components/CpSidebar.vue', () => ({default: Stub}));
vi.mock('@/common/components/CpHeaderBar.vue', () => ({default: Stub}));
vi.mock('./page/ScreenOverlays.vue', () => ({default: Stub}));
// Loaded asynchronously, so its imports could still be loading when the file ends.
vi.mock('@/modules/command-palette/components/CommandPalette.vue', () => ({
  __esModule: true,
  default: Stub,
}));

let app: App | undefined;

beforeAll(() => {
  setIconResolver(() => nothing);
  vi.stubGlobal('Craft', {systemUid: 'test'});
});

afterEach(() => {
  screenPanels.unregister('chat');
  app?.unmount();
  app = undefined;
  document.body.innerHTML = '';
});

it('keeps a panel opened on a narrow page without a details column open', async () => {
  await page.viewport(390, 844);

  const withPanel = ref(false);
  const container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () =>
      h(PageScreen, {title: 'Dashboard'}, () =>
        withPanel.value
          ? h(ScreenPanel, {id: 'chat', label: 'Chat', icon: 'sparkles'}, () =>
              h('p', 'Hello')
            )
          : null
      ),
  });
  app.mount(container);
  await nextTick();

  withPanel.value = true;
  screenPanels.open('chat');

  const panel = () =>
    container.querySelector<HTMLElement>('#details-panel-chat-panel');
  await vi.waitFor(() => expect(panel()?.hidden).toBe(false));
  expect(screenPanels.openPanelId.value).toBe('chat');
});

it('tells the header where its content starts, gutter included', async () => {
  await page.viewport(1280, 800);

  const container = document.createElement('div');
  document.body.append(container);
  app = createApp({
    render: () => h(PageScreen, {title: 'Dashboard'}, () => h('p', 'Hi')),
  });
  app.mount(container);

  const screen = container.querySelector<HTMLElement>('.page-screen')!;
  const contentStart = () =>
    parseFloat(screen.style.getPropertyValue('--cp-content-start'));
  // 16px up to a 400px column, 4% of it, and 48px from 1200px.
  const expected = () => {
    const main = container.querySelector('main')!.getBoundingClientRect();
    const column = container
      .querySelector('.cp-content__main')!
      .getBoundingClientRect();
    return (
      column.left - main.left + Math.min(48, Math.max(16, column.width * 0.04))
    );
  };

  await vi.waitFor(() => expect(contentStart()).toBeCloseTo(expected(), 1));

  await page.viewport(700, 800);

  await vi.waitFor(() => expect(contentStart()).toBeCloseTo(expected(), 1));
  expect(contentStart()).toBeLessThan(48);
});
