import {createApp, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import type CraftCheckbox from '@craftcms/ui/components/checkbox/checkbox';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import {registerNativeFormElementRenderers} from './form-element-types';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(
      new Response('<svg xmlns="http://www.w3.org/2000/svg"></svg>', {
        status: 200,
      })
    )
  );
});

afterEach(() => {
  vi.unstubAllGlobals();
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('option field Form Elements', () => {
  it('renders single-choice options and additional settings through the generic renderer', async () => {
    const values = reactive({
      settings: {
        options: [
          {
            label: 'News',
            value: 'news',
            icon: '',
            color: 'ff0000',
            default: true,
          },
          {
            label: 'Opinion',
            value: 'opinion',
            icon: '',
            color: '0000ff',
            default: false,
          },
        ],
        iconsOnly: false,
      },
    });
    const container = mount(
      {
        elements: [
          {
            type: 'craft:field',
            props: {
              label: 'Button Group Options',
              instructions: 'Define the available buttons.',
              required: true,
            },
            children: [
              {
                type: 'craft:option-rows',
                name: 'options',
                props: {icons: true, colors: true},
              },
            ],
          },
          {
            type: 'craft:field',
            props: {label: 'Icons only'},
            children: [{type: 'craft:lightswitch-input', name: 'iconsOnly'}],
          },
        ],
      },
      values,
      {'settings.options': ['Option values must be unique.']}
    );
    await optionRowsUpdate(container);
    const field =
      container.querySelector<HTMLElementTagNameMap['craft-field']>(
        'craft-field'
      )!;
    await field.updateComplete;
    await field.updateComplete;
    const optionRows = optionRowsElement(container);
    const fieldLabel = field.querySelector<HTMLLabelElement>(
      ':scope > label[slot="label"]'
    )!;
    const instructions = field.querySelector<HTMLElement>(
      '[data-form-element-instructions]'
    )!;
    const feedback = field.querySelector<HTMLElement>(
      '[data-form-element-errors]'
    )!;

    expect(rowValues(container, 'label')).toEqual(['News', 'Opinion']);
    expect(rowValues(container, 'value')).toEqual(['news', 'opinion']);
    expect(rowValues(container, 'icon')).toEqual(['', '']);
    expect(rowValues(container, 'color')).toEqual(['ff0000', '0000ff']);
    expect(feedback.textContent).toContain('Option values must be unique.');
    expect(optionRows.getAttribute('role')).toBe('group');
    expect(optionRows.getAttribute('aria-required')).toBe('true');
    expect(optionRows.getAttribute('aria-labelledby')).toBe(fieldLabel.id);
    expect(optionRows.getAttribute('aria-describedby')?.split(' ')).toEqual([
      instructions.id,
      feedback.id,
    ]);

    const defaults = optionRowsRoot(container).querySelectorAll<CraftCheckbox>(
      '[data-option-default]'
    );
    defaults[1]!.checked = true;
    defaults[1]!.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(values.settings.options.map((option) => option.default)).toEqual([
      false,
      true,
    ]);
    expect(values.settings.options.map((option) => option.label)).toEqual([
      'News',
      'Opinion',
    ]);

    container
      .querySelector<HTMLElement>('craft-switch-button')!
      .dispatchEvent(new Event('click', {bubbles: true}));
    await nextTick();

    expect(values.settings.iconsOnly).toBe(true);
  });

  it('emits generated values and ordered multi-choice option updates into host state', async () => {
    const values = reactive({
      settings: {
        options: [
          {label: 'First Choice', value: 'firstChoice', default: true},
          {optgroup: 'Archived'},
          {label: 'Last Choice', value: 'lastChoice', default: false},
        ],
      },
    });
    const container = mount(
      {
        elements: [
          {
            type: 'craft:field',
            children: [
              {
                type: 'craft:option-rows',
                name: 'options',
                props: {multipleDefaults: true, optgroups: true},
              },
            ],
          },
        ],
      },
      values
    );
    await optionRowsUpdate(container);
    const firstLabel = optionRowsRoot(container).querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-option-row="0"] [data-option-label]')!;

    firstLabel.value = '24 Hours';
    firstLabel.dispatchEvent(new Event('input', {bubbles: true}));
    await optionRowsUpdate(container);

    expect(values.settings.options[0]).toMatchObject({
      label: '24 Hours',
      value: '24Hours',
    });

    firstLabel.value = 'Featured Stories';
    firstLabel.dispatchEvent(new Event('input', {bubbles: true}));
    await optionRowsUpdate(container);

    expect(values.settings.options[0]).toMatchObject({
      label: 'Featured Stories',
      value: 'featuredStories',
    });
    expect(
      optionRowsRoot(container).querySelector(
        '[data-option-row="0"] [data-option-label]'
      )
    ).toBe(firstLabel);

    const firstValue = optionRowsRoot(container).querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-option-row="0"] [data-option-value]')!;
    firstValue.value = 'featured';
    firstValue.dispatchEvent(new Event('input', {bubbles: true}));
    await optionRowsUpdate(container);

    expect(values.settings.options[0]?.value).toBe('featured');

    const currentFirstLabel = optionRowsRoot(container).querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-option-row="0"] [data-option-label]')!;
    currentFirstLabel.value = 'Top Stories';
    currentFirstLabel.dispatchEvent(new Event('input', {bubbles: true}));
    await optionRowsUpdate(container);

    expect(values.settings.options[0]).toMatchObject({
      label: 'Top Stories',
      value: 'featured',
    });

    optionRowsRoot(container)
      .querySelector<HTMLElement>('[data-option-row="2"] craft-reorder-button')!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          detail: {direction: 'up'},
        })
      );
    await optionRowsUpdate(container);

    expect(
      values.settings.options.map((option) => option.label ?? option.optgroup)
    ).toEqual(['Top Stories', 'Last Choice', 'Archived']);

    const secondDefault = optionRowsRoot(
      container
    ).querySelector<CraftCheckbox>(
      '[data-option-row="1"] [data-option-default]'
    )!;
    secondDefault.checked = true;
    secondDefault.dispatchEvent(new Event('change', {bubbles: true}));
    await optionRowsUpdate(container);

    expect(values.settings.options.map((option) => option.default)).toEqual([
      true,
      true,
      undefined,
    ]);

    optionRowsRoot(container)
      .querySelector<HTMLElement>('[data-add-option]')!
      .click();
    await optionRowsUpdate(container);
    expect(values.settings.options).toHaveLength(4);

    optionRowsRoot(container)
      .querySelector<HTMLElement>('[data-option-row="3"] [data-delete-option]')!
      .click();
    await optionRowsUpdate(container);
    expect(values.settings.options).toHaveLength(3);
  });

  it('honors generic read-only state for option rows and lightswitches', async () => {
    const container = mount(
      {
        elements: [
          {
            type: 'craft:field',
            children: [
              {
                type: 'craft:option-rows',
                name: 'options',
                props: {icons: true},
              },
            ],
          },
          {
            type: 'craft:field',
            children: [
              {type: 'craft:lightswitch-input', name: 'customOptions'},
            ],
          },
        ],
      },
      {
        settings: {
          options: [{label: 'News', value: 'news', icon: 'newspaper'}],
          customOptions: true,
        },
      },
      {},
      true
    );
    await optionRowsUpdate(container);

    for (const control of optionRowsRoot(
      container
    ).querySelectorAll<HTMLElement>('craft-input, craft-input-color')) {
      expect(
        (control as {readOnly?: boolean}).readOnly ||
          (control as {disabled?: boolean}).disabled
      ).toBe(true);
    }

    expect(
      Array.from(
        optionRowsRoot(container).querySelectorAll<HTMLElement>(
          'craft-checkbox, craft-button, craft-reorder-button, craft-icon-picker'
        )
      )
        .filter(
          (control) =>
            !(control as {disabled?: boolean}).disabled &&
            !control.hasAttribute('disabled')
        )
        .map((control) => control.outerHTML)
    ).toEqual([]);
    expect(optionRowsElement(container).getAttribute('aria-readonly')).toBe(
      'true'
    );
  });

  it('starts with an editable row when the host has no option value', async () => {
    const values = reactive({settings: {}});
    const container = mount(
      {
        elements: [
          {
            type: 'craft:field',
            children: [{type: 'craft:option-rows', name: 'options'}],
          },
        ],
      },
      values
    );
    await optionRowsUpdate(container);
    const label = optionRowsRoot(container).querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-option-label]')!;

    label.value = '24 Hours';
    label.dispatchEvent(new Event('input', {bubbles: true}));
    await optionRowsUpdate(container);

    expect(values.settings).toEqual({
      options: [{label: '24 Hours', value: '24Hours', default: false}],
    });
  });

  it('preserves unchanged option controls across a complete definition refresh', async () => {
    const definition = reactive({
      elements: [
        {
          type: 'craft:field',
          children: [
            {
              type: 'craft:option-rows',
              name: 'options',
              props: {icons: true},
            },
          ],
        },
      ],
    });
    const container = mount(
      definition as CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData,
      {
        settings: {
          options: [{label: 'News', value: 'news', icon: 'newspaper'}],
        },
      }
    );
    await optionRowsUpdate(container);

    const element = optionRowsElement(container);
    const label = optionRowsRoot(container).querySelector(
      '[data-option-row="0"] [data-option-label]'
    );
    const icon = optionRowsRoot(container).querySelector<HTMLElement>(
      '[data-option-row="0"] [data-option-icon]'
    )!;

    definition.elements = JSON.parse(JSON.stringify(definition.elements));
    await optionRowsUpdate(container);

    expect(optionRowsElement(container)).toBe(element);
    expect(
      optionRowsRoot(container).querySelector(
        '[data-option-row="0"] [data-option-label]'
      )
    ).toBe(label);
    expect(
      optionRowsRoot(container).querySelector(
        '[data-option-row="0"] [data-option-icon]'
      )
    ).toBe(icon);

    const row = element.value[0]!;
    row.icon = 'star';
    element.value = [row];
    await element.updateComplete;
    await nextTick();

    expect(
      icon.querySelector<HTMLElementTagNameMap['craft-input']>('craft-input')
        ?.modelValue
    ).toBe('star');

    element.readOnly = true;
    await element.updateComplete;
    await nextTick();

    expect(
      icon.querySelector<HTMLElementTagNameMap['craft-input']>('craft-input')
        ?.disabled
    ).toBe(true);
  });
});

function mount(
  definition: CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData,
  values: Record<string, unknown>,
  errors: Record<string, string[]> = {},
  readOnly = false
): HTMLElement {
  const registry = createCpComponentRegistry();
  const container = document.createElement('div');

  registerNativeFormElementRenderers(registry);
  (window as any).Cp = {$components: registry};
  document.body.appendChild(container);
  const app = createApp(FormDefinitionRenderer, {
    definition,
    bindingScope: 'settings',
    values,
    errors,
    readOnly,
  });

  mountedApps.push(app);
  app.mount(container);

  return container;
}

function rowValues(container: HTMLElement, name: string): string[] {
  return Array.from(
    optionRowsRoot(container).querySelectorAll<HTMLElement>(
      `[data-option-${name}]`
    ),
    (control) =>
      String(
        control.getAttribute('value') ??
          ('value' in control
            ? control.value
            : control.querySelector<HTMLElementTagNameMap['craft-input']>(
                'craft-input'
              )?.value)
      )
  );
}

function optionRowsElement(
  container: HTMLElement
): HTMLElementTagNameMap['craft-option-rows'] {
  return container.querySelector('craft-option-rows')!;
}

function optionRowsRoot(container: HTMLElement): ShadowRoot {
  return optionRowsElement(container).shadowRoot!;
}

async function optionRowsUpdate(container: HTMLElement): Promise<void> {
  await nextTick();
  await optionRowsElement(container).updateComplete;
}
