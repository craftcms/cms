import {
  computed,
  ref,
  shallowRef,
  toValue,
  type MaybeRefOrGetter,
  type Ref,
  type ShallowRef,
} from 'vue';
import type {
  FabricCanvas,
  FabricGroup,
  FabricImage,
  FabricObject,
} from './fabric';
import type {
  CropperState,
  Dimensions,
  EditorView,
  FlipData,
  FocalPointState,
  VerticeCoords,
} from './types';

/**
 * How much bigger the editor can get before the source image is refetched at a
 * higher resolution rather than being upscaled.
 */
const RELOAD_THRESHOLD = 1.5;

/**
 * Breathing room kept around the image, in every view.
 *
 * The cropper's handles are drawn a few pixels *outside* the rectangle, and
 * their keyboard focus rings reach ~18px past a corner. With the image zoomed
 * flush to the canvas edge, a full-image crop puts its handles off-canvas —
 * clipped from view, with their grab zones half outside the hit area.
 *
 * Applied everywhere rather than only while cropping: an inset that appears
 * with the crop controls reframes the image the moment they open, on top of
 * the zoom change, which reads as a lurch. Holding the same content box in
 * every view leaves the switch as one zoom and nothing else.
 */
const CROP_HANDLE_MARGIN = 20;

export interface EditorColors {
  white: string;
  black: string;
  transparentBlack: string;
  transparent: string;
  accent: string;
}

export interface EditorSettings {
  animationDuration: number;
  allowDegreeFractions: boolean;
  colors: EditorColors;
}

/**
 * Every piece of mutable editor state, in one object passed to each feature
 * composable.
 *
 * fabric objects live in `shallowRef`s deliberately: Vue's deep reactivity
 * would proxy their internals and fabric mutates those on every render, so a
 * deep ref both thrashes and misbehaves. Nothing renders off their contents —
 * the canvas does — so shallow is also all we need.
 */
export interface EditorState {
  settings: EditorSettings;

  // Canvas elements, owned by the component and read-only here.
  imageCanvasEl: Readonly<Ref<HTMLCanvasElement | null>>;
  croppingCanvasEl: Readonly<Ref<HTMLCanvasElement | null>>;
  editorEl: Readonly<Ref<HTMLElement | null>>;

  // fabric objects.
  canvas: ShallowRef<FabricCanvas | null>;
  croppingCanvas: ShallowRef<FabricCanvas | null>;
  image: ShallowRef<FabricImage | null>;
  viewport: ShallowRef<FabricObject | null>;
  focalPoint: ShallowRef<FabricGroup | null>;
  previousFocalPoint: ShallowRef<FabricGroup | null>;
  focalPointPickedIndicator: ShallowRef<FabricObject | null>;
  grid: ShallowRef<FabricGroup | null>;
  clipper: ShallowRef<FabricObject | null>;
  croppingShade: ShallowRef<FabricObject | null>;
  croppingRectangle: ShallowRef<FabricGroup | null>;
  cropperHandles: ShallowRef<FabricGroup | null>;
  cropperGrid: ShallowRef<FabricGroup | null>;
  handleFocusIndicator: ShallowRef<FabricGroup | null>;
  moveIcon: ShallowRef<FabricObject | null>;

  // Image state.
  originalWidth: Ref<number>;
  originalHeight: Ref<number>;
  imageStraightenAngle: Ref<number>;
  viewportRotation: Ref<number>;
  zoomRatio: Ref<number>;
  scaleFactor: Ref<number>;
  flipData: Ref<FlipData>;
  imageVerticeCoords: ShallowRef<VerticeCoords | null>;
  lastLoadedDimensions: ShallowRef<Dimensions | null>;

  // Editor state.
  editorWidth: Ref<number>;
  editorHeight: Ref<number>;
  currentView: Ref<EditorView>;
  animationInProgress: Ref<boolean>;
  imageIsLoading: Ref<boolean>;
  cropperState: ShallowRef<CropperState | null>;
  focalPointState: ShallowRef<FocalPointState | null>;
  croppingConstraint: Ref<number | false>;
  shiftKeyHeld: Ref<boolean>;
}

/**
 * The elements the editor draws into. The component owns these — it's the one
 * with the template — and hands them over, so the editor never reaches into
 * the DOM to find them.
 */
export interface EditorElements {
  imageCanvas: MaybeRefOrGetter<HTMLCanvasElement | null | undefined>;
  croppingCanvas: MaybeRefOrGetter<HTMLCanvasElement | null | undefined>;
  editor: MaybeRefOrGetter<HTMLElement | null | undefined>;
}

export function useEditorState(
  settings: EditorSettings,
  elements: EditorElements
): EditorState {
  return {
    settings,

    imageCanvasEl: computed(() => toValue(elements.imageCanvas) ?? null),
    croppingCanvasEl: computed(() => toValue(elements.croppingCanvas) ?? null),
    editorEl: computed(() => toValue(elements.editor) ?? null),

    canvas: shallowRef(null),
    croppingCanvas: shallowRef(null),
    image: shallowRef(null),
    viewport: shallowRef(null),
    focalPoint: shallowRef(null),
    previousFocalPoint: shallowRef(null),
    focalPointPickedIndicator: shallowRef(null),
    grid: shallowRef(null),
    clipper: shallowRef(null),
    croppingShade: shallowRef(null),
    croppingRectangle: shallowRef(null),
    cropperHandles: shallowRef(null),
    cropperGrid: shallowRef(null),
    handleFocusIndicator: shallowRef(null),
    moveIcon: shallowRef(null),

    originalWidth: ref(0),
    originalHeight: ref(0),
    imageStraightenAngle: ref(0),
    viewportRotation: ref(0),
    zoomRatio: ref(1),
    scaleFactor: ref(1),
    flipData: ref<FlipData>({x: 0, y: 0}),
    imageVerticeCoords: shallowRef(null),
    lastLoadedDimensions: shallowRef(null),

    editorWidth: ref(0),
    editorHeight: ref(0),
    currentView: ref<EditorView>('rotate'),
    animationInProgress: ref(false),
    imageIsLoading: ref(false),
    cropperState: shallowRef(null),
    focalPointState: shallowRef(null),
    croppingConstraint: ref<number | false>(false),
    shiftKeyHeld: ref(false),
  };
}

/**
 * The geometry that falls out of the current state. Kept as plain functions
 * rather than computeds because most read fabric objects, which aren't
 * reactive — a computed would cache against dependencies that never invalidate.
 */
export function useEditorGeometry(state: EditorState) {
  /** True once the image has been rotated onto its side. */
  function hasOrientationChanged(): boolean {
    return state.viewportRotation.value % 180 !== 0;
  }

  /**
   * The area the image is laid out within: the editor, less the room the
   * cropper's handles need outside the rectangle.
   *
   * The same in every view, so moving between them changes the zoom and
   * nothing else.
   */
  function getContentSize(): Dimensions {
    return {
      width: Math.max(state.editorWidth.value - CROP_HANDLE_MARGIN * 2, 1),
      height: Math.max(state.editorHeight.value - CROP_HANDLE_MARGIN * 2, 1),
    };
  }

  /**
   * The size the image occupies in the editor with no straightening or rotation
   * applied — the basis every other measurement is expressed against.
   */
  function getScaledImageDimensions(): Dimensions {
    const originalWidth = state.originalWidth.value;
    const originalHeight = state.originalHeight.value;
    const {width: availableWidth, height: availableHeight} = getContentSize();

    if (originalHeight / originalWidth > availableHeight / availableWidth) {
      const height = Math.min(availableHeight, originalHeight);

      return {
        height,
        width: Math.round(originalWidth / (originalHeight / height)),
      };
    }

    const width = Math.min(availableWidth, originalWidth);

    return {
      width,
      height: Math.round(originalHeight * (width / originalWidth)),
    };
  }

  /** The zoom needed for a straightened image to still cover its viewport. */
  function getZoomToCoverRatio(dimensions: Dimensions): number {
    // Guards the divisions below: an unmeasured editor yields 0x0 dimensions,
    // and `0 / 0` would hand back NaN for every size derived from this.
    if (!dimensions.width || !dimensions.height) {
      return 1;
    }

    const radians =
      Math.abs(state.imageStraightenAngle.value) * (Math.PI / 180);

    const scaledWidth =
      Math.sin(radians) * dimensions.height +
      Math.cos(radians) * dimensions.width;
    const scaledHeight =
      Math.sin(radians) * dimensions.width +
      Math.cos(radians) * dimensions.height;

    return Math.max(
      scaledWidth / dimensions.width,
      scaledHeight / dimensions.height
    );
  }

  /** The axis-aligned box a straightened image needs, before any zooming. */
  function getImageBoundingBox(dimensions: Dimensions): Dimensions {
    const radians =
      Math.abs(state.imageStraightenAngle.value) * (Math.PI / 180);
    const proportion = dimensions.height / dimensions.width;

    const box = {
      height:
        dimensions.width * (Math.sin(radians) + Math.cos(radians) * proportion),
      width:
        dimensions.width * (Math.cos(radians) + Math.sin(radians) * proportion),
    };

    return hasOrientationChanged()
      ? {width: box.height, height: box.width}
      : box;
  }

  /** The zoom needed for the whole straightened image to fit on screen. */
  function getZoomToFitRatio(dimensions: Dimensions): number {
    const boundingBox = getImageBoundingBox(dimensions);

    if (!boundingBox.width || !boundingBox.height) {
      return 1;
    }

    // The inset now lives in `getScaledImageDimensions`, so the base size is
    // already within the content box; this only has to answer whether the
    // straightened bounding box still fits.
    const {width: availableWidth, height: availableHeight} = getContentSize();

    if (
      boundingBox.height <= availableHeight &&
      boundingBox.width <= availableWidth
    ) {
      return 1;
    }

    return Math.min(
      availableWidth / boundingBox.width,
      availableHeight / boundingBox.height
    );
  }

  function getCombinedZoomRatio(dimensions: Dimensions): number {
    return getZoomToCoverRatio(dimensions) / getZoomToFitRatio(dimensions);
  }

  /**
   * The image's four corners at a given zoom, accounting for both the
   * straightening angle and any 90° rotation. `zoomMode` is 'cover', 'fit', or
   * an explicit ratio.
   */
  function getImageVerticeCoords(
    zoomMode: 'cover' | 'fit' | number
  ): VerticeCoords {
    const radians =
      -1 *
      ((hasOrientationChanged() ? 90 : 0) + state.imageStraightenAngle.value) *
      (Math.PI / 180);

    const dimensions = getScaledImageDimensions();

    const ratio =
      typeof zoomMode === 'number'
        ? zoomMode
        : zoomMode === 'cover'
          ? getZoomToCoverRatio(dimensions)
          : getZoomToFitRatio(dimensions);

    const scaledHeight = dimensions.height * ratio;
    const scaledWidth = dimensions.width * ratio;

    // The segments of the box containing the rotated image, projected onto its
    // right and bottom edges.
    const topVertical = Math.cos(radians) * scaledHeight;
    const bottomVertical = Math.sin(radians) * scaledWidth;
    const rightHorizontal = Math.cos(radians) * scaledWidth;
    const leftHorizontal = Math.sin(radians) * scaledHeight;

    const verticalOffset =
      (state.editorHeight.value - (topVertical + bottomVertical)) / 2;
    const horizontalOffset =
      (state.editorWidth.value - (leftHorizontal + rightHorizontal)) / 2;

    return {
      a: {x: horizontalOffset + rightHorizontal, y: verticalOffset},
      b: {
        x: state.editorWidth.value - horizontalOffset,
        y: verticalOffset + topVertical,
      },
      c: {
        x: horizontalOffset + leftHorizontal,
        y: state.editorHeight.value - verticalOffset,
      },
      d: {x: horizontalOffset, y: verticalOffset + bottomVertical},
    };
  }

  /** Caches the corners of the image as zoomed to fit, for containment tests. */
  function setFittedImageVerticeCoordinates(): void {
    state.imageVerticeCoords.value = getImageVerticeCoords('fit');
  }

  /** The editor center, in editor-space. */
  function getEditorCenter() {
    return {
      x: state.editorWidth.value / 2,
      y: state.editorHeight.value / 2,
    };
  }

  /** Whether the editor has grown enough to warrant refetching a larger image. */
  function needsHigherResolution(): boolean {
    const last = state.lastLoadedDimensions.value;

    if (!last) {
      return false;
    }

    const current = getScaledImageDimensions();

    return (
      current.width / last.width > RELOAD_THRESHOLD ||
      current.height / last.height > RELOAD_THRESHOLD
    );
  }

  return {
    hasOrientationChanged,
    getContentSize,
    getScaledImageDimensions,
    getZoomToCoverRatio,
    getImageBoundingBox,
    getZoomToFitRatio,
    getCombinedZoomRatio,
    getImageVerticeCoords,
    setFittedImageVerticeCoordinates,
    getEditorCenter,
    needsHigherResolution,
  };
}

export type EditorGeometry = ReturnType<typeof useEditorGeometry>;
