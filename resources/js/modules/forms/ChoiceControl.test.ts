import {createApp, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import ChoiceControl from './ChoiceControl.vue';
import type {FormControlPayload, FormValue} from './types';

// `craft-button-group` calls attachInternals(), which happy-dom doesn't
// implement. Same stub FormRenderer.test.ts uses.
const attachInternals = Object.getOwnPropertyDescriptor(
  HTMLElement.prototype,
  'attachInternals'
);

describe('ChoiceControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;
  let emitted: unknown[];

  const options = [
    {label: 'One', value: 'one'},
    {label: 'Two', value: 'two'},
    {label: 'Three', value: 'three'},
  ];

  beforeEach(() => {
    Object.defineProperty(HTMLElement.prototype, 'attachInternals', {
      configurable: true,
      value: () => ({setFormValue: vi.fn()}),
    });
  });

  afterEach(() => {
    app?.unmount();
    container?.remove();

    if (attachInternals) {
      Object.defineProperty(
        HTMLElement.prototype,
        'attachInternals',
        attachInternals
      );
    } else {
      delete (HTMLElement.prototype as Partial<HTMLElement>).attachInternals;
    }
  });

  async function mount(
    props: Record<string, unknown>,
    value: FormValue
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

  const previewModes = [
    {label: 'Show thumbnails and titles', value: 'full'},
    {label: 'Show thumbnails only', value: 'thumbs'},
  ];

  function selectOptionLabels(): string[] {
    return [...container!.querySelectorAll('option')].map(
      (option) => option.textContent?.trim() ?? ''
    );
  }

  it('leads a single select with an unlabelled blank option by default', async () => {
    await mount(
      {multiple: false, presentation: 'select', options: previewModes},
      'full'
    );

    expect(selectOptionLabels()).toEqual([
      '',
      'Show thumbnails and titles',
      'Show thumbnails only',
    ]);
  });

  it('labels the blank option from the placeholder prop', async () => {
    await mount(
      {
        multiple: false,
        presentation: 'select',
        options: previewModes,
        placeholder: 'Select…',
      },
      'full'
    );

    expect(selectOptionLabels()[0]).toBe('Select…');
  });

  it('drops the blank option for a control with no empty state', async () => {
    // `Choice::withoutPlaceholder()` — the setting always holds a value, so
    // there's nothing for a blank option to mean.
    await mount(
      {
        multiple: false,
        presentation: 'select',
        options: previewModes,
        placeholder: false,
      },
      'full'
    );

    expect(selectOptionLabels()).toEqual([
      'Show thumbnails and titles',
      'Show thumbnails only',
    ]);
  });

  it('renders an icon option as an empty, labelled button', async () => {
    await mount(
      {
        presentation: 'buttons',
        multiple: false,
        options: [
          {label: 'Sort ascending', icon: 'asc', value: 'asc'},
          {label: 'Sort descending', icon: 'desc', value: 'desc'},
        ],
      },
      'asc'
    );

    const buttons = [...container!.querySelectorAll('craft-button')];

    expect(buttons.map((b) => b.getAttribute('icon'))).toEqual(['asc', 'desc']);
    expect(buttons.map((b) => b.getAttribute('aria-label'))).toEqual([
      'Sort ascending',
      'Sort descending',
    ]);
    // `craft-button` only keeps its square icon-only treatment while its light
    // DOM has no rendered content.
    expect(buttons.every((b) => b.textContent?.trim() === '')).toBe(true);
  });

  const allOption = {allLabel: 'All', allValue: '*', allMode: 'singleValue'};

  function allInput(): HTMLInputElement {
    return container!.querySelector<HTMLInputElement>(
      'craft-checkbox-indeterminate > input[slot="input"]'
    )!;
  }

  it('nests the options inside the All checkbox that governs them', async () => {
    await mount(allOption, ['two']);

    const all = container!.querySelector('craft-checkbox-indeterminate')!;

    // Nesting is how the component finds the boxes it governs.
    expect(all.querySelectorAll('craft-checkbox')).toHaveLength(3);
    expect(allInput().value).toBe('*');
    expect(allInput().checked).toBe(false);
  });

  it('renders the governed options checked while All is on', async () => {
    await mount(allOption, ['*']);

    expect(allInput().checked).toBe(true);

    // Checked, so the group still reads as fully selected. Not disabled: Lion
    // skips disabled children when All propagates, which would leave it unable
    // to clear them again — `craft-checkbox-indeterminate` parks their `name`
    // instead, which ChoiceAllOptionTest covers on the rendered markup.
    const items = checkboxes();

    expect(items.every((item) => (item as any).checked === true)).toBe(true);
    expect(items.every((item) => (item as any).disabled === false)).toBe(true);
  });

  it('emits the All token when All is checked, and nothing when unchecked', async () => {
    await mount(allOption, ['two']);

    allInput().checked = true;
    allInput().dispatchEvent(new Event('change', {bubbles: true}));

    expect(emitted).toEqual([['*']]);

    await mount(allOption, ['*']);

    allInput().checked = false;
    allInput().dispatchEvent(new Event('change', {bubbles: true}));

    expect(emitted).toEqual([[]]);
  });

  it('emits the selection in display order', async () => {
    await mount({sortable: true}, []);

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
