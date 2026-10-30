import {computed, createApp, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import LayoutSlot from '@/common/components/LayoutSlot.vue';
import ScreenPanel from '@/common/components/ScreenPanel.vue';
import {ScreenPanelsKey, screenPanels} from '@/common/composables/screenPanels';
import PageScreen from './PageScreen.vue';

type NavItem = CraftCms.Cms.Cp.Data.NavItem;

const {Stub, pageProps} = await vi.hoisted(async () => {
  const {h} = await import('vue');

  return {
    Stub: {render: () => h('div')},
    pageProps: {} as Record<string, unknown>,
  };
});

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  Head: Stub,
  usePage: () => ({
    url: '/admin/commerce/store-management',
    props: {
      title: 'General',
      subnav: [navItem({label: 'General'})],
      ...pageProps,
    },
  }),
}));
vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({nav: computed(() => []), siteCrumb: computed(() => null)}),
}));
vi.mock('@/common/components/CpSidebar.vue', () => ({default: Stub}));
vi.mock('@/common/components/CpHeaderBar.vue', () => ({default: Stub}));
vi.mock('./page/ScreenOverlays.vue', () => ({default: Stub}));
// Loaded asynchronously, so its imports could still be loading when the file ends.
vi.mock('@/modules/command-palette/components/CommandPalette.vue', () => ({
  __esModule: true,
  default: Stub,
}));

const realMatchMedia = window.matchMedia;

function stubViewport(isLarge: boolean): void {
  window.matchMedia = ((media: string) => ({
    media,
    matches: isLarge,
    onchange: null,
    addEventListener: () => {},
    removeEventListener: () => {},
    addListener: () => {},
    removeListener: () => {},
    dispatchEvent: () => false,
  })) as unknown as typeof window.matchMedia;
}

function navItem(config: Partial<NavItem> & {label: string}): NavItem {
  return {
    href: `/admin/${config.label.toLowerCase().replace(/\s+/g, '-')}`,
    selected: false,
    subnav: false,
    group: false,
    external: false,
    badgeCount: 0,
    ariaLabel: null,
    icon: null,
    id: null,
    fontIcon: null,
    linkAttributes: {},
    ...config,
  } as NavItem;
}

let teardown: (() => void) | undefined;

function addShellPanel(): () => void {
  const container = document.createElement('div');
  document.body.append(container);
  const app = createApp({
    render: () =>
      h(ScreenPanel, {
        id: 'chat',
        label: 'Chat',
        icon: 'sparkles',
        persistent: true,
      }),
  });
  app.provide(ScreenPanelsKey, screenPanels);
  app.mount(container);

  return () => {
    app.unmount();
    container.remove();
  };
}

beforeEach(() => {
  vi.stubGlobal('Craft', {systemUid: 'page-screen-test', cpTrigger: 'admin'});
});

afterEach(() => {
  teardown?.();
  teardown = undefined;
  for (const key of Object.keys(pageProps)) {
    delete pageProps[key];
  }
  vi.unstubAllGlobals();
  window.matchMedia = realMatchMedia;
});

describe('PageScreen', () => {
  it.each([
    {
      layout: 'expanded',
      isLarge: true,
      nav: '.secondary-nav',
      menu: '.secondary-nav__actions',
    },
    {
      layout: 'collapsed',
      isLarge: false,
      nav: 'craft-popover',
      menu: 'craft-action-menu',
    },
  ])(
    'draws the $layout subnav actions menu only once a page fills it',
    async ({isLarge, nav, menu}) => {
      stubViewport(isLarge);

      const filled = ref(false);
      const container = document.createElement('div');
      document.body.append(container);

      const app = createApp({
        render: () =>
          h(PageScreen, null, {
            default: () =>
              filled.value
                ? h(LayoutSlot, {name: 'subnav-actions'}, () =>
                    h('button', {type: 'button'}, 'New Store')
                  )
                : null,
          }),
      });
      app.mount(container);
      teardown = () => {
        app.unmount();
        container.remove();
      };
      await nextTick();

      expect(container.querySelector(nav)).not.toBeNull();
      expect(container.querySelector(menu)).toBeNull();

      filled.value = true;

      await vi.waitFor(() =>
        expect(container.querySelector(menu)?.textContent ?? '').toContain(
          'New Store'
        )
      );
    }
  );

  it('gives a page without details panels a details column once the shell has a panel', async () => {
    stubViewport(true);

    const container = document.createElement('div');
    document.body.append(container);
    const app = createApp({render: () => h(PageScreen, {title: 'Dashboard'})});
    app.mount(container);
    teardown = () => {
      screenPanels.unregister('chat');
      app.unmount();
      container.remove();
    };
    await nextTick();

    const rail = () =>
      container.querySelector<HTMLElement>('#cp-details-rail')!;
    expect(rail().style.display).toBe('none');

    const removeShellPanel = addShellPanel();

    await vi.waitFor(() => {
      expect(rail().style.display).toBe('');
      expect(
        container.querySelector('.cp-content__details section h3')?.textContent
      ).toBe('Chat');
    });

    removeShellPanel();

    await vi.waitFor(() => expect(rail().style.display).toBe('none'));
  });

  it('makes a page’s details content a panel of its own while there are other panels', async () => {
    stubViewport(true);

    const container = document.createElement('div');
    document.body.append(container);
    const app = createApp({
      render: () =>
        h(PageScreen, {title: 'Token'}, () =>
          h(LayoutSlot, {name: 'content-details'}, () =>
            h('p', {class: 'token-details'}, 'Enabled')
          )
        ),
    });
    app.mount(container);
    teardown = () => {
      screenPanels.unregister('chat');
      app.unmount();
      container.remove();
    };

    const details = () =>
      container.querySelector<HTMLElement>('.cp-content__details')!;
    await vi.waitFor(() =>
      expect(details().querySelector('.token-details')).not.toBeNull()
    );
    expect(details().querySelector('section')).toBeNull();

    const removeShellPanel = addShellPanel();

    await vi.waitFor(() => {
      const panel = details().querySelector('section:not([hidden])');
      expect(panel?.querySelector('h3')?.textContent).toBe('Details');
      expect(panel?.querySelector('.token-details')).not.toBeNull();
    });

    removeShellPanel();

    await vi.waitFor(() => {
      expect(details().querySelector('section')).toBeNull();
      expect(details().querySelector('.token-details')).not.toBeNull();
    });
  });

  it('hides the content header while it has nothing to show', async () => {
    stubViewport(true);

    const withActions = ref(false);
    const container = document.createElement('div');
    document.body.append(container);

    const app = createApp({
      render: () =>
        h(PageScreen, {title: ''} as never, {
          default: () =>
            withActions.value
              ? h(LayoutSlot, {name: 'content-actions'}, () =>
                  h('button', {type: 'button'}, 'Action')
                )
              : null,
        }),
    });
    app.mount(container);
    teardown = () => {
      app.unmount();
      container.remove();
    };
    await nextTick();

    const header = () =>
      container.querySelector<HTMLElement>('#cp-content-header')!;
    expect(header().style.display).toBe('none');

    withActions.value = true;

    await vi.waitFor(() => expect(header().style.display).toBe(''));
  });

  it('shows the notices region once a page fills its slot', async () => {
    stubViewport(true);

    const filled = ref(false);
    const container = document.createElement('div');
    document.body.append(container);

    const app = createApp({
      render: () =>
        h(PageScreen, null, {
          default: () => null,
          ...(filled.value
            ? {'content-notices': () => h('p', 'Saved as a draft.')}
            : {}),
        }),
    });
    app.mount(container);
    teardown = () => {
      app.unmount();
      container.remove();
    };
    await nextTick();

    const notices = () =>
      container.querySelector<HTMLElement>('#content-notice')!;
    expect(notices().style.display).toBe('none');

    filled.value = true;

    await vi.waitFor(() => {
      expect(notices().textContent).toContain('Saved as a draft.');
      expect(notices().style.display).toBe('');
    });
  });

  it('hides the footer for a page that asks it to, even with a form', async () => {
    stubViewport(true);

    const hideFooter = ref(false);
    const form = {
      hasErrors: false,
      errors: {},
      processing: false,
      isDirty: false,
    } as unknown as InstanceType<typeof Object>;
    const container = document.createElement('div');
    document.body.append(container);

    const app = createApp({
      render: () =>
        h(PageScreen, {
          form,
          defaultFormActions: [],
          hideFooter: hideFooter.value,
        } as never),
    });
    app.mount(container);
    teardown = () => {
      app.unmount();
      container.remove();
    };
    await nextTick();

    const footer = () =>
      container.querySelector<HTMLElement>('.content-footer');
    expect(
      footer()?.closest<HTMLElement>('[style*="display: none"]')
    ).toBeNull();

    hideFooter.value = true;
    await nextTick();

    expect(
      footer()?.closest<HTMLElement>('[style*="display: none"]')
    ).not.toBeNull();
  });

  it.each([
    {handlesReadOnly: false, notice: true, revert: false},
    {handlesReadOnly: true, notice: false, revert: true},
  ])(
    'treats a read-only page by its own read-only handling (handles: $handlesReadOnly)',
    async ({handlesReadOnly, notice, revert}) => {
      stubViewport(true);
      pageProps.readOnly = true;

      const form = {
        hasErrors: false,
        errors: {},
        processing: false,
        isDirty: false,
      } as unknown as InstanceType<typeof Object>;
      const container = document.createElement('div');
      document.body.append(container);

      const app = createApp({
        render: () =>
          h(PageScreen, {
            form,
            defaultFormActions: [],
            saveDisabled: true,
            handlesReadOnly,
            formAdditionalButtons: [{label: 'Revert content'}],
          } as never),
      });
      app.mount(container);
      teardown = () => {
        app.unmount();
        container.remove();
      };
      await nextTick();

      expect(
        container.textContent?.includes('permitted in this environment')
      ).toBe(notice);
      expect(container.textContent?.includes('Revert content')).toBe(revert);
    }
  );
});
