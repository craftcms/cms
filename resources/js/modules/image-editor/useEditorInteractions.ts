import {ref} from 'vue';
import {
  getCursorForHandle,
  getDeltasFromDirection,
  hitTestHandle,
} from './geometry';
import type {
  CropHandle,
  FabricElementHandle,
  NudgeDirection,
  Point,
} from './types';
import type {Cropper, CropperFocusContext} from './useCropper';
import type {EditorAnnouncements} from './useEditorAnnouncements';
import type {EditorState} from './useEditorState';
import type {FocalPoint} from './useFocalPoint';
import type {ImageCanvas} from './useImageCanvas';

const NUDGE_KEYS: Record<string, NudgeDirection> = {
  ArrowUp: 'up',
  ArrowDown: 'down',
  ArrowLeft: 'left',
  ArrowRight: 'right',
};

/**
 * Which element the keyboard is currently acting on.
 *
 * Split out from the interactions themselves so `useCropper` can read it while
 * drawing focus rings without depending on the interaction layer, which in turn
 * depends on the cropper.
 */
export function useEditingState() {
  /** The handle whose edit button has focus, if any. */
  const focusedHandle = ref<FabricElementHandle | null>(null);
  /** The handle picked up for keyboard editing, if any. */
  const pickedHandle = ref<CropHandle | null>(null);
  const rectanglePickedUp = ref(false);
  const focalPickedUp = ref(false);
  /**
   * True while the pointer is driving the editor. Suppresses the keyboard focus
   * rings, which would otherwise fight the cursor for saying what's happening.
   */
  const dragEditMode = ref(true);

  function reset(): void {
    rectanglePickedUp.value = false;
    pickedHandle.value = null;
    focalPickedUp.value = false;
  }

  const focusContext: CropperFocusContext = {
    focusedHandle: () => focusedHandle.value,
    pickedHandle: () => pickedHandle.value,
    rectanglePickedUp: () => rectanglePickedUp.value,
    dragEditMode: () => dragEditMode.value,
  };

  return {
    focusedHandle,
    pickedHandle,
    rectanglePickedUp,
    focalPickedUp,
    dragEditMode,
    reset,
    focusContext,
  };
}

export type EditingState = ReturnType<typeof useEditingState>;

/**
 * Pointer and keyboard editing of the cropper and focal point.
 *
 * Pointer events are unified through the Pointer Events API, which covers mouse
 * and touch in one set of handlers — the legacy editor bound `mouse*` and
 * `touch*` pairs separately through jQuery.
 */
export function useEditorInteractions(
  state: EditorState,
  editing: EditingState,
  canvas: ImageCanvas,
  cropper: Cropper,
  focalPoint: FocalPoint,
  announcements: EditorAnnouncements
) {
  /** Bound to the editor element, so the cursor stays a template concern. */
  const cursor = ref('default');

  const previousPointer = ref<Point>({x: 0, y: 0});
  const pointerHandle = ref<CropHandle | null>(null);
  const focalClicked = ref(false);
  const cropperClicked = ref(false);
  const draggingFocal = ref(false);
  const draggingCropper = ref(false);
  const scalingCropper = ref(false);

  /** Pointer position relative to the cropping canvas's top-left. */
  function toCanvasPoint(event: PointerEvent): Point {
    const rect = state.croppingCanvasEl.value?.getBoundingClientRect();

    if (!rect) {
      return {x: 0, y: 0};
    }

    return {x: event.clientX - rect.left, y: event.clientY - rect.top};
  }

  /** Whether a point is within a center-origin object's bounds. */
  function isOver(
    point: Point,
    object: {left: number; top: number; width: number; height: number} | null
  ): boolean {
    if (!object) {
      return false;
    }

    return (
      point.x >= object.left - object.width / 2 &&
      point.x <= object.left + object.width / 2 &&
      point.y >= object.top - object.height / 2 &&
      point.y <= object.top + object.height / 2
    );
  }

  function updateCursor(point: Point): void {
    const handle = state.clipper.value
      ? hitTestHandle(point, state.clipper.value)
      : null;

    if (state.focalPoint.value && isOver(point, state.focalPoint.value)) {
      cursor.value = 'pointer';
    } else if (handle) {
      cursor.value = getCursorForHandle(handle);
    } else if (isOver(point, state.clipper.value)) {
      cursor.value = 'move';
    } else if (editing.focalPickedUp.value) {
      cursor.value = 'grabbing';
    } else {
      cursor.value = 'default';
    }
  }

  /** Whether a press is currently driving the cropper or focal point. */
  function isDragging(): boolean {
    return (
      focalClicked.value || cropperClicked.value || pointerHandle.value !== null
    );
  }

  function onPointerDown(event: PointerEvent): void {
    editing.dragEditMode.value = true;

    const point = toCanvasPoint(event);

    // Focal point wins over a resize handle, which wins over a drag.
    const overFocal =
      Boolean(state.focalPoint.value) && isOver(point, state.focalPoint.value);
    const handle = state.clipper.value
      ? hitTestHandle(point, state.clipper.value)
      : null;
    const overClipper = isOver(point, state.clipper.value);

    if (!overFocal && !handle && !overClipper) {
      return;
    }

    previousPointer.value = {x: event.clientX, y: event.clientY};

    if (overFocal) {
      focalClicked.value = true;
    } else if (handle) {
      pointerHandle.value = handle;
    } else {
      cropperClicked.value = true;
    }

    // Captured *after* the drag state is set: taking capture dispatches
    // boundary events, and `onPointerLeave` decides whether to bail by asking
    // `isDragging()` — which has to already be true by then.
    //
    // Capture itself is what lets a drag stray outside the editor and keep
    // delivering moves. Without it the gesture dies as the cursor crosses the
    // edge, which is most drags: the handles sit on the rectangle's border and
    // the rectangle starts at the image's.
    (event.currentTarget as Element | null)?.setPointerCapture?.(
      event.pointerId
    );
  }

  function onPointerMove(event: PointerEvent): void {
    const deltaX = event.clientX - previousPointer.value.x;
    const deltaY = event.clientY - previousPointer.value.y;

    if (editing.dragEditMode.value) {
      if (state.focalPoint.value && focalClicked.value) {
        draggingFocal.value = true;
        focalPoint.moveByDelta(deltaX, deltaY);
        focalPoint.storeFocalPointState();
        canvas.renderImage();
      } else if (cropperClicked.value || pointerHandle.value) {
        if (cropperClicked.value) {
          draggingCropper.value = true;
          // Silent: a drag reports continuously, which would flood the live
          // region. The keyboard path announces instead.
          cropper.moveByDelta(deltaX, deltaY, false);
        } else if (pointerHandle.value) {
          scalingCropper.value = true;

          // An edge handle only resizes along its own axis.
          const constrainedX =
            pointerHandle.value === 'b' || pointerHandle.value === 't'
              ? 0
              : deltaX;
          const constrainedY =
            pointerHandle.value === 'l' || pointerHandle.value === 'r'
              ? 0
              : deltaY;

          if (constrainedX !== 0 || constrainedY !== 0) {
            cropper.resizeByHandle(
              pointerHandle.value,
              {x: constrainedX, y: constrainedY},
              false
            );
          }
        }

        cropper.redrawElements();
        cropper.storeCropperState();
        canvas.renderCropper();
      }
    }

    updateCursor(toCanvasPoint(event));
    previousPointer.value = {x: event.clientX, y: event.clientY};
  }

  function onPointerUp(event: PointerEvent): void {
    const target = event.currentTarget as Element | null;

    if (target?.hasPointerCapture?.(event.pointerId)) {
      target.releasePointerCapture(event.pointerId);
    }

    if (focalClicked.value) {
      // A click without a drag toggles the focal point's picked-up state.
      if (!draggingFocal.value) {
        editing.focalPickedUp.value = !editing.focalPickedUp.value;
        focalPoint.setPickedUpStyles(editing.focalPickedUp.value);
      }
    } else if (
      editing.focalPickedUp.value &&
      !draggingFocal.value &&
      !draggingCropper.value &&
      !scalingCropper.value
    ) {
      // While picked up, clicking anywhere moves the focal point there.
      focalPoint.moveTo(toCanvasPoint(event));
    }

    draggingCropper.value = false;
    cropperClicked.value = false;
    scalingCropper.value = false;
    pointerHandle.value = null;
    draggingFocal.value = false;
    focalClicked.value = false;
  }

  function onPointerLeave(event: PointerEvent): void {
    // Mid-drag the pointer is captured, so leaving isn't the end of the
    // gesture — only a real pointer-up is. Bailing here would cancel exactly
    // the drags that need to travel past the edge.
    if (isDragging()) {
      return;
    }

    updateCursor(toCanvasPoint(event));
  }

  /** The name announced for an element, matching its edit button's label. */
  function itemName(handle: FabricElementHandle, label?: string): string {
    return label ?? handle;
  }

  function pickUp(handle: FabricElementHandle, label?: string): void {
    editing.reset();

    if (handle === 'rectangle') {
      editing.rectanglePickedUp.value = true;
      announcements.announcePickUp(
        itemName(handle, label),
        state.clipper.value
      );
    } else if (handle === 'focalpoint') {
      editing.focalPickedUp.value = true;
      announcements.announcePickUp(
        itemName(handle, label),
        state.focalPoint.value
      );
    } else {
      editing.pickedHandle.value = handle;
      announcements.announcePickUp(itemName(handle, label), null);
    }

    if (state.croppingCanvas.value) {
      cropper.redrawElements();
      canvas.renderCropper();
    }
  }

  function drop(handle: FabricElementHandle, label?: string): void {
    const item =
      handle === 'rectangle'
        ? state.clipper.value
        : handle === 'focalpoint'
          ? // The marker is already gone by now, so report where it was.
            state.previousFocalPoint.value
          : null;

    editing.reset();
    announcements.announceDrop(itemName(handle, label), item);

    if (state.croppingCanvas.value) {
      cropper.redrawElements();
      canvas.renderCropper();
    }
  }

  /**
   * Handles a click on one of the edit buttons: toggles the focal point, then
   * picks the element up or puts it back down.
   */
  function onEditButtonClick(
    handle: FabricElementHandle,
    pressed: boolean,
    label?: string
  ): void {
    if (handle === 'focalpoint') {
      focalPoint.toggle();
    }

    if (pressed) {
      drop(handle, label);
    } else {
      editing.dragEditMode.value = false;
      pickUp(handle, label);
    }
  }

  /** Moves whatever is currently picked up. */
  function nudge(direction: NudgeDirection): void {
    const deltas = getDeltasFromDirection(direction);

    if (editing.rectanglePickedUp.value) {
      cropper.moveByDelta(deltas.x, deltas.y);
      cropper.redrawElements();
      cropper.storeCropperState();
      canvas.renderCropper();
    } else if (editing.pickedHandle.value) {
      cropper.resizeByHandle(editing.pickedHandle.value, deltas);
      canvas.renderCropper();
    } else if (editing.focalPickedUp.value) {
      focalPoint.moveByDelta(deltas.x, deltas.y);
      focalPoint.storeFocalPointState();
      canvas.renderImage();
    }
  }

  function onEditButtonKeydown(event: KeyboardEvent): void {
    const direction = NUDGE_KEYS[event.key];

    if (!direction) {
      return;
    }

    const somethingPickedUp =
      editing.rectanglePickedUp.value ||
      editing.pickedHandle.value !== null ||
      editing.focalPickedUp.value;

    if (!somethingPickedUp) {
      return;
    }

    editing.dragEditMode.value = false;
    event.preventDefault();
    nudge(direction);
  }

  /**
   * Tracks which edit button has focus so the canvas can draw a matching
   * outline, and clears the outlines once focus moves elsewhere.
   */
  function onEditButtonFocus(handle: FabricElementHandle): void {
    editing.focusedHandle.value = handle;
    editing.reset();

    if (state.croppingCanvas.value) {
      cropper.redrawElements();
      canvas.renderCropper();
    }
  }

  function onEditButtonBlur(): void {
    editing.focusedHandle.value = null;
    editing.reset();

    if (state.croppingCanvas.value) {
      cropper.redrawElements();
      canvas.renderCropper();
    }
  }

  /** Shift locks the aspect ratio while dragging a corner. */
  function onKeyDown(event: KeyboardEvent): void {
    if (event.key === 'Shift') {
      state.shiftKeyHeld.value = true;
    }
  }

  function onKeyUp(event: KeyboardEvent): void {
    if (event.key === 'Shift') {
      state.shiftKeyHeld.value = false;
    }
  }

  return {
    cursor,
    onPointerDown,
    onPointerMove,
    onPointerUp,
    onPointerLeave,
    onEditButtonClick,
    onEditButtonKeydown,
    onEditButtonFocus,
    onEditButtonBlur,
    onKeyDown,
    onKeyUp,
    nudge,
    pickUp,
    drop,
  };
}

export type EditorInteractions = ReturnType<typeof useEditorInteractions>;
