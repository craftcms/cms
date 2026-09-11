import {
  Circle,
  Group,
  Line,
  Path,
  Rect,
  StaticCanvas,
  type FabricGroup,
  type FabricObject,
} from './fabric';
import {
  arePointsInsideRectangle,
  getFarthestAllowedDeltas,
  getHandlePosition,
  getRectangleVertices,
  resizeRectangle,
  transposeRectangle,
} from './geometry';
import type {
  CropHandle,
  CropperState,
  FabricElementHandle,
  Point,
  Rectangle,
} from './types';
import type {EditorAnnouncements} from './useEditorAnnouncements';
import type {EditorGeometry, EditorState} from './useEditorState';
import type {ImageCanvas} from './useImageCanvas';

/** The cropping rectangle never shrinks below this, in either dimension. */
const MIN_CROP_SIZE = 30;

/** Extra breathing room around a straightened image's crop rectangle. */
const STRAIGHTENED_RECT_PADDING = 1.2;

export interface CropperFocusContext {
  /** The handle whose edit button currently has focus, if any. */
  focusedHandle: () => FabricElementHandle | null;
  /** The handle currently picked up for keyboard editing, if any. */
  pickedHandle: () => CropHandle | null;
  /** Whether the rectangle itself is picked up. */
  rectanglePickedUp: () => boolean;
  /** Whether the last interaction was a drag rather than the keyboard. */
  dragEditMode: () => boolean;
}

/**
 * The cropping layer: a second canvas stacked over the image, holding the
 * shade, the cropping rectangle and its handles.
 *
 * The clipper is drawn with `destination-out`, so it punches a hole in the
 * shade rather than being drawn on top of it. Its position, like the focal
 * point's, is stored zoom-independently in `cropperState`.
 */
export function useCropper(
  state: EditorState,
  geometry: EditorGeometry,
  canvas: ImageCanvas,
  announcements: EditorAnnouncements,
  focus: CropperFocusContext
) {
  /**
   * Captures the clipper's canvas position back into zoom-independent state,
   * or stores a state passed in wholesale. With no clipper yet, falls back to
   * the whole image.
   */
  function storeCropperState(next?: CropperState): void {
    if (next) {
      state.cropperState.value = next;
      return;
    }

    const clipper = state.clipper.value;
    const image = state.image.value;

    if (clipper && image) {
      const zoomFactor = 1 / state.zoomRatio.value;

      state.cropperState.value = {
        offsetX: (clipper.left - image.left) * zoomFactor,
        offsetY: (clipper.top - image.top) * zoomFactor,
        width: clipper.width * zoomFactor,
        height: clipper.height * zoomFactor,
        imageDimensions: geometry.getScaledImageDimensions(),
      };

      return;
    }

    const dimensions = geometry.getScaledImageDimensions();

    state.cropperState.value = {
      offsetX: 0,
      offsetY: 0,
      width: dimensions.width,
      height: dimensions.height,
      imageDimensions: dimensions,
    };
  }

  /** The clipper as a top-left-origin rectangle, for the containment maths. */
  function getClipperRect(): Rectangle | null {
    const clipper = state.clipper.value;

    if (!clipper) {
      return null;
    }

    return {
      left: clipper.left - clipper.width / 2,
      top: clipper.top - clipper.height / 2,
      width: clipper.width,
      height: clipper.height,
    };
  }

  /** The white L-shaped brackets at each corner of the rectangle. */
  function buildHandles(clipper: FabricObject): FabricGroup {
    // `fill: false` meant "no fill" in fabric 1.x; v7 spells it null.
    const lineOptions = {
      strokeWidth: 4,
      stroke: state.settings.colors.white,
      fill: null,
    };

    const {width, height} = clipper;

    const paths = [
      'M 0,10 L 0,0 L 10,0',
      `M ${width - 8},0 L ${width + 4},0 L ${width + 4},10`,
      `M ${width + 4},${height - 8} L${width + 4},${height + 4} L ${width - 8},${height + 4}`,
      `M 10,${height + 4} L 0,${height + 4} L 0,${height - 8}`,
    ].map((path) => new Path(path, lineOptions));

    return new Group(paths, {
      left: clipper.left,
      top: clipper.top,
      originX: 'center',
      originY: 'center',
    });
  }

  /** The rule-of-thirds guides inside the rectangle. */
  function buildGrid(clipper: FabricObject): FabricGroup {
    const gridOptions = {strokeWidth: 2, stroke: 'rgba(255,255,255,0.5)'};
    const {width, height} = clipper;

    const lines: [number, number, number, number][] = [
      [width * 0.33, 0, width * 0.33, height],
      [width * 0.66, 0, width * 0.66, height],
      [0, height * 0.33, width, height * 0.33],
      [0, height * 0.66, width, height * 0.66],
    ];

    return new Group(
      lines.map((points) => new Line(points, gridOptions)),
      {
        left: clipper.left,
        top: clipper.top,
        originX: 'center',
        originY: 'center',
      }
    );
  }

  /**
   * The rectangle outline. Gains a blue/white double outline while it's
   * focused or picked up, and the move icon while it's picked up.
   */
  function buildCroppingRectangle(clipper: FabricObject): FabricGroup {
    const strokeWidth = 2;
    const shared = {
      fill: state.settings.colors.transparent,
      top: 0,
      left: 0,
      strokeWidth,
      originX: 'center',
      originY: 'center',
    } as const;

    const outerOutline = new Rect({
      ...shared,
      width: clipper.width + strokeWidth * 4,
      height: clipper.height + strokeWidth * 4,
      stroke: null,
    });

    const innerOutline = new Rect({
      ...shared,
      width: clipper.width + strokeWidth * 2,
      height: clipper.height + strokeWidth * 2,
      stroke: null,
    });

    const outline = new Rect({
      ...shared,
      width: clipper.width,
      height: clipper.height,
      stroke: state.settings.colors.white,
    });

    const group = new Group([outerOutline, innerOutline, outline], {
      originX: 'center',
      originY: 'center',
      left: clipper.left,
      top: clipper.top,
    });

    const pickedUp = focus.rectanglePickedUp();
    const focused = focus.focusedHandle() === 'rectangle';

    if (pickedUp || focused) {
      outerOutline.set({stroke: state.settings.colors.white});
      innerOutline.set({stroke: state.settings.colors.accent});

      if (pickedUp && state.moveIcon.value) {
        group.add(
          new Circle({
            fill: state.settings.colors.black,
            top: 0,
            left: 0,
            radius: 15,
            stroke: state.settings.colors.white,
            strokeWidth: 2,
            originX: 'center',
            originY: 'center',
          })
        );
        group.add(state.moveIcon.value);
      }
    }

    return group;
  }

  /**
   * The concentric rings marking which handle the keyboard is acting on. Not
   * drawn during pointer interaction, where the cursor already says it.
   */
  function buildHandleFocusIndicator(
    clipper: FabricObject
  ): FabricGroup | null {
    const picked = focus.pickedHandle();
    const focused = focus.focusedHandle();
    const focusedIsHandle =
      focused !== null && focused !== 'rectangle' && focused !== 'focalpoint';

    if (focus.dragEditMode() || (!focusedIsHandle && !picked)) {
      return null;
    }

    const handle = (picked ?? focused) as CropHandle;
    const position = getHandlePosition(handle, clipper);

    const size = 12;
    const width = 3;
    const shared = {
      fill: null,
      strokeWidth: width,
      left: 0,
      top: 0,
      originX: 'center',
      originY: 'center',
    } as const;

    const rings = [
      new Circle({
        ...shared,
        radius: size + width * 2,
        stroke: state.settings.colors.accent,
      }),
      new Circle({
        ...shared,
        radius: size + width,
        stroke: state.settings.colors.white,
      }),
      new Circle({
        ...shared,
        radius: size,
        stroke: state.settings.colors.accent,
      }),
    ];

    const focusRing = new Group(rings, {
      originX: 'center',
      originY: 'center',
      left: position.x,
      top: position.y,
    });

    if (picked && state.moveIcon.value) {
      focusRing.add(state.moveIcon.value);
      focusRing.item(0).set({fill: state.settings.colors.transparentBlack});
    }

    return focusRing;
  }

  /** Rebuilds everything drawn on top of the clipper. */
  function redrawElements(): void {
    const croppingCanvas = state.croppingCanvas.value;
    const clipper = state.clipper.value;

    if (!croppingCanvas || !clipper) {
      return;
    }

    for (const object of [
      state.cropperHandles.value,
      state.cropperGrid.value,
      state.croppingRectangle.value,
      state.handleFocusIndicator.value,
    ]) {
      if (object) {
        croppingCanvas.remove(object);
      }
    }

    state.cropperHandles.value = buildHandles(clipper);
    state.cropperGrid.value = buildGrid(clipper);
    state.croppingRectangle.value = buildCroppingRectangle(clipper);
    state.handleFocusIndicator.value = buildHandleFocusIndicator(clipper);

    croppingCanvas.add(state.croppingRectangle.value);
    croppingCanvas.add(state.cropperHandles.value);
    croppingCanvas.add(state.cropperGrid.value);

    if (state.handleFocusIndicator.value) {
      croppingCanvas.add(state.handleFocusIndicator.value);
    }
  }

  /** Builds the cropping canvas, its shade, and the clipper that cuts it. */
  function setupLayer(clipperData?: Partial<Rectangle>): void {
    const canvasEl = state.croppingCanvasEl.value;

    if (!canvasEl) {
      return;
    }

    // Replacing the canvas on the same element -- safe without awaiting, for
    // the reason given in `hide()`.
    void state.croppingCanvas.value?.dispose();

    const croppingCanvas = new StaticCanvas(canvasEl, {
      backgroundColor: state.settings.colors.transparent,
      hoverCursor: 'default',
      selection: false,
    });

    croppingCanvas.setDimensions({
      width: state.editorWidth.value,
      height: state.editorHeight.value,
    });

    state.croppingCanvas.value = croppingCanvas;

    const shade = new Rect({
      left: state.editorWidth.value / 2,
      top: state.editorHeight.value / 2,
      originX: 'center',
      originY: 'center',
      width: state.editorWidth.value,
      height: state.editorHeight.value,
      fill: state.settings.colors.transparentBlack,
    });

    // A straightened image needs the rectangle pulled in, or its corners would
    // sit outside the picture.
    const dimensions = geometry.getScaledImageDimensions();
    const ratio =
      state.imageStraightenAngle.value === 0
        ? 1
        : geometry.getCombinedZoomRatio(dimensions) * STRAIGHTENED_RECT_PADDING;

    let rectWidth = dimensions.width / ratio;
    let rectHeight = dimensions.height / ratio;

    if (geometry.hasOrientationChanged()) {
      [rectWidth, rectHeight] = [rectHeight, rectWidth];
    }

    const clipper = new Rect({
      left: state.editorWidth.value / 2,
      top: state.editorHeight.value / 2,
      originX: 'center',
      originY: 'center',
      width: rectWidth,
      height: rectHeight,
      stroke: 'black',
      fill: 'rgba(128,0,0,1)',
      strokeWidth: 0,
    });

    if (clipperData) {
      clipper.set(clipperData as Record<string, unknown>);
    }

    // Cuts the rectangle out of the shade rather than drawing over it.
    clipper.globalCompositeOperation = 'destination-out';

    state.croppingShade.value = shade;
    state.clipper.value = clipper;

    croppingCanvas.add(shade);
    croppingCanvas.add(clipper);
  }

  function show(clipperData?: Partial<Rectangle>): void {
    setupLayer(clipperData);
    redrawElements();
    canvas.renderCropper();
  }

  function hide(): void {
    if (!state.clipper.value) {
      return;
    }

    // Wiped before disposing, because disposing no longer does it. fabric 1.x
    // cleared the context on the way out; fabric 7 only resets the element's
    // width and height attributes, and a browser is free to skip that when the
    // values haven't changed -- leaving the rectangle, its grid and the shade
    // painted over the image after crop closes.
    state.croppingCanvas.value?.clear();

    // Not awaited, and it doesn't need to be: the half that matters here --
    // unwrapping the canvas element and cancelling animations -- happens
    // synchronously before the promise resolves. Only object teardown is
    // deferred, and the environment hook it ends at is a no-op in the browser,
    // so the element is safe to hand to a new canvas the next time crop opens.
    void state.croppingCanvas.value?.dispose();
    state.croppingCanvas.value = null;
    state.clipper.value = null;
    state.croppingShade.value = null;
    state.cropperHandles.value = null;
    state.cropperGrid.value = null;
    state.croppingRectangle.value = null;
    state.handleFocusIndicator.value = null;
  }

  /**
   * Turns the rectangle on its side: width and height swap about its centre,
   * so the crop the user framed is kept and simply stands the other way up.
   *
   * Swapping the two *is* the inverted ratio, so a constrained crop lands on
   * exactly the shape the flipped constraint asks for — without `enforce()`
   * rebuilding it from the ratio and jumping in size.
   *
   * A turned rectangle can stick out of the image where the original didn't (a
   * wide crop becomes a tall one), so it shrinks about its centre until it
   * fits. Returns the shape it settled on, or null if it couldn't turn.
   */
  function transpose(): Rectangle | null {
    const clipper = state.clipper.value;
    const coords = state.imageVerticeCoords.value;

    if (state.animationInProgress.value || !clipper || !coords) {
      return null;
    }

    const target = transposeRectangle(clipper, coords);

    if (target.width < MIN_CROP_SIZE || target.height < MIN_CROP_SIZE) {
      return null;
    }

    state.animationInProgress.value = true;

    clipper.animate(
      {width: target.width, height: target.height},
      {
        duration: state.settings.animationDuration,
        onChange: () => {
          redrawElements();
          state.croppingCanvas.value?.renderAll();
        },
        onComplete: () => {
          redrawElements();
          state.animationInProgress.value = false;
          canvas.renderCropper();
          storeCropperState();
        },
      }
    );

    return target;
  }

  /**
   * Re-derives the rectangle from the stored cropper state and the image's
   * current position.
   *
   * `reposition()` translates the rectangle by how much the editor changed,
   * which preserves whatever offset it already had — right for a live resize,
   * wrong once the rectangle and the image have drifted apart. The stored state
   * is held independently of zoom and position, so re-deriving from it puts the
   * rectangle back onto the image whatever happened in between.
   */
  function restoreFromState(): void {
    const clipper = state.clipper.value;
    const cropperState = state.cropperState.value;
    const image = state.image.value;

    if (!clipper || !cropperState || !image) {
      return;
    }

    const sizeFactor =
      geometry.getScaledImageDimensions().width /
      cropperState.imageDimensions.width;
    const scale = sizeFactor * state.zoomRatio.value;

    clipper.set({
      left: image.left + cropperState.offsetX * scale,
      top: image.top + cropperState.offsetY * scale,
      width: cropperState.width * scale,
      height: cropperState.height * scale,
    });

    redrawElements();
  }

  /**
   * Resizes the cropping layer to the editor and puts the rectangle back where
   * it belongs on the image.
   *
   * The rectangle is re-derived rather than shifted by how much the editor
   * changed — the stored state holds it relative to the image at a zoom of 1,
   * so it lands on the same part of the picture whatever the editor did. Must
   * run after the image has taken its new position and size.
   */
  function reposition(): void {
    const croppingCanvas = state.croppingCanvas.value;
    const shade = state.croppingShade.value;

    if (!croppingCanvas || !shade) {
      return;
    }

    croppingCanvas.setDimensions({
      width: state.editorWidth.value,
      height: state.editorHeight.value,
    });

    shade.set({
      width: state.editorWidth.value,
      height: state.editorHeight.value,
      left: state.editorWidth.value / 2,
      top: state.editorHeight.value / 2,
    });

    restoreFromState();
    canvas.renderCropper();
  }

  /**
   * Moves the rectangle, clamping to the image. A move that would leave the
   * picture is retried at the farthest distance that stays inside, so dragging
   * into an edge slides along it.
   */
  function moveByDelta(deltaX: number, deltaY: number, announce = true): void {
    const clipper = state.clipper.value;
    const rectangle = getClipperRect();
    const coords = state.imageVerticeCoords.value;

    if (!clipper || !rectangle || !coords) {
      return;
    }

    let dx = deltaX;
    let dy = deltaY;

    if (
      !arePointsInsideRectangle(getRectangleVertices(rectangle, dx, dy), coords)
    ) {
      const {farthest, farthestDeltas} = getFarthestAllowedDeltas(
        rectangle,
        {x: dx, y: dy},
        coords
      );

      if (farthest === 0) {
        return;
      }

      dx = farthestDeltas.x;
      dy = farthestDeltas.y;
    }

    clipper.set({left: clipper.left + dx, top: clipper.top + dy});

    if (announce) {
      announcements.announcePosition(clipper);
    }
  }

  /** Resizes the rectangle by one handle, refusing moves that leave the image. */
  function resizeByHandle(
    handle: CropHandle,
    deltas: Point,
    announce = true
  ): void {
    const clipper = state.clipper.value;
    const starting = getClipperRect();
    const coords = state.imageVerticeCoords.value;

    if (!clipper || !starting || !coords) {
      return;
    }

    const attempt = (x: number, y: number): Rectangle =>
      resizeRectangle(
        starting,
        x,
        y,
        handle,
        state.croppingConstraint.value,
        state.shiftKeyHeld.value
      );

    const fits = (candidate: Rectangle): boolean =>
      candidate.height >= MIN_CROP_SIZE &&
      candidate.width >= MIN_CROP_SIZE &&
      arePointsInsideRectangle(getRectangleVertices(candidate), coords);

    // A corner drag moves on both axes, and the rectangle starts flush with the
    // image — so one axis is usually blocked while the other has room. Refusing
    // the whole move makes the cropper feel dead; taking whichever axis still
    // fits lets it slide along the edge, the way dragging the rectangle does.
    const rectangle =
      [
        attempt(deltas.x, deltas.y),
        attempt(deltas.x, 0),
        attempt(0, deltas.y),
      ].find(fits) ?? null;

    if (!rectangle) {
      return;
    }

    clipper.set({
      top: rectangle.top + rectangle.height / 2,
      left: rectangle.left + rectangle.width / 2,
      width: rectangle.width,
      height: rectangle.height,
    });

    redrawElements();

    if (announce) {
      announcements.announceSizeAndPosition(clipper);
    }
  }

  return {
    storeCropperState,
    getClipperRect,
    redrawElements,
    show,
    hide,
    reposition,
    restoreFromState,
    transpose,
    moveByDelta,
    resizeByHandle,
  };
}

export type Cropper = ReturnType<typeof useCropper>;
