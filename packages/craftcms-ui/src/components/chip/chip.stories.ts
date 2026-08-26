import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {Color} from '../../constants/colors';

import './chip.js';
import '../status/status.js';
import '../button/button.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Chip',
  component: 'craft-chip',
  argTypes: {},
  render: (args) => html` <craft-chip> This is a chip </craft-chip> `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const PrefixAndSuffix: Story = {
  args: {},
  render: (args) => html`
    <craft-chip>
      <craft-status slot="prefix" status="live"></craft-status>
      This is a chip
      <craft-button icon size="small" variant="plain" slot="suffix">
        <craft-icon name="ellipsis" label="Actions"></craft-icon>
      </craft-button>
    </craft-chip>
  `,
};

export const Thumbnail: Story = {
  args: {},
  render: (args) => html`
    <craft-chip>
      <img slot="thumbnail" src="https://picsum.photos/300/300" alt="" />
      This is a chip
      <craft-button icon size="small" variant="plain" slot="suffix">
        <craft-icon name="ellipsis" label="Actions"></craft-icon>
      </craft-button>
    </craft-chip>
  `,
};

export const PrefixOnly: Story = {
  args: {},
  render: (args) => html`
    <craft-chip>
      <craft-status slot="prefix" status="live"></craft-status>
      This is a chip
    </craft-chip>
  `,
};

export const SuffixOnly: Story = {
  args: {},
  render: (args) => html`
    <craft-chip>
      <craft-button icon size="small" variant="plain" slot="suffix">
        <craft-icon name="ellipsis" label="Actions"></craft-icon>
      </craft-button>
      This is a chip
    </craft-chip>
  `,
};

/**
 * `selectable` adds the selection checkbox; `selected` styles the chip to match
 * a selected thumbnail tile in the element index, so a selection reads the same
 * whichever view mode the elements are shown in.
 */
export const Selectable: Story = {
  args: {},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.5rem; align-items: start;">
      <craft-chip selectable select-label="Select Homepage">Homepage</craft-chip>
      <craft-chip selectable selected select-label="Select About us">About us</craft-chip>
    </div>
  `,
};

export const Colors: Story = {
  render: () => html`
    <div class="stage">
      <div
        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem"
      >
        ${Object.entries(Color).map(
          ([name, value]) =>
            html`<craft-chip data-color="${value}">
              ${name}
              <craft-button size="small" slot="suffix" inherit variant="plain"
                >Button</craft-button
              >
            </craft-chip>`
        )}
      </div>
    </div>
  `,
};
