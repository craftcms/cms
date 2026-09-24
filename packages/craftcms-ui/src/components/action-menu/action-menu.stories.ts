import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import {html} from 'lit';
import {ref} from 'lit/directives/ref.js';

import '../action-item/action-item.js';
import '../button/button.js';
import '../icon/icon.js';
import './action-menu.js';
import type CraftActionMenu from './action-menu.js';
import type {ActionMenuItem} from './action-menu.types.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `action-menu.ts` surfaces it here without touching this file.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftActionMenu>('craft-action-menu');

type CraftActionMenuArgs = CraftActionMenu & typeof args;

const meta = {
  title: 'Components/Action Menu',
  component: 'craft-action-menu',
  args,
  argTypes,
  render: ({label, open}) => {
    return html`
      <craft-action-menu ?opened="${open}">
        <craft-button type="button" icon type="button" slot="invoker">
          <craft-icon name="ellipsis"></craft-icon>
          <span class="sr-only">Open Menu</span>
        </craft-button>

        <div slot="content">
          <craft-action-item icon="up-right-from-square" href="#"
            >View in a new tab</craft-action-item
          >
          <craft-action-item icon="eye" href="#"
            >Preview File</craft-action-item
          >
          <hr />
          <craft-action-item icon="download">Download</craft-action-item>
          <craft-action-item icon="folder-open">
            Show in folder
          </craft-action-item>
          <craft-action-item icon="pen">Edit asset</craft-action-item>
          <hr />
          <craft-action-item icon="pen">
            Open in Image Editor
          </craft-action-item>
          <hr />
          <craft-action-item variant="danger" icon="rotate">
            Replace
          </craft-action-item>
          <craft-action-item variant="danger" icon="x">
            Remove
          </craft-action-item>
        </div>
      </craft-action-menu>
    `;
  },
} satisfies Meta<CraftActionMenuArgs>;

export default meta;
type Story = StoryObj<CraftActionMenuArgs>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

/**
 * Data-driven mode: pass an `actions` array and the component renders the full
 * menu itself (default invoker + items). Note the `danger` item is sorted to
 * the bottom automatically, and the `hr` divider is preserved.
 */
const dataDrivenActions: ActionMenuItem[] = [
  {
    label: 'View',
    icon: 'eye',
    onClick: () => alert('View'),
  },
  {
    label: 'Edit',
    icon: 'pen',
    onClick: () => alert('Edit'),
  },
  {
    type: 'link',
    href: '#',
    label: 'Open in new tab',
    icon: 'up-right-from-square',
  },
  {type: 'hr'},
  {
    label: 'Delete',
    icon: 'trash',
    variant: 'danger',
    onClick: () => alert('Delete'),
  },
];

export const DataDriven: Story = {
  render: () =>
    html`<craft-action-menu
      .actions="${dataDrivenActions}"
      label="Actions"
      icon="ellipsis"
    ></craft-action-menu>`,
};

/**
 * Data-driven mode with `disabled` set — the generated default invoker is
 * rendered disabled (and dimmed) and the menu is prevented from opening,
 * whether activated by click or keyboard.
 */
export const DataDrivenDisabled: Story = {
  render: () =>
    html`<craft-action-menu
      .actions="${dataDrivenActions}"
      label="Actions"
      icon="ellipsis"
      disabled
    ></craft-action-menu>`,
};

/**
 * Slot-based mode with `disabled` set — a consumer-slotted invoker gets
 * `aria-disabled` applied and is dimmed, and the menu is prevented from
 * opening even though the invoker itself isn't a form control.
 */
export const SlotBasedDisabled: Story = {
  render: () =>
    html`<craft-action-menu disabled>
      <craft-button type="button" slot="invoker" variant="fill">
        Custom invoker
      </craft-button>
      <div slot="content">
        <craft-action-item icon="eye">Preview File</craft-action-item>
      </div>
    </craft-action-menu>`,
};

/**
 * Data-driven mode with a custom slotted invoker — the slotted invoker
 * overrides the generated default, while items still come from `actions`.
 */
export const DataDrivenWithCustomInvoker: Story = {
  render: () =>
    html`<craft-action-menu .actions="${dataDrivenActions}">
      <craft-button type="button" slot="invoker" variant="fill">
        Custom invoker
      </craft-button>
    </craft-action-menu>`,
};

/**
 * `searchable` adds a filter input to the top of the menu (slot-based mode).
 * Items match on their visible text plus a `data-keywords` attribute — the
 * channel for hidden search terms. Try typing "plainText": it matches the
 * "Plain Text" item by its handle even though the visible label doesn't
 * contain it.
 */
export const Searchable: Story = {
  render: () =>
    html`<craft-action-menu searchable>
      <craft-button type="button" slot="invoker" variant="fill">
        Add a field
      </craft-button>

      <div slot="content">
        <craft-action-item icon="pen" data-keywords="plainText">
          Plain Text
        </craft-action-item>
        <craft-action-item icon="caret-down" data-keywords="dropdown optedList">
          Dropdown
        </craft-action-item>
        <craft-action-item icon="calendar" data-keywords="dateTime">
          Date
        </craft-action-item>
        <craft-action-item icon="lightbulb" data-keywords="lightswitch boolean">
          Lightswitch
        </craft-action-item>
        <craft-action-item icon="image" data-keywords="assets">
          Assets
        </craft-action-item>
      </div>
    </craft-action-menu>`,
};

/**
 * `searchable` in data-driven mode: the `keywords` descriptor field is
 * rendered onto the generated `craft-action-item` as `data-keywords`, so
 * hidden terms (e.g. handles) match identically in both modes.
 */
export const SearchableDataDriven: Story = {
  render: () => {
    const actions: ActionMenuItem[] = [
      {label: 'Plain Text', icon: 'pen', keywords: 'plainText'},
      {label: 'Dropdown', icon: 'caret-down', keywords: 'dropdown optedList'},
      {label: 'Date', icon: 'calendar', keywords: 'dateTime'},
      {
        label: 'Lightswitch',
        icon: 'lightbulb',
        keywords: 'lightswitch boolean',
      },
      {label: 'Assets', icon: 'image', keywords: 'assets'},
    ];

    return html`<craft-action-menu
      searchable
      .actions="${actions}"
      label="Add a field"
    ></craft-action-menu>`;
  },
};

/**
 * Search never overrides consumer-controlled visibility: items hidden with the
 * `hidden` attribute stay hidden even when they match the query (filtering
 * uses a separate `data-search-hidden` mechanism). This is how a consumer like
 * component-select keeps already-selected options out of the menu while the
 * user searches. "Dropdown" below is consumer-hidden — searching for it (or
 * its keywords) never reveals it.
 */
export const SearchableWithHiddenItems: Story = {
  render: () =>
    html`<craft-action-menu searchable>
      <craft-button type="button" slot="invoker" variant="fill">
        Add a field
      </craft-button>

      <div slot="content">
        <craft-action-item icon="pen" data-keywords="plainText">
          Plain Text
        </craft-action-item>
        <craft-action-item
          icon="caret-down"
          data-keywords="dropdown optedList"
          hidden
        >
          Dropdown
        </craft-action-item>
        <craft-action-item icon="calendar" data-keywords="dateTime">
          Date
        </craft-action-item>
        <craft-action-item icon="image" data-keywords="assets">
          Assets
        </craft-action-item>
      </div>
    </craft-action-menu>`,
};

/**
 * Data-driven `display` item: the descriptor takes a DOM `Node` (or a function
 * returning one), not a framework component.
 */
export const DataDrivenWithDisplay: Story = {
  render: () => {
    const makeHeader = () => {
      const el = document.createElement('div');
      el.style.padding = 'var(--c-spacing-xs)';
      el.style.fontWeight = '600';
      el.textContent = 'Signed in as Brad';
      return el;
    };

    const actions: ActionMenuItem[] = [
      {type: 'display', node: makeHeader},
      {type: 'hr'},
      {type: 'link', href: '#', label: 'Profile'},
      {type: 'link', href: '#', label: 'Sign out', variant: 'danger'},
    ];

    return html`<craft-action-menu
      ${ref((el) => {
        if (el) (el as CraftActionMenu).actions = actions;
      })}
    ></craft-action-menu>`;
  },
};
