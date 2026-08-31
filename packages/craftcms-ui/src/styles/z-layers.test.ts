import {readFileSync} from 'node:fs';
import {join} from 'node:path';

import {describe, expect, it} from 'vite-plus/test';

import {
  ZLayer,
  zLayerProperty,
  type ZLayerKey,
} from '@src/constants/z-layers.js';

// Read rather than import: Vitest stubs CSS imports (`test.css` defaults to
// off), so `?raw` would hand back an empty string. `cwd` is the package root.
const css = readFileSync(
  join(process.cwd(), 'src/styles/shared/z-layers.css'),
  'utf8'
);

/** Every `--c-z-*: <n>;` declaration in the stylesheet, as a name → value map. */
const declared = new Map(
  [...css.matchAll(/(--c-z-[a-z-]+):\s*(-?\d+);/g)].map(([, name, value]) => [
    name,
    Number(value),
  ])
);

const layers = Object.keys(ZLayer) as ZLayerKey[];

/**
 * `z-layers.css` is the documented source of truth and `constants/z-layers.ts`
 * is its mirror for JS. Nothing generates one from the other, so these assert
 * they haven't drifted.
 */
describe('z-layers', () => {
  it.each(layers)('publishes %s as a custom property', (layer) => {
    expect(declared.get(zLayerProperty(layer))).toBe(ZLayer[layer]);
  });

  it('declares no custom property the constant map is missing', () => {
    const expected = layers.map(zLayerProperty).sort();
    expect([...declared.keys()].sort()).toEqual(expected);
  });

  it('orders the rungs the same way it lists them', () => {
    const values = layers.map((layer) => ZLayer[layer] as number);
    expect(values).toEqual([...values].sort((a, b) => a - b));
  });

  it('clears the legacy CP bundle, which tops out at 1001', () => {
    const pageLevel = layers.slice(layers.indexOf('PageHeader'));
    for (const layer of pageLevel) {
      expect(ZLayer[layer]).toBeGreaterThan(1001);
    }
  });
});
