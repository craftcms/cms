import {ConfigService} from '@craftcms/ui';
import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {AssistantRequestError, renderMarkdown, streamFetch} from './assistant';

describe('streamFetch', () => {
  beforeEach(() => {
    vi.stubGlobal('Cp', {registeredAssetBundles: [], registeredJsFiles: []});
    vi.stubGlobal('Craft', {});
    ConfigService.getInstance().initialize({
      actionUrl: 'https://example.test/admin/actions',
    });
  });

  afterEach(() => {
    vi.unstubAllGlobals();
    ConfigService.resetInstance();
  });

  it('returns the reply as a response that can be read as it streams', async () => {
    const fetch = vi.fn(
      async (_input: RequestInfo | URL, _init?: RequestInit) =>
        new Response('data: {}\n\n', {
          headers: {'Content-Type': 'text/event-stream'},
        })
    );
    vi.stubGlobal('fetch', fetch);

    const response = await streamFetch('/admin/actions/ai/assistant', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: '{"threadId":"thread-1"}',
    });

    expect(response.headers.get('content-type')).toBe('text/event-stream');
    expect(await response.text()).toBe('data: {}\n\n');
    expect(fetch.mock.calls[0]![1]).toMatchObject({
      method: 'POST',
      body: '{"threadId":"thread-1"}',
    });
  });

  it('fails with the server’s message', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () =>
        Response.json({message: 'The AI assistant is disabled.'}, {status: 422})
      )
    );

    const request = streamFetch('/admin/actions/ai/assistant', {
      method: 'POST',
    });

    await expect(request).rejects.toBeInstanceOf(AssistantRequestError);
    await expect(request).rejects.toThrow('The AI assistant is disabled.');
  });
});

describe('renderMarkdown', () => {
  it('escapes raw HTML, and drops links with executable URLs', () => {
    const html = renderMarkdown(
      '<img src=x onerror="alert(1)"> [Open](javascript:alert(1))'
    );
    const container = document.createElement('div');
    container.innerHTML = html;

    expect(container.querySelector('img')).toBeNull();
    expect(
      container.querySelector('a')?.getAttribute('href') ?? ''
    ).not.toContain('javascript');
  });

  it.each([
    ['a listed image', '![Chart](https://cdn.example.com/chart.png)', true],
    ['an unlisted image', '![Chart](https://evil.example/c.png?d=1)', false],
    [
      'an image file on the control panel’s origin',
      '![Logo](/uploads/logo.svg)',
      true,
    ],
    ['an action on the control panel’s origin', '![x](/admin/logout)', false],
    [
      'a listed image without alt text',
      '![](https://cdn.example.com/chart.png)',
      false,
    ],
  ])('shows %s inline: %s', (_, markdown, inline) => {
    const container = document.createElement('div');
    container.innerHTML = renderMarkdown(
      markdown,
      new Set(['https://cdn.example.com/chart.png'])
    );

    expect(container.querySelector('img') !== null).toBe(inline);
    expect(container.querySelector('a') !== null).toBe(!inline);
  });

  it('drops an image with an executable URL, keeping its alt text', () => {
    const container = document.createElement('div');
    container.innerHTML = renderMarkdown('![Bad](javascript:alert(1))');

    expect(container.querySelector('img, a')).toBeNull();
    expect(container.textContent).toContain('Bad');
  });
});
