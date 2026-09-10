import {computed, onBeforeUnmount, ref, watch} from 'vue';
import {useEventListener, useResizeObserver} from '@vueuse/core';
import {t} from '@craftcms/ui';
import {useHelpers} from '@/common/composables/useCraftData';
import {useActionClient} from '@/common/composables/useFetch';
import {useFlashMessages} from '@/common/composables/useFlashMessages';
import {loadSvg} from './fabric';
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
import type {Dimensions, EditorView, RelativeFocalPoint} from './types';

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
}

/** `replace` overwrites the asset's file; `copy` saves the result alongside it. */
export type SaveMode = 'replace' | 'copy';

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
    announcements
  );

  const helpers = useHelpers();
  const {flash} = useFlashMessages();

  const isReady = ref(false);
  /**
   * Which save is running, rather than a single flag — the two buttons post the
   * same edits to the same asset, so only one runs at a time, but each spins on
   * its own.
   */
  const savingAs = ref<SaveMode | null>(null);
  const isSaving = computed(() => savingAs.value !== null);
  /** Bumped on save so a reloaded image isn't served from cache. */
  const cacheBust = ref(Date.now());

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

  /**
   * `getActionUrl()` can already carry a query string (`?site=…`), so the
   * params go on through `searchParams` rather than being concatenated behind
   * a second `?`.
   */
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

    if (state.currentView.value === 'crop') {
      state.zoomRatio.value = geometry.getZoomToFitRatio(
        geometry.getScaledImageDimensions()
      );

      const previouslyOccupied = geometry.getOccupiedArea();
      geometry.setFittedImageVerticeCoordinates();

      if (previouslyOccupied) {
        cropper.reposition(previouslyOccupied);
      }
    } else {
      state.zoomRatio.value =
        geometry.getZoomToCoverRatio(geometry.getScaledImageDimensions()) *
        state.scaleFactor.value;
    }

    canvas.repositionImage(previous);
    canvas.repositionViewport();
    focalPoint.reposition(previous);
    canvas.zoomImage();
    canvas.renderImage();

    if (geometry.needsHigherResolution()) {
      canvas.reloadImage(imageUrl(), updateSizeAndPosition);
    }
  }

  /** Animates the image and viewport between the rotate and crop layouts. */
  function transitionMode(
    imageProperties: Record<string, unknown>,
    viewportProperties: Record<string, unknown>,
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

    image.animate(imageProperties, {
      duration: state.settings.animationDuration,
      onChange: () => state.canvas.value?.renderAll(),
      onComplete: () => {
        onComplete();
        state.animationInProgress.value = false;
        canvas.renderImage();
      },
    });

    viewport.animate(viewportProperties, {
      duration: state.settings.animationDuration,
    });
  }

  /** Zooms the whole image into view and puts the cropping rectangle back. */
  function enableCropMode(): void {
    const dimensions = geometry.getScaledImageDimensions();
    state.zoomRatio.value = geometry.getZoomToFitRatio(dimensions);

    transitionMode(
      {
        width: dimensions.width * state.zoomRatio.value,
        height: dimensions.height * state.zoomRatio.value,
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

        if (state.focalPoint.value) {
          focalPoint.restoreFromState();
        }
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

    transitionMode(
      {
        width: dimensions.width * state.zoomRatio.value,
        height: dimensions.height * state.zoomRatio.value,
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

    updateSizeAndPosition();

    if (previousView === 'crop' && view !== 'crop') {
      enqueue(disableCropMode);
    } else if (previousView !== 'crop' && view === 'crop') {
      enqueue(enableCropMode);
    }

    state.currentView.value = view;
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

    if (options.focalPoint) {
      focalPoint.create();
    }
  }

  async function load(): Promise<void> {
    canvas.measureEditor();

    await loadMoveIcon();

    try {
      await canvas.createCanvas(imageUrl());
    } catch (error) {
      // Surfaced as well as flashed: the flash says something went wrong, the
      // console says what, which a bare `catch` would have thrown away.
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

      // `execute` resolves whether or not the request succeeded — it reports
      // failure through `state` and `onError` instead of throwing — so success
      // has to be checked rather than assumed.
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
   * The editor takes every measurement from its container, so nothing can
   * happen until that container has a size. Inside a dialog it has none at all
   * until the dialog opens — loading before then measured zero, which put the
   * image's centre at the origin and made the zoom ratio `NaN`.
   *
   * So the first real size drives the load, and every size after it drives a
   * re-layout.
   */
  function onEditorResized(): void {
    // Read the element without storing the result. `updateSizeAndPosition()`
    // shifts the image by how much the editor changed, which it works out from
    // the previous `editorWidth`/`editorHeight` — measuring here first would
    // overwrite those with the new size, make the delta zero, and leave the
    // image parked where the old size put it.
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

    // A transition animates the image towards targets worked out from the
    // editor's size when it started. Re-laying out underneath it would be
    // undone the moment it lands — the image would keep the old geometry while
    // the zoom and the image quad had moved on, and every containment test
    // against that stale image would fail. So wait for it to finish.
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
        updateSizeAndPosition();

        // The transition placed the rectangle against wherever the image was
        // when the animation started; the resize we just applied has since
        // moved the image. Re-derive rather than translate, or the two stay
        // out of step and every containment test fails.
        if (state.currentView.value === 'crop') {
          cropper.restoreFromState();
          canvas.renderCropper();
        }
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
   * Stands the cropping rectangle the other way up, for the orientation switch.
   *
   * The rectangle carries the ratio once it has turned, so an active constraint
   * is re-read from the new shape rather than recomputed from the option — that
   * keeps `original` (the image's own ratio) correct too, which inverting the
   * option's value wouldn't, since it isn't a number to invert.
   */
  function turnCrop(): void {
    const hadConstraint = state.croppingConstraint.value !== false;
    const turned = cropper.transpose();

    if (turned && hadConstraint) {
      state.croppingConstraint.value = turned.width / turned.height;
    }
  }

  /**
   * Begins loading, once the host confirms the editor's container has settled
   * at its final size.
   *
   * Everything here is measured off that container, and a container that
   * resizes *after* layout is what produced a run of bugs: the image ended up
   * sized for one editor while the zoom, the image quad and the cropping
   * rectangle were computed for another, and every containment test against
   * that mismatch failed. Inside a dialog the container has no size until it
   * opens and keeps changing while it animates, so the dialog waits for
   * `craft-after-show` — opened *and* finished updating — before calling this.
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
    isReady,
    isSaving,
    savingAs,
    cursor: interactions.cursor,
    editing,

    // Views
    showView,

    // Transforms
    rotate: transforms.rotate,
    flip: transforms.flip,
    straighten: transforms.straighten,
    showGrid: transforms.showGrid,
    hideGrid: transforms.hideGrid,
    cleanupFocalPointAfterStraighten: focalPoint.cleanupAfterStraighten,

    // Focal point
    toggleFocalPoint: focalPoint.toggle,

    // Cropping constraint
    applyConstraint: (value: ConstraintValue) => constraint.apply(value),
    turnCrop,
    applyCustomConstraint: (width: number, height: number) => {
      constraint.setCustomConstraint(width, height);
      constraint.enforce();
    },

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
