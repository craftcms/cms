import {detectPlatform} from '@tanstack/hotkeys';
import {afterEach, describe, expect, it, vi} from 'vite-plus/test';
import {registerShortcut} from '.';

const mod = detectPlatform() === 'mac' ? 'metaKey' : 'ctrlKey';

function press(target: EventTarget = document): KeyboardEvent {
  const event = new KeyboardEvent('keydown', {
    key: 's',
    code: 'KeyS',
    [mod]: true,
    bubbles: true,
    cancelable: true,
  });
  target.dispatchEvent(event);

  return event;
}

describe('registerShortcut', () => {
  const cleanups: Array<() => void> = [];

  afterEach(() => cleanups.splice(0).forEach((cleanup) => cleanup()));

  it('runs and prevents the default, unless something already claimed the press', () => {
    const callback = vi.fn();
    cleanups.push(registerShortcut('Mod+S', callback).unregister);

    expect(press().defaultPrevented).toBe(true);
    expect(callback).toHaveBeenCalledTimes(1);

    const input = document.createElement('textarea');
    document.body.append(input);
    cleanups.push(() => input.remove());
    input.addEventListener('keydown', (event) => event.preventDefault());
    press(input);

    expect(callback).toHaveBeenCalledTimes(1);
  });

  it('leaves the press alone while unavailable, unless told to always prevent it', () => {
    const callback = vi.fn();
    cleanups.push(
      registerShortcut('Mod+S', callback, {isAvailable: () => false}).unregister
    );

    expect(press().defaultPrevented).toBe(false);

    cleanups.push(
      registerShortcut('Mod+S', callback, {
        isAvailable: () => false,
        preventDefault: 'always',
      }).unregister
    );

    expect(press().defaultPrevented).toBe(true);
    expect(callback).not.toHaveBeenCalled();
  });

  it('leaves the default when the callback returns false', () => {
    cleanups.push(registerShortcut('Mod+S', () => false).unregister);

    expect(press().defaultPrevented).toBe(false);
  });
});
