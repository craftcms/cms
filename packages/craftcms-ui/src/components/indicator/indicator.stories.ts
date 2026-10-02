import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './indicator.js';
import type CraftIndicator from './indicator.js';
import {Variant} from '@src/constants/variants';
import {colors} from '@src/constants/colors';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `indicator.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftIndicator>('craft-indicator');

type IndicatorArgs = CraftIndicator & typeof args;

const APPEARANCES = ['solid', 'outline-fill', 'outline'] as const;

/** One row of the appearance matrix, so every grid story shares its markup. */
const row = (label: string, attrs: {fill?: string; size?: string}) => html`
  <tr>
    <td style="padding-inline-end: 1rem; font-size: 0.8rem">${label}</td>
    ${APPEARANCES.map(
      (appearance) => html`
        <td style="padding: 0.25rem 0.75rem">
          <craft-indicator
            fill="${attrs.fill ?? 'var(--c-color-fill-loud)'}"
            size="${attrs.size ?? 'md'}"
            appearance="${appearance}"
            label="${label} ${appearance}"
          ></craft-indicator>
        </td>
      `
    )}
  </tr>
`;

const matrix = (rows: unknown) => html`
  <table>
    <thead>
      <tr>
        <td></td>
        ${APPEARANCES.map(
          (appearance) =>
            html`<th style="font-size: 0.75rem; font-weight: 500">
              ${appearance}
            </th>`
        )}
      </tr>
    </thead>
    <tbody>
      ${rows}
    </tbody>
  </table>
`;

const meta = {
  title: 'Components/Indicator',
  component: 'craft-indicator',
  args: {...args, label: 'Live'},
  argTypes,
  // Render from args alone so every control drives the story. The grid stories
  // below vary the markup instead, and disable the controls that no longer
  // reach them.
  render: (args) => template(args),
} satisfies Meta<IndicatorArgs>;

export default meta;
type Story = StoryObj<IndicatorArgs>;

/** A single dot, driven by the controls. */
export const Default: Story = {};

/**
 * `fill` takes a status variant, which resolves to that variant's fill token.
 */
export const Variants: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    matrix(
      Object.values(Variant).map((variant) => row(variant, {fill: variant}))
    ),
};

/** `fill` also takes any palette swatch by name. */
export const Swatches: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    matrix(colors.slice(0, 8).map((color) => row(color, {fill: color}))),
};

/**
 * Anything the browser accepts as a colour works too — a hex value, an `rgba()`,
 * even a gradient. Prefer the variant and swatch names, which stay in step with
 * the rest of the palette.
 */
export const ArbitraryColor: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    matrix(
      ['#2c61de', 'rgba(30, 40, 40, 0.2)', 'linear-gradient(red, blue)'].map(
        (value) => row(value, {fill: value})
      )
    ),
};

/** `size` steps the dot between the two sizes it ships with. */
export const ArbitrarySize: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    matrix(
      (['md', 'lg'] as const).map((size) =>
        row(size === 'md' ? 'md (default)' : size, {size})
      )
    ),
};
