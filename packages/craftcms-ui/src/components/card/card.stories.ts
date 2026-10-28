import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html, nothing} from 'lit';

import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import {Color} from '../../constants/colors';

import type CraftCard from './card.js';
import './card.js';
import '../button/button.js';
import '../checkbox/checkbox.js';
import '../icon/icon.js';
import type {Luminance} from './cover.js';

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

/**
 * Each photo's luminance, as Craft samples it from the 800×600 crop the cover
 * stories show.
 */
const LUMINANCE: Record<number, Luminance> = {
  1015: [
    [
      [0.373, 0.628],
      [0.372, 0.629],
      [0.377, 0.601],
      [0.325, 0.594],
    ],
    [
      [0.089, 0.62],
      [0.113, 0.435],
      [0.061, 0.39],
      [0.032, 0.167],
    ],
    [
      [0.079, 0.589],
      [0.22, 0.559],
      [0.037, 0.562],
      [0.035, 0.112],
    ],
  ],
  1018: [
    [
      [0.663, 0.888],
      [0.72, 0.847],
      [0.385, 0.871],
      [0.096, 0.785],
    ],
    [
      [0.033, 0.55],
      [0.025, 0.592],
      [0.009, 0.231],
      [0.009, 0.055],
    ],
    [
      [0.007, 0.048],
      [0.016, 0.144],
      [0.006, 0.047],
      [0.004, 0.031],
    ],
  ],
  1025: [
    [
      [0.052, 0.151],
      [0.076, 0.348],
      [0.055, 0.322],
      [0.059, 0.175],
    ],
    [
      [0.043, 0.2],
      [0.102, 0.578],
      [0.162, 0.642],
      [0.067, 0.249],
    ],
    [
      [0.024, 0.359],
      [0.06, 0.652],
      [0.054, 0.494],
      [0.027, 0.118],
    ],
  ],
  1036: [
    [
      [0.41, 0.578],
      [0.329, 0.653],
      [0.213, 0.545],
      [0.127, 0.46],
    ],
    [
      [0.079, 0.502],
      [0.151, 0.673],
      [0.111, 0.59],
      [0.082, 0.429],
    ],
    [
      [0.024, 0.478],
      [0.033, 0.372],
      [0.068, 0.353],
      [0.093, 0.355],
    ],
  ],
  1069: [
    [
      [0.063, 0.125],
      [0.097, 0.161],
      [0.062, 0.278],
      [0.043, 0.102],
    ],
    [
      [0.057, 0.302],
      [0.075, 0.399],
      [0.072, 0.196],
      [0.066, 0.119],
    ],
    [
      [0.023, 0.149],
      [0.038, 0.093],
      [0.029, 0.073],
      [0.021, 0.05],
    ],
  ],
  1080: [
    [
      [0.017, 0.202],
      [0.019, 0.21],
      [0.031, 0.286],
      [0.017, 0.164],
    ],
    [
      [0.028, 0.22],
      [0.019, 0.222],
      [0.044, 0.254],
      [0.015, 0.193],
    ],
    [
      [0.015, 0.205],
      [0.014, 0.247],
      [0.016, 0.232],
      [0.015, 0.184],
    ],
  ],
  1084: [
    [
      [0.524, 0.796],
      [0.529, 0.845],
      [0.731, 0.828],
      [0.239, 0.718],
    ],
    [
      [0.03, 0.444],
      [0.076, 0.455],
      [0.049, 0.581],
      [0.022, 0.153],
    ],
    [
      [0.029, 0.608],
      [0.049, 0.688],
      [0.035, 0.704],
      [0.005, 0.596],
    ],
  ],
};

const photo = (id: number) => `https://picsum.photos/id/${id}/800/600`;

interface CoverCardOptions {
  id: number;
  label?: string;
  luminance?: Luminance | null;
  active?: boolean;
}

/** A cover card laid out like an element index's. */
const coverCard = ({
  id,
  label,
  luminance = LUMINANCE[id],
  active = false,
}: CoverCardOptions) => html`
  <craft-card
    cover-src="${photo(id)}"
    .luminance="${luminance}"
    ?active="${active}"
  >
    <div
      slot="header"
      style="display: flex; align-items: center; justify-content: space-between; gap: var(--c-spacing-sm); inline-size: 100%;"
    >
      <div
        style="display: flex; align-items: center; gap: var(--c-spacing-sm);"
      >
        <craft-checkbox label-sr-only .checked="${active}">
          <label slot="label">Select Photo ${id}</label>
        </craft-checkbox>
        ${label ? html`<span data-card-label>${label}</span>` : nothing}
      </div>
      <craft-button
        icon="ellipsis"
        variant="plain"
        inherit
        size="small"
        flush
        aria-label="Actions"
      ></craft-button>
    </div>
    <a href="#">Photo ${id}</a>
    <div>photo-${id}.jpg · 800×600</div>
  </craft-card>
`;

const coverParameters = {
  controls: {disable: true},
  a11y: {
    config: {
      // axe measures text against the fill behind a cover card's image rather
      // than the image and the bar or scrim over it. The card's own browser
      // tests check that contrast instead.
      rules: [
        {id: 'color-contrast', selector: ':not(craft-card[cover-src] *)'},
      ],
    },
  },
};

const coverGrid = (...cards: unknown[]) => html`
  <div
    style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 1rem;"
  >
    ${cards}
  </div>
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

/**
 * `data-color` tints the card with a palette color. On a colored card, links
 * take the card's text color and are underlined, losing the underline on
 * hover.
 */
export const Colors: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <div
      style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem"
    >
      ${Object.entries(Color).map(
        ([name, value]) =>
          html`<craft-card data-color="${value}">
            <a slot="label" href="#">${name}</a>
            Body text with <a href="#">a link</a>.
            <span slot="footer">Footer <a href="#">link</a></span>
          </craft-card>`
      )}
    </div>
  `,
};

/** Radius, shadow, and padding are themeable via custom properties. */
export const CustomProperties: Story = {
  parameters: {controls: {disable: true}},
  render: () => html`
    <craft-card
      label="Card label"
      style="--c-card-radius: 0; --c-card-shadow: none;"
    >
      Square corners and no shadow via
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

/**
 * `cover-src` makes a cover card: the image fills the card, and the header and
 * body are laid over it, in white or black picked from `luminance`. A header
 * with text gets a blurred bar of its own, in black or white, strong enough
 * for the text to reach 4.5:1 over any image. The body sits on a scrim of the
 * same black or white, which fades in above the content and blurs the image
 * behind it.
 */
export const Cover: Story = {
  parameters: coverParameters,
  render: () =>
    coverGrid(
      coverCard({id: 1069, label: 'Jellyfish'}),
      coverCard({id: 1036, label: 'Base camp'})
    ),
};

/**
 * Without text in the header, its checkbox and plain buttons get a blurred
 * backing, opposite their border, so they hold up over any image.
 */
export const CoverBackedControls: Story = {
  parameters: coverParameters,
  render: () =>
    coverGrid(
      coverCard({id: 1025}),
      coverCard({id: 1018}),
      coverCard({id: 1015}),
      coverCard({id: 1084})
    ),
};

/** A selected cover card gets an accent border and ring. */
export const CoverActive: Story = {
  parameters: coverParameters,
  render: () =>
    coverGrid(
      coverCard({id: 1080, active: true}),
      coverCard({id: 1080, label: 'Strawberries', active: true})
    ),
};

/**
 * Cover cards and normal cards share a grid: the same column widths, headers
 * of the same height, and controls that line up.
 */
export const CoverAlongsideNormalCards: Story = {
  parameters: coverParameters,
  render: () =>
    coverGrid(
      coverCard({id: 1015}),
      coverCard({id: 1036, label: 'Base camp'}),
      html`<craft-card label="Icon 48">
        <craft-button
          slot="actions"
          icon="ellipsis"
          variant="plain"
          size="small"
          aria-label="Actions"
        ></craft-button>
        <img
          slot="thumbnail"
          src="https://picsum.photos/id/1062/48/48"
          alt=""
          width="48"
          height="48"
        />
        <a href="#">Icon 48</a>
      </craft-card>`
    ),
};
