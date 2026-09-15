/** A point in editor-space (pixels, origin at the editor's top-left). */
export interface Point {
  x: number;
  y: number;
}

/** A rectangle whose `left`/`top` reference its **top-left** corner. */
export interface Rectangle {
  left: number;
  top: number;
  width: number;
  height: number;
}

export interface Dimensions {
  width: number;
  height: number;
}

/**
 * The four corners of the (possibly rotated) image, going clockwise from the
 * top-right. Named `a`–`d` because the containment maths treats `a`→`b` and
 * `b`→`c` as the two edge vectors.
 */
export interface VerticeCoords {
  a: Point;
  b: Point;
  c: Point;
  d: Point;
}

/**
 * The cropper's position and size, stored at a zoom ratio of 1 and relative to
 * the image center, so it survives zooming, rotation and editor resizes.
 */
export interface CropperState {
  offsetX: number;
  offsetY: number;
  width: number;
  height: number;
  imageDimensions: Dimensions;
}

/** The focal point's offset from the image center, stored at a zoom ratio of 1. */
export interface FocalPointState {
  offsetX: number;
  offsetY: number;
  imageDimensions: Dimensions;
}

/** Which axes the image has been flipped on, as 0/1 so it posts as ints. */
export interface FlipData {
  x: number;
  y: number;
}

/** A corner or edge handle on the cropping rectangle. */
export type CropHandle = 'tl' | 't' | 'tr' | 'l' | 'r' | 'bl' | 'b' | 'br';

/**
 * Anything the keyboard editing layer can pick up: the cropping rectangle
 * itself, the focal point, or one of the eight resize handles.
 */
export type FabricElementHandle = CropHandle | 'rectangle' | 'focalpoint';

export type NudgeDirection = 'up' | 'down' | 'left' | 'right';

export type EditorView = 'rotate' | 'crop';

/** The focal point as the server stores it: fractions of the image's size. */
export interface RelativeFocalPoint {
  x: number;
  y: number;
}
