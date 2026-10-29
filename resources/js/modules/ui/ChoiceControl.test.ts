import {createApp, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type CraftCheckboxSelect from '@craftcms/ui/components/checkbox-select/checkbox-select';
import ChoiceControl from './ChoiceControl.vue';
import type {UiControlPayload, UiValue} from './types';

// `craft-button-group` calls attachInternals(), which happy-dom doesn't
// implement. Same stub UiRenderer.test.ts uses.
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
    value: UiValue
  ): Promise<void> {
    emitted = [];
    const control = reactive<UiControlPayload<any>>({
      type: 'CraftCms\\Cms\\Ui\\Controls\\Choice',
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

  const previewModes = [
    {label: 'Show thumbnails and titles', value: 'full'},
    {label: 'Show thumbnails only', value: 'thumbs'},
  ];

  function selectOptionLabels(): string[] {
    return [...container!.querySelectorAll('option')].map(
      (option) => option.textContent?.trim() ?? ''
    );
  }

  it('leads a single select with an unlabeled blank option by default', async () => {
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

  it('renders single-choice buttons as radios drawn as buttons', async () => {
    await mount(
      {
        presentation: 'buttons',
        multiple: false,
        options: [
          {label: 'Sort ascending', icon: 'asc', value: 'asc'},
          {label: 'Descending', value: 'desc'},
        ],
      },
      'asc'
    );

    const group = container!.querySelector('craft-radio-group')!;
    const inputs = [
      ...group.querySelectorAll<HTMLInputElement>('input[type="radio"]'),
    ];
    const icon = group.querySelector('label craft-icon');

    expect(container!.querySelector('craft-button-group')).toBeNull();
    expect(group.hasAttribute('buttons')).toBe(true);
    expect(inputs.map((input) => input.value)).toEqual(['asc', 'desc']);
    expect(inputs.map((input) => input.checked)).toEqual([true, false]);
    expect(icon?.getAttribute('name')).toBe('asc');
    expect((icon as HTMLElement & {label?: string}).label).toBe(
      'Sort ascending'
    );
    expect(inputs[1]!.labels?.[0]?.textContent?.trim()).toBe('Descending');
  });

  it('renders an icon option as an empty, labeled button', async () => {
    await mount(
      {
        presentation: 'buttons',
        multiple: true,
        options: [
          {label: 'Sort ascending', icon: 'asc', value: 'asc'},
          {label: 'Sort descending', icon: 'desc', value: 'desc'},
        ],
      },
      ['asc']
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

  it.each([['checkboxes', true]])(
    'shows each %s option’s icon or color dot before its label',
    async (presentation, multiple) => {
      await mount(
        {
          presentation,
          multiple,
          options: [
            {label: 'Icon', value: 'icon', icon: 'star', color: '#ff0000'},
            {label: 'Color', value: 'color', color: '#00ff00'},
            {label: 'Plain', value: 'plain'},
          ],
        },
        multiple ? [] : ''
      );

      const labels = [
        ...container!.querySelectorAll(
          ':is(craft-checkbox, craft-radio) > label[slot=label]'
        ),
      ];
      const icon = labels[0]!.querySelector('craft-icon')!;

      expect(icon.getAttribute('name')).toBe('star');
      expect(icon.hasAttribute('label')).toBe(false);
      expect((icon as HTMLElement).style.color).toBe('#ff0000');
      expect(
        labels[1]!.querySelector('craft-indicator')!.getAttribute('fill')
      ).toBe('#00ff00');
      expect(labels[2]!.querySelector('craft-icon, craft-indicator')).toBe(
        null
      );
      expect(labels.map((label) => label.textContent?.trim())).toEqual([
        'Icon',
        'Color',
        'Plain',
      ]);
    }
  );

  it.each([
    ['single-choice', false, 'craft-radio'],
    ['multiple-choice', true, 'craft-button'],
  ])(
    'shows a %s button’s icon beside its label unless options show only their icons',
    async (_, multiple, selector) => {
      await mount(
        {
          presentation: 'buttons',
          multiple,
          iconsOnly: false,
          options: [{label: 'Left', value: 'left', icon: 'arrow-left'}],
        },
        multiple ? [] : ''
      );

      const option = container!.querySelector(selector)!;

      expect(option.textContent?.trim()).toBe('Left');
      expect(option.hasAttribute('title')).toBe(false);
      expect(option.hasAttribute('aria-label')).toBe(false);
      expect(option.querySelector('craft-icon[label]')).toBe(null);
    }
  );

  describe('custom checkbox options', () => {
    const customProps = {
      allowCustom: true,
      options: [
        {label: 'A', value: 'a'},
        {label: 'mine', value: 'mine', custom: true},
      ],
    };

    function customRows(): HTMLElement[] {
      return [...container!.querySelectorAll<HTMLElement>('[data-custom]')];
    }

    function textInput(row: HTMLElement): HTMLInputElement {
      return row.querySelector<HTMLInputElement>('input[type=text]')!;
    }

    function addButton(): HTMLElement {
      return container!.querySelector<HTMLElement>(
        'craft-button[data-add-option]'
      )!;
    }

    it('shows a saved custom value as a checked “Custom:” option with its text', async () => {
      await mount(customProps, ['mine']);

      const rows = customRows();
      const checkbox = rows[0]!.querySelector<HTMLInputElement>(
        'input[type=checkbox]'
      )!;

      expect(rows).toHaveLength(1);
      expect(checkbox.checked).toBe(true);
      expect(checkbox.value).toBe('mine');
      expect(rows[0]!.querySelector('label')!.textContent?.trim()).toBe(
        'Custom:'
      );
      expect(textInput(rows[0]!).value).toBe('mine');
      expect(
        [
          ...container!.querySelectorAll(
            'div:not([data-custom]) > craft-checkbox > label'
          ),
        ].map((label) => label.textContent?.trim())
      ).toEqual(['A']);
    });

    it('adds a focused custom option, and emits what’s typed into it', async () => {
      await mount(customProps, ['mine']);

      addButton().click();
      await nextTick();

      const row = customRows()[1]!;
      expect(document.activeElement).toBe(textInput(row));

      textInput(row).value = 'new';
      textInput(row).dispatchEvent(new Event('input', {bubbles: true}));
      await nextTick();

      expect(emitted.at(-1)).toEqual(['mine', 'new']);
      expect(
        row.querySelector<HTMLInputElement>('input[type=checkbox]')!.value
      ).toBe('new');
    });

    it('removes an empty custom option when it’s unchecked', async () => {
      await mount(customProps, []);

      addButton().click();
      await nextTick();

      const checkbox = customRows()[0]!.querySelector<HTMLInputElement>(
        'input[type=checkbox]'
      )!;
      checkbox.checked = false;
      checkbox.dispatchEvent(new Event('change', {bubbles: true}));
      await nextTick();

      expect(customRows()).toHaveLength(0);
      expect(document.activeElement).toBe(addButton());
    });
  });

  const allOption = {allLabel: 'All', allValue: '*', allMode: 'singleValue'};

  async function checkboxSelect(): Promise<CraftCheckboxSelect> {
    const element = container!.querySelector('craft-checkbox-select')!;
    await element.updateComplete;
    await new Promise((resolve) => setTimeout(resolve));
    await element.updateComplete;
    return element;
  }

  function allInput(element: CraftCheckboxSelect): HTMLInputElement {
    return element.querySelector<HTMLInputElement>(
      '.all input[type=checkbox]'
    )!;
  }

  it('renders “All” in a checkbox select, with the options and value', async () => {
    await mount(allOption, ['two']);
    const element = await checkboxSelect();

    expect(element.name).toBe('settings[sites]');
    expect(element.allLabel).toBe('All');
    expect(element.allMode).toBe('single-value');
    expect(element.options.map((option) => option.value)).toEqual([
      'one',
      'two',
      'three',
    ]);
    expect(element.modelValue).toEqual(['two']);
    expect(allInput(element).value).toBe('*');
  });

  it('emits the All token when All is checked, and nothing when unchecked', async () => {
    await mount(allOption, ['two']);
    let element = await checkboxSelect();

    allInput(element).checked = true;
    allInput(element).dispatchEvent(new Event('change', {bubbles: true}));

    expect(emitted).toEqual([['*']]);

    await mount(allOption, ['*']);
    element = await checkboxSelect();

    allInput(element).checked = false;
    allInput(element).dispatchEvent(new Event('change', {bubbles: true}));

    expect(emitted).toEqual([[]]);
  });

  it('renders sortable options and the add-menu presentation as asked', async () => {
    await mount({sortable: true}, ['two']);
    expect((await checkboxSelect()).sortable).toBe(true);
    expect((await checkboxSelect()).presentation).toBe('checkboxes');

    await mount({presentation: 'add-menu'}, ['two']);
    expect((await checkboxSelect()).sortable).toBe(false);
    expect((await checkboxSelect()).presentation).toBe('add-menu');
  });

  it('re-emits the value in the new order after a reorder, and keeps it', async () => {
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
    const element = await checkboxSelect();

    element
      .shadowRoot!.querySelector('craft-reorder-button')!
      .dispatchEvent(
        new CustomEvent('craft-reorder', {detail: {direction: 'down'}})
      );
    await nextTick();
    await element.updateComplete;

    // 'one' moved below 'three'; the selection is unchanged but its order is.
    expect(emitted.at(-1)).toEqual(['three', 'one']);
    expect(element.options.map((option) => option.value)).toEqual([
      'three',
      'one',
      'two',
    ]);
  });
});
