export const containingBlockCorrection = {
  name: 'containingBlockCorrection',
  enabled: true,
  phase: 'afterWrite' as const,
  fn: ({state}: {state: {elements: {popper: HTMLElement}}}) => {
    // Fixed-position descendants are rebased when a slideout ancestor creates
    // a containing block. Correct Popper's viewport coordinates by measuring
    // where the element actually landed.
    const pane = state.elements.popper;
    const left = parseFloat(pane.style.left);
    const top = parseFloat(pane.style.top);

    if (Number.isNaN(left) || Number.isNaN(top)) {
      return;
    }

    const rect = pane.getBoundingClientRect();
    const dx = rect.x - left;
    const dy = rect.y - top;

    if (dx !== 0 || dy !== 0) {
      pane.style.left = `${left - dx}px`;
      pane.style.top = `${top - dy}px`;
    }
  },
};
