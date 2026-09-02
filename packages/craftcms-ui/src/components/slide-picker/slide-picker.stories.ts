import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftSlidePicker from './slide-picker.js';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './slide-picker.js';

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
  args: {},
};

export const ReadOnly: Story = {
  args: {
    readonly: true,
  },
};

export const FiveSteps: Story = {
  args: {
    min: 0,
    max: 100,
    step: 20,
    value: 60,
    label: 'Completion',
  },
};
