import {createApp, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import UiRenderer from './UiRenderer.vue';
import {registerUiComponents} from './register';
import type {UiPayload} from './types';

describe('HandleControl', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  let form: HTMLFormElement;

  beforeEach(async () => {
    form = document.createElement('form');
    container = document.createElement('div');
    form.append(container);
    document.body.append(form);

    app = createApp({
      setup: () => () => h(UiRenderer, {payload}),
    });
    const components = createCpComponentRegistry();
    registerUiComponents(components);
    components.install(app);
    app.mount(container);
    await nextTick();
  });

  afterEach(() => {
    app.unmount();
    form.remove();
  });

  it('renders and submits handles generated from a relative source until directly edited', async () => {
    const handle = container.querySelector<HTMLElement & {modelValue: string}>(
      'craft-input-handle'
    )!;
    const name = container.querySelector<HTMLInputElement>(
      'input[name="settings[identity][name]"]'
    )!;

    expect(handle).not.toBeNull();
    expect(new FormData(form).get('settings[identity][handle]')).toBe(
      'initialName'
    );

    name.value = 'First generated handle';
    name.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();
    await nextTick();

    expect(handle.modelValue).toBe('firstGeneratedHandle');
    expect(new FormData(form).get('settings[identity][handle]')).toBe(
      'firstGeneratedHandle'
    );

    name.value = 'Second generated handle';
    name.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();
    await nextTick();

    expect(handle.modelValue).toBe('secondGeneratedHandle');

    handle.modelValue = 'customHandle';
    handle.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    handle.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    name.value = 'Ignored source change';
    name.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();
    await nextTick();

    expect(handle.modelValue).toBe('customHandle');
    expect(new FormData(form).get('settings[identity][handle]')).toBe(
      'customHandle'
    );
  });
});

const payload: UiPayload = {
  scope: ['settings'],
  refreshable: false,
  nodes: [
    {
      type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
      component: 'craft:field',
      props: {label: 'Name', instructions: null, required: true},
      control: {
        type: 'CraftCms\\Cms\\Ui\\Controls\\Text',
        component: 'craft:text',
        props: {},
        path: ['settings', 'identity', 'name'],
        mode: 'editable',
        deltaGroup: ['settings', 'identity', 'name'],
      },
    },
    {
      type: 'CraftCms\\Cms\\Ui\\Nodes\\Field',
      component: 'craft:field',
      props: {label: 'Handle', instructions: null, required: true},
      control: {
        type: 'CraftCms\\Cms\\Ui\\Controls\\Handle',
        component: 'craft:handle',
        props: {source: ['name']},
        path: ['settings', 'identity', 'handle'],
        mode: 'editable',
        deltaGroup: ['settings', 'identity', 'handle'],
      },
    },
  ],
  values: {
    settings: {identity: {name: 'Initial name', handle: 'initialName'}},
  },
  errors: [],
  globalErrors: [],
};
