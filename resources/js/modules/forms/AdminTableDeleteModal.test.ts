import {actionClient} from '@craftcms/ui';
import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import AdminTableDeleteModal from './AdminTableDeleteModal.vue';

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

vi.mock('./FormRenderer.vue', () => ({
  default: defineComponent({
    props: ['payload', 'errors'],
    setup(props, {expose}) {
      expose({currentValues: () => ({destination: '3'})});
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
  const events: string[] = [];

  beforeEach(() => {
    (window as any).Craft = {
      cp: {displayError: vi.fn(), displayNotice: vi.fn()},
    };
    get.mockResolvedValue({
      data: {
        form: {nodes: [], values: {destination: '2'}},
        title: 'Deleting it',
        submitLabel: 'Remove',
      },
    });
    events.length = 0;
    container = document.createElement('div');
    document.body.append(container);
    app = createApp(() =>
      h(AdminTableDeleteModal, {
        modalUrl: 'things/delete-modal',
        deleteUrl: 'things/delete',
        rowId: 7,
        onDeleted: () => events.push('deleted'),
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

  it('loads the Form for the row and uses the returned labels', async () => {
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

  it('posts the Form values with the row id to the delete URL', async () => {
    post.mockResolvedValue({data: {}});
    await flush();

    container.querySelector('form')!.dispatchEvent(new Event('submit'));
    await flush();

    expect(post).toHaveBeenCalledWith('things/delete', {
      destination: '3',
      id: 7,
    });
    expect(events).toEqual(['deleted']);
  });

  it('shows validation errors against their control paths', async () => {
    post.mockRejectedValue({
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
