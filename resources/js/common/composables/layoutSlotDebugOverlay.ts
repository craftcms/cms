/**
 * Labels for `LayoutSlotOutlet`'s debug mode, drawn in a fixed layer over the
 * page so they take no room in the layout.
 *
 * An outlet has no box of its own (`display: contents`), so a label can't be
 * positioned against it. Instead each frame measures the region's rendered
 * content and places its label just above the top-left corner. A label hides while
 * something else — a slideout, say — covers that corner, and a region with
 * nothing rendered gets no label.
 */

const LAYER_CLASS = 'layout-slot-debug-layer';
const LABEL_CLASS = 'layout-slot-debug-label';

const regions = new Map<HTMLElement, HTMLElement>();
let layer: HTMLElement | null = null;
let frame = 0;

/** Label `region` until the returned function is called. */
export function trackLayoutSlotDebugRegion(
  region: HTMLElement,
  name: string
): () => void {
  const label = document.createElement('span');
  label.className = LABEL_CLASS;
  label.textContent = name;
  label.hidden = true;
  regions.set(region, label);

  start().append(label);

  return () => {
    regions.get(region)?.remove();
    regions.delete(region);

    if (regions.size === 0) {
      stop();
    }
  };
}

function start(): HTMLElement {
  if (!layer) {
    layer = document.createElement('div');
    layer.className = LAYER_CLASS;
    layer.setAttribute('aria-hidden', 'true');
  }

  if (!layer.isConnected) {
    document.body.append(layer);
  }

  if (!frame) {
    frame = requestAnimationFrame(draw);
  }

  return layer;
}

function stop(): void {
  cancelAnimationFrame(frame);
  frame = 0;
  layer?.remove();
}

function draw(): void {
  const range = document.createRange();

  for (const [region, label] of regions) {
    range.selectNodeContents(region);
    const rect = Array.from(range.getClientRects()).find(
      (candidate) => candidate.width > 0 && candidate.height > 0
    );

    if (!rect || !isUncovered(region, rect)) {
      label.hidden = true;
      continue;
    }

    label.hidden = false;

    // Just above the region's outline, kept inside the viewport.
    const left = Math.max(
      0,
      Math.min(rect.left, window.innerWidth - label.offsetWidth)
    );
    const top = Math.max(
      0,
      Math.min(
        rect.top - label.offsetHeight,
        window.innerHeight - label.offsetHeight
      )
    );
    label.style.transform = `translate(${Math.round(left)}px, ${Math.round(top)}px)`;
  }

  frame = requestAnimationFrame(draw);
}

/**
 * Whether the region's top-left corner is actually on show. The hit lands in
 * the region itself, or in an ancestor where the corner falls in a gap
 * between its children; anything else is on top of it.
 */
function isUncovered(region: HTMLElement, rect: DOMRect): boolean {
  const hit = document.elementFromPoint(rect.left + 1, rect.top + 1);

  return Boolean(hit) && (region.contains(hit) || hit!.contains(region));
}
