import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick} from 'vue';
import AssetUploadButton from './AssetUploadButton.vue';
import {Uploader} from '@/modules/uploader/uploader';
vi.mock('@/modules/uploader/uploader', () => ({Uploader: vi.fn()}));

const conflicts = vi.hoisted(() => ({
  prompts: [] as any[],
  resolve: null as ((prompts: any[]) => void) | null,
}));

vi.mock('@/modules/prompt-handler/prompt-handler', () => ({
  PromptHandler: class {
    resetPrompts() {
      conflicts.prompts = [];
    }

    addPrompt(prompt: any) {
      conflicts.prompts.push(prompt);
    }

    getPromptCount() {
      return conflicts.prompts.length;
    }

    showBatchPrompts(resolve: (prompts: any[]) => void) {
      conflicts.resolve = resolve;
    }
  },
}));

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
  conflicts.prompts = [];
  conflicts.resolve = null;

  vi.mocked(Uploader)
    .mockReset()
    .mockImplementation(
      class {
        destroy = destroy;
        isLastUpload = isLastUpload;
        setParams = setParams;
      } as unknown as typeof Uploader
    );

  Object.assign(globalThis, {
    $: vi.fn((value) => value),
    Craft: {
      cp: {
        displayError: vi.fn(),
        runQueue: vi.fn(),
      },
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
  expect(Uploader).toHaveBeenCalledWith(
    input,
    expect.objectContaining({
      fileInput: input,
      url: '/admin/actions/assets/uploads',
    })
  );
  expect(setParams).toHaveBeenCalledWith({folderId: 12});

  app.unmount();
  expect(destroy).toHaveBeenCalledOnce();
});

interface UploaderCallbacks {
  done: (...args: any[]) => void;
  fail: (...args: any[]) => void;
  settled: (...args: any[]) => void;
}

/** Drives the callbacks the component hands `Uploader`. */
function mountUploader(props: Record<string, unknown> = {}): {
  app: ReturnType<typeof createApp>;
  events: () => UploaderCallbacks;
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
      (Uploader as any).mock.calls.at(-1)[1].on as UploaderCallbacks,
  };
}

const uploadDone = (result: unknown) => [{result}];

it('reports a completed upload to whoever is listening', async () => {
  const uploaded = vi.fn();
  const {app, events} = mountUploader({onUploaded: uploaded});
  await nextTick();

  events().done(...uploadDone({assetId: 7, filename: 'seascape.jpg'}));

  expect(uploaded).toHaveBeenCalledWith({id: 7, label: 'seascape.jpg'});

  app.unmount();
});

it('reports a conflicting upload after the user chooses to keep both', async () => {
  const uploaded = vi.fn();
  const {app, events} = mountUploader({onUploaded: uploaded});
  await nextTick();

  const result = {
    assetId: 7,
    filename: 'seascape.jpg',
    suggestedFilename: 'seascape_1.jpg',
    conflict: 'A file…',
  };
  events().done(...uploadDone(result));
  events().settled();

  expect(uploaded).not.toHaveBeenCalled();
  expect(conflicts.resolve).not.toBeNull();

  conflicts.resolve!([{...result, choice: 'keepBoth'}]);
  await nextTick();

  expect(uploaded).toHaveBeenCalledWith({id: 7, label: 'seascape_1.jpg'});

  app.unmount();
});

it('reloads the index behind it by default', async () => {
  const {app, events} = mountUploader();
  await nextTick();

  events().settled();

  expect(state.reload).toHaveBeenCalledWith({only: ['data', 'pagination']});

  app.unmount();
});

it('leaves the page alone when the caller owns the aftermath', async () => {
  // A relation field: reloading would discard unsaved edits on the element
  // being edited, and there is no index behind it to refresh.
  const {app, events} = mountUploader({reloadOnComplete: false});
  await nextTick();

  events().settled();

  expect(state.reload).not.toHaveBeenCalled();

  app.unmount();
});

it('binds the caller’s drop zone once it resolves', async () => {
  const zone = document.createElement('div');
  const {app} = mountUploader({dropZone: zone});
  await nextTick();

  expect(Uploader).toHaveBeenLastCalledWith(
    expect.anything(),
    expect.objectContaining({dropZone: zone})
  );

  app.unmount();
});
