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
  registerProvider,
} from '../registry';
import CommandPalette from './CommandPalette.vue';

import '@craftcms/ui/components/grid/grid';

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

    return document.querySelector<HTMLInputElement>('.command-palette__input')!;
  }

  /** The result with this label: the link or button the person presses. */
  function result(label: string): HTMLElement {
    return [
      ...document.querySelectorAll<HTMLElement>('.command-palette-row__item'),
    ].find(
      (item) =>
        item
          .querySelector('.command-palette-row__label')
          ?.textContent?.trim() === label
    )!;
  }

  /** Chooses a result, as a click or Return on it does. */
  async function choose(label: string): Promise<void> {
    result(label).click();
    await flush();
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

  it('opens on recent pages and actions, each a grid named by its heading, with focus in the search field', async () => {
    const input = await open();

    expect(
      [...document.querySelectorAll('.command-palette__results h2')].map(
        (heading) => heading.textContent?.trim()
      )
    ).toEqual(['Recents', 'Actions']);
    expect(
      [...document.querySelectorAll('craft-grid')].map((grid) =>
        document
          .getElementById(grid.getAttribute('aria-labelledby')!)
          ?.textContent?.trim()
      )
    ).toEqual(['Recents', 'Actions']);
    expect(document.activeElement).toBe(input);
  });

  it('keeps the search field a plain text field, which Enter does nothing in', async () => {
    const input = await open();

    expect(input.hasAttribute('role')).toBe(false);
    expect(input.hasAttribute('aria-activedescendant')).toBe(false);

    press(input, 'Enter');
    await flush();

    expect(isCommandPaletteOpen.value).toBe(true);
    expect(request).not.toHaveBeenCalled();
    expect(state.visit).not.toHaveBeenCalled();
    expect(document.activeElement).toBe(input);
  });

  it('makes each group of results one tab stop, moved around with the arrow keys', async () => {
    await open();
    const actions = document.querySelectorAll('craft-grid')[1]!;
    const items = [
      ...actions.querySelectorAll<HTMLElement>('.command-palette-row__item'),
    ];

    expect(items.map((item) => item.getAttribute('tabindex'))).toEqual([
      '0',
      ...items.slice(1).map(() => '-1'),
    ]);

    items[0]!.focus();
    press(items[0]!, 'ArrowDown');

    expect(document.activeElement).toBe(items[1]);
  });

  it('starts the results from the top again when the search changes', async () => {
    const input = await open();
    const search = async (value: string) => {
      input.value = value;
      input.dispatchEvent(new Event('input', {bubbles: true}));
      await flush();
    };
    const tabStop = () =>
      document
        .querySelector('craft-grid [tabindex="0"]')
        ?.querySelector('.command-palette-row__label')
        ?.textContent?.trim();

    await search('o');
    const items = [
      ...document.querySelectorAll<HTMLElement>(
        'craft-grid .command-palette-row__item'
      ),
    ];
    expect(items.length).toBeGreaterThan(1);
    items[0]!.focus();
    press(items[0]!, 'ArrowDown');
    await flush();
    expect(tabStop()).not.toBe(
      items[0]!
        .querySelector('.command-palette-row__label')
        ?.textContent?.trim()
    );

    input.focus();
    await search('on');
    expect(
      document.querySelectorAll('craft-grid .command-palette-row__item').length
    ).toBeGreaterThan(2);

    expect(tabStop()).toBe(
      document
        .querySelector('craft-grid .command-palette-row__label')
        ?.textContent?.trim()
    );
  });

  it('starts the results from the top again when a provider answers late', async () => {
    vi.useFakeTimers();
    get.mockImplementation((async (url: string) =>
      url === '/palette'
        ? {
            data: {
              providers: [{handle: 'entries', label: 'Entries'}],
              actions: [
                serverAction('homepage-settings', 'Open the homepage settings'),
              ],
            },
          }
        : {
            data: {
              results: [
                serverAction('entry-1', 'Homepage', {href: '/admin/entries/1'}),
                serverAction('entry-2', 'Homepage hero', {
                  href: '/admin/entries/2',
                }),
              ],
            },
          }) as never);
    const input = await open();
    const tabStop = () =>
      document
        .querySelector('craft-grid [tabindex="0"]')
        ?.querySelector('.command-palette-row__label')
        ?.textContent?.trim();

    input.value = 'homepage';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();
    expect(tabStop()).toBe('Open the homepage settings');

    vi.runAllTimers();
    await flush();
    await flush();
    vi.useRealTimers();

    expect(
      [
        ...document.querySelectorAll('craft-grid .command-palette-row__label'),
      ].map((label) => label.textContent?.trim())
    ).toEqual(['Homepage', 'Homepage hero', 'Open the homepage settings']);
    expect(tabStop()).toBe('Homepage');
  });

  it('keeps focus among the results when a late search replaces the focused one', async () => {
    // Like the page's own fields, which come back as new results each search
    let searches = 0;
    registerProvider({
      handle: 'test-fields',
      label: 'Fields',
      search: (term) =>
        term === 'hero'
          ? [{id: `field-${++searches}`, label: 'Hero image', run: vi.fn()}]
          : [],
    });
    let answer!: (value: unknown) => void;
    get.mockImplementation(
      (() =>
        new Promise((resolve) => {
          answer = resolve;
        })) as never
    );
    const input = await open();

    input.value = 'hero';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();
    const before = result('Hero image');
    before.focus();

    // The server's providers arrive, so the search runs again.
    answer({
      data: {providers: [{handle: 'entries', label: 'Entries'}], actions: []},
    });
    await flush();
    await flush();

    expect(result('Hero image')).not.toBe(before);
    expect(document.activeElement).toBe(result('Hero image'));
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

  /** Each keyboard instruction as it's read: the keys' names, then what they do. */
  /** Each instruction, one per list item: its keys' names, then what they do. */
  function instructions(): string[] {
    return [
      ...document.querySelectorAll(
        'section.command-palette__hint ul[role="list"] > li'
      ),
    ].map((item) =>
      [
        ...[...item.querySelectorAll('kbd .sr-only')].map((name) =>
          name.textContent?.trim()
        ),
        item.lastChild?.textContent?.trim(),
      ].join(' ')
    );
  }

  it('gives keyboard instructions a region of their own, apart from the search field', async () => {
    const input = await open();
    const region = document.querySelector<HTMLElement>(
      'section.command-palette__hint'
    )!;
    const heading = document.getElementById(
      region.getAttribute('aria-labelledby')!
    )!;

    expect(heading.textContent?.trim()).toBe('Keyboard instructions');
    expect(region.contains(heading)).toBe(true);
    expect(input.getAttribute('aria-describedby') ?? '').not.toContain(
      region.id || heading.id
    );
    expect(
      [...region.querySelectorAll('kbd')].every(
        (key) =>
          key.querySelector('[aria-hidden="true"]') &&
          key.querySelector('.sr-only')?.textContent?.trim()
      )
    ).toBe(true);
    expect(instructions()).toEqual([
      'Tab Next section',
      'Up arrow Down arrow Move within a section',
      expect.stringMatching(/^(Return|Enter) Open$/),
      'Escape Close',
    ]);

    input.value = 'maintenance';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();

    expect(instructions()).toEqual([
      'Tab Go to results',
      'Up arrow Down arrow Move between results',
      expect.stringMatching(/^(Return|Enter) Open$/),
      'Escape Clear search',
    ]);
  });

  it('lists Left and Right only while a result has actions of its own', async () => {
    get.mockResolvedValue({
      data: {
        providers: [],
        actions: [
          serverAction('blog', 'Blog', {
            href: '/admin/entries/blog',
            actions: [
              {label: 'Open in a new tab', href: '/admin/entries/blog'},
            ],
          }),
        ],
      },
    } as never);
    const input = await open();

    expect(instructions()).toContain(
      'Left arrow Right arrow Move to a result’s actions'
    );

    input.value = 'maintenance';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();

    expect(instructions().some((line) => line.startsWith('Left arrow'))).toBe(
      false
    );
  });

  it('clears the search on Escape wherever focus is, then closes', async () => {
    const input = await open();
    input.value = 'maintenance';
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();

    result('Enable maintenance mode').focus();
    const focused = document.activeElement!;
    expect(focused).not.toBe(input);

    expect(press(focused, 'Escape').defaultPrevented).toBe(true);
    await flush();

    expect(isCommandPaletteOpen.value).toBe(true);
    expect(input.value).toBe('');
    expect(document.activeElement).toBe(input);

    result('Entries').focus();
    press(document.activeElement!, 'Escape');
    await flush();

    expect(isCommandPaletteOpen.value).toBe(false);
  });

  it('lists docs search from the start, opening it with its own search box focused', async () => {
    await open();

    await choose('Search Craft CMS documentation');

    const field = document.activeElement?.closest('craft-input-search');
    expect(field?.closest('.command-palette-docs')).not.toBeNull();
    expect(field?.label).toBe('Search');
  });

  it('focuses the search box when its surrounding padding is pressed', async () => {
    const input = await open();
    result('Entries').focus();
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

  it('confirms destructive actions in the palette, and Escape goes back to the result that opened it', async () => {
    await open();

    await choose('Uninstall SEO');

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
    expect(document.activeElement).toBe(result('Uninstall SEO'));
  });

  it('confirms a page’s own action before running it, once the palette has closed', async () => {
    const discard = vi.fn();
    const {unregister} = registerActions(() => [
      {
        id: 'discard',
        label: 'Discard changes',
        variant: 'danger',
        confirm: 'Discard your changes?',
        run: discard,
      },
    ]);
    await open();

    await choose('Discard changes');

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
    await open();

    await choose('Uninstall SEO');

    press(document.querySelector('[role="dialog"]')!, 'Escape');
    await flush();

    expect(isCommandPaletteOpen.value).toBe(true);
    expect(document.querySelector('h2[tabindex="-1"]')).toBeNull();
    expect(document.activeElement).toBe(result('Uninstall SEO'));
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
    await open();

    await choose('Enable maintenance mode');

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
    await open();

    let urlWhenVisited = '';
    state.visit.mockImplementation(() => {
      urlWhenVisited = window.location.pathname;
    });

    await choose('Delete the News section');

    expect(state.visit).toHaveBeenCalledWith('/admin/settings/sections', {
      replace: true,
    });
    expect(urlWhenVisited).toBe('/admin/settings/sections');
  });

  it('stays open when an action fails', async () => {
    request.mockRejectedValue({response: {data: {message: 'Nope'}}});
    await open();

    await choose('Enable maintenance mode');

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

    const input = document.querySelector<HTMLInputElement>(
      '.command-palette__input'
    )!;

    expect(isCommandPaletteOpen.value).toBe(true);
    expect(document.activeElement).toBe(input);
  });

  it('gives focus back to where it was, and says so, once a copy closes the palette', async () => {
    const writeText = vi.fn().mockResolvedValue(undefined);
    Object.defineProperty(navigator, 'clipboard', {
      value: {writeText},
      configurable: true,
    });
    get.mockResolvedValue({
      data: {
        providers: [],
        actions: [
          serverAction('copy-id', 'Copy ID', {
            action: {type: 'clipboard', value: '42'},
          }),
        ],
      },
    } as never);
    const trigger = document.createElement('button');
    document.body.append(trigger);
    trigger.focus();
    await open();

    await choose('Copy ID');
    await flush();

    expect(isCommandPaletteOpen.value).toBe(false);
    expect(writeText).toHaveBeenCalledWith('42');
    expect((window as any).Craft.cp.displayNotice).toHaveBeenCalledWith(
      'Copied!'
    );
    expect(document.activeElement).toBe(trigger);
  });

  it('says so and gives focus back when a copy fails', async () => {
    Object.defineProperty(navigator, 'clipboard', {
      value: {writeText: vi.fn().mockRejectedValue(new Error('Denied'))},
      configurable: true,
    });
    get.mockResolvedValue({
      data: {
        providers: [],
        actions: [
          serverAction('copy-id', 'Copy ID', {
            action: {type: 'clipboard', value: '42'},
          }),
        ],
      },
    } as never);
    const trigger = document.createElement('button');
    document.body.append(trigger);
    trigger.focus();
    await open();

    await choose('Copy ID');
    await flush();

    expect(isCommandPaletteOpen.value).toBe(false);
    expect((window as any).Craft.cp.displayError).toHaveBeenCalledWith(
      'Denied'
    );
    expect(document.activeElement).toBe(trigger);
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
