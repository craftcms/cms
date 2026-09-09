import {afterEach, beforeEach, expect, it, vi} from 'vitest';
import {Uploader} from './uploader';
import {FileUpload, UploadError} from '@/upload-client';

vi.mock('./base-uploader', () => ({BaseUploader: class {}}));
vi.mock('./upload-notification', () => ({
  UploadNotification: class {
    updateState() {}
    close() {}
  },
}));

let uploader: Uploader;
const trigger = vi.fn();

beforeEach(() => {
  class TestUploader extends Uploader {}
  uploader = new TestUploader();
  trigger.mockReset();
  vi.stubGlobal('Craft', {
    csrfTokenValue: 'token',
    cp: {displayError: vi.fn()},
  });
  uploader.$element = {trigger, off: vi.fn()};
  uploader.uploader = {fileupload: vi.fn()};
  uploader.events = {};
  uploader.settings = {url: '/uploads', maxFileSize: Number.MAX_SAFE_INTEGER};
  uploader.processErrorMessages = vi.fn();
  uploader._inProgressCounter = 0;
});

afterEach(() => {
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

it('hands accepted index files to the application queue without owning their lifetime', () => {
  const enqueueUpload = vi.fn();
  uploader.settings.enqueueUpload = enqueueUpload;
  const upload = vi.spyOn(FileUpload.prototype, 'upload');
  const file = new File(['abc'], 'document.txt');
  const data = fileData(file);

  uploader.onFileAdd({stopPropagation: vi.fn()}, data);
  uploader.destroy();

  expect(enqueueUpload).toHaveBeenCalledWith(file, data.originalFiles);
  expect(upload).not.toHaveBeenCalled();
  expect(trigger).not.toHaveBeenCalled();
});

it('does not cancel a successfully canceled upload again during teardown', async () => {
  vi.spyOn(FileUpload.prototype, 'upload').mockRejectedValue(
    new UploadError('Upload failed.', 500)
  );
  const cancel = vi.spyOn(FileUpload.prototype, 'cancel').mockResolvedValue();
  const file = new File(['abc'], 'document.txt');
  const data = fileData(file);

  uploader.onFileAdd({stopPropagation: vi.fn()}, data);
  await vi.waitFor(() =>
    expect(trigger).toHaveBeenCalledWith('fileuploadstop')
  );
  await data.abort();
  uploader.destroy();

  expect(cancel).toHaveBeenCalledTimes(1);
});

it('allows retry after a replacement failure handler throws', async () => {
  const handlerError = new Error('Replacement handler failed');
  const reportError = vi.fn();
  vi.stubGlobal('reportError', reportError);
  trigger.mockImplementation((name: string) => {
    if (name === 'fileuploadfail') {
      throw handlerError;
    }
  });
  vi.spyOn(FileUpload.prototype, 'upload')
    .mockRejectedValueOnce(new UploadError('Upload failed.', 500))
    .mockResolvedValueOnce({assetId: 123});
  const file = new File(['abc'], 'replacement.txt');
  const data = fileData(file);

  uploader.onFileAdd({stopPropagation: vi.fn()}, data);
  await vi.waitFor(() =>
    expect(reportError).toHaveBeenCalledWith(handlerError)
  );
  data.submit();

  await vi.waitFor(() =>
    expect(trigger).toHaveBeenCalledWith(
      'fileuploaddone',
      expect.objectContaining({result: {assetId: 123}})
    )
  );
  expect(
    trigger.mock.calls.filter(([name]) => name === 'fileuploadstop')
  ).toHaveLength(2);
});

it('reports cancellation of a waiting file and keeps legacy start/stop events balanced', async () => {
  let finish!: (result: Record<string, unknown>) => void;
  const upload = vi.spyOn(FileUpload.prototype, 'upload').mockImplementation(
    () =>
      new Promise((resolve) => {
        finish = resolve;
      })
  );
  const first = fileData(new File(['a'], 'first.txt'));
  const second = fileData(new File(['b'], 'second.txt'));
  uploader.onFileAdd({stopPropagation: vi.fn()}, first);
  uploader.onFileAdd({stopPropagation: vi.fn()}, second);
  await vi.waitFor(() => expect(upload).toHaveBeenCalledOnce());

  await second.abort();
  finish({assetId: 123});

  await vi.waitFor(() =>
    expect(trigger).toHaveBeenCalledWith('fileuploadstop')
  );
  expect(upload).toHaveBeenCalledOnce();
  expect(trigger).toHaveBeenCalledWith(
    'fileuploadfail',
    expect.objectContaining({errorThrown: 'abort'})
  );
  expect(
    trigger.mock.calls.filter(([name]) => name === 'fileuploadstart')
  ).toHaveLength(1);
  expect(
    trigger.mock.calls.filter(([name]) => name === 'fileuploadalways')
  ).toHaveLength(2);
});

it('detaches a final save and cancels waiting files when a local picker is destroyed', async () => {
  let finish!: (result: Record<string, unknown>) => void;
  const upload = vi
    .spyOn(FileUpload.prototype, 'upload')
    .mockImplementation(function (this: FileUpload) {
      this.state = 'completing';
      return new Promise((resolve) => {
        finish = resolve;
      });
    });
  const cancel = vi.spyOn(FileUpload.prototype, 'cancel');
  uploader.onFileAdd(
    {stopPropagation: vi.fn()},
    fileData(new File(['a'], 'first.txt'))
  );
  uploader.onFileAdd(
    {stopPropagation: vi.fn()},
    fileData(new File(['b'], 'second.txt'))
  );
  await vi.waitFor(() => expect(upload).toHaveBeenCalledOnce());

  uploader.destroy();
  trigger.mockClear();
  finish({assetId: 123});

  await upload.mock.results[0]!.value;
  expect(cancel).toHaveBeenCalledOnce();
  expect(upload).toHaveBeenCalledOnce();
  expect(trigger).not.toHaveBeenCalled();
});

function fileData(file: File) {
  return {
    files: [file],
    originalFiles: [file],
    process: () => ({done: (callback: () => void) => callback()}),
    abort: async () => {},
    submit: () => {},
  };
}
