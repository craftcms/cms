import {createApp, h, nextTick, ref, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';

const getFeaturedSections = vi.hoisted(() => vi.fn());

vi.mock('@/modules/plugin-store/api', () => ({getFeaturedSections}));
vi.mock('@/modules/plugin-store/composables/usePluginStoreLayout', () => ({
  usePluginStoreLayout: () => {},
}));
vi.mock('@/modules/plugin-store/composables/useActiveTrials', () => ({
  useActiveTrials: () => ({trials: ref([]), failed: ref(false)}),
}));
vi.mock('@/modules/plugin-store/components/StoreContainer.vue', () => ({
  default: {
    render(this: {$slots: {default?: () => unknown}}) {
      return this.$slots.default?.();
    },
  },
}));
vi.mock('@/modules/plugin-store/components/PluginGrid.vue', () => ({
  default: {render: () => null},
}));

const {default: IndexPage} = await import('./Index.vue');
let app: App | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
});

it('says when the featured plugins couldn’t be loaded', async () => {
  getFeaturedSections.mockRejectedValue(new Error('Network Error'));

  const root = document.createElement('div');
  app = createApp({render: () => h(IndexPage)});
  app.mount(root);
  await new Promise((resolve) => setTimeout(resolve, 0));
  await nextTick();

  expect(root.querySelector('craft-spinner')).toBeNull();
  expect(root.textContent).toContain('Couldn’t load featured plugins.');
});
