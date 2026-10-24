import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {streamAction} from './stream-action';

vi.mock('@craftcms/ui', () => ({
  actionHeaders: () => ({}),
  csrf: {clearToken: vi.fn(), refreshToken: vi.fn()},
  getActionUrl: (url: string) => url,
}));

function respond(chunks: string[], init: ResponseInit = {}): Response {
  const encoder = new TextEncoder();

  return new Response(
    new ReadableStream({
      start(controller) {
        chunks.forEach((chunk) => controller.enqueue(encoder.encode(chunk)));
        controller.close();
      },
    }),
    init
  );
}

describe('streamAction', () => {
  afterEach(() => {
    vi.unstubAllGlobals();
  });

  it('calls back with each event, even when one arrives in pieces', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () =>
        respond([
          '{"type":"summary","delta":"Short',
          'ened."}\n{"type":"result","result":{"proposals":[]}}',
          '\n',
        ])
      )
    );
    const events: unknown[] = [];

    await streamAction(
      '/admin/actions/ai/ask',
      {prompt: 'Shorter'},
      {
        onEvent: (event) => events.push(event),
      }
    );

    expect(events).toEqual([
      {type: 'summary', delta: 'Shortened.'},
      {type: 'result', result: {proposals: []}},
    ]);
  });

  it('rejects with an error event, and stops reading the response', async () => {
    const cancel = vi.fn();
    vi.stubGlobal(
      'fetch',
      vi.fn(
        async () =>
          new Response(
            new ReadableStream({
              start(controller) {
                // The response stays open, as a reply still being generated would.
                controller.enqueue(
                  new TextEncoder().encode(
                    '{"type":"error","status":502,"message":"Failed"}\n'
                  )
                );
              },
              cancel,
            })
          )
      )
    );

    await expect(
      streamAction('/admin/actions/ai/ask', {}, {onEvent: () => {}})
    ).rejects.toEqual({
      response: {
        status: 502,
        data: {type: 'error', status: 502, message: 'Failed'},
      },
    });
    await vi.waitFor(() => expect(cancel).toHaveBeenCalled());
  });

  it('rejects with the response a request fails with before streaming', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () =>
        respond(['{"message":"The prompt field is required."}'], {status: 422})
      )
    );

    await expect(
      streamAction('/admin/actions/ai/ask', {}, {onEvent: () => {}})
    ).rejects.toEqual({
      response: {status: 422, data: {message: 'The prompt field is required.'}},
    });
    // Laravel only returns errors as JSON when JSON is the first type accepted.
    expect(vi.mocked(fetch).mock.calls[0]![1]!.headers).toMatchObject({
      Accept: 'application/json',
    });
  });

  it('retries once with a fresh CSRF token, even an empty one', async () => {
    vi.stubGlobal(
      'fetch',
      vi.fn(async () => respond([''], {status: 419}))
    );

    await expect(
      streamAction('/admin/actions/ai/ask', {}, {onEvent: () => {}})
    ).rejects.toMatchObject({response: {status: 419}});
    expect(fetch).toHaveBeenCalledTimes(2);
  });
});
