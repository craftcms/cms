import type {Meta, StoryObj} from '@storybook/web-components-vite';

import {html, nothing} from 'lit';
import {unsafeHTML} from 'lit/directives/unsafe-html.js';

import type CraftCard from './card.js';
import './card.js';
import '../badge/badge.js';
import '../button/button.js';
import '../checkbox/checkbox.js';
import '../icon/icon.js';
import type {Luminance} from './cover.js';
import {HARD_IMAGES, MANY_ATTRIBUTES} from '../../../test/cover-fixtures';

/**
 * Cases that nobody sets up on purpose, but that reviewers need to see: the
 * images and content the card's browser tests measure. The API's own examples
 * are in `card.stories.ts`.
 */
const meta: Meta<CraftCard> = {
  title: 'Components/Card/Edge Cases',
  component: 'craft-card',
  tags: ['!autodocs'],
  parameters: {
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
  },
};

export default meta;
type Story = StoryObj<CraftCard>;

const LONG_TEXT =
  'The Course of Empire: Destruction, by Thomas Cole, oil on canvas, 1836';

interface CoverCardOptions {
  src: string;
  name: string;
  luminance?: Luminance | null;
  label?: string;
  body?: unknown;
}

const coverCard = ({
  src,
  name,
  luminance = null,
  label,
  body = html`<div>${name.toLowerCase().replaceAll(' ', '-')}.jpg</div>`,
}: CoverCardOptions) => html`
  <craft-card cover-src="${src}" .luminance="${luminance}">
    <div
      slot="header"
      style="display: flex; align-items: center; justify-content: space-between; gap: var(--c-spacing-sm); inline-size: 100%;"
    >
      <div
        style="display: flex; align-items: center; gap: var(--c-spacing-sm); min-inline-size: 0;"
      >
        <craft-checkbox label-sr-only>
          <label slot="label">Select ${name}</label>
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
    <a href="#">${name}</a>
    ${body}
  </craft-card>
`;

const grid = (
  cards: unknown[],
  columns = 'repeat(auto-fill, minmax(260px, 1fr))'
) => html`
  <div style="display: grid; grid-template-columns: ${columns}; gap: 1rem;">
    ${cards}
  </div>
`;

/**
 * Images the luminance describes badly, each with only controls in the header
 * and with a label: pure white and black, stripes too thin to show in the
 * luminance, and portraits whose top and bottom, which the luminance is read
 * from, are cropped out of the card.
 */
export const CoverHardImages: Story = {
  render: () =>
    grid(
      HARD_IMAGES.flatMap(({name, src, luminance}) => [
        coverCard({src, name, luminance}),
        coverCard({src, name, luminance, label: name}),
      ])
    ),
};

/**
 * Before a cover image loads, the text and controls sit on the card's own fill,
 * with the colors picked for the image.
 */
export const CoverImageNotLoaded: Story = {
  render: () => {
    const {src, luminance} = HARD_IMAGES[3]!;

    return html`
      <style>
        #image-not-loaded craft-card::part(cover) {
          visibility: hidden;
        }
      </style>
      <div id="image-not-loaded">
        ${grid([
          coverCard({src, name: 'Not loaded', luminance}),
          coverCard({src, name: 'Not loaded', luminance, label: 'Not loaded'}),
        ])}
      </div>
    `;
  },
};

/**
 * Without luminance, as for an image sampled before luminance was, the header
 * and body are white.
 */
export const CoverWithoutLuminance: Story = {
  render: () => {
    const src = 'https://picsum.photos/id/1043/800/600';

    return grid([
      coverCard({src, name: 'Yosemite'}),
      coverCard({src, name: 'Yosemite', label: 'Yosemite'}),
    ]);
  },
};

/** A cover image that fails to load leaves a normal card with its thumbnail. */
export const CoverBrokenImage: Story = {
  render: () =>
    grid([
      html`<craft-card cover-src="data:image/png;base64,bm90IGFuIGltYWdl">
        <span slot="label">Broken image</span>
        <svg
          slot="thumbnail"
          xmlns="http://www.w3.org/2000/svg"
          viewBox="0 0 120 120"
          aria-hidden="true"
          style="display: block; width: 100%; height: auto;"
        >
          <rect width="120" height="120" fill="#e2e8f0" />
        </svg>
        <a href="#">Broken image</a>
      </craft-card>`,
    ]),
};

/**
 * Labels and content long enough to wrap over several lines. A cover card grows
 * past 4:3 to fit its content, with the scrim behind all of it, and the other
 * cards in its row grow to match.
 */
export const CoverLongContent: Story = {
  render: () => {
    const {src, luminance} = HARD_IMAGES[2]!;

    return grid([
      coverCard({
        src,
        name: LONG_TEXT,
        luminance,
        label: LONG_TEXT,
        body: html`${[1, 2, 3, 4].map(() => html`<div>${LONG_TEXT}</div>`)}
          <div slot="footer">${LONG_TEXT}</div>`,
      }),
      coverCard({
        src,
        name: LONG_TEXT,
        luminance,
        body: html`<div>${LONG_TEXT}</div>`,
      }),
    ]);
  },
};

/**
 * A body full of card attributes, as an element card renders them: wrapping
 * text, a link, option and color field previews, and status badges in every
 * palette color. The cards are wide enough, and so tall enough at 4:3, to show
 * all of it.
 */
export const CoverManyAttributes: Story = {
  render: () =>
    grid(
      HARD_IMAGES.map(({name, src, luminance}) =>
        coverCard({
          src,
          name,
          luminance,
          label: name,
          body: unsafeHTML(MANY_ATTRIBUTES),
        })
      ),
      'repeat(auto-fill, 600px)'
    ),
};
