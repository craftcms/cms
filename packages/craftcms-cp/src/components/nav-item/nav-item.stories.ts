import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import '../icon/icon.js';
import '../navigation/navigation.js';
import '../button/button.js';
import './nav-item.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Nav Item',
  component: 'craft-nav-item',
  argTypes: {},
  render: ({active, indicator}) => {
    return html`
      <craft-nav-list>
        <craft-nav-item
          icon="gauge"
          ?active="${active}"
          ?indicator="${indicator}"
          >Dashboard</craft-nav-item
        >
      </craft-nav-list>
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

export const WithIndicator: Story = {
  args: {
    indicator: true,
  },
};

export const WithChildren: Story = {
  args: {},
  render(args) {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item icon="code">
          GraphQL
          <craft-nav-list slot="subnav">
            <craft-nav-item>Schemas</craft-nav-item>
            <craft-nav-item active>Tokens</craft-nav-item>
            <craft-nav-item external>GraphiQL</craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
      </craft-nav-list>
    `;
  },
};
