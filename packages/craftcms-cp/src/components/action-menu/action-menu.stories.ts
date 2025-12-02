import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import '../action-item/action-item.js';
import './action-menu.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Action Menu',
  component: 'craft-action-menu',
  argTypes: {},
  render: ({label, open}) => {
    return html`
      <craft-action-menu ?opened="${open}">
        <craft-button type="button" icon type="button" slot="invoker">
          <craft-icon name="ellipsis"></craft-icon>
        </craft-button>

        <craft-action-item icon="up-right-from-square" href="#"
          >View in a new tab</craft-action-item
        >
        <craft-action-item icon="eye" href="#">Preview File</craft-action-item>
        <hr />
        <craft-action-item icon="download">Download</craft-action-item>
        <craft-action-item icon="folder-open">
          Show in folder
        </craft-action-item>
        <craft-action-item icon="pen">Edit asset</craft-action-item>
        <hr />
        <craft-action-item icon="pen"> Open in Image Editor </craft-action-item>
        <hr />
        <craft-action-item variant="danger" icon="rotate">
          Replace
        </craft-action-item>
        <craft-action-item variant="danger" icon="x">
          Remove
        </craft-action-item>
      </craft-action-menu>
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};
