import {expect, it} from 'vite-plus/test';

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
});
