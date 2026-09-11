import {expect, it} from 'vite-plus/test';
import {arePointsInsideRectangle, getRectangleVertices} from './geometry';
import {useEditorGeometry, useEditorState} from './useEditorState';
import type {CropperState, Rectangle} from './types';

function makeEditor(width: number, height: number) {
  const state = useEditorState(
    {
      animationDuration: 0,
      allowDegreeFractions: false,
      colors: {
        white: '#fff',
        black: '#000',
        transparentBlack: 'rgba(0,0,0,0.8)',
        transparent: 'rgba(0,0,0,0)',
        accent: '#00f',
      },
    },
    {imageCanvas: null, croppingCanvas: null, editor: null}
  );

  state.editorWidth.value = width;
  state.editorHeight.value = height;

  return {state, geometry: useEditorGeometry(state)};
}

/**
 * Where the cropping rectangle lands, mirroring `useCropper.restoreFromState()`
 * — the image's centre plus the stored offset, scaled to the current zoom.
 */
function clipperFor(
  cropperState: CropperState,
  imageCentre: {x: number; y: number},
  scaledWidth: number,
  zoom: number
): Rectangle {
  const scale = (scaledWidth / cropperState.imageDimensions.width) * zoom;
  const width = cropperState.width * scale;
  const height = cropperState.height * scale;

  return {
    left: imageCentre.x + cropperState.offsetX * scale - width / 2,
    top: imageCentre.y + cropperState.offsetY * scale - height / 2,
    width,
    height,
  };
}

/**
 * The invariant the cropper depends on: the rectangle has to sit inside the
 * image quad, because every drag is tested against it. Three separate bugs —
 * a transition racing a resize, a clobbered measurement baseline, and a
 * rectangle translated instead of re-derived — all surfaced as this being
 * false, and as the cropper silently refusing to move.
 */
function clipperFitsImage(width: number, height: number, image: number) {
  const {state, geometry} = makeEditor(width, height);

  state.originalWidth.value = image;
  state.originalHeight.value = image;

  const dimensions = geometry.getScaledImageDimensions();
  state.zoomRatio.value = geometry.getZoomToFitRatio(dimensions);

  const cropperState: CropperState = {
    offsetX: 0,
    offsetY: 0,
    width: dimensions.width,
    height: dimensions.height,
    imageDimensions: dimensions,
  };

  const quad = geometry.getImageVerticeCoords('fit');
  const clipper = clipperFor(
    cropperState,
    {x: width / 2, y: height / 2},
    dimensions.width,
    state.zoomRatio.value
  );

  return {quad, clipper, geometry, state, dimensions};
}

it('keeps the cropping rectangle inside the image at any editor size', () => {
  const sizes: Array<[number, number]> = [
    [1168, 574],
    [1168, 514],
    [1155, 670],
    [600, 900],
    [900, 600],
  ];

  for (const [w, h] of sizes) {
    const {quad, clipper} = clipperFitsImage(w, h, 3000);

    expect(arePointsInsideRectangle(getRectangleVertices(clipper), quad)).toBe(
      true
    );
  }
});

it('keeps it inside after the editor resizes, which is what used to break', () => {
  // The dialog opens short and settles taller. The rectangle is re-derived
  // from the stored state, so it has to land on the image at the new size —
  // translating it instead is what left it 30px above the image.
  const before = clipperFitsImage(1168, 514, 3000);

  const cropperState: CropperState = {
    offsetX: 0,
    offsetY: 0,
    width: before.dimensions.width,
    height: before.dimensions.height,
    imageDimensions: before.dimensions,
  };

  const {state, geometry} = makeEditor(1168, 574);
  state.originalWidth.value = 3000;
  state.originalHeight.value = 3000;

  const dimensions = geometry.getScaledImageDimensions();
  state.zoomRatio.value = geometry.getZoomToFitRatio(dimensions);

  const quad = geometry.getImageVerticeCoords('fit');
  const clipper = clipperFor(
    cropperState,
    {x: 1168 / 2, y: 574 / 2},
    dimensions.width,
    state.zoomRatio.value
  );

  expect(arePointsInsideRectangle(getRectangleVertices(clipper), quad)).toBe(
    true
  );
});

it('leaves room around the image for the cropper handles', () => {
  // The handles are drawn outside the rectangle; with the image flush to the
  // canvas edge they were clipped away and half their grab area sat off-canvas.
  const {quad} = clipperFitsImage(1155, 670, 3000);

  expect(quad.d.x).toBeGreaterThanOrEqual(4);
  expect(quad.d.y).toBeGreaterThanOrEqual(4);
  expect(quad.b.x).toBeLessThanOrEqual(1155 - 4);
  expect(quad.b.y).toBeLessThanOrEqual(670 - 4);
});

it('never returns a zero or NaN zoom for an unmeasured editor', () => {
  // A dialog's container has no size until it opens; `0 / 0` used to poison
  // every measurement downstream with NaN.
  const {state, geometry} = makeEditor(0, 0);

  state.originalWidth.value = 3000;
  state.originalHeight.value = 2000;

  const dimensions = geometry.getScaledImageDimensions();

  expect(Number.isFinite(geometry.getZoomToCoverRatio(dimensions))).toBe(true);
  expect(Number.isFinite(geometry.getZoomToFitRatio(dimensions))).toBe(true);
});

it('frames the image the same whether or not the crop controls are open', () => {
  // The inset that gives the cropper's handles room used to appear only while
  // cropping, so opening the controls reframed the image on top of the zoom
  // change — a visible lurch. The content box is the same in both views now.
  const {state, geometry} = makeEditor(1168, 574);

  state.originalWidth.value = 4032;
  state.originalHeight.value = 3024;

  const dimensions = geometry.getScaledImageDimensions();
  const content = geometry.getContentSize();

  // Unstraightened, both zooms are 1, so the drawn size is the base size.
  expect(geometry.getZoomToCoverRatio(dimensions)).toBeCloseTo(1, 10);
  expect(geometry.getZoomToFitRatio(dimensions)).toBeCloseTo(1, 10);

  // And that base already sits inside the content box, handles included.
  expect(dimensions.width).toBeLessThanOrEqual(content.width);
  expect(dimensions.height).toBeLessThanOrEqual(content.height);
});

it('keeps the content box inset from the editor on both axes', () => {
  const {geometry} = makeEditor(1168, 574);
  const content = geometry.getContentSize();

  expect(1168 - content.width).toBeGreaterThanOrEqual(8);
  expect(574 - content.height).toBeGreaterThanOrEqual(8);
});
