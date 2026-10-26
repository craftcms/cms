import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftGrid from './grid.js';
import {html, nothing} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './grid.js';
import '../action-menu/action-menu.js';
import '../action-item/action-item.js';
import '../button/button.js';
import '../icon/icon.js';

const {events, args, argTypes} = getStorybookHelpers('craft-grid');

const meta: Meta<CraftGrid> = {
  title: 'Components/Grid',
  component: 'craft-grid',
  args,
  argTypes,
  parameters: {
    actions: {
      handles: events,
    },
  },
};

export default meta;
type Story = StoryObj<CraftGrid & typeof args>;

interface Item {
  label: string;
  icon: string;
  /** Goes somewhere, as a link; without it, the item is a button. */
  href?: string;
  /** Whether the item has a menu of its own actions. */
  menu?: boolean;
}

const recent: Item[] = [
  {label: 'Blog', icon: 'newspaper', href: '#', menu: true},
  {label: 'About us', icon: 'file', href: '#', menu: true},
  {label: 'Settings', icon: 'gear', href: '#'},
  {label: 'Users', icon: 'user-group', href: '#'},
];

const actions: Item[] = [
  {label: 'New entry', icon: 'plus', menu: true},
  {label: 'New asset', icon: 'image', menu: true},
  {label: 'New user', icon: 'user-plus'},
  {label: 'Clear caches', icon: 'broom'},
  {label: 'Rebuild search indexes', icon: 'magnifying-glass'},
  {label: 'View the queue', icon: 'list-check', href: '#'},
  {label: 'Keyboard shortcuts', icon: 'keyboard'},
  {label: 'Sign out', icon: 'arrow-right-from-bracket'},
];

const rowStyle =
  'display: grid; grid-template-columns: 1fr auto; align-items: center; gap: var(--c-spacing-sm); min-block-size: var(--c-size-touch-target)';
const itemStyle =
  'display: flex; align-items: center; gap: var(--c-spacing-sm); padding: 0; border: 0; background: none; color: inherit; font: inherit; text-align: start; cursor: pointer';

function section(id: string, heading: string, items: Item[]) {
  return html`
    <h3 id="${id}">${heading}</h3>
    <craft-grid aria-labelledby="${id}">
      ${items.map(
        (item) => html`
          <craft-grid-row style="${rowStyle}">
            <craft-grid-cell>
              ${item.href
                ? html`<a href="${item.href}" style="${itemStyle}">
                    <craft-icon name="${item.icon}"></craft-icon>
                    ${item.label}
                  </a>`
                : html`<button type="button" style="${itemStyle}">
                    <craft-icon name="${item.icon}"></craft-icon>
                    ${item.label}
                  </button>`}
            </craft-grid-cell>
            ${item.menu
              ? html`<craft-grid-cell>
                  <craft-action-menu>
                    <craft-button
                      type="button"
                      icon
                      size="small"
                      variant="plain"
                      slot="invoker"
                    >
                      <craft-icon name="ellipsis"></craft-icon>
                      <span class="sr-only">Actions for ${item.label}</span>
                    </craft-button>
                    <div slot="content">
                      <craft-action-item icon="arrow-up-right-from-square"
                        >Open in a new tab</craft-action-item
                      >
                      <craft-action-item icon="copy"
                        >Copy URL</craft-action-item
                      >
                    </div>
                  </craft-action-menu>
                </craft-grid-cell>`
              : nothing}
          </craft-grid-row>
        `
      )}
    </craft-grid>
  `;
}

interface Key {
  symbol: string;
  name: string;
}

const instructions: {keys: Key[]; label: string}[] = [
  {keys: [{symbol: 'Tab', name: 'Tab'}], label: 'Next section'},
  {
    keys: [
      {symbol: '↑', name: 'Up arrow'},
      {symbol: '↓', name: 'Down arrow'},
    ],
    label: 'Move within a section',
  },
  {
    keys: [
      {symbol: '←', name: 'Left arrow'},
      {symbol: '→', name: 'Right arrow'},
    ],
    label: 'Move to an item’s actions',
  },
  {keys: [{symbol: 'Enter', name: 'Enter'}], label: 'Open'},
];

const instructionsStyle =
  'display: flex; flex-wrap: wrap; column-gap: var(--c-spacing-lg); row-gap: var(--c-spacing-sm); margin: 0; padding: 0; list-style: none; font-size: var(--c-text-sm); color: var(--c-text-quiet)';

/** The grids' keys, in a region of their own beside them, one command per list item. */
function keyboardInstructions(id: string) {
  return html`
    <section
      aria-labelledby="${id}"
      style="margin-block-start: var(--c-spacing-lg)"
    >
      <h3 id="${id}" class="sr-only">Keyboard instructions</h3>
      <ul role="list" style="${instructionsStyle}">
        ${instructions.map(
          ({keys, label}) => html`
            <li
              style="display: inline-flex; align-items: center; gap: var(--c-spacing-sm)"
            >
              <span style="display: inline-flex; gap: var(--c-spacing-xs)">
                ${keys.map(
                  (key) =>
                    html`<kbd
                      ><span aria-hidden="true">${key.symbol}</span
                      ><span class="sr-only">${key.name}</span></kbd
                    >`
                )}
              </span>
              ${label}
            </li>
          `
        )}
      </ul>
    </section>
  `;
}

/**
 * Two labeled groups of links and buttons, each its own grid. Some items have
 * a menu of their own actions beside them, which is what makes this a grid:
 * Down and Up move through the list, Right and Left between an item and its
 * menu, and Tab from one group to the next. Keyboard instructions beside
 * them say so, since few people know a grid's keys.
 */
export const Default: Story = {
  render: () => html`
    <div style="max-inline-size: 28rem">
      ${section('grid-recent-heading', 'Recent', recent)}
      ${section('grid-actions-heading', 'Actions', actions)}
      ${keyboardInstructions('grid-keys-heading')}
    </div>
  `,
};
