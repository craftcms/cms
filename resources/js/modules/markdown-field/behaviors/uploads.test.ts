import {afterEach, expect, it, vi} from 'vitest';
import {AssetUpload, UploadError} from '@/upload-client';
import {fileUploadOptions} from './uploads';

const {flash} = vi.hoisted(() => ({flash: vi.fn()}));
vi.mock('@craftcms/ui', () => ({t: (message: string) => message}));
vi.mock('@/common/composables/useFlashMessages', () => ({
  useFlashMessages: () => ({flash}),
}));

afterEach(() => {
  vi.restoreAllMocks();
  flash.mockReset();
});

it.each([
  ['image/png', '![uploaded.png]({asset:42@2:url})'],
  ['text/plain', '[uploaded.png]({asset:42@2:url})'],
])(
  'inserts the asset returned by the upload session for %s',
  async (type, markdown) => {
    vi.spyOn(AssetUpload.prototype, 'upload').mockResolvedValue({
      assetId: 42,
      filename: 'uploaded.png',
    });
    const file = new File(['contents'], 'original.png', {type});

    await expect(fileUploadOptions(12, 2)!.onInsertFile!(file)).resolves.toBe(
      markdown
    );
  }
);

it('reports an upload failure without inserting a link', async () => {
  const error = new UploadError('The file is not allowed.', 422);
  vi.spyOn(AssetUpload.prototype, 'upload').mockRejectedValue(error);

  await expect(
    fileUploadOptions(12, 2)!.onInsertFile!(new File(['contents'], 'file.txt'))
  ).rejects.toBe(error);
  expect(flash).toHaveBeenCalledWith('error', 'The file is not allowed.');
});
