import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import './select-rich.js';
import '../option/option.js';
import '../indicator/indicator.js';

const meta = {
  title: 'Controls/Select Rich',
  component: 'craft-select-rich',
  args: {},
  render: function () {
    return html`
      <craft-select-rich label="Favorite Fruit" name="fruit">
        <craft-option .choiceValue=${'apple'}>Apple</craft-option>
        <craft-option .choiceValue=${'banana'}>Banana</craft-option>
        <craft-option .choiceValue=${'cherry'}>Cherry</craft-option>
        <craft-option .choiceValue=${'grape'}>Grape</craft-option>
        <craft-option .choiceValue=${'mango'}>Mango</craft-option>
      </craft-select-rich>
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
      <craft-select-rich label="System Status" name="status">
        <craft-option .choiceValue=${'online'}>
          <div class="flex items-center gap-1">
            <craft-indicator variant="success"></craft-indicator>
            <span>Online</span>
          </div>
        </craft-option>
        <craft-option .choiceValue=${'maintenance'}>
          <div class="flex items-center gap-1">
            <craft-indicator variant="warning"></craft-indicator>
            <span>Maintenance</span>
          </div>
        </craft-option>
        <craft-option .choiceValue=${'offline'}>
          <div class="flex items-center gap-1">
            <craft-indicator variant="danger"></craft-indicator>
            <span>Offline</span>
          </div>
        </craft-option>
      </craft-select-rich>
    `;
  },
};

export const Small: Story = {
  args: {},
  render: function () {
    return html`
      <craft-select-rich label="Size" name="size" small>
        <craft-option .choiceValue=${'sm'}>Small</craft-option>
        <craft-option .choiceValue=${'md'}>Medium</craft-option>
        <craft-option .choiceValue=${'lg'}>Large</craft-option>
      </craft-select-rich>
    `;
  },
};

export const WithHints: Story = {
  args: {},
  render: function () {
    return html`
      <craft-select-rich label="Section" name="section">
        <craft-option .choiceValue=${'blog'} hint="12 entries"
          >Blog</craft-option
        >
        <craft-option .choiceValue=${'news'} hint="4 entries"
          >News</craft-option
        >
        <craft-option .choiceValue=${'docs'} hint="89 entries"
          >Documentation</craft-option
        >
      </craft-select-rich>
    `;
  },
};
