import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './checkbox-indeterminate.js';
import '../checkbox/checkbox.js';
import '../checkbox-group/checkbox-group.js';

const meta = {
  title: 'Controls/Checkbox Indeterminate',
  component: 'craft-checkbox-indeterminate',
  args: {
    label: 'Select All',
  },
  argTypes: {
    label: {
      control: 'text',
      description: 'Label for the indeterminate checkbox',
    },
  },
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

/**
 * The indeterminate checkbox provides a "select all" control for a group of checkboxes.
 * It shows three states: unchecked (none selected), indeterminate (some selected), and checked (all selected).
 */
export const Default: Story = {
  render: () => html`
    <craft-checkbox-group label="Notifications">
      <craft-checkbox-indeterminate label="All">
        <craft-checkbox>
          <label slot="label">Email notifications</label>
        </craft-checkbox>
        <craft-checkbox>
          <label slot="label">Push notifications</label>
        </craft-checkbox>
        <craft-checkbox>
          <label slot="label">SMS notifications</label>
        </craft-checkbox>
      </craft-checkbox-indeterminate>
    </craft-checkbox-group>
  `,
};

/**
 * When some child checkboxes are checked, the parent shows an indeterminate state.
 */
export const IndeterminateState: Story = {
  render: () => html`
    <craft-checkbox-group label="Features">
      <craft-checkbox-indeterminate label="All Features">
        <craft-checkbox checked>
          <label slot="label">Feature A (checked)</label>
        </craft-checkbox>
        <craft-checkbox>
          <label slot="label">Feature B (unchecked)</label>
        </craft-checkbox>
        <craft-checkbox checked>
          <label slot="label">Feature C (checked)</label>
        </craft-checkbox>
      </craft-checkbox-indeterminate>
    </craft-checkbox-group>
  `,
};

/**
 * When all child checkboxes are checked, the parent shows a checked state.
 */
export const AllSelected: Story = {
  render: () => html`
    <craft-checkbox-group label="Permissions">
      <craft-checkbox-indeterminate label="All Permissions">
        <craft-checkbox checked>
          <label slot="label">Read</label>
        </craft-checkbox>
        <craft-checkbox checked>
          <label slot="label">Write</label>
        </craft-checkbox>
        <craft-checkbox checked>
          <label slot="label">Delete</label>
        </craft-checkbox>
      </craft-checkbox-indeterminate>
    </craft-checkbox-group>
  `,
};

/**
 * Checkboxes can include help text for additional context.
 */
export const WithHelpText: Story = {
  render: () => html`
    <craft-checkbox-group label="Cache Options">
      <craft-checkbox-indeterminate label="All Caches">
        <craft-checkbox>
          <label slot="label">Data caches</label>
          <div slot="help-text">Clears all cached data</div>
        </craft-checkbox>
        <craft-checkbox>
          <label slot="label">Template caches</label>
          <div slot="help-text">Clears compiled templates</div>
        </craft-checkbox>
        <craft-checkbox>
          <label slot="label">Asset caches</label>
          <div slot="help-text">Clears generated thumbnails</div>
        </craft-checkbox>
      </craft-checkbox-indeterminate>
    </craft-checkbox-group>
  `,
};

/**
 * Multiple indeterminate groups can be used within the same form.
 */
export const MultipleGroups: Story = {
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 2rem;">
      <craft-checkbox-group label="Email Settings">
        <craft-checkbox-indeterminate label="All Email Types">
          <craft-checkbox>
            <label slot="label">Marketing emails</label>
          </craft-checkbox>
          <craft-checkbox>
            <label slot="label">Transactional emails</label>
          </craft-checkbox>
        </craft-checkbox-indeterminate>
      </craft-checkbox-group>

      <craft-checkbox-group label="Privacy Settings">
        <craft-checkbox-indeterminate label="All Privacy Options">
          <craft-checkbox>
            <label slot="label">Analytics tracking</label>
          </craft-checkbox>
          <craft-checkbox>
            <label slot="label">Personalization</label>
          </craft-checkbox>
        </craft-checkbox-indeterminate>
      </craft-checkbox-group>
    </div>
  `,
};
