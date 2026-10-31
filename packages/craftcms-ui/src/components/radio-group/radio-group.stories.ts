import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {unsafeSVG} from 'lit/directives/unsafe-svg.js';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import './radio-group.js';
import '../radio/radio.js';
import '../icon/icon.js';
import type CraftRadioGroup from './radio-group.js';
import {VIEW_MODES} from '../../../test/radio-tile-fixtures';

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

/**
 * `buttons` shows the options as a row of joined buttons. They're still
 * radios: the group is one tab stop, and the arrow keys move the selection.
 */
export const Buttons: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    html`<craft-radio-group buttons>
      <label slot="label">Alignment</label>
      ${['Start', 'Center', 'End'].map(
        (label) =>
          html`<craft-radio
            .choiceValue="${label.toLowerCase()}"
            ?checked="${label === 'Center'}"
          >
            <label slot="label">${label}</label>
          </craft-radio>`
      )}
    </craft-radio-group>`,
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
    html`<craft-radio-group buttons>
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

const tiles = (checked: string) =>
  VIEW_MODES.map(
    (option) =>
      html`<craft-radio
        .choiceValue="${option.value}"
        ?checked="${option.value === checked}"
      >
        <span slot="thumbnail" aria-hidden="true"
          >${unsafeSVG(option.svg)}</span
        >
        <label slot="label">${option.label}</label>
      </craft-radio>`
  );

/**
 * `thumbnails` shows the options as illustrated tiles, joined in one row. Each
 * radio slots its illustration into `thumbnail`, hidden from assistive tech,
 * since the label below it already names the option. An inline SVG drawn with
 * the illustration palette follows the theme and turns blue when selected; an
 * `<img>` with an empty `alt` works too, but keeps its own colors.
 */
export const Thumbnails: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    html`<craft-radio-group thumbnails>
      <label slot="label">View Mode</label>
      ${tiles('cards')}
    </craft-radio-group>`,
};

/**
 * When the row doesn't fit at `--c-radio-tile-min-size` per tile, the tiles
 * come apart and wrap onto as many rows as they need.
 */
export const ThumbnailsWrapped: Story = {
  parameters: {controls: {disable: true}},
  render: () =>
    html`<craft-radio-group thumbnails style="max-inline-size: 18rem">
      <label slot="label">View Mode</label>
      ${tiles('cards')}
    </craft-radio-group>`,
};
