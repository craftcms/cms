import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import FieldLayoutDesignerValueAdapter from './FieldLayoutDesignerValueAdapter.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('Field Layout Designer Form value adapter', () => {
  it('combines the existing designer and generated-fields values', async () => {
    const value = ref<Record<string, unknown>>({});
    const container = document.createElement('div');
    const host = defineComponent({
      setup() {
        return () =>
          h(FieldLayoutDesignerValueAdapter, {
            name: 'settings[fieldLayouts][content]',
            modelValue: value.value,
            designerHtml:
              '<craft-field-layout-designer><input data-config-input></craft-field-layout-designer>',
            generatedFieldsHtml: '<craft-generated-fields-table />',
            'onUpdate:modelValue': (nextValue: Record<string, unknown>) => {
              value.value = nextValue;
            },
          });
      },
    });
    const app = createApp(host);

    document.body.appendChild(container);
    mountedApps.push(app);
    app.mount(container);
    await nextTick();

    const designer = container.querySelector<
      HTMLElement & {serialize(): string}
    >('craft-field-layout-designer')!;
    const generatedFields = container.querySelector<
      HTMLElement & {serialize(): Record<string, unknown>[]}
    >('craft-generated-fields-table')!;
    const input = designer.querySelector<HTMLInputElement>(
      '[data-config-input]'
    )!;

    designer.serialize = () => JSON.stringify({tabs: [{uid: 'content'}]});
    generatedFields.serialize = () => [{uid: 'reading-time'}];
    input.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(input.name).toBe('settings[fieldLayouts][content]');
    expect(value.value).toEqual({
      tabs: [{uid: 'content'}],
      generatedFields: [{uid: 'reading-time'}],
    });

    generatedFields.serialize = () => [
      {uid: 'word-count'},
      {uid: 'reading-time'},
    ];
    generatedFields.dispatchEvent(new Event('sortChange', {bubbles: true}));
    await nextTick();

    expect(value.value.generatedFields).toEqual([
      {uid: 'word-count'},
      {uid: 'reading-time'},
    ]);
  });
});
