import '@craftcms/ui/components/checkbox/checkbox';
import '@craftcms/ui/components/checkbox-group/checkbox-group';
import type CraftCheckboxGroupElement from '@craftcms/ui/components/checkbox-group/checkbox-group';
import CraftCheckboxGroup from '@craftcms/ui/vue/CraftCheckboxGroup.vue';
import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';

describe('CraftCheckboxGroup', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('keeps checked options when it re-renders without a model', async () => {
    const error = ref<string | null>(null);
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(
          CraftCheckboxGroup,
          {error: error.value},
          {
            default: () => [
              h('craft-checkbox', {value: 'a', label: 'A', checked: true}),
              h('craft-checkbox', {value: 'b', label: 'B'}),
            ],
          }
        ),
    });
    app.mount(container);
    await nextTick();

    const group = container.querySelector<CraftCheckboxGroupElement>(
      'craft-checkbox-group'
    )!;
    await group.updateComplete;
    // Checked without a change event, as a script or the server would.
    group.querySelectorAll('input')[1]!.checked = true;
    expect(group.value).toEqual(['a', 'b']);

    error.value = 'Pick fewer';
    await nextTick();

    expect(group.value).toEqual(['a', 'b']);
  });

  it('checks what its model says, and updates the model on change', async () => {
    const model = ref<string[]>(['b']);
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(
          CraftCheckboxGroup,
          {
            modelValue: model.value,
            'onUpdate:modelValue': (value?: string[]) =>
              (model.value = value ?? []),
          },
          {
            default: () => [
              h('craft-checkbox', {value: 'a', label: 'A'}),
              h('craft-checkbox', {value: 'b', label: 'B'}),
            ],
          }
        ),
    });
    app.mount(container);
    await nextTick();

    const group = container.querySelector<CraftCheckboxGroupElement>(
      'craft-checkbox-group'
    )!;
    await group.updateComplete;
    expect(group.value).toEqual(['b']);

    group.querySelectorAll('input')[0]!.click();
    await nextTick();
    expect(model.value).toEqual(['a', 'b']);
  });
});
