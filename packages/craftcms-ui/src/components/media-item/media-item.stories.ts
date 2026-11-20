import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftMediaItem from './media-item.js';
import {html} from 'lit';
import './media-item.js';
import '../avatar/avatar.js';

const image =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" width="300" height="200" viewBox="0 0 300 200">
      <rect width="300" height="200" fill="#305ce7" />
      <rect x="100" width="100" height="200" fill="#ffffff" />
    </svg>`
  );

const meta = {
  title: 'Components/Media Item',
  component: 'craft-media-item',
  args: {
    icon: 'newspaper',
    truncate: false,
  },
  argTypes: {
    icon: {
      control: {type: 'text'},
      description: 'An icon drawn faintly in place of a missing thumbnail',
    },
    truncate: {
      control: {type: 'boolean'},
      description:
        'Keeps the heading and supporting text to one line each, ending in an ellipsis',
    },
  },
  render: ({icon, truncate}) => html`
    <craft-media-item
      icon="${icon}"
      ?truncate="${truncate}"
      style="max-width: 22rem;"
    >
      <a slot="heading" href="#">Annual report published</a>
      Nov 1, 2026 · News articles
    </craft-media-item>
  `,
} satisfies Meta<CraftMediaItem>;

export default meta;
type Story = StoryObj<CraftMediaItem>;

/** A heading and supporting text, with an icon standing in for the thumbnail. */
export const Default: Story = {};

/** An image or thumbnail slotted in, cropped to the square. */
export const Thumbnail: Story = {
  render: () => html`
    <craft-media-item style="max-width: 22rem;">
      <img slot="thumbnail" src="${image}" alt="" />
      <a slot="heading" href="#">Houses in Munich</a>
      Collection · Vassily Kandinsky
    </craft-media-item>
  `,
};

/** Content at the item's end, such as the author's avatar. */
export const Suffix: Story = {
  render: () => html`
    <craft-media-item icon="newspaper" style="max-width: 22rem;">
      <a slot="heading" href="#">Museum shop launches online</a>
      Nov 1, 2026 · News articles
      <craft-avatar slot="suffix" label="Sam Wilson"></craft-avatar>
    </craft-media-item>
  `,
};

/** Kept to one line each, for a dense list of items whose full text is a click away. */
export const Truncate: Story = {
  render: () => html`
    <craft-media-item icon="newspaper" truncate style="max-width: 16rem;">
      <a slot="heading" href="#"
        >Museum joins a European collections network for shared research</a
      >
      Nov 1, 2026 · News articles
    </craft-media-item>
  `,
};

/** Items stacked in a list, spaced as the dashboard's lists are. */
export const List: Story = {
  render: () => html`
    <ul
      role="list"
      style="display: grid; gap: calc(var(--c-spacing) * 3); max-width: 22rem; margin: 0; padding: 0; list-style: none;"
    >
      <li>
        <craft-media-item icon="newspaper">
          <a slot="heading" href="#">Annual report published</a>
          Nov 1, 2026 · News articles
        </craft-media-item>
      </li>
      <li>
        <craft-media-item>
          <img slot="thumbnail" src="${image}" alt="" />
          <a slot="heading" href="#">Houses in Munich</a>
          Collection · Vassily Kandinsky
        </craft-media-item>
      </li>
      <li>
        <craft-media-item icon="comment">
          <span slot="heading">New mention</span>
          Tomás Ortega mentioned you in a comment.
        </craft-media-item>
      </li>
    </ul>
  `,
};
