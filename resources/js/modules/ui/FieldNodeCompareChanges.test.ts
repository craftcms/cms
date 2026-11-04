import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {stubUiRenderer} from './renderer/context.test-support';
import ActionMenuNode from './ActionMenuNode.vue';
import FieldNode from './FieldNode.vue';
import type {UiNodePayload, UiValues} from './types';

const TextControl = defineComponent({
  props: ['value'],
  render() {
    return h('input', {value: this.value});
  },
});

const detail = {
  elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
  elementId: 43,
  siteId: 1,
  ownerId: null,
  fieldId: null,
  layoutElementUid: 'layout-uid',
  status: 'modified',
  label: 'Summary',
};

const node = {
  type: 'Field',
  component: 'craft:field',
  props: {label: 'Summary', layoutUid: 'layout-uid', status: 'modified'},
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
            label: 'Compare changes',
            action: {
              type: 'event',
              name: 'craft:compare-field-changes',
              detail,
            },
          },
        ],
      },
    },
  ],
} as UiNodePayload;

describe('FieldNode change comparisons', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const post = vi.spyOn(actionClient, 'post');

  beforeEach(() => {
    post.mockReset();
    container = document.createElement('div');
    document.body.append(container);
  });

  afterEach(() => {
    app?.unmount();
    container.remove();
  });

  async function openComparison(): Promise<HTMLElement> {
    const values = reactive<UiValues>({editor: {fields: {summary: 'New'}}});

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
          });
      },
    });
    app.component('craft:text', TextControl);
    app.component('craft:action-menu', ActionMenuNode);
    app.provide(...stubUiRenderer(values));
    app.mount(container);

    await vi.waitFor(() => {
      expect(
        [...container.querySelectorAll('craft-action-item')].some(
          (item) => item.textContent?.trim() === 'Compare changes'
        )
      ).toBe(true);
    });
    [...container.querySelectorAll<HTMLElement>('craft-action-item')]
      .find((item) => item.textContent?.trim() === 'Compare changes')!
      .click();

    await vi.waitFor(() => expect(post).toHaveBeenCalledOnce());

    return document.body.querySelector<HTMLElement>(
      '[data-field-changes-modal]'
    )!;
  }

  it('shows a word diff of the field’s text', async () => {
    post.mockResolvedValue({
      data: {
        fromLabel: 'Current',
        toLabel: 'Draft 1',
        hasBase: true,
        unchanged: false,
        diff: {current: 'Old summary', proposed: 'New summary'},
        preview: null,
      },
    });

    const modal = await openComparison();

    expect(post.mock.calls[0]![1]).toEqual({
      elementType: detail.elementType,
      elementId: 43,
      siteId: 1,
      ownerId: null,
      fieldId: null,
      layoutElementUid: 'layout-uid',
      status: 'modified',
    });
    await vi.waitFor(() =>
      expect(modal.querySelector('craft-text-diff')).not.toBeNull()
    );
    const diff = modal.querySelector('craft-text-diff') as HTMLElement & {
      current: string;
      proposed: string;
    };
    expect(modal.querySelector('[role="status"]')?.textContent).toBe(
      'Changes loaded.'
    );
    expect(diff.current).toBe('Old summary');
    expect(diff.proposed).toBe('New summary');
    expect(
      modal.querySelector('.field-changes__versions .sr-only')?.textContent
    ).toBe('Current compared with Draft 1');
    expect(
      (
        document.body.querySelector('craft-pane') as HTMLElement & {
          label: string;
        }
      ).label
    ).toBe('Compare “Summary”');
  });

  it('says when both values are the same', async () => {
    post.mockResolvedValue({
      data: {
        fromLabel: 'Revision 4',
        toLabel: 'Revision 5',
        hasBase: true,
        unchanged: true,
        diff: {current: 'Same', proposed: 'Same'},
        preview: null,
      },
    });

    const modal = await openComparison();

    await vi.waitFor(() =>
      expect(modal.querySelector('[role="status"]')?.textContent).toBe(
        'Revision 4 and Revision 5 have the same value.'
      )
    );
    expect(modal.querySelector('craft-text-diff')).toBeNull();
  });

  it('previews fields that aren’t text side by side', async () => {
    const preview = (label: string) => ({
      scope: [],
      refreshable: false,
      nodes: [
        {
          type: 'Field',
          component: 'craft:field',
          props: {label, labelSrOnly: true},
          control: {
            type: 'Text',
            component: 'craft:text',
            path: ['preview', label],
            deltaGroup: ['preview', label],
            mode: 'readOnly',
            props: {},
          },
        },
      ],
      values: {preview: {[label]: label}},
      errors: [],
      globalErrors: [],
    });
    post.mockResolvedValue({
      data: {
        fromLabel: 'Current',
        toLabel: 'Draft 1',
        hasBase: true,
        unchanged: false,
        diff: null,
        preview: {current: preview('old'), proposed: preview('new')},
      },
    });

    const modal = await openComparison();

    await vi.waitFor(() => expect(modal.querySelector('dl')).not.toBeNull());
    expect(
      [...modal.querySelectorAll('dt')].map((dt) => dt.textContent?.trim())
    ).toEqual(['Current Summary', 'Draft 1 Summary']);
    expect(modal.querySelectorAll('dd')).toHaveLength(2);
    expect(modal.querySelector('craft-text-diff')).toBeNull();
  });

  it('shows the server’s error', async () => {
    post.mockRejectedValue(new Error('Network down'));

    const modal = await openComparison();

    await vi.waitFor(() =>
      expect(modal.querySelector('[role="alert"]')?.textContent?.trim()).toBe(
        'Couldn’t load the changes.'
      )
    );
  });
});
