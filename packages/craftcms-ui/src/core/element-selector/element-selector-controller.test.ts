import {describe, expect, it, vi} from 'vite-plus/test';
import {ElementSelectorController} from './element-selector-controller.js';
import type {
  ElementIndexAdapter,
  ElementIndexBody,
  ElementInfo,
  ElementSelectorOptions,
} from './types.js';

const ENTRY = 'CraftCms\\Cms\\Entry\\Elements\\Entry';

function element(id: number, extra: Partial<ElementInfo> = {}): ElementInfo {
  return {
    id,
    siteId: 1,
    label: `Element ${id}`,
    status: 'live',
    url: `/entry-${id}`,
    hasThumb: false,
    ...extra,
  };
}

function body(props: Record<string, unknown> = {}): ElementIndexBody {
  return {html: '<div class="element-index"></div>', props};
}

function create(options: Partial<ElementSelectorOptions> = {}) {
  return new ElementSelectorController({
    elementType: ENTRY,
    loadIndexBody: async () => body(),
    ...options,
  });
}

function stubIndex() {
  // `satisfies` rather than an annotation: it checks the shape against the
  // adapter contract while keeping the Mock types the assertions need.
  return {
    clearSelection: vi.fn(),
    destroy: vi.fn(),
  } satisfies ElementIndexAdapter;
}

describe('options', () => {
  it('falls back to defaults', () => {
    const controller = create();

    expect(controller.options.bodyAction).toBe('element-selector-modals/body');
    expect(controller.options.hideOnSelect).toBe(true);
    expect(controller.options.multiSelect).toBe(false);
  });

  it('does not let explicitly-undefined keys shadow defaults', () => {
    // Callers build settings by spreading partials, which routinely produces
    // `{hideOnSelect: undefined}`.
    const controller = create({
      hideOnSelect: undefined,
      multiSelect: undefined,
    });

    expect(controller.options.hideOnSelect).toBe(true);
    expect(controller.options.multiSelect).toBe(false);
  });

  it('resolves title and select label lazily, not at module load', () => {
    const controller = create();

    expect(controller.options.modalTitle).toBeTruthy();
    expect(controller.options.selectBtnLabel).toBeTruthy();
    expect(create({modalTitle: 'Choose'}).options.modalTitle).toBe('Choose');
  });
});

describe('indexParams', () => {
  it('identifies the index', () => {
    const controller = create({sources: ['section:a'], condition: {x: 1}});

    expect(controller.indexParams()).toEqual({
      context: 'modal',
      elementType: ENTRY,
      sources: ['section:a'],
      condition: {x: 1},
    });
  });

  it.each([
    ['null', null, false],
    ['auto', 'auto' as const, false],
  ])('omits showSiteMenu when %s', (_label, showSiteMenu, present) => {
    expect('showSiteMenu' in create({showSiteMenu}).indexParams()).toBe(
      present
    );
  });

  it.each([
    [true, '1'],
    [false, '0'],
  ])('serializes showSiteMenu %s as %s', (showSiteMenu, expected) => {
    expect(create({showSiteMenu}).indexParams().showSiteMenu).toBe(expected);
  });

  it('includes siteIds only when set', () => {
    expect('siteIds' in create().indexParams()).toBe(false);
    expect(create({siteIds: [1, 2]}).indexParams().siteIds).toEqual([1, 2]);
  });
});

describe('selection and canSubmit', () => {
  it('cannot submit with nothing selected', () => {
    expect(create().state.canSubmit).toBe(false);
  });

  it('can submit once something is selected', () => {
    const controller = create();
    controller.setSelection([element(1)]);

    expect(controller.state.canSubmit).toBe(true);
    expect(controller.hasSelection).toBe(true);
  });

  it('cannot submit while loading', async () => {
    let release: () => void = () => {};
    const controller = create({
      loadIndexBody: () =>
        new Promise<ElementIndexBody>((resolve) => {
          release = () => resolve(body());
        }),
    });

    const pending = controller.reload();
    controller.setSelection([element(1)]);
    expect(controller.state.loading).toBe(true);
    expect(controller.state.canSubmit).toBe(false);

    release();
    await pending;
    expect(controller.state.canSubmit).toBe(true);
  });

  it('emits change when the selection changes', () => {
    const controller = create();
    const seen: number[] = [];
    controller.on('change', (state) => seen.push(state.selection.length));

    controller.setSelection([element(1)]);
    controller.setSelection([]);

    expect(seen).toEqual([1, 0]);
  });

  it('hands out a frozen snapshot rather than live internals', () => {
    const controller = create();
    controller.setSelection([element(1)]);

    const {state} = controller;
    expect(Object.isFrozen(state)).toBe(true);
    controller.setSelection([element(1), element(2)]);
    expect(state.selection).toHaveLength(1);
    expect(controller.state.selection).toHaveLength(2);
  });
});

describe('busy', () => {
  it('is held for the duration of an async onSelect', async () => {
    let release: () => void = () => {};
    const controller = create({
      hideOnSelect: false,
      onSelect: () =>
        new Promise<void>((resolve) => {
          release = resolve;
        }),
    });
    controller.setSelection([element(1)]);

    const pending = controller.submit();
    expect(controller.state.busy).toBe(true);
    expect(controller.state.canSubmit).toBe(false);
    expect(controller.state.canCancel).toBe(false);

    release();
    await pending;

    expect(controller.state.busy).toBe(false);
    expect(controller.state.canCancel).toBe(true);
  });

  it('is released when onSelect throws', async () => {
    // The old modal disabled four things by hand and re-enabled them only on
    // the success path, so a throw left the chrome stuck for good.
    const controller = create({
      onSelect: () => Promise.reject(new Error('render-elements failed')),
    });
    const errors: Error[] = [];
    controller.on('error', (error) => errors.push(error));
    controller.setSelection([element(1)]);

    await controller.submit();

    expect(controller.state.busy).toBe(false);
    expect(controller.state.canCancel).toBe(true);
    expect(errors.map((e) => e.message)).toEqual(['render-elements failed']);
    expect(controller.state.error?.message).toBe('render-elements failed');
  });

  it('ignores a second submit while one is in flight', async () => {
    let release: () => void = () => {};
    const onSelect = vi.fn(
      () =>
        new Promise<void>((resolve) => {
          release = resolve;
        })
    );
    const controller = create({hideOnSelect: false, onSelect});
    controller.setSelection([element(1)]);

    const first = controller.submit();
    await controller.submit();
    release();
    await first;

    expect(onSelect).toHaveBeenCalledTimes(1);
  });
});

describe('submit', () => {
  it('does nothing with an empty selection', async () => {
    const onSelect = vi.fn();
    await create({onSelect}).submit();

    expect(onSelect).not.toHaveBeenCalled();
  });

  it('hands the selection to onSelect and emits select', async () => {
    const onSelect = vi.fn();
    const controller = create({onSelect});
    const selected: ElementInfo[][] = [];
    controller.on('select', ({elements}) => selected.push(elements));

    controller.setSelection([element(1), element(2)]);
    await controller.submit();

    expect(onSelect).toHaveBeenCalledTimes(1);
    expect(onSelect.mock.calls[0]![0].map((e: ElementInfo) => e.id)).toEqual([
      1, 2,
    ]);
    expect(selected[0]!.map((e) => e.id)).toEqual([1, 2]);
  });

  it('preserves extra row data such as kind and alt', async () => {
    const onSelect = vi.fn();
    const controller = create({onSelect});
    controller.setSelection([element(1, {kind: 'image', alt: 'A cat'})]);

    await controller.submit();

    expect(onSelect.mock.calls[0]![0][0]).toMatchObject({
      kind: 'image',
      alt: 'A cat',
    });
  });

  it('copies elements rather than handing out the internal objects', async () => {
    const onSelect = vi.fn();
    const controller = create({onSelect});
    const original = element(1);
    controller.setSelection([original]);

    await controller.submit();

    expect(onSelect.mock.calls[0]![0][0]).not.toBe(original);
  });

  it('closes when hideOnSelect is set', async () => {
    const controller = create({hideOnSelect: true});
    await controller.open();
    controller.setSelection([element(1)]);

    await controller.submit();

    expect(controller.state.open).toBe(false);
  });

  it('stays open when hideOnSelect is off', async () => {
    const controller = create({hideOnSelect: false});
    await controller.open();
    controller.setSelection([element(1)]);

    await controller.submit();

    expect(controller.state.open).toBe(true);
  });

  describe('disableElementsOnSelect', () => {
    it('adds the selection to the disabled set and clears the index', async () => {
      const controller = create({
        disableElementsOnSelect: true,
        hideOnSelect: false,
      });
      const index = stubIndex();
      controller.attachIndex(index);
      controller.setSelection([element(1), element(2)]);

      await controller.submit();

      expect(controller.state.disabledElementIds).toEqual([1, 2]);
      expect(index.clearSelection).toHaveBeenCalled();
    });

    it('accumulates across submissions without duplicating', async () => {
      const controller = create({
        disableElementsOnSelect: true,
        hideOnSelect: false,
        disabledElementIds: [9],
      });
      controller.setSelection([element(1)]);
      await controller.submit();
      controller.setSelection([element(1), element(2)]);
      await controller.submit();

      expect(controller.state.disabledElementIds).toEqual([9, 1, 2]);
    });

    it('leaves the disabled set alone when off', async () => {
      const controller = create({hideOnSelect: false});
      controller.setSelection([element(1)]);

      await controller.submit();

      expect(controller.state.disabledElementIds).toEqual([]);
    });
  });
});

describe('cancel', () => {
  it('fires onCancel and closes', async () => {
    // The legacy modal declared `onCancel` and never called it.
    const onCancel = vi.fn();
    const controller = create({onCancel});
    await controller.open();

    controller.cancel();

    expect(onCancel).toHaveBeenCalledTimes(1);
    expect(controller.state.open).toBe(false);
  });

  it('emits cancel', async () => {
    const controller = create();
    const seen = vi.fn();
    controller.on('cancel', seen);
    await controller.open();

    controller.cancel();

    expect(seen).toHaveBeenCalledTimes(1);
  });

  it('is refused while busy', async () => {
    const onCancel = vi.fn();
    const controller = create({
      onCancel,
      hideOnSelect: false,
      onSelect: () => new Promise<void>(() => {}),
    });
    await controller.open();
    controller.setSelection([element(1)]);
    void controller.submit();

    controller.cancel();

    expect(onCancel).not.toHaveBeenCalled();
    expect(controller.state.open).toBe(true);
  });
});

describe('setDisabledElementIds', () => {
  it('emits change, so a bound index re-reads it', () => {
    // The old modal assigned to a settings array the index had already copied
    // by value, so the index never saw the new set.
    const controller = create();
    const seen: readonly number[][] = [];
    controller.on('change', (state) =>
      (seen as number[][]).push([...state.disabledElementIds])
    );

    controller.setDisabledElementIds([3, 4]);

    expect(seen).toEqual([[3, 4]]);
    expect(controller.state.disabledElementIds).toEqual([3, 4]);
  });

  it('de-duplicates and coerces to numbers', () => {
    const controller = create();
    controller.setDisabledElementIds([1, 1, 2, '3' as unknown as number]);

    expect(controller.state.disabledElementIds).toEqual([1, 2, 3]);
  });

  it('seeds from options', () => {
    expect(create({disabledElementIds: [5]}).state.disabledElementIds).toEqual([
      5,
    ]);
  });

  it('does not mutate the caller’s array', async () => {
    const ids = [1];
    const controller = create({
      disabledElementIds: ids,
      disableElementsOnSelect: true,
      hideOnSelect: false,
    });
    controller.setSelection([element(2)]);

    await controller.submit();

    expect(ids).toEqual([1]);
  });
});

describe('open and close', () => {
  it('loads the index body once', async () => {
    const loadIndexBody = vi.fn(async () => body({total: 3}));
    const controller = create({loadIndexBody});

    await controller.open();
    controller.close();
    await controller.open();

    expect(loadIndexBody).toHaveBeenCalledTimes(1);
    expect(controller.state.indexBody?.props).toEqual({total: 3});
  });

  it('passes the action and params to the loader', async () => {
    const loadIndexBody = vi.fn(async () => body());
    const controller = create({
      loadIndexBody,
      bodyAction: 'custom/body',
      sources: ['section:a'],
    });

    await controller.open();

    expect(loadIndexBody).toHaveBeenCalledWith('custom/body', {
      context: 'modal',
      elementType: ENTRY,
      sources: ['section:a'],
      condition: undefined,
    });
  });

  it('clears the selection on reopen but keeps disabled ids', async () => {
    // The relation field caches one instance and reopens it. A surviving
    // selection would leave Select enabled before the user picked anything.
    const controller = create({disabledElementIds: [7]});
    const index = stubIndex();
    controller.attachIndex(index);
    await controller.open();
    controller.setSelection([element(1)]);
    controller.close();

    await controller.open();

    expect(controller.state.selection).toEqual([]);
    expect(controller.state.canSubmit).toBe(false);
    expect(index.clearSelection).toHaveBeenCalled();
    expect(controller.state.disabledElementIds).toEqual([7]);
  });

  it('keeps the selection through close, so a submit handler can still read it', async () => {
    const controller = create();
    await controller.open();
    controller.setSelection([element(1)]);

    controller.close();

    expect(controller.state.selection).toHaveLength(1);
  });

  it('emits open and close once each', async () => {
    const controller = create();
    const events: string[] = [];
    controller.on('open', () => events.push('open'));
    controller.on('close', () => events.push('close'));

    await controller.open();
    await controller.open();
    controller.close();
    controller.close();

    expect(events).toEqual(['open', 'close']);
  });

  it('fires onClose when closing', async () => {
    const onClose = vi.fn();
    const controller = create({onClose});
    await controller.open();

    controller.close();

    expect(onClose).toHaveBeenCalledTimes(1);
  });

  it('records a load failure without throwing', async () => {
    const controller = create({
      loadIndexBody: async () => {
        throw new Error('nope');
      },
    });

    await controller.open();

    expect(controller.state.error?.message).toBe('nope');
    expect(controller.state.loading).toBe(false);
    expect(controller.state.indexBody).toBeNull();
  });
});

describe('destroy', () => {
  it('tears down the adapter and stops emitting', async () => {
    const controller = create();
    const index = stubIndex();
    const seen = vi.fn();
    controller.attachIndex(index);
    controller.on('change', seen);
    await controller.open();
    seen.mockClear();

    controller.destroy();
    const changesDuringDestroy = seen.mock.calls.length;
    controller.setSelection([element(1)]);

    expect(index.destroy).toHaveBeenCalled();
    expect(controller.index).toBeNull();
    expect(controller.state.open).toBe(false);
    expect(seen.mock.calls.length).toBe(changesDuringDestroy);
  });
});

describe('listeners', () => {
  it('unsubscribes through the returned function', () => {
    const controller = create();
    const seen = vi.fn();
    const off = controller.on('change', seen);

    controller.setSelection([element(1)]);
    off();
    controller.setSelection([]);

    expect(seen).toHaveBeenCalledTimes(1);
  });

  it('survives a listener unsubscribing itself mid-emit', () => {
    const controller = create();
    const order: string[] = [];
    const off = controller.on('change', () => {
      order.push('first');
      off();
    });
    controller.on('change', () => order.push('second'));

    controller.setSelection([element(1)]);

    expect(order).toEqual(['first', 'second']);
  });
});

describe('indexSettings', () => {
  it('carries the query and selection configuration', () => {
    const controller = create({
      storageKey: 'field-1',
      criteria: {status: 'live'},
      multiSelect: true,
      disabledElementIds: [4],
    });

    expect(controller.indexSettings()).toMatchObject({
      context: 'modal',
      storageKey: 'field-1',
      criteria: {status: 'live'},
      multiSelect: true,
      selectable: true,
      disabledElementIds: [4],
    });
  });

  it('lets indexSettings overrides win', () => {
    const controller = create({indexSettings: {multiSelect: 'overridden'}});

    expect(controller.indexSettings().multiSelect).toBe('overridden');
  });

  it('reflects the current disabled set, not the initial one', () => {
    const controller = create({disabledElementIds: [1]});
    controller.setDisabledElementIds([2, 3]);

    expect(controller.indexSettings().disabledElementIds).toEqual([2, 3]);
  });
});
