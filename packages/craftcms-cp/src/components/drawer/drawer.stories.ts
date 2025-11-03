import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './drawer.js';
import '../button/button.js';

import type CraftDrawer from './drawer.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Drawer',
  component: 'craft-drawer',
  args: {},
  parameters: {
    layout: 'centered',
  },
  render: function (args) {
    function openDrawer() {
      const drawer = document.getElementById('drawer-overview') as CraftDrawer;
      drawer.open = true;
    }

    return html`
      <craft-drawer label="Drawer" id="drawer-overview">
        Lorem ipsum dolor sit amet, consectetur adipiscing elit.
        <craft-button slot="footer" variant="brand" data-drawer="close"
          >Close</craft-button
        >
      </craft-drawer>

      <craft-button @click="${openDrawer}">Open Drawer</craft-button>
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
