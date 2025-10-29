import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './avatar.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Avatar',
  component: 'craft-avatar',
  argTypes: {},
  render: (args) => html` <craft-avatar label="bhadmin"></craft-avatar> `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
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
