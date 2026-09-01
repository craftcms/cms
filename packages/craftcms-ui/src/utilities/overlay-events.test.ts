import {describe, expect, it} from 'vite-plus/test';
import {wireOverlayLifecycleEvents} from './overlay-events.js';

type FakeHost = HTMLElement & {
  opened: boolean;
  updateComplete: Promise<boolean>;
};

function createHost(): FakeHost {
  const host = document.createElement('div') as unknown as FakeHost;
  host.opened = false;
  host.updateComplete = Promise.resolve(true);
  document.body.append(host as unknown as HTMLElement);
  return host;
}

function flush(): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve));
}

describe('wireOverlayLifecycleEvents', () => {
  it('dispatches craft-show then craft-after-show when opened', async () => {
    const host = createHost();
    wireOverlayLifecycleEvents(host);
    const events: string[] = [];
    for (const type of [
      'craft-show',
      'craft-after-show',
      'craft-hide',
      'craft-after-hide',
    ]) {
      host.addEventListener(type, () => events.push(type));
    }

    host.opened = true;
    host.dispatchEvent(new CustomEvent('opened-changed'));
    await flush();

    expect(events).toEqual(['craft-show', 'craft-after-show']);
  });

  it('dispatches craft-hide then craft-after-hide when closed', async () => {
    const host = createHost();
    wireOverlayLifecycleEvents(host);
    const events: string[] = [];
    for (const type of [
      'craft-show',
      'craft-after-show',
      'craft-hide',
      'craft-after-hide',
    ]) {
      host.addEventListener(type, () => events.push(type));
    }

    host.opened = true;
    host.dispatchEvent(new CustomEvent('opened-changed'));
    await flush();
    host.opened = false;
    host.dispatchEvent(new CustomEvent('opened-changed'));
    await flush();

    expect(events).toEqual([
      'craft-show',
      'craft-after-show',
      'craft-hide',
      'craft-after-hide',
    ]);
  });

  it('ignores opened-changed events that do not change state', async () => {
    const host = createHost();
    wireOverlayLifecycleEvents(host);
    const events: string[] = [];
    host.addEventListener('craft-show', () => events.push('craft-show'));
    host.addEventListener('craft-hide', () => events.push('craft-hide'));

    host.dispatchEvent(new CustomEvent('opened-changed'));
    await flush();

    expect(events).toEqual([]);
  });

  it('dispatches events that bubble and cross shadow boundaries', async () => {
    const host = createHost();
    wireOverlayLifecycleEvents(host);
    let event: Event | null = null;
    document.body.addEventListener('craft-show', (e) => {
      event = e;
    });

    host.opened = true;
    host.dispatchEvent(new CustomEvent('opened-changed'));
    await flush();

    expect(event).not.toBeNull();
    expect(event!.composed).toBe(true);
  });
});
