import {afterEach, beforeEach, describe, expect, it, vi} from 'vite-plus/test';
// The legacy manager, which slideouts join on pages that load the legacy
// bundle (see panel-stack.ts).
import Garnish from '../../../../packages/craftcms-legacy/garnish/src/Garnish.js';

interface LegacyUiLayerManager {
  registerShortcut(shortcut: number, callback: (ev: unknown) => void): void;
  destroy(): void;
}

describe('legacy Garnish UiLayerManager', () => {
  let manager: LegacyUiLayerManager;
  let inner: HTMLButtonElement;

  beforeEach(() => {
    document.body.innerHTML =
      '<div class="slideout"><button>Inside</button></div>';
    inner = document.querySelector('button')!;
    manager = new (
      Garnish as {UiLayerManager: new () => LegacyUiLayerManager}
    ).UiLayerManager();
  });

  afterEach(() => {
    manager.destroy();
    document.body.innerHTML = '';
  });

  /** Presses Escape on the button, as a menu or tooltip inside a slideout would hear it first. */
  function pressEscape({claimedInside}: {claimedInside: boolean}): void {
    if (claimedInside) {
      inner.addEventListener('keydown', (event) => event.preventDefault(), {
        once: true,
      });
    }
    inner.dispatchEvent(
      new KeyboardEvent('keydown', {
        key: 'Escape',
        keyCode: 27,
        bubbles: true,
        cancelable: true,
      } as never)
    );
  }

  it('closes the top layer on an Escape nothing inside it handled', () => {
    const close = vi.fn();
    manager.registerShortcut(27, close);

    pressEscape({claimedInside: false});

    expect(close).toHaveBeenCalledOnce();
  });

  it('leaves the top layer open on an Escape that something inside it already handled', () => {
    const close = vi.fn();
    manager.registerShortcut(27, close);

    pressEscape({claimedInside: true});

    expect(close).not.toHaveBeenCalled();
  });
});
