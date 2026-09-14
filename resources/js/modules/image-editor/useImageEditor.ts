import {computed, nextTick, onBeforeUnmount, ref, watch} from 'vue';
import {useEventListener, useResizeObserver} from '@vueuse/core';
import {t} from '@craftcms/ui';
import {useHelpers} from '@/common/composables/useCraftData';
import {useActionClient} from '@/common/composables/useFetch';
import {useFlashMessages} from '@/common/composables/useFlashMessages';
import {animate, loadSvg, type FabricAnimatable} from './fabric';
import {useCropper} from './useCropper';
import {
  useCroppingConstraint,
  type ConstraintValue,
} from './useCroppingConstraint';
import {useEditorAnnouncements} from './useEditorAnnouncements';
import {useEditingState, useEditorInteractions} from './useEditorInteractions';
import {
  useEditorGeometry,
  useEditorState,
  type EditorElements,
  type EditorSettings,
} from './useEditorState';
import {useFocalPoint} from './useFocalPoint';
import {useImageCanvas} from './useImageCanvas';
import {useImageTransforms} from './useImageTransforms';
import {useEditHistory} from './useEditHistory';
import type {
  CropperState,
  Dimensions,
  EditorView,
  FlipData,
  FocalPointState,
  RelativeFocalPoint,
} from './types';

export interface ImageEditorOptions {
  assetId: number;
  focalPoint: RelativeFocalPoint | null;
  /** Whether the browser's image driver supports fractional rotation. */
  allowDegreeFractions?: boolean;
  /** The canvases and container the editor draws into. */
  elements: EditorElements;
}

export interface SaveResult {
  newAssetId?: number;
  newAssetUrl?: string;
}

/** `replace` overwrites the asset's file; `copy` saves the result alongside it. */
export type SaveMode = 'replace' | 'copy';

/**
 * The host's own control state -- the selected constraint, the orientation --
 * which a history step has to put back alongside the image.
 */
export interface UiAdapter {
  capture(): unknown;
  /** Called after the editor has restored a snapshot. */
  apply(ui: unknown): void;
}

/** Everything an edit can change, as it stood at one moment. */
interface EditorSnapshot {
  view: EditorView;
  editorWidth: number;
  editorHeight: number;
  viewportRotation: number;
  imageStraightenAngle: number;
  flipData: FlipData;
  zoomRatio: number;
  scaleFactor: number;
  cropperState: CropperState | null;
  croppingConstraint: number | false;
  hasFocalPoint: boolean;
  focalPointState: FocalPointState | null;
  image: {
    angle: number;
    left: number;
    top: number;
    flipX: boolean;
    flipY: boolean;
  };
  viewport: {
    left: number;
    top: number;
    width: number;
    height: number;
    angle: number;
  };
  ui: unknown;
}

/**
 * The edit itself, for comparing snapshots. Offsets are relative to the image,
 * so a resize between snapshots isn't a change.
 */
function editOf(snapshot: EditorSnapshot): string {
  const round = (value: number) => Math.round(value * 1000) / 1000;
  const crop = snapshot.cropperState;
  const focal = snapshot.focalPointState;

  return JSON.stringify({
    rotation: snapshot.viewportRotation,
    straighten: round(snapshot.imageStraightenAngle),
    flip: snapshot.flipData,
    crop: crop
      ? [crop.offsetX, crop.offsetY, crop.width, crop.height].map((value) =>
          round(value / crop.imageDimensions.width)
        )
      : null,
    constraint: snapshot.croppingConstraint,
    focal:
      snapshot.hasFocalPoint && focal
        ? [focal.offsetX, focal.offsetY].map((value) =>
            round(value / focal.imageDimensions.width)
          )
        : null,
    ui: snapshot.ui,
  });
}

function defaultSettings(
  allowDegreeFractions: boolean,
  prefersReducedMotion: boolean
): EditorSettings {
  const styles = window.getComputedStyle(document.documentElement);

  return {
    animationDuration: prefersReducedMotion ? 1 : 100,
    allowDegreeFractions,
    colors: {
      white: 'rgb(255, 255, 255)',
      black: 'rgb(0, 0, 0)',
      transparentBlack: 'rgba(0, 0, 0, 0.8)',
      transparent: 'rgba(0,0,0,0)',
      accent: styles.getPropertyValue('--blue-500') || 'rgb(59, 130, 246)',
    },
  };
}

/**
 * The image editor, assembled.
 *
 * Owns the parts no single feature does: loading the image, keeping everything
 * sized to the editor element, moving between the rotate and crop views, and
 * saving. Everything else is delegated to the composable that owns it.
 */
export function useImageEditor(options: ImageEditorOptions) {
  const prefersReducedMotion = window.matchMedia(
    '(prefers-reduced-motion: reduce)'
  ).matches;

  const state = useEditorState(
    defaultSettings(
      options.allowDegreeFractions ?? false,
      prefersReducedMotion
    ),
    options.elements
  );

  const geometry = useEditorGeometry(state);
  const canvas = useImageCanvas(state, geometry);
  const announcements = useEditorAnnouncements(state);
  const editing = useEditingState();
  const focalPoint = useFocalPoint(state, geometry, canvas, announcements);
  const cropper = useCropper(
    state,
    geometry,
    canvas,
    announcements,
    editing.focusContext
  );
  const constraint = useCroppingConstraint(state, cropper, canvas);
  const transforms = useImageTransforms(
    state,
    geometry,
    canvas,
    cropper,
    focalPoint
  );
  const interactions = useEditorInteractions(
    state,
    editing,
    canvas,
    cropper,
    focalPoint,
    announcements,
    {begin: beginChange, commit: commitChange, record: recordChange}
  );

  const helpers = useHelpers();
  const {flash} = useFlashMessages();

  const isReady = ref(false);
  const savingAs = ref<SaveMode | null>(null);
  const isSaving = computed(() => savingAs.value !== null);
  const cacheBust = ref(Date.now());

  const history = useEditHistory<EditorSnapshot>({
    equals: (a, b) => editOf(a) === editOf(b),
  });
  /** True while a history step is being put back. */
  const restoring = ref(false);
  /** Changes still waiting to settle before they can be recorded. */
  const pendingRecords = ref(0);
  /** The "before" of a gesture that has started and not yet been recorded. */
  const gestureBefore = ref<EditorSnapshot | null>(null);
  let recordingDepth = 0;
  let uiAdapter: UiAdapter | null = null;

  /** The history can't move while an edit is still settling. */
  const canStep = computed(
    () =>
      isReady.value &&
      !restoring.value &&
      !state.animationInProgress.value &&
      pendingRecords.value === 0 &&
      gestureBefore.value === null
  );
  const canUndo = computed(() => canStep.value && history.canUndo.value);
  const canRedo = computed(() => canStep.value && history.canRedo.value);

  function captureSnapshot(): EditorSnapshot | null {
    const image = state.image.value;
    const viewport = state.viewport.value;
    const crop = state.cropperState.value;
    const focal = state.focalPointState.value;

    if (!image || !viewport) {
      return null;
    }

    return {
      view: state.currentView.value,
      editorWidth: state.editorWidth.value,
      editorHeight: state.editorHeight.value,
      viewportRotation: state.viewportRotation.value,
      imageStraightenAngle: state.imageStraightenAngle.value,
      flipData: {...state.flipData.value},
      zoomRatio: state.zoomRatio.value,
      scaleFactor: state.scaleFactor.value,
      cropperState: crop
        ? {...crop, imageDimensions: {...crop.imageDimensions}}
        : null,
      croppingConstraint: state.croppingConstraint.value,
      hasFocalPoint: state.focalPoint.value !== null,
      focalPointState: focal
        ? {...focal, imageDimensions: {...focal.imageDimensions}}
        : null,
      image: {
        angle: image.angle,
        left: image.left,
        top: image.top,
        flipX: image.flipX,
        flipY: image.flipY,
      },
      viewport: {
        left: viewport.left,
        top: viewport.top,
        width: viewport.width,
        height: viewport.height,
        angle: viewport.angle,
      },
      ui: uiAdapter?.capture() ?? null,
    };
  }

  /** Resolves once a view change, and anything it animates, has finished. */
  async function settle(): Promise<void> {
    await nextTick();
    await new Promise<void>((resolve) =>
      requestAnimationFrame(() => resolve())
    );

    if (!state.animationInProgress.value) {
      return;
    }

    await new Promise<void>((resolve) => {
      const stop = watch(
        () => state.animationInProgress.value,
        (busy) => {
          if (!busy) {
            stop();
            resolve();
          }
        }
      );
    });
  }

  function recordAfterSettling(before: EditorSnapshot, key?: string): void {
    pendingRecords.value += 1;

    void settle().then(() => {
      pendingRecords.value -= 1;
      const after = captureSnapshot();

      if (after) {
        history.record(before, after, key);
      }
    });
  }

  /**
   * Records whatever `work` changes as one history step. Nests: an operation
   * that records itself, run from inside a larger change, is part of that
   * change rather than a step of its own.
   */
  function recordChange(work: () => void, key?: string): void {
    if (
      recordingDepth > 0 ||
      restoring.value ||
      gestureBefore.value ||
      !isReady.value
    ) {
      work();
      return;
    }

    const before = captureSnapshot();
    recordingDepth += 1;

    try {
      work();
    } finally {
      recordingDepth -= 1;
    }

    if (before) {
      recordAfterSettling(before, key);
    }
  }

  /** Opens a gesture -- a drag, a straightening slide -- as one step. */
  function beginChange(): void {
    if (gestureBefore.value || restoring.value || !isReady.value) {
      return;
    }

    gestureBefore.value = captureSnapshot();
  }

  function commitChange(key?: string): void {
    const before = gestureBefore.value;
    gestureBefore.value = null;

    if (before) {
      recordAfterSettling(before, key);
    }
  }

  /**
   * Writes a snapshot's values back rather than recomputing them: straightening
   * and rotating don't invert exactly.
   */
  async function restoreSnapshot(snapshot: EditorSnapshot): Promise<void> {
    restoring.value = true;

    try {
      // Restore in the snapshot's own view.
      if (snapshot.view !== state.currentView.value) {
        showView(snapshot.view);
        await settle();
      }

      const image = state.image.value;
      const viewport = state.viewport.value;

      if (!image || !viewport) {
        return;
      }

      const measured = {
        width: state.editorWidth.value,
        height: state.editorHeight.value,
      };

      // Written against the size the snapshot was taken at; a resize since is
      // corrected below from the restored state.
      state.editorWidth.value = snapshot.editorWidth;
      state.editorHeight.value = snapshot.editorHeight;
      state.viewportRotation.value = snapshot.viewportRotation;
      state.imageStraightenAngle.value = snapshot.imageStraightenAngle;
      state.flipData.value = {...snapshot.flipData};
      state.zoomRatio.value = snapshot.zoomRatio;
      state.scaleFactor.value = snapshot.scaleFactor;
      state.cropperState.value = snapshot.cropperState
        ? {
            ...snapshot.cropperState,
            imageDimensions: {...snapshot.cropperState.imageDimensions},
          }
        : null;
      state.croppingConstraint.value = snapshot.croppingConstraint;
      state.focalPointState.value = snapshot.focalPointState
        ? {
            ...snapshot.focalPointState,
            imageDimensions: {...snapshot.focalPointState.imageDimensions},
          }
        : null;

      image.set({
        angle: snapshot.image.angle,
        left: snapshot.image.left,
        top: snapshot.image.top,
      });
      image.flipX = snapshot.image.flipX;
      image.flipY = snapshot.image.flipY;
      canvas.zoomImage();
      viewport.set({...snapshot.viewport});
      transforms.hideGrid();

      restoreFocalMarker(snapshot);

      if (
        measured.width !== snapshot.editorWidth ||
        measured.height !== snapshot.editorHeight
      ) {
        updateSizeAndPosition();
      } else {
        geometry.setFittedImageVerticeCoordinates();

        if (state.currentView.value === 'crop') {
          cropper.restoreFromState();
          canvas.renderCropper();
        }
      }

      editing.reset();
      uiAdapter?.apply(snapshot.ui);
      canvas.renderImage();
    } finally {
      restoring.value = false;
    }
  }

  /**
   * Brings the focal point marker in line with a snapshot. It's taken off the
   * canvas first whatever happens, so it can never end up there twice.
   */
  function restoreFocalMarker(snapshot: EditorSnapshot): void {
    const existing = state.focalPoint.value;

    if (existing) {
      state.canvas.value?.remove(existing);
    }

    if (!snapshot.hasFocalPoint) {
      state.focalPoint.value = null;
      return;
    }

    if (!existing) {
      // `create()` places a marker at the middle of the view when its offset
      // is zero; the recorded state is put back over that below.
      const recorded = state.focalPointState.value;
      focalPoint.create();
      state.focalPointState.value = recorded;
    }

    const marker = state.focalPoint.value;

    if (!marker) {
      return;
    }

    state.canvas.value?.remove(marker);
    focalPoint.positionFromState();
    focalPoint.setPickedUpStyles(false);

    // Off the canvas while cropping, as it is whenever the crop view opens.
    if (state.currentView.value !== 'crop') {
      state.canvas.value?.add(marker);
      focalPoint.updateVisibilityForViewport();
    }
  }

  function undo(): void {
    if (!canUndo.value) {
      return;
    }

    history.seal();
    const snapshot = history.undo();

    if (snapshot) {
      void restoreSnapshot(snapshot);
    }
  }

  function redo(): void {
    if (!canRedo.value) {
      return;
    }

    const snapshot = history.redo();

    if (snapshot) {
      void restoreSnapshot(snapshot);
    }
  }

  /**
   * Mode transitions animate, so overlapping ones would fight. They're chained
   * onto a single promise rather than run concurrently.
   */
  let transitionChain: Promise<void> = Promise.resolve();

  function enqueue(work: () => void): void {
    transitionChain = transitionChain.then(
      () =>
        new Promise<void>((resolve) => {
          work();
          resolve();
        })
    );
  }

  /** `getActionUrl()` may already carry a query string. */
  function imageUrl(): string {
    const url = new URL(helpers.getActionUrl('assets/edit-image'));

    url.searchParams.set('assetId', String(options.assetId));
    url.searchParams.set('size', String(canvas.getMaxImageSize()));
    url.searchParams.set('cacheBust', String(cacheBust.value));

    return url.toString();
  }

  /**
   * Re-lays out everything after the editor element changes size. Order
   * matters: the zoom ratio has to settle before anything is repositioned
   * against it.
   */
  function updateSizeAndPosition(): void {
    if (!state.image.value || !state.editorEl.value) {
      return;
    }

    const previous: Dimensions = {
      width: state.editorWidth.value,
      height: state.editorHeight.value,
    };

    canvas.measureEditor();
    canvas.resizeCanvases();

    const cropping = state.currentView.value === 'crop';

    if (cropping) {
      state.zoomRatio.value = geometry.getZoomToFitRatio(
        geometry.getScaledImageDimensions()
      );

      geometry.setFittedImageVerticeCoordinates();
    } else {
      state.zoomRatio.value =
        geometry.getZoomToCoverRatio(geometry.getScaledImageDimensions()) *
        state.scaleFactor.value;
    }

    canvas.repositionImage(previous);
    canvas.repositionViewport();
    canvas.zoomImage();

    // After the image moves: these are positioned relative to it.
    if (cropping) {
      cropper.reposition();
    }

    focalPoint.positionFromState();

    canvas.renderImage();

    if (geometry.needsHigherResolution()) {
      canvas.reloadImage(imageUrl(), updateSizeAndPosition);
    }
  }

  /** Animates the image and viewport between the rotate and crop layouts. */
  function transitionMode(
    imageProperties: FabricAnimatable,
    viewportProperties: FabricAnimatable,
    onComplete: () => void
  ): void {
    const image = state.image.value;
    const viewport = state.viewport.value;

    if (state.animationInProgress.value || !image || !viewport) {
      return;
    }

    state.animationInProgress.value = true;

    // The marker looks broken mid-animation, so it's lifted off and put back.
    if (state.focalPoint.value) {
      state.canvas.value?.remove(state.focalPoint.value);
      canvas.renderImage();
    }

    animate(image, imageProperties, {
      duration: state.settings.animationDuration,
      onChange: () => state.canvas.value?.renderAll(),
      onComplete: () => {
        onComplete();
        state.animationInProgress.value = false;
        canvas.renderImage();
      },
    });

    animate(viewport, viewportProperties, {
      duration: state.settings.animationDuration,
    });
  }

  /** Zooms the whole image into view and puts the cropping rectangle back. */
  function enableCropMode(): void {
    const dimensions = geometry.getScaledImageDimensions();
    state.zoomRatio.value = geometry.getZoomToFitRatio(dimensions);

    const fitScale = geometry.getImageScaleFor(
      dimensions.width * state.zoomRatio.value
    );

    transitionMode(
      {
        scaleX: fitScale,
        scaleY: fitScale,
        left: state.editorWidth.value / 2,
        top: state.editorHeight.value / 2,
      },
      {width: state.editorWidth.value, height: state.editorHeight.value},
      () => {
        geometry.setFittedImageVerticeCoordinates();

        const cropperState = state.cropperState.value;
        const image = state.image.value;

        if (!cropperState || !image) {
          return;
        }

        const sizeFactor =
          geometry.getScaledImageDimensions().width /
          cropperState.imageDimensions.width;
        const scale = sizeFactor * state.zoomRatio.value;

        cropper.show({
          left: image.left + cropperState.offsetX * scale,
          top: image.top + cropperState.offsetY * scale,
          width: cropperState.width * scale,
          height: cropperState.height * scale,
        });

        // Keep the marker hidden while cropping, but positioned: `disableCropMode`
        // checks it against the crop.
        focalPoint.positionFromState();
      }
    );
  }

  /** Zooms back to the cropped region and tears the cropping layer down. */
  function disableCropMode(): void {
    const clipper = state.clipper.value;
    const image = state.image.value;

    if (!clipper || !image) {
      return;
    }

    const clipperBounds = {
      left: clipper.left,
      top: clipper.top,
      width: clipper.width,
      height: clipper.height,
    };
    const offsetX = clipper.left - image.left;
    const offsetY = clipper.top - image.top;

    cropper.hide();

    const dimensions = geometry.getScaledImageDimensions();
    const targetZoom =
      geometry.getZoomToCoverRatio(dimensions) * state.scaleFactor.value;
    const inverseZoomFactor = targetZoom / state.zoomRatio.value;
    state.zoomRatio.value = targetZoom;

    // A focal point outside the new crop no longer means anything, so it goes.
    const marker = state.focalPoint.value;

    if (
      !marker ||
      !(
        marker.left > clipperBounds.left - clipperBounds.width / 2 &&
        marker.top > clipperBounds.top - clipperBounds.height / 2 &&
        marker.left < clipperBounds.left + clipperBounds.width / 2 &&
        marker.top < clipperBounds.top + clipperBounds.height / 2
      )
    ) {
      if (marker) {
        focalPoint.toggle();
      }

      focalPoint.resetPosition();
    }

    const coverScale = geometry.getImageScaleFor(
      dimensions.width * state.zoomRatio.value
    );

    transitionMode(
      {
        scaleX: coverScale,
        scaleY: coverScale,
        left: state.editorWidth.value / 2 - offsetX * inverseZoomFactor,
        top: state.editorHeight.value / 2 - offsetY * inverseZoomFactor,
      },
      {
        width: clipperBounds.width * inverseZoomFactor,
        height: clipperBounds.height * inverseZoomFactor,
      },
      () => {
        if (state.focalPoint.value) {
          focalPoint.restoreFromState();
        }
      }
    );
  }

  function showView(view: EditorView): void {
    if (state.currentView.value === view) {
      return;
    }

    const previousView = state.currentView.value;

    // Switch first so the layout settles before anything is measured.
    state.currentView.value = view;

    // Drop anything picked up; it can't be edited in the other view.
    editing.reset();
    focalPoint.setPickedUpStyles(false);

    void nextTick().then(() => {
      // Measure once the layout has settled. Both transitions set the image's
      // position outright, so the previous size isn't needed.
      canvas.measureEditor();
      canvas.resizeCanvases();

      if (previousView === 'crop' && view !== 'crop') {
        enqueue(disableCropMode);
      } else if (previousView !== 'crop' && view === 'crop') {
        enqueue(enableCropMode);
      }
    });
  }

  /** Parses the move icon out of the DOM so the cropper can draw it on canvas. */
  async function loadMoveIcon(): Promise<void> {
    const svg = state.editorEl.value
      ?.closest('.image-editor')
      ?.querySelector('#move-icon-wrapper svg')?.outerHTML;

    if (!svg) {
      return;
    }

    try {
      const icon = await loadSvg(svg);

      icon.set({
        left: 0,
        top: 0,
        scaleX: 0.03,
        scaleY: 0.03,
        originX: 'center',
        originY: 'center',
        fill: 'white',
      });

      state.moveIcon.value = icon;
    } catch {
      // A missing move icon costs an affordance, not the editor.
    }
  }

  /**
   * The focal point's offsets as the asset arrived, expressed as fractions of
   * the image so a later editor resize doesn't read as a change.
   */
  let seededFocalOffset: {x: number; y: number} | null = null;

  /** Seeds the focal point state from the asset's stored relative position. */
  function seedFocalPoint(): void {
    const dimensions = geometry.getScaledImageDimensions();

    const focalState = {
      imageDimensions: dimensions,
      offsetX: 0,
      offsetY: 0,
    };

    if (options.focalPoint) {
      focalState.offsetX =
        dimensions.width * options.focalPoint.x - dimensions.width / 2;
      focalState.offsetY =
        dimensions.height * options.focalPoint.y - dimensions.height / 2;
    }

    focalPoint.storeFocalPointState(focalState);

    seededFocalOffset = {
      x: focalState.offsetX / dimensions.width,
      y: focalState.offsetY / dimensions.height,
    };

    if (options.focalPoint) {
      focalPoint.create();
    }
  }

  /** Whether anything has changed since the image loaded, derived from state. */
  const isDirty = computed(() => {
    if (!isReady.value) {
      return false;
    }

    if (
      state.viewportRotation.value !== 0 ||
      state.imageStraightenAngle.value !== 0 ||
      state.flipData.value.x !== 0 ||
      state.flipData.value.y !== 0
    ) {
      return true;
    }

    // Sub-pixel wobble from repeated zoom maths isn't an edit.
    const tolerance = 1;
    const crop = state.cropperState.value;

    if (
      crop &&
      (Math.abs(crop.offsetX) > tolerance ||
        Math.abs(crop.offsetY) > tolerance ||
        crop.imageDimensions.width - crop.width > tolerance ||
        crop.imageDimensions.height - crop.height > tolerance)
    ) {
      return true;
    }

    if (Boolean(state.focalPoint.value) !== Boolean(options.focalPoint)) {
      return true;
    }

    const focalState = state.focalPointState.value;

    if (state.focalPoint.value && focalState && seededFocalOffset) {
      const moved =
        Math.abs(
          focalState.offsetX / focalState.imageDimensions.width -
            seededFocalOffset.x
        ) > 0.001 ||
        Math.abs(
          focalState.offsetY / focalState.imageDimensions.height -
            seededFocalOffset.y
        ) > 0.001;

      if (moved) {
        return true;
      }
    }

    return false;
  });

  /**
   * Discards every edit without refetching the image. The current view is kept.
   */
  function reset(): void {
    const image = state.image.value;

    if (!image || state.animationInProgress.value) {
      return;
    }

    state.imageStraightenAngle.value = 0;
    state.viewportRotation.value = 0;
    state.scaleFactor.value = 1;
    state.flipData.value = {x: 0, y: 0};

    // A mirror is held in `flipX`/`flipY` with the scale kept positive (see
    // `flip()`), so clearing the flags is what un-mirrors the image.
    image.flipX = false;
    image.flipY = false;
    image.set({
      angle: 0,
      scaleX: 1,
      scaleY: 1,
      left: state.editorWidth.value / 2,
      top: state.editorHeight.value / 2,
    });

    if (state.focalPoint.value) {
      state.canvas.value?.remove(state.focalPoint.value);
      state.focalPoint.value = null;
    }

    state.previousFocalPoint.value = null;

    const dimensions = geometry.getScaledImageDimensions();
    const cropping = state.currentView.value === 'crop';

    state.zoomRatio.value = cropping
      ? geometry.getZoomToFitRatio(dimensions)
      : geometry.getZoomToCoverRatio(dimensions);

    canvas.zoomImage();
    geometry.setFittedImageVerticeCoordinates();

    seedFocalPoint();

    cropper.storeCropperState({
      offsetX: 0,
      offsetY: 0,
      width: dimensions.width,
      height: dimensions.height,
      imageDimensions: dimensions,
    });

    canvas.repositionViewport();

    if (cropping) {
      cropper.restoreFromState();
      canvas.renderCropper();
    }

    canvas.renderImage();

    // Back to the original, so there is nothing before it to undo to.
    history.clear();
    gestureBefore.value = null;
  }

  async function load(): Promise<void> {
    canvas.measureEditor();

    await loadMoveIcon();

    try {
      await canvas.createCanvas(imageUrl());
    } catch (error) {
      // Logged as well as flashed, so the cause isn't lost.
      console.error('Image editor failed to load the image:', error);
      flash('error', t('Could not load the image for editing.'));
      return;
    }

    canvas.resizeCanvases();
    geometry.setFittedImageVerticeCoordinates();

    // The zoom has to be established before anything is positioned against it.
    state.zoomRatio.value =
      geometry.getZoomToCoverRatio(geometry.getScaledImageDimensions()) *
      state.scaleFactor.value;

    canvas.zoomImage();

    seedFocalPoint();
    canvas.createViewport();
    cropper.storeCropperState();
    canvas.renderImage();

    isReady.value = true;
  }

  const {
    data: saveResult,
    state: saveState,
    execute: postSave,
  } = useActionClient<SaveResult>('assets/save-image', {
    onError: () => flash('error', t('Could not save the image.')),
  });

  /**
   * Posts the accumulated edits. The server replays them against the original
   * file, so what goes up is the description of the transform, not pixels.
   */
  async function save(mode: SaveMode): Promise<SaveResult | null> {
    if (savingAs.value) {
      return null;
    }

    savingAs.value = mode;

    const cropperState = state.cropperState.value;
    const dimensions =
      cropperState?.imageDimensions ?? geometry.getScaledImageDimensions();

    try {
      await postSave({
        assetId: options.assetId,
        viewportRotation: state.viewportRotation.value,
        imageRotation: state.imageStraightenAngle.value,
        replace: mode === 'replace' ? 1 : 0,
        imageDimensions: {...dimensions},
        ...(cropperState
          ? {
              cropData: {
                height: cropperState.height,
                width: cropperState.width,
                offsetX: cropperState.offsetX,
                offsetY: cropperState.offsetY,
              },
            }
          : {}),
        ...(state.focalPoint.value && state.focalPointState.value
          ? {
              focalPoint: {
                offsetX: state.focalPointState.value.offsetX,
                offsetY: state.focalPointState.value.offsetY,
                imageDimensions: {
                  ...state.focalPointState.value.imageDimensions,
                },
              },
            }
          : {}),
        flipData: {...state.flipData.value},
        zoom: state.zoomRatio.value,
      });

      // `execute` doesn't throw on failure, so check the state.
      if (saveState.value !== 'success') {
        return null;
      }

      cacheBust.value = Date.now();

      flash(
        'success',
        mode === 'replace'
          ? t('Image saved.')
          : t('Image saved as a new asset.')
      );

      return saveResult.value ?? {};
    } finally {
      savingAs.value = null;
    }
  }

  let loadStarted = false;
  /** A resize arrived mid-animation and still needs applying. */
  let resizePending = false;
  /** Set once the host says the editor's container has settled. */
  let started = false;

  /**
   * The container has no size until the dialog opens, so its first real size
   * triggers the load and later sizes trigger a re-layout.
   */
  function onEditorResized(): void {
    // Don't store this: `updateSizeAndPosition()` shifts the image using the
    // previous size.
    const el = state.editorEl.value;

    if (!el?.clientWidth || !el?.clientHeight) {
      return;
    }

    if (!isReady.value) {
      if (started && !loadStarted) {
        loadStarted = true;
        void load();
      }

      return;
    }

    // Re-laying out mid-transition would be undone when it lands, so wait.
    if (state.animationInProgress.value) {
      resizePending = true;

      return;
    }

    updateSizeAndPosition();
  }

  // Covers every animation, not just mode transitions: rotate, flip and the
  // constraint reshape all park `animationInProgress` the same way.
  watch(
    () => state.animationInProgress.value,
    (busy) => {
      if (!busy && resizePending) {
        resizePending = false;
        // `updateSizeAndPosition` re-derives the rectangle and the focal
        // point from their stored state, which is what a transition placed
        // against a since-moved image needs.
        updateSizeAndPosition();
      }
    }
  );

  // Registered during setup rather than in `onMounted`: VueUse hangs its
  // cleanup on the active effect scope, and there isn't one inside a mounted
  // hook, so these would never be torn down.
  useResizeObserver(state.editorEl, onEditorResized);
  useEventListener(document, 'keydown', interactions.onKeyDown);
  useEventListener(document, 'keyup', interactions.onKeyUp);

  /**
   * Turns the crop on its side for the orientation switch. An active constraint
   * is re-read from the new shape, which keeps `original` correct too.
   */
  function turnCrop(): void {
    const hadConstraint = state.croppingConstraint.value !== false;
    const turned = cropper.transpose();

    if (turned && hadConstraint) {
      state.croppingConstraint.value = turned.width / turned.height;
    }
  }

  /**
   * Starts loading. Call once the container has its final size — in a dialog,
   * after `craft-after-show`.
   */
  function start(): void {
    started = true;
    onEditorResized();
  }

  onBeforeUnmount(() => {
    canvas.destroy();
  });

  return {
    state,
    start,
    reset,
    isDirty,
    isReady,
    isSaving,
    savingAs,
    cursor: interactions.cursor,
    editing,

    // Views
    showView,

    // History
    undo,
    redo,
    canUndo,
    canRedo,
    recordChange,
    beginChange,
    commitChange,
    setUiAdapter: (adapter: UiAdapter | null) => {
      uiAdapter = adapter;
    },

    // Transforms
    rotate: (degrees: 90 | -90) =>
      recordChange(() => transforms.rotate(degrees)),
    flip: (axis: 'x' | 'y') => recordChange(() => transforms.flip(axis)),
    straighten: transforms.straighten,
    showGrid: transforms.showGrid,
    hideGrid: transforms.hideGrid,
    cleanupFocalPointAfterStraighten: focalPoint.cleanupAfterStraighten,

    // Focal point
    toggleFocalPoint: () => recordChange(() => focalPoint.toggle()),

    // Cropping constraint
    applyConstraint: (value: ConstraintValue) =>
      recordChange(() => constraint.apply(value)),
    turnCrop: () => recordChange(turnCrop),
    applyCustomConstraint: (width: number, height: number) =>
      recordChange(() => {
        constraint.setCustomConstraint(width, height);
        constraint.enforce();
      }),

    // Pointer
    onPointerDown: interactions.onPointerDown,
    onPointerMove: interactions.onPointerMove,
    onPointerUp: interactions.onPointerUp,
    onPointerLeave: interactions.onPointerLeave,

    // Keyboard editing
    onEditButtonClick: interactions.onEditButtonClick,
    onEditButtonKeydown: interactions.onEditButtonKeydown,
    onEditButtonFocus: interactions.onEditButtonFocus,
    onEditButtonBlur: interactions.onEditButtonBlur,

    save,
    updateSizeAndPosition,
  };
}
