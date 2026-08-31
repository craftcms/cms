import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './select.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Form Controls/Select Controls/Select',
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
