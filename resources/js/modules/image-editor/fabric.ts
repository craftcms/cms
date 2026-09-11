/**
 * The editor still runs on fabric.js 1.7, which `FabricAsset` loads as a global
 * UMD bundle rather than something we import. Everything the editor touches is
 * funnelled through this one file so the upgrade to fabric 6/7 — which is ESM,
 * renames `fabric.Image` to `FabricImage`, and returns promises instead of
 * taking callbacks — is a change here and nowhere else.
 */

export interface FabricObject {
  left: number;
  top: number;
  width: number;
  height: number;
  angle: number;
  scaleX: number;
  scaleY: number;
  flipX: boolean;
  flipY: boolean;
  opacity: number;
  dirty: boolean;
  globalCompositeOperation?: string;
  set(properties: Record<string, unknown>): FabricObject;
  get(property: string): unknown;
  animate(
    properties: Record<string, unknown>,
    options: {
      duration?: number;
      onChange?: () => void;
      onComplete?: () => void;
    }
  ): void;
  _set(key: string, value: unknown): FabricObject;
}

export interface FabricGroup extends FabricObject {
  add(object: FabricObject): FabricGroup;
  item(index: number): FabricObject;
}

export interface FabricImage extends FabricObject {
  getWidth(): number;
  getHeight(): number;
  setSrc(src: string, callback: (image: FabricImage) => void): void;
}

export interface FabricCanvas {
  width: number;
  height: number;
  enableRetinaScaling: boolean;
  add(object: FabricObject): FabricCanvas;
  remove(object: FabricObject | null): FabricCanvas;
  renderAll(): void;
  setDimensions(dimensions: {width: number; height: number}): void;
  dispose(): void;
}

interface FabricNamespace {
  Rect: new (options: Record<string, unknown>) => FabricObject;
  Circle: new (options: Record<string, unknown>) => FabricObject;
  Line: new (
    points: number[],
    options: Record<string, unknown>
  ) => FabricObject;
  Path: new (path: string, options: Record<string, unknown>) => FabricObject;
  Group: new (
    objects: FabricObject[],
    options?: Record<string, unknown>
  ) => FabricGroup;
  StaticCanvas: new (
    element: HTMLCanvasElement | string,
    options?: Record<string, unknown>
  ) => FabricCanvas;
  Image: {
    fromURL(url: string, callback: (image: FabricImage) => void): void;
  };
  loadSVGFromString(
    svg: string,
    callback: (
      objects: FabricObject[],
      options: Record<string, unknown>
    ) => void
  ): void;
  util: {
    groupSVGElements(
      objects: FabricObject[],
      options: Record<string, unknown>
    ): FabricObject;
  };
}

declare global {
  interface Window {
    fabric?: FabricNamespace;
  }
}

/**
 * Throws rather than returning undefined: every caller needs fabric, and a
 * missing global means `FabricAsset` didn't register, which is worth surfacing
 * loudly instead of failing later on a property access.
 */
export function fabric(): FabricNamespace {
  if (!window.fabric) {
    throw new Error(
      'fabric.js is not loaded. The image editor needs FabricAsset registered.'
    );
  }

  return window.fabric;
}

/** Promise wrapper over fabric 1.x's callback-style image loading. */
export function loadImage(url: string): Promise<FabricImage> {
  return new Promise((resolve, reject) => {
    fabric().Image.fromURL(url, (image) => {
      if (!image) {
        reject(new Error(`Could not load image: ${url}`));
        return;
      }

      resolve(image);
    });
  });
}

/** Promise wrapper over fabric 1.x's callback-style SVG parsing. */
export function loadSvg(svg: string): Promise<FabricObject> {
  return new Promise((resolve, reject) => {
    fabric().loadSVGFromString(svg, (objects, options) => {
      if (!objects?.length) {
        reject(new Error('Could not parse SVG.'));
        return;
      }

      resolve(fabric().util.groupSVGElements(objects, options));
    });
  });
}
