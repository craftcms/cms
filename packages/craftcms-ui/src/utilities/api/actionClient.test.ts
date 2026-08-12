import {AxiosHeaders, type InternalAxiosRequestConfig} from 'axios';
import {afterEach, beforeEach, describe, expect, it} from 'vitest';
import {ConfigService} from '../../services/Config';
import {actionClient} from './actionClient';

// The request interceptor references a bare `Cp` global and `window.Craft` when
// building action headers; provide inert stand-ins.
beforeEach(() => {
  (globalThis as any).Cp = {registeredAssetBundles: [], registeredJsFiles: []};
  (globalThis as any).Craft = {};
  ConfigService.resetInstance();
});

afterEach(() => {
  delete (globalThis as any).Cp;
  delete (globalThis as any).Craft;
  ConfigService.resetInstance();
});

function runRequestInterceptor(
  url: string
): Promise<InternalAxiosRequestConfig> {
  const handler = (actionClient.interceptors.request as any).handlers[0]
    .fulfilled;

  return handler({url, headers: new AxiosHeaders()});
}

describe('actionClient request URL resolution', () => {
  it('preserves a query string on the action base URL for bare paths (multi-site)', async () => {
    ConfigService.getInstance().initialize({
      actionUrl: 'https://example.test/admin/actions?site=default',
    });

    const config = await runRequestInterceptor('users/confirm-password');

    // The path must extend the pathname and keep the query intact — NOT
    // `?site=default/users/confirm-password`, which is what naive baseURL
    // string concatenation produced.
    expect(config.url).toBe(
      'https://example.test/admin/actions/users/confirm-password?site=default'
    );
  });

  it('expands bare paths against a clean action base URL (single-site)', async () => {
    ConfigService.getInstance().initialize({
      actionUrl: 'https://example.test/admin/actions',
    });

    const config = await runRequestInterceptor('auth/verify-totp');

    expect(config.url).toBe(
      'https://example.test/admin/actions/auth/verify-totp'
    );
  });

  it('resolves /-prefixed Wayfinder route paths against the origin only', async () => {
    ConfigService.getInstance().initialize({
      actionUrl: 'https://example.test/admin/actions?site=default',
    });

    const config = await runRequestInterceptor(
      '/admin/actions/fields/render-settings'
    );

    expect(config.baseURL).toBe('https://example.test');
    expect(config.url).toBe('/admin/actions/fields/render-settings');
  });

  it('leaves absolute URLs untouched', async () => {
    ConfigService.getInstance().initialize({
      actionUrl: 'https://example.test/admin/actions',
    });

    const config = await runRequestInterceptor('https://other.test/thing');

    expect(config.url).toBe('https://other.test/thing');
    expect(config.baseURL).toBeUndefined();
  });
});
