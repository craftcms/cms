import {describe, expect, it} from 'vite-plus/test';
import {announceOverlayState} from './overlay-events.js';

type FakeHost = HTMLElement & {
  open: boolean;
  updateComplete: Promise<boolean>;
};

function createHost(): FakeHost {
  const host = document.createElement('div') as unknown as FakeHost;
  host.open = false;
  host.updateComplete = Promise.resolve(true);
  document.body.append(host as unknown as HTMLElement);
  return host;
}

function flush(): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve));
}

describe('announceOverlayState', () => {
  it('dispatches craft-show then craft-after-show when opened', async () => {
    const host = createHost();
    const events: string[] = [];
    for (const type of [
      'craft-show',
      'craft-after-show',
      'craft-hide',
      'craft-after-hide',
    ]) {
      host.addEventListener(type, () => events.push(type));
    }

    host.open = true;
    announceOverlayState(host);
    await flush();

    expect(events).toEqual(['craft-show', 'craft-after-show']);
  });

  it('dispatches craft-hide then craft-after-hide when closed', async () => {
    const host = createHost();
    const events: string[] = [];
    for (const type of [
      'craft-show',
      'craft-after-show',
      'craft-hide',
      'craft-after-hide',
    ]) {
      host.addEventListener(type, () => events.push(type));
    }

    host.open = true;
    announceOverlayState(host);
    await flush();
    host.open = false;
    announceOverlayState(host);
    await flush();

    expect(events).toEqual([
      'craft-show',
      'craft-after-show',
      'craft-hide',
      'craft-after-hide',
    ]);
  });

  it('does nothing when the state has not changed', async () => {
    const host = createHost();
    const events: string[] = [];
    host.addEventListener('craft-show', () => events.push('craft-show'));
    host.addEventListener('craft-hide', () => events.push('craft-hide'));

    announceOverlayState(host);
    await flush();

    expect(events).toEqual([]);
  });

  it('dispatches events that bubble and cross shadow boundaries', async () => {
    const host = createHost();
    let event: Event | null = null;
    document.body.addEventListener('craft-show', (e) => {
      event = e;
    });

    host.open = true;
    announceOverlayState(host);
    await flush();

    expect(event).not.toBeNull();
    expect(event!.composed).toBe(true);
  });
});
