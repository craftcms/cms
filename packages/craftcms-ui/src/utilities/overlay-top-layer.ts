import {css} from 'lit';

/** The `<dialog>` Lion wraps an overlay's content in. */
function overlayDialog(host: HTMLElement): HTMLElement | null {
  return (
    host.shadowRoot?.querySelector<HTMLElement>(
      'dialog[data-overlay-outer-wrapper]'
    ) ?? null
  );
}

/**
 * Lifts an open overlay into the browser's top layer, above every stacking
 * context. Lion's non-modal dialog otherwise stays inside its ancestors': a
 * z-index can't raise it past a sticky footer beside, say, a `container-type`
 * field group, which is a stacking context of its own, and an ancestor that
 * clips its overflow cuts it off.
 *
 * Call it before Lion positions the overlay, since the top layer also changes
 * what a fixed overlay is placed against. Overlays raised later stack above
 * earlier ones, so nested menus still land on top.
 */
export function raiseOverlay(host: HTMLElement): void {
  const dialog = overlayDialog(host);

  if (!dialog || typeof dialog.showPopover !== 'function') {
    return;
  }

  dialog.setAttribute('popover', 'manual');

  if (!dialog.matches(':popover-open')) {
    dialog.showPopover();
  }
}

/** Returns an overlay raised with {@link raiseOverlay} to its place. */
export function lowerOverlay(host: HTMLElement): void {
  const dialog = overlayDialog(host);

  if (dialog?.matches?.(':popover-open')) {
    dialog.hidePopover();
  }
}

/**
 * For overlays raised with {@link raiseOverlay}. The top layer's popover
 * defaults would center the dialog and paint it as a box of its own; Lion
 * positions the content, so the dialog stays invisible.
 */
export const topLayerStyles = css`
  dialog[data-overlay-outer-wrapper][popover] {
    inset: auto;
    margin: 0;
    padding: 0;
    border: 0;
    background: none;
    color: inherit;
    overflow: visible;
    width: auto;
    height: auto;
  }
`;
