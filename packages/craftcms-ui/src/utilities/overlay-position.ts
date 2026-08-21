/**
 * Popper modifiers that let a `strategy: 'fixed'` overlay escape the clipping
 * and scrolling ancestors it's rendered inside.
 *
 * The `fixed` strategy alone isn't enough: it writes viewport coordinates, but
 * whether those land where Popper thinks depends on the ancestor chain, which
 * Popper can't see from inside a shadow root. These modifiers close that gap,
 * and more than one overlay component needs them, so they live here rather
 * than being copied between them.
 */
export function viewportEscapingModifiers(): Array<Record<string, unknown>> {
  return [
    {
      // Position with top/left instead of `transform`. A transformed ancestor
      // becomes the containing block for descendant `position: fixed`
      // overlays, which would re-trap anything nested inside this one's
      // clipping pane. Using top/left keeps the viewport as the containing
      // block so nested overlays escape.
      name: 'computeStyles',
      options: {
        gpuAcceleration: false,
      },
    },
    {
      // Popper's `fixed` strategy writes viewport coordinates, but an ancestor
      // that forms a fixed-position containing block (`transform`,
      // `will-change: transform`, `container-type` — the CP slideout
      // qualifies) rebases them onto itself, shifting the pane by the
      // ancestor's offset. Popper can't see that ancestor from inside the
      // shadow root, so measure where the pane actually landed after each
      // write and subtract the difference.
      name: 'containingBlockCorrection',
      enabled: true,
      phase: 'afterWrite' as const,
      fn: ({state}: {state: {elements: {popper: HTMLElement}}}) => {
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
    },
  ];
}
