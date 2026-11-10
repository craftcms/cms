import {afterEach, expect, it, vi} from 'vitest';
import {FileUpload, UploadError} from '@/upload-client';
import * as uploadRequests from '@/upload-request';
import {fileUploadOptions} from './uploads';

const {showError} = vi.hoisted(() => ({showError: vi.fn()}));
vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));
vi.mock('@/modules/messages/useMessages', () => ({
  useMessages: () => ({error: showError}),
}));

afterEach(() => {
  vi.restoreAllMocks();
  showError.mockReset();
});

it.each([
  ['image/png', '![uploaded.png]({asset:42@2:url})'],
  ['text/plain', '[uploaded.png]({asset:42@2:url})'],
])(
  'inserts the asset returned by the upload session for %s',
  async (type, markdown) => {
    vi.spyOn(FileUpload.prototype, 'upload').mockResolvedValue({
      assetId: 42,
      filename: 'uploaded.png',
    });
    const file = new File(['contents'], 'original.png', {type});

    await expect(fileUploadOptions(12, 2)!.onInsertFile!(file)).resolves.toBe(
      markdown
    );
  }
);

it('uploads into the site the inserted reference points to', async () => {
  const error = new UploadError('Stop after the session request.', 422);
  const uploadRequest = vi
    .spyOn(uploadRequests, 'uploadRequest')
    .mockRejectedValue(error);

  await expect(
    fileUploadOptions(12, 3)!.onInsertFile!(new File(['contents'], 'file.txt'))
  ).rejects.toBe(error);
  expect(uploadRequest).toHaveBeenCalledWith(
    expect.any(String),
    'POST',
    expect.objectContaining({folderId: 12, siteId: 3}),
    expect.anything()
  );
});

it('reports an upload failure without inserting a link', async () => {
  const error = new UploadError('The file is not allowed.', 422);
  vi.spyOn(FileUpload.prototype, 'upload').mockRejectedValue(error);

  await expect(
    fileUploadOptions(12, 2)!.onInsertFile!(new File(['contents'], 'file.txt'))
  ).rejects.toBe(error);
  expect(showError).toHaveBeenCalledWith('The file is not allowed.');
});
