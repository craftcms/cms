import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html, nothing} from 'lit';

import './indicator.js';
import type CraftIndicator from './indicator.js';
import {Variant} from '@src/constants/variants';

const appearances = ['outline-fill', 'solid', 'outline'];

/**
 * Renders the indicator in each appearance, with a label, so every story shares
 * the same markup.
 */
const renderIndicators = (
  label: string,
  attrs: {fill?: string; size?: CraftIndicator['size']} = {}
) => html`
  <tr>
    <td>${label}</td>
    ${appearances.map(
      (appearance) => html`
        <td>
          <craft-indicator
            fill="${attrs.fill ?? nothing}"
            size="${attrs.size ?? nothing}"
            appearance="${appearance}"
          ></craft-indicator>
        </td>
      `
    )}
  </tr>
`;
type ColorGroup = string;
type Volume = string;

const SWATCH_COLORS: ColorGroup[] = [
  'red',
  'orange',
  'amber',
  'yellow',
  'lime',
  'green',
  'emerald',
  'teal',
  'cyan',
  'sky',
  'blue',
  'indigo',
  'violet',
  'purple',
  'fuchsia',
  'pink',
  'rose',
  'gray',
];

const VOLUMES: Volume[] = ['quiet', 'normal', 'loud'];

const meta = {
  title: 'Components/Indicator',
  component: 'craft-indicator',
  args: {},
  render: () => html`
    <table>
      <thead>
        <td></td>
        ${appearances.map((appearance) => html`<th>${appearance}</th>`)}
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

/** All semantic variants side by side at their default (`normal`) volume. */
export const Default: Story = {
  name: 'Semantic variants',
  render: () => html`
    <div style="display:flex;gap:0.5rem;align-items:center">
      ${Object.values(Variant).map(
        (variant) =>
          html`<craft-indicator
            variant="${variant}"
            label="${variant}"
          ></craft-indicator>`
      )}
      <craft-indicator variant="empty" label="empty"></craft-indicator>
    </div>
  `,
};

/**
 * The `volume` property controls color intensity. `quiet` renders a subtle tint,
 * `normal` a mid-tone fill, and `loud` a fully saturated fill with high contrast text.
 */
export const VolumeVariants: Story = {
  name: 'Volume',
  render: () => html`
    <div style="display:flex;flex-direction:column;gap:0.75rem">
      ${VOLUMES.map(
        (volume) => html`
          <div style="display:flex;gap:0.5rem;align-items:center">
            <span
              style="width:3.5rem;font-size:0.75rem;color:var(--c-text-muted)"
              >${volume}</span
            >
            ${Object.values(Variant).map(
              (variant) => html`
                <craft-indicator
                  variant="${variant}"
                  volume="${volume}"
                  label="${variant} ${volume}"
                ></craft-indicator>
              `
            )}
          </div>
        `
      )}
    </div>
  `,
};

/**
 * Use `variant="swatch"` with the `swatch` attribute to pick any named color from the
 * design system's colorable palette. The `volume` property controls the intensity.
 */
export const SwatchVariant: Story = {
  name: 'Swatch',
  render: () => html`
    <div style="display:flex;flex-direction:column;gap:0.5rem">
      <div style="display:flex;gap:0.5rem;margin-bottom:0.25rem">
        <span style="width:5rem"></span>
        ${VOLUMES.map(
          (v) =>
            html`<span
              style="width:1.5rem;font-size:0.7rem;color:var(--c-text-muted);text-align:center"
              >${v}</span
            >`
        )}
      </div>
      ${SWATCH_COLORS.map(
        (color) => html`
          <div style="display:flex;gap:0.5rem;align-items:center">
            <span style="width:5rem;font-size:0.75rem;color:var(--c-text-muted)"
              >${color}</span
            >
            ${VOLUMES.map(
              (volume) => html`
                <craft-indicator
                  variant="swatch"
                  swatch="${color}"
                  volume="${volume}"
                  label="${color} ${volume}"
                  style="width:1.5rem"
                ></craft-indicator>
              `
            )}
          </div>
        `
      )}
    </div>
  `,
};

/**
 * Use `variant="custom"` with the `color` attribute to set the indicator to any CSS
 * `background` value. This accepts hex, rgb, hsl, named colors, and gradients —
 * useful for user-defined or data-driven colors.
 */
export const CustomVariant: Story = {
  name: 'Custom color',
  render: () => html`
    <div style="display:flex;flex-direction:column;gap:0.75rem">
      <div>
        <p
          style="font-size:0.75rem;color:var(--c-text-muted);margin-bottom:0.5rem"
        >
          Hex colors
        </p>
        <div style="display:flex;gap:0.5rem;align-items:center">
          <craft-indicator
            variant="custom"
            color="#e74c3c"
            label="red"
          ></craft-indicator>
          <craft-indicator
            variant="custom"
            color="#e67e22"
            label="orange"
          ></craft-indicator>
          <craft-indicator
            variant="custom"
            color="#f1c40f"
            label="yellow"
          ></craft-indicator>
          <craft-indicator
            variant="custom"
            color="#2ecc71"
            label="green"
          ></craft-indicator>
          <craft-indicator
            variant="custom"
            color="#3498db"
            label="blue"
          ></craft-indicator>
          <craft-indicator
            variant="custom"
            color="#9b59b6"
            label="purple"
          ></craft-indicator>
        </div>
      </div>
      <div>
        <p
          style="font-size:0.75rem;color:var(--c-text-muted);margin-bottom:0.5rem"
        >
          Gradients
        </p>
        <div style="display:flex;gap:0.5rem;align-items:center">
          <craft-indicator
            variant="custom"
            color="linear-gradient(135deg, #e74c3c, #9b59b6)"
            label="red to purple"
          ></craft-indicator>
          <craft-indicator
            variant="custom"
            color="linear-gradient(135deg, #3498db, #2ecc71)"
            label="blue to green"
          ></craft-indicator>
          <craft-indicator
            variant="custom"
            color="linear-gradient(135deg, red, orange, yellow, green, blue, indigo, violet)"
            label="rainbow"
          ></craft-indicator>
          <craft-indicator
            variant="custom"
            color="conic-gradient(red, orange, yellow, green, blue, indigo, violet, red)"
            label="conic rainbow"
          ></craft-indicator>
        </div>
      </div>
    </div>
  `,
};

/** Interactive story with Storybook controls for exploring all properties. */
export const Playground: Story = {
  name: 'Playground',
  args: {
    fill: 'violet',
    size: 'md',
  },
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
        <td></td>
        ${appearances.map((appearance) => html`<th>${appearance}</th>`)}
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
        <td></td>
        ${appearances.map((appearance) => html`<th>${appearance}</th>`)}
      </thead>
      <tbody>
        ${sizes.map((size) =>
          renderIndicators(size === 'md' ? 'md (default)' : size, {size})
        )}
      </tbody>
    </table>
  `,
};
