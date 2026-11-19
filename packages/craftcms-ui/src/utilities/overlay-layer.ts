import {css} from 'lit';
import type {Modifier, Placement} from '@popperjs/core';
import {viewportEscapingModifiers} from './overlay-position.js';

/** How far a positioned overlay keeps from the viewport's edge when it flips. */
export const VIEWPORT_MARGIN = 16;

export const supportsPopover =
  typeof HTMLElement !== 'undefined' &&
  typeof HTMLElement.prototype.showPopover === 'function';

/**
 * Shows an overlay's layer element: in the top layer where the Popover API
 * exists, and above the page's stacking contexts where it doesn't.
 */
export function showLayer(layer: HTMLElement): void {
  layer.toggleAttribute('data-open', true);

  if (supportsPopover && !layer.matches(':popover-open')) {
    layer.showPopover();
  }
}

export function hideLayer(layer: HTMLElement): void {
  if (supportsPopover && layer.matches(':popover-open')) {
    layer.hidePopover();
  }

  layer.removeAttribute('data-open');
}

/**
 * A placement as it reads in `element`'s direction. Popper's placements are
 * physical, so in a right-to-left layout an overlay placed `bottom-start`
 * would still line up with its anchor's left edge and open out to the right.
 * This mirrors it: `-start` and `-end` swap on the top and bottom, and
 * `left` and `right` swap sides, keeping their alignment along the side.
 */
export function placementForDirection<T extends string>(
  placement: T,
  element: Element
): Placement {
  if (getComputedStyle(element).direction !== 'rtl') {
    return placement as unknown as Placement;
  }

  const [side, alignment] = placement.split('-') as [string, string?];

  if (side === 'left' || side === 'right') {
    const mirrored = side === 'left' ? 'right' : 'left';
    return (alignment ? `${mirrored}-${alignment}` : mirrored) as Placement;
  }

  if (alignment === 'start' || alignment === 'end') {
    return `${side}-${alignment === 'start' ? 'end' : 'start'}` as Placement;
  }

  return placement as unknown as Placement;
}

/** Popper modifiers shared by the overlays, `distance` px from their anchor. */
export function overlayModifiers(
  distance: number
): Partial<Modifier<string, object>>[] {
  return [
    {name: 'preventOverflow', options: {padding: 8}},
    {name: 'flip', options: {padding: VIEWPORT_MARGIN}},
    {name: 'offset', options: {offset: [0, distance]}},
    {name: 'arrow', enabled: false},
    ...(viewportEscapingModifiers() as Partial<Modifier<string, object>>[]),
  ];
}

/**
 * For a `.overlay-layer` element given `popover="manual"` where supported.
 * The resets undo the UA's popover styles, which would center it and draw a
 * box; the layer is only a carrier for the positioned element inside it.
 */
export const layerStyles = css`
  .overlay-layer {
    position: fixed;
    inset: auto;
    z-index: var(--c-layer-popover, 9999);
    margin: 0;
    padding: 0;
    border: 0;
    background: none;
    color: inherit;
    overflow: visible;
    width: auto;
    height: auto;
  }

  .overlay-layer:not([data-open]) {
    display: none;
  }
`;
