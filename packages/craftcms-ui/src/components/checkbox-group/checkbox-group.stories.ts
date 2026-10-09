import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './checkbox-group.js';
import '../checkbox/checkbox.js';
import type CraftCheckboxGroup from './checkbox-group.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `checkbox-group.ts` surfaces it here without touching this file.
 *
 * Lion's own `label` and `help-text` are slots here, so they are set through
 * the `label-slot` and `help-text-slot` args rather than as attributes.
 */
const {args, argTypes} = getStorybookHelpers<CraftCheckboxGroup>(
  'craft-checkbox-group'
);

type CheckboxGroupArgs = CraftCheckboxGroup & typeof args;

const meta = {
  title: 'Form Controls/Choice Controls/Checkbox Group',
  component: 'craft-checkbox-group',
  args: {...args, name: 'scientists[]', 'label-slot': 'Scientists'},
  argTypes,
  // The options are written out rather than generated: each one carries a
  // `choiceValue`, which is a property rather than an attribute, so it cannot
  // be expressed in a slot's markup string.
  render: (args) => html`
    <craft-checkbox-group
      name="${args.name ?? ''}"
      ?disabled="${args.disabled}"
    >
      <label slot="label">${args['label-slot']}</label>
      <craft-checkbox .choiceValue="${'curie'}">
        <label slot="label">Marie Curie</label>
      </craft-checkbox>
      <craft-checkbox .choiceValue="${'lovelace'}" checked>
        <label slot="label">Ada Lovelace</label>
      </craft-checkbox>
      <craft-checkbox .choiceValue="${'hopper'}">
        <label slot="label">Grace Hopper</label>
      </craft-checkbox>
    </craft-checkbox-group>
  `,
} satisfies Meta<CheckboxGroupArgs>;

export default meta;
type Story = StoryObj<CheckboxGroupArgs>;

/** The group owns the name; its value is the array of what is checked. */
export const Default: Story = {};

/**
 * A group rendered by the server adopts the name already on its inputs, so the
 * markup keeps posting the way it did before the component upgraded.
 */
export const ServerRendered: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    html`<craft-checkbox-group>
      <label slot="label">Allowed kinds</label>
      <div>
        <craft-checkbox>
          <input
            slot="input"
            type="checkbox"
            id="kind-image"
            name="allowedKinds[]"
            value="image"
            checked
          />
          <label slot="label" for="kind-image">Image</label>
        </craft-checkbox>
      </div>
      <div>
        <craft-checkbox>
          <input
            slot="input"
            type="checkbox"
            id="kind-video"
            name="allowedKinds[]"
            value="video"
          />
          <label slot="label" for="kind-video">Video</label>
        </craft-checkbox>
      </div>
    </craft-checkbox-group>`,
};
