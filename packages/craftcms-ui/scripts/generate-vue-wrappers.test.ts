import {readFileSync} from 'node:fs';
import {resolve} from 'node:path';
import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import CraftColorPalette from '../dist/vue/CraftColorPalette.vue';
import CraftFieldLayout from '../dist/vue/CraftFieldLayout.vue';
import CraftKeyedTable from '../dist/vue/CraftKeyedTable.vue';
import CraftObjectSelect from '../dist/vue/CraftObjectSelect.vue';
import CraftOptionRows from '../dist/vue/CraftOptionRows.vue';
import CraftSwitch from '../dist/vue/CraftSwitch.vue';

class PropertyControl extends HTMLElement {
  value: unknown;
}

class CheckedControl extends HTMLElement {
  checked = false;
  disabled = false;
  label = '';
  onLabel = '';
  offLabel = '';
  size = '';
}

customElements.define('craft-color-palette', PropertyControl);
customElements.define('craft-switch', CheckedControl);

const mountedApplications: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApplications.splice(0).forEach((application) => application.unmount());
  document.body.innerHTML = '';
});

describe('generated property-value wrappers', () => {
  it('generates wrappers for every aligned structured control', () => {
    expect([
      CraftColorPalette,
      CraftKeyedTable,
      CraftObjectSelect,
      CraftFieldLayout,
      CraftOptionRows,
    ]).not.toContain(undefined);
  });

  it('synchronizes value and ignores initialization input events', async () => {
    const model = ref([{color: '#ff0000', label: 'Red', default: true}]);
    const application = createApp(
      defineComponent(
        () => () =>
          h(CraftColorPalette, {
            modelValue: model.value,
            readonly: true,
            'onUpdate:modelValue': (value) => (model.value = value),
          })
      )
    );
    const container = document.createElement('div');

    document.body.append(container);
    mountedApplications.push(application);
    application.mount(container);

    const control = container.querySelector<PropertyControl>(
      'craft-color-palette'
    )!;

    expect(control.value).toEqual(model.value);
    expect(control.hasAttribute('readonly')).toBe(true);

    control.value = [];
    control.dispatchEvent(
      new CustomEvent('input', {
        bubbles: true,
        detail: {initialize: true},
      })
    );
    await nextTick();

    expect(model.value).toEqual([
      {color: '#ff0000', label: 'Red', default: true},
    ]);

    const edited = [{color: '#00ff00', label: 'Green', default: false}];
    control.value = edited;
    control.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(model.value).toEqual(edited);
  });

  it('keeps checked models boolean and normalizes declared readonly state', async () => {
    const model = ref(true);
    const application = createApp(
      defineComponent(
        () => () =>
          h(CraftSwitch, {
            modelValue: model.value,
            readonly: true,
            label: 'Status',
            onLabel: 'Enabled',
            offLabel: 'Disabled',
            size: 'small',
            'onUpdate:modelValue': (value) => (model.value = value),
          })
      )
    );
    const container = document.createElement('div');

    document.body.append(container);
    mountedApplications.push(application);
    application.mount(container);

    const control = container.querySelector<CheckedControl>('craft-switch')!;

    expect(control.checked).toBe(true);
    expect(control.disabled).toBe(true);
    expect(control.label).toBe('Status');
    expect(control.onLabel).toBe('Enabled');
    expect(control.offLabel).toBe('Disabled');
    expect(control.size).toBe('small');

    control.checked = false;
    control.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();

    expect(model.value).toBe(false);
  });

  it('emits model and readonly TypeScript declarations', () => {
    const propertyDeclaration = readFileSync(
      resolve(process.cwd(), 'dist/vue/CraftColorPalette.vue.d.ts'),
      'utf8'
    );
    const checkedDeclaration = readFileSync(
      resolve(process.cwd(), 'dist/vue/CraftSwitch.vue.d.ts'),
      'utf8'
    );

    expect(propertyDeclaration).toContain('modelValue?: ColorPaletteRow[];');
    expect(propertyDeclaration).toContain(
      "'onUpdate:modelValue'?: (val: ColorPaletteRow[]) => void;"
    );
    expect(checkedDeclaration).toContain('modelValue?: boolean | null;');
    expect(checkedDeclaration).toContain('readonly?: boolean;');
    expect(checkedDeclaration).toContain('onLabel?: string;');
  });
});
