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
 * `left`/`top` are resolved against. For an absolutely-positioned element that
 * is the padding box of its nearest positioned ancestor (`offsetParent`),
 * adjusted for that ancestor's border (`clientLeft/Top`) and scroll. When there
 * is no positioned ancestor (`offsetParent` is null, `<body>`, or `<html>`) the
 * containing block is the initial containing block at the page origin, so this
 * returns `{0, 0}` — matching the legacy assumption that draggable elements
 * were positioned relative to `<body>` (so `Modal` etc. are unchanged).
 */
function containingBlockOrigin(el: HTMLElement): {left: number; top: number} {
  const parent = el.offsetParent as HTMLElement | null;
  if (
    !parent ||
    parent === document.body ||
    parent === document.documentElement
  ) {
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
