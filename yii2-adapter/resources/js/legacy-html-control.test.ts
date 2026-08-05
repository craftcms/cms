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
  it('mounts head, HTML, and body in order and emits flat value mutations', async () => {
    const appendChild = Node.prototype.appendChild;

    vi.spyOn(Node.prototype, 'appendChild').mockImplementation(function (node) {
      if (node instanceof HTMLElement && node.dataset.legacyOrder) {
        ((window as any).legacyOrder ??= []).push(node.dataset.legacyOrder);
      }

      return appendChild.call(this, node) as Node;
    });

    const {container, mutation} = await mount({
      html: '<input name="settings[title]" value="Original"><span data-legacy-order="html"></span>',
      headHtml: '<meta data-legacy-order="head">',
      bodyHtml: '<span data-legacy-order="body"></span>',
    });
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
      __legacy: {'settings[title]': 'Edited'},
    });
  });

  it('reports asset failures and prevents submission', async () => {
    const appendChild = document.body.appendChild;
    let failedScript: HTMLScriptElement | undefined;

    vi.spyOn(document.body, 'appendChild').mockImplementation(function (node) {
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
    const {container} = await mount({
      html: '<input name="settings[title]" value="Captured" disabled>',
      headHtml: '',
      bodyHtml: '',
    });

    expect(
      container.querySelector<HTMLInputElement>('[name="settings[title]"]')
        ?.value
    ).toBe('Captured');
  });
});

async function mount(fragment: {
  html: string;
  headHtml: string;
  bodyHtml: string;
}) {
  const registry = createCpComponentRegistry();
  const mutation = ref<Record<string, unknown>>({});
  const payload: FormPayload = {
    scope: [],
    refreshable: false,
    nodes: [
      {
        type: 'CraftCms\\Yii2Adapter\\Form\\Nodes\\LegacyHtmlField',
        component: 'craft-legacy:html-field',
        props: {},
        control: {
          type: 'CraftCms\\Yii2Adapter\\Form\\Controls\\LegacyHtmlControl',
          component: 'craft-legacy:html',
          props: {fragment, namespace: 'settings'},
          path: ['__legacy'],
          mode: 'editable',
          deltaGroup: ['__legacy'],
        },
      },
    ],
    values: {__legacy: {'settings[title]': 'Original'}},
    errors: [],
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
