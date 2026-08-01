import {createApp, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import CheckboxSelectInputRenderer from './renderers/CheckboxSelectInputRenderer.vue';
import ElementConditionInputRenderer from './renderers/ElementConditionInputRenderer.vue';
import LightswitchInputRenderer from './renderers/LightswitchInputRenderer.vue';
import TextInputRenderer from './renderers/TextInputRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];
type CheckboxGroupElement = HTMLElement & {
  registrationComplete: Promise<unknown>;
  updateComplete: Promise<unknown>;
};

beforeEach(() => {
  vi.spyOn(globalThis, 'fetch').mockImplementation((_input, init) => {
    const request = init?.body as FormData;
    const postedValue = request.get(
      'settings[selectionCondition][conditionRules][0][value]'
    );
    const value = typeof postedValue === 'string' ? postedValue : '';

    return Promise.resolve(
      new Response(conditionBuilderHtml(value), {
        headers: {'Content-Type': 'text/html'},
      })
    );
  });
});

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
  vi.restoreAllMocks();
});

describe('relational settings renderers', () => {
  it('updates source and condition values while visibility preserves hidden settings', async () => {
    const values = reactive({
      settings: {
        restrictLocation: false,
        sources: '*' as string | string[],
        restrictedLocationSubpath: 'articles/{slug}',
        selectionCondition: {
          class: 'AssetCondition',
          conditionRules: [{class: 'TitleRule', value: 'Original'}],
        },
      },
    });
    const {container, hostForm} = mount(values);
    const checkboxes = container.querySelectorAll<HTMLInputElement>(
      'input[type="checkbox"]'
    );
    const restrictedSubpath =
      container.querySelector<HTMLElementTagNameMap['craft-input']>(
        'craft-input'
      )!;
    const checkboxGroup = container.querySelector<CheckboxGroupElement>(
      'craft-checkbox-group'
    )!;

    await checkboxGroup.registrationComplete;
    await checkboxGroup.updateComplete;
    await vi.waitFor(() => {
      expect(
        container.querySelector('[data-condition-rule-value]')
      ).not.toBeNull();
    });
    const condition = container.querySelector<HTMLInputElement>(
      '[data-condition-rule-value]'
    )!;

    expect(container.querySelectorAll('craft-checkbox')).toHaveLength(3);
    expect(container.querySelector('craft-action-menu')).not.toBeNull();
    expect(container.querySelector('craft-button')).not.toBeNull();
    expect(condition.form).toBe(hostForm);
    const request = vi.mocked(fetch).mock.calls[0]![1]!.body as FormData;
    const postedConfig = request.get('config');

    expect(postedConfig).toBeTypeOf('string');
    expect(JSON.parse(postedConfig as string)).toMatchObject({
      class: 'AssetCondition',
      id: 'form-element-settings--selectionCondition',
      name: 'settings[selectionCondition]',
      forProjectConfig: true,
    });
    expect(Array.from(checkboxes, ({checked}) => checked)).toEqual([
      true,
      false,
      false,
    ]);
    expect(checkboxes[0]!.name).toBe('settings[sources][]');
    expect(
      restrictedSubpath.closest<HTMLElement>('[data-form-element]')!.style
        .display
    ).toBe('none');
    expect(condition.value).toBe('Original');

    checkboxes[0]!.checked = false;
    checkboxes[0]!.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();
    checkboxes[1]!.checked = true;
    checkboxes[1]!.dispatchEvent(new Event('change', {bubbles: true}));
    condition.value = 'Changed';
    condition.dispatchEvent(new Event('input', {bubbles: true}));
    await vi.waitFor(() => {
      expect(values.settings.selectionCondition).toMatchObject({
        conditionRules: [{value: 'Changed'}],
      });
    });

    expect(values.settings.sources).toEqual(['volume:images']);
    expect(values.settings.selectionCondition).toEqual({
      class: 'AssetCondition',
      config: '{}',
      conditionRules: [{class: 'TitleRule', value: 'Changed'}],
    });

    const lightswitch =
      container.querySelector<HTMLElementTagNameMap['craft-switch']>(
        'craft-switch'
      )!;
    lightswitch.checked = true;
    lightswitch.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(
      restrictedSubpath.closest<HTMLElement>('[data-form-element]')!.style
        .display
    ).toBe('');
    expect(values.settings.restrictedLocationSubpath).toBe('articles/{slug}');
  });

  it('cannot weaken the host read-only state', async () => {
    const values = reactive({
      settings: {
        restrictLocation: false,
        sources: ['volume:images'],
        restrictedLocationSubpath: 'articles',
        selectionCondition: {
          class: 'AssetCondition',
          conditionRules: [{class: 'TitleRule', value: 'Inspect me'}],
        },
      },
    });
    const {container} = mount(values, true);

    const checkboxes = Array.from(
      container.querySelectorAll<HTMLInputElement>('input[type="checkbox"]')
    );
    await vi.waitFor(() => {
      expect(
        container.querySelector('[data-condition-rule-value]')
      ).not.toBeNull();
    });
    const condition = container.querySelector<HTMLInputElement>(
      '[data-condition-rule-value]'
    )!;

    expect(checkboxes.map(({disabled}) => disabled)).toEqual([
      true,
      true,
      true,
    ]);
    expect(checkboxes.map(({checked}) => checked)).toEqual([
      false,
      true,
      false,
    ]);
    expect(condition.disabled).toBe(true);
    expect(condition.value).toBe('Inspect me');
    expect(
      container.querySelector<HTMLElement & {disabled: boolean}>(
        'craft-button'
      )!.disabled
    ).toBe(true);
    expect(
      container.querySelector<HTMLElementTagNameMap['craft-switch']>(
        'craft-switch'
      )!.disabled
    ).toBe(true);
  });
});

function mount(values: Record<string, unknown>, readOnly = false) {
  const registry = createCpComponentRegistry();
  const hostForm = document.createElement('form');
  const container = document.createElement('div');

  registry.register(
    'form-element:craft:checkbox-select-input',
    CheckboxSelectInputRenderer
  );
  registry.register(
    'form-element:craft:element-condition-input',
    ElementConditionInputRenderer
  );
  registry.register(
    'form-element:craft:lightswitch-input',
    LightswitchInputRenderer
  );
  registry.register('form-element:craft:text-input', TextInputRenderer);
  (window as any).Cp = {$components: registry};
  hostForm.appendChild(container);
  document.body.appendChild(hostForm);
  const app = createApp(FormDefinitionRenderer, {
    definition,
    bindingScope: 'settings',
    values,
    errors: {},
    readOnly,
  });

  mountedApps.push(app);
  app.mount(container);

  return {container, hostForm};
}

const definition = {
  elements: [
    field('craft:lightswitch-input', 'restrictLocation'),
    field('craft:text-input', 'restrictedLocationSubpath', undefined, {
      name: 'restrictLocation',
      operator: 'equals',
      value: true,
    }),
    field('craft:checkbox-select-input', 'sources', {
      options: [
        {label: 'All', value: '*'},
        {label: 'Images', value: 'volume:images'},
        {label: 'Documents', value: 'volume:documents'},
      ],
      allOption: '*',
    }),
    field('craft:element-condition-input', 'selectionCondition', {
      conditionClass: 'AssetCondition',
      builderConfig: {},
      sortable: true,
      addRuleLabel: 'Add a rule',
    }),
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

function field(
  type: string,
  name: string,
  props?: Record<string, CraftCms.Cms.Cp.FormDefinitions.Data.JsonValue>,
  visibleWhen?: CraftCms.Cms.Cp.FormDefinitions.Data.VisibilityConditionData
) {
  return {
    type: 'craft:field',
    children: [{type, name, props}],
    visibleWhen,
  };
}

function conditionBuilderHtml(value: string): string {
  return `
  <div class="condition-main">
    <input type="hidden" name="settings[selectionCondition][class]" value="AssetCondition">
    <input type="hidden" name="settings[selectionCondition][config]" value="{}">
    <fieldset class="condition-rule">
      <input type="hidden" name="settings[selectionCondition][conditionRules][1][class]" value="TitleRule">
      <craft-action-menu></craft-action-menu>
      <input data-condition-rule-value name="settings[selectionCondition][conditionRules][1][value]" value="${value}">
      <craft-button type="button">Remove</craft-button>
    </fieldset>
  </div>
`;
}
