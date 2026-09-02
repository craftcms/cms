import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './select-color.js';
import type CraftSelectColor from './select-color.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `select-color.ts` surfaces it here without touching this file.
 *
 * The template is written out rather than generated: the value is bound as the
 * `modelValue` property so an empty selection can be `null`, which an attribute
 * cannot express.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftSelectColor>('craft-select-color');

type SelectColorArgs = CraftSelectColor & typeof args;

const meta = {
  title: 'Form Controls/Select Controls/Select Color',
  component: 'craft-select-color',
  args: {...args, label: 'Color', name: 'color', 'model-value': 'red'},
  argTypes,
  render: (args) => html`
    <craft-select-color
      label="${args.label ?? ''}"
      name="${args.name ?? 'color'}"
      ?allow-transparent="${args['allow-transparent']}"
      .modelValue="${args['model-value'] ?? null}"
    ></craft-select-color>
  `,
} satisfies Meta<SelectColorArgs>;

export default meta;
type Story = StoryObj<SelectColorArgs>;

/** A colour is selected on load, so the invoker shows its swatch. */
export const Default: Story = {};

/** Nothing selected — the invoker shows the placeholder with no swatch. */
export const Empty: Story = {
  args: {'model-value': null},
};

/** Preselected blue, so the blue swatch appears in the invoker. */
export const Preselected: Story = {
  args: {'model-value': 'blue'},
};

/**
 * `allow-transparent` adds a "transparent" option, whose swatch is the
 * chequerboard.
 */
export const AllowTransparent: Story = {
  args: {'allow-transparent': true, 'model-value': '__blank__'},
};

/** The same option offered but nothing selected yet. */
export const AllowTransparentEmpty: Story = {
  args: {'allow-transparent': true, 'model-value': null},
};
