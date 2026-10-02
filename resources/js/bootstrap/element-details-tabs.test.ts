import {describe, expect, it} from 'vite-plus/test';
import {defineComponent, nextTick, watchEffect} from 'vue';
import {
  createElementDetailsTabRegistry,
  type ElementDetailsTabDescriptor,
} from './element-details-tabs';
import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';

const component = defineComponent({render: () => null});

function descriptor(id: string): ElementDetailsTabDescriptor {
  return {id, label: id, icon: 'circle-info', component};
}

describe('element details tab registry', () => {
  it('reactively exposes registrations', async () => {
    const registry = createElementDetailsTabRegistry();
    let tabIds: string[] = [];

    watchEffect(() => {
      tabIds = registry.tabs.map((tab) => tab.id);
    });

    registry.register(descriptor('plugin:details'));
    await nextTick();

    expect(tabIds).toEqual(['plugin:details']);
    expect(registry.hasVisible({} as ElementEditPayload)).toBe(true);
  });

  it('allows the same descriptor to be registered more than once', () => {
    const registry = createElementDetailsTabRegistry();
    const tab = descriptor('plugin:details');

    registry.register(tab);
    registry.register(tab);

    expect(registry.tabs).toHaveLength(1);
  });

  it('reports whether a tab is visible for an element payload', () => {
    const registry = createElementDetailsTabRegistry();

    registry.register({
      ...descriptor('plugin:conditional'),
      visible: () => false,
    });

    expect(registry.hasVisible({} as ElementEditPayload)).toBe(false);
  });

  it('rejects different descriptors with the same id', () => {
    const registry = createElementDetailsTabRegistry();

    registry.register(descriptor('plugin:details'));

    expect(() => registry.register(descriptor('plugin:details'))).toThrow(
      'Element details tab already registered: plugin:details'
    );
  });
});
