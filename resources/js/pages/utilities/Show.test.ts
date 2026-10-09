import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, type App} from 'vue';

vi.mock('@/common/composables/useAppLayout', () => ({
  useAppLayout: () => {},
}));

// Both are layout plumbing; a pass-through keeps the markup under test visible.
const passthrough = {
  setup:
    (_: unknown, {slots}: {slots: Record<string, () => unknown>}) =>
    () =>
      h('div', slots.default?.() ?? []),
};
vi.mock('@/common/components/LayoutSlot.vue', () => ({default: passthrough}));
vi.mock('@/common/components/CpContainer.vue', () => ({default: passthrough}));

let app: App | null = null;

afterEach(() => {
  app?.unmount();
  app = null;
  document.body.innerHTML = '';
  delete (window as any).Craft;
});

async function mount(props: Record<string, unknown>) {
  const Show = (await import('./Show.vue')).default;
  const host = document.createElement('div');
  document.body.append(host);

  app = createApp(Show, {id: 'shopify-sync', title: 'Shopify Sync', ...props});
  app.mount(host);

  // The renderer compiles the markup into the document a tick after mount.
  await new Promise((resolve) => setTimeout(resolve, 0));

  return host;
}

/**
 * A utility hands over server-rendered HTML whose behaviors are wired per
 * element, not delegated — `Craft.initUiElements()` is what binds
 * `.formsubmit`. Without this the Shopify Sync utility's "Sync all" button did
 * nothing at all.
 */
it('boots the legacy UI in a utility’s content', async () => {
  const initUiElements = vi.fn();
  (window as any).Craft = {initUiElements};

  await mount({
    contentHtml:
      '<a class="btn submit formsubmit" data-action="shopify/products/sync">Sync all</a>',
  });

  expect(initUiElements).toHaveBeenCalled();

  // Handed the element the markup is actually inside, not the bare document.
  const element = initUiElements.mock.calls[0]![0] as HTMLElement;
  expect(element.querySelector('.formsubmit')).not.toBeNull();
});

it('boots the legacy UI in a utility’s footer', async () => {
  const initUiElements = vi.fn();
  (window as any).Craft = {initUiElements};

  await mount({footerHtml: '<a class="formsubmit">Footer action</a>'});

  const elements = initUiElements.mock.calls.map(
    (call) => call[0] as HTMLElement
  );

  expect(
    elements.some((element) => element.querySelector('.formsubmit') !== null)
  ).toBe(true);
});

it('does not fail when the legacy bundle is absent', async () => {
  const host = await mount({contentHtml: '<p>Nothing to boot</p>'});

  expect(host.textContent).toContain('Nothing to boot');
});
