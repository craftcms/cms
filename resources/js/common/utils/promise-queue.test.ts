import {afterEach, expect, it} from 'vite-plus/test';
import {PromiseQueue} from './promise-queue';

function deferred<T = void>() {
  let resolve!: (value: T) => void;
  let reject!: (reason: unknown) => void;
  const promise = new Promise<T>((res, rej) => {
    resolve = res;
    reject = rej;
  });

  return {promise, resolve, reject};
}

function setVisibility(state: DocumentVisibilityState): void {
  Object.defineProperty(document, 'visibilityState', {
    configurable: true,
    get: () => state,
  });
  document.dispatchEvent(new Event('visibilitychange'));
}

afterEach(() => {
  setVisibility('visible');
});

it('runs one job at a time, in order, and keeps going after a failure', async () => {
  const queue = new PromiseQueue();
  const log: string[] = [];
  const first = deferred<string>();

  const a = queue.push(() => {
    log.push('a');
    return first.promise;
  });
  const b = queue.push(async () => {
    log.push('b');
    throw new Error('b failed');
  });
  const c = queue.unshift(async () => {
    log.push('c');
    return 'c done';
  });

  expect(log).toEqual(['a']);

  first.resolve('a done');

  await expect(a).resolves.toBe('a done');
  await expect(c).resolves.toBe('c done');
  await expect(b).rejects.toThrow('b failed');
  expect(log).toEqual(['a', 'c', 'b']);
  expect(queue.running).toBe(false);
});

it('holds jobs while paused', async () => {
  const queue = new PromiseQueue();
  const log: string[] = [];

  queue.pause();
  const job = queue.push(async () => {
    log.push('ran');
  });
  await Promise.resolve();

  expect(log).toEqual([]);

  queue.resume();
  await job;

  expect(log).toEqual(['ran']);
});

it('waits for the tab to become visible before running jobs', async () => {
  const queue = new PromiseQueue();
  const log: string[] = [];

  setVisibility('hidden');
  const job = queue.push(async () => {
    log.push('ran');
  });
  await Promise.resolve();

  expect(queue.isPaused).toBe(true);
  expect(log).toEqual([]);

  setVisibility('visible');
  await job;

  expect(log).toEqual(['ran']);
});
