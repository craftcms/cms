import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input-time.js';
import type CraftInputTime from './input-time.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `input-time.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftInputTime>('craft-input-time');

type InputTimeArgs = CraftInputTime & typeof args;

const meta = {
  title: 'Form Controls/Text Controls/Input Time',
  component: 'craft-input-time',
  args: {...args, 'label-slot': 'Start Time'},
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<InputTimeArgs>;

export default meta;
type Story = StoryObj<InputTimeArgs>;

/** The browser's own time picker, with Craft's label and help text around it. */
export const Default: Story = {};

/**
 * `minute-increment` sets the spacing between the picker's suggestions, in
 * minutes. It is applied to the native input as `step`.
 */
export const MinuteIncrement: Story = {
  args: {'minute-increment': 15, 'help-text-slot': 'Quarter-hour steps.'},
};

/**
 * `force-round-time` snaps a typed time onto the nearest step when the field
 * changes, rather than leaving an off-step value in place.
 */
export const ForceRoundTime: Story = {
  args: {
    'minute-increment': 15,
    'force-round-time': true,
    'help-text-slot': 'Type 09:07 and leave the field — it rounds to 09:00.',
  },
};

/**
 * `disabled-time-ranges` takes `[start, end]` pairs of `HH:MM` times. The start
 * is inclusive and the end exclusive.
 *
 * The ranges are enforced by validation rather than by the picker: a time
 * inside one is still selectable, and fails with "This time is unavailable."
 */
export const DisabledRanges: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-input-time
      label="Start Time"
      help-text="Noon to 1pm is unavailable — pick 12:30 to see it fail."
      .disabledTimeRanges="${[['12:00', '13:00']]}"
    ></craft-input-time>
  `,
};

/**
 * `min` and `max` bound the selectable range. Both are `HH:MM`, and both are
 * properties rather than attributes — bind them with `.min` / `.max`.
 */
export const Bounded: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-input-time
      label="Start Time"
      help-text="Office hours only."
      .min="${'09:00'}"
      .max="${'17:00'}"
    ></craft-input-time>
  `,
};

export const Disabled: Story = {
  args: {disabled: true},
};

export const Readonly: Story = {
  args: {readonly: true},
};
