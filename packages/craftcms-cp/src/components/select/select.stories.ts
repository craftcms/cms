import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './select.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Controls/Select',
  component: 'craft-select',
  args: {},
  render: function ({opened}) {
    return html`<craft-select name="favoriteColor" label="Favorite Color">
      <craft-option .choiceValue="${null}">Select a color</craft-option>
      <craft-option .choiceValue="${'red'}">Red</craft-option>
      <craft-option .choiceValue="${'hotpink'}">Hotpink</craft-option>
      <craft-option .choiceValue="${'blue'}">Blue</craft-option>
    </craft-select>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const Open: Story = {
  args: {
    opened: true,
  },
  render: function ({opened}) {
    return html`<craft-select
      name="favoriteColor"
      label="Favorite Color"
      opened
    >
      <craft-option .choiceValue="${null}">Select a color</craft-option>
      <craft-option .choiceValue="${'red'}">Red</craft-option>
      <craft-option .choiceValue="${'hotpink'}">Hotpink</craft-option>
      <craft-option .choiceValue="${'blue'}">Blue</craft-option>
    </craft-select>`;
  },
};
