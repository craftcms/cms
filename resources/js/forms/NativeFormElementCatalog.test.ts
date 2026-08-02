import {expect, it} from 'vite-plus/test';
import CraftObjectSelect from '@craftcms/ui/vue/CraftObjectSelect.vue';
import {createCpComponentRegistry} from '@/bootstrap/components';
import catalog from './native-form-element-catalog.json';
import {
  isSharedContainer,
  nativeFormElementRenderers,
  registerNativeFormElementRenderers,
} from './form-element-types';
import FieldLayoutDesignerValueAdapter from './FieldLayoutDesignerValueAdapter.vue';

it('pairs every native Form Element Type with its declared Vue rendering path', () => {
  const registry = createCpComponentRegistry();

  registerNativeFormElementRenderers(registry);

  for (const registration of catalog) {
    expect(isSharedContainer(registration.type)).toBe(registration.container);

    if (!registration.container) {
      expect(registry.resolve(registration.type)).toBeDefined();
    }
  }

  expect(Object.keys(nativeFormElementRenderers).sort()).toEqual(
    catalog
      .filter(({container}) => !container)
      .map(({type}) => type)
      .sort()
  );
});

it.each([
  ['craft:object-select-input', CraftObjectSelect],
  ['craft:field-layout-designer', FieldLayoutDesignerValueAdapter],
])('registers the %s renderer', (type, wrapper) => {
  const registry = createCpComponentRegistry();

  registerNativeFormElementRenderers(registry);

  expect(registry.resolve(type)).toBe(wrapper);
});
