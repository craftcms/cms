import {createApp, h, nextTick, reactive, type App} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {router} from '@inertiajs/vue3';
import {setUrlDefaults} from '@/wayfinder';
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
  default: {
    emits: ['show-reviews'],
    setup:
      (_props: unknown, {emit}: {emit: (event: string) => void}) =>
      () =>
        h('a', {class: 'show-reviews', onClick: () => emit('show-reviews')}),
  },
}));
vi.mock('@/modules/plugin-store/components/PluginOverview.vue', () => ({
  default: {
    render: () =>
      h('div', [
        h('p', 'overview panel'),
        h('craft-tabs', [
          h('craft-tab', {slot: 'tab'}, 'Shell'),
          h('craft-tab', {slot: 'tab'}, 'DDEV'),
        ]),
      ]),
  },
}));
const reviewsMounts = vi.hoisted(() => ({count: 0}));

vi.mock('@/modules/plugin-store/components/PluginReviews.vue', () => ({
  default: {
    setup: () => {
      reviewsMounts.count++;
      return () => h('p', 'reviews panel');
    },
  },
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

it('moves focus to the Reviews tab when the hero’s rating link shows it', async () => {
  setUrlDefaults({cpTrigger: 'cp'});
  getPlugin.mockResolvedValue(makePlugin('paid-plugin', ['49', '99']));
  const focus = vi.spyOn(HTMLElement.prototype, 'focus');

  const root = mount({handle: 'paid-plugin', tab: 'overview'});
  await settle();
  root.querySelector<HTMLElement>('.show-reviews')!.click();
  await settle();

  expect(router.replace).toHaveBeenCalledWith(
    expect.objectContaining({preserveState: true})
  );
  const focused = focus.mock.contexts.at(-1) as HTMLElement;
  expect(focused.tagName).toBe('CRAFT-TAB');
  expect(focused.textContent).toBe('Reviews');
  focus.mockRestore();
  setUrlDefaults({});
});

it('keeps a tab it has shown mounted, rather than loading it again', async () => {
  setUrlDefaults({cpTrigger: 'cp'});
  getPlugin.mockResolvedValue(makePlugin('paid-plugin', ['49']));
  const props = reactive<Props>({handle: 'paid-plugin', tab: 'overview'});
  vi.mocked(router.replace).mockImplementation((visit) => {
    Object.assign(props, (visit as {props: (p: Props) => Props}).props(props));
  });
  reviewsMounts.count = 0;

  const root = mount(props);
  await settle();
  const tabs = root.querySelector('craft-tabs') as HTMLElement & {
    selectedIndex: number;
  };
  const show = async (index: number) => {
    tabs.selectedIndex = index;
    tabs.dispatchEvent(new Event('craft-tab-show'));
    await settle();
  };

  await show(1);
  await show(0);
  await show(1);

  expect(props.tab).toBe('reviews');
  expect(reviewsMounts.count).toBe(1);
  vi.mocked(router.replace).mockReset();
  setUrlDefaults({});
});
