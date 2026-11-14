import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftSlidePickerButton from './slide-picker-button.js';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './slide-picker-button.js';

const {events, args, argTypes, template} = getStorybookHelpers(
  'craft-slide-picker-button'
);

const meta: Meta<CraftSlidePickerButton> = {
  title: 'Form Controls/Slide Picker Button',
  component: 'craft-slide-picker-button',
  args,
  argTypes,
  render: (args) => template(args),
  parameters: {
    actions: {
      handles: events,
    },
  },
};

export default meta;
type Story = StoryObj<CraftSlidePickerButton & typeof args>;

export const Default: Story = {
  args: {
    label: 'Width',
    'value-unit': '%',
    min: 25,
    max: 100,
    step: 25,
    value: 50,
  },
};

/**
 * `button-label` names each button after its item, when several share a
 * `label`.
 */
export const InARow: Story = {
  render: () => html`
    <ul style="display: grid; gap: 0.5rem; padding: 0; list-style: none;">
      ${['News', 'Updates'].map(
        (name) => html`
          <li style="display: flex; align-items: center; gap: 0.5rem;">
            <span style="flex: 1;">${name}</span>
            <craft-slide-picker-button
              label="Columns"
              button-label=${`Columns ${name}`}
              value-unit="columns"
              min="1"
              max="3"
              step="1"
              value="1"
            ></craft-slide-picker-button>
          </li>
        `
      )}
    </ul>
  `,
};

export const ReadOnly: Story = {
  args: {
    label: 'Width',
    'value-unit': '%',
    min: 25,
    max: 100,
    step: 25,
    value: 75,
    readonly: true,
  },
};
