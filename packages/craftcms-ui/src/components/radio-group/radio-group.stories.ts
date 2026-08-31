import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './radio-group.js';
import '../radio/radio.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Form Controls/Choice Controls/Radio Group',
  component: 'craft-radio-group',
  args: {},
  render: function () {
    return html`<craft-radio-group name="scientist" label="Scientists">
      <craft-radio label="Marie Curie" .choiceValue=${'curie'}></craft-radio>
      <craft-radio
        label="Ada Lovelace"
        .choiceValue=${'lovelace'}
        .checked=${true}
      ></craft-radio>
      <craft-radio label="Grace Hopper" .choiceValue=${'hopper'}></craft-radio>
    </craft-radio-group>`;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const ServerRendered: Story = {
  render: () =>
    html`<craft-radio-group label="Color">
      <div>
        <craft-radio>
          <input
            slot="input"
            type="radio"
            id="color-red"
            name="color"
            value="red"
          />
          <label slot="label" for="color-red">Red</label>
        </craft-radio>
      </div>
      <div>
        <craft-radio>
          <input
            slot="input"
            type="radio"
            id="color-green"
            name="color"
            value="green"
            checked
          />
          <label slot="label" for="color-green">Green</label>
        </craft-radio>
      </div>
      <div>
        <craft-radio>
          <input
            slot="input"
            type="radio"
            id="color-blue"
            name="color"
            value="blue"
          />
          <label slot="label" for="color-blue">Blue</label>
        </craft-radio>
      </div>
    </craft-radio-group>`,
};
