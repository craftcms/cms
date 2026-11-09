type OverlayHost = HTMLElement & {
  open: boolean;
  updateComplete: Promise<boolean>;
};

/** The state each overlay last announced. Overlays start closed. */
const announced = new WeakMap<OverlayHost, boolean>();

/**
 * Announces a change of an overlay's `open` with the package's lifecycle
 * events: `craft-show`/`craft-hide` straight away, then
 * `craft-after-show`/`craft-after-hide` once the host's update has completed.
 * Call it whenever `open` may have changed; it does nothing when the state
 * is the one last announced.
 *
 * The events bubble and are composed, so listeners outside a host's shadow
 * root (e.g. `craft-info-icon` listening for its inner tooltip) receive them.
 */
export function announceOverlayState(host: OverlayHost): void {
  const open = Boolean(host.open);

  if (open === (announced.get(host) ?? false)) {
    return;
  }

  announced.set(host, open);

  host.dispatchEvent(
    new CustomEvent(open ? 'craft-show' : 'craft-hide', {
      bubbles: true,
      composed: true,
    })
  );

  void host.updateComplete.then(() => {
    if (Boolean(host.open) !== open) {
      return;
    }

    host.dispatchEvent(
      new CustomEvent(open ? 'craft-after-show' : 'craft-after-hide', {
        bubbles: true,
        composed: true,
      })
    );
  });
}
