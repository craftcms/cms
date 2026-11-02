import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {unsafeSVG} from 'lit/directives/unsafe-svg.js';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './radio-group.js';
import '../radio/radio.js';
import '../icon/icon.js';
import type CraftRadioGroup from './radio-group.js';
import {
  PALETTE_SAMPLE,
  VIEW_MODES,
  type IllustrationRole,
} from '../../../test/radio-card-fixtures';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `radio-group.ts` surfaces it here without touching this file.
 *
 * Lion's own `label` and `help-text` are slots here, so they are set through
 * the `label-slot` and `help-text-slot` args rather than as attributes.
 */
const {args, argTypes} =
  getStorybookHelpers<CraftRadioGroup>('craft-radio-group');

type RadioGroupArgs = CraftRadioGroup & typeof args;

const meta = {
  title: 'Form Controls/Choice Controls/Radio Group',
  component: 'craft-radio-group',
  args: {...args, name: 'scientist', 'label-slot': 'Scientists'},
  argTypes,
  // The options are written out rather than generated: each one carries a
  // `choiceValue`, which is a property rather than an attribute, so it cannot
  // be expressed in a slot's markup string.
  render: (args) => html`
    <craft-radio-group name="${args.name ?? ''}" ?disabled="${args.disabled}">
      <label slot="label">${args['label-slot']}</label>
      <craft-radio .choiceValue="${'curie'}">
        <label slot="label">Marie Curie</label>
      </craft-radio>
      <craft-radio .choiceValue="${'lovelace'}" checked>
        <label slot="label">Ada Lovelace</label>
      </craft-radio>
      <craft-radio .choiceValue="${'hopper'}">
        <label slot="label">Grace Hopper</label>
      </craft-radio>
    </craft-radio-group>
  `,
} satisfies Meta<RadioGroupArgs>;

export default meta;
type Story = StoryObj<RadioGroupArgs>;

/** Exactly one option may be selected, and the group owns the value. */
export const Default: Story = {};

/**
 * A group rendered by the server adopts the name already on its inputs, so the
 * markup keeps posting the way it did before the component upgraded.
 */
export const ServerRendered: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    html`<craft-radio-group>
      <label slot="label">Color</label>
      <div>
        <craft-radio>
          <input
            slot="input"
            type="radio"
            id="color-red"
            name="color"
            value="red"
          />
          <label slot="label" for="color-red">Red</label>
        </craft-radio>
      </div>
      <div>
        <craft-radio>
          <input
            slot="input"
            type="radio"
            id="color-green"
            name="color"
            value="green"
            checked
          />
          <label slot="label" for="color-green">Green</label>
        </craft-radio>
      </div>
      <div>
        <craft-radio>
          <input
            slot="input"
            type="radio"
            id="color-blue"
            name="color"
            value="blue"
          />
          <label slot="label" for="color-blue">Blue</label>
        </craft-radio>
      </div>
    </craft-radio-group>`,
};

const alignments = () =>
  ['Start', 'Center', 'End'].map(
    (label) =>
      html`<craft-radio
        .choiceValue="${label.toLowerCase()}"
        ?checked="${label === 'Center'}"
      >
        <label slot="label">${label}</label>
      </craft-radio>`
  );

const withIcons = (checked: string) =>
  VIEW_MODES.map(
    (option) =>
      html`<craft-radio
        .choiceValue="${option.value}"
        ?checked="${option.value === checked}"
      >
        <span slot="icon" aria-hidden="true">${unsafeSVG(option.svg)}</span>
        <label slot="label">${option.label}</label>
      </craft-radio>`
  );

/**
 * `appearance` draws the options as buttons or cards, separate or joined,
 * rather than a stacked list. They're still radios: the group is one tab stop,
 * and the arrow keys move the selection.
 *
 * - `buttons`: separate buttons, wrapping onto more rows as needed.
 * - `button-group`: buttons joined in one row, like a `craft-button-group`.
 * - `cards`: separate cards, each radio's `icon` drawn large above its label.
 * - `card-group`: cards joined in one row, which come apart when the row
 *   doesn't fit.
 */
export const Appearance: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    html`<div style="display: grid; gap: var(--c-spacing-xl, 1.5rem)">
      <craft-radio-group appearance="buttons" name="alignment-buttons">
        <label slot="label"><code>buttons</code></label>
        ${alignments()}
      </craft-radio-group>
      <craft-radio-group
        appearance="button-group"
        name="alignment-button-group"
      >
        <label slot="label"><code>button-group</code></label>
        ${alignments()}
      </craft-radio-group>
      <craft-radio-group appearance="cards" name="view-mode-cards">
        <label slot="label"><code>cards</code></label>
        ${withIcons('cards')}
      </craft-radio-group>
      <craft-radio-group appearance="card-group" name="view-mode-card-group">
        <label slot="label"><code>card-group</code></label>
        ${withIcons('cards')}
      </craft-radio-group>
    </div>`,
};

/**
 * An option labeled only by an icon gets a square button. The icon's `label`
 * names the radio.
 */
export const IconButtons: Story = {
  parameters: {
    controls: {disable: true},
    a11y: {
      config: {
        // The best-practice rule only counts visible text as a label, so it
        // doesn't see the icons' names, and Lion always gives each input an
        // `aria-describedby` for its help text, which the rule then flags.
        rules: [{id: 'label-title-only', enabled: false}],
      },
    },
  },
  render: () =>
    html`<craft-radio-group appearance="button-group" name="view">
      <label slot="label">View</label>
      ${[
        ['list', 'list-unordered', 'List'],
        ['table', 'table', 'Table'],
        ['thumbs', 'grid', 'Thumbnails'],
      ].map(
        ([value, icon, label]) =>
          html`<craft-radio
            .choiceValue="${value}"
            ?checked="${value === 'list'}"
          >
            <label slot="label"
              ><craft-icon name="${icon}" label="${label}"></craft-icon
            ></label>
          </craft-radio>`
      )}
    </craft-radio-group>`,
};

/**
 * A radio's `icon` is shown before its label, hidden from assistive tech,
 * since the label already names the option. An inline SVG drawn with the
 * illustration palette follows the theme; an `<img>` with an empty `alt` works
 * too, but keeps its own colors.
 */
export const Icons: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    html`<craft-radio-group name="view-mode">
      <label slot="label">View Mode</label>
      ${withIcons('cards')}
    </craft-radio-group>`,
};

/**
 * When a card group's row doesn't fit at `--c-radio-card-min-size` per card,
 * the cards come apart and wrap onto as many rows as they need.
 */
export const CardGroupWrapped: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    html`<craft-radio-group
      appearance="card-group"
      name="view-mode-wrapped"
      style="max-inline-size: 18rem"
    >
      <label slot="label">View Mode</label>
      ${withIcons('cards')}
    </craft-radio-group>`,
};

interface PaletteRole {
  token: IllustrationRole;
  role: string;
  /** Whether the role is used as a `fill`, a `stroke`, or both. */
  fill: boolean;
  stroke: boolean;
  /** Outlines the role's shapes in blue, since they're hard to spot. */
  callout?: boolean;
  /** Fills left unfaded, since the role only shows up on them. */
  backdrop?: IllustrationRole[];
}

const PALETTE_ROLES: PaletteRole[] = [
  {
    token: 'surface',
    role: 'Shapes such as chips and cards',
    fill: true,
    stroke: false,
    callout: true,
  },
  {
    token: 'paper',
    role: 'The lightest color, also used for a mark’s light edge',
    fill: true,
    stroke: true,
    callout: true,
    backdrop: ['surface'],
  },
  {
    token: 'subtle',
    role: 'Decoration that carries no meaning, such as placeholder text',
    fill: true,
    stroke: false,
    callout: true,
  },
  {
    token: 'field',
    role: 'An area that figures sit on, such as a photo’s background',
    fill: true,
    stroke: false,
    callout: true,
  },
  {
    token: 'figure',
    role: 'Shapes on a field, such as a photo’s mountains',
    fill: true,
    stroke: false,
    backdrop: ['field'],
  },
  {
    token: 'line',
    role: 'Outlines',
    fill: false,
    stroke: true,
  },
  {
    token: 'mark',
    role: 'The strongest marks, such as a crop frame or letterbox bars',
    fill: true,
    stroke: true,
  },
];

/** How a role is applied, as it's written in an illustration. */
const usage = ({token, fill, stroke}: PaletteRole) =>
  [fill && 'fill', stroke && 'stroke']
    .filter(Boolean)
    .map((property) => `${property}: var(--c-illustration-${token})`);

/**
 * Fades every part of the sample except the ones a role draws (and what it
 * sits on, if it only shows up there), and outlines a role's shapes in blue
 * when they're hard to spot.
 */
const fadeOthers = ({
  token,
  fill,
  stroke,
  callout,
  backdrop = [],
}: PaletteRole) => {
  const uses = (property: string, tokens: IllustrationRole[]) =>
    tokens
      .map((name) => `[style*='${property}: var(--c-illustration-${name})']`)
      .join(', ');
  const except = (property: string, tokens: IllustrationRole[]) =>
    tokens.length ? `:not(${uses(property, tokens)})` : '';
  const scope = `[data-palette-role='${token}'] [slot='icon']`;
  const fills = [...(fill ? [token] : []), ...backdrop];
  const strokes = stroke ? [token] : [];

  return `
    ${scope} [style*='fill:']${except('fill', fills)} {
      fill-opacity: 0.12;
    }
    ${scope} [style*='stroke:']${except('stroke', strokes)} {
      stroke-opacity: 0.12;
    }
    ${
      callout
        ? `${scope} :is(${uses('fill', [token])}) {
            stroke: var(--color-blue-500);
            /* Outranks the fading above, on a shape with another role's outline. */
            stroke-opacity: 1 !important;
            stroke-width: 1.5px;
            stroke-dasharray: 2 1.5;
          }`
        : ''
    }`;
};

/**
 * The illustration palette, one role per row. Each example is a real card,
 * drawn with the palette's colors in the current theme, with every part but
 * that role's faded.
 */
export const IllustrationPalette: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    html`<style>
        ${PALETTE_ROLES.map(fadeOthers).join('')} .illustration-palette {
          inline-size: auto;
          border-collapse: collapse;
          text-align: start;
        }
        .illustration-palette :is(th, td) {
          padding: var(--c-spacing-sm) var(--c-spacing-md);
          border-block-end: 1px solid var(--c-color-neutral-border-quiet);
          text-align: start;
          vertical-align: middle;
          background: none;
        }
        .illustration-palette code {
          display: block;
          white-space: nowrap;
        }
      </style>
      <table class="illustration-palette">
        <thead>
          <tr>
            <th scope="col">Example</th>
            <th scope="col">Token</th>
            <th scope="col">Use as</th>
            <th scope="col">Role</th>
          </tr>
        </thead>
        <tbody>
          ${PALETTE_ROLES.map(
            (role) =>
              html`<tr>
                <td>
                  <div inert data-palette-role="${role.token}">
                    <craft-radio-group
                      appearance="card-group"
                      name="palette-${role.token}"
                      style="--c-radio-card-icon-size: 7.5rem; --c-radio-card-min-size: 12rem; --c-radio-card-max-size: 12rem"
                    >
                      <craft-radio .choiceValue="${role.token}">
                        <span slot="icon" aria-hidden="true"
                          >${unsafeSVG(PALETTE_SAMPLE.svg)}</span
                        >
                      </craft-radio>
                    </craft-radio-group>
                  </div>
                </td>
                <th scope="row">
                  <code>--c-illustration-${role.token}</code>
                </th>
                <td>${usage(role).map((use) => html`<code>${use}</code>`)}</td>
                <td>${role.role}</td>
              </tr>`
          )}
        </tbody>
      </table>`,
};
