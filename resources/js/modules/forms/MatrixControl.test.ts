import {createApp, h, nextTick, reactive} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {ActionItems} from '@/common/types';
import type {FormControlPayload} from './types';

// `craft-action-menu` is a Lion overlay and doesn't bootstrap under happy-dom.
// These tests are about which items MatrixControl composes, so stand in for it
// and record the props each block's menu is handed.
const menuStub = vi.hoisted(() => ({
  instances: [] as Array<{actions: ActionItems}>,
}));

vi.mock('@/common/components/ActionMenu.vue', async () => {
  const {h: createElement} = await import('vue');

  return {
    default: {
      name: 'ActionMenu',
      props: {actions: {type: Array, default: () => []}},
      setup(props: {actions: ActionItems}) {
        menuStub.instances.push(props);

        return () => createElement('div', {class: 'stub-action-menu'});
      },
    },
  };
});

import MatrixControl from './MatrixControl.vue';
import {isBlockCollapsed} from '@/modules/matrix/collapsed-blocks';

describe('MatrixControl', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let container: HTMLElement | undefined;

  afterEach(() => {
    try {
      app?.unmount();
    } catch {
      // `craft-matrix-input` relocates its own light DOM, which trips Vue's
      // unmount under happy-dom. Not what these tests are about.
    }
    container?.remove();
    app = undefined;
    container = undefined;
  });

  const control = (): FormControlPayload =>
    ({
      type: 'CraftCms\\Cms\\Form\\Controls\\Matrix',
      component: 'craft:matrix',
      props: {
        entryTypes: [{value: 'newType', label: 'New Type'}],
        addLabel: 'Add an entry',
        minEntries: null,
        maxEntries: null,
      },
      path: ['fields', 'pageBuilder'],
      mode: 'editable',
      deltaGroup: ['fields', 'pageBuilder'],
      forms: [],
    }) as unknown as FormControlPayload;

  let emitted: Array<Record<string, unknown>>;
  const menus = menuStub.instances;

  beforeEach(() => {
    menus.length = 0;
    localStorage.clear();
  });

  function mount(
    value: unknown,
    props: Record<string, unknown> = {}
  ): {value: unknown} {
    emitted = [];
    const state = reactive({value});
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      setup: () => () =>
        h(MatrixControl, {
          control: {
            ...control(),
            props: {...control().props, ...props},
          },
          value: state.value,
          values: {},
          errors: [],
          touchedPaths: new Set<string>(),
          editable: true,
          'onUpdate:value': (next: unknown) => {
            emitted.push(next as Record<string, unknown>);
            state.value = next;
          },
        } as never),
    });
    app.mount(container);

    return state;
  }

  /** Fire a menu item's `event` action the way `runAction()` does. */
  function invoke(uid: string, label: string): void {
    const menu = menus.find((m) =>
      m.actions.some((item) => 'label' in item && item.label === label)
    );
    const item = menu?.actions.find(
      (i) => 'label' in i && i.label === label
    ) as {action?: {type: string; name: string; detail: object}} | undefined;

    if (item?.action?.type !== 'event') {
      throw new Error(
        `No event action labelled [${label}] for block [${uid}].`
      );
    }

    window.dispatchEvent(
      new CustomEvent(item.action.name, {
        detail: {
          ...item.action.detail,
          trigger: container!.querySelector(`[data-id="${uid}"]`),
        },
      })
    );
  }

  it('renders empty when its value is missing from the values tree', async () => {
    // A block minted in the browser is keyed `uid:<uuid>` in the values tree,
    // but the server strips that prefix and scopes the block's nested Form to
    // the bare UUID. Between those two renders a repeater nested inside the
    // block resolves its path to `undefined` — it has to render empty rather
    // than throw, or FormRenderer swaps the whole form for a render error.
    expect(() => mount(undefined)).not.toThrow();
    await nextTick();

    expect(container!.querySelectorAll('.matrixblock').length).toBe(0);
    expect(
      container!.querySelector('[data-form-matrix-add="newType"]')
    ).not.toBeNull();
  });

  it('offers a menu on a block the browser just minted', async () => {
    // No server-built menu exists until the next save materializes the block,
    // so the control composes the half that needs no server data.
    mount({
      entries: {'uid:block-a': {type: 'newType', enabled: true}},
      sortOrder: ['uid:block-a'],
    });
    await nextTick();

    const labels = menus[0]!.actions.flatMap((item) =>
      'label' in item ? [item.label] : []
    );

    expect(labels).toEqual([
      'Collapse',
      'Disable',
      'Delete',
      'Add New Type above',
    ]);
  });

  it('remembers a collapsed block in storage rather than the value', async () => {
    mount({
      entries: {'block-a': {type: 'newType', enabled: true}},
      sortOrder: ['block-a'],
    });
    await nextTick();

    invoke('block-a', 'Collapse');
    await nextTick();

    // Collapsing is a view preference. Posting it would mark the form dirty and
    // kick off an autosave just for hiding some fields.
    expect(emitted).toHaveLength(0);
    expect(isBlockCollapsed('block-a')).toBe(true);
    expect(container!.querySelector('.matrixblock')!.className).toContain(
      'collapsed'
    );

    const labels = menus
      .at(-1)!
      .actions.flatMap((item) => ('label' in item ? [item.label] : []));
    expect(labels).toContain('Expand');
    expect(labels).not.toContain('Collapse');
  });

  it('also posts collapsed state for a block the browser just minted', async () => {
    // It has no identity the server knows yet, so storage alone would lose it
    // the moment the next save renames it. Craft 5 posted a hidden input here.
    mount({
      entries: {'uid:block-a': {type: 'newType', enabled: true}},
      sortOrder: ['uid:block-a'],
    });
    await nextTick();

    invoke('uid:block-a', 'Collapse');
    await nextTick();

    expect(emitted.at(-1)).toMatchObject({
      entries: {'uid:block-a': {collapsed: true}},
    });
    expect(isBlockCollapsed('uid:block-a')).toBe(true);
  });

  it('disables a block', async () => {
    mount({
      entries: {'block-a': {type: 'newType', enabled: true}},
      sortOrder: ['block-a'],
    });
    await nextTick();

    invoke('block-a', 'Disable');
    await nextTick();

    expect(emitted.at(-1)).toMatchObject({
      entries: {'block-a': {enabled: false}},
    });
  });

  it('adds a block above the one whose menu was used', async () => {
    mount({
      entries: {
        'block-a': {type: 'newType', enabled: true},
        'block-b': {type: 'newType', enabled: true},
      },
      sortOrder: ['block-a', 'block-b'],
    });
    await nextTick();

    // The second block's menu — the new one has to land at index 1, not 0.
    const menu = menus[1]!;
    const item = menu.actions.find(
      (i) => 'label' in i && i.label === 'Add New Type above'
    ) as {action: {name: string; detail: object}};
    window.dispatchEvent(
      new CustomEvent(item.action.name, {
        detail: {
          ...item.action.detail,
          trigger: container!.querySelector('[data-id="block-b"]'),
        },
      })
    );
    await nextTick();

    const sortOrder = (emitted.at(-1) as {sortOrder: string[]}).sortOrder;
    expect(sortOrder).toHaveLength(3);
    expect(sortOrder[0]).toBe('block-a');
    expect(sortOrder[2]).toBe('block-b');
    expect(sortOrder[1]).toMatch(/^uid:/);
  });

  it('deletes a block, but not below minEntries', async () => {
    mount(
      {
        entries: {
          'block-a': {type: 'newType', enabled: true},
          'block-b': {type: 'newType', enabled: true},
        },
        sortOrder: ['block-a', 'block-b'],
      },
      {minEntries: 2}
    );
    await nextTick();

    invoke('block-a', 'Delete');
    await nextTick();
    expect(emitted).toHaveLength(0);
  });

  it('ignores a block action fired by a different Matrix field', async () => {
    mount({
      entries: {'block-a': {type: 'newType', enabled: true}},
      sortOrder: ['block-a'],
    });
    await nextTick();

    // `runAction()` dispatches on window, so every Matrix on the page hears it.
    window.dispatchEvent(
      new CustomEvent('craft:matrix-block-action', {
        detail: {
          action: 'delete',
          uid: 'block-a',
          trigger: document.createElement('div'),
        },
      })
    );
    await nextTick();

    expect(emitted).toHaveLength(0);
  });

  it('reorders through the reorder button', async () => {
    mount({
      entries: {
        'block-a': {type: 'newType', enabled: true},
        'block-b': {type: 'newType', enabled: true},
      },
      sortOrder: ['block-a', 'block-b'],
    });
    await nextTick();

    const button = container!.querySelectorAll('craft-reorder-button')[1]!;
    button.dispatchEvent(
      new CustomEvent('reorder', {bubbles: true, detail: {direction: 'up'}})
    );
    await nextTick();

    expect((emitted.at(-1) as {sortOrder: string[]}).sortOrder).toEqual([
      'block-b',
      'block-a',
    ]);
  });

  it('selects blocks and applies a menu action across the selection', async () => {
    mount({
      entries: {
        'block-a': {type: 'newType', enabled: true},
        'block-b': {type: 'newType', enabled: true},
        'block-c': {type: 'newType', enabled: true},
      },
      sortOrder: ['block-a', 'block-b', 'block-c'],
    });
    await nextTick();

    const blocks = [...container!.querySelectorAll('.matrixblock')];
    blocks[0]!.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    blocks[1]!.dispatchEvent(
      new MouseEvent('click', {bubbles: true, shiftKey: true})
    );
    await nextTick();

    expect(blocks[0]!.className).toContain('sel');
    expect(blocks[1]!.className).toContain('sel');
    expect(blocks[2]!.className).not.toContain('sel');

    // Craft 5's `bulkActionMode()`: a menu action on a block that's part of a
    // multi-selection applies to the whole selection.
    invoke('block-a', 'Disable');
    await nextTick();

    expect(emitted.at(-1)).toMatchObject({
      entries: {
        'block-a': {enabled: false},
        'block-b': {enabled: false},
        'block-c': {enabled: true},
      },
    });
  });

  it('renders its blocks when the value is present', async () => {
    mount({
      entries: {'uid:block-a': {type: 'newType'}},
      sortOrder: ['uid:block-a'],
    });
    await nextTick();

    expect(container!.querySelectorAll('.matrixblock').length).toBe(1);
  });
});
