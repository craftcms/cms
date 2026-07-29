import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html} from 'lit';

import type CraftCard from './card.js';
import './card.js';
import '../button/button.js';
import '../icon/icon.js';

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
const meta = {
  title: 'Components/Card',
  component: 'craft-card',
  args: {
    label: 'Card label',
    active: false,
    thumbAlignment: 'start',
  },
  argTypes: {
    label: {
      control: {type: 'text'},
      description:
        'Header label; the header only renders when this (or a header/label/actions slot) is present',
    },
    active: {
      control: {type: 'boolean'},
    },
    thumbAlignment: {
      control: {type: 'select'},
      options: ['start', 'end'],
    },
  },
  render: ({label, active}) => html`
    <craft-card label="${label}" ?active="${active}">
      Cards group related content into a bordered surface, with optional header,
      footer, and thumbnail regions.
    </craft-card>
  `,
} satisfies Meta<CraftCard>;

export default meta;
type Story = StoryObj<CraftCard>;

// More on writing stories with args: https://storybook.js.org/docs/writing-stories/args

/** Body only — with no label or header/label/actions slot, no header renders. */
export const Default: Story = {
  render: () => html`
    <craft-card>
      A bare card is just the bordered body surface — no header or footer chrome
      until content asks for it.
    </craft-card>
  `,
};

/** The `label` attribute alone brings in the header region. */
export const WithLabel: Story = {};

/** Buttons in the `actions` slot sit at the end of the header. */
export const WithActions: Story = {
  render: ({label}) => html`
    <craft-card label="${label}">
      <craft-button
        slot="actions"
        icon="pencil"
        appearance="plain"
        size="small"
        aria-label="Edit"
      ></craft-button>
      <craft-button
        slot="actions"
        icon="ellipsis"
        appearance="plain"
        size="small"
        aria-label="Actions"
      ></craft-button>

      Header actions are slotted content, so anything works — icon buttons, an
      action menu, a switch.
    </craft-card>
  `,
};

/** The `label` slot replaces the label text while keeping the header layout. */
export const CustomLabel: Story = {
  render: () => html`
    <craft-card>
      <span
        slot="label"
        style="display: inline-flex; align-items: center; gap: 0.5em;"
      >
        <craft-icon name="newspaper"></craft-icon>
        Article
        <code>article</code>
      </span>

      A slotted label can carry richer content than plain text — icons, handles,
      badges.
    </craft-card>
  `,
};

/** The `header` slot replaces the whole default label/actions header. */
export const CustomHeader: Story = {
  render: () => html`
    <craft-card>
      <div
        slot="header"
        style="display: flex; align-items: center; justify-content: space-between; inline-size: 100%;"
      >
        <strong>Custom header</strong>
        <em>anything goes here</em>
      </div>

      When the header slot is filled, the default label/actions layout is
      replaced entirely.
    </craft-card>
  `,
};

/** The footer region only renders when the `footer` slot is filled. */
export const WithFooter: Story = {
  render: ({label}) => html`
    <craft-card label="${label}">
      Body content.

      <div
        slot="footer"
        style="display: flex; justify-content: space-between; inline-size: 100%;"
      >
        <span>Updated 2 hours ago</span>
        <span>Draft</span>
      </div>
    </craft-card>
  `,
};

/** Thumbnail content renders in a fixed-width column beside the body. */
export const WithThumbnail: Story = {
  render: ({label, thumbAlignment}) => html`
    <craft-card label="${label}" thumb-alignment="${thumbAlignment}">
      ${placeholderThumb} The thumbnail slot gets a 120px column;
      <code>thumb-alignment</code> puts it at the start or end of the body.
    </craft-card>
  `,
};

export const ThumbnailAlignment: Story = {
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
  render: ({label}) => html`
    <div style="display: grid; gap: 1rem;">
      <craft-card label="${label}">An inactive card for comparison.</craft-card>
      <craft-card label="${label}" active>
        An active card — loud header/footer fill and border.
      </craft-card>
    </div>
  `,
};

/** Radius, shadow, and padding are themeable via custom properties. */
export const CustomProperties: Story = {
  render: ({label}) => html`
    <craft-card
      label="${label}"
      style="--c-card-radius: 0; --c-card-shadow: none; --c-card-padding-inline: 2rem; --c-card-padding-block: 1rem;"
    >
      Square corners, no shadow, and roomier padding via
      <code>--c-card-*</code> custom properties.
    </craft-card>
  `,
};

export const KitchenSink: Story = {
  render: ({label, active, thumbAlignment}) => html`
    <craft-card
      label="${label}"
      ?active="${active}"
      thumb-alignment="${thumbAlignment}"
    >
      <craft-button
        slot="actions"
        icon="ellipsis"
        appearance="plain"
        size="small"
        aria-label="Actions"
      ></craft-button>

      ${placeholderThumb}

      <strong>Autumn on the Coast</strong>
      <p style="margin: 0.25em 0 0">
        Header label and actions, a leading thumbnail, body content, and a
        metadata footer — all regions at once.
      </p>

      <div
        slot="footer"
        style="display: flex; justify-content: space-between; inline-size: 100%;"
      >
        <span>Posted Oct 14</span>
        <span>Live</span>
      </div>
    </craft-card>
  `,
};
