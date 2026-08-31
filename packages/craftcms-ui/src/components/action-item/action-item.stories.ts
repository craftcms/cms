import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import '../icon/icon.js';
import '../button/button.js';
import './action-item.js';
import type CraftActionItem from './action-item.js';
import {Color} from '@src/constants/colors';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `action-item.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftActionItem>('craft-action-item');

type ActionItemArgs = CraftActionItem & typeof args;

/** Action items are sized by their menu, so the stories supply one. */
const stage = (story: () => unknown) => html`
  <style>
    .stage {
      padding: var(--c-spacing-sm);
      border-radius: var(--c-radius-sm);
      width: calc(320rem / 16);
      background-color: var(--c-surface-raised);
      box-shadow: var(--c-shadow-raised);
    }
  </style>
  <div class="stage">${story()}</div>
`;

const meta = {
  title: 'Components/Action Item',
  component: 'craft-action-item',
  args: {...args, 'default-slot': 'View in a new tab'},
  argTypes,
  parameters: {layout: 'centered'},
  decorators: [stage],
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<ActionItemArgs>;

export default meta;
type Story = StoryObj<ActionItemArgs>;

/** A plain item is a button carrying nothing but its label. */
export const Default: Story = {};

/** `icon` renders leading artwork before the label. */
export const WithIcon: Story = {
  args: {icon: 'up-right-from-square'},
};

/** `active` marks the entry a menu opens onto, or the option already in effect. */
export const Active: Story = {
  args: {active: true, icon: 'up-right-from-square'},
};

/** `disabled` dims the item and stops it being activated. */
export const Disabled: Story = {
  args: {disabled: true, icon: 'up-right-from-square'},
};

/** Setting `href` renders the item as a link rather than a button. */
export const Link: Story = {
  args: {href: 'https://craftcms.com', 'default-slot': 'Open craftcms.com'},
};

/** `variant` colors the item — `danger` for a destructive entry. */
export const Variants: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-action-item icon="pen">Edit</craft-action-item>
    <craft-action-item icon="trash" variant="danger">Delete</craft-action-item>
  `,
};

/**
 * The `suffix` slot holds trailing content, before any shortcut.
 *
 * Note that an interactive suffix nests a control inside the item's own
 * button, which axe flags as `nested-interactive`. Prefer static content here
 * until the item renders its suffix outside the button.
 */
export const WithSuffix: Story = {
  // The violation is the component's structure, not this story's markup.
  parameters: {a11y: {test: 'todo'}},
  args: {
    'suffix-slot': '<craft-button size="small">Suffix</craft-button>',
  },
};

/** A `shortcut` string is shown at the end of the item. It is display only. */
export const WithShortcut: Story = {
  args: {icon: 'file', shortcut: 'S', 'default-slot': 'Save'},
};

/** An object shortcut names its modifiers. */
export const WithComplexShortcut: Story = {
  args: {
    icon: 'file',
    shortcut: '{"key": "S", "alt": true, "shift": true}',
    'default-slot': 'Save',
  },
};

/**
 * `type="checkbox"` reserves room for a checkmark before the icon, so a list
 * of options stays aligned whether or not each one is checked.
 */
export const Checkable: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-action-item icon="home" type="checkbox" checked>
      <div>
        <div class="font-bold">Entry Type</div>
        <pre>entryType</pre>
      </div>
    </craft-action-item>
    <craft-action-item icon="newspaper" type="checkbox" active>
      <div>
        <div class="font-bold">Entry Type</div>
        <pre>entryType</pre>
      </div>
    </craft-action-item>
  `,
};

/** An item inherits the palette of any `data-color` ancestor. */
export const Colors: Story = {
  parameters: {
    controls: {disable: true},
    // A full-palette grid necessarily includes colors that fall below the
    // contrast threshold on this surface; that is what the grid is showing.
    a11y: {test: 'todo'},
  },
  render: () => html`
    ${Object.entries(Color).map(
      ([name, value]) =>
        html`<craft-action-item data-color="${value}"
          >With color ${name}</craft-action-item
        >`
    )}
  `,
};
