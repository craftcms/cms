import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import AdminTableDeleteModal from './AdminTableDeleteModal.vue';
import UiModal from './UiModal.vue';

const modalProps = vi.hoisted(() => ({current: {} as Record<string, unknown>}));
const rendererProps = vi.hoisted(() => ({
  current: {} as Record<string, unknown>,
}));

vi.mock('@/common/components/ModalForm.vue', () => ({
  default: defineComponent({
    props: ['isActive', 'title', 'submitLabel', 'loading'],
    emits: ['submit', 'close'],
    setup(props, {slots, emit}) {
      return () => {
        modalProps.current = {...props};
        return h(
          'form',
          {
            'data-modal': '',
            onSubmit: (e: Event) => (e.preventDefault(), emit('submit')),
          },
          slots.default?.()
        );
      };
    },
  }),
}));

vi.mock('./UiRenderer.vue', () => ({
  default: defineComponent({
    props: ['payload', 'errors'],
    setup(props, {expose}) {
      expose({
        canSubmit: () => true,
        currentValues: () => ({destination: '3'}),
      });
      return () => {
        rendererProps.current = {...props};
        return h('div', {'data-renderer': ''});
      };
    },
  }),
}));

const flush = async () => {
  await Promise.resolve();
  await nextTick();
  await nextTick();
};

describe('AdminTableDeleteModal', () => {
  let app: ReturnType<typeof createApp>;
  let container: HTMLElement;
  const get = vi.spyOn(actionClient, 'get');
  const post = vi.spyOn(actionClient, 'post');
  const remove = vi.spyOn(actionClient, 'delete');
  const subject = ref<'deletion' | 'action'>('deletion');
  const events: string[] = [];

  beforeEach(() => {
    (window as any).Craft = {
      cp: {displayError: vi.fn(), displayNotice: vi.fn()},
    };
    get.mockResolvedValue({
      data: {
        ui: {nodes: [], values: {destination: '2'}},
        title: 'Deleting it',
        submitLabel: 'Remove',
      },
    });
    events.length = 0;
    subject.value = 'deletion';
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() =>
      subject.value === 'deletion'
        ? h(AdminTableDeleteModal, {
            modalUrl: 'things/delete-modal',
            deleteUrl: 'things/delete',
            rowId: 7,
            onDeleted: () => events.push('deleted'),
            onClose: () => events.push('close'),
          })
        : h(UiModal, {
            modalUrl: 'things/edit-modal',
            actionUrl: 'things/save',
            params: {id: 7},
            onSubmitted: () => events.push('submitted'),
            onClose: () => events.push('close'),
          })
    );
    app.mount(container);
  });

  afterEach(() => {
    app.unmount();
    container.remove();
    vi.clearAllMocks();
  });

  it('loads the UI for the row and uses the returned labels', async () => {
    await flush();

    expect(get).toHaveBeenCalledWith('things/delete-modal', {params: {id: 7}});
    expect(modalProps.current).toMatchObject({
      isActive: true,
      title: 'Deleting it',
      submitLabel: 'Remove',
    });
    expect(rendererProps.current.payload).toEqual({
      nodes: [],
      values: {destination: '2'},
    });
  });

  it.each([
    {
      kind: 'deletion',
      method: 'delete',
      url: 'things/delete',
      event: 'deleted',
    },
    {kind: 'action', method: 'post', url: 'things/save', event: 'submitted'},
  ] as const)(
    'submits $kind UI values and row id using $method',
    async ({kind, method, url, event}) => {
      subject.value = kind;
      post.mockResolvedValue({data: {}});
      remove.mockResolvedValue({data: {}});
      await flush();

      container.querySelector('form')!.dispatchEvent(new Event('submit'));
      await flush();

      const values = {destination: '3', id: 7};
      if (method === 'delete') {
        expect(remove).toHaveBeenCalledWith(url, {data: values});
        expect(post).not.toHaveBeenCalled();
      } else {
        expect(post).toHaveBeenCalledWith(url, values);
        expect(remove).not.toHaveBeenCalled();
      }
      expect(events).toEqual([event]);
    }
  );

  it('shows validation errors against their control paths', async () => {
    remove.mockRejectedValue({
      response: {
        data: {message: 'Nope', errors: {'address.line1': ['Required']}},
      },
    });
    await flush();

    container.querySelector('form')!.dispatchEvent(new Event('submit'));
    await flush();

    expect(rendererProps.current.errors).toEqual([
      {path: ['address', 'line1'], messages: ['Required']},
    ]);
    expect((window as any).Craft.cp.displayError).toHaveBeenCalledWith('Nope');
    expect(events).toEqual([]);
  });
});
