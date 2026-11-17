import type {Meta, StoryObj} from '@storybook/web-components-vite';
import {html, nothing} from 'lit';
import {ifDefined} from 'lit/directives/if-defined.js';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import './slide-picker-button.js';
import {withAccessibilityTree} from '../../../test/slide-picker-button-accessibility-tree';

const {
  events,
  args: helperArgs,
  argTypes: helperArgTypes,
} = getStorybookHelpers('craft-slide-picker-button');

// The helpers add a property arg for each attribute with a different name,
// such as `valueUnit` for `value-unit`, which would show each one twice in
// the controls. Leave them out.
const kebab = (name: string) =>
  name.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`);
const isDuplicate = (name: string) =>
  kebab(name) !== name && kebab(name) in helperArgs;
const args = Object.fromEntries(
  Object.entries(helperArgs).filter(([name]) => !isDuplicate(name))
) as typeof helperArgs;
// Storybook also adds controls from the manifest, so hide them rather than
// leaving them out.
const argTypes = Object.fromEntries(
  Object.entries(helperArgTypes).map(([name, argType]) => [
    name,
    isDuplicate(name) ? {...argType, table: {disable: true}} : argType,
  ])
) as typeof helperArgTypes;

const columns = (value: number) =>
  `${value} ${value === 1 ? 'column' : 'columns'}`;

/** Args only the stories use, which aren't passed to the button. */
interface StoryArgs {
  /** More items, each with a button set like the first. */
  otherItems: string;
}

type Args = typeof args & StoryArgs;

const meta: Meta<Args> = {
  title: 'Form Controls/Slide Picker Button',
  component: 'craft-slide-picker-button',
  args: {...args, otherItems: ''},
  argTypes: {
    ...argTypes,
    // A function can't be typed into a control, so pick one by name.
    valueLabel: {
      ...argTypes.valueLabel,
      control: 'select',
      options: ['None', '1 column / 2 columns'],
      mapping: {None: undefined, '1 column / 2 columns': columns},
    },
    otherItems: {
      name: 'Other items',
      description:
        'Names of more items, separated by commas. Each gets a button set like the first, with its name as `item-label`.',
      control: 'text',
      table: {category: 'Story'},
    },
  },
  render: (storyArgs) => items(storyArgs),
  parameters: {
    actions: {
      handles: events,
    },
  },
};

export default meta;
type Story = StoryObj<Args>;

/**
 * Shows each item's name beside its button, as a field layout or Widget
 * Settings does, then each button's name, role and state. The output is
 * hidden from screen readers, which hear the buttons themselves.
 *
 * Each button is rendered here rather than with the helpers' `template()`,
 * which uses Storybook hooks and so can't be called once per item.
 */
function items(storyArgs: Args) {
  const {otherItems, valueLabel, ...buttonArgs} = storyArgs as Args &
    Record<string, unknown>;
  const names = [
    buttonArgs['item-label'],
    ...otherItems
      .split(',')
      .map((name) => name.trim())
      .filter(Boolean),
  ];

  return withAccessibilityTree(html`
    <ul
      style="display: grid; gap: var(--c-spacing-sm); margin: 0; padding: 0; list-style: none;"
    >
      ${names.map(
        (name) => html`
          <li
            style="display: flex; align-items: center; gap: var(--c-spacing-sm);"
          >
            <span style="flex: 1;">${name}</span>
            <craft-slide-picker-button
              slider-label=${ifDefined(buttonArgs['slider-label'] as string)}
              item-label=${name}
              value-unit=${ifDefined(buttonArgs['value-unit'] as string)}
              name=${ifDefined((buttonArgs.name as string) || undefined)}
              flush=${ifDefined(buttonArgs.flush as string)}
              min=${ifDefined(buttonArgs.min as number)}
              max=${ifDefined(buttonArgs.max as number)}
              step=${ifDefined(buttonArgs.step as number)}
              value=${ifDefined(buttonArgs.value as number)}
              ?readonly=${Boolean(buttonArgs.readonly)}
              ?disabled=${Boolean(buttonArgs.disabled)}
              .valueLabel=${valueLabel ?? nothing}
            ></craft-slide-picker-button>
          </li>
        `
      )}
    </ul>
  `);
}

/**
 * A field's width in a field layout. The tooltip shows the value, "50% width",
 * and `item-label` adds the field's name beside it, so the button is named
 * "50% width Body". `slider-label`, "Width", shows only in the popover, below
 * the slider.
 */
export const Default: Story = {
  args: {
    'slider-label': 'Width',
    'item-label': 'Body',
    'value-unit': '% width',
    min: 25,
    max: 100,
    step: 25,
    value: 50,
  },
};

/**
 * Two fields at the same width show the same tooltip, "50% width".
 * `item-label` tells their buttons apart: "50% width Body" and "50% width
 * Summary".
 */
export const ItemLabel: Story = {
  args: {
    ...Default.args,
    otherItems: 'Summary',
  },
};

/**
 * A widget's column count in Widget Settings. `valueLabel` formats the value
 * as "1 column" or "2 columns", which a `value-unit` can't.
 */
export const ValueLabel: Story = {
  args: {
    'slider-label': 'Columns',
    'item-label': 'Recent Entries',
    otherItems: 'Updates',
    valueLabel: '1 column / 2 columns' as unknown as Args['valueLabel'],
    min: 1,
    max: 4,
    step: 1,
    value: 1,
  },
};

/** A field's width in a read-only field layout. */
export const ReadOnly: Story = {
  args: {
    ...Default.args,
    value: 75,
    readonly: true,
  },
};
