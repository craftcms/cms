import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, inject, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import UiModal from './UiModal.vue';
import {UiTableRefresh} from './runtime';

const renderer = vi.hoisted(() => ({
  refreshTable: undefined as (() => void) | undefined,
}));

vi.mock('@/common/components/ModalForm.vue', () => ({
  default: defineComponent({
    props: ['isActive', 'title', 'submitLabel', 'loading', 'width'],
    emits: ['submit', 'close'],
    setup(props, {slots, emit}) {
      return () => {
        return h(
          'form',
          {onSubmit: (e: Event) => (e.preventDefault(), emit('submit'))},
          [
            h('h1', props.title),
            slots.default?.(),
            h('button', {type: 'submit'}, props.submitLabel),
          ]
        );
      };
    },
  }),
}));

vi.mock('./UiRenderer.vue', () => ({
  default: defineComponent({
    props: ['payload', 'errors'],
    setup(_props, {expose}) {
      renderer.refreshTable = inject(UiTableRefresh);
      expose({
        canSubmit: () => true,
        currentValues: () => ({details: {a: {accept: '2'}}}),
      });
      return () => h('div');
    },
  }),
}));

const flush = async () => {
  await Promise.resolve();
  await nextTick();
  await nextTick();
};

describe('UiModal', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const get = vi.spyOn(actionClient, 'get');
  const post = vi.spyOn(actionClient, 'post');
  const submitted: Array<Record<string, unknown>> = [];

  function mount(props: Record<string, unknown> = {}): void {
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() =>
      h(UiModal, {
        modalUrl: 'things/receive-modal',
        actionUrl: 'things/receive',
        params: {thingId: 4},
        onSubmitted: (data: Record<string, unknown>) => submitted.push(data),
        ...props,
      })
    );
    app.mount(container);
  }

  beforeEach(() => {
    (window as any).Craft = {
      cp: {displayError: vi.fn(), displayNotice: vi.fn()},
    };
    get.mockResolvedValue({data: {ui: {nodes: [], values: {}}}});
    submitted.length = 0;
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    vi.clearAllMocks();
  });

  it('loads the UI with its params and falls back to its own labels', async () => {
    mount({title: 'Receive', submitLabel: 'Receive now'});
    await flush();

    expect(get).toHaveBeenCalledWith('things/receive-modal', {
      params: {thingId: 4},
    });
    expect(container.querySelector('h1')?.textContent).toBe('Receive');
    expect(container.querySelector('button')?.textContent).toBe('Receive now');
  });

  it('posts the UI values with its params, then reports the response', async () => {
    post.mockResolvedValue({data: {message: 'Received', ok: true}});
    mount();
    await flush();

    container.querySelector('form')!.dispatchEvent(new Event('submit'));
    await flush();

    expect(post).toHaveBeenCalledWith('things/receive', {
      details: {a: {accept: '2'}},
      thingId: 4,
    });
    expect((window as any).Craft.cp.displayNotice).toHaveBeenCalledWith(
      'Received'
    );
    expect(submitted).toEqual([{message: 'Received', ok: true}]);
  });

  it('closes when the UI can’t be loaded', async () => {
    const closed = vi.fn();
    get.mockRejectedValue({response: {data: {message: 'Not found'}}});
    mount({onClose: closed});
    await flush();

    expect((window as any).Craft.cp.displayError).toHaveBeenCalledWith(
      'Not found'
    );
    expect(closed).toHaveBeenCalled();
  });

  it('holds submission until a table within it changes what can be submitted', async () => {
    get.mockResolvedValueOnce({
      data: {ui: {nodes: [], values: {}}, submitDisabled: true},
    });
    mount();
    await flush();

    container.querySelector('form')!.dispatchEvent(new Event('submit'));
    await flush();
    expect(post).not.toHaveBeenCalled();

    renderer.refreshTable?.();
    await flush();
    container.querySelector('form')!.dispatchEvent(new Event('submit'));
    await flush();

    expect(get).toHaveBeenCalledTimes(2);
    expect(post).toHaveBeenCalledOnce();
  });
});
