import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import Select from './Select.vue';

describe('Select', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let root: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    root?.remove();
  });

  it('retains its model while options load', async () => {
    const model = ref('sortOrder');
    const options = ref<Array<{label: string; value: string}>>([]);
    const updates: string[] = [];
    root = document.createElement('div');
    document.body.append(root);
    app = createApp({
      render: () =>
        h(Select, {
          modelValue: model.value,
          options: options.value,
          'onUpdate:modelValue': (value: string | number) => {
            updates.push(String(value));
            model.value = String(value);
          },
        }),
    });
    app.mount(root);
    await nextTick();

    const control = root.querySelector('craft-select') as HTMLElement & {
      modelValue: string;
    };
    control.modelValue = '';
    control.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );

    options.value = [
      {label: 'Title', value: 'title'},
      {label: 'Order', value: 'sortOrder'},
    ];
    await nextTick();
    await new Promise((resolve) => setTimeout(resolve, 0));

    expect(model.value).toBe('sortOrder');
    expect(root.querySelector('select')?.value).toBe('sortOrder');
    expect(updates).toEqual([]);
  });
});
