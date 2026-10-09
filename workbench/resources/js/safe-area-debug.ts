import {
  flyoutHoverIntent,
  type SafeArea,
} from '@craftcms/ui/utilities/hover-intent';

/**
 * Draws the nav flyouts' safe areas over the page.
 *
 * Green while the pointer is inside the triangle, amber once it's outside,
 * dashed grey once the grace has run out. The last triangle lingers, fading,
 * so there's time to look at it.
 *
 * On by default; `localStorage.setItem('workbench.safeAreas', 'off')` hides it.
 */
const STORAGE_KEY = 'workbench.safeAreas';
const LINGER = 1500;
const SVG_NS = 'http://www.w3.org/2000/svg';

function enabled(): boolean {
  try {
    return localStorage.getItem(STORAGE_KEY) !== 'off';
  } catch {
    return true;
  }
}

function svg<K extends keyof SVGElementTagNameMap>(
  tag: K,
  attributes: Record<string, string | number>
): SVGElementTagNameMap[K] {
  const element = document.createElementNS(SVG_NS, tag);

  for (const [name, value] of Object.entries(attributes)) {
    element.setAttribute(name, String(value));
  }

  return element;
}

function colorFor(area: SafeArea): string {
  if (area.graceRemaining <= 0) {
    return '#71717a';
  }

  return area.pointerInside ? '#10b981' : '#f59e0b';
}

function draw(canvas: SVGSVGElement, areas: SafeArea[]): void {
  canvas.replaceChildren();

  for (const area of areas) {
    const [exit] = area.corners;
    const color = colorFor(area);

    canvas.append(
      svg('polygon', {
        points: area.corners.map(({x, y}) => `${x},${y}`).join(' '),
        fill: color,
        'fill-opacity': 0.2,
        stroke: color,
        'stroke-width': 1.5,
        'stroke-dasharray': area.graceRemaining > 0 ? 'none' : '4 3',
      }),
      svg('circle', {cx: exit.x, cy: exit.y, r: 4, fill: color})
    );

    const label = svg('text', {
      x: exit.x + 8,
      y: exit.y - 8,
      fill: color,
      'font-size': 11,
      'font-family': 'ui-monospace, monospace',
    });
    label.textContent = `${Math.round(area.graceRemaining)}ms`;
    canvas.append(label);
  }
}

function mount(): void {
  // A popover, because the flyouts are: nothing else draws over the top layer.
  const host = document.createElement('div');
  host.setAttribute('popover', 'manual');
  host.setAttribute('aria-hidden', 'true');
  host.style.cssText =
    'inset:0;width:100vw;height:100vh;margin:0;padding:0;border:0;background:none;overflow:visible;pointer-events:none;';

  const canvas = svg('svg', {width: '100%', height: '100%'});
  canvas.style.overflow = 'visible';
  host.append(canvas);
  document.body.append(host);

  let last: SafeArea[] = [];
  let lastSeenAt = 0;
  let showing = false;

  const frame = (): void => {
    const areas = enabled() ? flyoutHoverIntent.safeAreas() : [];
    const now = performance.now();

    if (areas.length > 0) {
      // Re-shown each time an area appears, so it lands above the flyout
      // that just opened.
      if (!showing) {
        host.hidePopover();
        host.showPopover();
        showing = true;
      }

      last = areas;
      lastSeenAt = now;
      host.style.opacity = '1';
      draw(canvas, areas);
    } else if (showing) {
      const faded = (now - lastSeenAt) / LINGER;

      if (faded >= 1) {
        host.hidePopover();
        showing = false;
      } else {
        host.style.opacity = String(1 - faded);
        draw(
          canvas,
          last.map((area) => ({...area, graceRemaining: 0}))
        );
      }
    }

    requestAnimationFrame(frame);
  };

  requestAnimationFrame(frame);
}

mount();
