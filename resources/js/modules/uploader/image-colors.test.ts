import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {readImageColors, sampleImageColors, type Pixels} from './image-colors';

type Rgba = [number, number, number, number?];

function pixels(
  width: number,
  height: number,
  background: Rgba,
  rectangle?: {x1: number; y1: number; x2: number; y2: number; color: Rgba}
): Pixels {
  const data = new Uint8ClampedArray(width * height * 4);

  for (let y = 0; y < height; y++) {
    for (let x = 0; x < width; x++) {
      const inRectangle =
        rectangle &&
        x >= rectangle.x1 &&
        x <= rectangle.x2 &&
        y >= rectangle.y1 &&
        y <= rectangle.y2;
      const [r, g, b, a = 255] = inRectangle ? rectangle.color : background;
      data.set([r, g, b, a], (y * width + x) * 4);
    }
  }

  return {data, width, height};
}

const red: Rgba = [200, 30, 40];
const blue: Rgba = [30, 80, 200];

describe('sampleImageColors', () => {
  it('samples a single-color image', () => {
    expect(sampleImageColors(pixels(100, 100, red))).toEqual({
      dominant: '#c81e28',
      grid: Array.from({length: 3}, () => Array(4).fill('#c81e28')),
    });
  });

  it('averages each region of the image into the grid', () => {
    const split = pixels(100, 75, red, {
      x1: 50,
      y1: 0,
      x2: 99,
      y2: 74,
      color: blue,
    });

    expect(sampleImageColors(split).grid).toEqual(
      Array.from({length: 3}, () => [
        '#c81e28',
        '#c81e28',
        '#1e50c8',
        '#1e50c8',
      ])
    );
  });

  it('averages each region in linear light', () => {
    const stripes = pixels(8, 3, [0, 0, 0]);
    for (let x = 1; x < 8; x += 2) {
      for (let y = 0; y < 3; y++) {
        stripes.data.set([255, 255, 255, 255], (y * 8 + x) * 4);
      }
    }

    expect(sampleImageColors(stripes).grid).toEqual(
      Array.from({length: 3}, () => Array(4).fill('#bcbcbc'))
    );
  });

  it('splits pixels that straddle regions by how much of them each covers', () => {
    const image = pixels(6, 3, [0, 0, 255], {
      x1: 0,
      y1: 0,
      x2: 0,
      y2: 2,
      color: [255, 0, 0],
    });

    expect(sampleImageColors(image).grid[0]).toEqual([
      '#d5009c',
      '#0000ff',
      '#0000ff',
      '#0000ff',
    ]);
  });

  it('keeps transparent regions transparent in the grid', () => {
    const halfClear = pixels(100, 75, red, {
      x1: 50,
      y1: 0,
      x2: 99,
      y2: 74,
      color: [0, 0, 0, 0],
    });

    expect(sampleImageColors(halfClear).grid).toEqual(
      Array.from({length: 3}, () => [
        '#c81e28',
        '#c81e28',
        '#00000000',
        '#00000000',
      ])
    );
  });

  it('turns the grid on its side for portrait images', () => {
    expect(sampleImageColors(pixels(75, 100, red)).grid).toEqual(
      Array.from({length: 4}, () => Array(3).fill('#c81e28'))
    );
  });

  it('passes over a white backdrop for the dominant color in front of it', () => {
    const image = pixels(100, 100, [255, 255, 255], {
      x1: 30,
      y1: 30,
      x2: 60,
      y2: 60,
      color: blue,
    });

    expect(sampleImageColors(image).dominant).toBe('#1e50c8');
  });

  it('passes over a black backdrop for the dominant color in front of it', () => {
    const image = pixels(100, 100, [0, 0, 0], {
      x1: 0,
      y1: 0,
      x2: 20,
      y2: 20,
      color: [240, 140, 20],
    });

    expect(sampleImageColors(image).dominant).toBe('#f08c14');
  });

  it('falls back to a white dominant color when there’s nothing else', () => {
    expect(sampleImageColors(pixels(100, 100, [255, 255, 255])).dominant).toBe(
      '#ffffff'
    );
  });

  it('has no dominant color for a fully transparent image', () => {
    expect(sampleImageColors(pixels(10, 10, [0, 0, 0, 0])).dominant).toBeNull();
  });
});

describe('readImageColors', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('leaves files that aren’t raster images to the server', async () => {
    const createImageBitmap = vi.fn();
    vi.stubGlobal('createImageBitmap', createImageBitmap);

    await expect(
      readImageColors(new File(['%PDF'], 'doc.pdf', {type: 'application/pdf'}))
    ).resolves.toBeNull();
    await expect(
      readImageColors(new File(['<svg/>'], 'logo.svg', {type: 'image/svg+xml'}))
    ).resolves.toBeNull();
    expect(createImageBitmap).not.toHaveBeenCalled();
  });

  it('leaves images the browser can’t decode to the server', async () => {
    vi.stubGlobal(
      'createImageBitmap',
      vi
        .fn()
        .mockRejectedValue(new DOMException('Unsupported', 'InvalidStateError'))
    );

    await expect(
      readImageColors(new File(['heic'], 'photo.heic', {type: 'image/heic'}))
    ).resolves.toBeNull();
  });
});
