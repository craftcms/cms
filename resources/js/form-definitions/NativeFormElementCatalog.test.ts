import {expect, it} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import catalog from './native-form-element-catalog.json';
import {
  isSharedContainer,
  nativeFormElementRenderers,
  registerNativeFormElementRenderers,
} from './form-element-types';

it('pairs every native Form Element Type with its declared Vue rendering path', () => {
  const registry = createCpComponentRegistry();

  registerNativeFormElementRenderers(registry);

  for (const registration of catalog) {
    expect(isSharedContainer(registration.type)).toBe(registration.container);

    if (!registration.container) {
      expect(
        registry.resolve(`form-element:${registration.type}`)
      ).toBeDefined();
    }
  }

  expect(Object.keys(nativeFormElementRenderers).sort()).toEqual(
    catalog
      .filter(({container}) => !container)
      .map(({type}) => type)
      .sort()
  );
});
