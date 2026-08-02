import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html} from 'lit';
import './color-palette.js';

const meta = {
  title: 'Controls/Color Palette',
  component: 'craft-color-palette',
  args: {
    readOnly: false,
  },
  render: ({readOnly}) => html`
    <craft-color-palette
      name="palette"
      .modelValue=${[
        {color: '#e5422b', label: 'Red', default: true},
        {color: '#1f6feb', label: 'Blue', default: false},
      ]}
      ?readonly=${readOnly}
    ></craft-color-palette>
  `,
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

export const Default: Story = {};
