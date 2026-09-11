import {Rect, StaticCanvas, loadImage, type FabricImage} from './fabric';
import type {EditorGeometry, EditorState} from './useEditorState';
import type {Dimensions} from './types';

/**
 * The canvas layer: creating the fabric canvases, keeping the image and
 * viewport sized and centered, and scheduling renders.
 *
 * The viewport is a filled rectangle drawn with `destination-in`, so it acts as
 * a mask — everything outside it is clipped away. That's how the editor shows a
 * cropped region without actually cropping the image.
 */
export function useImageCanvas(state: EditorState, geometry: EditorGeometry) {
  let imageFrame: number | null = null;
  let cropperFrame: number | null = null;

  /**
   * Renders are coalesced to one per frame: drags and animations can each ask
   * to render several times before the browser paints once.
   */
  function renderImage(): void {
    if (imageFrame !== null) {
      return;
    }

    imageFrame = requestAnimationFrame(() => {
      imageFrame = null;
      state.canvas.value?.renderAll();
    });
  }

  function renderCropper(): void {
    if (cropperFrame !== null || !state.croppingCanvas.value) {
      return;
    }

    cropperFrame = requestAnimationFrame(() => {
      cropperFrame = null;
      state.croppingCanvas.value?.renderAll();
    });
  }

  function cancelPendingRenders(): void {
    if (imageFrame !== null) {
      cancelAnimationFrame(imageFrame);
      imageFrame = null;
    }

    if (cropperFrame !== null) {
      cancelAnimationFrame(cropperFrame);
      cropperFrame = null;
    }
  }

  /** The largest image worth requesting for the current viewport. */
  function getMaxImageSize(): number {
    const {clientWidth, clientHeight} = document.documentElement;

    return (
      Math.max(clientHeight, clientWidth) *
      (window.devicePixelRatio > 1 ? 2 : 1)
    );
  }

  function measureEditor(): void {
    const el = state.editorEl.value;

    if (el) {
      state.editorWidth.value = el.clientWidth;
      state.editorHeight.value = el.clientHeight;
    }
  }

  /** Creates the main canvas and loads the image onto it, centered. */
  async function createCanvas(imageUrl: string): Promise<FabricImage> {
    const canvasEl = state.imageCanvasEl.value;

    if (!canvasEl) {
      throw new Error('The image canvas is not mounted.');
    }

    const canvas = new StaticCanvas(canvasEl);
    canvas.enableRetinaScaling = true;
    state.canvas.value = canvas;

    const image = await loadImage(imageUrl);

    image.set({
      originX: 'center',
      originY: 'center',
      left: state.editorWidth.value / 2,
      top: state.editorHeight.value / 2,
    });

    canvas.add(image);

    state.image.value = image;
    state.originalWidth.value = image.width;
    state.originalHeight.value = image.height;
    state.zoomRatio.value = 1;
    state.lastLoadedDimensions.value = geometry.getScaledImageDimensions();

    return image;
  }

  /**
   * Refetches the image at a higher resolution once the editor has grown enough
   * that the current one would visibly soften.
   */
  function reloadImage(imageUrl: string, onLoaded: () => void): void {
    const image = state.image.value;

    if (state.imageIsLoading.value || !image) {
      return;
    }

    state.imageIsLoading.value = true;

    // fabric 7 resolves rather than calling back, and hands back the same
    // object it was called on -- so the new size is read off `image` itself.
    void image.setSrc(imageUrl).then(() => {
      state.originalWidth.value = image.width;
      state.originalHeight.value = image.height;
      state.lastLoadedDimensions.value = {
        width: state.originalWidth.value,
        height: state.originalHeight.value,
      };
      state.imageIsLoading.value = false;
      onLoaded();
    });
  }

  /** Creates the mask that clips the image down to the cropped region. */
  function createViewport(): void {
    const image = state.image.value;
    const canvas = state.canvas.value;

    if (!image || !canvas) {
      return;
    }

    const viewport = new Rect({
      width: image.getScaledWidth(),
      height: image.getScaledHeight(),
      fill: 'rgba(127,0,0,1)',
      originX: 'center',
      originY: 'center',
      // Clips away everything drawn outside this rectangle.
      globalCompositeOperation: 'destination-in',
      left: image.left,
      top: image.top,
    });

    state.viewport.value = viewport;
    canvas.add(viewport);
    renderImage();
  }

  /** Sizes the image to the current zoom ratio. */
  function zoomImage(): void {
    const dimensions = geometry.getScaledImageDimensions();
    const scale = geometry.getImageScaleFor(
      dimensions.width * state.zoomRatio.value
    );

    state.image.value?.set({scaleX: scale, scaleY: scale});
  }

  /**
   * Keeps the image's offset from center intact as the editor resizes, so a
   * panned image doesn't jump when the window changes.
   */
  function repositionImage(previous: Dimensions): void {
    const image = state.image.value;

    if (!image) {
      return;
    }

    image.set({
      left: image.left - (previous.width - state.editorWidth.value) / 2,
      top: image.top - (previous.height - state.editorHeight.value) / 2,
    });
  }

  /**
   * Resizes the viewport mask. While cropping it covers the whole editor (the
   * cropper layer draws the shade instead); otherwise it takes the stored
   * cropper's size and the image slides so the right region shows through.
   */
  function repositionViewport(): void {
    const viewport = state.viewport.value;
    const image = state.image.value;

    if (!viewport || !image) {
      return;
    }

    const dimensions: Record<string, number> = {
      left: state.editorWidth.value / 2,
      top: state.editorHeight.value / 2,
    };

    if (state.currentView.value === 'crop') {
      dimensions.width = state.editorWidth.value;
      dimensions.height = state.editorHeight.value;
    } else if (state.cropperState.value) {
      const cropperState = state.cropperState.value;
      const scaled = geometry.getScaledImageDimensions();
      const sizeFactor = scaled.width / cropperState.imageDimensions.width;

      dimensions.width =
        cropperState.width * sizeFactor * state.zoomRatio.value;
      dimensions.height =
        cropperState.height * sizeFactor * state.zoomRatio.value;

      image.set({
        left: state.editorWidth.value / 2 - cropperState.offsetX * sizeFactor,
        top: state.editorHeight.value / 2 - cropperState.offsetY * sizeFactor,
      });
    } else {
      Object.assign(dimensions, geometry.getScaledImageDimensions());
    }

    viewport.set(dimensions);
  }

  /** Matches the fabric canvases to the editor element's current size. */
  function resizeCanvases(): void {
    const dimensions = {
      width: state.editorWidth.value,
      height: state.editorHeight.value,
    };

    state.canvas.value?.setDimensions(dimensions);
    state.croppingCanvas.value?.setDimensions(dimensions);
  }

  function destroy(): void {
    cancelPendingRenders();
    // Unmounting, so there is nothing left to wait for the deferred half of
    // these to finish tidying -- see the note in `useCropper.hide()`.
    void state.croppingCanvas.value?.dispose();
    void state.canvas.value?.dispose();
    state.croppingCanvas.value = null;
    state.canvas.value = null;
  }

  return {
    renderImage,
    renderCropper,
    cancelPendingRenders,
    getMaxImageSize,
    measureEditor,
    createCanvas,
    reloadImage,
    createViewport,
    zoomImage,
    repositionImage,
    repositionViewport,
    resizeCanvases,
    destroy,
  };
}

export type ImageCanvas = ReturnType<typeof useImageCanvas>;
