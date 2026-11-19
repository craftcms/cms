import {expect} from 'vite-plus/test';

/**
 * Paints `color` over `backdrop` and returns the resulting pixel, so a
 * translucent colour is measured as it is actually seen.
 */
function paint(color: string, backdrop = '#fff'): [number, number, number] {
  const context = document.createElement('canvas').getContext('2d')!;
  context.fillStyle = backdrop;
  context.fillRect(0, 0, 1, 1);
  context.fillStyle = color;
  context.fillRect(0, 0, 1, 1);
  const [red, green, blue] = context.getImageData(0, 0, 1, 1).data;

  return [red!, green!, blue!];
}

function luminance([red, green, blue]: [number, number, number]): number {
  const [r, g, b] = [red, green, blue].map((channel) => {
    const value = channel / 255;

    return value <= 0.03928 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
  });

  return 0.2126 * r! + 0.7152 * g! + 0.0722 * b!;
}

/**
 * The WCAG contrast ratio between `foreground` and `background`, with the
 * foreground composited over the background first, and a translucent
 * background composited over `backdrop`.
 */
export function contrast(
  foreground: string,
  background: string,
  backdrop = '#fff'
): number {
  const back = paint(background, backdrop);
  const front = paint(foreground, `rgb(${back.join(', ')})`);
  const [lighter, darker] = [luminance(front), luminance(back)].sort(
    (a, b) => b - a
  );

  return (lighter! + 0.05) / (darker! + 0.05);
}

/**
 * What's painted behind `element`: every background on its way up the
 * flattened tree, composited over white, as an `rgb()` string.
 */
export function backdropOf(element: Element): string {
  const layers: string[] = [];

  for (let node = parentOf(element); node; node = parentOf(node)) {
    layers.unshift(getComputedStyle(node).backgroundColor);
  }

  let backdrop = '#fff';

  for (const layer of layers) {
    backdrop = over(layer, backdrop);
  }

  return backdrop;
}

/** The element `node` is painted inside: its slot, its parent or its host. */
function parentOf(node: Element): Element | null {
  return (
    node.assignedSlot ??
    node.parentElement ??
    ((node.getRootNode() as ShadowRoot).host || null)
  );
}

/** `color` painted over `backdrop`, as an `rgb()` string. */
function over(color: string, backdrop: string): string {
  return `rgb(${paint(color, backdrop).join(', ')})`;
}

/** Whether `color` paints anything at all. */
function isPainted(color: string): boolean {
  return (
    paint(color, '#000').some((channel) => channel !== 0) ||
    paint(color, '#fff').some((channel) => channel !== 255)
  );
}

/**
 * Hovers the element that `mount` returns on each surface, in each theme, and
 * expects its text to keep `ratio` contrast against what's then painted behind
 * it: its own background and its `::before`, over the surfaces it sits on.
 *
 * It fails if hovering paints no fill, so a fill that stops applying (a
 * renamed token, a hover media query that doesn't match) can't pass by
 * measuring the text against the bare surface.
 */
export async function expectReadableOnHover(
  mount: (surface: HTMLElement) => Promise<HTMLElement>,
  {
    themes = ['light', 'dark'],
    surfaces = ['default'],
    ratio = 4.5,
  }: {themes?: string[]; surfaces?: string[]; ratio?: number} = {}
): Promise<void> {
  const {userEvent} = await import('vite-plus/test/browser');
  const failures: string[] = [];

  for (const theme of themes) {
    for (const surface of surfaces) {
      const wrapper = document.createElement('div');
      wrapper.dataset.theme = theme;
      wrapper.style.backgroundColor = `var(--c-surface-${surface})`;
      wrapper.style.padding = '2rem';
      document.body.append(wrapper);

      const target = await mount(wrapper);
      const label = `${theme} theme, surface-${surface}`;
      const fill = () => {
        const own = getComputedStyle(target).backgroundColor;
        const before = getComputedStyle(target, '::before');
        return before.content === 'none'
          ? [own]
          : [own, before.backgroundColor];
      };

      // A test id gives the hover a locator that doesn't depend on the
      // target's accessible name, which Vitest and Playwright can work out
      // differently (a tab's hidden bold copy of its label, for one).
      target.dataset.testid ??= 'hover-target';

      await userEvent.hover(target);
      await expect
        .poll(() => fill().some(isPainted), {
          message: `${label}: hovering paints no fill`,
        })
        .toBe(true);

      let backdrop = backdropOf(target);
      const layers = fill();
      for (const layer of layers.slice(0, -1)) {
        backdrop = over(layer, backdrop);
      }
      const measured = contrast(
        getComputedStyle(target).color,
        layers.at(-1)!,
        backdrop
      );

      if (measured < ratio) {
        failures.push(`${label}: ${measured.toFixed(2)}:1`);
      }

      await userEvent.unhover(target);
      wrapper.remove();
    }
  }

  // One assertion over every case, so a failure lists each one below `ratio`.
  expect(failures, `text below ${ratio}:1 on hover`).toEqual([]);
}

/** Loads the document-level token stylesheets the components read. */
export async function loadTokens(): Promise<void> {
  await import('../src/styles/shared/color-palette.css');
  await import('../src/styles/shared/colorable.css');
  await import('../src/styles/shared/variables.css');
  await import('../src/styles/shared/tokens.css');
}
