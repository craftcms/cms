import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import type {Ref} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import CheckboxSelectValueAdapter from './CheckboxSelectValueAdapter.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];
type Value =
  | string
  | number
  | boolean
  | null
  | Array<string | number | boolean | null>;

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

function mountAdapter(
  modelValue: Ref<Value>,
  props: Record<string, unknown> = {}
): HTMLElement {
  const container = document.createElement('div');
  const host = defineComponent({
    setup() {
      return () =>
        h(CheckboxSelectValueAdapter, {
          id: 'sources',
          name: 'settings[sources]',
          options: [
            {label: 'All', value: '*'},
            {label: 'Images', value: 'images', color: '#ff0000'},
            {label: 'Documents', value: 'documents'},
            {label: 'Locked', value: 'locked', disabled: true},
          ],
          allOption: '*',
          sortable: true,
          modelValue: modelValue.value,
          'onUpdate:modelValue': (value: Value) => {
            modelValue.value = value;
          },
          ...props,
        });
    },
  });
  const app = createApp(host);

  document.body.appendChild(container);
  mountedApps.push(app);
  app.mount(container);

  return container;
}

describe('Checkbox Select Form value adapter', () => {
  it('maps Form values onto legacy checkbox-select markup and sort events', async () => {
    const value = ref<Value>(['documents', 'images']);
    const container = mountAdapter(value);
    await nextTick();
    const sortable = container.querySelector('craft-sortable-checkbox-select')!;
    const inputs = Array.from(
      container.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')
    );
    const [all, documents, images, locked] = inputs as [
      HTMLInputElement,
      HTMLInputElement,
      HTMLInputElement,
      HTMLInputElement,
    ];

    expect(inputs.map((input) => input.value)).toEqual([
      '*',
      'documents',
      'images',
      'locked',
    ]);
    expect(documents.checked).toBe(true);
    expect(documents.name).toBe('settings[sources][]');
    expect(locked.disabled).toBe(true);

    documents
      .closest('.cp-checkbox-select__item')!
      .before(images.closest('.cp-checkbox-select__item')!);
    sortable.dispatchEvent(new Event('sortChange'));
    await nextTick();

    expect(value.value).toEqual(['images', 'documents']);

    all.checked = true;
    all.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(value.value).toBe('*');
    expect(documents.checked).toBe(true);
    expect(documents.disabled).toBe(true);
    expect(images.checked).toBe(true);
    expect(images.disabled).toBe(true);
  });

  it('disables sorting and every option when the Form is read-only', () => {
    const value = ref<Value>(['images']);
    const container = mountAdapter(value, {readonly: true});

    expect(
      container.querySelector('craft-sortable-checkbox-select')
    ).toBeNull();
    expect(
      Array.from(
        container.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')
      ).every((input) => input.disabled)
    ).toBe(true);
  });
});
