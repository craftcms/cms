import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
import {
  expiryFromHeaders,
  SessionTimeoutManager,
  type SessionInfo,
  WARNING_SECONDS,
} from './manager';

const nowSeconds = () => Math.floor(Date.now() / 1000);

function createManager(info: () => SessionInfo) {
  let listener: ((event: MessageEvent) => void) | undefined;
  const channel = {
    postMessage: vi.fn(),
    addEventListener: vi.fn(
      (_type: string, fn: (event: MessageEvent) => void) => (listener = fn)
    ),
  };
  const fetchInfo = vi.fn(async () => info());
  const renew = vi.fn(async () => nowSeconds() + 3600);
  const manager = new SessionTimeoutManager({
    fetchInfo,
    renew,
    now: () => Date.now(),
    channel: channel as any,
  });

  return {
    manager,
    fetchInfo,
    renew,
    channel,
    receive: (data: unknown) => listener?.({data} as MessageEvent),
  };
}

describe('SessionTimeoutManager', () => {
  beforeEach(() => {
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-10-25T12:00:00Z'));
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('warns shortly before the session expires', async () => {
    const expiresAt = nowSeconds() + 600;
    const {manager, fetchInfo} = createManager(() => ({
      isGuest: false,
      sessionExpiresAt: expiresAt,
    }));
    manager.start(expiresAt);

    await vi.advanceTimersByTimeAsync((600 - WARNING_SECONDS - 1) * 1000);
    expect(fetchInfo).not.toHaveBeenCalled();
    expect(manager.state.phase).toBe('active');

    await vi.advanceTimersByTimeAsync(1000);
    expect(fetchInfo).toHaveBeenCalledOnce();
    expect(manager.state.phase).toBe('warning');
  });

  it('stays quiet when another tab has kept the session alive', async () => {
    const extended = nowSeconds() + 3600;
    const {manager} = createManager(() => ({
      isGuest: false,
      sessionExpiresAt: extended,
    }));
    manager.start(nowSeconds() + 60);

    await vi.advanceTimersByTimeAsync(0);

    expect(manager.state.phase).toBe('active');
    expect(manager.state.expiresAt).toBe(extended);
  });

  it('reports the session expired once it has', async () => {
    const expiresAt = nowSeconds() + 60;
    let signedIn = true;
    const {manager} = createManager(() => ({
      isGuest: !signedIn,
      sessionExpiresAt: signedIn ? expiresAt : null,
    }));
    manager.start(expiresAt);
    await vi.advanceTimersByTimeAsync(0);
    expect(manager.state.phase).toBe('warning');

    signedIn = false;
    await vi.advanceTimersByTimeAsync(60 * 1000);

    expect(manager.state.phase).toBe('expired');
  });

  it('ends the warning when a response extends the session, and tells other tabs', async () => {
    const expiresAt = nowSeconds() + 60;
    const {manager, channel} = createManager(() => ({
      isGuest: false,
      sessionExpiresAt: expiresAt,
    }));
    manager.start(expiresAt);
    await vi.advanceTimersByTimeAsync(0);

    manager.update(nowSeconds() + 3600);

    expect(manager.state.phase).toBe('active');
    expect(channel.postMessage).toHaveBeenCalledWith({
      type: 'expires',
      expiresAt: nowSeconds() + 3600,
    });
  });

  it('takes expiries from other tabs without echoing them', () => {
    const {manager, channel, receive} = createManager(() => ({
      isGuest: false,
      sessionExpiresAt: null,
    }));
    manager.start(nowSeconds() + 600);

    receive({type: 'expires', expiresAt: nowSeconds() + 1200});

    expect(manager.state.expiresAt).toBe(nowSeconds() + 1200);
    expect(channel.postMessage).not.toHaveBeenCalled();
  });

  it('keeps the session alive on request', async () => {
    const {manager, renew} = createManager(() => ({
      isGuest: false,
      sessionExpiresAt: null,
    }));
    manager.start(nowSeconds() + 60);

    await manager.renew();

    expect(renew).toHaveBeenCalledOnce();
    expect(manager.state.expiresAt).toBe(nowSeconds() + 3600);
  });

  it('never reports an untracked session as expired', async () => {
    const {manager} = createManager(() => ({
      isGuest: true,
      sessionExpiresAt: null,
    }));
    manager.start(null);

    await manager.check();

    expect(manager.state.phase).toBe('active');
  });
});

describe('expiryFromHeaders', () => {
  it('reads the expiry whatever the header name’s case', () => {
    expect(expiryFromHeaders({'X-Craft-Session-Expires': '1790000000'})).toBe(
      1790000000
    );
    expect(expiryFromHeaders({'x-craft-session-expires': '1790000000'})).toBe(
      1790000000
    );
    expect(expiryFromHeaders({'content-type': 'text/html'})).toBeUndefined();
  });
});
