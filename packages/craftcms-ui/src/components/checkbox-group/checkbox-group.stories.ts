import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './checkbox-group.js';
import '../checkbox/checkbox.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Form Controls/Choice Controls/Checkbox Group',
  component: 'craft-checkbox-group',
  args: {},
  render: function () {
    return html`<craft-checkbox-group name="scientists[]" label="Scientists">
      <craft-checkbox
        label="Marie Curie"
        .choiceValue=${'curie'}
      ></craft-checkbox>
      <craft-checkbox
        label="Ada Lovelace"
        .choiceValue=${'lovelace'}
        .checked=${true}
      ></craft-checkbox>
      <craft-checkbox
        label="Grace Hopper"
        .choiceValue=${'hopper'}
      ></craft-checkbox>
    </craft-checkbox-group>`;
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
    html`<craft-checkbox-group label="Allowed kinds">
      <div>
        <craft-checkbox>
          <input
            slot="input"
            type="checkbox"
            id="kind-image"
            name="allowedKinds[]"
            value="image"
            checked
          />
          <label slot="label" for="kind-image">Image</label>
        </craft-checkbox>
      </div>
      <div>
        <craft-checkbox>
          <input
            slot="input"
            type="checkbox"
            id="kind-video"
            name="allowedKinds[]"
            value="video"
          />
          <label slot="label" for="kind-video">Video</label>
        </craft-checkbox>
      </div>
    </craft-checkbox-group>`,
};
