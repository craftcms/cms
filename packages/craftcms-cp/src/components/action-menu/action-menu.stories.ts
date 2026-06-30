import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {ref} from 'lit/directives/ref.js';

import '../action-item/action-item.js';
import './action-menu.js';
import type CraftActionMenu from './action-menu.js';
import type {ActionMenuItem} from './action-menu.types.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Action Menu',
  component: 'craft-action-menu',
  argTypes: {},
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
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

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
 * Data-driven mode with a custom slotted invoker — the slotted invoker
 * overrides the generated default, while items still come from `actions`.
 */
export const DataDrivenWithCustomInvoker: Story = {
  render: () =>
    html`<craft-action-menu .actions="${dataDrivenActions}">
      <craft-button type="button" slot="invoker" appearance="secondary">
        Custom invoker
      </craft-button>
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
