import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {FormFailure} from './runtime';
import ActionMenuNode from './ActionMenuNode.vue';
import FieldNode from './FieldNode.vue';
import type {FormChange, FormNodePayload, FormValues} from './types';

const TextControl = defineComponent({
  props: ['value'],
  render() {
    return h('input', {value: this.value, 'data-text-control': ''});
  },
});

describe('FieldNode cross-site copying', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const post = vi.spyOn(actionClient, 'post');

  const node = {
    type: 'Field',
    component: 'craft:field',
    props: {label: 'Summary', layoutUid: 'layout-uid'},
    control: {
      type: 'Text',
      component: 'craft:text',
      path: ['editor', 'fields', 'summary'],
      deltaGroup: ['editor', 'fields', 'summary'],
      mode: 'editable',
      props: {},
    },
    children: [
      {
        type: 'ActionMenu',
        component: 'craft:action-menu',
        uid: 'field-actions:editor.fields.summary:menu',
        props: {
          label: 'Field actions',
          items: [
            {
              type: 'button',
              label: 'Copy value from site…',
              action: {
                type: 'event',
                name: 'craft:copy-value-from-site',
                detail: {
                  elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
                  elementId: 42,
                  draftId: null,
                  siteId: 1,
                  layoutElementUid: 'layout-uid',
                  label: 'Summary',
                  siteIds: [2],
                },
              },
            },
          ],
        },
      },
    ],
  } as FormNodePayload;

  beforeEach(() => {
    post.mockReset();
    container = document.createElement('div');
    document.body.append(container);
    window.Craft = {
      sites: [
        {id: 1, uid: 'primary', handle: 'primary', name: 'Primary'},
        {id: 2, uid: 'secondary', handle: 'secondary', name: 'Secondary'},
      ],
      cp: {
        displayNotice: vi.fn(),
        displayError: vi.fn(),
      },
    } as never;
  });

  afterEach(() => {
    app?.unmount();
    container.remove();
    delete (window as {Craft?: unknown}).Craft;
  });

  it('copies the selected site value into the field', async () => {
    const values = reactive<FormValues>({
      editor: {fields: {summary: 'Original'}},
    });
    const changed = vi.fn<(change: FormChange) => void>();
    post.mockResolvedValue({
      data: {
        message: 'Field value copied.',
        field: node,
        values: {editor: {fields: {summary: 'Copied'}}},
      },
    });

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
            onChange: changed,
          });
      },
    });
    app.component('craft:text', TextControl);
    app.component('craft:action-menu', ActionMenuNode);
    app.provide(FormFailure, vi.fn());
    app.mount(container);

    await vi.waitFor(() => {
      expect(
        [...container.querySelectorAll('craft-action-item')].some(
          (item) => item.textContent === 'Copy value from site…'
        )
      ).toBe(true);
    });
    [...container.querySelectorAll<HTMLElement>('craft-action-item')]
      .find((item) => item.textContent === 'Copy value from site…')!
      .click();

    await vi.waitFor(() => {
      expect(
        document.body.querySelector('[data-cross-site-copy-modal]')
      ).not.toBeNull();
    });
    expect(
      (
        document.body.querySelector('craft-pane') as HTMLElement & {
          label: string;
        }
      ).label
    ).toBe('Copy “Summary” value');
    const select = document.body.querySelector<HTMLSelectElement>(
      '[data-cross-site-copy-modal] select'
    )!;
    expect([...select.options].map((option) => option.textContent)).toEqual([
      'Secondary',
    ]);
    select.value = '2';
    select.dispatchEvent(new Event('change', {bubbles: true}));
    select.closest('form')!.requestSubmit();

    await vi.waitFor(() => expect(post).toHaveBeenCalledOnce());
    await nextTick();

    expect(
      container.querySelector<HTMLInputElement>('[data-text-control]')!.value
    ).toBe('Copied');
    expect(changed).toHaveBeenCalledOnce();
    expect(window.Craft.cp!.displayNotice).toHaveBeenCalledWith(
      'Field value copied.'
    );
  });
});
