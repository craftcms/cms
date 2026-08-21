import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {ElementSelectorController, type ElementInfo} from '@craftcms/ui';

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
