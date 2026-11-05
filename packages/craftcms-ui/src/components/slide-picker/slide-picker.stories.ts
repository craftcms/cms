import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftSlidePicker from './slide-picker.js';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './slide-picker.js';

/**
 * Lion's own `label` and `help-text` are slots here, so they are set through
 * the `label-slot` and `help-text-slot` args rather than as attributes.
 */
const {events, args, argTypes, template} =
  getStorybookHelpers('craft-slide-picker');

const meta: Meta<CraftSlidePicker> = {
  title: 'Form Controls/Slide Picker',
  component: 'craft-slide-picker',
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
type Story = StoryObj<CraftSlidePicker & typeof args>;

export const Default: Story = {
  args: {
    'label-slot': 'Completion',
  },
};

/** A read-only picker can still be focused, so its value can be read. */
export const ReadOnly: Story = {
  render: () => html`
    <craft-slide-picker
      label="Completion"
      readonly
      model-value="60"
    ></craft-slide-picker>
  `,
};

export const FiveSteps: Story = {
  args: {
    min: 0,
    max: 100,
    step: 20,
    'model-value': 60,
    'label-slot': 'Completion',
  },
};
