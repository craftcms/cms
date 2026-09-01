import {describe, expect, it, vi} from 'vite-plus/test';
import {ElevatedSessionManager} from './manager';

describe('ElevatedSessionManager', () => {
  it('uses the default minimum remaining time', async () => {
    const request = vi.fn().mockResolvedValue({confirmed: true, timeout: 5});
    const manager = new ElevatedSessionManager(request);

    await expect(manager.require()).resolves.toBe(true);
    expect(request).toHaveBeenCalledWith({
      minimumRemainingSeconds: 5,
      force: false,
    });
  });

  it('coalesces confirmation checks using the highest minimum', async () => {
    const request = vi.fn().mockResolvedValue({confirmed: true, timeout: 30});
    const manager = new ElevatedSessionManager(request);

    const first = manager.require({minimumRemainingSeconds: 10});
    const second = manager.require({minimumRemainingSeconds: 30});

    await expect(Promise.all([first, second])).resolves.toEqual([true, true]);
    expect(request).toHaveBeenCalledTimes(1);
    expect(request).toHaveBeenCalledWith({
      minimumRemainingSeconds: 30,
      force: false,
    });
  });

  it('rechecks when a stricter minimum arrives during a request', async () => {
    let resolveFirst!: (response: {
      confirmed: boolean;
      timeout: number;
    }) => void;
    const firstResponse = new Promise<{confirmed: boolean; timeout: number}>(
      (resolve) => {
        resolveFirst = resolve;
      }
    );
    let resolveSecond!: (response: {
      confirmed: boolean;
      timeout: number;
    }) => void;
    const secondResponse = new Promise<{confirmed: boolean; timeout: number}>(
      (resolve) => {
        resolveSecond = resolve;
      }
    );
    const request = vi
      .fn()
      .mockReturnValueOnce(firstResponse)
      .mockReturnValueOnce(secondResponse);
    const manager = new ElevatedSessionManager(request);

    const first = manager.require({minimumRemainingSeconds: 10});
    await Promise.resolve();
    const second = manager.require({minimumRemainingSeconds: 30});
    resolveFirst({confirmed: true, timeout: 10});
    await Promise.resolve();
    await Promise.resolve();

    expect(manager.state.checking).toBe(true);
    resolveSecond({confirmed: true, timeout: 30});

    await expect(Promise.all([first, second])).resolves.toEqual([true, true]);
    expect(request).toHaveBeenNthCalledWith(1, {
      minimumRemainingSeconds: 10,
      force: false,
    });
    expect(request).toHaveBeenNthCalledWith(2, {
      minimumRemainingSeconds: 30,
      force: false,
    });
  });

  it('resolves every waiter when the prompt succeeds', async () => {
    const manager = new ElevatedSessionManager(async () => ({
      confirmed: false,
      timeout: 0,
      loginName: 'admin@example.com',
      alternativeLoginMethods: null,
    }));

    const first = manager.require();
    const second = manager.require();
    await Promise.resolve();
    await Promise.resolve();

    expect(manager.state.active).toBe(true);
    expect(manager.state.loginName).toBe('admin@example.com');
    manager.confirm();

    await expect(Promise.all([first, second])).resolves.toEqual([true, true]);
  });

  it('returns undefined without running the callback when canceled', async () => {
    const manager = new ElevatedSessionManager(async () => ({
      confirmed: false,
      timeout: 0,
      loginName: 'admin@example.com',
    }));
    const callback = vi.fn();
    const result = manager.run(callback);
    await Promise.resolve();
    await Promise.resolve();
    manager.cancel();

    await expect(result).resolves.toBeUndefined();
    expect(callback).not.toHaveBeenCalled();
  });

  it('forces confirmation and retries once after a 423 response', async () => {
    const request = vi
      .fn()
      .mockResolvedValueOnce({confirmed: true, timeout: 10})
      .mockResolvedValueOnce({confirmed: true, timeout: 10});
    const callback = vi
      .fn()
      .mockRejectedValueOnce({response: {status: 423}})
      .mockResolvedValueOnce('saved');
    const manager = new ElevatedSessionManager(request);

    await expect(manager.run(callback)).resolves.toBe('saved');
    expect(callback).toHaveBeenCalledTimes(2);
    expect(request).toHaveBeenLastCalledWith({
      minimumRemainingSeconds: 5,
      force: true,
    });
  });

  it('propagates a second 423 response', async () => {
    const error = {response: {status: 423}};
    const manager = new ElevatedSessionManager(async () => ({
      confirmed: true,
      timeout: 10,
    }));

    await expect(manager.run(async () => Promise.reject(error))).rejects.toBe(
      error
    );
  });

  it('propagates errors other than 423 without another check', async () => {
    const request = vi
      .fn()
      .mockResolvedValue({confirmed: true, timeout: false});
    const error = new Error('No connection');
    const manager = new ElevatedSessionManager(request);

    await expect(manager.run(async () => Promise.reject(error))).rejects.toBe(
      error
    );
    expect(request).toHaveBeenCalledTimes(1);
  });
});
