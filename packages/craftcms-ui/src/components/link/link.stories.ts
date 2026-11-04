import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './link.js';
import type CraftLink from './link.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc.
 */
const {args, argTypes} = getStorybookHelpers<CraftLink>('craft-link');

type LinkArgs = CraftLink & typeof args;

const meta = {
  title: 'Components/Link',
  component: 'craft-link',
  args,
  argTypes,
  parameters: {layout: 'centered'},
  render: () => html`
    <craft-link>
      <a href="https://craftcms.com/">craftcms.com</a>
    </craft-link>
  `,
} satisfies Meta<LinkArgs>;

export default meta;
type Story = StoryObj<LinkArgs>;

/** The details button opens the full address, with buttons to open and copy it. */
export const Default: Story = {};

/** A link longer than its container is cut off with an ellipsis. */
export const Truncated: Story = {
  render: () => html`
    <div style="width: 14rem">
      <craft-link>
        <a
          href="https://www.example.com/a/very/long/path/that/keeps/going/without/any/spaces?utm_source=newsletter"
          >www.example.com/a/very/long/path/that/keeps/going/without/any/spaces?utm_source=newsletter</a
        >
      </craft-link>
    </div>
  `,
};

/** `mailto:` and `tel:` links show and copy the bare address. */
export const EmailAndPhone: Story = {
  render: () => html`
    <div style="display: grid; gap: 0.5rem">
      <craft-link>
        <a href="mailto:someone@example.com">someone@example.com</a>
      </craft-link>
      <craft-link>
        <a href="tel:+15555550123">+1 555 555 0123</a>
      </craft-link>
    </div>
  `,
};
