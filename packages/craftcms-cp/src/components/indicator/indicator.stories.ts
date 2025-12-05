import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './indicator.js';
import {Variant} from '@/types';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Indicator',
  component: 'craft-indicator',
  args: {},
  render: function () {
    return html`
      <div class="stack">
        ${Object.keys(Variant).map(
          (variant) =>
            html`<craft-indicator variant="${Variant[variant]}"></craft-indicator>`
        )}
      </div>
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
