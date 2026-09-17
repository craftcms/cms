import {beforeEach, expect, it, vi} from 'vite-plus/test';
import {computeAccessibleName} from 'dom-accessibility-api';
import './thumbnail.js';

beforeEach(() => {
  document.body.innerHTML = '';
});

function imageFixture(width: number, height: number) {
  return (
    'data:image/svg+xml;utf8,' +
    encodeURIComponent(
      `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
      <rect width="${width}" height="${height}" fill="#305ce7" />
      <rect x="${width / 3}" width="${width / 3}" height="${height}" fill="#ffffff" />
    </svg>`
    )
  );
}

const opaqueImage = imageFixture(300, 300);

it('freezes an animated source instead of autoplaying, via the attribute or a .gif/.webp extension', async () => {
  document.body.innerHTML = `
    <style>
      /* Mirrors Edit.vue's real-world override for a non-square preview. */
      craft-thumbnail.auto-sized::part(thumbnail) {
        width: auto;
        height: auto;
        max-width: 100%;
        max-height: 120px;
      }
    </style>
    <craft-thumbnail
      src="${opaqueImage}"
      alt="Animated via attribute"
      animated
      style="--c-thumbnail-size: 120px;"
    ></craft-thumbnail>
    <craft-thumbnail
      src="${opaqueImage}#animated.gif"
      alt="Animated via extension"
      style="--c-thumbnail-size: 120px;"
    ></craft-thumbnail>
    <craft-thumbnail
      class="auto-sized"
      src="${opaqueImage}"
      alt="Animated with an auto-sized wrapper"
      animated
    ></craft-thumbnail>
  `;

  for (const thumbnail of document.querySelectorAll('craft-thumbnail')) {
    await thumbnail.updateComplete;
    const image = thumbnail.shadowRoot!.querySelector('img')!;
    await image.decode();

    const cover = thumbnail.shadowRoot!.querySelector('canvas[part="cover"]');
    expect(cover).not.toBeNull();
    expect(cover!.getAttribute('aria-hidden')).toBe('true');

    // Once the frame is actually captured, the real <img> is visually
    // hidden — otherwise a source with transparent pixels would leave it
    // still playing underneath, visible through the cover's transparent
    // areas. It stays in the accessibility tree throughout (alt intact).
    await vi.waitFor(() =>
      expect(image.classList.contains('cp-visually-hidden')).toBe(true)
    );
    expect(computeAccessibleName(image)).toBe(thumbnail.alt);

    // The wrapper must not collapse to 0x0 once the image is hidden and
    // the cover takes over.
    const wrapperRect = thumbnail
      .shadowRoot!.querySelector('[part="thumbnail"]')!
      .getBoundingClientRect();
    expect(wrapperRect.width).toBeGreaterThan(0);
    expect(wrapperRect.height).toBeGreaterThan(0);
  }
});

it('freezes once a thumbnail becomes visible, even if it was hidden when its image loaded', async () => {
  document.body.innerHTML = `
    <div id="hidden-host" hidden>
      <craft-thumbnail
        src="${opaqueImage}"
        alt="Animated while hidden"
        animated
        style="--c-thumbnail-size: 120px;"
      ></craft-thumbnail>
    </div>
  `;

  const host = document.querySelector<HTMLDivElement>('#hidden-host')!;
  const thumbnail = host.querySelector('craft-thumbnail')!;
  await thumbnail.updateComplete;
  const image = thumbnail.shadowRoot!.querySelector('img')!;
  await image.decode();

  // The image loads and decodes normally while hidden (display:none
  // doesn't block that), but has no layout box yet.
  await new Promise((resolve) => setTimeout(resolve, 300));

  host.hidden = false;

  await vi.waitFor(() => {
    const canvas = thumbnail.shadowRoot!.querySelector<HTMLCanvasElement>(
      'canvas[part="cover"]'
    )!;
    expect(canvas.width).toBeGreaterThan(0);
    expect(canvas.height).toBeGreaterThan(0);
  });
});

it('shows the image again when animated is turned off after freezing', async () => {
  document.body.innerHTML = `<craft-thumbnail src="${opaqueImage}" alt="Toggle" animated></craft-thumbnail>`;
  const thumbnail = document.querySelector('craft-thumbnail')!;
  await thumbnail.updateComplete;
  const image = thumbnail.shadowRoot!.querySelector('img')!;
  await image.decode();

  await vi.waitFor(() =>
    expect(image.classList.contains('cp-visually-hidden')).toBe(true)
  );

  thumbnail.animated = false;
  await thumbnail.updateComplete;

  expect(
    thumbnail.shadowRoot!.querySelector('canvas[part="cover"]')
  ).toBeNull();
  expect(image.classList.contains('cp-visually-hidden')).toBe(false);
});
