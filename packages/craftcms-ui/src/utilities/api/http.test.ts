import axios from 'axios';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {createHttpClient, isCancel, isHttpError, type HttpError} from './http';

let fetchMock: ReturnType<typeof vi.fn>;

function respond(body: BodyInit | null, init: ResponseInit = {}) {
  fetchMock.mockResolvedValueOnce(new Response(body, init));
}

function lastRequest(): {url: string; init: RequestInit} {
  const [url, init] = fetchMock.mock.lastCall!;
  return {url, init};
}

beforeEach(() => {
  fetchMock = vi.fn();
  vi.stubGlobal('fetch', fetchMock);
});

afterEach(() => {
  vi.unstubAllGlobals();
  vi.useRealTimers();
});

describe('params', () => {
  // Expected URLs come from axios itself, so the two serializers can't drift
  // while axios is still installed. PHP parses these back into arrays.
  it.each([
    ['flat values', {a: 1, b: 'two words', c: true}],
    ['top-level arrays', {ids: [1, 2, 3], 'tags[]': ['x', 'y']}],
    ['nested objects', {criteria: {siteId: 1, status: ['live', 'pending']}}],
    ['arrays of objects', {sort: [{attr: 'title', dir: 'asc'}]}],
    ['null and undefined', {a: null, b: undefined, c: [1, null], d: 0}],
    ['reserved characters', {q: 'a:b,c$d&e=f/g?h#i'}],
    ['dates', {since: new Date(Date.UTC(2026, 0, 2, 3, 4, 5))}],
  ])('serializes %s the same way axios does', async (_label, params) => {
    respond('{}');
    await createHttpClient().get('https://example.test/path?site=en', {params});

    expect(lastRequest().url).toBe(
      axios.getUri({url: 'https://example.test/path?site=en', params})
    );
  });

  it('joins a relative URL onto baseURL', async () => {
    respond('{}');
    await createHttpClient({baseURL: 'https://api.test/v1/'}).get('/plugins', {
      params: {page: 2},
    });

    expect(lastRequest().url).toBe('https://api.test/v1/plugins?page=2');
  });
});

describe('request bodies', () => {
  it.each([
    [
      'plain objects as JSON',
      {a: [1, {b: 2}]},
      'application/json',
      '{"a":[1,{"b":2}]}',
    ],
    [
      'URLSearchParams as a urlencoded form',
      new URLSearchParams({a: '1', b: 'x y'}),
      'application/x-www-form-urlencoded;charset=utf-8',
      'a=1&b=x+y',
    ],
    [
      'strings as a urlencoded form',
      'a=1&action=foo',
      'application/x-www-form-urlencoded',
      'a=1&action=foo',
    ],
  ])('sends %s', async (_label, data, contentType, body) => {
    respond('{}');
    await createHttpClient().post('/save', data);

    const {init} = lastRequest();
    expect(new Headers(init.headers).get('Content-Type')).toBe(contentType);
    expect(init.body).toBe(body);
  });

  it('leaves FormData for the browser to encode', async () => {
    const form = new FormData();
    form.append('file', new Blob(['x']), 'x.txt');
    respond('{}');
    await createHttpClient().post('/upload', form);

    const {init} = lastRequest();
    expect(init.body).toBe(form);
    expect(new Headers(init.headers).has('Content-Type')).toBe(false);
  });

  it('urlencodes an object when asked to', async () => {
    respond('{}');
    await createHttpClient().post(
      '/save',
      {fields: {title: 'Hi'}},
      {headers: {'Content-Type': 'application/x-www-form-urlencoded'}}
    );

    expect(lastRequest().init.body).toBe('fields%5Btitle%5D=Hi');
  });
});

describe('headers', () => {
  it('lets a request header override a default that differs only in case', async () => {
    const client = createHttpClient({headers: {'X-CSRF-TOKEN': 'old'}});
    respond('{}');
    await client.post('/save', {}, {headers: {'X-CSRF-Token': 'new'}});

    expect(new Headers(lastRequest().init.headers).get('x-csrf-token')).toBe(
      'new'
    );
  });

  it('copies defaults so clients stay independent', async () => {
    const shared = createHttpClient();
    const other = createHttpClient(shared.defaults);
    shared.defaults.headers['X-CSRF-TOKEN'] = 'abc';
    respond('{}');
    await other.get('/x');

    expect(new Headers(lastRequest().init.headers).has('X-CSRF-TOKEN')).toBe(
      false
    );
  });
});

describe('responses', () => {
  it('parses JSON regardless of content type and lowercases headers', async () => {
    respond('{"ok":true}', {
      headers: {'Content-Type': 'text/html', 'X-CSRF-Token': 'abc'},
    });
    const response = await createHttpClient().get('/x');

    expect(response.data).toEqual({ok: true});
    expect(response.status).toBe(200);
    expect(response.headers['x-csrf-token']).toBe('abc');
  });

  it('returns non-JSON bodies as text', async () => {
    respond('<p>hi</p>');
    expect((await createHttpClient().get('/x')).data).toBe('<p>hi</p>');
  });

  it('returns blobs when asked', async () => {
    respond('file contents');
    const {data} = await createHttpClient().get('/x', {responseType: 'blob'});

    expect(data).toBeInstanceOf(Blob);
    expect(await (data as Blob).text()).toBe('file contents');
  });

  it('rejects non-2xx responses with the parsed response attached', async () => {
    respond('{"message":"Nope"}', {status: 422});
    const error = await createHttpClient()
      .post('/save', {})
      .catch((e: unknown) => e);

    expect(isHttpError(error)).toBe(true);
    expect((error as HttpError).response?.status).toBe(422);
    expect((error as HttpError).response?.data).toEqual({message: 'Nope'});
    expect(isCancel(error)).toBe(false);
  });

  it('resolves statuses accepted by validateStatus', async () => {
    respond('{"conflict":true}', {status: 409});
    const response = await createHttpClient().get('/x', {
      validateStatus: (status) => status < 400 || status === 409,
    });

    expect(response.status).toBe(409);
  });
});

describe('cancellation', () => {
  function hangUntilAborted() {
    fetchMock.mockImplementationOnce(
      (_url: string, init: RequestInit) =>
        new Promise((_resolve, reject) => {
          init.signal!.addEventListener('abort', () =>
            reject(new DOMException('Aborted', 'AbortError'))
          );
        })
    );
  }

  it('rejects with a cancel error when the signal aborts', async () => {
    hangUntilAborted();
    const controller = new AbortController();
    const request = createHttpClient().get('/x', {signal: controller.signal});
    controller.abort();

    expect(isCancel(await request.catch((e: unknown) => e))).toBe(true);
  });

  it('does not send a request whose signal already aborted', async () => {
    const error = await createHttpClient()
      .get('/x', {signal: AbortSignal.abort()})
      .catch((e: unknown) => e);

    expect(isCancel(error)).toBe(true);
    expect(fetchMock).not.toHaveBeenCalled();
  });

  it('reports a timeout as a timeout, not a cancellation', async () => {
    vi.useFakeTimers();
    hangUntilAborted();
    const request = createHttpClient()
      .get('/x', {timeout: 50})
      .catch((e: unknown) => e);
    await vi.advanceTimersByTimeAsync(50);
    const error = await request;

    expect(isCancel(error)).toBe(false);
    expect((error as HttpError).code).toBe('ECONNABORTED');
  });
});

describe('interceptors', () => {
  it('lets a request interceptor rewrite the config before sending', async () => {
    const client = createHttpClient();
    client.interceptors.request.use((config) => ({
      ...config,
      url: `https://example.test/${config.url}`,
      headers: {...config.headers, 'X-Requested-With': 'XMLHttpRequest'},
    }));
    respond('{}');
    await client.get('users/session-info');

    const {url, init} = lastRequest();
    expect(url).toBe('https://example.test/users/session-info');
    expect(new Headers(init.headers).get('X-Requested-With')).toBe(
      'XMLHttpRequest'
    );
  });

  it('lets a response interceptor recover by retrying the request', async () => {
    const client = createHttpClient();
    client.interceptors.response.use(undefined, (error: HttpError) => {
      if (error.response?.status === 419 && !error.config.meta?.retried) {
        return client.request({...error.config, meta: {retried: true}});
      }
      throw error;
    });
    respond('{}', {status: 419});
    respond('{"saved":true}');

    const response = await client.post('/save', {a: 1});

    expect(response.data).toEqual({saved: true});
    expect(fetchMock).toHaveBeenCalledTimes(2);
    expect(lastRequest().init.body).toBe('{"a":1}');
  });
});
