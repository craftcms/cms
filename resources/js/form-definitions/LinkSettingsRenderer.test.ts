import {createApp, nextTick, reactive} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import CraftCheckboxSelect from '@craftcms/ui/vue/CraftCheckboxSelect.vue';
import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
import '@craftcms/ui/components/checkbox-select/checkbox-select';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('Link settings renderer', () => {
  it('renders text and element link settings while preserving order and hidden values', async () => {
    const registry = createCpComponentRegistry();
    const values = reactive({
      settings: {
        types: ['url', 'entry'],
        typeSettings: {
          url: {allowAnchors: true},
          entry: {sources: null as string[] | null},
          asset: {allowedKinds: null as string[] | null},
        },
      },
    });
    const container = document.createElement('div');

    registry.register(
      'form-element:craft:checkbox-select-input',
      CraftCheckboxSelect
    );
    registry.register('form-element:craft:lightswitch-input', CraftSwitch);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);
    const app = createApp(FormDefinitionRenderer, {
      definition,
      bindingScope: 'settings',
      values,
      errors: {},
    });

    mountedApps.push(app);
    app.mount(container);
    const typeInputs = Array.from(
      container.querySelectorAll<HTMLInputElement>(
        'input[name="settings[types][]"]'
      )
    );
    const entrySources = Array.from(
      container.querySelectorAll<HTMLInputElement>(
        'input[name="settings[typeSettings][entry][sources][]"]'
      )
    );
    const urlGroup = container.querySelector<HTMLElement>(
      '[data-form-element="craft:group"]'
    )!;
    const assetGroup = Array.from(
      container.querySelectorAll<HTMLElement>(
        '[data-form-element="craft:group"]'
      )
    )[2]!;

    expect(typeInputs.map(({checked}) => checked)).toEqual([true, true, false]);
    expect(entrySources[0]!.checked).toBe(true);
    expect(urlGroup.style.display).toBe('');
    expect(assetGroup.style.display).toBe('none');

    const firstReorderButton = container.querySelector<HTMLElement>(
      'craft-reorder-button'
    )!;
    firstReorderButton.dispatchEvent(
      new CustomEvent('reorder', {detail: {direction: 'down'}, bubbles: true})
    );
    await nextTick();

    expect(values.settings.types).toEqual(['entry', 'url']);

    typeInputs[1]!.checked = false;
    typeInputs[1]!.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();
    typeInputs[1]!.checked = true;
    typeInputs[1]!.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(values.settings.types).toEqual(['entry', 'url']);

    typeInputs[0]!.checked = false;
    typeInputs[0]!.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(urlGroup.style.display).toBe('none');
    expect(values.settings.typeSettings.url.allowAnchors).toBe(true);

    typeInputs[2]!.checked = true;
    typeInputs[2]!.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(values.settings.types).toEqual(['entry', 'asset']);
    expect(assetGroup.style.display).toBe('');
    expect(values.settings.typeSettings.asset.allowedKinds).toBeNull();
  });
});

const definition = {
  elements: [
    field('craft:checkbox-select-input', 'types', {
      options: [
        {label: 'URL', value: 'url'},
        {label: 'Entry', value: 'entry'},
        {label: 'Asset', value: 'asset'},
      ],
      sortable: true,
    }),
    group('url', [
      field('craft:lightswitch-input', 'typeSettings.url.allowAnchors'),
    ]),
    group('entry', [
      field('craft:checkbox-select-input', 'typeSettings.entry.sources', {
        options: [
          {label: 'All', value: '*'},
          {label: 'News', value: 'section:news'},
        ],
        allOption: '*',
      }),
    ]),
    group('asset', [
      field('craft:checkbox-select-input', 'typeSettings.asset.allowedKinds', {
        options: [
          {label: 'All', value: '*'},
          {label: 'Images', value: 'image'},
        ],
        allOption: '*',
      }),
    ]),
  ],
} satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;

function field(
  type: string,
  name: string,
  props?: Record<string, CraftCms.Cms.Cp.FormDefinitions.Data.JsonValue>
) {
  return {type: 'craft:field', children: [{type, name, props}]};
}

function group(type: string, children: ReturnType<typeof field>[]) {
  return {
    type: 'craft:group',
    key: `link-type:${type}`,
    children,
    visibleWhen: {name: 'types', operator: 'contains', value: type} as const,
  };
}
