import {afterEach, beforeEach, describe, expect, it, vi} from 'vitest';
import {ConfigService} from '../../services/Config';
import {actionClient} from './actionClient';

// The request interceptor references a bare `Cp` global and `window.Craft` when
// building action headers; provide inert stand-ins.
let fetchMock: ReturnType<typeof vi.fn>;

beforeEach(() => {
  fetchMock = vi.fn(async () => new Response('{}'));
  vi.stubGlobal('fetch', fetchMock);
  (globalThis as any).Cp = {registeredAssetBundles: [], registeredJsFiles: []};
  (globalThis as any).Craft = {};
  ConfigService.resetInstance();
});

afterEach(() => {
  vi.unstubAllGlobals();
  delete (globalThis as any).Cp;
  delete (globalThis as any).Craft;
  ConfigService.resetInstance();
});

async function requestedUrl(url: string): Promise<string> {
  await actionClient.get(url);

  return fetchMock.mock.lastCall![0];
}

describe('actionClient request URL resolution', () => {
  it('preserves a query string on the action base URL for bare paths (multi-site)', async () => {
    ConfigService.getInstance().initialize({
      actionUrl: 'https://example.test/admin/actions?site=default',
    });

    const url = await requestedUrl('users/confirm-password');

    // The path must extend the pathname and keep the query intact — NOT
    // `?site=default/users/confirm-password`, which is what naive baseURL
    // string concatenation produced.
    expect(url).toBe(
      'https://example.test/admin/actions/users/confirm-password?site=default'
    );
  });

  it('expands bare paths against a clean action base URL (single-site)', async () => {
    ConfigService.getInstance().initialize({
      actionUrl: 'https://example.test/admin/actions',
    });

    const url = await requestedUrl('auth/verify-totp');

    expect(url).toBe('https://example.test/admin/actions/auth/verify-totp');
  });

  it('resolves /-prefixed Wayfinder route paths against the origin only', async () => {
    ConfigService.getInstance().initialize({
      actionUrl: 'https://example.test/admin/actions?site=default',
    });

    const url = await requestedUrl('/admin/actions/fields/render-settings');

    expect(url).toBe(
      'https://example.test/admin/actions/fields/render-settings'
    );
  });

  it('leaves absolute URLs untouched', async () => {
    ConfigService.getInstance().initialize({
      actionUrl: 'https://example.test/admin/actions',
    });

    const url = await requestedUrl('https://other.test/thing');

    expect(url).toBe('https://other.test/thing');
  });
});

describe('actionClient CSRF recovery', () => {
  beforeEach(() => {
    ConfigService.getInstance().initialize({
      actionUrl: 'https://example.test/admin/actions',
    });
  });

  function sentToken(callIndex: number): string | null {
    const init = fetchMock.mock.calls[callIndex]![1] as RequestInit;
    return new Headers(init.headers).get('X-CSRF-Token');
  }

  it('refreshes the token and retries once after a 419', async () => {
    fetchMock
      .mockResolvedValueOnce(new Response('{}', {status: 419}))
      .mockResolvedValueOnce(
        new Response('{"csrfTokenName":"CRAFT_CSRF","csrfTokenValue":"fresh"}')
      )
      .mockResolvedValueOnce(new Response('{"saved":true}'));

    const {data} = await actionClient.post('entries/save', {title: 'Hi'});

    expect(data).toEqual({saved: true});
    expect(fetchMock.mock.calls[1]![0]).toBe(
      'https://example.test/admin/actions/users/session-info'
    );
    expect(sentToken(2)).toBe('fresh');
  });

  it('gives up when the retried request is rejected again', async () => {
    fetchMock
      .mockResolvedValueOnce(new Response('{}', {status: 419}))
      .mockResolvedValueOnce(
        new Response('{"csrfTokenName":"CRAFT_CSRF","csrfTokenValue":"fresh"}')
      )
      .mockResolvedValueOnce(new Response('{}', {status: 419}));

    await expect(actionClient.post('entries/save', {})).rejects.toMatchObject({
      response: {status: 419},
    });
    expect(fetchMock).toHaveBeenCalledTimes(3);
  });
});
