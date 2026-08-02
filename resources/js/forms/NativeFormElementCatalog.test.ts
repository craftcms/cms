import {expect, it} from 'vite-plus/test';
import CraftFieldLayout from '@craftcms/ui/vue/CraftFieldLayout.vue';
import CraftKeyedTable from '@craftcms/ui/vue/CraftKeyedTable.vue';
import CraftObjectSelect from '@craftcms/ui/vue/CraftObjectSelect.vue';
import CraftOptionRows from '@craftcms/ui/vue/CraftOptionRows.vue';
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
  ['craft:keyed-table-input', CraftKeyedTable],
  ['craft:object-select-input', CraftObjectSelect],
  ['craft:field-layout-input', CraftFieldLayout],
  ['craft:option-rows', CraftOptionRows],
])('registers %s through its generated Vue wrapper', (type, wrapper) => {
  const registry = createCpComponentRegistry();

  registerNativeFormElementRenderers(registry);

  expect(registry.resolve(type)).toBe(wrapper);
});
