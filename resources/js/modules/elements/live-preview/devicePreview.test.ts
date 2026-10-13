import {describe, expect, it} from 'vite-plus/test';
import {DEVICES, deviceLayout} from './devicePreview';

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
