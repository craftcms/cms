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
let isLastUpload: ReturnType<typeof vi.fn>;

beforeEach(() => {
  container = document.createElement('div');
  document.body.append(container);
  destroy = vi.fn();
  setParams = vi.fn();
  isLastUpload = vi.fn(() => true);

  Object.assign(globalThis, {
    $: vi.fn((value) => value),
    Craft: {
      cp: {
        displayError: vi.fn(),
        runQueue: vi.fn(),
      },
      createUploader: vi.fn(() => ({
        destroy,
        isLastUpload,
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

interface UploaderEvents {
  fileuploaddone: (...args: any[]) => void;
  fileuploadfail: (...args: any[]) => void;
  fileuploadalways: (...args: any[]) => void;
}

/** Drives the `events` blob the component hands `Craft.createUploader`. */
function mountUploader(props: Record<string, unknown> = {}): {
  app: ReturnType<typeof createApp>;
  events: () => UploaderEvents;
} {
  const app = createApp({
    render: () =>
      h(AssetUploadButton, {
        canUpload: true,
        folderId: 12,
        fsType: 'Local',
        ...props,
      }),
  });
  app.mount(container);

  return {
    app,
    events: () =>
      (Craft.createUploader as any).mock.calls.at(-1)[2]
        .events as UploaderEvents,
  };
}

/** jQuery File Upload's shape: `(event, data)` with the response on `data.result`. */
const uploadDone = (result: unknown) => [new Event('fileuploaddone'), {result}];

it('reports a completed upload to whoever is listening', async () => {
  const uploaded = vi.fn();
  const {app, events} = mountUploader({onUploaded: uploaded});
  await nextTick();

  events().fileuploaddone(
    ...uploadDone({assetId: 7, filename: 'seascape.jpg'})
  );

  expect(uploaded).toHaveBeenCalledWith({id: 7, label: 'seascape.jpg'});

  app.unmount();
});

it('holds back an upload still waiting on a filename conflict', async () => {
  const uploaded = vi.fn();
  const {app, events} = mountUploader({onUploaded: uploaded});
  await nextTick();

  events().fileuploaddone(
    ...uploadDone({assetId: 7, filename: 'seascape.jpg', conflict: 'A file…'})
  );

  expect(uploaded).not.toHaveBeenCalled();

  app.unmount();
});

it('reloads the index behind it by default', async () => {
  const {app, events} = mountUploader();
  await nextTick();

  events().fileuploadalways();

  expect(state.reload).toHaveBeenCalledWith({only: ['data', 'pagination']});

  app.unmount();
});

it('leaves the page alone when the caller owns the aftermath', async () => {
  // A relation field: reloading would discard unsaved edits on the element
  // being edited, and there is no index behind it to refresh.
  const {app, events} = mountUploader({reloadOnComplete: false});
  await nextTick();

  events().fileuploadalways();

  expect(state.reload).not.toHaveBeenCalled();

  app.unmount();
});

it('binds the caller’s drop zone once it resolves', async () => {
  const zone = document.createElement('div');
  const {app} = mountUploader({dropZone: zone});
  await nextTick();

  expect(Craft.createUploader).toHaveBeenLastCalledWith(
    'Local',
    expect.anything(),
    expect.objectContaining({dropZone: zone})
  );

  app.unmount();
});
