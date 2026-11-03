import {createApp, h, nextTick, reactive, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {HttpError} from '@craftcms/ui';
import type {Plugin} from '@/modules/plugin-store/types';

const getPlugin = vi.hoisted(() => vi.fn());

vi.mock('@inertiajs/vue3', () => ({router: {replace: vi.fn()}}));
vi.mock('@/modules/plugin-store/api', () => ({getPlugin}));
vi.mock('@/modules/plugin-store/composables/usePluginStoreLayout', () => ({
  usePluginStoreLayout: () => {},
}));

const passThrough = {
  render(this: any) {
    return this.$slots.default?.();
  },
};
const marker = (text: string) => ({render: () => h('p', text)});

vi.mock('@/modules/plugin-store/components/StoreContainer.vue', () => ({
  default: passThrough,
}));
vi.mock('@/common/components/LayoutSlot.vue', () => ({default: passThrough}));
vi.mock('@/modules/plugin-store/components/PluginHero.vue', () => ({
  default: {render: () => null},
}));
vi.mock('@/modules/plugin-store/components/PluginOverview.vue', () => ({
  default: marker('overview panel'),
}));
vi.mock('@/modules/plugin-store/components/PluginReviews.vue', () => ({
  default: marker('reviews panel'),
}));
vi.mock('@/modules/plugin-store/components/PluginChangelog.vue', () => ({
  default: marker('changelog panel'),
}));
vi.mock('@/modules/plugin-store/components/PluginEdition.vue', () => ({
  default: marker('edition'),
}));

const {default: PluginPage} = await import('./Plugin.vue');
let app: App | undefined;

afterEach(() => {
  app?.unmount();
  app = undefined;
  getPlugin.mockReset();
});

function makePlugin(handle: string, prices: Array<string | null>): Plugin {
  return {
    id: 1,
    handle,
    name: handle,
    editions: prices.map((price, i) => ({
      handle: `e${i}`,
      name: `E${i}`,
      price,
    })),
  } as unknown as Plugin;
}

type Props = {
  handle: string;
  tab: 'overview' | 'reviews' | 'editions' | 'changelog';
};

function mount(props: Props): HTMLElement {
  const root = document.createElement('div');
  app = createApp({render: () => h(PluginPage, props)});
  app.mount(root);
  return root;
}

async function settle(): Promise<void> {
  for (let i = 0; i < 5; i++) {
    await Promise.resolve();
  }
  await nextTick();
}

it('shows the overview for a tab the plugin doesn’t have', async () => {
  getPlugin.mockResolvedValue(makePlugin('free-plugin', [null]));

  const root = mount({handle: 'free-plugin', tab: 'editions'});
  await settle();

  expect(root.textContent).toContain('overview panel');
  expect(root.querySelector('craft-tabs')?.selectedIndex).toBe(0);
});

it('keeps the tab in the URL when the plugin has it', async () => {
  getPlugin.mockResolvedValue(makePlugin('paid-plugin', ['49', '99']));

  const root = mount({handle: 'paid-plugin', tab: 'editions'});
  await settle();

  expect(root.textContent).not.toContain('overview panel');
  expect(root.querySelectorAll('p')).toHaveLength(2);
});

it('shows the plugin a new handle loads, even after the previous request is cancelled', async () => {
  getPlugin.mockImplementation(
    (handle: string, signal: AbortSignal) =>
      new Promise((resolve, reject) => {
        if (handle === 'first') {
          signal.addEventListener('abort', () => reject(new Error('canceled')));
        } else {
          setTimeout(() => resolve(makePlugin(handle, [null])), 0);
        }
      })
  );

  const props = reactive<Props>({handle: 'first', tab: 'overview'});
  const root = mount(props);
  props.handle = 'second';
  await new Promise((resolve) => setTimeout(resolve, 5));
  await settle();

  expect(root.textContent).toContain('overview panel');
  expect(root.textContent).not.toContain('Couldn’t find that plugin.');
});

it('tells a plugin that doesn’t exist from one that failed to load', async () => {
  getPlugin.mockRejectedValue(new Error('Couldn’t find plugin'));
  const missing = mount({handle: 'missing', tab: 'overview'});
  await settle();
  expect((missing.querySelector('craft-empty') as any)?.label).toBe(
    'Couldn’t find that plugin.'
  );
  app?.unmount();

  getPlugin.mockRejectedValue(
    new HttpError(
      'Server error',
      'ERR_BAD_RESPONSE',
      {} as never,
      {
        status: 500,
      } as never
    )
  );
  const failed = mount({handle: 'broken', tab: 'overview'});
  await settle();
  expect(failed.querySelector('craft-empty')).toBeNull();
  expect(failed.textContent).toContain('Couldn’t load the plugin.');
});
