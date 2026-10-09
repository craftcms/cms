import {Circle, Group, type FabricGroup} from './fabric';
import {
  arePointsInsideRectangle,
  isCenterInside,
  rotatePoint,
} from './geometry';
import type {FocalPointState, Point} from './types';
import type {EditorAnnouncements} from './useEditorAnnouncements';
import type {EditorGeometry, EditorState} from './useEditorState';
import type {ImageCanvas} from './useImageCanvas';

/**
 * The focal point marker: the spot transforms crop around when Craft generates
 * a smaller version of the image.
 *
 * Its position is stored as an offset from the image center at a zoom ratio of
 * 1 (`focalPointState`), so it survives zooming, straightening and rotation;
 * the on-canvas circle is derived from that offset whenever anything moves.
 */
export function useFocalPoint(
  state: EditorState,
  geometry: EditorGeometry,
  canvas: ImageCanvas,
  announcements: EditorAnnouncements
) {
  /**
   * Captures the marker's current canvas position back into zoom-independent
   * state, or stores a state passed in wholesale.
   */
  function storeFocalPointState(next?: FocalPointState): void {
    if (next) {
      state.focalPointState.value = next;
      return;
    }

    const focalPoint = state.focalPoint.value;
    const image = state.image.value;

    if (!focalPoint || !image) {
      return;
    }

    const zoomFactor = 1 / state.zoomRatio.value;

    state.focalPointState.value = {
      offsetX:
        ((focalPoint.left - image.left) * zoomFactor) / state.scaleFactor.value,
      offsetY:
        ((focalPoint.top - image.top) * zoomFactor) / state.scaleFactor.value,
      imageDimensions: geometry.getScaledImageDimensions(),
    };
  }

  /** Puts the stored offset back at the image center. */
  function resetPosition(): void {
    const focalState = state.focalPointState.value;

    if (focalState) {
      storeFocalPointState({...focalState, offsetX: 0, offsetY: 0});
    }
  }

  /** Builds the marker: a dark disc, a white ring, and a pick-up halo. */
  function buildMarker(left: number, top: number): FabricGroup {
    const pickedIndicator = new Circle({
      radius: 12,
      strokeWidth: 0,
      stroke: 'rgba(255,255,255,0.8)',
      left: 0,
      top: 0,
      originX: 'center',
      originY: 'center',
    });

    const outerCircle = new Circle({
      radius: 8,
      fill: 'rgba(0,0,0,0.5)',
      strokeWidth: 2,
      stroke: 'rgba(255,255,255,0.8)',
      left: 0,
      top: 0,
      originX: 'center',
      originY: 'center',
    });

    const innerCircle = new Circle({
      radius: 1,
      fill: 'rgba(255,255,255,0)',
      strokeWidth: 2,
      stroke: 'rgba(255,255,255,0.8)',
      left: 0,
      top: 0,
      originX: 'center',
      originY: 'center',
    });

    state.focalPointPickedIndicator.value = pickedIndicator;

    return new Group([pickedIndicator, outerCircle, innerCircle], {
      originX: 'center',
      originY: 'center',
      left,
      top,
    });
  }

  function create(): void {
    const focalState = state.focalPointState.value;
    const image = state.image.value;

    if (!focalState || !image) {
      return;
    }

    const scaled = geometry.getScaledImageDimensions();
    const sizeFactor = scaled.width / focalState.imageDimensions.width;
    const perOffsetPixel =
      sizeFactor * state.zoomRatio.value * state.scaleFactor.value;

    let focalX = focalState.offsetX * perOffsetPixel + image.left;
    let focalY = focalState.offsetY * perOffsetPixel + image.top;

    const next = {...focalState};

    // A fresh focal point lands in the middle of what the user can actually
    // see — the cropper while cropping, the viewport otherwise — rather than
    // the middle of an image that may be panned off-screen.
    if (next.offsetX === 0 && next.offsetY === 0) {
      const anchor =
        state.currentView.value === 'crop'
          ? state.clipper.value
          : state.viewport.value;

      if (anchor) {
        const deltaX = anchor.left - image.left;
        const deltaY = anchor.top - image.top;

        focalX += deltaX;
        focalY += deltaY;

        next.offsetX += deltaX / perOffsetPixel;
        next.offsetY += deltaY / perOffsetPixel;
      }
    }

    state.focalPoint.value = buildMarker(focalX, focalY);
    storeFocalPointState(next);
    state.canvas.value?.add(state.focalPoint.value);
  }

  function toggle(): void {
    if (state.focalPoint.value) {
      // Held onto so the drop announcement can still report where it was.
      state.previousFocalPoint.value = state.focalPoint.value;
      state.canvas.value?.remove(state.focalPoint.value);
      state.focalPoint.value = null;
    } else {
      create();
      state.previousFocalPoint.value = null;
    }

    canvas.renderImage();
  }

  /**
   * Swings the marker around the image center by an angle, so it stays on the
   * same part of the picture when the image rotates.
   */
  function adjustByAngle(angle: number): void {
    const focalState = state.focalPointState.value;
    const focalPoint = state.focalPoint.value;
    const image = state.image.value;

    if (!focalState || !focalPoint || !image) {
      return;
    }

    const rotated = rotatePoint(
      {x: focalState.offsetX, y: focalState.offsetY},
      angle
    );

    const sizeFactor =
      geometry.getScaledImageDimensions().width /
      focalState.imageDimensions.width;

    focalPoint.left =
      image.left + rotated.x * sizeFactor * state.zoomRatio.value;
    focalPoint.top = image.top + rotated.y * sizeFactor * state.zoomRatio.value;

    storeFocalPointState({
      ...focalState,
      offsetX: rotated.x,
      offsetY: rotated.y,
    });
  }

  /**
   * Puts the marker back on the part of the image it belongs to.
   *
   * Derived from the stored offset rather than shifted by how much the editor
   * changed: the offset is held relative to the image at a zoom of 1, so this
   * lands on the same spot in the picture whatever the editor did. Must run
   * after the image has taken its new position and size, since it reads both.
   */
  function positionFromState(): void {
    const focalState = state.focalPointState.value;
    const focalPoint = state.focalPoint.value;
    const image = state.image.value;

    if (!focalState || !focalPoint || !image) {
      return;
    }

    const sizeFactor =
      geometry.getScaledImageDimensions().width /
      focalState.imageDimensions.width;

    focalPoint.left =
      image.left + focalState.offsetX * sizeFactor * state.zoomRatio.value;
    focalPoint.top =
      image.top + focalState.offsetY * sizeFactor * state.zoomRatio.value;
  }

  /**
   * Positions the marker and puts it back on the canvas, for a transition that
   * lifted it off. Only for that case — `add()` appends unconditionally, so
   * calling this on every resize would stack up duplicates.
   */
  function restoreFromState(): void {
    if (!state.focalPoint.value) {
      return;
    }

    positionFromState();
    state.canvas.value?.add(state.focalPoint.value);
  }

  /** Whether a point falls within the unclipped region. */
  function isPointInsideViewport(point: Point): boolean {
    const viewport = state.viewport.value;

    if (!viewport) {
      return false;
    }

    return (
      viewport.left - viewport.width / 2 - point.x < 0 &&
      viewport.left + viewport.width / 2 - point.x > 0 &&
      viewport.top - viewport.height / 2 - point.y < 0 &&
      viewport.top + viewport.height / 2 - point.y > 0
    );
  }

  /**
   * Whether the marker may sit at a point — bounded by the image while
   * cropping (where the whole image is visible) and by the viewport otherwise.
   */
  function canMoveTo(point: Point): boolean {
    if (state.currentView.value === 'crop') {
      return state.imageVerticeCoords.value
        ? arePointsInsideRectangle([point], state.imageVerticeCoords.value)
        : false;
    }

    return isPointInsideViewport(point);
  }

  function moveByDelta(deltaX: number, deltaY: number): void {
    const focalPoint = state.focalPoint.value;

    if (!focalPoint || (deltaX === 0 && deltaY === 0)) {
      return;
    }

    const target = {x: focalPoint.left + deltaX, y: focalPoint.top + deltaY};

    if (!canMoveTo(target)) {
      return;
    }

    focalPoint.set({left: target.x, top: target.y});
    announcements.announcePosition(focalPoint);
  }

  /** Jumps the marker to a clicked point, if that point is in bounds. */
  function moveTo(point: Point): void {
    const focalPoint = state.focalPoint.value;

    if (!focalPoint || !canMoveTo(point)) {
      return;
    }

    focalPoint.set({left: point.x, top: point.y});
    storeFocalPointState();
    canvas.renderImage();
  }

  /** Dims the marker once straightening has pushed it out of the viewport. */
  function updateVisibilityForViewport(): void {
    const focalPoint = state.focalPoint.value;
    const viewport = state.viewport.value;

    if (!focalPoint || !viewport) {
      return;
    }

    focalPoint.set({opacity: isCenterInside(focalPoint, viewport) ? 1 : 0});
  }

  /**
   * Drops a marker that straightening pushed outside the viewport, rather than
   * leaving an invisible focal point behind.
   */
  function cleanupAfterStraighten(): void {
    const focalPoint = state.focalPoint.value;
    const viewport = state.viewport.value;

    if (!focalPoint || !viewport || isCenterInside(focalPoint, viewport)) {
      return;
    }

    focalPoint.set({opacity: 1});
    resetPosition();
    toggle();
  }

  /** Swaps the halo in and out as the marker is picked up and dropped. */
  function setPickedUpStyles(pickedUp: boolean): void {
    state.focalPointPickedIndicator.value?.set({
      strokeWidth: pickedUp ? 2 : 0,
      fill: pickedUp ? 'rgba(0,0,0,0.5)' : state.settings.colors.transparent,
    });

    state.canvas.value?.renderAll();
  }

  return {
    storeFocalPointState,
    resetPosition,
    create,
    toggle,
    positionFromState,
    adjustByAngle,
    restoreFromState,
    isPointInsideViewport,
    canMoveTo,
    moveByDelta,
    moveTo,
    updateVisibilityForViewport,
    cleanupAfterStraighten,
    setPickedUpStyles,
  };
}

export type FocalPoint = ReturnType<typeof useFocalPoint>;
