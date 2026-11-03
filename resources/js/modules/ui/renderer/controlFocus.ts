/**
 * Remembers which control has focus before a re-render that may replace it.
 *
 * Returns a function that, once the DOM has updated, focuses the same element
 * if it survived, or else the control now rendered at the same path — or
 * nothing, when no control had focus.
 */
export function captureControlFocus(): (() => void) | undefined {
  const focusedElement =
    document.activeElement instanceof HTMLElement
      ? document.activeElement
      : undefined;
  const focusedPath = focusedElement?.closest<HTMLElement>(
    '[data-ui-control-path]'
  )?.dataset.uiControlPath;

  if (!focusedPath) {
    return undefined;
  }

  return () => {
    const control = [
      ...document.querySelectorAll<HTMLElement>('[data-ui-control-path]'),
    ].find((element) => element.dataset.uiControlPath === focusedPath);
    const focusTarget = focusedElement?.isConnected
      ? focusedElement
      : control?.hasAttribute('data-ui-control-override')
        ? control.querySelector<HTMLElement>(
            'input:checked, input:not([type="hidden"]), button, select, textarea, [tabindex]:not([tabindex="-1"])'
          )
        : control;

    focusTarget?.focus();
  };
}
