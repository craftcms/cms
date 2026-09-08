import {afterEach, beforeEach, expect, it, vi} from 'vitest';
import {Uploader} from './uploader';
import {AssetUpload, UploadError} from '@/upload-client';

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
  uploader.settings = {};
  uploader._inProgressCounter = 0;
});

afterEach(() => {
  vi.unstubAllGlobals();
  vi.restoreAllMocks();
});

it('does not cancel a successfully canceled upload again during teardown', async () => {
  vi.spyOn(AssetUpload.prototype, 'upload').mockRejectedValue(
    new UploadError('Upload failed.', 500)
  );
  const cancel = vi.spyOn(AssetUpload.prototype, 'cancel').mockResolvedValue();
  const file = new File(['abc'], 'document.txt');
  const data = {files: [file], abort: async () => {}};

  uploader['uploadFile'](file, data);
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
  vi.spyOn(AssetUpload.prototype, 'upload')
    .mockRejectedValueOnce(new UploadError('Upload failed.', 500))
    .mockResolvedValueOnce({assetId: 123});
  const file = new File(['abc'], 'replacement.txt');
  const data = {files: [file], submit: () => {}};

  uploader['uploadFile'](file, data);
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
