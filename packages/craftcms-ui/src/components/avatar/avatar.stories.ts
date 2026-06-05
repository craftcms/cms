import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftAvatar from './avatar.js';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
const {events, args, argTypes, template} = getStorybookHelpers('craft-avatar');
import './avatar.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta: Meta<CraftAvatar> = {
  title: 'Components/Avatar',
  component: 'craft-avatar',
  args,
  argTypes,
  render: (args) => template(args),
  parameters: {
    actions: {
      handles: events,
    },
  },
};

export default meta;
type Story = StoryObj<CraftAvatar & typeof args>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
  parameters: {
    a11y: {
      config: {
        rules: [
          {
            id: 'svg-img-alt', // TODO: figure out best way of handling alt text for avatars
            enabled: false,
          },
        ],
      },
    },
  },
};

export const CustomColors: Story = {
  args: {
    colorStart: 'green',
    colorEnd: 'black',
  },
  render: (args) => html`
    <craft-avatar
      style="--color-start: ${args.colorStart}; --color-end: ${args.colorEnd}"
      label="Brian Hanson"
    ></craft-avatar>
  `,
};

export const CustomSize: Story = {
  args: {},
  render: (args) => html`
    <craft-avatar label="Brian Hanson">BH</craft-avatar>
    <craft-avatar style="--size: 60px;" label="M">MD</craft-avatar>
    <craft-avatar style="--size: 100px;" label="LG">LG</craft-avatar>
  `,
};
