import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import './breadcrumbs.js';
import '../breadcrumb-item/breadcrumb-item.js';

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Breadcrumbs',
  component: 'craft-breadcrumbs',
  argTypes: {},
  render: (args) => html`
    <craft-breadcrumbs>
      <craft-breadcrumb-item href="#">Site Name</craft-breadcrumb-item>
      <craft-breadcrumb-item href="#">Entries</craft-breadcrumb-item>
      <craft-breadcrumb-item href="#">Entry Type</craft-breadcrumb-item>
      <craft-breadcrumb-item href="#">Current Entry</craft-breadcrumb-item>
    </craft-breadcrumbs>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

export const Resizable: Story = {
  render: (args) => html`
    <div class="resizable-container">
      <craft-breadcrumbs>
        <craft-breadcrumb-item href="#">Site Name</craft-breadcrumb-item>
        <craft-breadcrumb-item href="#">Entries</craft-breadcrumb-item>
        <craft-breadcrumb-item href="#">Entry Type</craft-breadcrumb-item>
        <craft-breadcrumb-item href="#">Current Entry</craft-breadcrumb-item>
      </craft-breadcrumbs>
    </div>
  `,
};
