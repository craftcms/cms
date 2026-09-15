import axios from 'axios';
import {expect, it, vi} from 'vite-plus/test';
import {useFetch} from './useFetch';

it('ignores a superseded HTTP response', async () => {
  const client = axios.create();
  let finish!: () => void;

  vi.spyOn(client, 'request')
    .mockImplementationOnce(
      () =>
        new Promise((resolve) => {
          finish = () => resolve({data: 'Old'});
        })
    )
    .mockResolvedValueOnce({data: 'New'});

  const request = useFetch<string>('/example', {
    immediate: false,
    axiosInstance: client,
  });

  const first = request.execute();
  expect(request.isLoading.value).toBe(true);

  await request.execute();
  finish();

  expect(await first).toBeUndefined();
  expect(request.isSuccess.value).toBe(true);
  expect(request.data.value).toBe('New');
});

it('stays loading during a transform and discards its superseded result', async () => {
  const client = axios.create();
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
    axiosInstance: client,
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
