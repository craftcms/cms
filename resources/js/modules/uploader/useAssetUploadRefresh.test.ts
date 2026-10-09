import {createApp, h, nextTick, ref} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';

const navigation = vi.hoisted(() => ({
  reload: vi.fn(),
  on: vi.fn(),
  changes: new Map<number, number>(),
}));
vi.mock('@inertiajs/vue3', () => ({router: navigation}));
vi.mock('./asset-upload-queue', () => {
  const changes = navigation.changes;
  return {
    assetUploadQueue: Object.assign(new EventTarget(), {
      changes,
      revision: (folder: number) => changes.get(folder) ?? 0,
      acknowledge: (folder: number, revision: number) => {
        if (changes.get(folder) === revision) changes.delete(folder);
      },
    }),
  };
});

import {assetUploadQueue} from './asset-upload-queue';
import {useAssetUploadRefresh} from './useAssetUploadRefresh';

const changes = navigation.changes;
let app: ReturnType<typeof createApp> | undefined;
let container: HTMLElement;
const folder = ref<number | undefined>(7);
const listeners = new Map<string, (event: unknown) => void>();

beforeEach(() => {
  folder.value = 7;
  changes.clear();
  listeners.clear();
  navigation.reload.mockReset();
  navigation.on.mockReset().mockImplementation((name, listener) => {
    listeners.set(name, listener);
    return () => listeners.delete(name);
  });
  container = document.createElement('div');
  document.body.append(container);
});
afterEach(() => {
  app?.unmount();
  app = undefined;
  container.remove();
});

async function mount(): Promise<void> {
  app = createApp({
    setup() {
      useAssetUploadRefresh(() => folder.value);
      return () => h('div');
    },
  });
  app.mount(container);
  await nextTick();
}

function uploaded(folder: number, revision = 1): void {
  changes.set(folder, revision);
  assetUploadQueue.dispatchEvent(new CustomEvent('change', {detail: folder}));
}

it('refreshes only the current destination without starting a blocking visit', async () => {
  await mount();
  uploaded(8);
  expect(navigation.reload).not.toHaveBeenCalled();
  uploaded(7);
  expect(navigation.reload).toHaveBeenCalledWith(
    expect.objectContaining({only: ['data', 'pagination'], async: true})
  );
});

it('does not reload while leaving Assets and refreshes when returning to stale history', async () => {
  await mount();
  listeners.get('start')!({detail: {visit: {async: false}}});
  uploaded(7);
  expect(navigation.reload).not.toHaveBeenCalled();
  app!.unmount();
  await mount();
  expect(navigation.reload).toHaveBeenCalledOnce();
  const reload = navigation.reload.mock.calls[0]![0];
  reload.onSuccess();
  reload.onFinish();
  app!.unmount();
  await mount();
  expect(navigation.reload).toHaveBeenCalledOnce();
});

it('catches a completion that arrives during a listing refresh', async () => {
  await mount();
  uploaded(7);
  const first = navigation.reload.mock.calls[0]![0];
  uploaded(7, 2);
  expect(navigation.reload).toHaveBeenCalledOnce();
  first.onSuccess();
  first.onFinish();
  expect(navigation.reload).toHaveBeenCalledTimes(2);
  const second = navigation.reload.mock.calls[1]![0];
  second.onSuccess();
  second.onFinish();
  app!.unmount();
  await mount();
  expect(navigation.reload).toHaveBeenCalledTimes(2);
});

it('does not retry failed reloads in a loop or refresh after unmount', async () => {
  await mount();
  uploaded(7);
  const first = navigation.reload.mock.calls[0]![0];
  first.onFinish();
  expect(navigation.reload).toHaveBeenCalledOnce();
  app!.unmount();
  app = undefined;
  uploaded(7, 2);
  first.onSuccess();
  first.onFinish();
  expect(navigation.reload).toHaveBeenCalledOnce();
});

it('refreshes the destination after switching folders within Assets', async () => {
  await mount();
  uploaded(8);
  folder.value = 8;
  await nextTick();
  expect(navigation.reload).toHaveBeenCalledOnce();
});
