import {
  createHttpClient,
  type HttpResponse,
} from '@craftcms/ui/utilities/api/http';
import {expect, it, vi} from 'vite-plus/test';
import {useFetch} from './useFetch';

it('ignores a superseded HTTP response', async () => {
  const client = createHttpClient();
  let finish!: () => void;

  const requestSpy = vi
    .spyOn(client, 'request')
    .mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          finish = () => resolve({data: 'Old'} as HttpResponse);
        })
    )
    .mockResolvedValueOnce({data: 'New'} as HttpResponse);

  const request = useFetch<string>('/example', {
    immediate: false,
    client: client,
  });

  const first = request.execute();
  expect(request.isLoading.value).toBe(true);

  await request.execute();
  // The superseded request is aborted, not just ignored.
  expect(requestSpy.mock.calls[0]![0].signal?.aborted).toBe(true);
  finish();

  expect(await first).toBeUndefined();
  expect(request.isSuccess.value).toBe(true);
  expect(request.data.value).toBe('New');
});

it('stays loading during a transform and discards its superseded result', async () => {
  const client = createHttpClient();
  vi.spyOn(client, 'request').mockResolvedValue({data: 'Raw'});

  let finish!: () => void;
  const transform = vi
    .fn()
    .mockImplementationOnce(
      () =>
        new Promise<string>((resolve) => {
          finish = () => resolve('Old');
        })
    )
    .mockResolvedValueOnce('New');

  const request = useFetch<string>('/example', {
    immediate: false,
    client: client,
    transform,
  });

  const first = request.execute();
  await vi.waitFor(() => expect(transform).toHaveBeenCalledOnce());
  expect(request.isLoading.value).toBe(true);

  expect(await request.execute()).toBe('New');
  finish();

  expect(await first).toBeUndefined();
  expect(request.isSuccess.value).toBe(true);
  expect(request.data.value).toBe('New');
});
