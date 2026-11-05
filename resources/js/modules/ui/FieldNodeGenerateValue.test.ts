import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {stubUiRenderer} from './renderer/context.test-support';
import ActionMenuNode from './ActionMenuNode.vue';
import FieldNode from './FieldNode.vue';
import type {UiChange, UiNodePayload, UiValues} from './types';

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

  function mount(values: UiValues, onChange: (change: UiChange) => void) {
    app = createApp({
      setup() {
        return () =>
          h(FieldNode, {
            node,
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
      cp: {displayNotice: vi.fn(), displayError: vi.fn()},
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
    expect(window.Craft.cp!.displayNotice).toHaveBeenCalledWith(
      'Alt text generated.'
    );
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
      expect(window.Craft.cp!.displayError).toHaveBeenCalledWith(
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
});
