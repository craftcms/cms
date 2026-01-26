import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './badge-indicator.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Badge Indicator',
  component: 'craft-badge-indicator',
  argTypes: {},
  render: (args) => html`
    <craft-badge-indicator></craft-badge-indicator>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const WithNumber: Story = {
  name: 'With Number',
  args: {
    number: 5,
    itemType: 'updates'
  },
  render: (args) => html`
    <craft-badge-indicator .number="${args.number}" .itemType="${args.itemType}"></craft-badge-indicator>
  `,
}

export const WithoutNumber: Story = {
  name: 'Without Number',
  args: {
    number: 5,
    itemType: 'updates'
  },
  render: (args) => html`
    <craft-badge-indicator .itemType="${args.itemType}"></craft-badge-indicator>
  `,
}
