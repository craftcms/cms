import {
  createApp,
  defineComponent,
  h,
  nextTick,
  reactive,
  ref,
  toRaw,
} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from '../FormDefinitionRenderer.vue';
import ElementConditionInputRenderer from './ElementConditionInputRenderer.vue';

const conditionClass = 'craft\\elements\\conditions\\ElementCondition';
const mountedApplications: Array<ReturnType<typeof createApp>> = [];

beforeEach(() => {
  vi.stubGlobal(
    'fetch',
    vi.fn().mockResolvedValue(
      new Response(`
        <template class="hx-head-html"><meta data-condition-asset="head-first"></template>
        <template class="hx-head-html"><meta data-condition-asset="head-second"></template>
        <template class="hx-body-html"><div data-condition-asset="body-first"></div></template>
        <template class="hx-body-html"><div data-condition-asset="body-second"></div></template>
        <div data-condition-builder>
          <input type="hidden" name="settings[condition][class]" value="${conditionClass}">
          <input type="hidden" name="settings[condition][conditionRules][0][type]" value="title">
        </div>
      `)
    )
  );
  (window as any).Craft = {
    _actionHeaders: () => ({'X-CSRF-Token': 'token'}),
    initUiElements: vi.fn(),
  };
  (window as any).htmx = {process: vi.fn()};
});

afterEach(() => {
  vi.unstubAllGlobals();
  mountedApplications.splice(0).forEach((application) => application.unmount());
  document.head
    .querySelectorAll('[data-condition-asset]')
    .forEach((asset) => asset.remove());
  document.body.innerHTML = '';
  delete (window as any).Craft;
  delete (window as any).htmx;
});

describe('Element Condition Form Elements', () => {
  it('requests and initializes remote HTML before synchronizing later DOM changes', async () => {
    const initial = {
      class: conditionClass,
      conditionRules: [{type: 'title'}],
    };
    const values = reactive({settings: {condition: initial}});
    const readOnly = ref(false);
    const form = mount(values, readOnly);

    await vi.waitFor(() => {
      expect(form.querySelector('[data-condition-builder]')).not.toBeNull();
    });

    const [, options = {}] = vi.mocked(fetch).mock.calls.at(0)!;
    const body = options.body as FormData;
    const config = JSON.parse(String(body.get('config')));
    const container = form.querySelector<
      HTMLElementTagNameMap['craft-element-condition']
    >('craft-element-condition')!;

    expect(options.method).toBe('POST');
    expect(options.headers).toMatchObject({
      Accept: 'text/html',
      'HX-Request': 'true',
      'X-CSRF-Token': 'token',
    });
    expect(config).toMatchObject({
      class: conditionClass,
      id: 'form-element-settings--condition',
      name: 'settings[condition]',
      mainTag: 'div',
      sortable: true,
      forProjectConfig: true,
      addRuleLabel: 'Add a condition',
      elementType: 'craft\\elements\\Entry',
    });
    expect(body.get('settings[condition][class]')).toBe(conditionClass);
    expect(body.get('settings[condition][conditionRules][0][type]')).toBe(
      'title'
    );
    expect(
      Array.from(
        document.head.querySelectorAll<HTMLElement>('[data-condition-asset]'),
        ({dataset}) => dataset.conditionAsset
      )
    ).toEqual(['head-first', 'head-second']);
    expect(
      Array.from(
        document.body.querySelectorAll<HTMLElement>(
          ':scope > [data-condition-asset]'
        ),
        ({dataset}) => dataset.conditionAsset
      )
    ).toEqual(['body-first', 'body-second']);
    expect(
      vi.mocked((window as any).Craft.initUiElements).mock.lastCall
    ).toEqual([container]);
    expect(vi.mocked((window as any).htmx.process).mock.lastCall).toEqual([
      container,
    ]);
    expect(toRaw(values.settings.condition)).toBe(initial);

    const secondRule = document.createElement('input');
    secondRule.type = 'hidden';
    secondRule.name = 'settings[condition][conditionRules][1][type]';
    secondRule.value = 'slug';
    form.querySelector('[data-condition-builder]')!.append(secondRule);

    await vi.waitFor(() => {
      expect(values.settings.condition.conditionRules).toEqual([
        {type: 'title'},
        {type: 'slug'},
      ]);
    });

    readOnly.value = true;
    await nextTick();
    await container.updateComplete;
    const thirdRule = document.createElement('input');
    thirdRule.type = 'hidden';
    thirdRule.name = 'settings[condition][conditionRules][2][type]';
    thirdRule.value = 'uri';
    form.querySelector('[data-condition-builder]')!.append(thirdRule);
    container.dispatchEvent(new Event('input', {bubbles: true}));
    container.dispatchEvent(new Event('change', {bubbles: true}));
    await new Promise<void>((resolve) => queueMicrotask(resolve));
    await nextTick();

    expect(container.readOnly).toBe(true);
    expect(values.settings.condition.conditionRules).toEqual([
      {type: 'title'},
      {type: 'slug'},
    ]);
  });
});

function mount(
  values: Record<string, unknown>,
  readOnly: {value: boolean}
): HTMLFormElement {
  const registry = createCpComponentRegistry();
  const form = document.createElement('form');
  const host = defineComponent({
    setup() {
      return () =>
        h(FormDefinitionRenderer, {
          definition: {
            elements: [
              {
                type: 'craft:element-condition-input',
                name: 'condition',
                props: {
                  builderConfig: {elementType: 'craft\\elements\\Entry'},
                  conditionClass,
                  addRuleLabel: 'Add a condition',
                },
              },
            ],
          },
          bindingScope: 'settings',
          values,
          errors: {},
          readOnly: readOnly.value,
        });
    },
  });

  registry.register(
    'form-element:craft:element-condition-input',
    ElementConditionInputRenderer
  );
  (window as any).Cp = {$components: registry};
  document.body.appendChild(form);
  const application = createApp(host);

  mountedApplications.push(application);
  application.mount(form);

  return form;
}
