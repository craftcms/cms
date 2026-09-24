import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';

import type CraftCard from './card.js';
import './card.js';
import '../button/button.js';
import '../icon/icon.js';

/**
 * `args` and `argTypes` are derived from the custom elements manifest, so the
 * controls and the API tables follow the component's JSDoc. Adding a property
 * to `card.ts` surfaces it here without touching this file.
 */
const {args, argTypes, template} = getStorybookHelpers<CraftCard>('craft-card');

const BODY =
  'Cards group related content into a bordered surface, with optional header, footer, and thumbnail regions.';

const ACTION_BUTTON = `<craft-button icon="ellipsis" variant="plain" size="small" aria-label="Actions"></craft-button>`;

const THUMB = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 120 120" role="img" aria-label="Placeholder thumbnail" style="display: block; width: 100%; height: auto; border-radius: 4px;">
  <rect width="120" height="120" fill="#e2e8f0" />
  <path d="M20 88l24-28 18 20 14-16 24 24v12H20z" fill="#94a3b8" />
  <circle cx="42" cy="38" r="10" fill="#94a3b8" />
</svg>`;

const placeholderThumb = html`
  <svg
    slot="thumbnail"
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 120 120"
    role="img"
    aria-label="Placeholder thumbnail"
    style="display: block; width: 100%; height: auto; border-radius: 4px;"
  >
    <rect width="120" height="120" fill="#e2e8f0" />
    <path d="M20 88l24-28 18 20 14-16 24 24v12H20z" fill="#94a3b8" />
    <circle cx="42" cy="38" r="10" fill="#94a3b8" />
  </svg>
`;

// More on how to set up stories at: https://storybook.js.org/docs/writing-stories
/**
 * The helpers add slot and CSS-property args alongside the element's own
 * properties, so stories are typed against both.
 */
type CardArgs = CraftCard & typeof args;

const meta = {
  title: 'Components/Card',
  component: 'craft-card',
  args: {...args, label: 'Card label', 'default-slot': BODY},
  argTypes,
  // Render from args alone so every control — attributes and slots — drives
  // the story. Stories below vary the args, not the template.
  render: (args) => template(args),
} satisfies Meta<CardArgs>;

export default meta;
type Story = StoryObj<CardArgs>;

/** Body only — with no label or header/label/actions slot, no header renders. */
export const Default: Story = {
  args: {
    label: '',
    'default-slot':
      'A bare card is just the bordered body surface — no header or footer chrome until content asks for it.',
  },
};

/** The `label` attribute alone brings in the header region. */
export const WithLabel: Story = {};

/** Buttons in the `actions` slot sit at the end of the header. */
export const WithActions: Story = {
  args: {
    'actions-slot': `<craft-button icon="pencil" variant="plain" size="small" aria-label="Edit"></craft-button>
${ACTION_BUTTON}`,
    'default-slot':
      'Header actions are slotted content, so anything works — icon buttons, an action menu, a switch.',
  },
};

/** The `label` slot replaces the label text while keeping the header layout. */
export const CustomLabel: Story = {
  args: {
    'label-slot': `<span style="display: inline-flex; align-items: center; gap: 0.5em;">
  <craft-icon name="newspaper"></craft-icon>
  Article
  <code>article</code>
</span>`,
    'default-slot':
      'A slotted label can carry richer content than plain text — icons, handles, badges.',
  },
};

/** The `header` slot replaces the whole default label/actions header. */
export const CustomHeader: Story = {
  args: {
    'header-slot': `<div style="display: flex; align-items: center; justify-content: space-between; inline-size: 100%;">
  <strong>Custom header</strong>
  <em>anything goes here</em>
</div>`,
    'default-slot':
      'When the header slot is filled, the default label/actions layout is replaced entirely.',
  },
};

/** The footer region only renders when the `footer` slot is filled. */
export const WithFooter: Story = {
  args: {
    'default-slot': 'Body content.',
    'footer-slot': `<div style="display: flex; justify-content: space-between; inline-size: 100%;">
  <span>Updated 2 hours ago</span>
  <span>Draft</span>
</div>`,
  },
};

/** Thumbnail content renders in a fixed-width column beside the body. */
export const WithThumbnail: Story = {
  args: {
    'thumbnail-slot': THUMB,
    'default-slot':
      'The thumbnail slot gets a 120px column; thumb-alignment puts it at the start or end of the body.',
  },
};

/** `thumb-alignment` puts the thumbnail column before or after the body. */
export const ThumbnailAlignment: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: grid; gap: 1rem;">
      <craft-card label='thumb-alignment="start"'>
        ${placeholderThumb} Thumbnail leading the body content.
      </craft-card>
      <craft-card label='thumb-alignment="end"' thumb-alignment="end">
        ${placeholderThumb} Thumbnail trailing the body content.
      </craft-card>
    </div>
  `,
};

/** The reflected `active` attribute marks selection (e.g. element index rows). */
export const Active: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div style="display: grid; gap: 1rem;">
      <craft-card label="Card label"
        >An inactive card for comparison.</craft-card
      >
      <craft-card label="Card label" active>
        An active card — loud header/footer fill and border.
      </craft-card>
    </div>
  `,
};

/** Radius, shadow, and padding are themeable via custom properties. */
export const CustomProperties: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-card
      label="Card label"
      style="--c-card-radius: 0; --c-card-shadow: none; --c-card-padding-inline: 2rem; --c-card-padding-block: 1rem;"
    >
      Square corners, no shadow, and roomier padding via
      <code>--c-card-*</code> custom properties.
    </craft-card>
  `,
};

/** Every region at once: header label and actions, thumbnail, body, footer. */
export const KitchenSink: Story = {
  args: {
    label: 'Autumn on the Coast',
    'actions-slot': ACTION_BUTTON,
    'thumbnail-slot': THUMB,
    'default-slot': `<p style="margin: 0">Header label and actions, a leading thumbnail, body content, and a metadata footer — all regions at once.</p>`,
    'footer-slot': `<div style="display: flex; justify-content: space-between; inline-size: 100%;">
  <span>Posted Oct 14</span>
  <span>Live</span>
</div>`,
  },
};
