import {createApp, h, nextTick, reactive, shallowRef, type Ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import type {ActionItems} from '@/common/types';
import type {FormControlPayload} from './types';

// `craft-action-menu` is a Lion overlay and doesn't bootstrap under happy-dom.
// These tests are about which items MatrixControl composes, so stand in for it
// and record the props each block's menu is handed.
const menuStub = vi.hoisted(() => ({
  instances: [] as Array<{actions: ActionItems}>,
}));

const action = vi.hoisted(() => ({post: vi.fn()}));

vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal<Record<string, unknown>>()),
  actionClient: {post: action.post},
}));

vi.mock('@/common/components/ActionMenu.vue', async () => {
  const {h: createElement} = await import('vue');

  return {
    default: {
      name: 'ActionMenu',
      props: {actions: {type: Array, default: () => []}},
      setup(
        props: {actions: ActionItems},
        {
          slots,
        }: {slots: Record<string, ((scope: object) => unknown) | undefined>}
      ) {
        menuStub.instances.push(props);

        // The invoker renders where the real one does, so its state can be read.
        return () =>
          createElement('div', {class: 'stub-action-menu'}, [
            slots.invoker
              ? createElement(
                  'span',
                  {slot: 'invoker'},
                  slots.invoker({attributes: {slot: 'invoker'}}) as never
                )
              : null,
          ]);
      },
    },
  };
});

import MatrixControl from './MatrixControl.vue';
import {FieldActionItems} from './runtime';
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

  /** Provided the way FieldNode does, when a test wants the field's menu. */
  let fieldActions:
    | Ref<((items: ActionItems) => ActionItems) | undefined>
    | undefined;

  beforeEach(() => {
    fieldActions = undefined;
    menus.length = 0;
    localStorage.clear();
    action.post.mockReset();
  });

  function mount(
    value: unknown,
    props: Record<string, unknown> = {},
    values: Record<string, unknown> = {}
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
          values,
          errors: [],
          touchedPaths: new Set<string>(),
          editable: true,
          'onUpdate:value': (next: unknown) => {
            emitted.push(next as Record<string, unknown>);
            state.value = next;
          },
        } as never),
    });
    if (fieldActions) {
      app.provide(FieldActionItems, fieldActions);
    }
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

  function menuLabels(menu = menus.at(-1)): string[] {
    return (
      menu?.actions.flatMap((item) => ('label' in item ? [item.label] : [])) ??
      []
    );
  }

  it('renders empty when it is handed its empty value', async () => {
    // A block minted in the browser is keyed `uid:<uuid>` in the values tree,
    // but the server strips that prefix and scopes the block's nested Form to
    // the bare UUID. Between those two renders a repeater nested inside the
    // block resolves its path to `undefined`; `controlValueAt()` stands this
    // Control's own empty value in at the render boundary — see
    // `Control::emptyValue()`.
    expect(() => mount({entries: {}, sortOrder: []})).not.toThrow();
    await nextTick();

    expect(container!.querySelectorAll('[data-matrix-block]').length).toBe(0);
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

    expect(menuLabels(menus[0])).toEqual([
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
    expect(
      container!
        .querySelector('[data-matrix-block]')!
        .hasAttribute('data-collapsed')
    ).toBe(true);

    expect(menuLabels()).toContain('Expand');
    expect(menuLabels()).not.toContain('Collapse');
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

  it('names a folded block in its header, and only then', async () => {
    mount(
      {
        entries: {'block-a': {type: 'newType', enabled: true, title: 'Hero'}},
        sortOrder: ['block-a'],
      },
      {blocks: {'block-a': {label: 'Saved label'}}}
    );
    await nextTick();

    // Expanded, the block's own fields identify it — there's nothing to say.
    expect(
      container!.querySelector(
        '[data-matrix-block] [data-matrix-block-preview]'
      )
    ).toBeNull();

    invoke('block-a', 'Collapse');
    await nextTick();

    // A title being typed is fresher than the server's copy.
    expect(
      container!
        .querySelector('[data-matrix-block] [data-matrix-block-preview]')!
        .textContent!.trim()
    ).toBe('Hero');
  });

  it('falls back to the label the server sent when there is no title', async () => {
    mount(
      {
        entries: {'block-a': {type: 'newType', enabled: true, title: ''}},
        sortOrder: ['block-a'],
      },
      {blocks: {'block-a': {label: 'Saved label'}}}
    );
    await nextTick();

    invoke('block-a', 'Collapse');
    await nextTick();

    expect(
      container!
        .querySelector('[data-matrix-block] [data-matrix-block-preview]')!
        .textContent!.trim()
    ).toBe('Saved label');
  });

  it('carries the entry type icon and colour into the block header', async () => {
    mount(
      {
        entries: {'block-a': {type: 'newType', enabled: true}},
        sortOrder: ['block-a'],
      },
      {
        blocks: {
          'block-a': {icon: {name: 'gear', family: 'solid'}, color: 'red'},
        },
      }
    );
    await nextTick();

    const block = container!.querySelector('[data-matrix-block]')!;
    const icon = block.querySelector('craft-icon') as {
      name?: string;
      family?: string;
    } | null;

    expect(icon?.name).toBe('gear');
    expect(icon?.family).toBe('solid');
    // `data-color` is all it takes — the CP's colorable rules turn it into the
    // whole `--c-color-*` set the card paints from.
    expect(block.getAttribute('data-color')).toBe('red');
  });

  it('disables a block, folds it away, and flags it', async () => {
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

    const block = container!.querySelector('[data-matrix-block]')!;

    expect(block.hasAttribute('data-disabled')).toBe(true);
    expect(block.hasAttribute('data-collapsed')).toBe(true);
    expect(isBlockCollapsed('block-a')).toBe(true);
    // The card folds its own body and footer away; the class is only a hook.
    expect(
      (block.querySelector('craft-card') as {collapsed?: boolean} | null)
        ?.collapsed
    ).toBe(true);
    // `status` isn't reflected, so Vue sets it as a property on the element.
    expect(
      (block.querySelector('craft-status') as {status?: string} | null)?.status
    ).toBe('disabled');
  });

  it('brings a block back when it is enabled again', async () => {
    mount({
      entries: {'block-a': {type: 'newType', enabled: false}},
      sortOrder: ['block-a'],
    });
    await nextTick();

    // A block that arrives disabled starts folded away.
    expect(
      container!
        .querySelector('[data-matrix-block]')!
        .hasAttribute('data-collapsed')
    ).toBe(true);

    invoke('block-a', 'Enable');
    await nextTick();

    const block = container!.querySelector('[data-matrix-block]')!;

    expect(block.hasAttribute('data-disabled')).toBe(false);
    expect(block.hasAttribute('data-collapsed')).toBe(false);
    expect(
      (block.querySelector('craft-card') as {collapsed?: boolean} | null)
        ?.collapsed
    ).toBe(false);
    expect(block.querySelector('craft-status')).toBeNull();
  });

  it('updates site and global statuses independently', async () => {
    mount(
      {
        entries: {
          'block-a': {
            type: 'newType',
            enabled: true,
            enabledForSite: true,
          },
        },
        sortOrder: ['block-a'],
      },
      {siteName: 'English'}
    );
    await nextTick();

    invoke('block-a', 'Disable for English');
    await nextTick();

    expect(emitted.at(-1)).toMatchObject({
      entries: {
        'block-a': {enabled: true, enabledForSite: false},
      },
    });

    expect(menuLabels()).toEqual(
      expect.arrayContaining(['Enable for English', 'Disable globally'])
    );

    invoke('block-a', 'Disable globally');
    await nextTick();

    expect(emitted.at(-1)).toMatchObject({
      entries: {
        'block-a': {enabled: false, enabledForSite: false},
      },
    });
    expect(menuLabels()).not.toContain('Enable for English');

    invoke('block-a', 'Enable globally');
    await nextTick();

    expect(emitted.at(-1)).toMatchObject({
      entries: {
        'block-a': {enabled: true, enabledForSite: false},
      },
    });
    expect(menuLabels()).toContain('Enable for English');
  });

  it('folds a block when its titlebar is double-clicked', async () => {
    mount({
      entries: {'block-a': {type: 'newType', enabled: true}},
      sortOrder: ['block-a'],
    });
    await nextTick();

    const header = (): Element =>
      container!.querySelector('[data-matrix-block] [slot="header"]')!;
    const dblclick = (target: Element): void => {
      target.dispatchEvent(new MouseEvent('dblclick', {bubbles: true}));
    };

    dblclick(header().querySelector('[data-matrix-block-titlebar]')!);
    await nextTick();

    expect(
      container!
        .querySelector('[data-matrix-block]')!
        .hasAttribute('data-collapsed')
    ).toBe(true);
    expect(isBlockCollapsed('block-a')).toBe(true);

    // The checkbox shares the titlebar, but double-clicking it isn't a fold.
    dblclick(header().querySelector('craft-checkbox')!);
    await nextTick();

    expect(isBlockCollapsed('block-a')).toBe(true);

    dblclick(header());
    await nextTick();

    expect(
      container!
        .querySelector('[data-matrix-block]')!
        .hasAttribute('data-collapsed')
    ).toBe(false);
  });

  it('keeps Craft 5 class names off its blocks', async () => {
    mount({
      entries: {'block-a': {type: 'newType', enabled: false}},
      sortOrder: ['block-a'],
    });
    await nextTick();

    // The legacy stylesheet styles these, and would restyle the card frame.
    expect(
      container!.querySelectorAll(
        '.matrix, .matrix-field, .matrixblock, .js-deletable, .collapsed, .disabled-entry, .blocktype, .preview, .fields, .error'
      )
    ).toHaveLength(0);

    const block = container!.querySelector('[data-matrix-block]')!;
    expect(block.hasAttribute('data-disabled')).toBe(true);
    expect(block.hasAttribute('data-collapsed')).toBe(true);
    expect(block.querySelector('[data-matrix-block-preview]')).not.toBeNull();
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

  describe('the server-built menu, resolved against live state', () => {
    /** A block action item the way `Matrix::blockActions()` ships it. */
    function serverItem(
      uid: string,
      action: string,
      label: string,
      extra: Record<string, unknown> = {}
    ) {
      return {
        label,
        ...extra,
        action: {
          type: 'event',
          name: 'craft:matrix-block-action',
          detail: {action, uid},
        },
      };
    }

    const serverActions = (uid: string) => [
      serverItem(uid, 'collapse', 'Collapse'),
      serverItem(uid, 'expand', 'Expand', {hidden: true}),
      serverItem(uid, 'disableForSite', 'Disable for English'),
      serverItem(uid, 'enableForSite', 'Enable for English', {hidden: true}),
      serverItem(uid, 'disableGlobally', 'Disable globally'),
      serverItem(uid, 'enableGlobally', 'Enable globally', {hidden: true}),
      serverItem(uid, 'delete', 'Delete'),
      serverItem(uid, 'duplicate', 'Duplicate'),
      serverItem(uid, 'copy', 'Copy'),
      serverItem(uid, 'paste', 'Paste entry above', {hidden: true}),
      serverItem(uid, 'add', 'Add New Type above'),
    ];

    function item(uid: string, action: string) {
      const menu = menus.find((instance) =>
        instance.actions.some(
          (candidate) =>
            'action' in candidate &&
            (candidate.action as {detail?: {uid?: string}})?.detail?.uid === uid
        )
      );

      return menu?.actions.find(
        (candidate) =>
          'action' in candidate &&
          (candidate.action as {detail?: {action?: string}})?.detail?.action ===
            action
      ) as {label?: string; hidden?: boolean; disabled?: boolean} | undefined;
    }

    function mountWithMenu(
      props: Record<string, unknown> = {},
      value?: unknown
    ) {
      return mount(
        value ?? {
          entries: {
            'block-a': {type: 'newType', enabled: true},
            'block-b': {type: 'newType', enabled: true},
          },
          sortOrder: ['block-a', 'block-b'],
        },
        {
          elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
          create: {
            fieldId: 3,
            ownerId: 7,
            ownerElementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
            siteId: 1,
            entryTypeIds: {newType: 9},
          },
          blocks: {
            'block-a': {
              actions: serverActions('block-a'),
              data: {'element-id': 12, 'owner-id': 7, 'site-id': 1},
            },
            'block-b': {
              actions: serverActions('block-b'),
              data: {'element-id': 13, 'owner-id': 7, 'site-id': 1},
            },
          },
          ...props,
        }
      );
    }

    it('resolves the stateful pair against the block, not the last save', async () => {
      mountWithMenu();
      await nextTick();

      // The server shipped Expand hidden and Collapse shown. Folding the block
      // has to swap them without waiting for a save.
      expect(item('block-a', 'collapse')?.hidden).toBe(false);
      expect(item('block-a', 'expand')?.hidden).toBe(true);

      invoke('block-a', 'Collapse');
      await nextTick();

      expect(item('block-a', 'collapse')?.hidden).toBe(true);
      expect(item('block-a', 'expand')?.hidden).toBe(false);
    });

    it('speaks in the plural once a selection is what the action applies to', async () => {
      mountWithMenu();
      await nextTick();

      expect(item('block-a', 'delete')?.label).toBe('Delete');

      for (const box of container!.querySelectorAll('craft-checkbox')) {
        box.dispatchEvent(new MouseEvent('click', {bubbles: true}));
        Object.assign(box, {checked: true});
        box.dispatchEvent(
          new CustomEvent('model-value-changed', {bubbles: true})
        );
      }
      await nextTick();

      expect(item('block-a', 'delete')?.label).toBe('Delete selected blocks');
      expect(item('block-a', 'duplicate')?.label).toBe(
        'Duplicate selected blocks'
      );
      expect(item('block-a', 'copy')?.label).toBe('Copy selected blocks');
    });

    it('hides what there is no room for', async () => {
      mountWithMenu({maxEntries: 2});
      await nextTick();

      expect(item('block-a', 'add')?.hidden).toBe(true);
      expect(item('block-a', 'duplicate')?.hidden).toBe(true);
      // Delete is still on: it is what makes room.
      expect(item('block-a', 'delete')?.hidden).toBeUndefined();
    });

    it('keeps paste hidden while the clipboard has nothing that fits', async () => {
      mountWithMenu();
      await nextTick();

      expect(item('block-a', 'paste')?.hidden).toBe(true);
    });

    it('duplicates through the create endpoint, naming the source', async () => {
      action.post.mockResolvedValue({
        data: {
          uid: 'block-c',
          type: 'newType',
          form: {
            scope: ['fields', 'pageBuilder', 'entries', 'block-c'],
            nodes: [],
          },
          values: {},
        },
      });
      mountWithMenu();
      await nextTick();

      invoke('block-a', 'Duplicate');
      await nextTick();
      await nextTick();

      expect(action.post).toHaveBeenCalledWith(
        'matrix/create-entry',
        expect.objectContaining({duplicate: 12, entryTypeId: 9})
      );
    });

    it('hands the block to the CP clipboard by its element id', async () => {
      const copyElements = vi.fn();
      Object.assign(window, {
        Craft: {
          ...window.Craft,
          cp: {...window.Craft?.cp, copyElements},
        },
      });
      mountWithMenu();
      await nextTick();

      invoke('block-a', 'Copy');
      await nextTick();

      expect(copyElements).toHaveBeenCalledWith([
        {
          type: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
          id: 12,
          draftId: null,
          revisionId: null,
          fieldId: null,
          ownerId: 7,
          siteId: 1,
        },
      ]);
    });
  });

  it('paints a freshly minted block from the presentation that came with it', async () => {
    action.post.mockResolvedValue({
      data: {
        uid: 'block-b',
        type: 'newType',
        form: {
          scope: ['fields', 'pageBuilder', 'entries', 'block-b'],
          nodes: [],
        },
        values: {},
        // Without this the new block is a blank card — no colour, no icon, no
        // menu — until the next save brings the field's own copy round.
        block: {
          label: 'Entry 20',
          color: 'teal',
          icon: {name: 'gear', family: 'solid'},
          data: {'element-id': 20},
        },
      },
    });
    mount(
      {entries: {}, sortOrder: []},
      {
        create: {
          fieldId: 3,
          ownerId: 7,
          ownerElementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
          siteId: 1,
          entryTypeIds: {newType: 9},
        },
      }
    );
    await nextTick();

    container!
      .querySelector<HTMLElement>('[data-form-matrix-add]')!
      .dispatchEvent(new MouseEvent('click', {bubbles: true}));
    await nextTick();
    await nextTick();
    await nextTick();

    const block = container!.querySelector<HTMLElement>('[data-matrix-block]')!;

    expect(block.dataset.color).toBe('teal');
    expect(block.dataset.elementId).toBe('20');
    expect(block.querySelector('craft-icon')).not.toBeNull();
  });

  it('highlights a block that has just appeared, then lets it settle', async () => {
    vi.useFakeTimers();

    try {
      action.post.mockResolvedValue({
        data: {
          uid: 'block-b',
          type: 'newType',
          form: {
            scope: ['fields', 'pageBuilder', 'entries', 'block-b'],
            nodes: [],
          },
          values: {},
          block: {},
        },
      });
      mount(
        {entries: {}, sortOrder: []},
        {
          create: {
            fieldId: 3,
            ownerId: 7,
            ownerElementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
            siteId: 1,
            entryTypeIds: {newType: 9},
          },
        }
      );
      await nextTick();

      container!
        .querySelector<HTMLElement>('[data-form-matrix-add]')!
        .dispatchEvent(new MouseEvent('click', {bubbles: true}));

      for (let tick = 0; tick < 5; tick++) {
        await nextTick();
      }

      const block = container!.querySelector('[data-matrix-block]')!;

      expect(block.hasAttribute('data-matrix-block-new')).toBe(true);

      vi.advanceTimersByTime(1200);
      await nextTick();

      expect(
        container!
          .querySelector('[data-matrix-block]')!
          .hasAttribute('data-matrix-block-new')
      ).toBe(false);
    } finally {
      vi.useRealTimers();
    }
  });

  describe('the add buttons', () => {
    const types = (count: number, group?: (index: number) => string) =>
      Array.from({length: count}, (_, index) => ({
        value: `type-${index}`,
        label: `Type ${index}`,
        icon: {name: 'gear', family: 'solid'},
        color: 'red',
        ...(group ? {group: group(index)} : {}),
      }));

    it('shows the add menu’s button loading while the server mints a block', async () => {
      const uid = '9f1c0a3e-0000-4000-8000-000000000002';
      let settle: (value: unknown) => void;
      action.post.mockReturnValueOnce(
        new Promise((resolve) => {
          settle = resolve;
        })
      );

      mount(
        {entries: {}, sortOrder: []},
        {
          // Two groups: the types are offered from a menu.
          entryTypes: types(2, (index) => (index === 0 ? 'Layout' : 'Content')),
          create: {
            fieldId: 33,
            ownerId: 1568,
            ownerElementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
            siteId: 1,
            entryTypeIds: {'type-0': 25, 'type-1': 26},
          },
        }
      );
      await nextTick();

      const invoker = () =>
        container!.querySelector<
          HTMLElement & {loading?: boolean; disabled?: boolean}
        >('[slot="invoker"] craft-button');
      const groups = menus.at(-1)!.actions as unknown as Array<{
        items: Array<{onClick: () => void}>;
      }>;

      expect(invoker()).not.toBeNull();

      groups[0]!.items[0]!.onClick();
      await nextTick();

      expect(invoker()!.loading).toBe(true);
      expect(invoker()!.disabled).toBe(true);

      settle!({
        data: {
          uid,
          type: 'type-0',
          form: {
            scope: ['fields', 'pageBuilder', 'entries', uid],
            refreshable: true,
            nodes: [],
          },
          values: {},
        },
      });

      await vi.waitFor(() => expect(invoker()?.loading).toBe(false));
      expect(invoker()!.disabled).toBe(false);
    });

    it('carries each entry type’s own icon and colour', async () => {
      mount({entries: {}, sortOrder: []}, {entryTypes: types(2)});
      await nextTick();

      const buttons = container!.querySelectorAll('[data-form-matrix-add]');

      expect(buttons).toHaveLength(2);
      // `craft-button` takes its icon as a property once it's defined.
      expect(
        (buttons[0] as {icon?: string}).icon ?? buttons[0]!.getAttribute('icon')
      ).toBe('gear');
      expect(buttons[0]!.getAttribute('data-color')).toBe('red');
    });

    it('files the types under their groups once there is more than one', async () => {
      mount(
        {entries: {}, sortOrder: []},
        {entryTypes: types(4, (index) => (index < 2 ? 'Layout' : 'Content'))}
      );
      await nextTick();

      // One button per type gives no room for the group names, so they move
      // into a menu — the way Craft 5 offered them.
      expect(
        container!.querySelectorAll('[data-form-matrix-add]')
      ).toHaveLength(0);

      const menu = menus.at(-1)!;
      const groups = menu.actions as Array<{
        type: string;
        heading?: string;
        items: unknown[];
      }>;

      expect(groups.map((group) => group.heading)).toEqual([
        'Layout',
        'Content',
      ]);
      expect(groups[0]!.items).toHaveLength(2);
    });
  });

  describe('field menu selection', () => {
    const serverItems = (): ActionItems =>
      [
        {
          type: 'button',
          label: 'Copy all blocks',
          action: {
            type: 'event',
            name: 'craft:copy-nested-elements',
            detail: {selector: '[data-matrix-block]'},
          },
        },
        {
          type: 'button',
          label: 'Collapse selected blocks',
          hidden: true,
          action: {
            type: 'event',
            name: 'craft:matrix-selection-action',
            detail: {action: 'collapse'},
          },
        },
        {
          type: 'button',
          label: 'Disable selected blocks',
          hidden: true,
          action: {
            type: 'event',
            name: 'craft:matrix-selection-action',
            detail: {action: 'disable'},
          },
        },
      ] as unknown as ActionItems;

    type ResolvedItem = {
      label: string;
      hidden?: boolean;
      action: {detail: Record<string, unknown>};
    };

    function resolved(): ResolvedItem[] {
      return fieldActions!.value!(serverItems()) as unknown as ResolvedItem[];
    }

    async function mountTwoInField(): Promise<HTMLElement> {
      fieldActions = shallowRef();
      mount({
        entries: {
          'block-a': {type: 'newType', enabled: true},
          'block-b': {type: 'newType', enabled: true},
        },
        sortOrder: ['block-a', 'block-b'],
      });
      await nextTick();

      const field = document.createElement('craft-field');
      container!.replaceWith(field);
      field.append(container!);
      const trigger = document.createElement('button');
      field.append(trigger);

      return trigger;
    }

    async function selectFirst(): Promise<void> {
      const box = container!.querySelector(
        '[data-matrix-block] craft-checkbox'
      )!;
      Object.assign(box, {checked: true});
      box.dispatchEvent(new MouseEvent('click', {bubbles: true}));
      box.dispatchEvent(
        new CustomEvent('model-value-changed', {bubbles: true})
      );
      await nextTick();
    }

    function invokeSelection(trigger: Element, action: string): void {
      window.dispatchEvent(
        new CustomEvent('craft:matrix-selection-action', {
          detail: {action, trigger},
        })
      );
    }

    it('relabels the field menu for the selection', async () => {
      await mountTwoInField();

      expect(resolved().map((item) => item.label)).toEqual([
        'Copy all blocks',
        'Collapse selected blocks',
        'Disable selected blocks',
      ]);
      expect(resolved()[1]!.hidden).toBe(true);

      await selectFirst();

      expect(resolved().map((item) => item.label)).toEqual([
        'Copy selected blocks',
        'Collapse selected blocks',
        'Disable selected blocks',
      ]);
      expect(resolved()[1]!.hidden).toBe(false);
      expect(resolved()[0]!.action.detail.selector).toBe(
        '[data-matrix-block][data-selected]'
      );
    });

    it('collapses and disables just the selected blocks', async () => {
      const trigger = await mountTwoInField();
      await selectFirst();

      invokeSelection(trigger, 'collapse');
      await nextTick();

      const blocks = container!.querySelectorAll('[data-matrix-block]');
      expect(blocks[0]!.hasAttribute('data-collapsed')).toBe(true);
      expect(blocks[1]!.hasAttribute('data-collapsed')).toBe(false);
      expect(resolved()[1]!.label).toBe('Expand selected blocks');

      invokeSelection(trigger, 'disable');
      await nextTick();

      expect(emitted.at(-1)).toMatchObject({
        entries: {
          'block-a': {enabled: false},
          'block-b': {enabled: true},
        },
      });
      expect(resolved()[2]!.label).toBe('Enable selected blocks');
    });

    it('ignores the selection items of another field', async () => {
      await mountTwoInField();
      await selectFirst();

      invokeSelection(document.createElement('button'), 'collapse');
      await nextTick();

      expect(
        container!
          .querySelector('[data-matrix-block]')!
          .hasAttribute('data-collapsed')
      ).toBe(false);
    });

    it('offers to expand or collapse all only when there is something to do', async () => {
      const trigger = await mountTwoInField();
      const items = [
        {
          type: 'button',
          label: 'Expand all blocks',
          action: {
            type: 'event',
            name: 'craft:matrix-toggle-all',
            detail: {collapse: false},
          },
        },
        {
          type: 'button',
          label: 'Collapse all blocks',
          action: {
            type: 'event',
            name: 'craft:matrix-toggle-all',
            detail: {collapse: true},
          },
        },
      ] as unknown as ActionItems;
      const hidden = () =>
        (fieldActions!.value!(items) as unknown as ResolvedItem[]).map((item) =>
          Boolean(item.hidden)
        );

      // Both blocks start expanded: nothing to expand.
      expect(hidden()).toEqual([true, false]);

      window.dispatchEvent(
        new CustomEvent('craft:matrix-toggle-all', {
          detail: {collapse: true, trigger},
        })
      );
      await nextTick();

      expect(hidden()).toEqual([false, true]);

      // One block open again: both have something to do.
      await selectFirst();
      invokeSelection(trigger, 'expand');
      await nextTick();

      expect(hidden()).toEqual([false, false]);
    });

    it('offers select and deselect all for what is and is not selected', async () => {
      const trigger = await mountTwoInField();
      const selectionItem = (action: string, label: string) => ({
        type: 'button',
        label,
        action: {
          type: 'event',
          name: 'craft:matrix-selection-action',
          detail: {action},
        },
      });
      const items = [
        selectionItem('select', 'Select all entries'),
        selectionItem('deselect', 'Deselect all entries'),
      ] as unknown as ActionItems;
      const hidden = () =>
        (fieldActions!.value!(items) as unknown as ResolvedItem[]).map((item) =>
          Boolean(item.hidden)
        );
      const selectedCount = () =>
        container!.querySelectorAll('[data-matrix-block][data-selected]')
          .length;

      expect(hidden()).toEqual([false, true]);

      // Some selected, some not: both apply.
      await selectFirst();
      expect(hidden()).toEqual([false, false]);

      invokeSelection(trigger, 'select');
      await nextTick();

      expect(selectedCount()).toBe(2);
      expect(hidden()).toEqual([true, false]);

      invokeSelection(trigger, 'deselect');
      await nextTick();

      expect(selectedCount()).toBe(0);
      expect(hidden()).toEqual([false, true]);
    });

    it('hands the menu back when it goes away', async () => {
      await mountTwoInField();

      app!.unmount();
      app = undefined;

      expect(fieldActions!.value).toBeUndefined();
    });
  });

  describe('expand/collapse all', () => {
    /**
     * The field menu's items are scoped by `craft-field`, not by the Matrix host
     * the way block actions are — the invoking item sits in the field's header,
     * outside the input.
     */
    function inField(): HTMLElement {
      const field = document.createElement('craft-field');
      container!.replaceWith(field);
      field.append(container!);

      return field;
    }

    function toggleAll(trigger: Element | null, collapse: boolean): void {
      window.dispatchEvent(
        new CustomEvent('craft:matrix-toggle-all', {
          detail: {collapse, trigger},
        })
      );
    }

    async function mountTwo(): Promise<void> {
      mount({
        entries: {
          'block-a': {type: 'newType', enabled: true},
          'block-b': {type: 'newType', enabled: true},
        },
        sortOrder: ['block-a', 'block-b'],
      });
      await nextTick();
      inField();
    }

    it('folds and unfolds every block in the field', async () => {
      await mountTwo();

      toggleAll(container!.querySelector('[data-matrix-block]'), true);
      await nextTick();

      expect(
        [...container!.querySelectorAll('[data-matrix-block]')].map((block) =>
          block.hasAttribute('data-collapsed')
        )
      ).toEqual([true, true]);

      toggleAll(container!.querySelector('[data-matrix-block]'), false);
      await nextTick();

      expect(
        [...container!.querySelectorAll('[data-matrix-block]')].map((block) =>
          block.hasAttribute('data-collapsed')
        )
      ).toEqual([false, false]);
    });

    it('ignores the item when it belongs to another field', async () => {
      await mountTwo();
      const other = document.createElement('craft-field');
      document.body.append(other);

      toggleAll(other, true);
      await nextTick();

      expect(
        container!
          .querySelector('[data-matrix-block]')!
          .hasAttribute('data-collapsed')
      ).toBe(false);
      other.remove();
    });
  });

  it('summarises a folded-up block from its fields when it has no label', async () => {
    mount({
      entries: {'block-a': {type: 'newType', enabled: true}},
      sortOrder: ['block-a'],
    });
    await nextTick();

    // Stand in for the nested form FormNodeList would have rendered.
    const fields = container!.querySelector(
      '[data-matrix-block] [data-matrix-block-fields]'
    )!;
    fields.innerHTML =
      '<craft-field><input type="text" value="Hello"></craft-field>' +
      '<craft-field><input type="text" value="World"></craft-field>';

    invoke('block-a', 'Collapse');
    await nextTick();

    expect(
      container!
        .querySelector('[data-matrix-block] [data-matrix-block-preview]')!
        .textContent!.trim()
    ).toBe('Hello | World');
  });

  it('owns up to a block whose fields have errors', async () => {
    emitted = [];
    container = document.createElement('div');
    document.body.append(container);
    app = createApp({
      setup: () => () =>
        h(MatrixControl, {
          control: control(),
          value: {
            entries: {'block-a': {type: 'newType', enabled: true}},
            sortOrder: ['block-a'],
          },
          values: {},
          errors: [
            {
              path: ['fields', 'pageBuilder', 'entries', 'block-a', 'heading'],
              messages: ['Heading is required.'],
            },
          ],
          touchedPaths: new Set<string>(),
          editable: true,
          'onUpdate:value': () => {},
        } as never),
    });
    app.mount(container);
    await nextTick();

    const titlebar = container!.querySelector('[data-matrix-block-titlebar]')!;

    expect(
      titlebar.querySelector('craft-icon[name="triangle-exclamation"]')
    ).not.toBeNull();
  });

  it('carries the block identity the clipboard reads off the DOM', async () => {
    mount(
      {
        entries: {'block-a': {type: 'newType', enabled: true}},
        sortOrder: ['block-a'],
      },
      {
        blocks: {
          'block-a': {
            label: 'Entry 12',
            data: {'element-id': 12, 'owner-id': 5, 'site-id': 1},
          },
        },
      }
    );
    await nextTick();

    const block = container!.querySelector<HTMLElement>('[data-matrix-block]')!;

    // `data-id` stays the UID — the identity the sort order and the posted value
    // are keyed by — so the element id rides alongside it.
    expect(block.dataset.id).toBe('block-a');
    expect(block.dataset.elementId).toBe('12');
    expect(block.dataset.ownerId).toBe('5');
    expect(block.dataset.siteId).toBe('1');
    expect(block.dataset.uiLabel).toBe('Entry 12');
  });

  it('moves the whole selection when one of it is dragged', async () => {
    mount({
      entries: {
        'block-a': {type: 'newType', enabled: true},
        'block-b': {type: 'newType', enabled: true},
        'block-c': {type: 'newType', enabled: true},
      },
      sortOrder: ['block-a', 'block-b', 'block-c'],
    });
    await nextTick();

    const boxes = container!.querySelectorAll('craft-checkbox');
    for (const box of [boxes[0]!, boxes[2]!]) {
      box.dispatchEvent(new MouseEvent('click', {bubbles: true}));
      Object.assign(box, {checked: true});
      box.dispatchEvent(
        new CustomEvent('model-value-changed', {bubbles: true})
      );
    }
    await nextTick();

    // Grab the first of the two selected blocks and drop it at the end. Craft 5
    // took the whole selection along; the drag engine only reports the one.
    container!
      .querySelectorAll('craft-reorder-button')[0]!
      .dispatchEvent(
        new CustomEvent('reorder', {bubbles: true, detail: {direction: 'down'}})
      );
    await nextTick();

    expect((emitted.at(-1) as {sortOrder: string[]}).sortOrder).toEqual([
      'block-b',
      'block-a',
      'block-c',
    ]);
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

  it('renders a select checkbox on each block', async () => {
    mount({
      entries: {
        'block-a': {type: 'newType', enabled: true},
        'block-b': {type: 'newType', enabled: true},
      },
      sortOrder: ['block-a', 'block-b'],
    });
    await nextTick();

    const boxes = container!.querySelectorAll(
      '[data-matrix-block] craft-checkbox'
    );
    expect(boxes).toHaveLength(2);

    // `craft-checkbox` reports its change from the host, not an inner input.
    const first = boxes[0]!;
    Object.assign(first, {checked: true});
    first.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    first.dispatchEvent(
      new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();

    expect(
      container!
        .querySelectorAll('[data-matrix-block]')[0]!
        .hasAttribute('data-selected')
    ).toBe(true);
  });

  it('disables a mixed selection globally from any selected block’s menu', async () => {
    mount(
      {
        entries: {
          'block-a': {
            type: 'newType',
            enabled: false,
            enabledForSite: true,
          },
          'block-b': {
            type: 'newType',
            enabled: true,
            enabledForSite: true,
          },
          'block-c': {
            type: 'newType',
            enabled: true,
            enabledForSite: true,
          },
        },
        sortOrder: ['block-a', 'block-b', 'block-c'],
      },
      {siteName: 'English'}
    );
    await nextTick();

    const blocks = [...container!.querySelectorAll('[data-matrix-block]')];
    blocks[0]!.dispatchEvent(new MouseEvent('click', {bubbles: true}));
    blocks[1]!.dispatchEvent(
      new MouseEvent('click', {bubbles: true, shiftKey: true})
    );
    await nextTick();

    expect(blocks[0]!.hasAttribute('data-selected')).toBe(true);
    expect(blocks[1]!.hasAttribute('data-selected')).toBe(true);
    expect(blocks[2]!.hasAttribute('data-selected')).toBe(false);

    const selectedMenu = menus.find((menu) =>
      menuLabels(menu).includes('Disable selected blocks globally')
    );
    expect(menuLabels(selectedMenu)).not.toContain(
      'Disable selected blocks for English'
    );

    invoke('block-a', 'Disable selected blocks globally');
    await nextTick();

    expect(emitted.at(-1)).toMatchObject({
      entries: {
        'block-a': {enabled: false, enabledForSite: true},
        'block-b': {enabled: false, enabledForSite: true},
        'block-c': {enabled: true, enabledForSite: true},
      },
    });
  });

  it('has the server mint a block when it can, and renders the nodes it returns', async () => {
    const uid = '9f1c0a3e-0000-4000-8000-000000000001';
    // Held open on purpose: the busy state only exists while the request is in
    // flight, and an immediately-resolved mock closes that window inside a
    // microtask — the assertion below would be racing it.
    let settle: (value: unknown) => void;
    action.post.mockReturnValueOnce(
      new Promise((resolve) => {
        settle = resolve;
      })
    );
    const response = {
      data: {
        uid,
        type: 'newType',
        form: {
          scope: ['fields', 'pageBuilder', 'entries', uid],
          refreshable: true,
          nodes: [],
        },
        values: {
          fields: {pageBuilder: {entries: {[uid]: {fields: {body: 'hi'}}}}},
        },
      },
    };

    const values: Record<string, unknown> = {};
    mount(
      {entries: {}, sortOrder: []},
      {
        create: {
          fieldId: 33,
          ownerId: 1568,
          ownerElementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
          siteId: 1,
          entryTypeIds: {newType: 25},
        },
      },
      values
    );
    await nextTick();

    const button = container!.querySelector<HTMLElement>(
      '[data-form-matrix-add="newType"]'
    )!;
    button.click();
    await nextTick();

    // The button reports itself busy while the server is working. `loading`
    // isn't reflected, so Vue sets it as a property on the element.
    expect((button as {loading?: boolean}).loading).toBe(true);

    settle!(response);
    await vi.waitFor(() => expect(emitted).toHaveLength(1));
    await nextTick();

    expect(action.post).toHaveBeenCalledWith('matrix/create-entry', {
      fieldId: 33,
      entryTypeId: 25,
      ownerId: 1568,
      ownerElementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
      siteId: 1,
      path: ['fields', 'pageBuilder'],
    });

    // The server's identity is used as-is — no `uid:` prefix to reconcile later.
    expect((emitted.at(-1) as {sortOrder: string[]}).sortOrder).toEqual([uid]);

    // Its field values ride in the same emit. Written straight into `values`
    // they wouldn't survive — the Control's value is written back wholesale at
    // its own path, dropping anything under the block that wasn't part of it.
    expect(
      (emitted.at(-1) as {entries: Record<string, unknown>}).entries[uid]
    ).toEqual({
      type: 'newType',
      enabled: true,
      enabledForSite: true,
      fields: {body: 'hi'},
    });
    // The Control re-keys its whole subtree when sortOrder changes, so the
    // button that was busy is not the button that's there now.
    await vi.waitFor(() =>
      expect(
        (
          container!.querySelector('[data-form-matrix-add="newType"]') as {
            loading?: boolean;
          }
        ).loading
      ).toBe(false)
    );
  });

  it('mints the block itself when the server offers no create config', async () => {
    mount({entries: {}, sortOrder: []});
    await nextTick();

    container!
      .querySelector<HTMLButtonElement>('[data-form-matrix-add="newType"]')!
      .click();
    await nextTick();

    expect(action.post).not.toHaveBeenCalled();
    expect((emitted.at(-1) as {sortOrder: string[]}).sortOrder[0]).toMatch(
      /^uid:/
    );
  });

  it('renders its blocks when the value is present', async () => {
    mount({
      entries: {'uid:block-a': {type: 'newType'}},
      sortOrder: ['uid:block-a'],
    });
    await nextTick();

    expect(container!.querySelectorAll('[data-matrix-block]').length).toBe(1);
  });
});
