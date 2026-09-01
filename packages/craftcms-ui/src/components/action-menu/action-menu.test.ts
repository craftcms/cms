import {beforeEach, describe, expect, it} from 'vite-plus/test';
import type CraftActionMenu from './action-menu.js';
import type CraftActionItem from '../action-item/action-item.js';
import type {ActionMenuItem} from './action-menu.types.js';
import './action-menu.js';

/**
 * Parses markup inertly and appends the complete subtree, matching how
 * server-rendered islands reach the page — the element upgrades with its
 * children present.
 */
async function createFromMarkup(markup: string): Promise<CraftActionMenu> {
  const template = document.createElement('template');
  template.innerHTML = markup;
  document.body.append(template.content);
  const element = document.body.querySelector(
    'craft-action-menu'
  ) as CraftActionMenu;
  await element.updateComplete;
  return element;
}

/** The slot-based fixture used by most filtering tests. */
function slotBasedMarkup(attrs = 'searchable'): string {
  return `
    <craft-action-menu ${attrs}>
      <button slot="invoker" type="button">Open</button>
      <div slot="content">
        <craft-action-item data-keywords="plainText">Plain Text</craft-action-item>
        <craft-action-item data-keywords="dropdown optedList">Dropdown</craft-action-item>
        <craft-action-item>Date</craft-action-item>
      </div>
    </craft-action-menu>
  `;
}

function getSearchInput(element: CraftActionMenu): HTMLInputElement | null {
  // Note: Lion moves the content node inside its overlay wrapper and strips
  // the `slot` attribute, so query by the search container class instead.
  return element.querySelector<HTMLInputElement>('.action-menu__search input');
}

function typeIntoSearch(element: CraftActionMenu, value: string): void {
  const input = getSearchInput(element)!;
  input.value = value;
  input.dispatchEvent(new Event('input', {bubbles: true}));
}

function visibleLabels(element: CraftActionMenu): string[] {
  return Array.from(element.querySelectorAll<HTMLElement>('craft-action-item'))
    .filter((item) => !item.hasAttribute('data-search-hidden'))
    .map((item) => item.textContent?.trim() ?? '');
}

beforeEach(() => {
  document.body.innerHTML = '';
});

describe('searchable (slot-based)', () => {
  it('inserts a search input at the top of the content when searchable', async () => {
    const element = await createFromMarkup(slotBasedMarkup());
    const input = getSearchInput(element);

    expect(input).not.toBeNull();
    expect(input!.getAttribute('aria-label')).toBe('Search');
    // The search container is the first child of the content node (the same
    // container that holds the items).
    const container = input!.closest('.action-menu__search')!;
    const content = container.parentElement!;
    expect(content.firstElementChild).toBe(container);
    expect(content.querySelector('craft-action-item')).not.toBeNull();
  });

  it('does not insert a search input by default', async () => {
    const element = await createFromMarkup(slotBasedMarkup(''));

    expect(getSearchInput(element)).toBeNull();
  });

  it('filters items case-insensitively by text content', async () => {
    const element = await createFromMarkup(slotBasedMarkup());

    typeIntoSearch(element, 'dROp');

    expect(visibleLabels(element)).toEqual(['Dropdown']);
  });

  it('matches hidden terms via data-keywords', async () => {
    const element = await createFromMarkup(slotBasedMarkup());

    // "plainText" appears in no visible label — only in data-keywords.
    typeIntoSearch(element, 'plaintext');

    expect(visibleLabels(element)).toEqual(['Plain Text']);
  });

  it('unhides items when the query is cleared', async () => {
    const element = await createFromMarkup(slotBasedMarkup());

    typeIntoSearch(element, 'date');
    expect(visibleLabels(element)).toEqual(['Date']);

    typeIntoSearch(element, '');
    expect(visibleLabels(element)).toEqual(['Plain Text', 'Dropdown', 'Date']);
  });

  it('never touches consumer-controlled visibility', async () => {
    const element = await createFromMarkup(`
      <craft-action-menu searchable>
        <button slot="invoker" type="button">Open</button>
        <div slot="content">
          <craft-action-item hidden>Plain Text</craft-action-item>
          <craft-action-item>Dropdown</craft-action-item>
        </div>
      </craft-action-menu>
    `);
    const hiddenItem = element.querySelector('craft-action-item[hidden]')!;

    // A matching filter must not unhide the consumer-hidden item.
    typeIntoSearch(element, 'plain');
    expect(hiddenItem.hasAttribute('hidden')).toBe(true);
    expect(hiddenItem.hasAttribute('data-search-hidden')).toBe(false);

    // A non-matching filter hides via the dedicated attribute only.
    typeIntoSearch(element, 'dropdown');
    expect(hiddenItem.hasAttribute('data-search-hidden')).toBe(true);
    expect(hiddenItem.hasAttribute('hidden')).toBe(true);
  });

  it('resets the filter when the menu closes', async () => {
    const element = await createFromMarkup(slotBasedMarkup());

    element.opened = true;
    await element.updateComplete;

    typeIntoSearch(element, 'date');
    expect(visibleLabels(element)).toEqual(['Date']);

    element.opened = false;
    await element.updateComplete;

    expect(getSearchInput(element)!.value).toBe('');
    expect(visibleLabels(element)).toEqual(['Plain Text', 'Dropdown', 'Date']);
  });

  it('clears the filter on Escape without closing the menu', async () => {
    const element = await createFromMarkup(slotBasedMarkup());
    const input = getSearchInput(element)!;

    typeIntoSearch(element, 'date');
    expect(visibleLabels(element)).toEqual(['Date']);

    input.dispatchEvent(
      new KeyboardEvent('keydown', {key: 'Escape', bubbles: true})
    );

    expect(input.value).toBe('');
    expect(visibleLabels(element)).toEqual(['Plain Text', 'Dropdown', 'Date']);
  });

  it('removes the search input when searchable is turned off', async () => {
    const element = await createFromMarkup(slotBasedMarkup());

    typeIntoSearch(element, 'date');
    element.searchable = false;
    await element.updateComplete;

    expect(getSearchInput(element)).toBeNull();
    expect(visibleLabels(element)).toEqual(['Plain Text', 'Dropdown', 'Date']);
  });

  it('tolerates plain li items', async () => {
    const element = await createFromMarkup(`
      <craft-action-menu searchable>
        <button slot="invoker" type="button">Open</button>
        <ul slot="content">
          <li data-keywords="alpha"><button type="button">One</button></li>
          <li><button type="button">Two</button></li>
        </ul>
      </craft-action-menu>
    `);
    const items = Array.from(element.querySelectorAll('li'));

    typeIntoSearch(element, 'alpha');

    expect(items[0]!.hasAttribute('data-search-hidden')).toBe(false);
    expect(items[1]!.hasAttribute('data-search-hidden')).toBe(true);
    // The nested button is not filtered independently of its <li>.
    expect(
      items[1]!.querySelector('button')!.hasAttribute('data-search-hidden')
    ).toBe(false);
  });
});

describe('searchable (data-driven)', () => {
  const actions: ActionMenuItem[] = [
    {label: 'Plain Text', keywords: 'plainText'},
    {label: 'Dropdown', keywords: 'dropdown optedList'},
    {label: 'Date'},
  ];

  async function createDataDriven(): Promise<CraftActionMenu> {
    const element = document.createElement(
      'craft-action-menu'
    ) as CraftActionMenu;
    element.searchable = true;
    element.actions = actions;
    document.body.append(element);
    await element.updateComplete;
    return element;
  }

  it('renders keywords as a data-keywords attribute', async () => {
    const element = await createDataDriven();
    const item = Array.from(element.querySelectorAll('craft-action-item')).find(
      (el) => el.textContent === 'Plain Text'
    )!;

    expect(item.getAttribute('data-keywords')).toBe('plainText');
  });

  it('inserts the search input into the generated content', async () => {
    const element = await createDataDriven();

    expect(getSearchInput(element)).not.toBeNull();
  });

  it('filters generated items by label and keywords', async () => {
    const element = await createDataDriven();

    typeIntoSearch(element, 'optedlist');
    expect(visibleLabels(element)).toEqual(['Dropdown']);

    typeIntoSearch(element, 'da');
    expect(visibleLabels(element)).toEqual(['Date']);
  });
});

describe('keyboard navigation', () => {
  function pressKey(el: Element, key: string): KeyboardEvent {
    const event = new KeyboardEvent('keydown', {
      key,
      bubbles: true,
      composed: true,
      cancelable: true,
    });
    el.dispatchEvent(event);
    return event;
  }

  /**
   * happy-dom's focus fidelity for shadow hosts (delegatesFocus) is limited,
   * so item-focus assertions record `focus()` calls instead of inspecting
   * `document.activeElement`.
   */
  function spyOnItemFocus(element: CraftActionMenu): string[] {
    const focused: string[] = [];
    element.querySelectorAll('craft-action-item').forEach((item) => {
      (item as CraftActionItem).focus = () => {
        focused.push(item.textContent?.trim() ?? '');
      };
    });
    return focused;
  }

  const keyboardMarkup = `
    <craft-action-menu searchable>
      <button slot="invoker" type="button">Open</button>
      <div slot="content">
        <craft-action-item>Plain Text</craft-action-item>
        <craft-action-item>Dropdown</craft-action-item>
        <craft-action-item>Date</craft-action-item>
      </div>
    </craft-action-menu>
  `;

  function getItem(element: CraftActionMenu, label: string): CraftActionItem {
    return Array.from(element.querySelectorAll('craft-action-item')).find(
      (item) => item.textContent?.trim() === label
    ) as CraftActionItem;
  }

  it('ArrowDown from the search input focuses the first navigable item', async () => {
    const element = await createFromMarkup(keyboardMarkup);
    const focused = spyOnItemFocus(element);

    const event = pressKey(getSearchInput(element)!, 'ArrowDown');

    expect(focused).toEqual(['Plain Text']);
    expect(event.defaultPrevented).toBe(true);
  });

  it('ArrowUp from the search input focuses the last navigable item', async () => {
    const element = await createFromMarkup(keyboardMarkup);
    const focused = spyOnItemFocus(element);

    pressKey(getSearchInput(element)!, 'ArrowUp');

    expect(focused).toEqual(['Date']);
  });

  it('ArrowDown/ArrowUp move between items and wrap at the ends', async () => {
    const element = await createFromMarkup(keyboardMarkup);
    const focused = spyOnItemFocus(element);

    pressKey(getItem(element, 'Plain Text'), 'ArrowDown');
    pressKey(getItem(element, 'Date'), 'ArrowDown'); // wraps to first
    pressKey(getItem(element, 'Plain Text'), 'ArrowUp'); // wraps to last

    expect(focused).toEqual(['Dropdown', 'Plain Text', 'Date']);
  });

  it('Home/End jump to the first/last navigable item', async () => {
    const element = await createFromMarkup(keyboardMarkup);
    const focused = spyOnItemFocus(element);

    pressKey(getItem(element, 'Dropdown'), 'End');
    pressKey(getItem(element, 'Dropdown'), 'Home');

    expect(focused).toEqual(['Date', 'Plain Text']);
  });

  it('skips consumer-hidden, filtered, and disabled items', async () => {
    const element = await createFromMarkup(`
      <craft-action-menu searchable>
        <button slot="invoker" type="button">Open</button>
        <div slot="content">
          <craft-action-item>One</craft-action-item>
          <craft-action-item hidden>Two</craft-action-item>
          <craft-action-item disabled>Three</craft-action-item>
          <craft-action-item>Four</craft-action-item>
          <craft-action-item>Five</craft-action-item>
        </div>
      </craft-action-menu>
    `);
    getItem(element, 'Five').setAttribute('data-search-hidden', '');
    const focused = spyOnItemFocus(element);

    // Two (hidden), Three (disabled) and Five (filtered) are not navigable.
    pressKey(getItem(element, 'One'), 'ArrowDown');
    pressKey(getItem(element, 'Four'), 'ArrowDown'); // wraps past Five

    expect(focused).toEqual(['Four', 'One']);
  });

  it('returns focus to the search input when typing on a focused item', async () => {
    const element = await createFromMarkup(keyboardMarkup);
    const input = getSearchInput(element)!;

    const event = pressKey(getItem(element, 'Dropdown'), 'd');

    expect(document.activeElement).toBe(input);
    expect(input.value).toBe('d');
    expect(event.defaultPrevented).toBe(true);
    expect(visibleLabels(element)).toEqual(['Dropdown', 'Date']);

    // Backspace deletes from the query.
    pressKey(getItem(element, 'Dropdown'), 'Backspace');
    expect(input.value).toBe('');
    expect(visibleLabels(element)).toEqual(['Plain Text', 'Dropdown', 'Date']);
  });

  it('does not redirect modified or non-printable keys to the search input', async () => {
    const element = await createFromMarkup(keyboardMarkup);
    const input = getSearchInput(element)!;

    const item = getItem(element, 'Dropdown');
    item.dispatchEvent(
      new KeyboardEvent('keydown', {
        key: 'c',
        metaKey: true,
        bubbles: true,
        composed: true,
        cancelable: true,
      })
    );
    pressKey(item, 'Enter');
    pressKey(item, 'Tab');

    expect(input.value).toBe('');
    expect(document.activeElement).not.toBe(input);
  });

  it('does nothing on typing keys in non-searchable mode', async () => {
    const element = await createFromMarkup(`
      <craft-action-menu>
        <button slot="invoker" type="button">Open</button>
        <div slot="content">
          <craft-action-item>One</craft-action-item>
          <craft-action-item>Two</craft-action-item>
        </div>
      </craft-action-menu>
    `);
    const focused = spyOnItemFocus(element);

    const typing = pressKey(getItem(element, 'One'), 'x');
    expect(typing.defaultPrevented).toBe(false);

    // Arrow navigation still works without a search input.
    pressKey(getItem(element, 'One'), 'ArrowDown');
    expect(focused).toEqual(['Two']);
  });

  it('focuses the first navigable item on open in non-searchable mode', async () => {
    const element = await createFromMarkup(`
      <craft-action-menu>
        <button slot="invoker" type="button">Open</button>
        <div slot="content">
          <craft-action-item hidden>One</craft-action-item>
          <craft-action-item>Two</craft-action-item>
        </div>
      </craft-action-menu>
    `);
    const focused = spyOnItemFocus(element);

    element.opened = true;
    await element.updateComplete;
    // Lion dispatches `show` on the overlay controller asynchronously.
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(focused).toEqual(['Two']);
  });
});

describe('groups', () => {
  async function createWithActions(
    actions: ActionMenuItem[]
  ): Promise<CraftActionMenu> {
    const element = document.createElement(
      'craft-action-menu'
    ) as CraftActionMenu;
    element.actions = actions;
    document.body.append(element);
    await element.updateComplete;
    return element;
  }

  /** The generated content container, as Lion leaves it. */
  function contentNode(element: CraftActionMenu): HTMLElement {
    return (
      (element.querySelector<HTMLElement>('.action-menu__search')
        ?.parentElement as HTMLElement) ??
      element.querySelector<HTMLElement>('[slot="content"]')!
    );
  }

  it('renders a heading followed by its members as siblings', async () => {
    const element = await createWithActions([
      {label: 'Ungrouped'},
      {
        type: 'group',
        heading: 'Europe',
        items: [
          {type: 'link', label: 'France', href: '/fr'},
          {type: 'link', label: 'Spain', href: '/es'},
        ],
      },
    ]);

    const shape = Array.from(contentNode(element).children).map((child) =>
      child.classList.contains('action-menu__heading')
        ? `heading:${child.textContent}`
        : `${child.tagName.toLowerCase()}:${child.textContent}`
    );

    expect(shape).toEqual([
      'craft-action-item:Ungrouped',
      'heading:Europe',
      'craft-action-item:France',
      'craft-action-item:Spain',
    ]);
  });

  it('keeps group members reachable as items, with their hrefs intact', async () => {
    const element = await createWithActions([
      {
        type: 'group',
        heading: 'Europe',
        items: [{type: 'link', label: 'France', href: '/fr'}],
      },
    ]);

    const items = element.querySelectorAll('craft-action-item');

    expect(items.length).toBe(1);
    expect((items[0] as unknown as CraftActionItem).href).toBe('/fr');
  });

  it('sorts danger items to the end of their own group, not the menu', async () => {
    const element = await createWithActions([
      {
        type: 'group',
        heading: 'One',
        items: [{label: 'Delete', variant: 'danger'}, {label: 'Keep'}],
      },
      {type: 'group', heading: 'Two', items: [{label: 'Later'}]},
    ]);

    const labels = Array.from(
      element.querySelectorAll('craft-action-item')
    ).map((item) => item.textContent);

    expect(labels).toEqual(['Keep', 'Delete', 'Later']);
  });

  it('renders a group with no heading as a bare run of items', async () => {
    const element = await createWithActions([
      {type: 'group', items: [{label: 'Solo'}]},
    ]);

    expect(element.querySelector('.action-menu__heading')).toBeNull();
    expect(element.querySelectorAll('craft-action-item').length).toBe(1);
  });

  it('leaves the heading out of the searchable item set', async () => {
    const element = document.createElement(
      'craft-action-menu'
    ) as CraftActionMenu;
    element.searchable = true;
    element.actions = [
      {type: 'group', heading: 'Europe', items: [{label: 'France'}]},
    ];
    document.body.append(element);
    await element.updateComplete;

    typeIntoSearch(element, 'zzz');

    // The heading is presentational, so filtering never hides or matches it.
    expect(element.querySelector('.action-menu__heading')).not.toBeNull();
    expect(visibleLabels(element)).toEqual([]);
  });
});
