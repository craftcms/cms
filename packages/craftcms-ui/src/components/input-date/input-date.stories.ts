import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input-date.js';
import type CraftInputDate from './input-date.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `input-date.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftInputDate>('craft-input-date');

type InputDateArgs = CraftInputDate & typeof args;

const meta = {
  title: 'Controls/Input Date',
  component: 'craft-input-date',
  args: {...args, 'label-slot': 'Post Date'},
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<InputDateArgs>;

export default meta;
type Story = StoryObj<InputDateArgs>;

/** The browser's own date picker, with Craft's label and help text around it. */
export const Default: Story = {};

export const WithHelpText: Story = {
  args: {'help-text-slot': 'When the entry should go live.'},
};

/**
 * `min` and `max` bound the selectable range. Both are `YYYY-MM-DD`, and both
 * are properties rather than attributes — bind them with `.min` / `.max`.
 */
export const Bounded: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-input-date
      label="Post Date"
      help-text="August 2026 only."
      .min="${'2026-08-01'}"
      .max="${'2026-08-31'}"
    ></craft-input-date>
  `,
};

export const Disabled: Story = {
  args: {disabled: true},
};

export const Readonly: Story = {
  args: {readonly: true},
};

/**
 * To edit a date and a time as one value, use
 * [Input Date Time](?path=/docs/controls-input-date-time--docs), which pairs
 * this with an [Input Time](?path=/docs/controls-input-time--docs) and submits
 * them together.
 */
export const Sizes: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      ${(['small', 'medium', 'large'] as const).map(
        (size) =>
          html`<craft-input-date
            label='size="${size}"'
            size="${size}"
          ></craft-input-date>`
      )}
    </div>
  `,
};
