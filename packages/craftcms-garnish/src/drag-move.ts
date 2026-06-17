/**
 * DragMove — drag-to-move helper.
 *
 * The legacy `DragMove` is a ~15-line subclass of `BaseDrag`, which updates an
 * element's `left`/`top` as the pointer moves. `BaseDrag` (~580 lines) is
 * intentionally NOT part of the modern core for the vertical-slice PoC (doc 03
 * §2/§5: Tier 2), so there is nothing to subclass yet.
 *
 * Rather than ship a half-built dragger, this is a clearly-marked placeholder.
 * `Modal` defaults `draggable:false`/`resizable:false` and throws a descriptive
 * error if either is enabled, so no consumer path silently relies on this.
 *
 * When `BaseDrag` lands, replace this with the real `class DragMove extends
 * BaseDrag` and remove the throwing constructor.
 */

export class DragMove {
  constructor() {
    throw new Error(
      'Garnish.DragMove is not yet supported in the modern build: BaseDrag is out of PoC scope. ' +
        'Modal’s `draggable`/`resizable` options remain disabled until BaseDrag is ported.'
    );
  }
}

export default DragMove;
