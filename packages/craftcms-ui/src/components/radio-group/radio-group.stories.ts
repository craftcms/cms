import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './radio-group.js';
import '../radio/radio.js';
import type CraftRadioGroup from './radio-group.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `radio-group.ts` surfaces it here without touching this file.
 *
 * Lion's own `label` and `help-text` are slots here, so they are set through
 * the `label-slot` and `help-text-slot` args rather than as attributes.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftRadioGroup>('craft-radio-group');

type RadioGroupArgs = CraftRadioGroup & typeof args;

const meta = {
  title: 'Form Controls/Choice Controls/Radio Group',
  component: 'craft-radio-group',
  args: {...args, name: 'scientist', 'label-slot': 'Scientists'},
  argTypes,
  // The options are written out rather than generated: each one carries a
  // `choiceValue`, which is a property rather than an attribute, so it cannot
  // be expressed in a slot's markup string.
  render: (args) => html`
    <craft-radio-group name="${args.name ?? ''}" ?disabled="${args.disabled}">
      <label slot="label">${args['label-slot']}</label>
      <craft-radio .choiceValue="${'curie'}">
        <label slot="label">Marie Curie</label>
      </craft-radio>
      <craft-radio .choiceValue="${'lovelace'}" checked>
        <label slot="label">Ada Lovelace</label>
      </craft-radio>
      <craft-radio .choiceValue="${'hopper'}">
        <label slot="label">Grace Hopper</label>
      </craft-radio>
    </craft-radio-group>
  `,
} satisfies Meta<RadioGroupArgs>;

export default meta;
type Story = StoryObj<RadioGroupArgs>;

/** Exactly one option may be selected, and the group owns the value. */
export const Default: Story = {};

/**
 * A group rendered by the server adopts the name already on its inputs, so the
 * markup keeps posting the way it did before the component upgraded.
 */
export const ServerRendered: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    html`<craft-radio-group>
      <label slot="label">Color</label>
      <div>
        <craft-radio>
          <input
            slot="input"
            type="radio"
            id="color-red"
            name="color"
            value="red"
          />
          <label slot="label" for="color-red">Red</label>
        </craft-radio>
      </div>
      <div>
        <craft-radio>
          <input
            slot="input"
            type="radio"
            id="color-green"
            name="color"
            value="green"
            checked
          />
          <label slot="label" for="color-green">Green</label>
        </craft-radio>
      </div>
      <div>
        <craft-radio>
          <input
            slot="input"
            type="radio"
            id="color-blue"
            name="color"
            value="blue"
          />
          <label slot="label" for="color-blue">Blue</label>
        </craft-radio>
      </div>
    </craft-radio-group>`,
};
