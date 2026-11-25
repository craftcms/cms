import {page} from 'vite-plus/test/browser';
import type {StatusShape} from '../src/constants/statuses';
import {contrast} from './contrast';

/**
 * Points inside a dot's box, as fractions of its width and height, and
 * whether each shape covers them. Every shape covers a different set, so the
 * set of points a dot hit-tests on names its shape.
 */
const probes: Array<[x: number, y: number, covers: StatusShape[]]> = [
  [0.06, 0.2, ['square']],
  [0.08, 0.5, ['circle', 'square', 'diamond']],
  [0.13, 0.92, ['square', 'triangle']],
  [0.2, 0.2, ['circle', 'square']],
];

/**
 * The shape a dot is drawn as, read from where it hit-tests, which follows its
 * rendered `border-radius` and `clip-path`. Draw the dot at least 40px wide.
 */
export function renderedShape(dot: HTMLElement): StatusShape | null {
  const root = dot.getRootNode() as ShadowRoot;
  const rect = dot.getBoundingClientRect();
  if (rect.width < 40) {
    throw new Error(`The dot is ${rect.width}px wide, too small to probe.`);
  }
  const hits = probes.map(
    ([x, y]) =>
      root.elementFromPoint(
        rect.left + rect.width * x,
        rect.top + rect.height * y
      ) === dot
  );
  const shapes: StatusShape[] = ['circle', 'square', 'triangle', 'diamond'];

  return (
    shapes.find((shape) =>
      probes.every(([, , covers], i) => covers.includes(shape) === hits[i])
    ) ?? null
  );
}

/** Every pixel of a screenshot of `element`, to compare two renderings. */
export async function screenshotData(element: Element): Promise<string> {
  return (await page.screenshot({element, save: false})) as string;
}

/** Decodes a screenshot of `element` and returns a reader for its pixels. */
async function pixelsOf(element: Element) {
  const base64 = (await page.screenshot({element, save: false})) as string;
  const image = new Image();
  image.src = `data:image/png;base64,${base64}`;
  await image.decode();
  const canvas = document.createElement('canvas');
  canvas.width = image.naturalWidth;
  canvas.height = image.naturalHeight;
  const context = canvas.getContext('2d', {willReadFrequently: true})!;
  context.drawImage(image, 0, 0);

  return {
    width: canvas.width,
    height: canvas.height,
    at(x: number, y: number): string {
      const [r, g, b] = context.getImageData(x, y, 1, 1).data;
      return `rgb(${r}, ${g}, ${b})`;
    },
  };
}

/**
 * Walks the pixels from `from` toward `to` until the color leaves the
 * backdrop, and returns the best contrast in the few pixels after that: what's
 * painted along the shape's edge there.
 */
function edgeContrast(
  pixels: Awaited<ReturnType<typeof pixelsOf>>,
  from: [number, number],
  to: [number, number],
  backdrop: string
): number {
  const steps = Math.max(Math.abs(to[0] - from[0]), Math.abs(to[1] - from[1]));
  const point = (i: number) =>
    [
      Math.round(from[0] + ((to[0] - from[0]) * i) / steps),
      Math.round(from[1] + ((to[1] - from[1]) * i) / steps),
    ] as const;

  for (let i = 0; i <= steps; i++) {
    if (contrast(pixels.at(...point(i)), backdrop) > 1.05) {
      let best = 0;
      for (let j = i; j <= Math.min(steps, i + 4); j++) {
        best = Math.max(best, contrast(pixels.at(...point(j)), backdrop));
      }
      return best;
    }
  }

  return 1;
}

/**
 * How well a dot's outline stands out from what's behind it along its left,
 * right and bottom edges, read from a screenshot. Zoom the dot 4× (`zoom: 4`)
 * so its 1px outline is 4 pixels wide and anti-aliasing can't blur it. Its
 * box's top-left corner must lie outside the shape, as for every status shape.
 */
export async function outlineContrast(
  element: Element
): Promise<{left: number; right: number; base: number}> {
  const pixels = await pixelsOf(element);
  const {width: w, height: h} = pixels;
  const backdrop = pixels.at(1, 1);
  const middle = Math.round(h / 2);

  return {
    left: edgeContrast(pixels, [0, middle], [w / 2, middle], backdrop),
    right: edgeContrast(pixels, [w - 1, middle], [w / 2, middle], backdrop),
    base: edgeContrast(
      pixels,
      [Math.round(w / 2), h - 1],
      [Math.round(w / 2), h / 2],
      backdrop
    ),
  };
}
