import {cdp} from 'vite-plus/test/browser';

export interface Pixels {
  /** Device pixels per CSS px in the screenshot. */
  scale: number;
  /** The screenshot's size in device pixels. */
  width: number;
  height: number;
  /** The color at a device pixel, counted from the element's top left. */
  at(x: number, y: number): string;
  /** The color at a point in viewport CSS px. */
  atPoint(x: number, y: number): string;
}

/**
 * Decodes a screenshot of `element` at the device pixel ratio, to read the
 * colors it's drawn in.
 */
export async function pixels(element: Element): Promise<Pixels> {
  const frame = window.frameElement?.getBoundingClientRect();
  if (frame && Math.abs(frame.width - window.innerWidth) > 0.5) {
    throw new Error(
      'The test frame is scaled to fit the window. Set a viewport that fits it, such as 414×720.'
    );
  }
  const origin = element.getBoundingClientRect();
  // The clip is in the top page's document coordinates. That page can scroll
  // a few px, because the inline test frame leaves room below it, and a
  // scroll into view in any earlier test or file keeps it scrolled.
  const top = frame ? window.top! : window;
  const {data: base64} = (await cdp().send('Page.captureScreenshot', {
    format: 'png',
    clip: {
      x: (frame?.x ?? 0) + top.scrollX + origin.x,
      y: (frame?.y ?? 0) + top.scrollY + origin.y,
      width: origin.width,
      height: origin.height,
      scale: 1,
    },
  })) as {data: string};
  const image = new Image();
  image.src = `data:image/png;base64,${base64}`;
  await image.decode();

  const canvas = document.createElement('canvas');
  canvas.width = image.naturalWidth;
  canvas.height = image.naturalHeight;
  const context = canvas.getContext('2d', {willReadFrequently: true})!;
  context.drawImage(image, 0, 0);
  const {data} = context.getImageData(0, 0, canvas.width, canvas.height);
  const scale = canvas.width / origin.width;

  const at = (x: number, y: number) => {
    const i = (Math.floor(y) * canvas.width + Math.floor(x)) * 4;
    return `rgb(${data[i]}, ${data[i + 1]}, ${data[i + 2]})`;
  };

  return {
    scale,
    width: canvas.width,
    height: canvas.height,
    at,
    atPoint: (x, y) => at((x - origin.left) * scale, (y - origin.top) * scale),
  };
}

/**
 * The device pixel rows where a rounded outline drawn around `box` starts
 * and ends, for each column of the screenshot along its straight edges, in
 * `${top}-${bottom}` form: the first and last rows drawn in anything but the
 * `surface` color. An even outline gives one pair for every column.
 */
export function outlineRows(
  image: Pixels,
  origin: DOMRect,
  box: DOMRect,
  surface: string
): Set<string> {
  const probe = document.createElement('div');
  probe.style.borderRadius = 'var(--c-radius-sm)';
  document.body.append(probe);
  const radius = parseFloat(getComputedStyle(probe).borderTopLeftRadius);
  probe.remove();

  const toDevice = (value: number, start: number) =>
    (value - start) * image.scale;
  const left = Math.ceil(toDevice(box.left + radius + 1, origin.left));
  const right = Math.floor(toDevice(box.right - radius - 1, origin.left));
  const top = Math.max(0, Math.floor(toDevice(box.top - 2, origin.top)));
  const bottom = Math.min(
    image.height,
    Math.ceil(toDevice(box.bottom + 2, origin.top))
  );
  const rows = new Set<string>();

  for (let x = left; x < right; x++) {
    const drawn: number[] = [];
    for (let y = top; y < bottom; y++) {
      if (image.at(x, y) !== surface) {
        drawn.push(y);
      }
    }
    rows.add(`${drawn[0]}-${drawn.at(-1)}`);
  }

  return rows;
}
