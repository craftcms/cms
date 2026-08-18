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

const {
  closeAllSlideouts,
  closeSlideout,
  notifySlideoutSaved,
  openSlideout,
  openSlideoutWith,
  setSlideoutDirtyCheck,
  slideoutPanels,
} = await import('./store');
const {stackedPanels} = await import('./panel-stack');
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

  document.body.innerHTML = '';
});

/** An opener sitting inside a rendered panel, as the DOM would have it. */
function openerInPanel(panelId: string): HTMLElement {
  const panel = document.createElement('div');
  panel.dataset.slideoutId = panelId;
  const button = document.createElement('button');
  panel.appendChild(button);
  document.body.appendChild(panel);

  return button;
}

describe('slideout store', () => {
  it('nests a panel opened from inside another', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    });

    const first = (await openSlideout('/a'))!;
    await openSlideout('/b', {opener: openerInPanel(first.id)});

    const panels = slideoutPanels();

    expect(panels).toHaveLength(2);
    expect(panels.map((p) => p.href)).toEqual(['/a', '/b']);
    // The container id is what the server namespaces inputs against, so two
    // slideouts of the same screen must not share one.
    expect(panels[0]!.containerId).not.toBe(panels[1]!.containerId);
  });

  it('replaces the open panel when opened from the base page again', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    });

    // Double-clicking a second row on an index: the opener is in the page, not
    // in a panel, so this swaps rather than stacks.
    await openSlideout('/a', {opener: document.createElement('button')});
    await openSlideout('/b', {opener: document.createElement('button')});

    expect(slideoutPanels().map((p) => p.href)).toEqual(['/b']);
  });

  it('drops deeper panels when reopening from an outer one', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    });

    const first = (await openSlideout('/a'))!;
    const opener = openerInPanel(first.id);
    await openSlideout('/b', {opener});
    await openSlideout('/c', {opener});

    // `/c` was opened from the first panel, so it takes `/b`'s place.
    expect(slideoutPanels().map((p) => p.href)).toEqual(['/a', '/c']);
  });

  it('closes nested panels along with the one they were opened from', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    });

    const first = (await openSlideout('/a'))!;
    await openSlideout('/b', {opener: openerInPanel(first.id)});

    closeSlideout(first.id);

    // The nested panel has nowhere to sit once its parent is gone.
    expect(slideoutPanels()).toHaveLength(0);
  });

  it('sends the container id with the request', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    });

    const panel = (await openSlideout('/a'))!;

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

    const panel = (await openSlideout('/a', {opener}))!;
    closeSlideout(panel.id);

    expect(focus).toHaveBeenCalled();
    expect(slideoutPanels()).toHaveLength(0);

    opener.remove();
  });

  it('surfaces a load failure on the panel instead of throwing', async () => {
    fetchSlideoutPage.mockRejectedValue(new Error('boom'));

    const panel = (await openSlideout('/a'))!;

    expect(panel.loading).toBe(false);
    expect(panel.error).toBe('boom');
  });
});

describe('locally-built panels', () => {
  const Local = defineComponent({render: () => h('div', 'local')});

  it('opens without fetching a screen', () => {
    const panel = openSlideoutWith(Local as any, {foo: 'bar'});

    expect(fetchSlideoutPage).not.toHaveBeenCalled();
    expect(panel).not.toBeNull();
    expect(panel!.component).toStrictEqual(Local);
    expect(panel!.props).toEqual({foo: 'bar'});
    expect(panel!.loading).toBe(false);
    expect(panel!.href).toBe('');
  });

  it('stacks against fetched panels like any other', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: {render: () => null},
      props: {},
      url: '/one',
    });
    const first = await openSlideout('/one');
    openSlideoutWith(
      Local as any,
      {},
      {
        opener: openerInPanel(first!.id),
      }
    );

    expect(slideoutPanels()).toHaveLength(2);
  });

  it('replaces an existing panel when opened from the base page', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: {render: () => null},
      props: {},
      url: '/one',
    });
    await openSlideout('/one');
    openSlideoutWith(Local as any);

    expect(slideoutPanels()).toHaveLength(1);
  });

  it('honours the unsaved-changes prompt of the panel it replaces', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: {render: () => null},
      props: {},
      url: '/one',
    });
    const first = await openSlideout('/one');
    setSlideoutDirtyCheck(first!.id, () => true);
    // happy-dom doesn't implement confirm(), so install one to spy on.
    const confirmSpy = vi.fn(() => false);
    Object.defineProperty(window, 'confirm', {
      configurable: true,
      writable: true,
      value: confirmSpy,
    });

    const panel = openSlideoutWith(Local as any);

    expect(confirmSpy).toHaveBeenCalled();
    expect(panel).toBeNull();
    expect(slideoutPanels()).toHaveLength(1);
  });
});

describe('reporting a save to the opener', () => {
  beforeEach(() => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    });
  });

  it('hands the result to the opener’s handler', async () => {
    const onSaved = vi.fn();
    const panel = (await openSlideout('/a', {onSaved}))!;

    expect(notifySlideoutSaved(panel.id, {data: {id: 7}})).toBe(true);
    expect(onSaved).toHaveBeenCalledWith({data: {id: 7}});
  });

  /**
   * The caller's cue to fall back to reloading the page behind — the only
   * refresh a panel opened from an arbitrary place can safely do.
   */
  it('reports back that nobody was listening', async () => {
    const panel = (await openSlideout('/a'))!;

    expect(notifySlideoutSaved(panel.id)).toBe(false);
  });

  /**
   * Closing drops the panel from the store, handler and all. Save paths have
   * to notify first, and this is what goes red when one stops doing that.
   */
  it('finds nothing once the panel has closed', async () => {
    const onSaved = vi.fn();
    const panel = (await openSlideout('/a', {onSaved}))!;

    closeSlideout(panel.id, {force: true});

    expect(notifySlideoutSaved(panel.id)).toBe(false);
    expect(onSaved).not.toHaveBeenCalled();
  });
});

describe('discarding unsaved changes', () => {
  let confirmSpy: ReturnType<typeof vi.fn>;

  beforeEach(() => {
    confirmSpy = vi.fn(() => true);
    // happy-dom doesn't implement confirm(), so install one to spy on.
    Object.defineProperty(window, 'confirm', {
      configurable: true,
      writable: true,
      value: confirmSpy,
    });

    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/a',
    });
  });

  /** Open a panel and mark it as holding unsaved changes. */
  async function openDirty(href = '/a', options = {}) {
    const panel = (await openSlideout(href, options))!;
    setSlideoutDirtyCheck(panel.id, () => true);

    return panel;
  }

  it('does not prompt when nothing is dirty', async () => {
    const panel = (await openSlideout('/a'))!;

    closeSlideout(panel.id);

    expect(confirmSpy).not.toHaveBeenCalled();
    expect(slideoutPanels()).toHaveLength(0);
  });

  it('prompts before closing a dirty panel, and closes when confirmed', async () => {
    const panel = await openDirty();

    closeSlideout(panel.id);

    expect(confirmSpy).toHaveBeenCalled();
    expect(slideoutPanels()).toHaveLength(0);
  });

  it('keeps the panel open when the prompt is declined', async () => {
    confirmSpy.mockReturnValue(false);
    const panel = await openDirty();

    closeSlideout(panel.id);

    expect(slideoutPanels().map((p) => p.id)).toEqual([panel.id]);
  });

  it('prompts before a dirty panel is replaced', async () => {
    // The silent-loss case: double-clicking a second row on an index replaces
    // whatever is open, with no close button involved.
    confirmSpy.mockReturnValue(false);
    const first = await openDirty('/a', {
      opener: document.createElement('button'),
    });

    const second = await openSlideout('/b', {
      opener: document.createElement('button'),
    });

    expect(confirmSpy).toHaveBeenCalled();
    // Declining leaves the original panel untouched and opens nothing.
    expect(second).toBeNull();
    expect(slideoutPanels().map((p) => p.href)).toEqual([first.href]);
  });

  it('replaces a dirty panel once the prompt is accepted', async () => {
    await openDirty('/a', {opener: document.createElement('button')});

    const second = await openSlideout('/b', {
      opener: document.createElement('button'),
    });

    expect(second).not.toBeNull();
    expect(slideoutPanels().map((p) => p.href)).toEqual(['/b']);
  });

  it('asks once when closing a parent with dirty nested panels', async () => {
    const first = (await openSlideout('/a'))!;
    const nested = (await openSlideout('/b', {
      opener: openerInPanel(first.id),
    }))!;
    setSlideoutDirtyCheck(nested.id, () => true);

    closeSlideout(first.id);

    // One prompt for the whole subtree, not one per panel.
    expect(confirmSpy).toHaveBeenCalledTimes(1);
    expect(slideoutPanels()).toHaveLength(0);
  });

  it('skips the prompt for a forced close, as used after a save', async () => {
    const panel = await openDirty();

    closeSlideout(panel.id, {force: true});

    expect(confirmSpy).not.toHaveBeenCalled();
    expect(slideoutPanels()).toHaveLength(0);
  });

  it('forgets a panel dirty check once it closes', async () => {
    const panel = await openDirty();
    closeSlideout(panel.id, {force: true});

    // Ids aren't reused, but a stale predicate would wrongly guard later panels.
    const next = (await openSlideout('/c'))!;
    closeSlideout(next.id);

    expect(confirmSpy).not.toHaveBeenCalled();
  });
});

describe('SlideoutHost', () => {
  async function mountHost() {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/screen',
    });

    const SlideoutHost = (await import('./SlideoutHost.vue')).default;
    const root = mount(defineComponent({render: () => h(SlideoutHost)}));
    await nextTick();

    return root;
  }

  /**
   * The shade is created once and left in the document between slideouts, so
   * "closed" means not `.is-visible` rather than not present. The stylesheet
   * makes it inert in that state.
   */
  it('shows no shade until a slideout is open', async () => {
    const root = await mountHost();

    expect(document.querySelector('.cp-slideout-shade.is-visible')).toBeNull();
    expect(root).toBeTruthy();
  });

  it('shows a shade while a slideout is open', async () => {
    await mountHost();
    await openSlideout('/a');
    await nextTick();

    const shade = document.querySelector('.cp-slideout-shade');

    expect(shade).not.toBeNull();
    expect(shade!.classList.contains('is-visible')).toBe(true);
    /**
     * The shade must NOT be `.slideout-shade`: the legacy stylesheet owns
     * that class and hides it with `:not(.visible) { display: none }` until
     * its own JS marks it visible, which would stop this one rendering.
     */
    expect(shade!.classList.contains('slideout-shade')).toBe(false);
  });

  it('places the first Vue slideout above an open legacy slideout', async () => {
    await mountHost();
    const legacy = document.body.appendChild(document.createElement('div'));
    legacy.className = 'slideout-container';

    await openSlideout('/a');
    await nextTick();

    const panel = document.querySelector('.slideout-panel')!;
    expect(
      legacy.compareDocumentPosition(panel) & Node.DOCUMENT_POSITION_FOLLOWING
    ).not.toBe(0);
  });

  it('hides the shade again once the last slideout closes', async () => {
    await mountHost();
    const panel = (await openSlideout('/a'))!;
    await nextTick();

    closeSlideout(panel.id, {force: true});
    await nextTick();

    const shade = document.querySelector('.cp-slideout-shade')!;

    expect(shade.classList.contains('is-visible')).toBe(false);
  });

  it('closes the top slideout when the shade is clicked', async () => {
    await mountHost();
    const first = (await openSlideout('/a'))!;
    await openSlideout('/b', {opener: openerInPanel(first.id)});
    await nextTick();

    document.querySelector<HTMLElement>('.cp-slideout-shade')!.click();
    await nextTick();

    // Only the top one — clicking again closes the next.
    expect(slideoutPanels().map((p) => p.href)).toEqual(['/a']);
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

    const instance = (await openSlideout('/screen'))!;

    const root = mount(
      defineComponent({
        render: () => h(SlideoutPanel, {instance}),
      })
    );

    await nextTick();
    await nextTick();

    return {root, instance};
  }

  it('spreads stacked panels across the space beside the newest one', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/screen',
    });

    const instance = (await openSlideout('/screen'))!;

    const root = mount(
      defineComponent({
        // Second of two: sits flush against the edge, so the whole leftover
        // width. The outer one gets half of it and peeks out behind.
        render: () => h(SlideoutPanel, {instance}),
      })
    );
    await nextTick();

    const panel = root.querySelector<HTMLElement>('.slideout-panel')!;

    // Derived from the width rather than Craft 5's hard-coded `45vw`, so
    // changing `--slideout-width` keeps the stack geometry correct.
    expect(panel.getAttribute('style')).toContain(
      'calc((100vw - var(--slideout-panel-width)) * 1)'
    );
  });

  /**
   * The panel joins the stack shared with the legacy jQuery slideouts, rather
   * than positioning itself from its index in the Vue list — that's what lets
   * the two interleave.
   */
  it('joins the shared panel stack for as long as it is mounted', async () => {
    await mountPanel(defineComponent({render: () => h('div')}));

    expect(stackedPanels()).toHaveLength(1);

    // The host renders one of these per open slideout, so closing a panel
    // reaches the stack as an unmount.
    const app = apps.pop()!;
    app.unmount();
    app.root.remove();
    await nextTick();

    expect(stackedPanels()).toHaveLength(0);
  });

  it('presents itself to assistive technology as a modal dialog', async () => {
    const {root} = await mountPanel(defineComponent({render: () => h('div')}));
    const panel = root.querySelector<HTMLElement>('.slideout-panel')!;

    expect(panel.getAttribute('aria-modal')).toBe('true');
    expect(panel.getAttribute('role')).toBe('dialog');
  });

  /**
   * Escape goes through the UI layer manager rather than a window listener, so
   * it closes one thing: whichever layer is on top, across both slideout
   * stacks and every modal, HUD, and menu.
   */
  it('closes on Escape via the layer manager', async () => {
    await mountPanel(defineComponent({render: () => h('div')}));

    document.body.dispatchEvent(
      new KeyboardEvent('keydown', {keyCode: 27} as never)
    );
    await nextTick();

    expect(slideoutPanels()).toHaveLength(0);
  });

  it('applies a per-panel width override', async () => {
    fetchSlideoutPage.mockResolvedValue({
      component: defineComponent({render: () => h('div')}),
      props: {},
      url: '/screen',
    });

    const instance = (await openSlideout('/screen', {width: '40rem'}))!;

    const root = mount(
      defineComponent({
        render: () => h(SlideoutPanel, {instance}),
      })
    );
    await nextTick();

    expect(
      root.querySelector<HTMLElement>('.slideout-panel')!.getAttribute('style')
    ).toContain('--slideout-width: 40rem');
  });

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

  it("titles itself from the slideout's own props, not the page behind it", async () => {
    // `usePage()` always resolves to the base page, so a shell reading its
    // chrome from there shows the title of whatever is open underneath.
    pageProps.value = {title: 'Entries'};

    const {root} = await mountPanel(defineComponent({render: () => h('div')}), {
      title: 'Edit entry type',
    });

    expect(root.querySelector('.slideout-screen__title')?.textContent).toBe(
      'Edit entry type'
    );
  });

  it('reads the edit URL from the slideout props', async () => {
    pageProps.value = {title: 'Entries', screen: {editUrl: '/wrong'}};

    const {root} = await mountPanel(defineComponent({render: () => h('div')}), {
      screen: {editUrl: '/admin/entries/5'},
    });

    expect(
      root.querySelector<HTMLAnchorElement>('.slideout-screen__edit-link')?.href
    ).toContain('/admin/entries/5');
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

    root
      .querySelector<HTMLElement>('form.slideout-screen')!
      .dispatchEvent(new Event('submit', {bubbles: true, cancelable: true}));
    await nextTick();

    expect(onSave).toHaveBeenCalled();
  });
});
