import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {stubUiRenderer} from './renderer/context.test-support';
import ActionMenuNode from './ActionMenuNode.vue';
import FieldNode from './FieldNode.vue';
import type {UiChange, UiNodePayload, UiValues} from './types';

const messages = vi.hoisted(() => ({
  showError: vi.fn(),
  showNotice: vi.fn(),
  showSuccess: vi.fn(),
}));
vi.mock('@/modules/messages/messages', async (importOriginal) => ({
  ...(await importOriginal<object>()),
  ...messages,
}));
beforeEach(() => {
  Object.values(messages).forEach((fn) => fn.mockReset());
  // `craft-icon`s fetch their SVGs; left in flight they're aborted at
  // teardown and reported as unhandled errors.
  vi.stubGlobal(
    'fetch',
    vi.fn(async () => new Response('<svg></svg>'))
  );
});
afterEach(() => {
  vi.unstubAllGlobals();
});

const TextControl = defineComponent({
  props: ['value'],
  render() {
    return h('textarea', {value: this.value, 'data-text-control': ''});
  },
});

describe('FieldNode generated values', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const post = vi.spyOn(actionClient, 'post');

  const node = {
    type: 'Field',
    component: 'craft:field',
    props: {label: 'Alternative Text', layoutUid: 'alt-uid'},
    control: {
      type: 'Textarea',
      component: 'craft:textarea',
      path: ['editor', 'alt'],
      deltaGroup: ['editor', 'alt'],
      mode: 'editable',
      props: {},
      writable: true,
    },
    children: [
      {
        type: 'ActionMenu',
        component: 'craft:action-menu',
        uid: 'field-actions:editor.alt:menu',
        props: {
          label: 'Field actions',
          items: [
            {
              type: 'button',
              label: 'Generate alt text',
              action: {
                type: 'event',
                name: 'craft:generate-field-value',
                detail: {
                  url: '/actions/assets/generate-alt-text',
                  params: {assetId: 7, siteId: 1},
                },
              },
            },
          ],
        },
      },
    ],
  } as UiNodePayload;

  function mount(
    values: UiValues,
    onChange: (change: UiChange) => void,
    fieldNode: UiNodePayload = node
  ) {
    app = createApp({
      setup() {
        return () =>
          h(FieldNode, {
            node: fieldNode,
            values,
            errors: [],
            touchedPaths: new Set<string>(),
            scope: ['editor'],
            refreshable: false,
            onChange,
          });
      },
    });
    app.component('craft:textarea', TextControl);
    app.component('craft:action-menu', ActionMenuNode);
    app.provide(...stubUiRenderer(values));
    app.mount(container);
  }

  async function clickGenerate() {
    await vi.waitFor(() => {
      expect(
        [...container.querySelectorAll('craft-action-item')].some(
          (item) => item.textContent?.trim() === 'Generate alt text'
        )
      ).toBe(true);
    });
    [...container.querySelectorAll<HTMLElement>('craft-action-item')]
      .find((item) => item.textContent?.trim() === 'Generate alt text')!
      .click();
  }

  beforeEach(() => {
    post.mockReset();
    container = document.createElement('div');
    document.body.append(container);
    window.Craft = {
      cp: {},
    } as never;
  });

  afterEach(() => {
    app?.unmount();
    container.remove();
    delete (window as {Craft?: unknown}).Craft;
  });

  it('fills the field with the generated value without saving it', async () => {
    const values = reactive<UiValues>({editor: {alt: ''}});
    const changed = vi.fn<(change: UiChange) => void>();
    post.mockResolvedValue({
      data: {value: 'A red square', message: 'Alt text generated.'},
    });
    mount(values, changed);

    await clickGenerate();

    await vi.waitFor(() => expect(post).toHaveBeenCalledOnce());
    expect(post).toHaveBeenCalledWith('/actions/assets/generate-alt-text', {
      assetId: 7,
      siteId: 1,
    });
    await nextTick();

    expect(
      container.querySelector<HTMLTextAreaElement>('[data-text-control]')!.value
    ).toBe('A red square');
    expect(changed).toHaveBeenCalledOnce();
    expect(
      container.querySelector('craft-field')!.getAttribute('aria-busy')
    ).toBeNull();
    expect(messages.showNotice).toHaveBeenCalledWith('Alt text generated.');
  });

  it('marks the field busy while generating, and reports errors', async () => {
    let reject!: (error: unknown) => void;
    post.mockReturnValue(
      new Promise((_resolve, rejectPromise) => {
        reject = rejectPromise;
      }) as never
    );
    mount(reactive<UiValues>({editor: {alt: 'Kept'}}), vi.fn());

    await clickGenerate();

    await vi.waitFor(() =>
      expect(
        container.querySelector('craft-field')!.getAttribute('aria-busy')
      ).toBe('true')
    );
    reject(new Error('Network down'));

    await vi.waitFor(() =>
      expect(messages.showError).toHaveBeenCalledWith(
        'Couldn’t generate a value for this field.'
      )
    );
    expect(
      container.querySelector<HTMLTextAreaElement>('[data-text-control]')!.value
    ).toBe('Kept');
    expect(
      container.querySelector('craft-field')!.getAttribute('aria-busy')
    ).toBeNull();
  });

  it('offers no generated value to a field whose control can’t be given one', async () => {
    const values = reactive<UiValues>({editor: {alt: 'Kept'}});
    const changed = vi.fn<(change: UiChange) => void>();
    mount(values, changed, {
      ...node,
      control: {...node.control!, writable: undefined},
    });
    await nextTick();

    expect(container.querySelector('craft-action-menu')).toBeNull();

    window.dispatchEvent(
      new CustomEvent('craft:generate-field-value', {
        detail: {
          trigger: container.querySelector('[data-text-control]'),
          url: '/actions/assets/generate-alt-text',
          params: {},
        },
      })
    );
    await nextTick();

    expect(post).not.toHaveBeenCalled();
    expect(changed).not.toHaveBeenCalled();
  });
});
