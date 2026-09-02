import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftThumbnail from './thumbnail.js';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
const {events, args, argTypes, template} =
  getStorybookHelpers('craft-thumbnail');
import './thumbnail.js';

// An opaque sample image.
const opaqueImage = 'https://picsum.photos/seed/craft/300/300';

// A semi-transparent image so the checkered backing is visible behind it.
const transparentImage =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
      <circle cx="50" cy="50" r="38" fill="%234a7cff" opacity="0.85" />
    </svg>`
  );

const meta: Meta<CraftThumbnail> = {
  title: 'Components/Thumbnail',
  component: 'craft-thumbnail',
  args: {
    ...args,
    src: transparentImage,
    alt: 'Sample thumbnail',
  },
  argTypes,
  render: (args) => template(args),
  parameters: {
    actions: {
      handles: events,
    },
  },
};

export default meta;
type Story = StoryObj<CraftThumbnail & typeof args>;

export const Default: Story = {
  args: {},
};

// Side by side, so the chequerboard reads as a property of the left thumbnail
// rather than as part of the image itself.
export const Checkered: Story = {
  render: () => html`
    <craft-thumbnail src="${transparentImage}" alt="Default"></craft-thumbnail>
    <craft-thumbnail src="${transparentImage}" alt="Checkered" checkered>
    </craft-thumbnail>
  `,
};

// Rounded corners (a full circle by default via --c-thumbnail-radius).
export const Rounded: Story = {
  render: () => html`
    <craft-thumbnail src="${opaqueImage}" alt="Rounded" rounded>
    </craft-thumbnail>
    <craft-thumbnail
      src="${opaqueImage}"
      alt="Rounded with custom radius"
      rounded
      style="--c-thumbnail-radius: var(--c-radius-lg);"
    >
    </craft-thumbnail>
  `,
};

// Native lazy loading is the default; eager can be requested explicitly.
export const Lazy: Story = {
  render: () => html`
    <craft-thumbnail
      src="${opaqueImage}"
      alt="Lazy loaded"
      .checkered="${false}"
    >
    </craft-thumbnail>
  `,
};

// Custom sizing via the --c-thumbnail-size custom property.
export const CustomSize: Story = {
  render: () => html`
    <craft-thumbnail src="${opaqueImage}" alt="Default size"></craft-thumbnail>
    <craft-thumbnail
      src="${opaqueImage}"
      alt="Medium"
      style="--c-thumbnail-size: 60px;"
    ></craft-thumbnail>
    <craft-thumbnail
      src="${opaqueImage}"
      alt="Large"
      style="--c-thumbnail-size: 100px;"
    ></craft-thumbnail>
  `,
};

// Falls back to slotted content when no src is provided.
export const SlottedContent: Story = {
  render: () => html`
    <craft-thumbnail alt="Slotted image">
      <img src="${transparentImage}" alt="Slotted" />
    </craft-thumbnail>
  `,
};
