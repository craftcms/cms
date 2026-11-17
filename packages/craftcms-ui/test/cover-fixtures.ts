import type {Luminance} from '../src/components/card/cover.js';
import {colors} from '../src/constants/colors.js';

const svg = (width: number, height: number, shapes: string) =>
  `data:image/svg+xml,${encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}">${shapes}</svg>`
  )}`;

const rows = (count: number, ...ranges: [number, number][]): Luminance =>
  ranges.map((range) => Array.from({length: count}, () => range));

export interface CoverImage {
  name: string;
  src: string;
  /** The luminance Craft samples for it. */
  luminance: Luminance;
}

/**
 * Images that are hard for luminance to describe: plain white and black,
 * stripes too thin to show in it, and portraits whose top and bottom, which
 * the luminance is read from, are cropped out of a cover card.
 */
export const HARD_IMAGES: CoverImage[] = [
  {
    name: 'White',
    src: svg(800, 600, '<rect width="800" height="600" fill="#fff"/>'),
    luminance: rows(4, [1, 1], [1, 1], [1, 1]),
  },
  {
    name: 'Black',
    src: svg(800, 600, '<rect width="800" height="600"/>'),
    luminance: rows(4, [0, 0], [0, 0], [0, 0]),
  },
  {
    name: 'Thin stripes',
    src: svg(
      1600,
      1200,
      `<rect width="1600" height="1200"/>${[0, 1, 2, 3]
        .flatMap((region) =>
          [6, 18].map((offset) => (region * 25 + offset) * 16)
        )
        .map((x) => `<rect x="${x}" width="16" height="1200" fill="#fff"/>`)
        .join('')}`
    ),
    luminance: rows(4, [0, 0], [0, 0], [0, 0]),
  },
  {
    name: 'Light portrait',
    src: svg(
      1200,
      1600,
      '<rect width="1200" height="1600"/><rect y="380" width="1200" height="840" fill="#fff"/>'
    ),
    luminance: rows(3, [0, 0], [1, 1], [1, 1], [0, 0]),
  },
  {
    name: 'Dark portrait',
    src: svg(
      1200,
      1600,
      '<rect width="1200" height="1600" fill="#fff"/><rect y="380" width="1200" height="840"/>'
    ),
    luminance: rows(3, [1, 1], [0, 0], [0, 0], [1, 1]),
  },
];

const attribute = (html: string) =>
  `<div class="card-attribute-preview">${html}</div>`;

/**
 * A cover card body full of card attributes, as an element card renders them
 * below its title: text that wraps, a link, option and color field previews, status
 * badges in every palette color with and without icons, and an element's
 * status labels.
 */
export const MANY_ATTRIBUTES = [
  '<div class="card-content">',
  attribute(
    'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt.'
  ),
  attribute('Oct 28 – Nov 30, 2026'),
  attribute('Gallery 3, North Wing'),
  attribute('<a href="#">example.com/exhibits/the-quick-brown-fox</a>'),
  attribute('Painting, Sculpture, Photography'),
  attribute(
    '<div class="color small static"><div class="color-preview" style="background-color: #e11d48"></div></div><span class="colorhex">Crimson</span>'
  ),
  attribute(
    '<craft-badge fill="teal"><craft-icon slot="prefix" name="check"></craft-icon>Featured</craft-badge>'
  ),
  attribute(
    '<craft-badge fill="gray"><craft-icon slot="prefix" name="xmark-large"></craft-icon>Archived</craft-badge>'
  ),
  attribute(
    colors
      .map((color) => `<craft-badge fill="${color}">${color}</craft-badge>`)
      .join(' ')
  ),
  '<ul class="flex gap-xs"><li><craft-badge fill="green">Live</craft-badge></li><li><craft-badge fill="blue"><craft-icon slot="prefix" name="pen-circle"></craft-icon>Edited</craft-badge></li></ul>',
  '</div>',
].join('');
