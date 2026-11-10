import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import ReadOnlyElementIndex from './ReadOnlyElementIndex.vue';

const post = vi.hoisted(() => vi.fn());
const picker = vi.hoisted(() => ({props: null as Record<string, any> | null}));

vi.mock('@craftcms/ui', async (importOriginal) => ({
  ...(await importOriginal<typeof import('@craftcms/ui')>()),
  actionClient: {post},
}));

vi.mock('./ElementPicker.vue', async () => {
  const {defineComponent: define, h: create} = await import('vue');

  return {
    default: define({
      name: 'ElementPickerStub',
      props: ['action', 'initial', 'params', 'readOnly', 'storageKey'],
      setup(props) {
        return () => {
          picker.props = props as Record<string, any>;
          return create('div', {class: 'picker-stub'});
        };
      },
    }),
  };
});

const response = (marker: string) => ({data: {props: {marker}}});

let host: HTMLElement;
let app: ReturnType<typeof createApp>;
const criteria = ref<Record<string, unknown>>({customerId: 1});

async function flush() {
  await nextTick();
  await Promise.resolve();
  await nextTick();
}

function mount(extra: Record<string, unknown> = {}) {
  app = createApp(
    defineComponent({
      render: () =>
        h(ReadOnlyElementIndex, {
          elementType: 'Order',
          criteria: criteria.value,
          storageKey: 'orders',
          ...extra,
        }),
    })
  );
  app.mount(host);
}

beforeEach(() => {
  post.mockReset();
  picker.props = null;
  criteria.value = {customerId: 1};
  host = document.createElement('div');
  document.body.append(host);
});

afterEach(() => {
  app.unmount();
  host.remove();
});

describe('ReadOnlyElementIndex', () => {
  it('requests a read-only index for the criteria and hands the payload to the picker', async () => {
    post.mockResolvedValue(response('first'));
    mount();
    await flush();

    expect(post).toHaveBeenCalledWith('element-selector-modals/body', {
      context: 'modal',
      elementType: 'Order',
      criteria: {customerId: 1},
      readOnly: true,
    });
    expect(picker.props).toMatchObject({
      initial: {marker: 'first'},
      readOnly: '',
      storageKey: 'orders',
    });
  });

  it('asks for the given source', async () => {
    post.mockResolvedValue(response('first'));
    mount({source: 'volume:abc'});
    await flush();

    expect(post.mock.lastCall?.[1]).toMatchObject({source: 'volume:abc'});
  });

  it('reloads when the criteria change, dropping a superseded response', async () => {
    let resolveFirst!: (value: unknown) => void;
    post.mockImplementationOnce(
      () => new Promise((resolve) => (resolveFirst = resolve))
    );
    post.mockResolvedValueOnce(response('second'));
    mount();
    await flush();

    criteria.value = {customerId: 2};
    await flush();
    resolveFirst(response('stale'));
    await flush();

    expect(post).toHaveBeenCalledTimes(2);
    expect(post.mock.lastCall?.[1]).toMatchObject({
      criteria: {customerId: 2},
    });
    expect(picker.props?.initial).toEqual({marker: 'second'});
    expect(picker.props?.params.criteria).toEqual({customerId: 2});
  });

  it('shows an error with a retry when the request fails', async () => {
    post.mockRejectedValueOnce(new Error('offline'));
    post.mockResolvedValueOnce(response('recovered'));
    mount();
    await flush();

    expect(host.querySelector('[role="alert"]')).not.toBeNull();
    expect(picker.props).toBeNull();

    (host.querySelector('craft-button') as HTMLElement).click();
    await flush();

    expect(host.querySelector('[role="alert"]')).toBeNull();
    expect(picker.props?.initial).toEqual({marker: 'recovered'});
  });
});
