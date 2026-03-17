import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {expect, waitFor} from 'storybook/test';

import {html} from 'lit';

import './dialog.js';
import type CraftDialog from './dialog.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Dialog',
  component: 'craft-dialog',
  args: {},
  parameters: {
    layout: 'centered',
  },
  render: function (args) {
    function openDialog() {
      const dialog = document.getElementById('storybook-dialog') as CraftDialog;
      dialog.open = true;
    }

    return html`
      <craft-dialog label="Dialog" id="storybook-dialog">
        This is some text within a dialog.

        <craft-button slot="footer" data-dialog="close" autofocus
          >Close</craft-button
        >
      </craft-dialog>

      <craft-button @click="${openDialog}">Open Dialog</craft-button>
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
  play: async ({canvas, userEvent}) => {
    const openBtn = canvas.getByShadowText(/Open Dialog/i);
    const dialog = canvas.getByShadowRole('dialog');
    const closeBtn = canvas.getByShadowText(/Close/i);
    await userEvent.click(openBtn);

    await expect(dialog).toHaveClass('open');
    await waitFor(() => expect(closeBtn).toHaveFocus());
    await userEvent.keyboard('{Escape}');
    await waitFor(() => expect(openBtn).toHaveFocus());
    await expect(dialog).not.toHaveClass('open');
  },
};
