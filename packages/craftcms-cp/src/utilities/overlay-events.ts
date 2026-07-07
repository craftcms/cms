type OverlayHost = HTMLElement & {
  opened: boolean;
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
  let lastOpened = host.opened;

  host.addEventListener('opened-changed', () => {
    if (host.opened === lastOpened) {
      return;
    }

    lastOpened = host.opened;
    const opened = host.opened;

    host.dispatchEvent(
      new CustomEvent(opened ? 'craft-show' : 'craft-hide', {
        bubbles: true,
        composed: true,
      })
    );

    void host.updateComplete.then(() => {
      host.dispatchEvent(
        new CustomEvent(opened ? 'craft-after-show' : 'craft-after-hide', {
          bubbles: true,
          composed: true,
        })
      );
    });
  });
}
