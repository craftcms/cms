import type {Meta, StoryObj} from '@storybook/html-vite';
import {DisclosureMenu} from '../src/index';
import {createEventLog, storyLayout, type EventLog} from './_log';

/**
 * `DisclosureMenu` — dropdown menu — playground section 12.
 *
 * Pairs a trigger button (`aria-controls` / `aria-expanded`) with a menu panel.
 * Click to toggle; the panel anchors under the trigger (flipping above when
 * there's no room). Arrow keys move between items, typing a letter does type-ahead
 * search, Esc / outside-click closes. A second variant adds a live search input.
 *
 * Each story builds its menu as the trigger's `nextElementSibling` with a unique
 * id, so `DisclosureMenu` resolves it even before Storybook mounts the canvas.
 */
const meta: Meta = {
  title: 'DisclosureMenu',
};
export default meta;

type Story = StoryObj;

// Unique-id counter so re-renders don't collide with menus moved to <body>.
let uid = 0;
const nextId = (): string => `sb-disc-menu-${++uid}`;

/** Wire a menu's lifecycle events into the log. */
function wireMenuEvents(
  log: EventLog,
  menu: DisclosureMenu,
  label: string
): void {
  for (const evt of ['beforeShow', 'show', 'hide'] as const) {
    menu.on(evt, () => log.log('disclosure', `${label}: ${evt}`));
  }
}

export const Dropdown: Story = {
  render: () => {
    const log = createEventLog();
    const menuId = nextId();
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        A <code>new DisclosureMenu(trigger)</code> with static markup: groups, a
        separator, plus a disabled and a destructive item. Open it, then use
        <kbd>↑</kbd>/<kbd>↓</kbd>, type a letter for <strong>type-ahead</strong>, or
        <kbd>Esc</kbd> to close.
      </p>
      <div class="pg-controls">
        <button type="button" class="pg-disc-trigger" aria-controls="${menuId}" aria-expanded="false">Actions ▾</button>
        <div id="${menuId}" class="menu menu--disclosure" data-align="left">
          <ul>
            <li><button type="button" class="menu-item"><span class="menu-item-label">New entry</span></button></li>
            <li><button type="button" class="menu-item sel"><span class="menu-item-label">Duplicate</span></button></li>
            <li><button type="button" class="menu-item disabled" disabled><span class="menu-item-label">Rename (disabled)</span></button></li>
          </ul>
          <hr />
          <h3 class="h6">Danger zone</h3>
          <ul>
            <li><button type="button" class="menu-item error" data-destructive="true"><span class="menu-item-label">Delete</span></button></li>
          </ul>
        </div>
        <button type="button" data-act="destroy">Destroy this menu</button>
      </div>`;

    const trigger = main.querySelector<HTMLButtonElement>('.pg-disc-trigger')!;
    const menu = new DisclosureMenu(trigger);
    wireMenuEvents(log, menu, 'actions');

    menu.$container
      ?.querySelectorAll<HTMLButtonElement>('.menu-item:not(.disabled)')
      .forEach((item) => {
        item.addEventListener('click', () => {
          const label =
            item.querySelector('.menu-item-label')?.textContent ?? '?';
          log.log('disclosure', `actions: selected "${label}"`);
        });
      });

    main
      .querySelector<HTMLButtonElement>('[data-act="destroy"]')!
      .addEventListener('click', () => {
        menu.destroy();
        log.log('disclosure', 'Destroyed the DisclosureMenu instance');
      });

    return storyLayout(main);
  },
};

interface FilterableArgs {
  withSearchInput: boolean;
}

export const Filterable: StoryObj<FilterableArgs> = {
  argTypes: {
    withSearchInput: {
      control: 'boolean',
      description: 'Add a live search input that filters the items',
    },
  },
  args: {
    withSearchInput: true,
  },
  render: (args) => {
    const log = createEventLog();
    const menuId = nextId();
    const main = document.createElement('div');
    main.innerHTML = `
      <p>
        Items are added via the API (<code>addItems</code>). With
        <code>withSearchInput</code> enabled, a search field filters them live.
      </p>
      <div class="pg-controls">
        <button type="button" class="pg-disc-trigger" aria-controls="${menuId}" aria-expanded="false">Filterable ▾</button>
        <div id="${menuId}" class="menu menu--disclosure" data-align="left"></div>
      </div>`;

    const trigger = main.querySelector<HTMLButtonElement>('.pg-disc-trigger')!;
    const menu = new DisclosureMenu(trigger, {
      withSearchInput: args.withSearchInput,
    });
    wireMenuEvents(log, menu, 'filter');

    const fruits = [
      'Apple',
      'Apricot',
      'Banana',
      'Blueberry',
      'Cherry',
      'Grape',
      'Mango',
      'Orange',
      'Peach',
      'Pear',
    ];
    menu.addItems(
      fruits.map((name) => ({
        label: name,
        onActivate: () => log.log('disclosure', `filter: selected "${name}"`),
      }))
    );

    return storyLayout(main);
  },
};
