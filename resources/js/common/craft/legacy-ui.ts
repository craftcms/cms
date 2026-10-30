/**
 * Starts the legacy jQuery UI (menus, form-submit links, date pickers, …) in
 * server-rendered markup that was just added to the page.
 *
 * The legacy bundle provides it; markup that uses only custom elements needs
 * none of this.
 */
export function initLegacyUi(
  container: Element | JQuery | null | undefined
): void {
  if (container) {
    window.Craft?.initUiElements?.(container);
  }
}
