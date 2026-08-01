import {createApp, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import type CraftCheckbox from '@craftcms/ui/components/checkbox/checkbox';
import FormDefinitionRenderer from '../FormDefinitionRenderer.vue';
import LightswitchInputRenderer from './LightswitchInputRenderer.vue';
import OptionRowsRenderer from './OptionRowsRenderer.vue';

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
            props: {label: 'Button Group Options'},
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

    expect(rowValues(container, 'label')).toEqual(['News', 'Opinion']);
    expect(rowValues(container, 'value')).toEqual(['news', 'opinion']);
    expect(rowValues(container, 'icon')).toEqual(['', '']);
    expect(rowValues(container, 'color')).toEqual(['ff0000', '0000ff']);
    expect(
      container.querySelector('[data-form-element-errors]')?.textContent
    ).toContain('Option values must be unique.');

    const defaults = container.querySelectorAll<CraftCheckbox>(
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
    const firstLabel = container.querySelector<HTMLInputElement>(
      '[data-option-row="0"] [data-option-label]'
    )!;

    firstLabel.value = '24 Hours';
    firstLabel.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(values.settings.options[0]).toMatchObject({
      label: '24 Hours',
      value: '24Hours',
    });

    firstLabel.value = 'Featured Stories';
    firstLabel.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(values.settings.options[0]).toMatchObject({
      label: 'Featured Stories',
      value: 'featuredStories',
    });
    expect(
      container.querySelector('[data-option-row="0"] [data-option-label]')
    ).toBe(firstLabel);

    const firstValue = container.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-option-row="0"] [data-option-value]')!;
    firstValue.value = 'featured';
    firstValue.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(values.settings.options[0]?.value).toBe('featured');

    const currentFirstLabel = container.querySelector<
      HTMLElementTagNameMap['craft-input']
    >('[data-option-row="0"] [data-option-label]')!;
    currentFirstLabel.value = 'Top Stories';
    currentFirstLabel.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(values.settings.options[0]).toMatchObject({
      label: 'Top Stories',
      value: 'featured',
    });

    container
      .querySelector<HTMLElement>('[data-option-row="2"] craft-reorder-button')!
      .dispatchEvent(
        new CustomEvent('reorder', {
          bubbles: true,
          detail: {direction: 'up'},
        })
      );
    await nextTick();

    expect(
      values.settings.options.map((option) => option.label ?? option.optgroup)
    ).toEqual(['Top Stories', 'Last Choice', 'Archived']);

    const secondDefault = container.querySelector<CraftCheckbox>(
      '[data-option-row="1"] [data-option-default]'
    )!;
    secondDefault.checked = true;
    secondDefault.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(values.settings.options.map((option) => option.default)).toEqual([
      true,
      true,
      undefined,
    ]);
  });

  it('honors generic read-only state for option rows and lightswitches', () => {
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

    for (const control of container.querySelectorAll<HTMLElement>(
      'craft-input, craft-input-color'
    )) {
      expect(
        (control as {readOnly?: boolean}).readOnly ||
          (control as {disabled?: boolean}).disabled
      ).toBe(true);
    }

    expect(
      Array.from(
        container.querySelectorAll<HTMLElement>(
          'craft-checkbox, craft-button, craft-reorder-button, craft-switch'
        )
      )
        .filter((control) => !(control as {disabled?: boolean}).disabled)
        .map((control) => control.outerHTML)
    ).toEqual([]);
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
    const label = container.querySelector<HTMLElementTagNameMap['craft-input']>(
      '[data-option-label]'
    )!;

    label.value = '24 Hours';
    label.dispatchEvent(new Event('input', {bubbles: true}));
    await nextTick();

    expect(values.settings).toEqual({
      options: [{label: '24 Hours', value: '24Hours', default: false}],
    });
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

  registry.register('form-element:craft:option-rows', OptionRowsRenderer);
  registry.register(
    'form-element:craft:lightswitch-input',
    LightswitchInputRenderer
  );
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
    container.querySelectorAll<HTMLElement>(`[data-option-${name}]`),
    (control) =>
      String(
        'value' in control
          ? control.value
          : control.querySelector<HTMLElementTagNameMap['craft-input']>(
              'craft-input'
            )?.value
      )
  );
}
