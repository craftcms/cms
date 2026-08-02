import {createApp, nextTick, reactive} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import CraftSwitch from '@craftcms/ui/vue/CraftSwitch.vue';
import CraftCombobox from '@craftcms/ui/vue/CraftCombobox.vue';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from './FormDefinitionRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.body.innerHTML = '';
});

describe('filesystem settings renderer', () => {
  it('reacts to URL visibility without clearing host-owned values', async () => {
    const registry = createCpComponentRegistry();
    const values = reactive({
      types: {
        local: {
          hasUrls: false,
          url: 'https://assets.example.test',
          path: '@webroot/uploads',
        },
      },
    });
    const container = document.createElement('div');

    registry.register('form-element:craft:combobox-input', CraftCombobox);
    registry.register('form-element:craft:lightswitch-input', CraftSwitch);
    (window as any).Cp = {$components: registry};
    document.body.appendChild(container);

    const app = createApp(FormDefinitionRenderer, {
      definition: {
        elements: [
          field('craft:lightswitch-input', 'hasUrls'),
          {
            ...field('craft:combobox-input', 'url', {
              options: [],
              placeholder: '//example.com/path/to/folder',
            }),
            visibleWhen: {
              name: 'hasUrls',
              operator: 'equals',
              value: true,
            },
          },
          {
            ...field('craft:combobox-input', 'path', {options: []}),
            props: {
              tip: 'This can begin with an environment variable or alias.',
            },
          },
        ],
      },
      bindingScope: 'types.local',
      values,
      errors: {},
    });

    mountedApps.push(app);
    app.mount(container);
    await nextTick();

    const fields = container.querySelectorAll<HTMLElement>(
      '[data-form-element="craft:field"]'
    );
    const hasUrls =
      container.querySelector<HTMLElementTagNameMap['craft-switch']>(
        'craft-switch'
      )!;
    const [url, path] =
      container.querySelectorAll<HTMLElementTagNameMap['craft-combobox']>(
        'craft-combobox'
      );

    expect(fields[1]?.style.display).toBe('none');
    expect(url?.modelValue).toBe('https://assets.example.test');
    expect(path?.modelValue).toBe('@webroot/uploads');
    expect(container.textContent).toContain(
      'This can begin with an environment variable or alias.'
    );

    hasUrls.checked = true;
    hasUrls.dispatchEvent(new Event('change', {bubbles: true}));
    await nextTick();

    expect(fields[1]?.style.display).toBe('');
    expect(values.types.local.url).toBe('https://assets.example.test');

    path!.modelValue = '@storage/uploads';
    path!.dispatchEvent(
      new CustomEvent('model-value-changed', {
        bubbles: true,
        detail: {},
      })
    );
    await nextTick();

    expect(values.types.local.path).toBe('@storage/uploads');
  });
});

function field(
  type: string,
  name: string,
  props?: Record<string, CraftCms.Cms.Cp.FormDefinitions.Data.JsonValue>
) {
  return {
    type: 'craft:field',
    children: [{type, name, props}],
  };
}
