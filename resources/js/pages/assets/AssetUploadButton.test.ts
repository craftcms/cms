import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import AssetUploadButton from './AssetUploadButton.vue';

const state = vi.hoisted(() => ({
  reload: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({
  router: {reload: state.reload},
}));

let container: HTMLDivElement;
let destroy: ReturnType<typeof vi.fn>;
let setParams: ReturnType<typeof vi.fn>;

beforeEach(() => {
  container = document.createElement('div');
  document.body.append(container);
  destroy = vi.fn();
  setParams = vi.fn();

  Object.assign(globalThis, {
    $: vi.fn((value) => value),
    Craft: {
      cp: {
        displayError: vi.fn(),
        runQueue: vi.fn(),
      },
      createUploader: vi.fn(() => ({
        destroy,
        isLastUpload: () => true,
        setParams,
      })),
      t: (_category: string, message: string) => message,
    },
  });
});

afterEach(() => {
  container.remove();
  state.reload.mockReset();
});

it('opens the file picker and configures uploads for the selected folder', async () => {
  const app = createApp({
    render: () =>
      h(AssetUploadButton, {
        canUpload: true,
        folderId: 12,
        fsType: 'Local',
      }),
  });

  app.mount(container);
  await nextTick();

  const input = container.querySelector<HTMLInputElement>('input[type=file]')!;
  const inputClick = vi.spyOn(input, 'click');

  container.querySelector<HTMLElement>('craft-button')!.click();

  expect(inputClick).toHaveBeenCalledOnce();
  expect(Craft.createUploader).toHaveBeenCalledWith(
    'Local',
    input,
    expect.objectContaining({
      fileInput: input,
      url: '/admin/actions/assets/upload',
    })
  );
  expect(setParams).toHaveBeenCalledWith({folderId: 12});

  app.unmount();
  expect(destroy).toHaveBeenCalledOnce();
});
