import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './icon.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Icon',
  component: 'craft-icon',
  args: {
    iconId: 'chevron-down',
  },
  render: function ({iconId}) {
    return html`<craft-icon name="${iconId}"></craft-icon>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

// Render a custom inline SVG via the default slot. When the slot has content,
// the slotted SVG is shown instead of the name-based icon.
export const SlottedSvg: Story = {
  render: () => html`
    <craft-icon>
      <svg
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
      >
        <path d="M12 2 15 9l7 .5-5.5 4.5L18 22l-6-3.5L6 22l1.5-8L2 9.5 9 9z" />
      </svg>
    </craft-icon>
  `,
};
