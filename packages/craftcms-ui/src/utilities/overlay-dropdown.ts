import {createPopper, type Instance as PopperInstance} from '@popperjs/core';
import {hideLayer, overlayModifiers, showLayer} from './overlay-layer.js';

export interface DropdownParts {
  /** The `.overlay-layer` element, given `popover="manual"` where supported. */
  layer: HTMLElement;
  /** The element Popper positions, inside the layer. */
  pane: HTMLElement;
  /** What the pane opens against. */
  reference: Element;
}

/**
 * A pane that drops down from a field: below it unless there's no room, and
 * at least as wide as it. The pane sits inside its layer so the layer, not
 * a transformed ancestor, is what it's positioned against.
 */
export class Dropdown {
  #popper: PopperInstance | null = null;

  #parts?: DropdownParts;

  constructor(private readonly parts: () => DropdownParts | undefined) {}

  get shown(): boolean {
    return this.#popper !== null;
  }

  show(): void {
    const parts = this.parts();

    if (!parts || this.#popper) {
      return;
    }

    this.#parts = parts;
    showLayer(parts.layer);
    parts.pane.style.minWidth = `${parts.reference.getBoundingClientRect().width}px`;
    this.#popper = createPopper(parts.reference, parts.pane, {
      strategy: 'fixed',
      placement: 'bottom-start',
      modifiers: overlayModifiers(0),
    });
    this.#popper.forceUpdate();
  }

  hide(): void {
    if (!this.#parts) {
      return;
    }

    hideLayer(this.#parts.layer);
    this.#popper?.destroy();
    this.#popper = null;
    this.#parts = undefined;
  }

  /** Re-measures, for when the pane's content or the field has changed size. */
  update(): void {
    void this.#popper?.update();
  }
}
