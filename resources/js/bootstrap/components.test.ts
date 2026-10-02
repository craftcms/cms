import {describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from './components';
import {createApp, defineComponent, h, useId} from 'vue';

const testComponent = defineComponent({name: 'TestComponent'});
const alternateComponent = defineComponent({name: 'AlternateComponent'});

function createTestApp() {
  const app = createApp(defineComponent({render: () => null}));
  const component = vi.spyOn(app, 'component');

  return {app, component};
}

describe('CP component registry', () => {
  it('installs registered components', () => {
    const registry = createCpComponentRegistry();
    const {app, component} = createTestApp();

    registry.register('TestComponent', testComponent);
    registry.install(app);

    expect(component).toHaveBeenCalledWith('TestComponent', testComponent);
  });

  it('registers components added after install', () => {
    const registry = createCpComponentRegistry();
    const {app, component} = createTestApp();

    registry.install(app);
    registry.register('TestComponent', testComponent);

    expect(component).toHaveBeenCalledWith('TestComponent', testComponent);
  });

  it('registers components with every mounted form host', () => {
    const registry = createCpComponentRegistry();
    const {app: firstApp, component: firstComponent} = createTestApp();
    const {app: secondApp, component: secondComponent} = createTestApp();

    registry.install(firstApp);
    registry.install(secondApp);
    registry.register('TestComponent', testComponent);

    expect(firstComponent).toHaveBeenCalledWith('TestComponent', testComponent);
    expect(secondComponent).toHaveBeenCalledWith(
      'TestComponent',
      testComponent
    );
  });

  it('gives each mounted app a unique Vue id prefix', () => {
    const registry = createCpComponentRegistry();
    const component = defineComponent({
      setup: () => () => h('input', {id: useId()}),
    });
    const firstRoot = document.createElement('div');
    const secondRoot = document.createElement('div');
    const firstApp = createApp(component);
    const secondApp = createApp(component);

    registry.install(firstApp);
    registry.install(secondApp);
    firstApp.mount(firstRoot);
    secondApp.mount(secondRoot);

    expect(firstRoot.querySelector('input')?.id).not.toBe(
      secondRoot.querySelector('input')?.id
    );

    firstApp.unmount();
    secondApp.unmount();
  });

  it('stops registering components with unmounted form hosts', () => {
    const registry = createCpComponentRegistry();
    const {app, component} = createTestApp();

    registry.install(app);
    registry.uninstall(app);
    registry.register('TestComponent', testComponent);

    expect(component).not.toHaveBeenCalled();
  });

  it('allows duplicate registrations with the same value', () => {
    const registry = createCpComponentRegistry();

    registry.register('TestComponent', testComponent);

    expect(() => {
      registry.register('TestComponent', testComponent);
    }).not.toThrow();
  });

  it('fails duplicate registrations with a different value', () => {
    const registry = createCpComponentRegistry();

    registry.register('TestComponent', testComponent);

    expect(() => {
      registry.register('TestComponent', alternateComponent);
    }).toThrow('CP component already registered: TestComponent');
  });
});
