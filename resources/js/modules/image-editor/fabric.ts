/**
 * The fabric API the editor uses, in one place. The legacy editor still loads
 * fabric 1.7 separately, through `FabricAsset`.
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

export type FabricAnimatable = Record<string, number>;

export interface AnimateOptions {
  duration?: number;
  /** Once per frame, after every property has taken its value for it. */
  onChange?: () => void;
  /** Once, after every property has landed. */
  onComplete?: () => void;
}

export function animate(
  object: FabricObject,
  properties: FabricAnimatable,
  {duration, onChange, onComplete}: AnimateOptions = {}
): void {
  const entries = Object.entries(properties);

  if (entries.length === 0) {
    onComplete?.();
    return;
  }

  let remaining = entries.length;

  entries.forEach(([key, value], index) => {
    const last = index === entries.length - 1;

    object.animate(
      {[key]: value},
      {
        duration,
        ...(last && onChange ? {onChange} : {}),
        onComplete: () => {
          remaining -= 1;

          if (remaining === 0) {
            onComplete?.();
          }
        },
      }
    );
  });
}

/** Named for what the editor uses them as, rather than what fabric calls them. */
export type FabricGroup = Group;
export type FabricCanvas = StaticCanvas;

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
