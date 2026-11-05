import type {ReactiveController, ReactiveControllerHost} from 'lit';

export interface ReorderableItem {
  /** The element that moves, and that items are dropped beside. */
  element: HTMLElement;
  /** Where a drag starts. Defaults to the whole element. */
  handle?: HTMLElement | null;
}

export interface ReorderableControllerOptions {
  /** Called when an item is dropped somewhere new. */
  onReorder: (startIndex: number, finishIndex: number) => void;
}

/** How far the pointer moves before a press on a handle becomes a drag. */
const DRAG_THRESHOLD = 4;

/**
 * Pointer-driven drag reordering for a Lit host's vertical list.
 *
 * Unlike native HTML drag and drop, this works on elements inside a shadow
 * root, where the browser retargets `dragstart` and hit-testing to the host.
 * A press on a handle only becomes a drag once the pointer moves, so a handle
 * that's also a button, like `craft-reorder-button`, still opens on a click.
 * Escape cancels a drag. Touch input is left alone: dragging is only ever a
 * shortcut, so give users another way to move items too.
 *
 * The host hands over its items with {@link setItems} after each render, and
 * reads {@link draggingIndex} and {@link dropIndex} to draw them.
 */
export class ReorderableController implements ReactiveController {
  /** The index of the item being dragged, if any. */
  draggingIndex: number | null = null;

  /**
   * The gap the dragged item would land in, from 0 (before the first item) to
   * the item count (after the last), or null when it would stay put.
   */
  dropIndex: number | null = null;

  readonly #host: ReactiveControllerHost;
  readonly #options: ReorderableControllerOptions;
  #items: ReorderableItem[] = [];
  #cleanup: Array<() => void> = [];
  #press: {
    index: number;
    pointerId: number;
    handle: HTMLElement;
    x: number;
    y: number;
  } | null = null;

  constructor(
    host: ReactiveControllerHost,
    options: ReorderableControllerOptions
  ) {
    this.#host = host;
    this.#options = options;
    host.addController(this);
  }

  hostDisconnected(): void {
    this.#cancel();
    this.setItems([]);
  }

  /** Registers the items that can be dragged, in order. Pass `[]` to stop. */
  setItems(items: ReorderableItem[]): void {
    for (const cleanup of this.#cleanup) {
      cleanup();
    }
    this.#items = items;
    this.#cleanup = items.map(({element, handle}, index) => {
      const target = handle ?? element;
      const onPointerDown = (event: PointerEvent) =>
        this.#onPointerDown(event, index, target);
      target.addEventListener('pointerdown', onPointerDown);

      return () => target.removeEventListener('pointerdown', onPointerDown);
    });
  }

  #onPointerDown(
    event: PointerEvent,
    index: number,
    handle: HTMLElement
  ): void {
    if (
      event.button !== 0 ||
      event.pointerType === 'touch' ||
      this.#press ||
      handle.matches('[disabled]')
    ) {
      return;
    }

    this.#press = {
      index,
      pointerId: event.pointerId,
      handle,
      x: event.clientX,
      y: event.clientY,
    };
    window.addEventListener('pointermove', this.#onPointerMove);
    window.addEventListener('pointerup', this.#onPointerUp);
    window.addEventListener('pointercancel', this.#onPointerCancel);
    window.addEventListener('keydown', this.#onKeyDown, true);
  }

  #onPointerMove = (event: PointerEvent): void => {
    const press = this.#press;
    if (!press || event.pointerId !== press.pointerId) {
      return;
    }

    if (this.draggingIndex === null) {
      if (
        Math.hypot(event.clientX - press.x, event.clientY - press.y) <
        DRAG_THRESHOLD
      ) {
        return;
      }

      this.#setState(press.index, null);
    }

    event.preventDefault();
    this.#setState(this.draggingIndex, this.#gapAt(event));
  };

  #onPointerUp = (event: PointerEvent): void => {
    const press = this.#press;
    if (!press || event.pointerId !== press.pointerId) {
      return;
    }

    const start = this.draggingIndex;
    const gap = this.dropIndex;
    const dragged = start !== null;
    this.#cancel();

    if (!dragged) {
      return;
    }

    // The drag ends in a click on the handle, which mustn't also open it.
    const swallowClick = (click: Event) => {
      click.preventDefault();
      click.stopImmediatePropagation();
    };
    press.handle.addEventListener('click', swallowClick, {
      capture: true,
      once: true,
    });
    setTimeout(() =>
      press.handle.removeEventListener('click', swallowClick, true)
    );

    if (gap !== null) {
      const finish = gap > start ? gap - 1 : gap;
      if (finish !== start) {
        this.#options.onReorder(start, finish);
      }
    }
  };

  #onPointerCancel = (event: PointerEvent): void => {
    if (event.pointerId === this.#press?.pointerId) {
      this.#cancel();
    }
  };

  #onKeyDown = (event: KeyboardEvent): void => {
    if (event.key === 'Escape' && this.draggingIndex !== null) {
      event.preventDefault();
      event.stopPropagation();
      this.#cancel();
    }
  };

  #cancel(): void {
    this.#press = null;
    window.removeEventListener('pointermove', this.#onPointerMove);
    window.removeEventListener('pointerup', this.#onPointerUp);
    window.removeEventListener('pointercancel', this.#onPointerCancel);
    window.removeEventListener('keydown', this.#onKeyDown, true);
    this.#setState(null, null);
  }

  /**
   * The gap under the pointer: before the first item whose middle it hasn't
   * reached, or after the last. A gap that would leave the dragged item where
   * it is counts as none.
   */
  #gapAt(event: PointerEvent): number | null {
    let gap = this.#items.length;

    for (const [index, {element}] of this.#items.entries()) {
      const rect = element.getBoundingClientRect();
      if (event.clientY < rect.top + rect.height / 2) {
        gap = index;
        break;
      }
    }

    const start = this.draggingIndex;
    return start !== null && (gap === start || gap === start + 1) ? null : gap;
  }

  #setState(draggingIndex: number | null, dropIndex: number | null): void {
    if (draggingIndex === this.draggingIndex && dropIndex === this.dropIndex) {
      return;
    }

    this.draggingIndex = draggingIndex;
    this.dropIndex = dropIndex;
    this.#host.requestUpdate();
  }
}
