import {csrf} from '@craftcms/ui';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {sessionTimeoutManager} from '@/modules/auth/session-timeout/manager';
import {actionFetch, AssistantRequestError, renderMarkdown} from './assistant';

vi.mock('@craftcms/ui', () => ({
  actionHeaders: () => ({}),
  csrf: {tokenValue: null, clearToken: vi.fn(), refreshToken: vi.fn()},
  getActionUrl: (url: string) => url,
}));

describe('actionFetch', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
    vi.clearAllMocks();
  });

  it('retries once with a fresh CSRF token when the token is rejected', async () => {
    const fetch = vi
      .fn()
      .mockResolvedValueOnce(new Response(null, {status: 419}))
      .mockResolvedValueOnce(new Response('data: {}\n\n', {status: 200}));
    vi.stubGlobal('fetch', fetch);
    vi.mocked(csrf.refreshToken).mockResolvedValue('fresh-token');

    const response = await actionFetch('ai/assistant', {method: 'POST'});

    expect(response.ok).toBe(true);
    expect(fetch).toHaveBeenCalledTimes(2);
    expect(fetch.mock.calls[1]![1].headers['X-CSRF-Token']).toBe('fresh-token');
  });

  it('tells the session timeout when the session now expires', async () => {
    const update = vi
      .spyOn(sessionTimeoutManager, 'update')
      .mockImplementation(() => {});
    vi.stubGlobal(
      'fetch',
      vi.fn(
        async () =>
          new Response('data: {}\n\n', {
            headers: {'X-Craft-Session-Expires': '1900000000'},
          })
      )
    );

    await actionFetch('ai/assistant', {method: 'POST'});

    expect(update).toHaveBeenCalledWith(1900000000);
  });

  it('fails with the server’s message', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () =>
        Response.json({message: 'The AI assistant is disabled.'}, {status: 422})
      )
    );

    const request = actionFetch('ai/assistant', {method: 'POST'});

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
