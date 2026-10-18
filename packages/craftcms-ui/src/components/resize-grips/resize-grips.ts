import type {TemplateResult} from 'lit';
import {html, LitElement} from 'lit';
import {property, state} from 'lit/decorators.js';
import {t} from '@src/utilities/translate.js';
import styles from './resize-grips.styles.js';

type Axis = 'x' | 'y' | 'both';

export interface ResizeGripsSize {
  /** Width in px, or `null` once the size is reset to the box's own. */
  width: number | null;
  /** Height in px, or `null` once the size is reset to the box's own. */
  height: number | null;
}

/**
 * Resize grips for a centered box, such as a modal.
 *
 * Fills its positioned parent (the box) and draws a grip along the box's
 * inline-end and block-end edges, plus a smaller one in their corner. Each
 * only shows on hover or while dragging. Dragging an edge resizes one axis,
 * and the corner both. The box is centered, so both of its edges move by the
 * pointer's travel.
 *
 * Only the corner grip takes focus: arrow keys resize the box (Shift for a
 * bigger step), and Enter puts it back to its own size, as does
 * double-clicking any grip.
 *
 * The grips don't size the box themselves. They report each new size with a
 * `craft-resize` event, and the box's owner applies it. Sizes are kept within
 * the box's computed `max-width`/`max-height` and `min-size`, and are clamped
 * again when the window resizes.
 *
 * @example <craft-resize-grips .target=${box}></craft-resize-grips>
 *
 * @fires craft-resize - The box has a new size. `detail` is a
 *   {@link ResizeGripsSize}; both are `null` once it's reset.
 */
export default class CraftResizeGrips extends LitElement {
  static override styles = [styles];

  /** The box being resized. Defaults to the host's parent element. */
  @property({attribute: false}) target: HTMLElement | null = null;

  /** Accessible name for the corner grip. Defaults to a translated "Resize". */
  @property() label = '';

  /** Smallest size a drag or key press can leave the box at, in px. */
  @property({type: Number, attribute: 'min-size'}) minSize = 200;

  /** Arrow-key increment, in px. */
  @property({type: Number}) step = 16;

  /** Shift + arrow-key increment, in px. */
  @property({type: Number, attribute: 'large-step'}) largeStep = 64;

  /** The axis being dragged, if any. */
  @state() private dragging: Axis | null = null;

  #width: number | null = null;

  #height: number | null = null;

  #drag: {
    axis: Axis;
    pointerId: number;
    startX: number;
    startY: number;
    startWidth: number;
    startHeight: number;
    sign: number;
  } | null = null;

  override connectedCallback(): void {
    super.connectedCallback();
    window.addEventListener('resize', this.#onWindowResize);
  }

  override disconnectedCallback(): void {
    super.disconnectedCallback();
    window.removeEventListener('resize', this.#onWindowResize);
  }

  /** Puts the box back to its own size. */
  reset(): void {
    this.#width = null;
    this.#height = null;
    this.#emit();
  }

  get #box(): HTMLElement | null {
    return this.target ?? this.parentElement;
  }

  get #rtl(): boolean {
    const box = this.#box;

    return box ? getComputedStyle(box).direction === 'rtl' : false;
  }

  #measured(): {width: number; height: number} {
    const rect = this.#box?.getBoundingClientRect();

    return {
      width: Math.round(rect?.width ?? 0),
      height: Math.round(rect?.height ?? 0),
    };
  }

  /** The largest the box may be, from its computed max size. */
  #bounds(): {width: number; height: number} {
    const box = this.#box;
    const style = box ? getComputedStyle(box) : null;

    return {
      width: parseFloat(style?.maxWidth ?? '') || window.innerWidth,
      height: parseFloat(style?.maxHeight ?? '') || window.innerHeight,
    };
  }

  #clamp(value: number, max: number): number {
    return Math.round(Math.min(Math.max(value, this.minSize), max));
  }

  #resize(width: number | null, height: number | null): void {
    const bounds = this.#bounds();
    this.#width = width === null ? null : this.#clamp(width, bounds.width);
    this.#height = height === null ? null : this.#clamp(height, bounds.height);
    this.#emit();
  }

  #emit(): void {
    this.dispatchEvent(
      new CustomEvent<ResizeGripsSize>('craft-resize', {
        detail: {width: this.#width, height: this.#height},
        bubbles: true,
        composed: true,
      })
    );
  }

  #onWindowResize = (): void => {
    if (this.#width !== null || this.#height !== null) {
      this.#resize(this.#width, this.#height);
    }
  };

  #onPointerDown(event: PointerEvent, axis: Axis): void {
    if (event.button !== 0) {
      return;
    }

    event.preventDefault();
    const grip = event.currentTarget as HTMLElement;
    try {
      grip.setPointerCapture(event.pointerId);
    } catch {
      // Not an active pointer (a synthetic event, say): track it uncaptured.
    }
    const size = this.#measured();
    this.#drag = {
      axis,
      pointerId: event.pointerId,
      startX: event.clientX,
      startY: event.clientY,
      // Builds on a size already set, which a capped box can render smaller.
      startWidth: this.#width ?? size.width,
      startHeight: this.#height ?? size.height,
      sign: this.#rtl ? -1 : 1,
    };
    this.dragging = axis;
  }

  #onPointerMove(event: PointerEvent): void {
    const drag = this.#drag;

    if (!drag || event.pointerId !== drag.pointerId) {
      return;
    }

    const dx = (event.clientX - drag.startX) * drag.sign;
    const dy = event.clientY - drag.startY;

    this.#resize(
      drag.axis === 'y' ? this.#width : drag.startWidth + dx * 2,
      drag.axis === 'x' ? this.#height : drag.startHeight + dy * 2
    );
  }

  #onPointerUp(event: PointerEvent): void {
    if (!this.#drag || event.pointerId !== this.#drag.pointerId) {
      return;
    }

    const grip = event.currentTarget as HTMLElement;
    if (grip.hasPointerCapture?.(event.pointerId)) {
      grip.releasePointerCapture(event.pointerId);
    }
    this.#drag = null;
    this.dragging = null;
  }

  #onKeydown(event: KeyboardEvent): void {
    const increment = event.shiftKey ? this.largeStep : this.step;
    const size = this.#measured();
    const width = this.#width ?? size.width;
    const height = this.#height ?? size.height;
    const inline = this.#rtl ? -increment : increment;

    switch (event.key) {
      case 'ArrowLeft':
        this.#resize(width - inline, height);
        break;
      case 'ArrowRight':
        this.#resize(width + inline, height);
        break;
      case 'ArrowUp':
        this.#resize(width, height - increment);
        break;
      case 'ArrowDown':
        this.#resize(width, height + increment);
        break;
      case 'Enter':
        this.reset();
        break;
      default:
        return;
    }

    event.preventDefault();
  }

  #gripEvents(axis: Axis) {
    return {
      down: (event: PointerEvent) => this.#onPointerDown(event, axis),
      move: (event: PointerEvent) => this.#onPointerMove(event),
      up: (event: PointerEvent) => this.#onPointerUp(event),
    };
  }

  override render(): TemplateResult {
    const inline = this.#gripEvents('x');
    const block = this.#gripEvents('y');
    const corner = this.#gripEvents('both');

    return html`
      <div
        class="grip grip--inline ${this.dragging === 'x' ? 'grip--active' : ''}"
        part="grip grip-inline"
        aria-hidden="true"
        @pointerdown=${inline.down}
        @pointermove=${inline.move}
        @pointerup=${inline.up}
        @pointercancel=${inline.up}
        @dblclick=${() => this.reset()}
      ></div>
      <div
        class="grip grip--block ${this.dragging === 'y' ? 'grip--active' : ''}"
        part="grip grip-block"
        aria-hidden="true"
        @pointerdown=${block.down}
        @pointermove=${block.move}
        @pointerup=${block.up}
        @pointercancel=${block.up}
        @dblclick=${() => this.reset()}
      ></div>
      <button
        type="button"
        class="grip grip--corner ${this.dragging === 'both'
          ? 'grip--active'
          : ''}"
        part="grip grip-corner"
        aria-label=${this.label || t('Resize')}
        @pointerdown=${corner.down}
        @pointermove=${corner.move}
        @pointerup=${corner.up}
        @pointercancel=${corner.up}
        @dblclick=${() => this.reset()}
        @keydown=${this.#onKeydown}
      ></button>
    `;
  }
}

if (!customElements.get('craft-resize-grips')) {
  customElements.define('craft-resize-grips', CraftResizeGrips);
}

declare global {
  interface HTMLElementTagNameMap {
    'craft-resize-grips': CraftResizeGrips;
  }
}
