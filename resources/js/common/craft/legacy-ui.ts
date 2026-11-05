/**
 * Starts the legacy jQuery UI (menus, form-submit links, outbound links, …) in
 * server-rendered markup that was just added to the page.
 *
 * Core's own markup uses custom elements and needs none of it; this is for
 * plugin markup inside what core inserts. It does nothing without the legacy
 * bundle.
 */
export function initLegacyUi(
  container: Element | JQuery | null | undefined
): void {
  if (container) {
    window.Craft?.initUiElements?.(container);
  }
}
