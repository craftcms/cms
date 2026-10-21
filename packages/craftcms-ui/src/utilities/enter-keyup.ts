/**
 * Keeps an Enter press that closes an overlay from also activating whatever
 * gets focus next.
 */

/**
 * Whether Enter is down right now, anywhere on the page.
 *
 * Kept for the page rather than per overlay, and registered before any trap
 * below, so it sees every keyup — including one a trap swallows, or one that
 * lands outside the overlay the press began in — and can't go stale.
 */
let enterDown = false;
let swallowingEnterKeyup = false;

window.addEventListener(
  'keydown',
  (event) => {
    if (event.key === 'Enter') {
      enterDown = true;
    }
  },
  true
);
window.addEventListener(
  'keyup',
  (event) => {
    if (event.key === 'Enter') {
      enterDown = false;
    }
  },
  true
);
window.addEventListener('blur', () => {
  enterDown = false;
});

/**
 * Swallows the keyup of an Enter press that's still down as an overlay closes.
 *
 * Enter activates a native button on keydown, so by the time the key comes
 * back up, focus has gone back to whatever opened the overlay. A control that
 * activates on keyup — a `craft-button` clicks on any Enter keyup, wherever
 * the keydown landed — would take it as a press of its own and, usually, open
 * the overlay straight back up.
 *
 * Caught on the window in the capture phase so nothing further down sees it.
 * Lasts for one press: the keyup ends it, and so does a fresh Enter keydown or
 * the window losing focus, either of which means that keyup isn't coming.
 */
export function swallowHeldEnterKeyup(): void {
  if (!enterDown || swallowingEnterKeyup) {
    return;
  }

  swallowingEnterKeyup = true;

  const release = (): void => {
    swallowingEnterKeyup = false;
    window.removeEventListener('keyup', onKeyup, true);
    window.removeEventListener('keydown', onKeydown, true);
    window.removeEventListener('blur', release);
  };

  const onKeyup = (event: KeyboardEvent): void => {
    if (event.key === 'Enter') {
      event.preventDefault();
      event.stopImmediatePropagation();
      release();
    }
  };

  const onKeydown = (event: KeyboardEvent): void => {
    if (event.key === 'Enter' && !event.repeat) {
      release();
    }
  };

  window.addEventListener('keyup', onKeyup, true);
  window.addEventListener('keydown', onKeydown, true);
  window.addEventListener('blur', release);
}
