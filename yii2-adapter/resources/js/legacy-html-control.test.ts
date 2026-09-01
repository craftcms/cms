import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FormRenderer from '@/modules/forms/FormRenderer.vue';
import type {FormPayload} from '@/modules/forms/types';

const apps: Array<ReturnType<typeof createApp>> = [];

afterEach(() => {
  apps.splice(0).forEach((app) => app.unmount());
  document.head.innerHTML = '';
  document.body.innerHTML = '';
  delete (window as any).Cp;
  delete (window as any).Craft;
  delete (window as any).legacyOrder;
  vi.restoreAllMocks();
});

describe('Legacy HTML Form Control', () => {
  it('mounts assets in order and expands flat values before mutation', async () => {
    const appendChild = Node.prototype.appendChild;

    vi.spyOn(Node.prototype, 'appendChild').mockImplementation(function (
      this: Node,
      node
    ) {
      if (node instanceof HTMLElement && node.dataset.legacyOrder) {
        ((window as any).legacyOrder ??= []).push(node.dataset.legacyOrder);
      }

      return appendChild.call(this, node) as Node;
    });

    const {container, mutation} = await mount(
      {
        html: '<input name="settings[title]" value="Original"><input name="features[]" value="forms"><span data-legacy-order="html"></span>',
        headHtml: '<meta data-legacy-order="head">',
        bodyHtml: '<span data-legacy-order="body"></span>',
      },
      {
        values: {
          __legacy: {
            'settings[title]': 'Original',
            'features[]': 'forms',
          },
        },
      }
    );
    await vi.waitFor(() => {
      expect((window as any).legacyOrder).toEqual([
        'head',
        'html',
        'body',
        'init',
      ]);
    });

    const input = container.querySelector<HTMLInputElement>(
      '[name="settings[title]"]'
    )!;

    input.value = 'Edited';
    input.dispatchEvent(new InputEvent('input', {bubbles: true}));
    await nextTick();

    expect(mutation.value).toEqual({
      settings: {title: 'Edited'},
      features: ['forms'],
    });
  });

  it('reports asset failures and prevents submission', async () => {
    const appendChild = document.body.appendChild;
    let failedScript: HTMLScriptElement | undefined;

    vi.spyOn(document.body, 'appendChild').mockImplementation(function (
      this: Node,
      node
    ) {
      if (
        node instanceof HTMLScriptElement &&
        node.src.endsWith('/missing-legacy.js')
      ) {
        failedScript = node;

        return node;
      }

      return appendChild.call(this, node) as Node;
    });

    const {form, container} = await mount({
      html: '<input name="settings[title]" value="Original">',
      headHtml: '',
      bodyHtml: '<script src="/missing-legacy.js"></script>',
    });
    await vi.waitFor(() => expect(failedScript).toBeDefined());

    failedScript!.dispatchEvent(new Event('error'));

    await vi.waitFor(() => {
      expect(container.querySelector('[role="alert"]')?.textContent).toContain(
        '/missing-legacy.js'
      );
    });

    const submit = new SubmitEvent('submit', {bubbles: true, cancelable: true});
    form.dispatchEvent(submit);

    expect(submit.defaultPrevented).toBe(true);
  });

  it('matches PHP expansion for sparse keys and normalized roots', async () => {
    const {container, mutation} = await mount(
      {
        html: '<input name="a.b[c.d]" value="nested"><input name="items[2]" value="sparse">',
        headHtml: '',
        bodyHtml: '',
      },
      {
        values: {
          __legacy: {
            'a.b[c.d]': 'nested',
            'items[2]': 'sparse',
          },
        },
      }
    );

    container
      .querySelector<HTMLInputElement>('[name="items[2]"]')!
      .dispatchEvent(new InputEvent('input', {bubbles: true}));
    await nextTick();

    expect(mutation.value).toEqual({
      a_b: {'c.d': 'nested'},
      items: {'2': 'sparse'},
    });
  });

  it('preserves leading-zero keys like PHP', async () => {
    const {container, mutation} = await mount(
      {html: '<input name="codes[00]" value="leading">', headHtml: '', bodyHtml: ''},
      {values: {__legacy: {'codes[00]': 'leading'}}}
    );

    container
      .querySelector<HTMLInputElement>('input')!
      .dispatchEvent(new InputEvent('input', {bubbles: true}));
    await nextTick();

    expect(mutation.value).toEqual({codes: {'00': 'leading'}});
  });

  it('rejects unsafe legacy input paths', async () => {
    const {container, form} = await mount(
      {
        html: '<input name="__proto__[polluted]" value="yes">',
        headHtml: '',
        bodyHtml: '',
      },
      {values: {__legacy: {'__proto__[polluted]': 'yes'}}}
    );

    container
      .querySelector<HTMLInputElement>('input')!
      .dispatchEvent(new InputEvent('input', {bubbles: true}));
    await nextTick();

    const submit = new SubmitEvent('submit', {bubbles: true, cancelable: true});
    form.dispatchEvent(submit);

    expect(container.querySelector('[role="alert"]')?.textContent).toContain(
      'unsafe path'
    );
    expect(({} as {polluted?: string}).polluted).toBeUndefined();
    expect(submit.defaultPrevented).toBe(true);
  });

  it('reports a selected file and prevents submission without dropping it', async () => {
    const {form, container} = await mount({
      html: '<input type="file" name="settings[file]">',
      headHtml: '',
      bodyHtml: '',
    });
    const input = container.querySelector<HTMLInputElement>('input[type=file]')!;

    Object.defineProperty(input, 'files', {
      configurable: true,
      value: [new File(['content'], 'selected.txt')],
    });

    const submit = new SubmitEvent('submit', {bubbles: true, cancelable: true});
    form.dispatchEvent(submit);

    expect(submit.defaultPrevented).toBe(true);
    expect(container.querySelector('[role="alert"]')?.textContent).toContain(
      'selected.txt'
    );
  });

  it('preserves captured disabled values during hydration', async () => {
    const {container} = await mount(
      {
        html: '<input name="settings[title]" value="Captured">',
        headHtml: '',
        bodyHtml: '',
      },
      {mode: 'disabled', values: {__legacy: {}}}
    );

    await vi.waitFor(() => {
      const input = container.querySelector<HTMLInputElement>(
        '[name="settings[title]"]'
      );

      expect(input?.value).toBe('Captured');
      expect(input?.disabled).toBe(true);
    });
  });

  it('renders Form errors owned by a legacy input root', async () => {
    const {container} = await mount(
      {
        html: '<input name="settings[title]" value="Original">',
        headHtml: '',
        bodyHtml: '',
      },
      {
        errors: [{path: ['__legacy'], messages: ['Title is invalid.']}],
      }
    );

    expect(container.querySelector('[aria-invalid="true"]')).not.toBeNull();
    expect(
      container.querySelector('[data-legacy-form-errors]')?.textContent
    ).toContain('Title is invalid.');
  });

  it('refreshes through the shared Form scope protocol', async () => {
    vi.useFakeTimers();
    const refresh = vi.fn();
    const {container} = await mount(
      {
        html: '<input name="nested[block][settings][title]" value="Original">',
        headHtml: '',
        bodyHtml: '',
      },
      {
        refreshable: true,
        refresh,
        scope: ['nested', 'block'],
        path: ['nested', 'block', '__legacyFieldLayout', 'legacy-element'],
        namespace: 'nested[block]',
        values: {
          nested: {
            block: {
              __legacyFieldLayout: {
                'legacy-element': {
                  'nested[block][settings][title]': 'Original',
                },
              },
            },
          },
        },
      }
    );
    const input = container.querySelector<HTMLInputElement>(
      '[name="nested[block][settings][title]"]'
    )!;

    input.value = 'Edited';
    input.dispatchEvent(new InputEvent('input', {bubbles: true}));
    await nextTick();
    await vi.advanceTimersByTimeAsync(1000);

    expect(refresh).toHaveBeenCalledWith(
      {
        settings: {title: 'Edited'},
      },
      ['nested', 'block']
    );
    vi.useRealTimers();
  });
});

async function mount(
  fragment: {
    html: string;
    headHtml: string;
    bodyHtml: string;
  },
  options: {
    refreshable?: boolean;
    refresh?: (values: FormPayload['values'], scope?: string[]) => void;
    scope?: string[];
    path?: string[];
    namespace?: string;
    values?: FormPayload['values'];
    errors?: FormPayload['errors'];
    mode?: 'editable' | 'readOnly' | 'disabled';
  } = {}
) {
  const registry = createCpComponentRegistry();
  const mutation = ref<Record<string, unknown>>({});
  const payload: FormPayload = {
    scope: options.scope ?? [],
    refreshable: options.refreshable ?? false,
    nodes: [
      {
        type: 'CraftCms\\Yii2Adapter\\Form\\Nodes\\LegacyHtmlField',
        component: 'craft-legacy:html-field',
        props: {},
        control: {
          type: 'CraftCms\\Yii2Adapter\\Form\\Controls\\LegacyHtmlControl',
          component: 'craft-legacy:html',
          props: {
            fragment,
            namespace: options.namespace ?? 'settings',
            expandValues: true,
          },
          path: options.path ?? ['__legacy'],
          mode: options.mode ?? 'editable',
          deltaGroup: options.scope ?? [],
        },
      },
    ],
    values: options.values ?? {__legacy: {'settings[title]': 'Original'}},
    errors: options.errors ?? [],
    globalErrors: [],
  };

  (window as any).Cp = {$components: registry};
  (window as any).Craft = {
    initUiElements: vi.fn(() => {
      ((window as any).legacyOrder ??= []).push('init');
    }),
  };
  vi.resetModules();
  await import('../../../packages/craftcms-legacy/cpcompat/src/legacy-html-control.js');

  const form = document.createElement('form');
  const container = document.createElement('div');
  const app = createApp({
    setup() {
      return () =>
        h(FormRenderer, {
          payload,
          refresh: options.refresh
            ? async (values: FormPayload['values'], scope?: string[]) => {
                options.refresh!(values, scope);

                return payload;
              }
            : undefined,
          'onUpdate:mutation': (value: Record<string, unknown>) => {
            mutation.value = value;
          },
        });
    },
  });

  form.appendChild(container);
  document.body.appendChild(form);
  registry.install(app);
  apps.push(app);
  app.mount(container);
  await nextTick();

  return {app, container, form, mutation};
}
