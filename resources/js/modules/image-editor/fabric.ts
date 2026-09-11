/**
 * Every fabric API the editor touches is funnelled through this one file, so a
 * version bump lands here and, as far as possible, nowhere else.
 *
 * It earned that on the way from 1.7 to 7. The library stopped being a UMD
 * global that `FabricAsset` registered and became an ESM package we import,
 * renamed `fabric.Image` to `FabricImage`, dropped the `getWidth()` accessors
 * in favour of plain properties, and returned promises where it used to take
 * callbacks -- and the composables saw none of it.
 *
 * The legacy jQuery editor still runs on the 1.7 global. That copy is built
 * from `packages/craftcms-legacy`'s own dependency and served by `FabricAsset`,
 * so the two versions don't meet.
 */
import {
  Circle,
  FabricImage,
  Group,
  Line,
  Path,
  Rect,
  StaticCanvas,
  loadSVGFromString,
  util,
  type FabricObject,
} from 'fabric';

export {Circle, FabricImage, Group, Line, Path, Rect, StaticCanvas};

export type {FabricObject};

/**
 * Properties `animate()` will tween. Everything the editor animates is a
 * number -- an angle, a size, a position -- and fabric's own signature is
 * narrower than the `unknown` these objects used to be typed with.
 */
export type FabricAnimatable = Record<string, number>;

/** Named for what the editor uses them as, rather than what fabric calls them. */
export type FabricGroup = Group;
export type FabricCanvas = StaticCanvas;

/**
 * Loads an image, rejecting rather than resolving null.
 *
 * fabric resolves to `null` for an image it couldn't fetch, which reads as a
 * success everywhere it is awaited. The editor wants the failure.
 */
export async function loadImage(url: string): Promise<FabricImage> {
  const image = await FabricImage.fromURL(url);

  if (!image) {
    throw new Error(`Could not load image: ${url}`);
  }

  return image;
}

/** Parses an SVG into a single object the editor can place on a canvas. */
export async function loadSvg(svg: string): Promise<FabricObject> {
  const {objects, options} = await loadSVGFromString(svg);
  const parsed = objects.filter((object) => object !== null);

  if (!parsed.length) {
    throw new Error('Could not parse SVG.');
  }

  return util.groupSVGElements(parsed, options);
}
