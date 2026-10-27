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
            actions: [
              {label: 'Learn more', href: 'https://example.test/maintenance'},
            ],
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

  /** The active option, which the search input points to. */
  function activeOption(input: HTMLInputElement): HTMLElement | null {
    return document.getElementById(
      input.getAttribute('aria-activedescendant') ?? ''
    );
  }

  /** The active option's label. */
  function selected(input: HTMLInputElement): string {
    return (
      activeOption(input)
        ?.querySelector('.command-palette-row__label')
        ?.textContent?.replace(/\s+/g, ' ')
        .trim() ?? ''
    );
  }

  function option(label: string): HTMLElement {
    return [...document.querySelectorAll<HTMLElement>('[role="option"]')].find(
      (element) =>
        element
          .querySelector('.command-palette-row__label')
          ?.textContent?.trim() === label
    )!;
  }

  async function type(input: HTMLInputElement, value: string): Promise<void> {
    input.value = value;
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await flush();
  }

  /**
   * The actions' menu button. `craft-button` isn't defined in these tests,
   * so it's made focusable here, as the real one is.
   */
  /** Each keyboard instruction as a screen reader reads it: its keys' names, then what they do. */
  function hints(): string[] {
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

  function actionsButton(): HTMLElement {
    const button = document.querySelector<HTMLElement>(
      '.command-palette-actions craft-button'
    )!;
    button.tabIndex = 0;

    return button;
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
    const listbox = document.getElementById(
      input.getAttribute('aria-controls')!
    )!;

    expect(listbox.getAttribute('role')).toBe('listbox');
    expect(input.getAttribute('aria-expanded')).toBe('true');
    expect(
      [...listbox.querySelectorAll('[role="group"]')].map((group) =>
        document
          .getElementById(group.getAttribute('aria-labelledby')!)
          ?.textContent?.trim()
      )
    ).toEqual(['Recents', 'Actions']);
    expect(document.activeElement).toBe(input);
    expect(selected(input)).toBe('Entries');
    expect(activeOption(input)?.getAttribute('role')).toBe('option');
    expect(activeOption(input)?.getAttribute('aria-selected')).toBe('true');
  });

  it('collapses the combobox when nothing matches', async () => {
    const input = await open();
    await type(input, 'zzzz');

    expect(input.getAttribute('aria-expanded')).toBe('false');
    expect(input.hasAttribute('aria-activedescendant')).toBe(false);
  });

  it('never gives the options focus, or anything in them to focus', async () => {
    await open();
    const listbox = document.querySelector('[role="listbox"]')!;

    expect(listbox.querySelectorAll('[role="option"]').length).toBeGreaterThan(
      0
    );
    expect(
      listbox.querySelectorAll('a, button, input, [tabindex], craft-button')
    ).toHaveLength(0);
  });

  it('moves the selection with the arrow keys while focus stays in the search box', async () => {
    const input = await open();

    press(input, 'ArrowDown');
    await nextTick();
    expect(selected(input)).toContain('Notifications');

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

  it('leaves Tab to the browser', async () => {
    const input = await open();

    expect(press(input, 'Tab').defaultPrevented).toBe(false);
  });

  it('offers the active result’s actions in a group after the listbox, and says Tab reaches them', async () => {
    const input = await open();

    expect(document.querySelector('.command-palette-actions')).toBeNull();
    expect(hints()).not.toContain('Tab Actions');

    while (selected(input) !== 'Enable maintenance mode') {
      press(input, 'ArrowDown');
      await nextTick();
    }
    await flush();

    const group = document.querySelector('.command-palette-actions')!;
    const listbox = document.querySelector('[role="listbox"]')!;
    expect(group.getAttribute('role')).toBe('group');
    expect(group.getAttribute('aria-label')).toBe(
      'Actions for Enable maintenance mode'
    );
    expect(listbox.contains(group)).toBe(false);
    expect(
      listbox.compareDocumentPosition(group) & Node.DOCUMENT_POSITION_FOLLOWING
    ).toBeTruthy();
    expect(hints()).toContain('Tab Actions');
  });

  it('says in an option’s name when Tab reaches its actions', async () => {
    await open();

    expect(option('Enable maintenance mode').textContent).toContain(
      'Press Tab for actions'
    );
    expect(option('Uninstall SEO').textContent).not.toContain(
      'Press Tab for actions'
    );
  });

  it('makes the option under the pointer the active one, unless focus is on the actions', async () => {
    const input = await open();

    option('Enable maintenance mode').dispatchEvent(
      new Event('pointermove', {bubbles: true})
    );
    await flush();
    expect(selected(input)).toBe('Enable maintenance mode');

    actionsButton().focus();
    option('Entries').dispatchEvent(new Event('pointermove', {bubbles: true}));
    await flush();
    expect(selected(input)).toBe('Enable maintenance mode');
  });

  it('keeps focus in the search box when an option is pressed, and follows the link it was', async () => {
    const input = await open();
    const entries = option('Entries');

    const down = new MouseEvent('mousedown', {bubbles: true, cancelable: true});
    entries.dispatchEvent(down);
    expect(down.defaultPrevented).toBe(true);
    expect(document.activeElement).toBe(input);

    entries.click();
    await flush();

    expect(state.visit).toHaveBeenCalledWith(
      'https://example.test/admin/entries'
    );
    expect(isCommandPaletteOpen.value).toBe(false);
  });

  it('opens a link in a new tab on Command or Control click, staying open', async () => {
    const open_ = vi.spyOn(window, 'open').mockImplementation(() => null);
    await open();

    option('Entries').dispatchEvent(
      new MouseEvent('click', {bubbles: true, metaKey: true})
    );
    await flush();

    expect(open_).toHaveBeenCalledWith(
      'https://example.test/admin/entries',
      '_blank',
      'noopener'
    );
    expect(state.visit).not.toHaveBeenCalled();
    expect(isCommandPaletteOpen.value).toBe(true);
    open_.mockRestore();
  });

  it('gives keyboard instructions a region of their own, named by a heading', async () => {
    await open();
    const region = document.querySelector('section.command-palette__hint')!;
    const heading = document.getElementById(
      region.getAttribute('aria-labelledby')!
    )!;

    expect(heading.tagName).toBe('H2');
    expect(heading.textContent?.trim()).toBe('Keyboard instructions');
    expect(region.contains(heading)).toBe(true);
    expect(hints()).toEqual([
      'Up arrow Down arrow Choose item',
      'Enter Open item',
      'Escape Close',
    ]);
  });

  it('says what Escape does in the footer: clear the search, or close', async () => {
    const input = await open();

    expect(hints().at(-1)).toBe('Escape Close');

    await type(input, 'maintenance');

    expect(hints().at(-1)).toBe('Escape Clear search');
  });

  it('clears the search on Escape wherever focus is, then closes', async () => {
    const input = await open();
    await type(input, 'maintenance');

    const focused = actionsButton();
    focused.focus();
    expect(document.activeElement).toBe(focused);

    expect(press(focused, 'Escape').defaultPrevented).toBe(true);
    await flush();

    expect(isCommandPaletteOpen.value).toBe(true);
    expect(input.value).toBe('');
    expect(document.activeElement).toBe(input);

    press(input, 'Escape');
    await flush();

    expect(isCommandPaletteOpen.value).toBe(false);
  });

  it('lists docs search from the start, opening it with its own search box focused', async () => {
    const input = await open();

    for (
      let i = 0;
      i < 50 && selected(input) !== 'Search Craft CMS documentation';
      i++
    ) {
      press(input, 'ArrowDown');
      await nextTick();
    }
    expect(selected(input)).toBe('Search Craft CMS documentation');

    press(input, 'Enter');
    await flush();

    const docsInput = document.activeElement as HTMLInputElement;
    expect(docsInput.getAttribute('role')).toBe('combobox');
    expect(docsInput.closest('.command-palette__view')).not.toBeNull();
    // Named "Search" by its field's own label, as the palette's input is.
    expect(docsInput.hasAttribute('aria-label')).toBe(false);
  });

  it('keeps the keyboard instructions in docs search, where Escape clears the search, then goes back', async () => {
    const input = await open();
    const footer = () =>
      document.querySelector<HTMLElement>('.command-palette__hint')!;

    while (selected(input) !== 'Search Craft CMS documentation') {
      press(input, 'ArrowDown');
      await nextTick();
    }
    press(input, 'Enter');
    await flush();

    expect(footer().style.display).not.toBe('none');
    expect(hints()).toEqual([
      'Up arrow Down arrow Choose item',
      'Enter Open in new tab',
      'Escape Back',
    ]);

    await type(document.activeElement as HTMLInputElement, 'matrix');
    expect(hints().at(-1)).toBe('Escape Clear search');
  });

  it('warns screen reader users that choosing a result changes the context', async () => {
    const input = await open();
    const note = () =>
      document
        .querySelector('.command-palette > p.sr-only')
        ?.textContent?.trim();

    expect(note()).toBe(
      'Choosing a result opens it, which may take you to another page.'
    );

    while (selected(input) !== 'Search Craft CMS documentation') {
      press(input, 'ArrowDown');
      await nextTick();
    }
    press(input, 'Enter');
    await flush();

    expect(note()).toBe('Choosing a result opens it in a new tab.');
  });

  it('hides the keyboard instructions in views without a search', async () => {
    const input = await open();

    while (selected(input) !== 'Uninstall SEO') {
      press(input, 'ArrowDown');
      await nextTick();
    }
    press(input, 'Enter');
    await flush();

    expect(
      document.querySelector<HTMLElement>('.command-palette__hint')!.style
        .display
    ).toBe('none');
  });

  it('focuses the search box when its surrounding padding is pressed', async () => {
    const input = await open();
    await type(input, 'maintenance');
    actionsButton().focus();
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
    const {unregister} = registerActions(() => [
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
