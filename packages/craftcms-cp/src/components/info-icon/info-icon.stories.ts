import type {Meta, StoryObj} from '@storybook/web-components-vite';
import './info-icon';
import {html} from 'lit';

const meta: Meta = {
  title: 'Components/Info Icon',
  tags: ['autodocs'],
  args: {},
  render: (args) => {
    return html`<craft-info-icon>
      This is the content for the tooltip
    </craft-info-icon>`;
  },
};

export default meta;

type Story = StoryObj<any>;

export const Default: Story = {
  args: {
    label: 'More Info',
    icon: 'circle-info',
  },
};

export const Multiple: Story = {
  render: () => {
    return html`<div style="display: flex; gap: 2rem; align-items: center;">
      <craft-info-icon>Tooltip content for icon 1</craft-info-icon>
      <craft-info-icon>Tooltip content for icon 2</craft-info-icon>
      <craft-info-icon>Tooltip content for icon 3</craft-info-icon>
    </div>`;
  },
};
