import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {Uploader} from './uploader';
import {FileUpload} from '@/upload-client';

vi.mock('@craftcms/ui', () => ({
  t: (message: string, parameters: Record<string, string> = {}) =>
    message.replace(/\{(\w+)\}/g, (_, key) => parameters[key] ?? `{${key}}`),
}));
vi.mock('./upload-notification', () => ({
  createFileUploadNotification: () => ({
    updateState() {},
    updateProgress() {},
    close() {},
  }),
}));

let uploader: Uploader;
let input: HTMLInputElement;
let dropZone: HTMLDivElement;
const enqueue = vi.fn();
const error = vi.fn();

beforeEach(() => {
  enqueue.mockReset();
  error.mockReset();
  vi.stubGlobal('Craft', {
    csrfTokenValue: 'token',
    maxUploadSize: 100,
    maxAssetUploadSize: 100,
    fileKinds: {
      image: {extensions: ['png', 'jpg']},
      archive: {extensions: ['7z']},
    },
    t: (_category: string, message: string) => message,
    cp: {displayError: error},
  });
  input = document.createElement('input');
  input.type = 'file';
  input.multiple = true;
  dropZone = document.createElement('div');
  dropZone.append(input);
  document.body.append(dropZone);
});

afterEach(() => {
  uploader?.destroy();
  document.body.replaceChildren();
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

function createPicker(settings: Record<string, unknown> = {}): void {
  uploader = new Uploader(input, {
    fileInput: input,
    dropZone,
    pasteZone: dropZone,
    enqueueUpload: enqueue,
    ...settings,
  });
}

function select(files: File[]): void {
  Object.defineProperty(input, 'files', {configurable: true, value: files});
  input.dispatchEvent(new Event('change', {bubbles: true}));
}

function drop(files: File[], target: HTMLElement = dropZone): Event {
  const event = new Event('drop', {bubbles: true, cancelable: true});
  Object.defineProperty(event, 'dataTransfer', {
    value: {types: ['Files'], files},
  });
  target.dispatchEvent(event);
  return event;
}

it('uses the existing input and allows selecting the same file again', () => {
  const upload = vi.spyOn(FileUpload.prototype, 'upload');
  const start = vi.fn();
  createPicker({on: {start}});
  const file = new File(['image'], 'photo.png');
  select([file]);
  select([file]);

  expect(enqueue).toHaveBeenCalledTimes(2);
  expect(enqueue).toHaveBeenLastCalledWith(file, [file]);
  expect(dropZone.querySelector('input')).toBe(input);
  expect(input.value).toBe('');
  uploader.destroy();
  expect(upload).not.toHaveBeenCalled();
  expect(start).not.toHaveBeenCalled();
});

it('hands a dropped selection to the application queue as one batch', async () => {
  createPicker();
  const files = [new File(['a'], 'a.png'), new File(['b'], 'b.png')];
  const event = drop(files);

  await vi.waitFor(() => expect(enqueue).toHaveBeenCalledTimes(2));
  expect(event.defaultPrevented).toBe(true);
  expect(enqueue.mock.calls[0]).toEqual([files[0], files]);
  expect(enqueue.mock.calls[1]![1]).toBe(enqueue.mock.calls[0]![1]);
});

it.each(['input', 'drop'] as const)(
  'applies Uppy restrictions to %s selections before starting sessions',
  async (source) => {
    createPicker({allowedKinds: ['image'], maxFileSize: 5});
    const accepted = new File(['ok'], 'photo.PNG');
    const files = [
      new File(['bad'], 'program.exe'),
      new File(['too large'], 'large.png'),
      accepted,
    ];
    if (source === 'input') {
      select(files);
    } else {
      drop(files);
    }

    await vi.waitFor(() => expect(enqueue).toHaveBeenCalledOnce());
    expect(enqueue).toHaveBeenCalledWith(accepted, [accepted]);
    expect(error).toHaveBeenCalledTimes(2);
    expect(input.accept).toBe('.png,.jpg');
  }
);

it('accepts extensions containing digits above four', () => {
  createPicker({allowedKinds: ['archive']});
  const file = new File(['archive'], 'backup.7z');
  select([file]);
  expect(enqueue).toHaveBeenCalledWith(file, [file]);
});

it('uses the input accept and single-file constraints for dropped files', async () => {
  input.accept = 'image/*';
  input.multiple = false;
  createPicker();
  drop([
    new File(['a'], 'a.png', {type: 'image/png'}),
    new File(['b'], 'b.png', {type: 'image/png'}),
  ]);

  await vi.waitFor(() =>
    expect(error).toHaveBeenCalledWith('You can only upload one file.')
  );
  expect(enqueue).not.toHaveBeenCalled();
  select([new File(['text'], 'notes.txt', {type: 'text/plain'})]);
  expect(enqueue).not.toHaveBeenCalled();
  const file = new File(['image'], 'photo.png', {type: 'image/png'});
  select([file]);
  expect(enqueue).toHaveBeenCalledWith(file, [file]);
});

it('keeps field limits and completion callbacks across consecutive selections', async () => {
  const done = vi.fn();
  const canAddMoreFiles = vi.fn((slots: number) => slots < 2);
  const upload = vi
    .spyOn(FileUpload.prototype, 'upload')
    .mockResolvedValue({assetId: 123});
  createPicker({enqueueUpload: undefined, canAddMoreFiles, on: {done}});
  select([new File(['a'], 'a.png')]);
  select([new File(['b'], 'b.png'), new File(['c'], 'c.png')]);

  await vi.waitFor(() => expect(done).toHaveBeenCalledTimes(2));
  expect(upload).toHaveBeenCalledTimes(2);
  expect(canAddMoreFiles.mock.calls).toEqual([[0], [1], [2]]);
  expect(done.mock.calls[0]![0]).toMatchObject({result: {assetId: 123}});
  expect(error).toHaveBeenCalledOnce();
  expect(uploader.getInProgress()).toBe(0);
});

it('accepts pasted files without intercepting ordinary text', () => {
  createPicker();
  const text = new Event('paste', {bubbles: true, cancelable: true});
  dropZone.dispatchEvent(text);
  expect(text.defaultPrevented).toBe(false);
  const file = new File(['image'], 'clipboard.png');
  const paste = new Event('paste', {bubbles: true, cancelable: true});
  Object.defineProperty(paste, 'clipboardData', {value: {files: [file]}});
  dropZone.dispatchEvent(paste);
  expect(paste.defaultPrevented).toBe(true);
  expect(enqueue).toHaveBeenCalledWith(file, [file]);
});

it('removes listeners and ignores a drop still being read during teardown', async () => {
  createPicker({allowedKinds: ['image']});
  dropZone.classList.add('uppy-is-drag-over');
  const file = new File(['image'], 'photo.png');
  drop([file]);
  uploader.destroy();
  select([file]);
  const event = drop([file]);
  await new Promise((resolve) => setTimeout(resolve, 0));

  expect(enqueue).not.toHaveBeenCalled();
  expect(event.defaultPrevented).toBe(false);
  expect(input.accept).toBe('');
  expect(dropZone.classList.contains('uppy-is-drag-over')).toBe(false);
});
