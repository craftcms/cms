import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './select.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Controls/Select',
  component: 'craft-select',
  args: {},
  render: function () {
    return html`
      <craft-select
        label="Language"
        id="site-language"
        name="language"
        .modelValue="en-US"
      >
        <select slot="input">
          <option value="">Select a language</option>
          <option value="fr-FR">French</option>
          <option value="en-US">English</option>
          <option value="de-DE">German</option>
        </select>
      </craft-select>
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const InlineLabel: Story = {
  render: () => html`
    <craft-select label="Items per page" label-position="start" small>
      <select slot="input">
        <option value="50">50</option>
        <option value="100">100</option>
        <option value="250">250</option>
      </select>
    </craft-select>
  `,
};

// Demonstrates the automatic fallback: help text present + label-position
// set still renders the normal stacked layout, not a broken hybrid.
export const InlineLabelWithHelpText: Story = {
  render: () => html`
    <craft-select
      label="Items per page"
      label-position="start"
      help-text="Applies to every source in this index."
    >
      <select slot="input">
        <option value="50">50</option>
        <option value="100">100</option>
        <option value="250">250</option>
      </select>
    </craft-select>
  `,
};
