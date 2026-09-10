import type {
  CropHandle,
  Dimensions,
  NudgeDirection,
  Point,
  Rectangle,
  VerticeCoords,
} from './types';

/** How far one arrow-key press moves the cropper or focal point, in pixels. */
const NUDGE_STEP = 5;

export function getVector(a: Point, b: Point): Point {
  return {x: b.x - a.x, y: b.y - a.y};
}

export function getScalarProduct(a: Point, b: Point): number {
  return a.x * b.x + a.y * b.y;
}

export function getVectorMagnitude(vector: Point): number {
  return Math.sqrt(vector.x * vector.x + vector.y * vector.y);
}

/** The angle between two vectors in degrees, to two decimal places. */
export function getAngleBetweenVectors(a: Point, b: Point): number {
  const cosine = Math.min(
    1,
    getScalarProduct(a, b) / (getVectorMagnitude(a) * getVectorMagnitude(b))
  );

  return Math.round(((Math.acos(cosine) * 180) / Math.PI) * 100) / 100;
}

/**
 * The four corners of a rectangle, clockwise from the top-left, optionally
 * displaced by an offset.
 *
 * @see https://stackoverflow.com/a/2763387
 */
export function getRectangleVertices(
  rectangle: Rectangle,
  offsetX = 0,
  offsetY = 0
): Point[] {
  const topLeft = {
    x: rectangle.left + offsetX,
    y: rectangle.top + offsetY,
  };

  return [
    topLeft,
    {x: topLeft.x + rectangle.width, y: topLeft.y},
    {x: topLeft.x + rectangle.width, y: topLeft.y + rectangle.height},
    {x: topLeft.x, y: topLeft.y + rectangle.height},
  ];
}

/**
 * Whether every point falls inside a rectangle given by its corners — which may
 * be rotated, so this projects each point onto two adjacent edges rather than
 * comparing bounds.
 */
export function arePointsInsideRectangle(
  points: Point[],
  rectangle: VerticeCoords
): boolean {
  const ab = getVector(rectangle.a, rectangle.b);
  const bc = getVector(rectangle.b, rectangle.c);
  const scalarAbAb = getScalarProduct(ab, ab);
  const scalarBcBc = getScalarProduct(bc, bc);

  return points.every((point) => {
    const scalarAbAp = getScalarProduct(ab, getVector(rectangle.a, point));
    const scalarBcBp = getScalarProduct(bc, getVector(rectangle.b, point));

    return (
      scalarAbAp >= 0 &&
      scalarAbAp <= scalarAbAb &&
      scalarBcBp >= 0 &&
      scalarBcBp <= scalarBcBc
    );
  });
}

/** The axis-aligned box enclosing a set of (possibly rotated) corners. */
export function getBoundingRectangle(coords: VerticeCoords): Dimensions {
  const xs = [coords.a.x, coords.b.x, coords.c.x, coords.d.x];
  const ys = [coords.a.y, coords.b.y, coords.c.y, coords.d.y];

  return {
    width: Math.max(...xs) - Math.min(...xs),
    height: Math.max(...ys) - Math.min(...ys),
  };
}

/**
 * Whether one center-origin object's center sits inside another's bounds. Only
 * valid for an unrotated container.
 */
export function isCenterInside(
  object: {left: number; top: number},
  container: {left: number; top: number; width: number; height: number}
): boolean {
  return (
    object.left > container.left - container.width / 2 &&
    object.top > container.top - container.height / 2 &&
    object.left < container.left + container.width / 2 &&
    object.top < container.top + container.height / 2
  );
}

/**
 * Which edge of `rectangle` an imaginary line from `center` to `vertex` crosses.
 *
 * Found by angle rather than intersection: for the offending edge, the angle
 * from center to vertex equals the sum of the angles each makes with the edge.
 * Rounding means that's never exact, so the closest match wins.
 */
export function getEdgeCrossed(
  rectangle: VerticeCoords,
  vertex: Point,
  center: Point
): [Point, Point] | null {
  const edges: Array<[Point, Point]> = [
    [rectangle.a, rectangle.b],
    [rectangle.b, rectangle.c],
    [rectangle.c, rectangle.d],
    [rectangle.d, rectangle.a],
  ];

  let smallestDiff = 180;
  let edgeCrossed: [Point, Point] | null = null;

  for (const edge of edges) {
    const toCenter = getVector(edge[0], center);
    const edgeVector = getVector(edge[0], edge[1]);
    const toVertex = getVector(edge[0], vertex);

    const diff = Math.abs(
      getAngleBetweenVectors(toCenter, toVertex) -
        (getAngleBetweenVectors(toCenter, edgeVector) +
          getAngleBetweenVectors(edgeVector, toVertex))
    );

    if (diff < smallestDiff) {
      smallestDiff = diff;
      edgeCrossed = edge;
    }
  }

  return edgeCrossed;
}

/** Perpendicular distance from a point to the line through an edge. */
function distanceToEdge(edge: [Point, Point], point: Point): number {
  return (
    Math.abs(
      (edge[1].y - edge[0].y) * point.x -
        (edge[1].x - edge[0].x) * point.y +
        edge[1].x * edge[0].y -
        edge[1].y * edge[0].x
    ) /
    Math.sqrt(
      Math.pow(edge[1].y - edge[0].y, 2) + Math.pow(edge[1].x - edge[0].x, 2)
    )
  );
}

/**
 * How much a rectangle would have to be zoomed for it to fit inside a container
 * given by its corners. Returns 1 when it already fits.
 *
 * @see https://en.wikipedia.org/wiki/Distance_from_a_point_to_a_line
 */
export function getZoomRatioToFitRectangle(
  rectangle: Rectangle,
  containingVertices: VerticeCoords,
  center: Point
): number {
  const escapee = getRectangleVertices(rectangle).find(
    (vertex) => !arePointsInsideRectangle([vertex], containingVertices)
  );

  if (!escapee) {
    return 1;
  }

  const edge = getEdgeCrossed(containingVertices, escapee, center);

  if (!edge) {
    return 1;
  }

  const rectangleCenter = {
    x: rectangle.left + rectangle.width / 2,
    y: rectangle.top + rectangle.height / 2,
  };

  const distanceFromVertex = distanceToEdge(edge, escapee);
  const distanceFromCenter = distanceToEdge(edge, rectangleCenter);

  return (distanceFromVertex + distanceFromCenter) / distanceFromCenter;
}

/**
 * The largest fraction of a proposed move that keeps the rectangle inside the
 * image, so dragging into an edge slides along it instead of stopping dead.
 * Searches at most ten pixels per axis, which is all a single frame or key
 * press can produce.
 */
export function getFarthestAllowedDeltas(
  rectangle: Rectangle,
  deltas: Point,
  containingVertices: VerticeCoords
): {farthest: number; farthestDeltas: Point} {
  const signX = deltas.x > 0 ? 1 : -1;
  const signY = deltas.y > 0 ? 1 : -1;

  const result = {farthest: 0, farthestDeltas: {x: 0, y: 0}};

  for (let dxi = Math.min(Math.abs(deltas.x), 10); dxi >= 0; dxi--) {
    for (let dyi = Math.min(Math.abs(deltas.y), 10); dyi >= 0; dyi--) {
      const vertices = getRectangleVertices(
        rectangle,
        dxi * signX,
        dyi * signY
      );

      if (
        arePointsInsideRectangle(vertices, containingVertices) &&
        dxi + dyi > result.farthest
      ) {
        result.farthest = dxi + dyi;
        result.farthestDeltas = {x: dxi * signX, y: dyi * signY};
      }
    }
  }

  return result;
}

/** Where a named handle sits on a center-origin rectangle. */
export function getHandlePosition(
  handle: CropHandle,
  clipper: {left: number; top: number; width: number; height: number}
): Point {
  const halfWidth = clipper.width / 2;
  const halfHeight = clipper.height / 2;

  const x = handle.includes('l')
    ? clipper.left - halfWidth
    : handle.includes('r')
      ? clipper.left + halfWidth
      : clipper.left;

  const y = handle.includes('t')
    ? clipper.top - halfHeight
    : handle.includes('b')
      ? clipper.top + halfHeight
      : clipper.top;

  return {x, y};
}

export function getDeltasFromDirection(direction: NudgeDirection): Point {
  switch (direction) {
    case 'up':
      return {x: 0, y: -NUDGE_STEP};
    case 'down':
      return {x: 0, y: NUDGE_STEP};
    case 'left':
      return {x: -NUDGE_STEP, y: 0};
    case 'right':
      return {x: NUDGE_STEP, y: 0};
  }
}

/** Rotates an offset around the origin — how a point moves as the image turns. */
export function rotatePoint(point: Point, degrees: number): Point {
  const radians = degrees * (Math.PI / 180);

  return {
    x: point.x * Math.cos(radians) - point.y * Math.sin(radians),
    y: point.x * Math.sin(radians) + point.y * Math.cos(radians),
  };
}

/** How close to an edge or corner the pointer must be to grab that handle. */
const HANDLE_HIT_SLOP = 10;

/**
 * Which cropper handle, if any, sits under a point. The asymmetric tolerances
 * come from the handle artwork, which is drawn a few pixels outside the
 * rectangle on the right and bottom.
 */
export function hitTestHandle(
  point: Point,
  clipper: {left: number; top: number; width: number; height: number}
): CropHandle | null {
  const left = clipper.left - clipper.width / 2;
  const right = left + clipper.width;
  const top = clipper.top - clipper.height / 2;
  const bottom = top + clipper.height;

  const nearLeft = point.x < left + HANDLE_HIT_SLOP && point.x > left - 3;
  const nearRight = point.x > right - 13 && point.x < right + 3;
  const nearTop = point.y < top + HANDLE_HIT_SLOP && point.y > top - 3;
  const nearBottom = point.y < bottom + 3 && point.y > bottom - HANDLE_HIT_SLOP;

  if (nearLeft && nearTop) return 'tl';
  if (nearLeft && nearBottom) return 'bl';
  if (nearRight && nearTop) return 'tr';
  if (nearRight && nearBottom) return 'br';

  const betweenVertically =
    point.y < bottom - HANDLE_HIT_SLOP && point.y > top + HANDLE_HIT_SLOP;
  const betweenHorizontally =
    point.x > left + HANDLE_HIT_SLOP && point.x < right - HANDLE_HIT_SLOP;

  if (point.x < left + 3 && point.x > left - 3 && betweenVertically) return 'l';
  if (point.x < right + 1 && point.x > right - 5 && betweenVertically)
    return 'r';
  if (point.y < top + 4 && point.y > top - 2 && betweenHorizontally) return 't';
  if (point.y < bottom + 2 && point.y > bottom - 4 && betweenHorizontally)
    return 'b';

  return null;
}

/** The mouse cursor that signals what dragging a given handle would do. */
export function getCursorForHandle(handle: CropHandle): string {
  if (handle === 't' || handle === 'b') return 'ns-resize';
  if (handle === 'l' || handle === 'r') return 'ew-resize';
  if (handle === 'tl' || handle === 'br') return 'nwse-resize';
  return 'nesw-resize';
}

/**
 * How far a drag on one handle moves the rectangle's size along each axis,
 * before the aspect ratio is applied. A corner takes whichever axis the pointer
 * moved further along, so the drag follows the mouse rather than one edge.
 */
function getConstrainedChange(
  handle: CropHandle,
  deltaX: number,
  deltaY: number
): number {
  const dominantIsVertical = Math.abs(deltaY) > Math.abs(deltaX);

  switch (handle) {
    case 't':
      return -deltaY;
    case 'b':
      return deltaY;
    case 'r':
      return deltaX;
    case 'l':
      return -deltaX;
    case 'tr':
      return dominantIsVertical ? -deltaY : deltaX;
    case 'tl':
      return dominantIsVertical ? -deltaY : -deltaX;
    case 'br':
      return dominantIsVertical ? deltaY : deltaX;
    case 'bl':
      return dominantIsVertical ? deltaY : -deltaX;
  }
}

/**
 * The rectangle a resize drag produces.
 *
 * With a locked aspect ratio the rectangle grows from the dragged edge and
 * spreads evenly along the other axis, so it feels anchored where the pointer
 * is. Unconstrained, each named edge moves independently — with Shift on a
 * corner holding the current ratio.
 */
export function resizeRectangle(
  startingRectangle: Rectangle,
  deltaX: number,
  deltaY: number,
  handle: CropHandle,
  constraint: number | false,
  shiftKeyHeld: boolean
): Rectangle {
  const rectangle = {...startingRectangle};

  if (constraint) {
    const change = getConstrainedChange(handle, deltaX, deltaY);

    let dx: number;
    let dy: number;

    if (constraint > 1) {
      dx = change;
      dy = dx / constraint;
    } else {
      dy = change;
      dx = dy * constraint;
    }

    rectangle.width += dx;
    rectangle.height += dy;

    // Shift the origin so the rectangle expands away from the dragged edge.
    if (handle.includes('t')) {
      rectangle.top -= dy;
    }
    if (handle.includes('l')) {
      rectangle.left -= dx;
    }
    if (handle === 't' || handle === 'b') {
      rectangle.left -= dx / 2;
    }
    if (handle === 'l' || handle === 'r') {
      rectangle.top -= dy / 2;
    }

    return rectangle;
  }

  let dx = deltaX;
  let dy = deltaY;

  const isCorner = handle.length === 2;

  if (shiftKeyHeld && isCorner) {
    const ratio = startingRectangle.width / startingRectangle.height;
    const invert = handle === 'tr' || handle === 'bl' ? -1 : 1;

    if (Math.abs(deltaX) > Math.abs(deltaY)) {
      dy = (dx / ratio) * invert;
    } else {
      dx = dy * ratio * invert;
    }
  }

  if (handle.includes('t')) {
    rectangle.top += dy;
    rectangle.height -= dy;
  }
  if (handle.includes('b')) {
    rectangle.height += dy;
  }
  if (handle.includes('r')) {
    rectangle.width += dx;
  }
  if (handle.includes('l')) {
    rectangle.left += dx;
    rectangle.width -= dx;
  }

  return rectangle;
}

/** Bisection steps used to shrink a turned rectangle back inside its container. */
const TRANSPOSE_FIT_STEPS = 24;

/**
 * Stands a centre-origin rectangle the other way up: width and height swap
 * about its centre.
 *
 * Swapping the two *is* the inverted aspect ratio, so a constrained crop lands
 * on exactly the shape a flipped constraint asks for. A turned rectangle can
 * stick out where the original didn't — a wide crop becomes a tall one — so it
 * shrinks about its centre until it fits.
 *
 * Returns the shape it settled on as a top-left-origin rectangle.
 */
export function transposeRectangle(
  clipper: {left: number; top: number; width: number; height: number},
  containingVertices: VerticeCoords
): Rectangle {
  const turned = {width: clipper.height, height: clipper.width};

  const at = (scale: number): Rectangle => ({
    left: clipper.left - (turned.width * scale) / 2,
    top: clipper.top - (turned.height * scale) / 2,
    width: turned.width * scale,
    height: turned.height * scale,
  });

  const contained = (candidate: Rectangle): boolean =>
    arePointsInsideRectangle(
      getRectangleVertices(candidate),
      containingVertices
    );

  if (contained(at(1))) {
    return at(1);
  }

  // Shrinking about the centre converges on the centre point, which is inside
  // the container, so a fitting scale exists. Bisection finds it to well under
  // a pixel in a handful of steps.
  let tooSmall = 0;
  let tooBig = 1;

  for (let step = 0; step < TRANSPOSE_FIT_STEPS; step++) {
    const middle = (tooSmall + tooBig) / 2;

    if (contained(at(middle))) {
      tooSmall = middle;
    } else {
      tooBig = middle;
    }
  }

  return at(tooSmall);
}
