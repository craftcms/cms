import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import '../icon/icon.js';
import '../button/button.js';
import './action-item.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Action Item',
  component: 'craft-action-item',
  argTypes: {},
  render: ({active, indicator, href, icon}) => {
    return html`
      <craft-action-item icon="${icon}" ?active="${active}" ?href="${href}"
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
