import {describe, expect, it} from 'vite-plus/test';
import {
  blendShapes,
  DEVICES,
  deviceLayout,
  deviceShape,
  fitDevice,
  isRotation,
  rotatesClockwise,
} from './devicePreview';

describe('deviceLayout', () => {
  it('leaves desktop to fill the room', () => {
    expect(deviceLayout('desktop', 'portrait', {width: 800, height: 600})).toBe(
      null
    );
  });

  it('shows a device at full size when it fits', () => {
    const layout = deviceLayout('phone', 'portrait', {
      width: 2000,
      height: 2000,
    });

    expect(layout?.zoom).toBe(1);
    expect(layout?.viewport).toEqual({
      width: DEVICES.phone.width,
      height: DEVICES.phone.height,
    });
  });

  it('scales a device down to fit, keeping a gutter', () => {
    const layout = deviceLayout(
      'tablet',
      'portrait',
      {width: 2000, height: 600},
      0
    )!;

    expect(layout.zoom).toBeCloseTo(600 / layout.frame.height);
  });

  it.each(['tablet', 'phone'] as const)(
    'frames a %s evenly on every side',
    (type) => {
      for (const orientation of ['portrait', 'landscape'] as const) {
        const {viewport, frame} = deviceLayout(type, orientation, {
          width: 1,
          height: 1,
        })!;

        expect(frame.width - viewport.width).toBe(
          frame.height - viewport.height
        );
      }
    }
  );

  it('swaps the dimensions in landscape', () => {
    const portrait = deviceLayout('tablet', 'portrait', {width: 1, height: 1})!;
    const landscape = deviceLayout('tablet', 'landscape', {
      width: 1,
      height: 1,
    })!;

    expect(landscape.viewport).toEqual({
      width: portrait.viewport.height,
      height: portrait.viewport.width,
    });
    expect(landscape.frame).toEqual({
      width: portrait.frame.height,
      height: portrait.frame.width,
    });
  });
});

describe('blendShapes', () => {
  it('runs from one device to the other', () => {
    const tablet = deviceShape('tablet', 'portrait')!;
    const phone = deviceShape('phone', 'portrait')!;

    expect(blendShapes(tablet, phone, 0)).toEqual(tablet);
    expect(blendShapes(tablet, phone, 1)).toEqual(phone);
    expect(blendShapes(tablet, phone, 0.5).viewport).toEqual({
      width: (tablet.viewport.width + phone.viewport.width) / 2,
      height: (tablet.viewport.height + phone.viewport.height) / 2,
    });
  });

  it('never fits wider or taller than the room on the way', () => {
    const tablet = deviceShape('tablet', 'portrait')!;
    const phone = deviceShape('phone', 'portrait')!;
    const room = {width: 900, height: 860};

    for (let step = 0; step <= 20; step++) {
      const {frame, zoom} = fitDevice(
        blendShapes(tablet, phone, step / 20),
        room
      );

      expect(frame.width * zoom).toBeLessThanOrEqual(room.width - 48 + 1e-9);
      expect(frame.height * zoom).toBeLessThanOrEqual(room.height - 48 + 1e-9);
    }
  });
});

describe('rotating a device', () => {
  it('only counts a quarter turn of the same device', () => {
    const portrait = deviceShape('tablet', 'portrait');
    const landscape = deviceShape('tablet', 'landscape');

    expect(isRotation(portrait, landscape)).toBe(true);
    expect(isRotation(landscape, portrait)).toBe(true);
    expect(isRotation(portrait, deviceShape('phone', 'landscape'))).toBe(false);
    expect(isRotation(portrait, null)).toBe(false);
  });

  it('fits the device on its way round, and lands on the other orientation’s fit', () => {
    const portrait = deviceShape('tablet', 'portrait')!;
    const landscape = deviceShape('tablet', 'landscape')!;
    const room = {width: 900, height: 860};

    for (let step = 0; step <= 18; step++) {
      const degrees = step * 5;
      const {frame, zoom} = fitDevice(portrait, room, 24, degrees);
      const radians = (degrees * Math.PI) / 180;
      const cos = Math.abs(Math.cos(radians));
      const sin = Math.abs(Math.sin(radians));

      expect(
        (frame.width * cos + frame.height * sin) * zoom
      ).toBeLessThanOrEqual(room.width - 48 + 1e-9);
      expect(
        (frame.width * sin + frame.height * cos) * zoom
      ).toBeLessThanOrEqual(room.height - 48 + 1e-9);
    }

    expect(fitDevice(portrait, room, 24, 90).zoom).toBeCloseTo(
      fitDevice(landscape, room).zoom,
      10
    );
  });
});

describe('rotatesClockwise', () => {
  it('turns on to its side clockwise and back counterclockwise, mirrored for right-to-left', () => {
    expect(rotatesClockwise('portrait', false)).toBe(true);
    expect(rotatesClockwise('landscape', false)).toBe(false);
    expect(rotatesClockwise('portrait', true)).toBe(false);
    expect(rotatesClockwise('landscape', true)).toBe(true);
  });
});
