import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './checkbox-indeterminate.js';
import '../checkbox/checkbox.js';
import '../checkbox-group/checkbox-group.js';
import type CraftCheckboxIndeterminate from './checkbox-indeterminate.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `checkbox-indeterminate.ts` surfaces it here without touching this file.
 */
const {args, argTypes} = getStorybookHelpers<CraftCheckboxIndeterminate>(
  'craft-checkbox-indeterminate'
);

type CheckboxIndeterminateArgs = CraftCheckboxIndeterminate & typeof args;

/** A group with a select-all at its head, which is the only way to use this. */
const group = (groupLabel: string, allLabel: string, children: unknown) => html`
  <craft-checkbox-group>
    <label slot="label">${groupLabel}</label>
    <craft-checkbox-indeterminate>
      <label slot="label">${allLabel}</label>
      ${children}
    </craft-checkbox-indeterminate>
  </craft-checkbox-group>
`;

const meta = {
  title: 'Form Controls/Choice Controls/Checkbox Indeterminate',
  component: 'craft-checkbox-indeterminate',
  args: {...args, 'label-slot': 'All'},
  argTypes,
  parameters: {controls: {disable: true}},
  render: (args) =>
    group(
      'Notifications',
      String(args['label-slot'] ?? 'All'),
      html`
        <craft-checkbox
          ><label slot="label">Email notifications</label></craft-checkbox
        >
        <craft-checkbox
          ><label slot="label">Push notifications</label></craft-checkbox
        >
        <craft-checkbox
          ><label slot="label">SMS notifications</label></craft-checkbox
        >
      `
    ),
} satisfies Meta<CheckboxIndeterminateArgs>;

export default meta;
type Story = StoryObj<CheckboxIndeterminateArgs>;

/**
 * A select-all for a group of checkboxes, with three states: unchecked,
 * indeterminate when some are selected, and checked when all are.
 */
export const Default: Story = {};

/** Some children checked, so the parent shows the indeterminate state. */
export const IndeterminateState: Story = {
  render: () =>
    group(
      'Features',
      'All features',
      html`
        <craft-checkbox checked
          ><label slot="label">Feature A</label></craft-checkbox
        >
        <craft-checkbox><label slot="label">Feature B</label></craft-checkbox>
        <craft-checkbox checked
          ><label slot="label">Feature C</label></craft-checkbox
        >
      `
    ),
};

/** All children checked, so the parent is checked too. */
export const AllSelected: Story = {
  render: () =>
    group(
      'Permissions',
      'All permissions',
      html`
        <craft-checkbox checked
          ><label slot="label">Read</label></craft-checkbox
        >
        <craft-checkbox checked
          ><label slot="label">Write</label></craft-checkbox
        >
        <craft-checkbox checked
          ><label slot="label">Delete</label></craft-checkbox
        >
      `
    ),
};

/** Children can carry help text of their own. */
export const WithHelpText: Story = {
  render: () =>
    group(
      'Cache options',
      'All caches',
      html`
        <craft-checkbox>
          <label slot="label">Data caches</label>
          <div slot="help-text">Clears all cached data</div>
        </craft-checkbox>
        <craft-checkbox>
          <label slot="label">Template caches</label>
          <div slot="help-text">Clears compiled templates</div>
        </craft-checkbox>
      `
    ),
};

/** More than one group can sit in the same form. */
export const MultipleGroups: Story = {
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 2rem">
      ${group(
        'Email settings',
        'All email types',
        html`
          <craft-checkbox
            ><label slot="label">Marketing emails</label></craft-checkbox
          >
          <craft-checkbox
            ><label slot="label">Transactional emails</label></craft-checkbox
          >
        `
      )}
      ${group(
        'Privacy settings',
        'All privacy options',
        html`
          <craft-checkbox
            ><label slot="label">Analytics tracking</label></craft-checkbox
          >
          <craft-checkbox
            ><label slot="label">Personalization</label></craft-checkbox
          >
        `
      )}
    </div>
  `,
};
