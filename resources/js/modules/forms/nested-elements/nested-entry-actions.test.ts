import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import type {NestedEntry} from './nested-entries';
import {
  nestedEntryActions,
  useNestedEntryActionEvents,
} from './nested-entry-actions';
import {
  DELETE_ACTION,
  nestedElementAction,
  nestedEntry,
  standardElementAction,
} from './nested-index.fixture';

it('disables a row action when any selected entry lacks its capability', () => {
  const deleteAction = standardElementAction(
    DELETE_ACTION,
    'Delete',
    'deletable',
    'http'
  );
  const first = nestedEntry(11, {
    actionMenuItems: [nestedElementAction(11, deleteAction)],
  });
  const permittedSecond = nestedEntry(12);
  const forbiddenSecond = nestedEntry(12, {
    capabilities: {
      copyable: true,
      duplicatable: true,
      deletable: false,
    },
  });
  const options = {
    entry: first,
    ids: [11, 12],
    index: 0,
    count: 2,
    editable: true,
    busy: false,
    canPaste: false,
    canReorder: false,
    canAdd: () => true,
  };
  const permittedActions = nestedEntryActions({
    ...options,
    selectedEntries: [first, permittedSecond],
  });
  const forbiddenActions = nestedEntryActions({
    ...options,
    selectedEntries: [first, forbiddenSecond],
  });

  expect(permittedActions).toHaveLength(1);
  expect(
    permittedActions[0] &&
      'disabled' in permittedActions[0] &&
      permittedActions[0].disabled
  ).toBe(false);
  expect(forbiddenActions).toHaveLength(1);
  expect(
    forbiddenActions[0] &&
      'disabled' in forbiddenActions[0] &&
      forbiddenActions[0].disabled
  ).toBe(true);
});

describe('useNestedEntryActionEvents', () => {
  let app: ReturnType<typeof createApp> | undefined;
  let root: HTMLElement | undefined;

  afterEach(() => {
    app?.unmount();
    root?.remove();
  });

  function mount(options: {editable: boolean; busy: boolean}) {
    const editable = ref(options.editable);
    const busy = ref(options.busy);
    const handlers = {
      perform: vi.fn(),
      paste: vi.fn(),
      move: vi.fn(),
    };
    const entries = [{id: 11}, {id: 12}] as NestedEntry[];

    root = document.createElement('div');
    document.body.append(root);
    app = createApp({
      setup() {
        const container = ref<HTMLElement>();
        useNestedEntryActionEvents(container, {
          entries,
          actionIds: () => [11, 12],
          editable,
          busy,
          handlers,
        });

        return () => h('div', {ref: container}, [h('button')]);
      },
    });
    app.mount(root);

    return {editable, busy, handlers};
  }

  function dispatch(
    action: string,
    trigger: HTMLElement,
    item?: {
      key: string;
      label: string;
      action: {type: 'event'; name: string};
    }
  ): void {
    window.dispatchEvent(
      new CustomEvent('craft:nested-element-action', {
        detail: {action, elementId: 11, trigger, item},
      })
    );
  }

  it('scopes an action to its container and dispatches the active selection', async () => {
    const {handlers} = mount({editable: true, busy: false});
    await nextTick();

    const item = {
      key: 'Delete',
      label: 'Delete',
      action: {type: 'event' as const, name: 'test:delete'},
    };
    const trigger = root!.querySelector('button')!;
    dispatch('element-action', trigger, item);
    dispatch('element-action', document.createElement('button'), item);

    expect(handlers.perform).toHaveBeenCalledOnce();
    expect(handlers.perform).toHaveBeenCalledWith(item, [11, 12], trigger);
  });

  it('allows copy while busy or read-only and blocks mutation actions', async () => {
    const {busy, editable, handlers} = mount({editable: false, busy: true});
    await nextTick();
    const trigger = root!.querySelector('button')!;

    dispatch('element-action', trigger, {
      key: 'Copy',
      label: 'Copy',
      action: {type: 'event', name: 'craft:copy-elements'},
    });
    dispatch('element-action', trigger, {
      key: 'Duplicate',
      label: 'Duplicate',
      action: {type: 'event', name: 'test:duplicate'},
    });
    busy.value = false;
    dispatch('paste', trigger);
    editable.value = true;
    dispatch('move-backward', trigger);

    expect(handlers.perform).toHaveBeenCalledOnce();
    expect(handlers.paste).not.toHaveBeenCalled();
    expect(handlers.move).toHaveBeenCalledWith(0, 1);
  });
});
