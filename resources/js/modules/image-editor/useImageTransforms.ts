import {fabric} from './fabric';
import {
  getZoomRatioToFitRectangle,
  isCenterInside,
  rotatePoint,
} from './geometry';
import type {Cropper} from './useCropper';
import type {EditorGeometry, EditorState} from './useEditorState';
import type {FocalPoint} from './useFocalPoint';
import type {ImageCanvas} from './useImageCanvas';

/** How many guide lines the straightening grid draws per axis. */
const GRID_LINE_COUNT = 8;

/**
 * Rotating, flipping and straightening.
 *
 * Rotation and straightening are two different things here: rotation turns the
 * viewport in 90° steps and is animated, while straightening tilts the image
 * underneath a fixed viewport and zooms to cover the gap that opens at the
 * corners.
 */
export function useImageTransforms(
  state: EditorState,
  geometry: EditorGeometry,
  canvas: ImageCanvas,
  cropper: Cropper,
  focalPoint: FocalPoint
) {
  /** Rotates the viewport a quarter turn, animating the image with it. */
  function rotate(degrees: 90 | -90): void {
    const image = state.image.value;
    const viewport = state.viewport.value;
    const cropperState = state.cropperState.value;

    if (
      state.animationInProgress.value ||
      !image ||
      !viewport ||
      !cropperState
    ) {
      return;
    }

    state.animationInProgress.value = true;
    state.viewportRotation.value = Math.trunc(
      (state.viewportRotation.value + degrees + 360) % 360
    );

    const scaled = geometry.getScaledImageDimensions();

    let imageZoomRatio = geometry.hasOrientationChanged()
      ? geometry.getZoomToCoverRatio({
          height: scaled.width,
          width: scaled.height,
        })
      : geometry.getZoomToCoverRatio(scaled);

    // Respect a zoom the user has already applied.
    imageZoomRatio = Math.max(imageZoomRatio, state.zoomRatio.value);

    // A viewport taller than the editor is wide (or vice versa) has to shrink
    // to fit once it turns onto its side.
    let scaleFactor = 1;

    if (state.scaleFactor.value < 1) {
      scaleFactor = 1 / state.scaleFactor.value;
      state.scaleFactor.value = 1;
    } else {
      if (viewport.width > state.editorHeight.value) {
        scaleFactor = state.editorHeight.value / viewport.width;
      } else if (viewport.height > state.editorWidth.value) {
        scaleFactor = state.editorWidth.value / viewport.height;
      }

      state.scaleFactor.value = scaleFactor;
    }

    const imageProperties: Record<string, unknown> = {
      angle: image.angle + degrees,
      width:
        scaled.width * imageZoomRatio * (scaleFactor < 1 ? scaleFactor : 1),
      height:
        scaled.height * imageZoomRatio * (scaleFactor < 1 ? scaleFactor : 1),
    };

    // Swing the stored crop offset around the same arc so the same region
    // stays framed after the turn.
    const rotated = rotatePoint(
      {x: cropperState.offsetX, y: cropperState.offsetY},
      degrees
    );

    const sizeFactor = scaled.width / cropperState.imageDimensions.width;
    const perOffsetPixel =
      sizeFactor * state.zoomRatio.value * state.scaleFactor.value;

    imageProperties.left =
      state.editorWidth.value / 2 - rotated.x * perOffsetPixel;
    imageProperties.top =
      state.editorHeight.value / 2 - rotated.y * perOffsetPixel;

    cropper.storeCropperState({
      ...cropperState,
      offsetX: rotated.x,
      offsetY: rotated.y,
      width: cropperState.height,
      height: cropperState.width,
    });

    if (state.focalPoint.value) {
      state.canvas.value?.remove(state.focalPoint.value);
    }

    viewport.animate(
      {angle: degrees === 90 ? '+=90' : '-=90'},
      {
        duration: state.settings.animationDuration,
        onComplete: () => {
          const height = viewport.height * scaleFactor;
          viewport.height = viewport.width * scaleFactor;
          viewport.width = height;
          viewport.set({angle: 0});
        },
      }
    );

    image.animate(imageProperties, {
      duration: state.settings.animationDuration,
      onChange: () => state.canvas.value?.renderAll(),
      onComplete: () => {
        image.set({angle: (image.angle + 360) % 360});
        state.animationInProgress.value = false;

        if (state.focalPoint.value) {
          focalPoint.adjustByAngle(degrees);
          straighten(state.imageStraightenAngle.value);
          state.canvas.value?.add(state.focalPoint.value);
        } else {
          focalPoint.resetPosition();
        }
      },
    });
  }

  /**
   * Mirrors the image on one axis.
   *
   * Which axis the user means depends on how the viewport is turned: with the
   * image on its side, "flip vertical" is a horizontal flip of the underlying
   * picture.
   */
  function flip(axis: 'x' | 'y'): void {
    const image = state.image.value;
    const cropperState = state.cropperState.value;
    const focalPointState = state.focalPointState.value;

    if (state.animationInProgress.value || !image || !cropperState) {
      return;
    }

    state.animationInProgress.value = true;

    const effectiveAxis = geometry.hasOrientationChanged()
      ? axis === 'y'
        ? 'x'
        : 'y'
      : axis;

    if (state.focalPoint.value) {
      state.canvas.value?.remove(state.focalPoint.value);
    } else {
      focalPoint.resetPosition();
    }

    const center = geometry.getEditorCenter();

    // Flipping mirrors the straightening angle too, so a tilted horizon stays
    // tilted the same way relative to the picture.
    state.imageStraightenAngle.value = -state.imageStraightenAngle.value;

    const properties: Record<string, unknown> = {
      angle: state.viewportRotation.value + state.imageStraightenAngle.value,
    };

    const nextCropperState = {...cropperState};
    const nextFocalState = focalPointState ? {...focalPointState} : null;

    if (effectiveAxis === 'x') {
      nextCropperState.offsetX = -nextCropperState.offsetX;

      if (nextFocalState) {
        nextFocalState.offsetX = -nextFocalState.offsetX;
      }

      properties.left = center.x - (image.left - center.x);
    } else {
      nextCropperState.offsetY = -nextCropperState.offsetY;

      if (nextFocalState) {
        nextFocalState.offsetY = -nextFocalState.offsetY;
      }

      properties.top = center.y - (image.top - center.y);
    }

    if (axis === 'y') {
      properties.scaleY = image.scaleY * -1;
      state.flipData.value = {
        ...state.flipData.value,
        y: 1 - state.flipData.value.y,
      };
    } else {
      properties.scaleX = image.scaleX * -1;
      state.flipData.value = {
        ...state.flipData.value,
        x: 1 - state.flipData.value.x,
      };
    }

    cropper.storeCropperState(nextCropperState);

    if (nextFocalState) {
      focalPoint.storeFocalPointState(nextFocalState);
    }

    // fabric normalizes a negative scale by flipping the corresponding
    // flipX/flipY flag and making the value positive. `set()` runs on every
    // animation frame, so each frame that passes a negative scale toggles the
    // flag again and the final state depends on the frame count. Bypassing
    // `_set` for the scale keys keeps the animation deterministic.
    // Captured unbound on purpose — it's put back on the same object below.
    // eslint-disable-next-line @typescript-eslint/unbound-method
    const originalSet = image._set;

    image._set = function (key: string, value: unknown) {
      if (key === 'scaleX' || key === 'scaleY') {
        (this as unknown as Record<string, unknown>)[key] = value;
        this.dirty = true;
        return this;
      }

      return originalSet.call(this, key, value);
    };

    image.flipX = false;
    image.flipY = false;

    image.animate(properties, {
      duration: state.settings.animationDuration,
      onChange: () => state.canvas.value?.renderAll(),
      onComplete: () => {
        image._set = originalSet;
        state.animationInProgress.value = false;

        if (state.focalPoint.value) {
          focalPoint.adjustByAngle(0);
          state.canvas.value?.add(state.focalPoint.value);
        }
      },
    });
  }

  /**
   * Tilts the image under a fixed viewport, zooming enough to keep the corners
   * covered.
   */
  function straighten(angle: number): void {
    const image = state.image.value;

    if (state.animationInProgress.value || !image) {
      return;
    }

    state.animationInProgress.value = true;

    const previousAngle = image.angle;

    state.imageStraightenAngle.value =
      (state.settings.allowDegreeFractions ? angle : Math.round(angle)) % 360;

    image.set({
      angle: state.viewportRotation.value + state.imageStraightenAngle.value,
    });

    state.zoomRatio.value =
      geometry.getZoomToCoverRatio(geometry.getScaledImageDimensions()) *
      state.scaleFactor.value;

    canvas.zoomImage();

    if (state.cropperState.value) {
      adjustEditorElementsOnStraighten(previousAngle);
    }

    canvas.renderImage();
    state.animationInProgress.value = false;
  }

  /**
   * Keeps the cropped region centered as the image tilts, zooming in far
   * enough that no image edge creeps into the viewport.
   *
   * The zoom and the offset depend on each other — zooming in moves the corners
   * — so this iterates until a pass needs no further adjustment.
   */
  function adjustEditorElementsOnStraighten(previousAngle: number): void {
    const image = state.image.value;
    const viewport = state.viewport.value;
    const cropperState = state.cropperState.value;

    if (!image || !viewport || !cropperState) {
      return;
    }

    const scaled = geometry.getScaledImageDimensions();
    const angleDelta = image.angle - previousAngle;
    const center = geometry.getEditorCenter();

    let currentZoomRatio = state.zoomRatio.value;
    let adjustmentRatio = 1;
    let newCenter = {x: cropperState.offsetX, y: cropperState.offsetY};
    let delta = {x: 0, y: 0};
    let sizeFactor = 1;

    do {
      newCenter = rotatePoint(
        {x: cropperState.offsetX, y: cropperState.offsetY},
        angleDelta
      );

      sizeFactor = scaled.width / cropperState.imageDimensions.width;

      delta = {
        x: newCenter.x * currentZoomRatio * sizeFactor,
        y: newCenter.y * currentZoomRatio * sizeFactor,
      };

      adjustmentRatio = getZoomRatioToFitRectangle(
        {
          width: viewport.width,
          height: viewport.height,
          left: center.x - viewport.width / 2 + delta.x,
          top: center.y - viewport.height / 2 + delta.y,
        },
        geometry.getImageVerticeCoords(currentZoomRatio),
        center
      );

      currentZoomRatio *= adjustmentRatio;
    } while (adjustmentRatio !== 1);

    image.set({left: center.x - delta.x, top: center.y - delta.y});

    cropper.storeCropperState({
      ...cropperState,
      offsetX: newCenter.x,
      offsetY: newCenter.y,
      width: viewport.width / currentZoomRatio / sizeFactor,
      height: viewport.height / currentZoomRatio / sizeFactor,
    });

    state.zoomRatio.value = currentZoomRatio;

    if (state.focalPoint.value) {
      focalPoint.adjustByAngle(angleDelta);
      focalPoint.updateVisibilityForViewport();
    } else if (angleDelta !== 0) {
      focalPoint.resetPosition();
    }

    canvas.zoomImage();
  }

  /** Draws the alignment grid shown while the straighten slider is in use. */
  function showGrid(): void {
    const viewport = state.viewport.value;

    if (state.grid.value || !viewport) {
      return;
    }

    const strokeOptions = {strokeWidth: 1, stroke: 'rgba(255,255,255,0.5)'};
    const gridWidth = viewport.width;
    const gridHeight = viewport.height;
    const xStep = gridWidth / (GRID_LINE_COUNT + 1);
    const yStep = gridHeight / (GRID_LINE_COUNT + 1);

    const parts = [
      new (fabric().Rect)({
        strokeWidth: 2,
        stroke: state.settings.colors.white,
        originX: 'center',
        originY: 'center',
        width: gridWidth,
        height: gridHeight,
        left: gridWidth / 2,
        top: gridHeight / 2,
        fill: 'rgba(255,255,255,0)',
      }),
    ];

    for (let i = 1; i <= GRID_LINE_COUNT; i++) {
      parts.push(
        new (fabric().Line)(
          [i * xStep, 0, i * xStep, gridHeight],
          strokeOptions
        )
      );
      parts.push(
        new (fabric().Line)([0, i * yStep, gridWidth, i * yStep], strokeOptions)
      );
    }

    state.grid.value = new (fabric().Group)(parts, {
      left: state.editorWidth.value / 2,
      top: state.editorHeight.value / 2,
      originX: 'center',
      originY: 'center',
      angle: viewport.angle,
    });

    state.canvas.value?.add(state.grid.value);
    canvas.renderImage();
  }

  function hideGrid(): void {
    if (!state.grid.value) {
      return;
    }

    state.canvas.value?.remove(state.grid.value);
    state.grid.value = null;
    canvas.renderImage();
  }

  /** Whether the focal point is still inside the viewport after a straighten. */
  function focalPointEscapedViewport(): boolean {
    const marker = state.focalPoint.value;
    const viewport = state.viewport.value;

    return Boolean(marker && viewport && !isCenterInside(marker, viewport));
  }

  return {
    rotate,
    flip,
    straighten,
    showGrid,
    hideGrid,
    focalPointEscapedViewport,
  };
}

export type ImageTransforms = ReturnType<typeof useImageTransforms>;
