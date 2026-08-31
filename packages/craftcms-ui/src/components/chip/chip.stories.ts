import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html, nothing} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import {Color} from '../../constants/colors';

import './chip.js';
import '../status/status.js';
import '../button/button.js';
import '../icon/icon.js';
import '../action-menu/action-menu.js';
import '../avatar/avatar.js';
import '../badge/badge.js';
import type CraftChip from './chip.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `chip.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} = getStorybookHelpers<CraftChip>('craft-chip');

const ACTION_BUTTON = `<craft-button icon size="small" variant="plain">
  <craft-icon name="ellipsis" label="Actions"></craft-icon>
</craft-button>`;

const meta = {
  title: 'Components/Chip',
  component: 'craft-chip',
  args: {...args, 'default-slot': 'Homepage'},
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

/** Every `size` value, including unset, for the size stories below. */
const chipSizes = [
  {size: 'small', label: 'Small'},
  {size: 'medium', label: 'Medium'},
  {size: 'large', label: 'Large'},
] as const;

/**
 * A chip with nothing but a label renders neither the prefix nor the suffix
 * region.
 */
export const Default: Story = {};

/**
 * The two regions are independent. Fill `suffix` for a per-chip action, and
 * `prefix` to supply your own leading content.
 */
export const PrefixAndSuffix: Story = {
  args: {
    'status-slot': '<craft-status status="live"></craft-status>',
    'suffix-slot': ACTION_BUTTON,
  },
};

export const CustomPrefix: Story = {
  args: {
    'prefix-slot': `<div style="padding: var(--_chip-spacing);">
  <craft-button size="small" inherit variant="primary" type="button">Btn</craft-button>
</div>`,
  },
};

export const SuffixOnly: Story = {
  args: {'suffix-slot': ACTION_BUTTON},
};

/**
 * Attaching an action menu after the chip has rendered, then adding items to
 * it. "Attach action menu" appends a `craft-action-menu` to the chip's
 * `suffix` slot, and the chip renders the suffix region without being told to
 * re-render. "Add action" appends an item to the menu that is already there.
 */
export const DeferredActions: Story = {
  parameters: {
    controls: {disable: true},
    docs: {
      // The feature here is imperative, so the rendered markup does not show
      // it. Keep this in sync with the handlers below.
      source: {
        code: `const chip = document.querySelector('craft-chip');

// Attach the menu once the entity's available actions are known.
const menu = document.createElement('craft-action-menu');
menu.slot = 'suffix';
menu.label = 'Actions';
menu.icon = 'ellipsis';
menu.actions = [
  {label: 'View', icon: 'eye', onClick: () => {}},
  {label: 'Edit', icon: 'pen', onClick: () => {}},
  {label: 'Delete', icon: 'trash', variant: 'danger', onClick: () => {}},
];

// The chip observes its own light DOM, so the suffix region appears on its own.
chip.append(menu);

// \`actions\` is a reactive property. Add an item by reassigning the array —
// pushing onto it in place does not trigger a re-render.
menu.actions = [
  ...menu.actions,
  {label: 'Custom action 1', icon: 'lightbulb', onClick: () => {}},
];`,
        language: 'js',
      },
    },
  },
  render: () => {
    let added = 0;

    const menuFor = (trigger: HTMLElement) =>
      trigger.parentElement?.querySelector('craft-action-menu') ?? null;

    const attachMenu = (event: Event) => {
      const trigger = event.currentTarget as HTMLElement;
      const chip = trigger.parentElement?.querySelector('craft-chip');
      if (!chip || chip.querySelector('[slot="suffix"]')) {
        return;
      }

      const menu = document.createElement('craft-action-menu');
      menu.slot = 'suffix';
      menu.label = 'Actions';
      menu.icon = 'ellipsis';
      menu.actions = [
        {label: 'View', icon: 'eye', onClick: () => {}},
        {label: 'Edit', icon: 'pen', onClick: () => {}},
        {label: 'Delete', icon: 'trash', variant: 'danger', onClick: () => {}},
      ];

      chip.append(menu);
    };

    const addAction = (event: Event) => {
      const menu = menuFor(event.currentTarget as HTMLElement);
      if (!menu) {
        return;
      }

      const current = Array.isArray(menu.actions) ? menu.actions : [];
      added++;

      // Reassign rather than push: `actions` is a reactive property, and Lit
      // compares by reference.
      menu.actions = [
        ...current,
        {
          label: `Custom action ${added}`,
          icon: 'lightbulb',
          onClick: () => {},
        },
      ];
    };

    return html`
      <div style="display: flex; gap: 1rem; align-items: center">
        <craft-chip>Homepage</craft-chip>
        <craft-button size="small" @click="${attachMenu}">
          Attach action menu
        </craft-button>
        <craft-button size="small" @click="${addAction}">
          Add action
        </craft-button>
      </div>
    `;
  },
};

/**
 * `show-thumb` is required. Without it, the `thumbnail` slot is not rendered,
 * and its content does not appear.
 */
export const Thumbnail: Story = {
  args: {
    'show-thumb': true,
    'thumbnail-slot': '<img src="https://picsum.photos/120/120" alt="" />',
    'suffix-slot': ACTION_BUTTON,
  },
};

/**
 * The `icon` attribute is a shorthand for the `icon` slot, and setting it is
 * what causes that slot to be rendered.
 */
export const Icon: Story = {
  args: {icon: 'star'},
};

/**
 * The four `size` values on a bare chip. Each step is taller than the last:
 * `small` adds block padding, and `medium` applies a minimum height. `large`
 * has no styles of its own, so it renders the same as an unset `size`.
 */
export const Sizes: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div
      style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: start"
    >
      ${chipSizes.map(
        ({size, label}) =>
          html`<craft-chip size="${size || nothing}">${label}</craft-chip>`
      )}
    </div>
  `,
};

/**
 * The same sizes with a thumbnail and a suffix. `medium` sets a minimum height
 * on the chip's regions, so the difference is clearest once there is content
 * that does not already fill them.
 */
export const SizesWithContent: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div
      style="display: flex; flex-direction: column; gap: 0.75rem; align-items: start"
    >
      ${chipSizes.map(
        ({size, label}) => html`
          <craft-chip size="${size || nothing}" show-thumb>
            <img slot="thumbnail" src="https://picsum.photos/120/120" alt="" />
            ${label}
            <craft-button icon size="small" variant="plain" slot="suffix">
              <craft-icon name="ellipsis" label="Actions"></craft-icon>
            </craft-button>
          </craft-chip>
        `
      )}
    </div>
  `,
};

/**
 * `variant` sets the color group and `appearance` determines how those tokens
 * are applied. See [Variants & Appearances](?path=/docs/tokens-variants-appearances--docs)
 * for the underlying token mapping.
 */
export const Appearances: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div
      style="display: flex; flex-wrap: wrap; gap: 0.75rem; align-items: center"
    >
      ${['solid', 'fill', 'outline-fill', 'outline', 'plain'].map(
        (appearance) =>
          html`<craft-chip variant="info" appearance="${appearance}">
            ${appearance}
          </craft-chip>`
      )}
    </div>
  `,
};

/**
 * A chip inherits the palette of any `data-color` ancestor, so chips can be
 * tinted per row without overriding tokens.
 */
export const Colors: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div
      style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1rem"
    >
      ${Object.entries(Color).map(
        ([name, value]) =>
          html`<craft-chip data-color="${value}">
            ${name}
            <craft-button size="small" slot="suffix" inherit variant="plain"
              >Button</craft-button
            >
          </craft-chip>`
      )}
    </div>
  `,
};

/**
 * Every slot at once. The first chip fills the built-in prefix slots —
 * `thumbnail`, `icon`, and `status` — each of which needs its own attribute
 * before it renders. The second fills `prefix` instead, which replaces that
 * whole region, so the built-in slots are ignored. Both fill the default slot
 * and `suffix`.
 */
export const KitchenSink: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-wrap: wrap; gap: 1rem; align-items: center">
      <craft-chip
        selectable
        select-label="Select Homepage"
        show-thumb
        show-status
        icon="file"
      >
        <img slot="thumbnail" src="https://picsum.photos/120/120" alt="" />
        <craft-icon slot="icon" name="lightbulb"></craft-icon>
        <craft-status slot="status" status="live"></craft-status>
        Built-in prefix slots
        <craft-action-menu slot="suffix">
          <craft-button
            slot="invoker"
            label="Actions"
            size="small"
            variant="plain"
          >
            <craft-icon name="ellipsis" label="Actions"></craft-icon>
          </craft-button>
          <craft-action-item>Action Item</craft-action-item>
        </craft-action-menu>
      </craft-chip>

      <craft-chip>
        <craft-badge
          fill="info"
          slot="prefix"
          style="margin-inline: var(--_chip-spacing)"
          >Badge</craft-badge
        >
        Custom prefix
        <craft-action-menu slot="suffix">
          <craft-button
            slot="invoker"
            label="Actions"
            size="small"
            variant="plain"
          >
            <craft-icon name="ellipsis" label="Actions"></craft-icon>
          </craft-button>
          <craft-action-item>Action Item</craft-action-item>
        </craft-action-menu>
      </craft-chip>
    </div>
  `,
};
