import {createApp, defineComponent, h, nextTick} from 'vue';
import {describe, expect, it, vi} from 'vite-plus/test';
import {ElementSelectorController, type ElementInfo} from '@craftcms/ui';
import {useElementSelectorController} from './useElementSelectorController';

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
    loadIndexBody: async () => ({html: '', props: {total: 1}}),
    ...options,
  });
}

/** Mounts the composable and hands back what it returned, plus a disposer. */
function mountComposable(instance: ElementSelectorController) {
  let api!: ReturnType<typeof useElementSelectorController>;

  const app = createApp(
    defineComponent({
      setup() {
        api = useElementSelectorController(instance);
        return () => h('div');
      },
    })
  );

  const host = document.createElement('div');
  document.body.append(host);
  app.mount(host);

  return {api, unmount: () => app.unmount()};
}

describe('useElementSelectorController', () => {
  it('starts from the controller’s current state', () => {
    const instance = controller({modalTitle: 'Choose'});
    const {api, unmount} = mountComposable(instance);

    expect(api.state.value.title).toBe('Choose');
    expect(api.open.value).toBe(false);
    unmount();
  });

  it('tracks changes as they are emitted', async () => {
    const instance = controller();
    const {api, unmount} = mountComposable(instance);

    instance.setSelection([element(1)]);
    await nextTick();

    expect(api.canSubmit.value).toBe(true);
    expect(api.selection.value.map((e) => e.id)).toEqual([1]);
    unmount();
  });

  it('exposes the loaded index body', async () => {
    const instance = controller();
    const {api, unmount} = mountComposable(instance);

    await instance.open();
    await nextTick();

    expect(api.open.value).toBe(true);
    expect(api.indexBody.value?.props).toEqual({total: 1});
    unmount();
  });

  it('re-reads disabled ids when the opener republishes them', async () => {
    // The old modal copied this into the index once by value, so later updates
    // never reached it.
    const instance = controller({disabledElementIds: [1]});
    const {api, unmount} = mountComposable(instance);

    expect(api.disabledElementIds.value).toEqual([1]);

    instance.setDisabledElementIds([2, 3]);
    await nextTick();

    expect(api.disabledElementIds.value).toEqual([2, 3]);
    unmount();
  });

  it('does not wrap the controller’s objects in a reactive proxy', () => {
    // A shallowRef of a frozen snapshot: the object a Vue template reads is the
    // same one the web component reads.
    const instance = controller();
    const {api, unmount} = mountComposable(instance);

    expect(api.state.value).toBe(instance.state);
    unmount();
  });

  it('forwards submit and cancel', async () => {
    const onSelect = vi.fn();
    const onCancel = vi.fn();
    const instance = controller({onSelect, onCancel, hideOnSelect: false});
    const {api, unmount} = mountComposable(instance);

    instance.setSelection([element(1)]);
    await nextTick();
    api.submit();
    await nextTick();
    expect(onSelect).toHaveBeenCalledTimes(1);

    await instance.open();
    api.cancel();
    expect(onCancel).toHaveBeenCalledTimes(1);
    unmount();
  });

  it('unsubscribes on unmount', async () => {
    const instance = controller();
    const {api, unmount} = mountComposable(instance);
    const before = api.state.value;

    unmount();
    instance.setSelection([element(1)]);
    await nextTick();

    expect(api.state.value).toBe(before);
  });
});
