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

export {type BaseDragSettings};

/**
 * Drag-to-move helper: a {@link BaseDrag} subclass whose only job is to set the
 * dragged element's `left`/`top` so it follows the cursor. Used by `Modal`'s
 * `draggable` option.
 */
export class DragMove extends BaseDrag {
  override onDrag(): void {
    if (this.$targetItem) {
      this.$targetItem.style.left = `${this.mouseX! - this.mouseOffsetX!}px`;
      this.$targetItem.style.top = `${this.mouseY! - this.mouseOffsetY!}px`;
    }

    super.onDrag();
  }
}

export default DragMove;
