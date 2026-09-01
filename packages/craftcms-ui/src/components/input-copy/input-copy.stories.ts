import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './input-copy.js';

const meta = {
  title: 'Controls/Input Copy',
  component: 'craft-input-copy',
  render: () => html`
    <craft-input-copy
      label="Site URL"
      help-text="Click the copy button to copy this value."
      value="https://craftcms.com"
    ></craft-input-copy>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {
  args: {},
};

/**
 * Use \`copy-value\` when the value sent to the clipboard should differ from
 * what is displayed in the textbox — for example, showing a truncated token
 * while copying the full one.
 */
export const WithSeparateCopyValue: Story = {
  render: () => html`
    <craft-input-copy
      label="API Token"
      value="sk-••••••••••••••••••••••••1234"
      copy-value="sk-abcdefghijklmnopqrstuvwxyz1234"
    ></craft-input-copy>
  `,
};

export const Disabled: Story = {
  render: () => html`
    <craft-input-copy
      label="Site URL"
      value="https://craftcms.com"
      disabled
    ></craft-input-copy>
  `,
};
