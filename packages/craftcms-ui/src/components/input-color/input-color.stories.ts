import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './input-color.js';
import type CraftInputColor from './input-color.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `input-color.ts` surfaces it here without touching this file.
 *
 * The template is written out rather than generated: the value is Lion's
 * `modelValue`, a property rather than an attribute, and the label is a slot.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftInputColor>('craft-input-color');

type CraftInputColorArgs = CraftInputColor & typeof args;

/** The shell every story shares, so each one only supplies its args. */
const field = (args: Record<string, unknown>, extra: unknown = '') => html`
  <craft-input-color
    ?disabled="${args.disabled}"
    .modelValue="${args.value ?? ''}"
    .presets="${(args.presets as string[]) ?? []}"
  >
    <label slot="label">${args['label-slot']}</label>
    ${args['help-text-slot']
      ? html`<span slot="help-text">${args['help-text-slot']}</span>`
      : ''}
    ${extra}
  </craft-input-color>
`;

const meta = {
  title: 'Form Controls/Text Controls/Input Color',
  component: 'craft-input-color',
  args: {...args, 'label-slot': 'Fill Color', value: '7ab55c'},
  argTypes,
  render: (args) => field(args),
} satisfies Meta<CraftInputColorArgs>;

export default meta;
type Story = StoryObj<CraftInputColorArgs>;

/** A hex value beside a swatch that opens the browser's colour picker. */
export const Default: Story = {};

/** Three-digit shorthand is accepted and expanded. */
export const Shorthand: Story = {
  args: {value: 'abc'},
};

/** A value that is not a colour leaves the swatch empty. */
export const Invalid: Story = {
  args: {value: 'not-a-color'},
};

export const Disabled: Story = {
  args: {disabled: true},
};

/** `presets` offers a fixed set beneath the picker. */
export const WithPresets: Story = {
  args: {presets: ['#ffffff', '000000', '#7ab55c', 'e5422b']},
};

/** Validation feedback goes in the `feedback` slot, as on any Lion field. */
export const WithError: Story = {
  args: {value: 'not-a-color'},
  render: (args) =>
    html`<craft-input-color
      .modelValue="${args.value ?? ''}"
      has-feedback-for="error"
    >
      <label slot="label">${args['label-slot']}</label>
      <div slot="feedback">
        <ul class="error-list">
          <li>Enter a valid hex color.</li>
        </ul>
      </div>
    </craft-input-color>`,
};
