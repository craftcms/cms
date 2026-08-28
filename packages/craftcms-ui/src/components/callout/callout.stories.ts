import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html, nothing} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import {appearances} from '@src/constants/appearances.js';
import {variants} from '@src/constants/variants.js';
import {SPACING_STEPS} from '@src/mixins/Paddable.js';

import './callout.js';
import '../button/button.js';
import '../icon/icon.js';
import type CraftCallout from './callout.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `callout.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} =
  getStorybookHelpers<CraftCallout>('craft-callout');

/**
 * `padding` is supplied by the `Paddable` mixin, so the analyzer records no
 * resolvable type for it and the helpers cannot derive a control. Build one
 * from the same constant the mixin resolves against, rather than restating
 * the values by hand.
 */
const paddingArgType = {
  control: {type: 'select'},
  options: [...SPACING_STEPS, 'none', '0'],
} as const;

const MESSAGE = 'Entries in this section are disabled for the current site.';

const ACTION_BUTTON = `<craft-button type="button" variant="outline" inherit size="small">Action</craft-button>`;

const meta = {
  title: 'Components/Callout',
  component: 'craft-callout',
  args: {...args, 'default-slot': MESSAGE},
  argTypes: {...argTypes, padding: paddingArgType},
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<any>;

export default meta;
type Story = StoryObj<any>;

/**
 * A `neutral` callout has no default icon, so the icon region collapses and
 * the box is body content alone.
 */
export const Default: Story = {};

/**
 * Every variant except `neutral` supplies its own icon, so a variant alone is
 * usually all a callout needs.
 */
export const Variants: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      ${variants.map(
        (variant) =>
          html`<craft-callout variant="${variant}">${variant}</craft-callout>`
      )}
    </div>
  `,
};

/**
 * `appearance` sets how loudly the variant is stated, from `solid` down to
 * `plain`. See [Variants & Appearances](?path=/docs/tokens-variants-appearances--docs)
 * for the underlying token mapping.
 */
export const Appearances: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      ${appearances.map(
        (appearance) =>
          html`<craft-callout variant="info" appearance="${appearance}"
            >${appearance}</craft-callout
          >`
      )}
    </div>
  `,
};

/** The `title` attribute is the shorthand for the `title` slot. */
export const WithTitle: Story = {
  args: {variant: 'info', title: 'Site-specific content'},
};

/** Setting `icon` replaces whatever icon the variant would have supplied. */
export const WithIcon: Story = {
  args: {variant: 'info', icon: 'circle-info'},
};

/**
 * Slotting `icon` replaces the icon region with your own artwork. Slotted
 * content is honored even when no icon name resolves.
 */
export const CustomIcon: Story = {
  args: {
    variant: 'info',
    'icon-slot': `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" width="1lh" height="1lh" stroke="currentColor">
  <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
</svg>`,
  },
};

/** `hide-icon` suppresses the icon region, including a variant's default. */
export const HideIcon: Story = {
  args: {variant: 'warning', 'hide-icon': true},
};

/** The `action` slot holds a trailing button or link. */
export const WithAction: Story = {
  args: {variant: 'warning', 'action-slot': ACTION_BUTTON},
};

/**
 * `rounded` selects which corners are rounded, for callouts that sit flush
 * against another surface.
 */
export const Rounded: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      ${(['all', 'start', 'end', 'none'] as const).map(
        (rounded) =>
          html`<craft-callout variant="info" rounded="${rounded}"
            >rounded="${rounded}"</craft-callout
          >`
      )}
    </div>
  `,
};

/** `inline` renders the callout as a pill that flows with surrounding text. */
export const Inline: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <p style="max-width: 48ch">
      This entry has unsaved changes.
      <craft-callout variant="warning" inline>Draft</craft-callout>
      Publish it to make the changes live.
    </p>
  `,
};

/**
 * `size` steps the type down. The box padding is rem-based, so it does not
 * scale with the size — pair `size` with `padding` for a tighter box.
 */
export const Sizes: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      ${(['auto', 'small'] as const).map(
        (size) =>
          html`<craft-callout variant="info" size="${size}"
            >size="${size}" — ${MESSAGE}</craft-callout
          >`
      )}
    </div>
  `,
};

/**
 * The default (no `padding` attribute) keeps the callout's asymmetric pair —
 * `sm` on the block axis, `md` on the inline one. Any value that is given
 * applies to both axes, the way a one-value CSS `padding` shorthand does.
 */
export const Padding: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: flex; flex-direction: column; gap: 0.75rem">
      ${[undefined, 'none', 'sm', 'md', 'lg', 'xl'].map(
        (padding) => html`
          <craft-callout variant="info" padding="${padding ?? nothing}">
            ${padding ? `padding="${padding}"` : 'no padding attribute'} —
            ${MESSAGE}
          </craft-callout>
        `
      )}
    </div>
  `,
};

/**
 * The `padding` attribute is closed to the spacing scale. For a value off it,
 * or an asymmetric pair of your own, set the custom properties instead.
 */
export const CustomPadding: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-callout
      variant="info"
      style="--c-callout-padding-block: 2.5rem; --c-callout-padding-inline: var(--c-spacing-xl)"
    >
      ${MESSAGE}
    </craft-callout>
  `,
};

/**
 * Every region at once: a slotted title, a slotted icon, rich body content,
 * and a trailing action.
 */
export const KitchenSink: Story = {
  args: {
    variant: 'danger',
    'title-slot': 'Unable to save entry.',
    'icon-slot': '<craft-icon name="triangle-exclamation"></craft-icon>',
    'action-slot': ACTION_BUTTON,
    'default-slot': `<p>Please correct the errors and try again.</p>
<ul class="list">
  <li><a href="#">Title</a> is required.</li>
  <li><a href="#">Slug</a> is required.</li>
  <li><a href="#">Feature Image</a> must have at least one item.</li>
</ul>`,
  },
};
