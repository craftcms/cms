import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {expect, waitFor} from 'storybook/test';

import {html} from 'lit';

import './dialog.js';
import './dialog-content.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Dialog',
  component: 'craft-dialog',
  args: {},
  parameters: {
    layout: 'centered',
  },
  render: function (args) {
    return html`
      <craft-dialog>
        <craft-button slot="invoker">Open Dialog</craft-button>
        <craft-dialog-content class="test" slot="content">
          This is some text within a dialog.
          <craft-button
            slot="footer"
            @click="${(event: Event) =>
              event.target?.dispatchEvent(
                new Event('close-overlay', {bubbles: true})
              )}"
            >Close</craft-button
          >
        </craft-dialog-content>
      </craft-dialog>
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
    const closeBtn = canvas.getByText(/Close/i);
    await userEvent.click(openBtn);

    const dialog = canvas.getByRole('dialog');
    await expect(dialog).toHaveClass('overlays__overlay');
    await userEvent.click(closeBtn);
    await expect(dialog).not.toHaveClass('overlays__overlay');
    await expect(openBtn).toHaveFocus();
  },
};
