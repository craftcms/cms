// `opened` is `unknown` because Lion's JSDoc types it as the boxed `Boolean`;
// runtime values are primitive booleans.
type OverlayHost = HTMLElement & {
  opened: unknown;
  updateComplete: Promise<boolean>;
};

/**
 * Bridges Lion's `opened-changed` event to the package's public overlay
 * lifecycle events. `craft-show`/`craft-hide` fire synchronously when the
 * `opened` state flips; `craft-after-show`/`craft-after-hide` fire once the
 * host's update has completed.
 *
 * All events bubble and are composed, so listeners outside a host's shadow
 * root (e.g. `craft-info-icon` listening for its inner tooltip) receive them.
 */
export function wireOverlayLifecycleEvents(host: OverlayHost): void {
  let lastOpened = Boolean(host.opened);

  host.addEventListener('opened-changed', () => {
    const opened = Boolean(host.opened);

    if (opened === lastOpened) {
      return;
    }

    lastOpened = opened;

    host.dispatchEvent(
      new CustomEvent(opened ? 'craft-show' : 'craft-hide', {
        bubbles: true,
        composed: true,
      })
    );

    const expectedOpened = opened;
    void host.updateComplete.then(() => {
      if (Boolean(host.opened) !== expectedOpened) {
        return;
      }

      host.dispatchEvent(
        new CustomEvent(
          expectedOpened ? 'craft-after-show' : 'craft-after-hide',
          {
            bubbles: true,
            composed: true,
          }
        )
      );
    });
  });
}
