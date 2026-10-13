import {describe, expect, it, vi} from 'vite-plus/test';
import {defineComponent, nextTick, watchEffect} from 'vue';
import {
  createElementDetailsPanelRegistry,
  type ElementDetailsPanelDescriptor,
} from './element-details-panels';
import type {ElementEditPayload} from '@/modules/elements/composables/useElementEditor';

const component = defineComponent({render: () => null});

function descriptor(id: string): ElementDetailsPanelDescriptor {
  return {id, label: id, icon: 'circle-info', component};
}

describe('element details panel registry', () => {
  it('reactively exposes registrations', async () => {
    const registry = createElementDetailsPanelRegistry();
    let panelIds: string[] = [];

    watchEffect(() => {
      panelIds = registry.panels.map((panel) => panel.id);
    });

    registry.register(descriptor('plugin:details'));
    await nextTick();

    expect(panelIds).toEqual(['plugin:details']);
    expect(registry.hasVisible({} as ElementEditPayload)).toBe(true);
  });

  it('allows the same descriptor to be registered more than once', () => {
    const registry = createElementDetailsPanelRegistry();
    const panel = descriptor('plugin:details');

    registry.register(panel);
    registry.register(panel);

    expect(registry.panels).toHaveLength(1);
  });

  it('reports whether a panel is visible for an element payload', () => {
    const registry = createElementDetailsPanelRegistry();

    registry.register({
      ...descriptor('plugin:conditional'),
      visible: () => false,
    });

    expect(registry.hasVisible({} as ElementEditPayload)).toBe(false);
  });

  it('rejects different descriptors with the same id', () => {
    const registry = createElementDetailsPanelRegistry();

    registry.register(descriptor('plugin:details'));

    expect(() => registry.register(descriptor('plugin:details'))).toThrow(
      'Element details panel already registered: plugin:details'
    );
  });

  it('keeps the deprecated tabs alias, warning once', () => {
    const warn = vi.spyOn(console, 'warn').mockImplementation(() => {});
    const registry = createElementDetailsPanelRegistry();

    registry.register(descriptor('plugin:details'));

    expect(registry.tabs).toBe(registry.panels);
    expect(registry.tabs).toBe(registry.panels);
    expect(warn).toHaveBeenCalledOnce();
    expect(warn.mock.calls[0]![0]).toContain('Cp.$elementDetailsPanels.panels');
    warn.mockRestore();
  });
});
