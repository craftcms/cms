import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import './combobox.js';
import '../option/option.js';
import '../indicator/indicator.js';

const meta = {
  title: 'Controls/Combobox',
  component: 'craft-combobox',
  args: {},
  render: function () {
    return html`
      <craft-combobox label="Country" name="country">
        <craft-option .choiceValue=${'us'}>United States</craft-option>
        <craft-option .choiceValue=${'ca'}>Canada</craft-option>
        <craft-option .choiceValue=${'mx'}>Mexico</craft-option>
        <craft-option .choiceValue=${'uk'}>United Kingdom</craft-option>
        <craft-option .choiceValue=${'fr'}>France</craft-option>
        <craft-option .choiceValue=${'de'}>Germany</craft-option>
      </craft-combobox>
    `;
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {
  args: {},
};

export const RichOptions: Story = {
  args: {},
  render: function () {
    return html`
      <craft-combobox label="System Status" name="status" show-all-on-empty>
        <craft-option .choiceValue="${'true'}">
          <div class="flex items-center gap-1">
            <craft-indicator variant="success"></craft-indicator>
            <span>Online</span>
          </div>
        </craft-option>
        <craft-option .choiceValue="${'false'}">
          <div class="flex items-center gap-1">
            <craft-indicator variant="danger"></craft-indicator>
            <span>Offline</span>
          </div>
        </craft-option>
      </craft-combobox>
    `;
  },
};
