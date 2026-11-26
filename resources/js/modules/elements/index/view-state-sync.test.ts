import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';

const {post, handlers} = vi.hoisted(() => ({
  post: vi.fn(),
  handlers: {} as Record<string, (event: unknown) => void>,
}));

vi.mock('@craftcms/ui', () => ({actionClient: {post}}));
vi.mock('@inertiajs/vue3', () => ({
  router: {
    flushAll: vi.fn(),
    on: (name: string, handler: (event: unknown) => void) => {
      handlers[name] = handler;
    },
  },
}));

const {installElementIndexViewStateSync, pushElementIndexViewStates} =
  await import('./view-state-sync');

const navigate = (synced: boolean) =>
  handlers.navigate!({
    detail: {
      page: {
        props: {craft: {currentUser: {elementIndexViewStatesSynced: synced}}},
      },
    },
  });

describe('view-state-sync', () => {
  beforeEach(() => post.mockReset());

  it('sends a push only once the one before it has landed', async () => {
    let land!: () => void;
    post.mockReturnValueOnce(new Promise<void>((r) => (land = r)));
    post.mockResolvedValueOnce({});

    const first = pushElementIndexViewStates({a: {} as never});
    const second = pushElementIndexViewStates({b: {} as never});
    await Promise.resolve();
    await Promise.resolve();

    expect(post).toHaveBeenCalledOnce();

    land();
    expect(await first).toBe(true);
    expect(await second).toBe(true);
    expect(post).toHaveBeenCalledTimes(2);
    expect(post.mock.calls[1]![1]).toEqual({states: {b: {}}, all: false});
  });

  it('retries the full push on the next navigation when it fails', async () => {
    installElementIndexViewStateSync();
    post.mockRejectedValueOnce(new Error('offline'));
    post.mockResolvedValueOnce({});

    navigate(false);
    await vi.waitFor(() => expect(post).toHaveBeenCalledOnce());
    await new Promise((r) => setTimeout(r));

    navigate(false);
    await vi.waitFor(() => expect(post).toHaveBeenCalledTimes(2));
    expect(post.mock.calls[1]![1]).toMatchObject({all: true});
  });
});
