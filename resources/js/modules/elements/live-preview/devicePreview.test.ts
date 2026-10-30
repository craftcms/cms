import {describe, expect, it} from 'vite-plus/test';
import {
  blendShapes,
  DEVICES,
  deviceLayout,
  deviceShape,
  fitDevice,
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
