import {createApp, h, nextTick, reactive} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import ChoiceControl from './ChoiceControl.vue';
import type {FormControlPayload} from './types';

describe('ChoiceControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;
  let emitted: unknown[];

  const options = [
    {label: 'One', value: 'one'},
    {label: 'Two', value: 'two'},
    {label: 'Three', value: 'three'},
  ];

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  async function mount(
    props: Record<string, unknown>,
    value: unknown
  ): Promise<void> {
    emitted = [];
    const control = reactive<FormControlPayload<any>>({
      type: 'CraftCms\\Cms\\Form\\Controls\\Choice',
      component: 'craft:choice',
      props: {options, multiple: true, presentation: 'checkboxes', ...props},
      path: ['settings', 'sites'],
      mode: 'editable',
      deltaGroup: ['settings', 'sites'],
    });
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(ChoiceControl, {
          control,
          value,
          editable: true,
          invalid: false,
          required: false,
          'onUpdate:value': (next: unknown) => emitted.push(next),
        }),
    });
    app.mount(container);
    await nextTick();
  }

  // `handleValueChange` reads the group's `modelValue`; happy-dom won't
  // register Lion's children far enough to compute it, so state it directly.
  function reportChecked(values: string[]): void {
    const group = container!.querySelector('craft-checkbox-group')!;
    Object.defineProperty(group, 'modelValue', {
      value: values,
      configurable: true,
    });
    group.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
  }

  function checkboxes(): HTMLElement[] {
    return [...container!.querySelectorAll<HTMLElement>('craft-checkbox')];
  }

  it('renders All checked and disables every other option', async () => {
    await mount({allowAll: true}, '*');

    const [all, ...items] = checkboxes();

    expect((all as any).choiceValue).toBe('*');
    expect((all as any).disabled).toBe(false);
    expect(items.map((item) => (item as any).choiceValue)).toEqual([
      'one',
      'two',
      'three',
    ]);
    expect(items.every((item) => (item as any).disabled === true)).toBe(true);
  });

  it('emits the All sentinel when All is checked', async () => {
    await mount({allowAll: true}, ['two']);

    reportChecked(['*', 'two']);

    expect(emitted).toEqual(['*']);
  });

  it('clears the selection when All is unchecked', async () => {
    await mount({allowAll: true}, '*');

    // The options still report checked at this point — only All changed.
    reportChecked(['one', 'two', 'three']);

    expect(emitted).toEqual([[]]);
  });

  it('emits the selection in display order', async () => {
    await mount({allowAll: true}, []);

    reportChecked(['three', 'one']);

    expect(emitted).toEqual([['one', 'three']]);
  });

  it('re-emits the value in the new order after a reorder', async () => {
    // The server sends the selected options first, in their stored order.
    await mount(
      {
        sortable: true,
        options: [
          {label: 'One', value: 'one'},
          {label: 'Three', value: 'three'},
          {label: 'Two', value: 'two'},
        ],
      },
      ['one', 'three']
    );

    const handle = container!.querySelector('craft-reorder-button')!;
    handle.dispatchEvent(
      new CustomEvent('reorder', {detail: {direction: 'down'}})
    );
    await nextTick();

    // 'one' moved below 'three'; the selection is unchanged but its order is.
    expect(emitted[0]).toEqual(['three', 'one']);
  });
});
