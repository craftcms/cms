import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import {html} from 'lit';

import './dialog.js';
import type CraftDialog from './dialog.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `dialog.ts` surfaces it here without touching this file.
 */
const {args, argTypes} = getStorybookHelpers<CraftDialog>('craft-dialog');

type CraftDialogArgs = CraftDialog & typeof args;

const meta = {
  title: 'Components/Dialog',
  component: 'craft-dialog',
  args: {...args, label: 'Dialog'},
  argTypes,
  parameters: {
    layout: 'centered',
  },
  render: function (args) {
    function openDialog() {
      const dialog = document.getElementById('storybook-dialog') as CraftDialog;
      dialog.opened = true;
    }

    return html`
      <craft-dialog label="Dialog" id="storybook-dialog">
        This is some text within a dialog.

        <craft-button slot="footer" data-dialog="close">Close</craft-button>
      </craft-dialog>

      <craft-button @click="${openDialog}">Open Dialog</craft-button>
    `;
  },
} satisfies Meta<CraftDialogArgs>;

export default meta;
type Story = StoryObj<CraftDialogArgs>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
