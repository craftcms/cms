import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  AssetSelectorController,
  ElementSelectorController,
  type AssetSelectorOptions,
  type ElementInfo,
} from '@craftcms/ui';

/**
 * The real index pulls the whole element-index component tree and its
 * composables; this stub stands in for it so the test is about the wiring —
 * controller to chrome, index to controller — and nothing else.
 *
 * It keeps the real component's contract: emits `selection-change` / `choose`,
 * exposes `clearSelection`, takes `disabled-element-ids`.
 */
const stub = vi.hoisted(() => ({
  clearSelection: vi.fn(),
  lastProps: null as Record<string, any> | null,
  emit: null as ((event: string, payload?: unknown) => void) | null,
}));

const menu = vi.hoisted(() => ({
  actions: null as any[] | null,
}));

vi.mock('@/common/components/ActionMenu.vue', async () => {
  const {defineComponent: define, h: create} = await import('vue');

  return {
    default: define({
      name: 'ActionMenuStub',
      props: ['actions', 'label'],
      setup(props) {
        // Read inside render so each re-render republishes the current list.
        return () => {
          menu.actions = props.actions as any[];
          return create('div', {class: 'action-menu-stub'});
        };
      },
    }),
  };
});

vi.mock('./ModalElementIndex.vue', async () => {
  const {defineComponent: define, h: create} = await import('vue');

  return {
    default: define({
      name: 'ModalElementIndexStub',
      props: ['action', 'initial', 'params', 'disabledElementIds'],
      emits: ['selection-change', 'choose'],
      setup(props, {emit, expose}) {
        stub.lastProps = props as Record<string, any>;
        stub.emit = emit as (event: string, payload?: unknown) => void;
        expose({clearSelection: stub.clearSelection});
        return () => create('div', {class: 'index-stub'});
      },
    }),
  };
});

function element(id: number): ElementInfo {
  return {
    id,
    siteId: 1,
    label: `Element ${id}`,
    status: null,
    url: null,
    hasThumb: false,
  };
}

function controller(options = {}) {
  return new ElementSelectorController({
    elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    modalTitle: 'Choose an entry',
    hideOnSelect: false,
    loadIndexBody: async () => ({html: '', props: {total: 2}}),
    ...options,
  });
}

async function mountModal(instance: ElementSelectorController) {
  const {default: ElementSelectorModal} =
    await import('./ElementSelectorModal.vue');

  const app = createApp(
    defineComponent({
      setup: () => () => h(ElementSelectorModal, {controller: instance}),
    })
  );
  app.config.compilerOptions.isCustomElement = (tag: string) =>
    tag.includes('-');

  const host = document.createElement('div');
  document.body.append(host);
  app.mount(host);
  await nextTick();

  return {
    host,
    unmount: () => app.unmount(),
    modal: () =>
      host.querySelector('craft-element-selector-modal') as HTMLElement & {
        controller: ElementSelectorController | null;
      },
  };
}

beforeEach(() => {
  document.body.innerHTML = '';
  stub.clearSelection.mockClear();
  stub.lastProps = null;
  stub.emit = null;
  menu.actions = null;
});

afterEach(() => {
  document.body.innerHTML = '';
});

describe('ElementSelectorModal', () => {
  it('hands the controller to the web component as a property', async () => {
    const instance = controller();
    const {modal, unmount} = await mountModal(instance);

    expect(modal().controller).toBe(instance);
    unmount();
  });

  it('holds the index back until the body has loaded', async () => {
    const instance = controller();
    const {host, unmount} = await mountModal(instance);

    expect(host.querySelector('.index-stub')).toBeNull();

    await instance.open();
    await nextTick();

    expect(host.querySelector('.index-stub')).not.toBeNull();
    unmount();
  });

  it('passes the action, payload and params to the index', async () => {
    const instance = controller();
    const {unmount} = await mountModal(instance);

    await instance.open();
    await nextTick();

    expect(stub.lastProps!.action).toBe('element-selector-modals/body');
    expect(stub.lastProps!.initial).toEqual({total: 2});
    expect(stub.lastProps!.params).toMatchObject({
      context: 'modal',
      elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
    });
    unmount();
  });

  it('routes the index’s selection into the controller', async () => {
    const instance = controller();
    const {unmount} = await mountModal(instance);
    await instance.open();
    await nextTick();

    stub.emit!('selection-change', [element(1)]);
    await nextTick();

    expect(instance.state.selection.map((e) => e.id)).toEqual([1]);
    expect(instance.state.canSubmit).toBe(true);
    unmount();
  });

  it('routes a double-click choose into a submit', async () => {
    const onSelect = vi.fn();
    const instance = controller({onSelect});
    const {unmount} = await mountModal(instance);
    await instance.open();
    await nextTick();

    stub.emit!('selection-change', [element(1)]);
    await nextTick();
    stub.emit!('choose');
    await nextTick();

    expect(onSelect).toHaveBeenCalledTimes(1);
    unmount();
  });

  it('republishes disabled ids to the index reactively', async () => {
    // The bug this whole seam exists to fix: the old modal passed these once by
    // value and the index never saw an update.
    const instance = controller({disabledElementIds: [1]});
    const {unmount} = await mountModal(instance);
    await instance.open();
    await nextTick();

    expect(stub.lastProps!.disabledElementIds).toEqual([1]);

    instance.setDisabledElementIds([2, 3]);
    await nextTick();

    expect(stub.lastProps!.disabledElementIds).toEqual([2, 3]);
    unmount();
  });

  it('registers the index so the controller can clear it', async () => {
    const instance = controller({disableElementsOnSelect: true});
    const {unmount} = await mountModal(instance);
    await instance.open();
    await nextTick();

    stub.emit!('selection-change', [element(1)]);
    await nextTick();
    await instance.submit();

    expect(stub.clearSelection).toHaveBeenCalled();
    expect(instance.state.disabledElementIds).toEqual([1]);
    unmount();
  });

  it('detaches the index on unmount', async () => {
    const instance = controller();
    const {unmount} = await mountModal(instance);
    await instance.open();
    await nextTick();
    expect(instance.index).not.toBeNull();

    unmount();

    expect(instance.index).toBeNull();
  });
});

describe('the transform menu', () => {
  function assetController(
    transforms: {handle: string; name: string}[],
    options: Partial<AssetSelectorOptions> = {}
  ) {
    return new AssetSelectorController({
      elementType: 'CraftCms\\Cms\\Asset\\Elements\\Asset',
      transforms,
      hideOnSelect: false,
      loadIndexBody: async () => ({html: '', props: {}}),
      fetchTransformUrl: async (id, handle) => `/t/${handle}/${id}.jpg`,
      ...options,
    });
  }

  it('is absent for a controller with no transforms', async () => {
    const {host, unmount} = await mountModal(controller());

    expect(host.querySelector('.action-menu-stub')).toBeNull();
    unmount();
  });

  it('is absent for an asset controller that was given none', async () => {
    const {host, unmount} = await mountModal(assetController([]));

    expect(host.querySelector('.action-menu-stub')).toBeNull();
    unmount();
  });

  it('offers one item per transform', async () => {
    const instance = assetController([
      {handle: 'thumb', name: 'Thumbnail'},
      {handle: 'large', name: 'Large'},
    ]);
    const {host, unmount} = await mountModal(instance);

    expect(host.querySelector('.action-menu-stub')).not.toBeNull();
    expect(menu.actions!.map((a) => a.label)).toEqual(['Thumbnail', 'Large']);
    unmount();
  });

  it('disables the items until something is selected', async () => {
    // The invoker is frozen under `v-once`, so this is where reactivity has to
    // land — a `disabled` on the invoker would never update.
    const instance = assetController([{handle: 'thumb', name: 'Thumbnail'}]);
    const {unmount} = await mountModal(instance);

    expect(menu.actions!.every((a) => a.disabled)).toBe(true);

    instance.setSelection([element(1)]);
    await nextTick();

    expect(menu.actions!.every((a) => a.disabled)).toBe(false);
    unmount();
  });

  it('submits with the chosen transform applied', async () => {
    const onSelect = vi.fn();
    const instance = assetController([{handle: 'thumb', name: 'Thumbnail'}], {
      onSelect,
    });
    const {unmount} = await mountModal(instance);

    instance.setSelection([element(1)]);
    await nextTick();
    await menu.actions![0]!.onClick();

    expect(onSelect).toHaveBeenCalledTimes(1);
    expect(onSelect.mock.calls[0]![1]).toEqual({transform: 'thumb'});
    expect(onSelect.mock.calls[0]![0][0].url).toBe('/t/thumb/1.jpg');
    unmount();
  });
});
