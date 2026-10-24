import {actionClient} from '@craftcms/ui';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  createApp,
  defineComponent,
  h,
  nextTick,
  onMounted,
  onUnmounted,
  reactive,
  ref,
  type App,
} from 'vue';
import {
  installCommandPaletteShortcut,
  isCommandPaletteOpen,
  registerActions,
} from '../registry';
import CommandPalette from './CommandPalette.vue';

const state = vi.hoisted(() => ({
  page: null as any,
  visit: vi.fn(),
}));

vi.mock('@inertiajs/vue3', async () => ({
  ...(await vi.importActual('@inertiajs/vue3')),
  usePage: () => state.page,
  router: {visit: state.visit},
}));

vi.mock('@/common/composables/useCraftData', () => ({
  default: () => ({siteCrumb: ref(null)}),
}));

vi.mock('@/common/composables/useGlobalSidebar', () => ({
  useGlobalSidebar: () => ({
    sidebar: reactive({visibility: 'visible'}),
    toggle: vi.fn(),
  }),
}));

vi.mock('@actions/CommandPaletteController', () => ({
  index: {url: () => '/palette'},
  search: {url: ({provider}: {provider: string}) => `/palette/${provider}`},
}));

vi.mock('@/common/components/Modal.vue', () => ({
  default: defineComponent({
    props: ['isActive', 'label', 'width', 'dismissible', 'restoreFocus'],
    emits: ['close', 'opened', 'closed'],
    setup(props, {slots, emit}) {
      const Body = defineComponent({
        setup() {
          onMounted(() => emit('opened'));
          onUnmounted(() => emit('closed'));

          // Like the real Modal, Escape anywhere in the dialog asks it to close.
          return () =>
            h(
              'div',
              {
                role: 'dialog',
                onKeydown: (event: KeyboardEvent) => {
                  if (event.key === 'Escape') {
                    emit('close', event);
                  }
                },
              },
              slots.default?.()
            );
        },
      });

      return () => (props.isActive ? h(Body) : null);
    },
  }),
}));

function serverAction(id: string, label: string, extra = {}) {
  return {
    id,
    label,
    description: null,
    icon: null,
    iconSvg: null,
    thumbHtml: null,
    href: null,
    external: false,
    action: null,
    view: null,
    reload: false,
    actions: [],
    keywords: null,
    searchOnly: false,
    variant: null,
    ...extra,
  };
}

const flush = async () => {
  for (let i = 0; i < 4; i++) {
    await Promise.resolve();
    await nextTick();
  }
};

describe('CommandPalette', () => {
  let app: App | null = null;
  const get = vi.spyOn(actionClient, 'get');
  const request = vi.spyOn(actionClient, 'request');

  beforeEach(() => {
    (window as any).Craft = {
      systemUid: 'test',
      cp: {displayError: vi.fn(), displayNotice: vi.fn()},
    };
    localStorage.setItem(
      'Craft-test.commandPalette.recentPages',
      JSON.stringify([
        {url: 'https://example.test/admin/entries', title: 'Entries'},
      ])
    );
    state.page = reactive({
      url: '/admin/dashboard',
      props: {title: 'Dashboard'},
    });
    get.mockResolvedValue({
      data: {
        providers: [],
        actions: [
          serverAction('create-section', 'Create section', {
            href: '/admin/settings/sections/new',
          }),
          serverAction('uninstall-plugin-seo', 'Uninstall SEO', {
            variant: 'danger',
            action: {
              type: 'http',
              url: '/admin/settings/plugins/seo/uninstall',
              confirm: 'Are you sure you want to uninstall SEO?',
            },
          }),
          serverAction('enable-maintenance-mode', 'Enable maintenance mode', {
            action: {
              type: 'http',
              url: '/admin/settings/general/maintenance-mode',
              body: {enabled: true},
            },
          }),
        ],
      },
    } as never);
  });

  afterEach(() => {
    app?.unmount();
    app = null;
    document.body.innerHTML = '';
    isCommandPaletteOpen.value = false;
    localStorage.clear();
    get.mockReset();
    request.mockReset();
    state.visit.mockReset();
    window.history.replaceState(null, '', '/');
  });

  async function open(): Promise<HTMLInputElement> {
    const container = document.createElement('div');
    document.body.append(container);
    app = createApp(() => h(CommandPalette));
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
    isCommandPaletteOpen.value = true;
    await flush();

    return document.querySelector<HTMLInputElement>('[role="combobox"]')!;
  }

  function selected(input: HTMLInputElement): string {
    const cell = document.getElementById(
      input.getAttribute('aria-activedescendant') ?? ''
    );

    return cell?.textContent?.replace(/\s+/g, ' ').trim() ?? '';
  }

  function press(target: Element, key: string, init: KeyboardEventInit = {}) {
    const event = new KeyboardEvent('keydown', {
      key,
      bubbles: true,
      cancelable: true,
      ...init,
    });
    target.dispatchEvent(event);

    return event;
  }

  it('opens on recent pages and actions, with the first one selected', async () => {
    const input = await open();

    expect(
      [...document.querySelectorAll('h2')].map((heading) =>
        heading.textContent?.trim()
      )
    ).toEqual(['Recents', 'Actions']);
    expect(document.activeElement).toBe(input);
    expect(selected(input)).toBe('Entries');
  });

  it('moves the selection with the arrow keys while focus stays in the search box', async () => {
    const input = await open();

    press(input, 'ArrowDown');
    await nextTick();
    expect(selected(input)).toContain('Collapse the global navigation');

    press(input, 'ArrowUp');
    press(input, 'ArrowUp');
    await nextTick();
    expect(selected(input)).toBe('Uninstall SEO');
    expect(document.activeElement).toBe(input);
  });

  it('lists destructive actions last', async () => {
    await open();

    const actions = [
      ...document.querySelectorAll('.command-palette-row__label'),
    ].map((label) => label.textContent?.trim());

    expect(actions.slice(-2)).toEqual([
      'Enable maintenance mode',
      'Uninstall SEO',
    ]);
  });

  it('moves focus to the selected result on Tab', async () => {
    const input = await open();

    press(input, 'ArrowDown');
    await nextTick();
    const event = press(input, 'Tab');

    expect(event.defaultPrevented).toBe(true);
    expect(document.activeElement?.textContent).toContain(
      'Collapse the global navigation'
    );
  });

  it('cycles Tab between the search box and the selected result, leaving the other results to the arrow keys', async () => {
    const input = await open();

    press(input, 'Tab');
    const first = document.activeElement!;
    expect(first.textContent).toContain('Entries');

    expect(press(first, 'Tab').defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(input);

    expect(press(input, 'Tab', {shiftKey: true}).defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(first);

    press(first, 'ArrowDown');
    await nextTick();
    const second = document.activeElement!;
    expect(second.textContent).toContain('Collapse the global navigation');

    press(second, 'Tab', {shiftKey: true});
    expect(document.activeElement).toBe(input);

    press(input, 'Tab');
    expect(document.activeElement).toBe(second);
  });

  it('says what Escape does in the footer: clear the search, or close', async () => {
    const input = await open();
    const hint = () =>
      [...document.querySelectorAll('.command-palette__hint-item')]
        .at(-1)
        ?.textContent?.replace(/\s+/g, ' ')
        .trim();

    expect(hint()).toBe('Esc Close');

    input.value = 'maintenance';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();

    expect(hint()).toBe('Esc Clear search');
  });

  it('clears the search on Escape wherever focus is, then closes', async () => {
    const input = await open();
    input.value = 'maintenance';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();

    press(input, 'Tab');
    const focused = document.activeElement!;
    expect(focused).not.toBe(input);

    expect(press(focused, 'Escape').defaultPrevented).toBe(true);
    await flush();

    expect(isCommandPaletteOpen.value).toBe(true);
    expect(input.value).toBe('');
    expect(document.activeElement).toBe(input);

    press(input, 'Tab');
    press(document.activeElement!, 'Escape');
    await flush();

    expect(isCommandPaletteOpen.value).toBe(false);
  });

  it('focuses the search box when its surrounding padding is pressed', async () => {
    const input = await open();
    press(input, 'Tab');
    expect(document.activeElement).not.toBe(input);

    const field = input.closest<HTMLElement>('.command-palette__field')!;
    const event = new MouseEvent('mousedown', {
      bubbles: true,
      cancelable: true,
    });
    field.dispatchEvent(event);

    expect(event.defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(input);
  });

  it('confirms destructive actions in the palette, and Escape goes back to the results as they were', async () => {
    const input = await open();

    while (selected(input) !== 'Uninstall SEO') {
      press(input, 'ArrowDown');
      await nextTick();
    }

    press(input, 'Enter');
    await flush();

    const heading = document.querySelector<HTMLElement>('h2[tabindex="-1"]')!;
    expect(heading.textContent?.trim()).toBe('Uninstall SEO');
    expect(document.activeElement).toBe(heading);
    expect(document.body.textContent).toContain(
      'Are you sure you want to uninstall SEO?'
    );
    expect(request).not.toHaveBeenCalled();

    expect(press(heading, 'Escape').defaultPrevented).toBe(true);
    await flush();

    expect(document.querySelector('h2[tabindex="-1"]')).toBeNull();
    expect(isCommandPaletteOpen.value).toBe(true);
    expect(document.activeElement).toBe(input);
    expect(selected(input)).toBe('Uninstall SEO');
  });

  it('confirms a page’s own action before running it, once the palette has closed', async () => {
    const discard = vi.fn();
    const unregister = registerActions(() => [
      {
        id: 'discard',
        label: 'Discard changes',
        variant: 'danger',
        confirm: 'Discard your changes?',
        run: discard,
      },
    ]);
    const input = await open();

    while (selected(input) !== 'Discard changes') {
      press(input, 'ArrowDown');
      await nextTick();
    }

    press(input, 'Enter');
    await flush();

    expect(document.body.textContent).toContain('Discard your changes?');
    expect(discard).not.toHaveBeenCalled();

    [...document.querySelectorAll('craft-button')]
      .find((button) => button.textContent?.trim() === 'Discard changes')!
      .dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await flush();

    expect(isCommandPaletteOpen.value).toBe(false);
    expect(discard).toHaveBeenCalledOnce();

    unregister();
    isCommandPaletteOpen.value = true;
    await flush();

    expect(document.body.textContent).not.toContain('Discard changes');
  });

  it('goes back from a view on Escape, even with focus on the dialog itself', async () => {
    const input = await open();

    while (selected(input) !== 'Uninstall SEO') {
      press(input, 'ArrowDown');
      await nextTick();
    }

    press(input, 'Enter');
    await flush();

    press(document.querySelector('[role="dialog"]')!, 'Escape');
    await flush();

    expect(isCommandPaletteOpen.value).toBe(true);
    expect(document.querySelector('h2[tabindex="-1"]')).toBeNull();
    expect(document.activeElement).toBe(input);
  });

  it('finds the actions it opened with before the server answers', async () => {
    get.mockResolvedValue({
      data: {
        providers: [{handle: 'actions', label: 'Actions'}],
        actions: [
          serverAction('enable-maintenance-mode', 'Enable maintenance mode'),
        ],
      },
    } as never);
    const input = await open();
    get.mockClear();

    input.value = 'maintenance';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();

    expect(
      [...document.querySelectorAll('.command-palette-row__label')].map(
        (label) => label.textContent?.trim()
      )
    ).toEqual(['Enable maintenance mode']);
    expect(get).not.toHaveBeenCalled();
  });

  it('runs actions, then closes and follows any redirect', async () => {
    request.mockResolvedValue({
      data: {messages: [], redirect: '/admin/settings/general'},
    } as never);
    const input = await open();

    while (selected(input) !== 'Enable maintenance mode') {
      press(input, 'ArrowDown');
      await nextTick();
    }

    press(input, 'Enter');
    await flush();

    expect(request).toHaveBeenCalledWith({
      url: '/admin/settings/general/maintenance-mode',
      method: 'POST',
      data: {enabled: true},
    });
    expect(isCommandPaletteOpen.value).toBe(false);
    expect(state.visit).toHaveBeenCalledWith('/admin/settings/general', {
      replace: false,
    });
  });

  it('leaves the page being viewed once an action has removed it, URL first', async () => {
    request.mockResolvedValue({data: {messages: []}} as never);
    get.mockResolvedValue({
      data: {
        providers: [],
        actions: [
          serverAction('delete-section-1', 'Delete the News section', {
            action: {type: 'http', method: 'DELETE', url: '/sections/1'},
            removedPage: window.location.href,
            removedPageRedirect: '/admin/settings/sections',
          }),
        ],
      },
    } as never);
    const input = await open();

    while (selected(input) !== 'Delete the News section') {
      press(input, 'ArrowDown');
      await nextTick();
    }

    let urlWhenVisited = '';
    state.visit.mockImplementation(() => {
      urlWhenVisited = window.location.pathname;
    });

    press(input, 'Enter');
    await flush();

    expect(state.visit).toHaveBeenCalledWith('/admin/settings/sections', {
      replace: true,
    });
    expect(urlWhenVisited).toBe('/admin/settings/sections');
  });

  it('stays open when an action fails', async () => {
    request.mockRejectedValue({response: {data: {message: 'Nope'}}});
    const input = await open();

    while (selected(input) !== 'Enable maintenance mode') {
      press(input, 'ArrowDown');
      await nextTick();
    }

    press(input, 'Enter');
    await flush();

    expect(isCommandPaletteOpen.value).toBe(true);
    expect((window as any).Craft.cp.displayError).toHaveBeenCalledWith('Nope');
  });

  it('finishes opening when the shortcut opened it before it was mounted', async () => {
    isCommandPaletteOpen.value = true;
    const container = document.createElement('div');
    document.body.append(container);
    app = createApp(() => h(CommandPalette));
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
    await flush();

    const input =
      document.querySelector<HTMLInputElement>('[role="combobox"]')!;

    expect(isCommandPaletteOpen.value).toBe(true);
    expect(document.activeElement).toBe(input);
    expect(selected(input)).toBe('Entries');
  });

  it('toggles with Cmd/Ctrl+K unless something else handled it', async () => {
    installCommandPaletteShortcut();
    await open();
    isCommandPaletteOpen.value = false;

    press(document.body, 'k', {ctrlKey: true});
    expect(isCommandPaletteOpen.value).toBe(true);

    const handled = new KeyboardEvent('keydown', {
      key: 'k',
      metaKey: true,
      bubbles: true,
      cancelable: true,
    });
    document.body.addEventListener(
      'keydown',
      (event) => event.preventDefault(),
      {
        once: true,
      }
    );
    document.body.dispatchEvent(handled);
    expect(isCommandPaletteOpen.value).toBe(true);
  });
});
