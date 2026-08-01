import {createApp, defineComponent, h, nextTick, reactive} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import '@craftcms/ui/components/input/input';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import TextInputRenderer from './renderers/TextInputRenderer.vue';
import type {FormDefinitionData} from './types';
import architectureFixture from './fixtures/architecture-acceptance.json';

const fixture = {
  ...architectureFixture,
  conditionalVisibility: {
    ...architectureFixture.conditionalVisibility,
    elements: architectureFixture.conditionalVisibility.elements.map(
      (element) => ({
        ...element,
        visibleWhen: element.visibleWhen
          ? {...element.visibleWhen, operator: 'equals' as const}
          : undefined,
      })
    ),
  },
} satisfies Record<
  'ordinary' | 'conditionalVisibility' | 'plugin' | 'fieldLayout',
  FormDefinitionData
>;
const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

it('type-checks and renders the shared architecture fixture', async () => {
  const registry = createCpComponentRegistry();
  const values = reactive({
    settings: {
      title: 'Architecture',
      mode: 'advanced',
      details: 'Visible details',
      palette: 'sunset',
      fields: {
        title: 'Projected title',
        summary: 'Projected summary',
      },
    },
  });
  const pluginRenderer = defineComponent({
    props: ['config', 'binding', 'attributes'],
    emits: ['update:value'],
    setup(props, {emit}) {
      return () =>
        h(
          'button',
          {
            'data-plugin-renderer': '',
            onClick: () => emit('update:value', 'ocean'),
          },
          `${props.config.colors.join(',')}:${props.binding.value}`
        );
    },
  });
  const container = document.createElement('div');
  const host = defineComponent({
    setup() {
      return () =>
        h(
          'div',
          Object.entries(fixture).map(([name, definition]) =>
            h(
              'section',
              {'data-acceptance-case': name},
              h(FormDefinitionRenderer, {
                definition,
                bindingScope: 'settings',
                values,
                errors: {},
              })
            )
          )
        );
    },
  });

  registry.register('form-element:craft:text-input', TextInputRenderer);
  registry.register('form-element:color-tools:color-map', pluginRenderer);
  (window as any).Cp = {$components: registry};
  document.body.appendChild(container);

  const app = createApp(host);
  mountedApps.push(app);
  app.mount(container);

  expect(
    Array.from(container.querySelectorAll('craft-input'), (input) => input.name)
  ).toEqual([
    'settings[title]',
    'settings[mode]',
    'settings[details]',
    'settings[fields][title]',
    'settings[fields][summary]',
  ]);
  expect(
    Array.from(
      container.querySelectorAll<HTMLElementTagNameMap['craft-input']>(
        '[data-form-tab-panel] craft-input'
      ),
      (input) => input.name
    )
  ).toEqual(['settings[fields][title]', 'settings[fields][summary]']);

  const plugin = container.querySelector<HTMLButtonElement>(
    '[data-plugin-renderer]'
  )!;
  expect(plugin.textContent).toBe('red,blue:sunset');
  plugin.click();
  await nextTick();
  expect(values.settings.palette).toBe('ocean');

  const details = container
    .querySelector('craft-input[name="settings[details]"]')!
    .closest<HTMLElement>('[data-form-element="craft:field"]')!;
  values.settings.mode = 'simple';
  await nextTick();
  expect(details.style.display).toBe('none');
  expect(values.settings.details).toBe('Visible details');
});
