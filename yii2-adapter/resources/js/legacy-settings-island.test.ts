import {createApp, defineComponent, h, nextTick, onMounted, ref} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormDefinitionRenderer from '@/form-definitions/FormDefinitionRenderer.vue';

const mountedApps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  mountedApps.splice(0).forEach((app) => app.unmount());
  document.head.querySelectorAll('[data-legacy-asset]').forEach((element) => {
    element.remove();
  });
  document.body.innerHTML = '';
  delete (window as any).legacyMountOrder;
  delete (window as any).Cp;
  vi.restoreAllMocks();
});

describe('Legacy Settings Island', () => {
  it('waits for the CP component registry before registering its renderer', async () => {
    (window as any).Cp = {};
    vi.resetModules();

    await expect(
      import('../../legacy/web/assets/cpcompat/legacy-settings-island.js')
    ).resolves.toBeDefined();

    const registry = createCpComponentRegistry();

    (window as any).Cp = {$components: registry};

    expect(registry.resolve('form-element:yii2-adapter:legacy-settings')).toBe(
      'craft-legacy-settings-island'
    );
  });

  it('fails loudly when the adapter renderer is unavailable', () => {
    (window as any).Cp = {$components: createCpComponentRegistry()};

    const container = document.createElement('div');
    const app = createApp(FormDefinitionRenderer, {
      definition: islandDefinition(fragment('Unavailable')),
      bindingScope: 'settings',
      values: {},
      errors: {},
    });

    expect(() => app.mount(container)).toThrow(
      'Missing Form Element Renderer for yii2-adapter:legacy-settings.'
    );
  });

  it('mounts a fragment configured by server-rendered component HTML', async () => {
    (window as any).Cp = {$components: createCpComponentRegistry()};
    (window as any).Craft = {initUiElements: vi.fn()};
    vi.resetModules();
    await import('../../legacy/web/assets/cpcompat/legacy-settings-island.js');

    const island = document.createElement('craft-legacy-settings-island');
    island.dataset.fragment = JSON.stringify(fragment('Server rendered'));
    document.body.appendChild(island);

    await vi.waitFor(() => {
      expect(
        island.querySelector<HTMLInputElement>('[name="settings[label]"]')
          ?.value
      ).toBe('Server rendered');
    });
    expect((window as any).Craft.initUiElements).toHaveBeenCalledWith(island);
  });

  it('mounts assets and the fragment in order before initializing legacy UI', async () => {
    const appendChild = Node.prototype.appendChild;

    vi.spyOn(Node.prototype, 'appendChild').mockImplementation(function (node) {
      if (node instanceof HTMLElement && node.dataset.legacyOrder) {
        ((window as any).legacyMountOrder ??= []).push(
          node.dataset.legacyOrder
        );
      }

      return appendChild.call(this, node) as any;
    });

    const {container} = await mountIsland({
      headHtml: '<meta data-legacy-asset data-legacy-order="head">',
      html: '<input name="settings[label]" value="Original"><span data-legacy-order="fragment"></span>',
      bodyHtml: '<div data-legacy-asset data-legacy-order="body"></div>',
    });

    await vi.waitFor(() => {
      expect((window as any).legacyMountOrder).toEqual([
        'head',
        'fragment',
        'body',
        'init',
      ]);
    });

    expect(container.querySelector('[name="settings[label]"]')).not.toBeNull();
  });

  it('serializes live controls before replacement and submission', async () => {
    const {container, definition} = await mountIsland(fragment('Original'));
    const form = container.closest('form')!;
    const input = container.querySelector<HTMLInputElement>(
      '[name="settings[label]"]'
    )!;
    const enabled = container.querySelector<HTMLInputElement>(
      '[name="settings[enabled]"]'
    )!;
    const serialized = vi.fn();

    container.addEventListener('legacy-settings:serialized', serialized);
    input.value = 'Edited';
    enabled.checked = false;
    definition.value = islandDefinition({
      ...fragment('Server value'),
      html: `${fragment('Server value').html}<input name="settings[new]">`,
    });
    await vi.waitFor(() => {
      expect(
        container.querySelector<HTMLInputElement>('[name="settings[new]"]')
      ).not.toBeNull();
    });

    expect(
      container.querySelector<HTMLInputElement>('[name="settings[label]"]')!
        .value
    ).toBe('Edited');
    expect(
      container.querySelector<HTMLInputElement>('[name="settings[enabled]"]')!
        .checked
    ).toBe(false);
    expect(serialized).toHaveBeenCalledWith(
      expect.objectContaining({
        detail: new URLSearchParams({'settings[label]': 'Edited'}),
      })
    );

    container.querySelector<HTMLInputElement>(
      '[name="settings[label]"]'
    )!.value = 'Submitted';
    form.dispatchEvent(new SubmitEvent('submit', {bubbles: true}));

    expect(serialized).toHaveBeenLastCalledWith(
      expect.objectContaining({
        detail: new URLSearchParams({
          'settings[label]': 'Submitted',
          'settings[new]': '',
        }),
      })
    );
  });

  it('preserves edits when replacement interrupts asynchronous asset mounting', async () => {
    const appendChild = document.body.appendChild;
    const pendingScripts: HTMLScriptElement[] = [];

    vi.spyOn(document.body, 'appendChild').mockImplementation(function (node) {
      if (
        node instanceof HTMLScriptElement &&
        node.src.endsWith('/slow-legacy-settings.js')
      ) {
        pendingScripts.push(node);

        return node;
      }

      return appendChild.call(this, node) as any;
    });

    const {container, definition} = await mountIsland({
      ...fragment('Original'),
      bodyHtml: '<script src="/slow-legacy-settings.js"></script>',
    });

    await vi.waitFor(() => {
      expect(
        container.querySelector<HTMLInputElement>('[name="settings[label]"]')
      ).not.toBeNull();
    });

    container.querySelector<HTMLInputElement>(
      '[name="settings[label]"]'
    )!.value = 'Edited while loading';
    definition.value = islandDefinition({
      ...fragment('Server value'),
      bodyHtml: '<script src="/slow-legacy-settings.js"></script>',
    });

    await nextTick();

    expect(pendingScripts).toHaveLength(1);
    expect((window as any).Craft.initUiElements).not.toHaveBeenCalled();
    pendingScripts[0]!.dispatchEvent(new Event('load'));

    await vi.waitFor(() => {
      expect(
        container.querySelector<HTMLInputElement>('[name="settings[label]"]')
          ?.value
      ).toBe('Edited while loading');
    });
    expect((window as any).Craft.initUiElements).toHaveBeenCalledOnce();
  });

  it('does not append an asset whose relative URL is already loaded', async () => {
    const existingAsset = document.createElement('link');

    existingAsset.rel = 'canonical';
    existingAsset.href = '/legacy-settings.css';
    existingAsset.dataset.legacyAsset = '';
    document.head.appendChild(existingAsset);

    await mountIsland({
      ...fragment('Original'),
      headHtml:
        '<link rel="canonical" href="/legacy-settings.css" data-legacy-asset>',
    });

    expect(
      document.head.querySelectorAll('link[href="/legacy-settings.css"]')
    ).toHaveLength(1);
  });

  it('preserves an unchanged keyed island and disposes its owned assets', async () => {
    const {app, container, definition} = await mountIsland({
      ...fragment('Original'),
      headHtml: '<style data-legacy-asset>.legacy { color: red; }</style>',
      bodyHtml: '<div data-legacy-asset data-legacy-body></div>',
    });
    const island = container.querySelector('craft-legacy-settings-island')!;
    const input = container.querySelector<HTMLInputElement>(
      '[name="settings[label]"]'
    )!;

    input.dataset.initializedState = 'preserved';
    definition.value = islandDefinition({
      ...fragment('Original'),
      headHtml: '<style data-legacy-asset>.legacy { color: red; }</style>',
      bodyHtml: '<div data-legacy-asset data-legacy-body></div>',
    });
    await nextTick();

    expect(container.querySelector('craft-legacy-settings-island')).toBe(
      island
    );
    expect(container.querySelector('[name="settings[label]"]')).toBe(input);
    expect(input.dataset.initializedState).toBe('preserved');

    app.unmount();
    mountedApps.pop();

    expect(document.querySelector('[data-legacy-asset]')).toBeNull();
  });

  it('preserves a selected mixed tab and mounts only inserted layout elements', async () => {
    const registry = createCpComponentRegistry();
    const mountedNativeInputs: string[] = [];
    const nativeInputRenderer = defineComponent({
      inheritAttrs: false,
      props: ['modelValue'],
      setup(props, {attrs}) {
        onMounted(() => mountedNativeInputs.push(String(attrs.name)));

        return () =>
          h('input', {
            ...attrs,
            value: props.modelValue,
            'data-mixed-native-input': attrs.name,
          });
      },
    });

    (window as any).Cp = {$components: registry};
    (window as any).Craft = {initUiElements: vi.fn()};
    vi.resetModules();
    await import('../../legacy/web/assets/cpcompat/legacy-settings-island.js');
    registry.register(
      'form-element:application:native-field-input',
      nativeInputRenderer
    );

    const definition = ref(mixedFieldLayoutDefinition(false));
    const container = document.createElement('div');
    const app = createApp({
      setup() {
        return () =>
          h(FormDefinitionRenderer, {
            definition: definition.value,
            bindingScope: 'entry',
            values: {entry: {fields: {body: 'Body', summary: ''}}},
            errors: {},
          });
      },
    });

    document.body.appendChild(container);
    mountedApps.push(app);
    app.mount(container);

    await vi.waitFor(() => {
      expect(
        container.querySelector('[name="entry[legacyRating]"]')
      ).not.toBeNull();
    });

    const bodyInput = container.querySelector(
      '[data-mixed-native-input="entry[fields][body]"]'
    );
    const island = container.querySelector('craft-legacy-settings-island');
    const legacyInput = container.querySelector('[name="entry[legacyRating]"]');

    definition.value = mixedFieldLayoutDefinition(true);
    await nextTick();

    expect(
      container.querySelector('[role="tab"][aria-selected="true"]')?.textContent
    ).toContain('Content');
    expect(
      container.querySelector(
        '[data-mixed-native-input="entry[fields][body]"]'
      )
    ).toBe(bodyInput);
    expect(container.querySelector('craft-legacy-settings-island')).toBe(
      island
    );
    expect(container.querySelector('[name="entry[legacyRating]"]')).toBe(
      legacyInput
    );
    expect(mountedNativeInputs).toEqual([
      'entry[fields][body]',
      'entry[fields][summary]',
    ]);
  });
});

async function mountIsland(fragmentConfig: ReturnType<typeof fragment>) {
  const registry = createCpComponentRegistry();

  (window as any).Cp = {$components: registry};
  (window as any).Craft = {
    initUiElements: vi.fn(() => {
      ((window as any).legacyMountOrder ??= []).push('init');
    }),
  };
  vi.resetModules();
  await import('../../legacy/web/assets/cpcompat/legacy-settings-island.js');

  const definition = ref(islandDefinition(fragmentConfig));
  const host = document.createElement('form');
  const container = document.createElement('div');
  const app = createApp({
    setup() {
      return () =>
        h(FormDefinitionRenderer, {
          definition: definition.value,
          bindingScope: 'settings',
          values: {},
          errors: {},
        });
    },
  });

  host.appendChild(container);
  document.body.appendChild(host);
  mountedApps.push(app);
  app.mount(container);
  await nextTick();

  return {app, container, definition};
}

function fragment(value: string) {
  return {
    headHtml: '',
    html: `<input name="settings[label]" value="${value}"><input type="checkbox" name="settings[enabled]" value="1" checked>`,
    bodyHtml: '',
  };
}

function islandDefinition(fragmentConfig: ReturnType<typeof fragment>) {
  return {
    elements: [
      {
        type: 'yii2-adapter:legacy-settings',
        key: 'legacy-settings',
        props: {fragment: fragmentConfig},
      },
    ],
  } satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;
}

function mixedFieldLayoutDefinition(refreshed: boolean) {
  function nativeField(
    key: string,
    name: string
  ): CraftCms.Cms.Cp.FormDefinitions.Data.FormElementData {
    return {
      type: 'craft:field',
      key,
      props: {label: name},
      children: [
        {
          type: 'application:native-field-input',
          name,
        },
      ],
    };
  }

  const contentTab: CraftCms.Cms.Cp.FormDefinitions.Data.FormElementData = {
    type: 'craft:tab',
    key: 'content-tab',
    props: {label: 'Content'},
    children: [
      ...(refreshed
        ? [nativeField('summary-layout-element', 'fields.summary')]
        : []),
      nativeField('body-layout-element', 'fields.body'),
      {
        type: 'yii2-adapter:legacy-settings',
        key: 'legacy-layout-element',
        props: {
          fragment: {
            html: '<input name="entry[legacyRating]">',
            headHtml: '',
            bodyHtml: '',
          },
        },
      },
    ],
  };

  return {
    elements: [
      {
        type: 'craft:tabs',
        key: 'mixed-layout',
        children: [
          ...(refreshed
            ? [
                {
                  type: 'craft:tab',
                  key: 'general-tab',
                  props: {label: 'General'},
                  children: [],
                } satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormElementData,
              ]
            : []),
          contentTab,
        ],
      },
    ],
  } satisfies CraftCms.Cms.Cp.FormDefinitions.Data.FormDefinitionData;
}
