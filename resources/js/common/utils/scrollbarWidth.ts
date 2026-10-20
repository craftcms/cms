/**
 * Returns the width of a classic scrollbar, or 0 where scrollbars overlay
 * the content.
 */
export function measureScrollbarWidth(doc: Document = document): number {
  const probe = doc.createElement('div');
  probe.style.cssText =
    'position: absolute; inset-block-start: -9999px; width: 100px; height: 100px; overflow: scroll;';
  doc.body.append(probe);
  const width = probe.offsetWidth - probe.clientWidth;
  probe.remove();

  return width;
}

/**
 * Keeps `--cp-scrollbar-width` on the root element in step with the
 * scrollbar width, which changes with the zoom level, so the layout can keep
 * clear of the page scrollbar that overlays it.
 */
export function trackScrollbarWidth(doc: Document = document): void {
  const update = () =>
    doc.documentElement.style.setProperty(
      '--cp-scrollbar-width',
      `${measureScrollbarWidth(doc)}px`
    );

  update();
  doc.defaultView?.addEventListener('resize', update);
}
