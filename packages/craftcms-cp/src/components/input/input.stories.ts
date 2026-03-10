import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './input.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Controls/Input',
  component: 'craft-input',
  args: {
    size: undefined,
  },
  argTypes: {
    size: {
      control: {type: 'number'},
    },
  },
  render: function ({maxlength}) {
    return html`<craft-input label="Craft Input" help-text="This is some instructions text" .maxlength="${maxlength}"></craft-input>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const WithMaxLength: Story = {
  args: {
    maxlength: 5,
  },
};

export const WithPrefix: Story = {
  render: () => html`
    <craft-input label="Search">
      <craft-icon name="search" slot="prefix"></craft-icon>
    </craft-input>
  `,
};

export const WithSuffix: Story = {
  render: () => html`
    <craft-input label="Search">
      <craft-icon name="search" slot="suffix"></craft-icon>
    </craft-input>
  `,
};
