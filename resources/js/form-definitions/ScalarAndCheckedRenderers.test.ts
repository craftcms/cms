import {createApp, nextTick, reactive} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import '@craftcms/ui/components/input/input';
import '@craftcms/ui/components/switch/switch';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import {registerNativeFormElementRenderers} from './form-element-types';

const mountedApplications: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApplications.splice(0).forEach((application) => application.unmount());
  document.body.innerHTML = '';
});

describe('scalar and checked Form Element Renderers', () => {
  it('preserves initial host values and normalizes later edits', async () => {
    const values = reactive({
      settings: {
        title: 'Craft',
        enabled: true,
        date: '2026-01-02T03:04:05+00:00' as string | null,
        time: '08:30:59' as string | null,
        limit: 42 as number | string,
      },
    });
    const container = document.createElement('div');
    const registry = createCpComponentRegistry();

    registerNativeFormElementRenderers(registry);
    (window as any).Cp = {$components: registry};
    document.body.append(container);

    const application = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          input('craft:text-input', 'title', {placeholder: 'Title'}),
          input('craft:lightswitch-input', 'enabled'),
          input('craft:date-input', 'date', {type: 'date'}),
          input('craft:time-input', 'time', {type: 'time'}),
          input('craft:number-input', 'limit', {type: 'number', min: 1}),
        ],
      },
      bindingScope: 'settings',
      values,
      errors: {},
    });

    mountedApplications.push(application);
    application.mount(container);

    const text = control(container, 'settings[title]');
    const date = control(container, 'settings[date]');
    const time = control(container, 'settings[time]');
    const number = control(container, 'settings[limit]');
    const lightswitch =
      container.querySelector<HTMLElementTagNameMap['craft-switch']>(
        'craft-switch'
      )!;

    expect(text.modelValue).toBe('Craft');
    expect(text.placeholder).toBe('Title');
    expect(lightswitch.checked).toBe(true);
    expect(date.modelValue).toBe('2026-01-02T03:04:05+00:00');
    expect(date.formatter(date.modelValue)).toBe('2026-01-02');
    expect(time.modelValue).toBe('08:30:59');
    expect(time.formatter(time.modelValue)).toBe('08:30');
    expect(number.modelValue).toBe(42);
    expect(number.getAttribute('min')).toBe('1');

    dispatchInitializationEvent(text, '');
    dispatchInitializationEvent(number, '');
    await nextTick();

    expect(values.settings.title).toBe('Craft');
    expect(values.settings.limit).toBe(42);

    update(text, 'Craft CMS');
    lightswitch.checked = false;
    lightswitch.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    update(date, '2027-03-04');
    update(time, '09:45');
    update(number, '120');
    await nextTick();

    expect(values.settings).toMatchObject({
      title: 'Craft CMS',
      enabled: false,
      date: '2027-03-04',
      time: '09:45',
      limit: '120',
    });

    update(date, '');
    update(time, '');
    update(number, '');
    await nextTick();

    expect(values.settings.date).toBe('');
    expect(values.settings.time).toBe('');
    expect(values.settings.limit).toBe('');
  });

  it('disables a checked control in an effectively read-only form', () => {
    const container = document.createElement('div');
    const registry = createCpComponentRegistry();

    registerNativeFormElementRenderers(registry);
    (window as any).Cp = {$components: registry};
    document.body.append(container);

    const application = createApp(FormDefinitionRenderer, {
      definition: {elements: [input('craft:lightswitch-input', 'enabled')]},
      bindingScope: 'settings',
      values: {settings: {enabled: true}},
      errors: {},
      readOnly: true,
    });

    mountedApplications.push(application);
    application.mount(container);

    expect(
      container.querySelector<HTMLElementTagNameMap['craft-switch']>(
        'craft-switch'
      )!.disabled
    ).toBe(true);
  });
});

function input(
  type: string,
  name: string,
  props?: CraftCms.Cms.Cp.FormDefinitions.Data.FormElementData['props']
): CraftCms.Cms.Cp.FormDefinitions.Data.FormElementData {
  return {type, name, props};
}

function control(
  container: HTMLElement,
  name: string
): HTMLElementTagNameMap['craft-input'] {
  return Array.from(container.querySelectorAll('craft-input')).find(
    (input) => input.name === name
  )!;
}

function update(
  input: HTMLElementTagNameMap['craft-input'],
  value: string
): void {
  input.modelValue = value;
  input.dispatchEvent(new CustomEvent('model-value-changed', {bubbles: true}));
}

function dispatchInitializationEvent(
  input: HTMLElementTagNameMap['craft-input'],
  value: string
): void {
  Object.defineProperty(input, 'modelValue', {configurable: true, value});
  input.dispatchEvent(
    new CustomEvent('model-value-changed', {
      bubbles: true,
      detail: {initialize: true},
    })
  );
  Reflect.deleteProperty(input, 'modelValue');
}
