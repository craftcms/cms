import {describe, expect, it} from 'vite-plus/test';
import {isSameOrigin, tokenizedPreviewUrl} from './previewUrl';

const params = {
  tokenParam: 'token',
  token: 'abc',
  previewParam: 'x-craft-live-preview',
  paramValue: 'signed',
  siteTokenParam: 'siteToken',
  siteToken: null,
};

describe('tokenizedPreviewUrl', () => {
  it('adds the preview param and token, keeping the query and hash', () => {
    const url = new URL(
      tokenizedPreviewUrl('https://site.test/news/a?page=2#top', params)
    );

    expect(url.searchParams.get('page')).toBe('2');
    expect(url.searchParams.get('x-craft-live-preview')).toBe('signed');
    expect(url.searchParams.get('token')).toBe('abc');
    expect(url.hash).toBe('#top');
  });

  it('adds the site token only when there is one', () => {
    expect(
      new URL(
        tokenizedPreviewUrl('https://site.test/', params)
      ).searchParams.has('siteToken')
    ).toBe(false);

    expect(
      new URL(
        tokenizedPreviewUrl('https://site.test/', {...params, siteToken: 'st'})
      ).searchParams.get('siteToken')
    ).toBe('st');
  });

  it('resolves relative URLs against the given base', () => {
    expect(
      tokenizedPreviewUrl('/news/a', params, 'https://cp.test/admin/entries')
    ).toBe('https://cp.test/news/a?x-craft-live-preview=signed&token=abc');
  });
});

describe('isSameOrigin', () => {
  it('compares origins', () => {
    expect(isSameOrigin('https://a.test/x', 'https://a.test')).toBe(true);
    expect(isSameOrigin('/x', 'https://a.test')).toBe(true);
    expect(isSameOrigin('https://b.test/x', 'https://a.test')).toBe(false);
  });
});
