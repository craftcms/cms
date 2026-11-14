import {beforeEach, describe, expect, it, vi} from 'vite-plus/test';

async function loadLifecycle() {
  vi.resetModules();

  return import('./lifecycle');
}

describe('boot lifecycle', () => {
  let lifecycle: Awaited<ReturnType<typeof loadLifecycle>>;

  beforeEach(async () => {
    lifecycle = await loadLifecycle();
  });

  it('runs callbacks registered before a phase when it starts, in order', () => {
    const calls: string[] = [];
    lifecycle.onBooted(() => calls.push('booted'));
    lifecycle.onBooting(() => calls.push('booting 1'));
    lifecycle.onBooting(() => calls.push('booting 2'));

    expect(calls).toEqual([]);

    lifecycle.runLifecyclePhase('booting');
    expect(calls).toEqual(['booting 1', 'booting 2']);

    lifecycle.runLifecyclePhase('booted');
    expect(calls).toEqual(['booting 1', 'booting 2', 'booted']);
  });

  it('runs callbacks registered after their phase straight away', () => {
    lifecycle.runLifecyclePhase('booting');
    lifecycle.runLifecyclePhase('booted');

    const booting = vi.fn();
    const booted = vi.fn();
    lifecycle.onBooting(booting);
    lifecycle.onBooted(booted);

    expect(booting).toHaveBeenCalledOnce();
    expect(booted).toHaveBeenCalledOnce();
  });
});
