import {
  createApp,
  defineComponent,
  h,
  nextTick,
  onMounted,
  reactive,
  ref,
  shallowRef,
} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import '@craftcms/ui/components/input/input';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';
import TextInputRenderer from './renderers/TextInputRenderer.vue';
import type {FormDefinitionData} from './types';
import architectureFixture from './fixtures/architecture-acceptance.json';

type ArchitectureFixture = Record<
  'ordinary' | 'conditionalVisibility' | 'plugin' | 'fieldLayout',
  FormDefinitionData
>;

// JSON imports widen literal strings. Keep the imported object intact and lock
// the one discriminant that needs narrowing before using the wire type.
if (
  architectureFixture.conditionalVisibility.elements[1]?.visibleWhen
    ?.operator !== 'equals'
) {
  throw new Error('The shared visibility fixture must use equals.');
}

const fixture = architectureFixture as ArchitectureFixture;
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
        inserted: '',
        title: 'Projected title',
        summary: 'Projected summary',
      },
    },
  });
  const errors = {'settings.title': ['A title is required.']};
  const readOnly = ref(false);
  const fieldLayoutDefinition = shallowRef<FormDefinitionData>(
    fixture.fieldLayout
  );
  let legacyMounts = 0;
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
  const legacyRenderer = defineComponent({
    inheritAttrs: false,
    props: ['attributes', 'config'],
    setup() {
      onMounted(() => legacyMounts++);

      return () => h('div', {'data-legacy-island': ''}, 'Legacy rating');
    },
  });
  const container = document.createElement('div');
  const host = defineComponent({
    setup() {
      return () => {
        const definitions: Record<string, FormDefinitionData> = {
          ...fixture,
          fieldLayout: fieldLayoutDefinition.value,
        };

        return h(
          'div',
          Object.entries(definitions).map(([name, definition]) =>
            h(
              'section',
              {'data-acceptance-case': name},
              h(FormDefinitionRenderer, {
                definition,
                bindingScope: 'settings',
                values,
                errors,
                readOnly: readOnly.value,
              })
            )
          )
        );
      };
    },
  });

  registry.register('form-element:craft:text-input', TextInputRenderer);
  registry.register('form-element:color-tools:color-map', async () => ({
    default: pluginRenderer,
  }));
  registry.register('form-element:application:legacy-island', legacyRenderer);
  (window as any).Cp = {$components: registry};
  document.body.appendChild(container);

  const app = createApp(host);
  mountedApps.push(app);
  app.mount(container);

  const ordinary = container.querySelector<
    HTMLElementTagNameMap['craft-input']
  >('[data-acceptance-case="ordinary"] craft-input')!;
  ordinary.value = 'Updated architecture';
  ordinary.dispatchEvent(new Event('input', {bubbles: true}));
  await nextTick();

  expect(values.settings.title).toBe('Updated architecture');
  expect(
    container.querySelector(
      '[data-acceptance-case="ordinary"] [data-form-element-errors]'
    )?.textContent
  ).toContain('A title is required.');

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

  await vi.waitFor(() => {
    expect(container.querySelector('[data-plugin-renderer]')).not.toBeNull();
  });
  const plugin = container.querySelector<HTMLButtonElement>(
    '[data-plugin-renderer]'
  )!;
  expect(plugin.textContent).toBe('red,blue:sunset');
  plugin.click();
  await nextTick();
  expect(values.settings.palette).toBe('ocean');

  const projectedTitle = container.querySelector(
    'craft-input[name="settings[fields][title]"]'
  );
  const legacyIsland = container.querySelector('[data-legacy-island]');
  const tabs = fieldLayoutDefinition.value.elements[0]!;
  const contentTab = tabs.children![0]!;
  fieldLayoutDefinition.value = {
    elements: [
      {
        ...tabs,
        children: [
          {
            type: 'craft:tab',
            key: 'general-tab',
            props: {label: 'General'},
            children: [],
          },
          {
            ...contentTab,
            children: [
              {
                type: 'craft:field',
                key: 'inserted-layout-element',
                children: [{type: 'craft:text-input', name: 'fields.inserted'}],
              },
              ...contentTab.children!,
            ],
          },
        ],
      },
    ],
  };
  await nextTick();

  expect(
    container.querySelector('[role="tab"][aria-selected="true"]')?.textContent
  ).toContain('Content');
  expect(
    container.querySelector('craft-input[name="settings[fields][title]"]')
  ).toBe(projectedTitle);
  expect(container.querySelector('[data-legacy-island]')).toBe(legacyIsland);
  expect(legacyMounts).toBe(1);

  const details = container
    .querySelector('craft-input[name="settings[details]"]')!
    .closest<HTMLElement>('[data-form-element="craft:field"]')!;
  values.settings.mode = 'simple';
  await nextTick();
  expect(details.style.display).toBe('none');
  expect(values.settings.details).toBe('Visible details');

  readOnly.value = true;
  await nextTick();
  expect(ordinary.hasAttribute('readonly')).toBe(true);
});
