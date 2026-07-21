import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import '../icon/icon.js';
import '../nav-item/nav-item.js';
import './nav-list.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Nav List',
  component: 'craft-nav-list',
  argTypes: {},
  parameters: {
    a11y: {
      config: {
        rules: [
          {
            id: 'list',
            enabled: false,
          },
          {
            id: 'listitem',
            enabled: false,
          },
        ],
      },
    },
  },
  render: () => {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item icon="gauge" active>Dashboard</craft-nav-item>
        <craft-nav-item icon="pencil">Entries</craft-nav-item>
        <craft-nav-item icon="image">Assets</craft-nav-item>
        <craft-nav-item icon="users">Users</craft-nav-item>
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

/** Nested in a nav item's `subnav` slot for a collapsible hierarchy. */
export const Nested: Story = {
  args: {},
  render() {
    return html`
      <craft-nav-list style="max-width: 300px">
        <craft-nav-item icon="gauge" active>Dashboard</craft-nav-item>
        <craft-nav-item icon="code">
          GraphQL
          <craft-nav-list slot="subnav">
            <craft-nav-item>Schemas</craft-nav-item>
            <craft-nav-item active>Tokens</craft-nav-item>
            <craft-nav-item external>GraphiQL</craft-nav-item>
          </craft-nav-list>
        </craft-nav-item>
        <craft-nav-item icon="gear">Settings</craft-nav-item>
      </craft-nav-list>
    `;
  },
};
