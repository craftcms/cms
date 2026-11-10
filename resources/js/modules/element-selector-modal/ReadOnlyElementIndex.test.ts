import {createApp, defineComponent, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import ReadOnlyElementIndex from './ReadOnlyElementIndex.vue';

const post = vi.hoisted(() => vi.fn());
const picker = vi.hoisted(() => ({
  props: null as Record<string, any> | null,
  emit: null as ((event: string, payload?: unknown) => void) | null,
}));

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
      emits: ['error'],
      setup(props, {emit}) {
        picker.emit = emit as (event: string, payload?: unknown) => void;

        return () => {
          picker.props = props as Record<string, any>;
          return create('div', {class: 'picker-stub'}, [create('input')]);
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

  it('shows the error and retry when the picker’s row request fails', async () => {
    post.mockResolvedValue(response('first'));
    mount();
    await flush();

    picker.emit?.('error', new Error('offline'));
    await flush();

    expect(host.querySelector('[role="alert"]')).not.toBeNull();

    (host.querySelector('craft-button') as HTMLElement).click();
    await flush();

    expect(post).toHaveBeenCalledTimes(2);
    expect(host.querySelector('[role="alert"]')).toBeNull();
  });

  it('moves focus to the retry button on failure and back into the index after retrying', async () => {
    post.mockResolvedValue(response('first'));
    mount();
    await flush();
    (host.querySelector('input') as HTMLElement).focus();

    picker.emit?.('error', new Error('offline'));
    await flush();

    expect(document.activeElement).toBe(host.querySelector('craft-button'));

    (host.querySelector('craft-button') as HTMLElement).click();
    await flush();
    await flush();

    expect(document.activeElement).toBe(host.querySelector('input'));
  });

  it('leaves focus alone when the initial load fails while the user is elsewhere', async () => {
    const other = document.createElement('input');
    document.body.append(other);
    other.focus();
    post.mockRejectedValueOnce(new Error('offline'));
    mount();
    await flush();

    expect(host.querySelector('[role="alert"]')).not.toBeNull();
    expect(document.activeElement).toBe(other);
    other.remove();
  });

  it('keeps focus on the retry button when the retry fails too', async () => {
    post.mockRejectedValue(new Error('offline'));
    mount();
    await flush();

    const retry = host.querySelector('craft-button') as HTMLElement;
    retry.focus();
    retry.click();
    await flush();
    await flush();

    expect(document.activeElement).toBe(host.querySelector('craft-button'));
  });

  it('leaves focus alone when the user moves on while a retry loads', async () => {
    let resolveRetry!: (value: unknown) => void;
    post.mockRejectedValueOnce(new Error('offline'));
    post.mockImplementationOnce(
      () => new Promise((resolve) => (resolveRetry = resolve))
    );
    mount();
    await flush();

    (host.querySelector('craft-button') as HTMLElement).click();
    await flush();
    const other = document.createElement('input');
    document.body.append(other);
    other.focus();
    resolveRetry(response('recovered'));
    await flush();
    await flush();

    expect(document.activeElement).toBe(other);
    other.remove();
  });

  it('keeps focus in the index when its criteria change', async () => {
    post.mockResolvedValue(response('first'));
    mount();
    await flush();
    (host.querySelector('input') as HTMLElement).focus();

    criteria.value = {customerId: 2};
    await flush();
    await flush();
    await flush();

    expect(document.activeElement).toBe(host.querySelector('input'));
  });
});
