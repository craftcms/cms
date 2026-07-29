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
    return html`<craft-input
      label="Craft Input"
      help-text="This is some instructions text"
      placeholder="Placeholder value"
      .maxlength="${maxlength}"
    ></craft-input>`;
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

/** \`width="full"\` spans the column even when a \`maxlength\` is set. */
export const FullWidthOverride: Story = {
  render: () => html`
    <craft-input label="Port" maxlength="6" width="full"></craft-input>
  `,
};

/** \`width="auto"\` shrinks the control without requiring a \`maxlength\`. */
export const AutoWidth: Story = {
  render: () => html`<craft-input label="Handle" width="auto"></craft-input>`,
};
