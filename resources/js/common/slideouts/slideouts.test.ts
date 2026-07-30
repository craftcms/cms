import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';

const fetchSlideoutPage = vi.hoisted(() => vi.fn());

vi.mock('./request', () => ({
  fetchSlideoutPage,
  setAssetVersion: vi.fn(),
}));

// `usePage()` is only reachable inside a real Inertia app; the shells read
// chrome props off it.
const pageProps = vi.hoisted(() => ({
  value: {} as Record<string, unknown>,
}));

vi.mock('@inertiajs/vue3', () => ({
  usePage: () => ({
    get props() {
      return pageProps.value;
    },
    version: 'test-version',
  }),
  Head: {render: () => null},
  setLayoutProps: vi.fn(),
}));

vi.mock('@craftcms/ui/utilities/translate', () => ({
  t: (message: string) => message,
}));

// Partial: the shells pull in components that import plenty else from here.
vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal<Record<string, unknown>>()),
  appendHeadHtml: vi.fn(async () => vi.fn()),
  appendBodyHtml: vi.fn(async () => vi.fn()),
  appendElementHtml: vi.fn(async () => vi.fn()),
}));

const {closeAllSlideouts, closeSlideout, openSlideout, slideoutPanels} =
  await import('./store');
const SlideoutPanel = (await import('./SlideoutPanel.vue')).default;
const LayoutSlot = (await import('@/common/components/LayoutSlot.vue')).default;
const {useAppLayout} = await import('@/common/composables/useAppLayout');

const apps: Array<{unmount(): void; root: HTMLElement}> = [];

function mount(component: any, props?: Record<string, unknown>): HTMLElement {
  const root = document.createElement('div');
  document.body.appendChild(root);
  const app = createApp(component, props);
  app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
  app.mount(root);
  apps.push({unmount: () => app.unmount(), root});

  return root;
}

beforeEach(() => {
  pageProps.value = {title: 'Fallback title'};
  fetchSlideoutPage.mockReset();
});

afterEach(() => {
  closeAllSlideouts();

  while (apps.length) {
    const entry = apps.pop()!;
    entry.unmount();
    entry.root.remove();
  }
});

describe('slideout store', () => {
  it('stacks panels and gives each its own container id', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    });

    await openSlideout('/a');
    await openSlideout('/b');

    const panels = slideoutPanels();

    expect(panels).toHaveLength(2);
    expect(panels[0]!.containerId).not.toBe(panels[1]!.containerId);
    // The container id is what the server namespaces inputs against, so two
    // slideouts of the same screen must not share one.
    expect(panels.map((p) => p.href)).toEqual(['/a', '/b']);
  });

  it('sends the container id with the request', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    });

    const panel = await openSlideout('/a');

    expect(fetchSlideoutPage).toHaveBeenCalledWith('/a', panel.containerId);
  });

  it('restores focus to the opener on close', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    });

    const opener = document.createElement('button');
    document.body.appendChild(opener);
    const focus = vi.spyOn(opener, 'focus');

    const panel = await openSlideout('/a', {opener});
    closeSlideout(panel.id);

    expect(focus).toHaveBeenCalled();
    expect(slideoutPanels()).toHaveLength(0);

    opener.remove();
  });

  it('surfaces a load failure on the panel instead of throwing', async () => {
    fetchSlideoutPage.mockRejectedValue(new Error('boom'));

    const panel = await openSlideout('/a');

    expect(panel.loading).toBe(false);
    expect(panel.error).toBe('boom');
  });
});

describe('SlideoutPanel', () => {
  /** Mount a panel around a page component, as the host does. */
  async function mountPanel(page: any, props: Record<string, unknown> = {}) {
    fetchSlideoutPage.mockResolvedValue({
      component: page,
      props,
      url: '/screen',
    });

    const instance = await openSlideout('/screen');

    const root = mount(
      defineComponent({
        render: () => h(SlideoutPanel, {instance, depth: 0, total: 1}),
      })
    );

    await nextTick();
    await nextTick();

    return {root, instance};
  }

  it('renders the page inside the slideout shell, not the full-page shell', async () => {
    const Page = defineComponent({
      render: () => h('div', {class: 'page-content'}, 'hello'),
    });

    const {root} = await mountPanel(Page);

    // The slideout shell's chrome, and none of PageScreen's.
    expect(root.querySelector('.slideout-screen')).not.toBeNull();
    expect(root.querySelector('.cp__header')).toBeNull();
    expect(root.querySelector('.page-content')?.textContent).toBe('hello');
  });

  it('shows the screen title from the page props', async () => {
    pageProps.value = {title: 'Edit entry type'};

    const {root} = await mountPanel(
      defineComponent({render: () => h('div')})
    );

    expect(root.querySelector('.slideout-screen__title')?.textContent).toBe(
      'Edit entry type'
    );
  });

  it('routes a page LayoutSlot into the slideout own details outlet', async () => {
    // The regression this whole scoping refactor exists to prevent: with a
    // global registry this content would teleport into the base page.
    const Page = defineComponent({
      render: () =>
        h(LayoutSlot, {name: 'details'}, () =>
          h('span', {class: 'detail'}, 'side info')
        ),
    });

    const {root} = await mountPanel(Page);

    const details = root.querySelector('.slideout-screen__details')!;

    expect(details.querySelector('.detail')?.textContent).toBe('side info');
  });

  it('closes when the shell close button is pressed', async () => {
    const {root} = await mountPanel(defineComponent({render: () => h('div')}));

    expect(slideoutPanels()).toHaveLength(1);

    root.querySelector<HTMLElement>('[data-slideout-close]')!.click();
    await nextTick();

    expect(slideoutPanels()).toHaveLength(0);
  });

  it('reaches a page save handler registered through useAppLayout', async () => {
    // The shell's save button sits above the page in the tree, so this only
    // works via the panel's props store.
    const onSave = vi.fn();

    const Page = defineComponent({
      setup() {
        useAppLayout({form: {} as any, onSave});

        return () => h('div');
      },
    });

    const {root} = await mountPanel(Page);
    await nextTick();

    root.querySelector<HTMLElement>('form.slideout-screen')!.dispatchEvent(
      new Event('submit', {bubbles: true, cancelable: true})
    );
    await nextTick();

    expect(onSave).toHaveBeenCalled();
  });
});
