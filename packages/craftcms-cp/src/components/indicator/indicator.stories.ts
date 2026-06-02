import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html, nothing} from 'lit';

import './indicator.js';
import type CraftIndicator from './indicator.js';
import {Variant} from '@src/types';

/**
 * Renders a `solid` and an `empty` indicator side by side, with a label,
 * so every story shares the same markup.
 */
const renderIndicators = (
  label: string,
  attrs: {fill?: string; size?: CraftIndicator['size']} = {}
) => html`
  <tr>
    <td>${label}</td>
    <td>
      <craft-indicator
        fill="${attrs.fill ?? nothing}"
        size="${attrs.size ?? nothing}"
      ></craft-indicator>
    </td>
    <td>
      <craft-indicator
        appearance="empty"
        fill="${attrs.fill ?? nothing}"
        size="${attrs.size ?? nothing}"
      ></craft-indicator>
    </td>
  </tr>
`;

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
const meta = {
  title: 'Components/Indicator',
  component: 'craft-indicator',
  args: {},
  render: () => html`
    <table>
      <thead>
        <th></th>
        <th>Solid</th>
        <th>Empty</th>
      </thead>
      <tbody>
        ${Object.values(Variant).map((variant) =>
          renderIndicators(variant, {fill: variant})
        )}
      </tbody>
    </table>
  `,
} satisfies Meta<CraftIndicator>;

export default meta;
type Story = StoryObj<CraftIndicator>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args
export const Default: Story = {
  args: {},
};

const arbitraryValues = [
  '#2c61de',
  'rgba(30, 40, 40, 0.2)',
  'linear-gradient(red, blue)',
];

export const ArbitraryColor: Story = {
  render: () => html`
    <table>
      <thead>
        <th></th>
        <th>Solid</th>
        <th>Empty</th>
      </thead>
      <tbody>
        ${arbitraryValues.map((value) =>
          renderIndicators(value, {fill: value})
        )}
      </tbody>
    </table>
  `,
};

const sizes: Array<CraftIndicator['size']> = ['md', 'lg'];

export const ArbitrarySize: Story = {
  render: () => html`
    <table>
      <thead>
        <th></th>
        <th>Solid</th>
        <th>Empty</th>
      </thead>
      <tbody>
        ${sizes.map((size) =>
          renderIndicators(size === 'md' ? 'md (default)' : size, {size})
        )}
      </tbody>
    </table>
  `,
};
