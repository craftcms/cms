import {createApp, h, nextTick} from 'vue';
import {afterEach, describe, expect, it} from 'vite-plus/test';
import ActionMenuNode from './ActionMenuNode.vue';
import CopyAttributeNode from './CopyAttributeNode.vue';
import type {FormNodePayload} from './types';

describe('ActionMenuNode', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  function mount(component: unknown, node: unknown) {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(component as never, {
          node,
          // FormNode hands every node the same prop set; a stateless node
          // must not leak these onto its host element.
          values: {},
          errors: [],
          touchedPaths: new Set<string>(),
          scope: [],
          refreshable: false,
        }),
    });
    app.mount(container);

    return container;
  }

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('passes the server descriptors through to craft-action-menu', async () => {
    const node = {
      type: 'CraftCms\\Cms\\Form\\Nodes\\ActionMenu',
      component: 'craft:action-menu',
      uid: 'field-actions:fields.body:menu',
      props: {
        label: 'Field actions',
        icon: 'ellipsis',
        items: [
          {
            type: 'button',
            label: 'Field settings',
            icon: 'gear',
            action: {
              type: 'event',
              name: 'craft:edit-field',
              detail: {fieldId: 1},
            },
          },
        ],
      },
    } as unknown as FormNodePayload;

    const el = mount(ActionMenuNode, node).querySelector('craft-action-menu')!;
    await nextTick();

    expect(el).not.toBeNull();
    expect(el.getAttribute('data-form-node')).toBe(
      'field-actions:fields.body:menu'
    );
    // `actions` is `@property({attribute: false})` — a JS property, not an
    // attribute, so assert on the property.
    expect((el as never as {actions: unknown[]}).actions).toEqual([
      {
        type: 'button',
        label: 'Field settings',
        icon: 'gear',
        action: {type: 'event', name: 'craft:edit-field', detail: {fieldId: 1}},
      },
    ]);
    expect(el.getAttribute('scope')).toBeNull();
    expect(el.getAttribute('refreshable')).toBeNull();
  });

  it('renders nothing but an empty menu when the server sent no items', async () => {
    const node = {
      component: 'craft:action-menu',
      uid: 'menu',
      props: {},
    } as unknown as FormNodePayload;

    const el = mount(ActionMenuNode, node).querySelector('craft-action-menu')!;
    await nextTick();

    expect((el as never as {actions: unknown[]}).actions).toEqual([]);
  });
});

describe('CopyAttributeNode', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    container?.remove();
  });

  it('renders the handle into craft-copy-attribute', async () => {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      render: () =>
        h(CopyAttributeNode, {
          node: {
            component: 'craft:copy-attribute',
            uid: 'field-actions:fields.body:handle',
            props: {value: 'body'},
          },
          values: {},
          errors: [],
          touchedPaths: new Set<string>(),
          scope: [],
          refreshable: false,
        } as never),
    });
    app.mount(container);
    await nextTick();

    const el = container.querySelector('craft-copy-attribute')!;

    // `value` is a declared Lit property without `reflect`, so Vue sets it as
    // a property on the upgraded element rather than an attribute.
    expect((el as never as {value: string}).value).toBe('body');
    expect(el.getAttribute('data-form-node')).toBe(
      'field-actions:fields.body:handle'
    );
    expect(el.getAttribute('scope')).toBeNull();
  });
});
