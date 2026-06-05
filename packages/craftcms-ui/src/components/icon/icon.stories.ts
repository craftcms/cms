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
