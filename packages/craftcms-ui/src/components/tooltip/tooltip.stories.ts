import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import {html} from 'lit';

import './tooltip.js';
import type CraftTooltip from './tooltip.js';
import '../button/button.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `tooltip.ts` surfaces it here without touching this file.
 */
const {args, argTypes} = getStorybookHelpers<CraftTooltip>('craft-tooltip');

type CraftTooltipArgs = CraftTooltip & typeof args;

const meta = {
  title: 'Components/Tooltip',
  component: 'craft-tooltip',
  args: {
    ...args,
    placement: 'top',
    content: 'This is some content within a tooltip',
  },
  argTypes,
  parameters: {
    layout: 'centered',
  },
  render: function (args) {
    let style = '';
    if (args.maxWidth) {
      style = `--max-width: ${args.maxWidth};`;
    }

    return html`
      <craft-tooltip
        placement="${args.placement}"
        style="${args.style}"
        for="my-button"
        >${args.content}</craft-tooltip
      >
      <craft-button id="my-button">Hover me</craft-button>
    `;
  },
} satisfies Meta<CraftTooltipArgs>;

export default meta;
type Story = StoryObj<CraftTooltipArgs>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Playground: Story = {
  args: {
    placement: 'top',
  },
  argTypes: {
    style: {
      control: {
        type: 'text',
      },
    },
    content: {
      control: {
        type: 'text',
      },
    },
    placement: {
      control: {
        type: 'select',
      },
      options: [
        'top-start',
        'top',
        'top-end',
        'right-start',
        'right',
        'right-end',
        'bottom-end',
        'bottom',
        'bottom-start',
        'left-end',
        'left',
        'left-start',
      ],
      defaultValue: 'top',
    },
  },
};
