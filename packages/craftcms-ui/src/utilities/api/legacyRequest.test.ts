import axios from 'axios';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {createHttpClient} from './http';
import {sendLegacyRequest} from './legacyRequest';

let fetchMock: ReturnType<typeof vi.fn>;

beforeEach(() => {
  fetchMock = vi.fn(
    (_url: string, init: RequestInit) =>
      new Promise((_resolve, reject) => {
        init.signal!.addEventListener('abort', () =>
          reject(new DOMException('Aborted', 'AbortError'))
        );
      })
  );
  vi.stubGlobal('fetch', fetchMock);
});

afterEach(() => {
  vi.unstubAllGlobals();
});

describe('sendLegacyRequest', () => {
  // Plugins still create tokens with the axios global the adapter provides and
  // check failures with axios.isCancel(); both have to keep working.
  it('aborts when an axios cancel token is cancelled', async () => {
    const source = axios.CancelToken.source();
    const request = sendLegacyRequest(createHttpClient(), {
      url: '/x',
      cancelToken: source.token,
    }).catch((e: unknown) => e);

    await vi.waitFor(() => expect(fetchMock).toHaveBeenCalled());
    source.cancel();
    const error = await request;

    expect(axios.isCancel(error)).toBe(true);
    expect(axios.isAxiosError(error)).toBe(true);
  });

  it('does not send a request whose token was already cancelled', async () => {
    const source = axios.CancelToken.source();
    source.cancel();

    const error = await sendLegacyRequest(createHttpClient(), {
      url: '/x',
      cancelToken: source.token,
    }).catch((e: unknown) => e);

    expect(axios.isCancel(error)).toBe(true);
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('accepts an AbortSignal passed as cancelToken', async () => {
    const controller = new AbortController();
    const request = sendLegacyRequest(createHttpClient(), {
      url: '/x',
      cancelToken: controller.signal,
    }).catch((e: unknown) => e);

    await vi.waitFor(() => expect(fetchMock).toHaveBeenCalled());
    controller.abort();

    expect(axios.isCancel(await request)).toBe(true);
  });

  it('marks failed responses so axios.isAxiosError() still recognizes them', async () => {
    fetchMock.mockResolvedValueOnce(
      new Response('{"message":"Nope"}', {status: 400})
    );

    const error = await sendLegacyRequest(createHttpClient(), {
      url: '/x',
    }).catch((e: unknown) => e);

    expect(axios.isAxiosError(error)).toBe(true);
    expect(axios.isCancel(error)).toBe(false);
    expect(error).toMatchObject({response: {data: {message: 'Nope'}}});
  });
});
