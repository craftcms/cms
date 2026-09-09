import {expect, it} from 'vite-plus/test';

// The two imports below pull in a large slice of the legacy runtime and are
// transformed on demand, landing a hair under the 5s default while the rest of
// the suite competes for the same workers. Hence the explicit timeout: this is
// slow by nature, and a loaded machine shouldn't decide whether the run is
// green.
it('registers control dependencies before their custom elements', async () => {
  window.Craft = Object.create(null);

  await import('./element-select-input');
  await import('./field-layout-designer');

  const elementThumbLoader = Object.getOwnPropertyDescriptor(
    window.Craft,
    'ElementThumbLoader'
  )?.value;
  const grid = Object.getOwnPropertyDescriptor(window.Craft, 'Grid')?.value;

  expect(elementThumbLoader).toBeTypeOf('function');
  expect(() => Reflect.construct(elementThumbLoader, [])).not.toThrow();
  expect(grid).toBeTypeOf('function');
}, 30_000);
