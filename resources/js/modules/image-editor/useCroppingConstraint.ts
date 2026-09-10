import {arePointsInsideRectangle, getRectangleVertices} from './geometry';
import type {Cropper} from './useCropper';
import type {EditorState} from './useEditorState';
import type {ImageCanvas} from './useImageCanvas';

/**
 * A ratio, either as a number or a numeric string, or one of the named values
 * `none`, `original` (the asset's own ratio) and `current` (whatever the
 * rectangle happens to be).
 */
export type ConstraintValue = string | number;

/**
 * The aspect-ratio lock on the cropping rectangle.
 *
 * Setting a constraint records the ratio; enforcing it animates the rectangle
 * to match, growing along whichever axis keeps it inside the image.
 */
export function useCroppingConstraint(
  state: EditorState,
  cropper: Cropper,
  canvas: ImageCanvas
) {
  function setConstraint(constraint: ConstraintValue): void {
    const clipper = state.clipper.value;

    switch (constraint) {
      case 'none':
        state.croppingConstraint.value = false;
        break;

      case 'original':
        state.croppingConstraint.value =
          state.originalWidth.value / state.originalHeight.value;
        break;

      case 'current':
        state.croppingConstraint.value = clipper
          ? clipper.width / clipper.height
          : false;
        break;

      // Custom keeps whatever the width/height inputs last applied.
      case 'custom':
        break;

      default: {
        const ratio =
          typeof constraint === 'number' ? constraint : parseFloat(constraint);
        state.croppingConstraint.value = Number.isNaN(ratio) ? false : ratio;
      }
    }
  }

  /** Applies a `w / h` ratio from the custom constraint inputs. */
  function setCustomConstraint(width: number, height: number): void {
    if (width > 0 && height > 0) {
      state.croppingConstraint.value = width / height;
    }
  }

  /**
   * Reshapes the rectangle to the current ratio.
   *
   * Grows the short axis first, since that keeps the visible crop as large as
   * possible; if that would push a corner off the image, shrinks the long axis
   * instead.
   */
  function enforce(): void {
    const constraint = state.croppingConstraint.value;
    const clipper = state.clipper.value;
    const rectangle = cropper.getClipperRect();
    const coords = state.imageVerticeCoords.value;

    if (
      state.animationInProgress.value ||
      !constraint ||
      !clipper ||
      !rectangle ||
      !coords
    ) {
      return;
    }

    state.animationInProgress.value = true;

    if (clipper.width > clipper.height * constraint) {
      const previousHeight = rectangle.height;

      rectangle.height = clipper.width / constraint;
      rectangle.top -= (rectangle.height - previousHeight) / 2;

      if (!arePointsInsideRectangle(getRectangleVertices(rectangle), coords)) {
        rectangle.width = clipper.height * constraint;
        rectangle.height = rectangle.width / constraint;
      }
    } else {
      const previousWidth = rectangle.width;

      rectangle.width = clipper.height * constraint;
      rectangle.left -= (rectangle.width - previousWidth) / 2;

      if (!arePointsInsideRectangle(getRectangleVertices(rectangle), coords)) {
        rectangle.height = clipper.width / constraint;
        rectangle.width = rectangle.height * constraint;
      }
    }

    clipper.animate(
      {width: rectangle.width, height: rectangle.height},
      {
        duration: state.settings.animationDuration,
        onChange: () => {
          cropper.redrawElements();
          state.croppingCanvas.value?.renderAll();
        },
        onComplete: () => {
          cropper.redrawElements();
          state.animationInProgress.value = false;
          canvas.renderCropper();
          cropper.storeCropperState();
        },
      }
    );
  }

  /** Sets a constraint and immediately reshapes the rectangle to match. */
  function apply(constraint: ConstraintValue): void {
    setConstraint(constraint);
    enforce();
  }

  return {setConstraint, setCustomConstraint, enforce, apply};
}

export type CroppingConstraint = ReturnType<typeof useCroppingConstraint>;
