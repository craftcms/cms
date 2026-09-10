import {expect, it} from 'vite-plus/test';
import {
  arePointsInsideRectangle,
  getFarthestAllowedDeltas,
  getHandlePosition,
  getRectangleVertices,
  hitTestHandle,
  resizeRectangle,
  transposeRectangle,
} from './geometry';
import type {Rectangle, VerticeCoords} from './types';

/** An axis-aligned image quad, corners clockwise from the top-right. */
function quad(
  left: number,
  top: number,
  width: number,
  height: number
): VerticeCoords {
  return {
    a: {x: left + width, y: top},
    b: {x: left + width, y: top + height},
    c: {x: left, y: top + height},
    d: {x: left, y: top},
  };
}

const image = quad(20, 20, 600, 400);
const full: Rectangle = {left: 20, top: 20, width: 600, height: 400};

it('accepts a rectangle whose corners sit exactly on the boundary', () => {
  // The crop starts flush with the image, so the common case is corners
  // touching the edge rather than strictly inside it.
  expect(arePointsInsideRectangle(getRectangleVertices(full), image)).toBe(
    true
  );
});

it('rejects a rectangle that leaves the image on any side', () => {
  for (const [dx, dy] of [
    [-1, 0],
    [1, 0],
    [0, -1],
    [0, 1],
  ]) {
    expect(
      arePointsInsideRectangle(getRectangleVertices(full, dx, dy), image)
    ).toBe(false);
  }
});

it('shrinks from the dragged edge and leaves the others alone', () => {
  const smaller = resizeRectangle(full, -50, 0, 'r', false, false);

  expect(smaller).toEqual({left: 20, top: 20, width: 550, height: 400});

  const fromLeft = resizeRectangle(full, 50, 0, 'l', false, false);

  expect(fromLeft).toEqual({left: 70, top: 20, width: 550, height: 400});
});

it('grows a corner outward on both axes', () => {
  // `bl` drags the left edge left and the bottom edge down.
  expect(resizeRectangle(full, -10, 10, 'bl', false, false)).toEqual({
    left: 10,
    top: 20,
    width: 610,
    height: 410,
  });
});

it('preserves the aspect ratio of a rectangle that already conforms', () => {
  // A constrained drag scales the *deltas* by the ratio, so it holds a shape
  // that already matches rather than converging on one that doesn't — bringing
  // the rectangle to the ratio in the first place is `enforce()`'s job.
  const conforming: Rectangle = {left: 20, top: 20, width: 600, height: 300};
  const constrained = resizeRectangle(conforming, -60, 0, 'r', 2, false);

  expect(constrained.width / constrained.height).toBeCloseTo(2, 5);
});

it('slides along an edge rather than refusing a blocked move', () => {
  // Pushing left is blocked by the image, but downward travel is free.
  const {farthest, farthestDeltas} = getFarthestAllowedDeltas(
    {left: 20, top: 20, width: 600, height: 300},
    {x: -5, y: 5},
    image
  );

  expect(farthest).toBeGreaterThan(0);
  // `toBeCloseTo` rather than `toBe`: the blocked axis comes back as `-0`.
  expect(farthestDeltas.x).toBeCloseTo(0, 10);
  expect(farthestDeltas.y).toBeGreaterThan(0);
});

it('finds each handle from a point on the rectangle border', () => {
  const clipper = {left: 320, top: 220, width: 600, height: 400};

  for (const handle of ['tl', 't', 'tr', 'l', 'r', 'bl', 'b', 'br'] as const) {
    const position = getHandlePosition(handle, clipper);

    expect(hitTestHandle(position, clipper)).toBe(handle);
  }
});

it('finds no handle in the middle of the rectangle', () => {
  const clipper = {left: 320, top: 220, width: 600, height: 400};

  expect(hitTestHandle({x: 320, y: 220}, clipper)).toBeNull();
});

it('swaps width and height about the centre when it already fits', () => {
  // A 300x200 crop sitting well inside a 600x400 image.
  const clipper = {left: 320, top: 220, width: 300, height: 200};
  const turned = transposeRectangle(clipper, image);

  expect(turned.width).toBe(200);
  expect(turned.height).toBe(300);
  // Centre is preserved, so the crop stays where the user framed it.
  expect(turned.left + turned.width / 2).toBeCloseTo(320, 10);
  expect(turned.top + turned.height / 2).toBeCloseTo(220, 10);
});

it('lands on exactly the inverted aspect ratio', () => {
  const clipper = {left: 320, top: 220, width: 320, height: 180};
  const turned = transposeRectangle(clipper, image);
  const before = clipper.width / clipper.height;
  const after = turned.width / turned.height;

  expect(before * after).toBeCloseTo(1, 10);
});

it('shrinks a turned rectangle that would leave the image', () => {
  // 560 wide fits a 600-wide image; turned it would be 560 tall in 400.
  const clipper = {left: 320, top: 220, width: 560, height: 300};
  const turned = transposeRectangle(clipper, image);

  expect(arePointsInsideRectangle(getRectangleVertices(turned), image)).toBe(
    true
  );
  // Shrunk, but still the inverted shape.
  expect(turned.width / turned.height).toBeCloseTo(300 / 560, 6);
  expect(turned.height).toBeLessThan(560);
});

it('keeps a turned rectangle centred where it was', () => {
  const clipper = {left: 200, top: 150, width: 500, height: 200};
  const turned = transposeRectangle(clipper, image);

  expect(turned.left + turned.width / 2).toBeCloseTo(200, 10);
  expect(turned.top + turned.height / 2).toBeCloseTo(150, 10);
});
