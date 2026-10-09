import {afterEach, beforeEach, expect, it, vi} from 'vitest';
import {Uploader} from './uploader';
import {FileUpload, UploadError} from '@/upload-client';

vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));
vi.mock('./upload-notification', () => ({
  createFileUploadNotification: () => ({
    updateState() {},
    close() {},
  }),
}));

let uploader: Uploader;
const callbacks = {
  start: vi.fn(),
  progress: vi.fn(),
  done: vi.fn(),
  fail: vi.fn(),
  settled: vi.fn(),
  stop: vi.fn(),
};

beforeEach(() => {
  for (const callback of Object.values(callbacks)) {
    callback.mockReset();
  }
  vi.stubGlobal('Craft', {
    csrfTokenValue: 'token',
    cp: {displayError: vi.fn()},
  });
  const input = document.createElement('input');
  input.type = 'file';
  uploader = new Uploader(input, {url: '/uploads', on: callbacks});
});

afterEach(() => {
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

it('does not cancel a successfully canceled upload again during teardown', async () => {
  vi.spyOn(FileUpload.prototype, 'upload').mockRejectedValue(
    new UploadError('Upload failed.', 500)
  );
  const cancel = vi.spyOn(FileUpload.prototype, 'cancel').mockResolvedValue();
  const controls = uploader.acceptFile(new File(['abc'], 'document.txt'))!;

  await vi.waitFor(() => expect(callbacks.stop).toHaveBeenCalledOnce());
  await controls.cancel();
  uploader.destroy();

  expect(cancel).toHaveBeenCalledTimes(1);
});

it('allows retry after a replacement failure handler throws', async () => {
  const handlerError = new Error('Replacement handler failed');
  const reportError = vi.fn();
  vi.stubGlobal('reportError', reportError);
  callbacks.fail.mockImplementation(() => {
    throw handlerError;
  });
  vi.spyOn(FileUpload.prototype, 'upload')
    .mockRejectedValueOnce(new UploadError('Upload failed.', 500))
    .mockResolvedValueOnce({assetId: 123});
  const controls = uploader.acceptFile(new File(['abc'], 'replacement.txt'))!;

  await vi.waitFor(() =>
    expect(reportError).toHaveBeenCalledWith(handlerError)
  );
  controls.retry();

  await vi.waitFor(() =>
    expect(callbacks.done).toHaveBeenCalledWith(
      expect.objectContaining({result: {assetId: 123}})
    )
  );
  expect(callbacks.stop).toHaveBeenCalledTimes(2);
});

it('reports cancellation of a waiting file and keeps start/stop callbacks balanced', async () => {
  let finish!: (result: Record<string, unknown>) => void;
  const upload = vi.spyOn(FileUpload.prototype, 'upload').mockImplementation(
    () =>
      new Promise((resolve) => {
        finish = resolve;
      })
  );
  uploader.acceptFile(new File(['a'], 'first.txt'));
  const second = uploader.acceptFile(new File(['b'], 'second.txt'))!;

  await second.cancel();
  finish({assetId: 123});

  await vi.waitFor(() => expect(callbacks.stop).toHaveBeenCalledOnce());
  expect(upload).toHaveBeenCalledOnce();
  expect(callbacks.fail).toHaveBeenCalledWith(
    expect.objectContaining({canceled: true})
  );
  expect(callbacks.start).toHaveBeenCalledOnce();
  expect(callbacks.settled).toHaveBeenCalledTimes(2);
});

it('reports only the final concurrent upload as the last upload', async () => {
  const finishes: Array<(result: Record<string, unknown>) => void> = [];
  const lastUploads: boolean[] = [];
  vi.spyOn(FileUpload.prototype, 'upload').mockImplementation(
    () =>
      new Promise((resolve) => {
        finishes.push(resolve);
      })
  );
  callbacks.settled.mockImplementation(() => {
    lastUploads.push(uploader.isLastUpload());
  });

  uploader.acceptFile(new File(['a'], 'first.txt'));
  uploader.acceptFile(new File(['b'], 'second.txt'));
  await vi.waitFor(() => expect(finishes).toHaveLength(2));

  finishes[0]!({assetId: 1});
  await vi.waitFor(() => expect(callbacks.settled).toHaveBeenCalledOnce());
  finishes[1]!({assetId: 2});
  await vi.waitFor(() => expect(callbacks.settled).toHaveBeenCalledTimes(2));

  expect(lastUploads).toEqual([false, true]);
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
  uploader.acceptFile(new File(['a'], 'first.txt'));
  await vi.waitFor(() => expect(upload).toHaveBeenCalledOnce());
  uploader.acceptFile(new File(['b'], 'second.txt'));
  uploader.destroy();
  finish({assetId: 123});

  await upload.mock.results[0]!.value;
  expect(cancel).toHaveBeenCalledOnce();
  expect(upload).toHaveBeenCalledOnce();
  expect(callbacks.done).not.toHaveBeenCalled();
  expect(callbacks.fail).not.toHaveBeenCalled();
  expect(callbacks.settled).not.toHaveBeenCalled();
});
