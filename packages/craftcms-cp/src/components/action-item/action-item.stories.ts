import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import '../icon/icon.js';
import '../button/button.js';
import './action-item.js';
import {Color} from '@src/constants/colors';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Action Item',
  component: 'craft-action-item',
  argTypes: {},
  parameters: {
    layout: 'centered',
    a11y: {test: 'todo'},
  },
  decorators: [
    (story) => html`
      <style>
        .stage {
          padding: var(--c-spacing-sm);
          border-radius: var(--c-radius-sm);
          width: calc(320rem / 16);
          background-color: var(--c-surface-raised);
          box-shadow: var(--c-shadow-raised);
        }
      </style>
      <div class="stage">${story()}</div>
    `,
  ],
  render: ({active, indicator, href, icon, checked}) => {
    return html`
      <craft-action-item
        icon="${icon}"
        ?active="${active}"
        ?href="${href}"
        ?checked="${checked}"
        >View in a new tab</craft-action-item
      >
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const Active: Story = {
  args: {
    active: true,
  },
};

export const WithIcon: Story = {
  args: {
    icon: 'up-right-from-square',
  },
};

export const WithSuffix: Story = {
  args: {
    icon: 'up-right-from-square',
  },
  render() {
    return html`
      <craft-action-item
        >View in a new tab
        <craft-button slot="suffix" size="small">Suffix</craft-button>
      </craft-action-item>
    `;
  },
};

export const Link: Story = {
  args: {
    href: 'https://craftcms.com',
  },
};

export const WithShortcut: Story = {
  args: {},
  render({icon, active, href, checked}) {
    return html`
      <craft-action-item icon="file" shortcut="S"> Save </craft-action-item>
    `;
  },
};

export const WithComplexShortcut: Story = {
  args: {},
  render() {
    return html`
      <craft-action-item
        icon="file"
        shortcut='{"key": "S", "alt": true, "shift": true}'
      >
        Save
      </craft-action-item>
    `;
  },
};

export const CheckableWithIcon: Story = {
  args: {
    checked: true,
    icon: 'up-right-from-square',
  },
  render({icon, active, href, checked}) {
    return html`
      <craft-action-item icon="home" ?href="${href}" checked type="checkbox">
        <div>
          <div class="font-bold">Entry Type</div>
          <pre>entryType</pre>
        </div>
      </craft-action-item>
      <craft-action-item
        icon="newspaper"
        active
        ?href="${href}"
        type="checkbox"
      >
        <div>
          <div class="font-bold">Entry Type</div>
          <pre>entryType</pre>
        </div>
      </craft-action-item>
    `;
  },
};

export const Colors: Story = {
  render() {
    return html`
      ${Object.entries(Color).map(([name, value]) => {
        return html`<craft-action-item data-color="${value}"
          >With color ${name}</craft-action-item
        >`;
      })}
    `;
  },
};
