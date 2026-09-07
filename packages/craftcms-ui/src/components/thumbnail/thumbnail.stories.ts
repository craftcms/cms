import type {Meta, StoryObj} from '@storybook/web-components-vite';
import type CraftThumbnail from './thumbnail.js';
import {html} from 'lit';
import {getStorybookHelpers} from '@wc-toolkit/storybook-helpers';
import {expect} from 'storybook/test';
const {events, args, argTypes, template} =
  getStorybookHelpers('craft-thumbnail');
import './thumbnail.js';

// An opaque sample image.
const opaqueImage = imageFixture(300, 300);

function imageFixture(width: number, height: number, transparent = false) {
  return (
    'data:image/svg+xml;utf8,' +
    encodeURIComponent(
      `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
      <rect width="${width}" height="${height}" fill="#305ce7" opacity="${transparent ? 0.3 : 1}" />
      <rect x="${width / 3}" width="${width / 3}" height="${height}" fill="#ffffff" />
    </svg>`
    )
  );
}

// A semi-transparent image so the checkered backing is visible behind it.
const transparentImage =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">
      <circle cx="50" cy="50" r="38" fill="#4a7cff" opacity="0.85" />
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

// Checkered is enabled by default and is visible behind the transparent image.
export const Default: Story = {
  args: {},
};

// Disable the checkered backing for an opaque image.
export const Checkered: Story = {
  render: () => html`
    <craft-thumbnail src="${transparentImage}" alt="Checkered (default)">
    </craft-thumbnail>
    <craft-thumbnail
      src="${transparentImage}"
      alt="No checkered"
      .checkered="${false}"
    >
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

const modes = ['fit', 'crop', 'stretch', 'letterbox'] as const;
const fixtures = [
  {
    name: 'Landscape override URL',
    width: 300,
    height: 150,
    fittedWidth: 120,
    fittedHeight: 60,
  },
  {
    name: 'Portrait',
    width: 150,
    height: 300,
    fittedWidth: 60,
    fittedHeight: 120,
  },
  {
    name: 'Square',
    width: 300,
    height: 300,
    fittedWidth: 120,
    fittedHeight: 120,
  },
  {
    name: 'Small source',
    width: 12,
    height: 8,
    fittedWidth: 12,
    fittedHeight: 8,
  },
  {
    name: 'Transparent',
    width: 300,
    height: 150,
    fittedWidth: 120,
    fittedHeight: 60,
    transparent: true,
  },
];

export const Modes: Story = {
  render: () => html`
    ${modes.map(
      (mode) => html`
        <section aria-label=${mode}>
          <h2>${mode}</h2>
          ${fixtures.map(
            (fixture, index) => html`
              <craft-thumbnail
                mode=${mode}
                src=${imageFixture(
                  fixture.width,
                  fixture.height,
                  fixture.transparent
                )}
                alt=${fixture.name}
                loading="eager"
                data-fixture=${index}
                style="--c-thumbnail-size: 120px;"
              ></craft-thumbnail>
              <craft-thumbnail
                mode=${mode}
                data-fixture=${index}
                style="--c-thumbnail-size: 120px;"
                rounded
              >
                <img
                  src=${imageFixture(
                    fixture.width,
                    fixture.height,
                    fixture.transparent
                  )}
                  alt=${`Slotted ${fixture.name}`}
                />
              </craft-thumbnail>
            `
          )}
        </section>
      `
    )}
  `,
  play: async ({canvasElement}) => {
    for (const thumbnail of canvasElement.querySelectorAll('craft-thumbnail')) {
      await thumbnail.updateComplete;
      const image =
        thumbnail.shadowRoot!.querySelector('img') ??
        thumbnail.querySelector('img')!;
      await image.decode();
      const fixture = fixtures[Number(thumbnail.dataset.fixture)]!;
      const sized = thumbnail.mode === 'crop' || thumbnail.mode === 'stretch';
      const wrapper = thumbnail
        .shadowRoot!.querySelector('[part="thumbnail"]')!
        .getBoundingClientRect();
      const box = image.getBoundingClientRect();
      await expect(wrapper.width).toBe(120);
      await expect(wrapper.height).toBe(120);
      await expect(box.width).toBe(sized ? 120 : fixture.fittedWidth);
      await expect(box.height).toBe(sized ? 120 : fixture.fittedHeight);
      await expect(getComputedStyle(image).objectFit).toBe(
        thumbnail.mode === 'crop'
          ? 'cover'
          : thumbnail.mode === 'stretch'
            ? 'fill'
            : 'contain'
      );
      await expect(box.x + box.width / 2).toBeCloseTo(wrapper.x + 60);
      await expect(box.y + box.height / 2).toBeCloseTo(wrapper.y + 60);
    }
  },
};

export const InlineSvgModes: Story = {
  render: () => html`
    ${modes.map(
      (mode) => html`
        <craft-thumbnail mode=${mode} style="--c-thumbnail-size: 120px;">
          <svg
            width="300"
            height="150"
            viewBox="0 0 300 150"
            role="img"
            aria-label=${`Inline SVG ${mode}`}
          >
            <rect width="300" height="150" fill="#305ce7" />
            <rect
              class="marker"
              x="100"
              width="100"
              height="150"
              fill="#ffffff"
            />
          </svg>
        </craft-thumbnail>
      `
    )}
  `,
  play: async ({canvasElement}) => {
    for (const thumbnail of canvasElement.querySelectorAll('craft-thumbnail')) {
      await thumbnail.updateComplete;
      const svg = thumbnail.querySelector('svg')!;
      const marker = svg.querySelector('.marker')!.getBoundingClientRect();
      const wrapper = thumbnail
        .shadowRoot!.querySelector('[part="thumbnail"]')!
        .getBoundingClientRect();
      await expect(wrapper.width).toBe(120);
      await expect(wrapper.height).toBe(120);
      await expect(marker.width).toBe(thumbnail.mode === 'crop' ? 80 : 40);
      await expect(marker.height).toBe(
        thumbnail.mode === 'crop' || thumbnail.mode === 'stretch' ? 120 : 60
      );
      await expect(marker.x + marker.width / 2).toBeCloseTo(wrapper.x + 60);
      await expect(marker.y + marker.height / 2).toBeCloseTo(wrapper.y + 60);

      const mode = thumbnail.mode;
      thumbnail.mode = 'fit';
      await thumbnail.updateComplete;
      await expect(svg.hasAttribute('preserveAspectRatio')).toBe(false);
      await expect(
        svg.querySelector('.marker')!.getBoundingClientRect().height
      ).toBe(60);
      thumbnail.mode = mode;
      await thumbnail.updateComplete;
    }
  },
};
