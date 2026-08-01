import {createApp, defineComponent, h, nextTick, reactive, ref} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import CheckboxSelectInputRenderer from './renderers/CheckboxSelectInputRenderer.vue';
import ComboboxInputRenderer from './renderers/ComboboxInputRenderer.vue';
import SelectInputRenderer from './renderers/SelectInputRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];
type CheckboxGroupElement = HTMLElement & {
  registrationComplete: Promise<unknown>;
  updateComplete: Promise<unknown>;
};

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('choice input renderers', () => {
  it('preserves host values, accessibility, read-only state, and control identity', async () => {
    const registry = createCpComponentRegistry();
    const container = document.createElement('div');
    const readOnly = ref(false);
    const values = reactive({
      settings: {
        status: null as string | null,
        path: '@storage/uploads',
        sources: ['images'] as string[] | string,
      },
    });

    registry.register('form-element:craft:select-input', SelectInputRenderer);
    registry.register(
      'form-element:craft:combobox-input',
      ComboboxInputRenderer
    );
    registry.register(
      'form-element:craft:checkbox-select-input',
      CheckboxSelectInputRenderer
    );
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);

    const host = defineComponent({
      setup() {
        return () =>
          h(FormDefinitionRenderer, {
            definition,
            bindingScope: 'settings',
            values,
            errors: {'settings.status': ['Choose a status.']},
            readOnly: readOnly.value,
          });
      },
    });
    const app = createApp(host);

    mountedApps.push(app);
    app.mount(container);
    await nextTick();

    const fields = Array.from(
      container.querySelectorAll<HTMLElementTagNameMap['craft-field']>(
        'craft-field'
      )
    );
    const select =
      container.querySelector<HTMLElementTagNameMap['craft-select']>(
        'craft-select'
      )!;
    const nativeSelect = select.querySelector('select')!;
    const combobox =
      container.querySelector<HTMLElementTagNameMap['craft-combobox']>(
        'craft-combobox'
      )!;
    const checkboxGroup = container.querySelector<CheckboxGroupElement>(
      'craft-checkbox-group'
    )!;
    const comboboxInput = (
      combobox as unknown as {_inputNode: HTMLInputElement}
    )._inputNode;

    await Promise.all(fields.map((field) => field.updateComplete));
    await combobox.updateComplete;
    await checkboxGroup.registrationComplete;
    await checkboxGroup.updateComplete;

    const checkboxes = Array.from(
      checkboxGroup.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')
    );
    const selectOptions = nativeSelect.querySelectorAll('option');
    const labels = fields.map((field) =>
      field.querySelector<HTMLLabelElement>(':scope > label[slot="label"]')!
    );

    expect(nativeSelect.querySelector('optgroup')?.label).toBe('Visible');
    expect(selectOptions[0]!.value).toBe('');
    expect(selectOptions[0]!.selected).toBe(true);
    expect(selectOptions[2]!.disabled).toBe(true);
    expect(selectOptions[3]!.dataset.reason).toBe('restricted');
    expect(nativeSelect.querySelectorAll('optgroup')[1]!.disabled).toBe(true);
    expect(combobox.modelValue).toBe('@storage/uploads');
    expect(comboboxInput.placeholder).toBe('/path/to/folder');
    expect(combobox.options).toEqual([
      {
        type: 'optgroup',
        label: 'Aliases',
        options: [{label: '@storage', value: '@storage'}],
      },
    ]);
    expect(combobox.limit).toBe(25);
    expect(combobox.clearable).toBe(true);
    expect(checkboxes.map(({checked}) => checked)).toEqual([
      false,
      true,
      false,
      false,
    ]);
    expect(checkboxes[2]!.disabled).toBe(true);
    expect(
      checkboxGroup.querySelector<HTMLElement>('.color-preview')!.style
        .backgroundColor
    ).toBe('#ff0000');
    expect(labels[0]!.htmlFor).toBe(select.id);
    expect(labels[1]!.htmlFor).toBe(combobox.id);
    expect(labels[2]!.htmlFor).toBe(checkboxGroup.id);
    expect(select.getAttribute('aria-labelledby')).toBe(labels[0]!.id);
    expect(combobox.getAttribute('aria-labelledby')).toBe(labels[1]!.id);
    expect(checkboxGroup.getAttribute('aria-labelledby')).toBe(labels[2]!.id);

    values.settings.sources = '*';
    await nextTick();

    expect(checkboxes.map(({checked}) => checked)).toEqual([
      true,
      true,
      true,
      true,
    ]);
    expect(checkboxes.map(({disabled}) => disabled)).toEqual([
      false,
      true,
      true,
      true,
    ]);

    values.settings.sources = ['images'];
    await nextTick();

    readOnly.value = true;
    await nextTick();

    expect(container.querySelector('craft-select')).toBe(select);
    expect(container.querySelector('craft-combobox')).toBe(combobox);
    expect(container.querySelector('craft-checkbox-group')).toBe(checkboxGroup);
    expect(select.disabled).toBe(true);
    expect(combobox.disabled).toBe(true);
    expect(checkboxes.every(({disabled}) => disabled)).toBe(true);
    expect(values.settings).toEqual({
      status: null,
      path: '@storage/uploads',
      sources: ['images'],
    });

    readOnly.value = false;
    await nextTick();

    nativeSelect.value = 'published';
    nativeSelect.dispatchEvent(new Event('change', {bubbles: true}));
    combobox.modelValue = '@web/uploads';
    combobox.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
    );
    checkboxes[3]!.checked = true;
    checkboxes[3]!.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(values.settings).toEqual({
      status: 'published',
      path: '@web/uploads',
      sources: ['images', 'videos'],
    });
    expect(container.querySelector('craft-select')).toBe(select);
    expect(container.querySelector('craft-combobox')).toBe(combobox);
    expect(container.querySelector('craft-checkbox-group')).toBe(checkboxGroup);
  });
});

const definition = {
  elements: [
    field('Status', 'craft:select-input', 'status', {
      options: [
        {label: 'Choose a status', value: null},
        {
          type: 'optgroup',
          label: 'Visible',
          options: [
            {label: 'Published', value: 'published'},
            {label: 'Archived', value: 'archived', disabled: true},
          ],
        },
        {
          type: 'optgroup',
          label: 'Unavailable',
          disabled: true,
          options: [
            {
              label: 'Draft',
              value: 'draft',
              data: {reason: 'restricted'},
            },
          ],
        },
      ],
    }),
    field('Path', 'craft:combobox-input', 'path', {
      options: [
        {
          type: 'optgroup',
          label: 'Aliases',
          options: [{label: '@storage', value: '@storage'}],
        },
      ],
      placeholder: '/path/to/folder',
      allowAliases: true,
      limit: 25,
      clearable: true,
    }),
    field('Sources', 'craft:checkbox-select-input', 'sources', {
      options: [
        {label: 'All', value: '*'},
        {label: 'Images', value: 'images', color: '#ff0000'},
        {label: 'Documents', value: 'documents', disabled: true},
        {label: 'Videos', value: 'videos'},
      ],
      allOption: '*',
    }),
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

function field(
  label: string,
  type: string,
  name: string,
  props: Record<string, CraftCms.Cms.Cp.FormDefinitions.Data.JsonValue>
) {
  return {
    type: 'craft:field',
    props: {label},
    children: [{type, name, props}],
  };
}
