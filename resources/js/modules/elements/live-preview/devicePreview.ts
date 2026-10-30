export type DeviceType = 'desktop' | 'tablet' | 'phone';

export type DeviceOrientation = 'portrait' | 'landscape';

interface DeviceSpec {
  /** The viewport the page is rendered at. */
  width: number;
  height: number;
  /** The bezel drawn around the viewport, the same on every side. */
  bezel: number;
  /** The device's corner radius. The screen's is this less the bezel. */
  radius: number;
}

/** An 11-inch iPad Pro and an iPhone 16 Pro, portrait. */
export const DEVICES: Record<Exclude<DeviceType, 'desktop'>, DeviceSpec> = {
  tablet: {width: 834, height: 1210, bezel: 44, radius: 62},
  phone: {width: 402, height: 874, bezel: 15, radius: 77},
};

export interface DeviceLayout {
  /** The iframe's own size, before scaling. */
  viewport: {width: number; height: number};
  /** The device, bezel included, before scaling. */
  frame: {width: number; height: number};
  /** How much the device is scaled to fit the room it has. Never above 1. */
  zoom: number;
}

/** A device's viewport, bezel and corners, before it's fitted to the room it has. */
export interface DeviceShape {
  viewport: {width: number; height: number};
  bezel: number;
  radius: number;
}

/** The device to show, or `null` for desktop, which fills the room. */
export function deviceShape(
  type: DeviceType,
  orientation: DeviceOrientation
): DeviceShape | null {
  if (type === 'desktop') {
    return null;
  }

  const {width, height, bezel, radius} = DEVICES[type];

  return {
    viewport:
      orientation === 'landscape'
        ? {width: height, height: width}
        : {width, height},
    bezel,
    radius,
  };
}

/** Sizes a device to fit the available room, keeping a gutter around it. */
export function fitDevice(
  {viewport, bezel}: DeviceShape,
  available: {width: number; height: number},
  gutter = 24
): DeviceLayout {
  const frame = {
    width: viewport.width + bezel * 2,
    height: viewport.height + bezel * 2,
  };
  const zoom = Math.min(
    1,
    Math.max(0, available.width - gutter * 2) / frame.width,
    Math.max(0, available.height - gutter * 2) / frame.height
  );

  return {viewport, frame, zoom};
}

/** The shape `progress` of the way from one device to another. */
export function blendShapes(
  from: DeviceShape,
  to: DeviceShape,
  progress: number
): DeviceShape {
  const mix = (a: number, b: number) => a + (b - a) * progress;

  return {
    viewport: {
      width: mix(from.viewport.width, to.viewport.width),
      height: mix(from.viewport.height, to.viewport.height),
    },
    bezel: mix(from.bezel, to.bezel),
    radius: mix(from.radius, to.radius),
  };
}

/**
 * Sizes a device preview to fit the available room, or `null` for desktop,
 * which fills it.
 */
export function deviceLayout(
  type: DeviceType,
  orientation: DeviceOrientation,
  available: {width: number; height: number},
  gutter = 24
): DeviceLayout | null {
  const shape = deviceShape(type, orientation);

  return shape ? fitDevice(shape, available, gutter) : null;
}
