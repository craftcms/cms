/**
 * DragMove — trivial `BaseDrag` subclass that positions the dragged element's
 * top-left at the cursor (minus the grab offset) on each drag frame.
 *
 * The legacy `DragMove.onDrag` *replaced* `BaseDrag.onDrag` and did not call
 * super, so it never emitted the `drag` event. We call `super.onDrag()` here
 * (doc 07 §5 Option A) so `DragMove` is strictly more capable — it also emits
 * `drag` / invokes `settings.onDrag`. The CSS write happens synchronously before
 * delegating to the RAF-deferred `super.onDrag()`.
 */

import {BaseDrag, type BaseDragSettings} from './drag/base-drag';
import {getOffset} from './utils/dom';

export {type BaseDragSettings};

/**
 * Page-coordinate origin of `el`'s containing block — the box its CSS
 * `left`/`top` are resolved against. We convert the pointer's PAGE coordinates
 * into this space before writing `left`/`top`:
 *
 * - **Positioned ancestor** (`offsetParent` is an element): the padding box of
 *   that ancestor, adjusted for its border (`clientLeft/Top`) and scroll.
 * - **`position: fixed`** (`offsetParent` is `null`): the containing block is
 *   the viewport, whose top-left sits at the page scroll offset — so the origin
 *   is `{scrollX, scrollY}`. Without this a fixed element (e.g. a `Modal`) jumps
 *   *down/right by the scroll amount* on the first drag frame.
 * - **`position: absolute` with no positioned ancestor** (`offsetParent` is
 *   `<body>`/`<html>`), or a detached/`display:none` element: the initial
 *   containing block anchored at the page origin → `{0, 0}`.
 */
function containingBlockOrigin(el: HTMLElement): {left: number; top: number} {
  const parent = el.offsetParent as HTMLElement | null;

  if (!parent) {
    // No offsetParent: position:fixed (containing block = the viewport), or a
    // detached/display:none element. Only fixed needs the scroll correction.
    if (
      typeof window.getComputedStyle === 'function' &&
      window.getComputedStyle(el).position === 'fixed'
    ) {
      return {left: window.scrollX, top: window.scrollY};
    }
    return {left: 0, top: 0};
  }

  if (parent === document.body || parent === document.documentElement) {
    // Absolute with no positioned ancestor → initial containing block at the
    // page origin (matches the legacy <body>-relative assumption).
    return {left: 0, top: 0};
  }

  const offset = getOffset(parent);
  return {
    left: offset.left + parent.clientLeft - parent.scrollLeft,
    top: offset.top + parent.clientTop - parent.scrollTop,
  };
}

/**
 * Drag-to-move helper: a {@link BaseDrag} subclass whose only job is to set the
 * dragged element's `left`/`top` so it follows the cursor. Used by `Modal`'s
 * `draggable` option.
 */
export class DragMove extends BaseDrag {
  override onDrag(): void {
    if (this.$targetItem) {
      // `mouseX/Y - mouseOffset` is the target position in PAGE coordinates.
      // Convert it into the element's own containing-block coordinates before
      // writing `left`/`top` — otherwise an element nested inside a positioned
      // container is offset by that container's page position and flies
      // off-screen the moment the drag starts.
      const origin = containingBlockOrigin(this.$targetItem);
      this.$targetItem.style.left = `${this.mouseX! - this.mouseOffsetX! - origin.left}px`;
      this.$targetItem.style.top = `${this.mouseY! - this.mouseOffsetY! - origin.top}px`;
    }

    super.onDrag();
  }
}

export default DragMove;
