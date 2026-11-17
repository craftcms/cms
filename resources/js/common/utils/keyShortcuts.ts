/** Whether the platform's shortcuts use Command, as Apple platforms do, rather than Control. */
export function usesCommandKey(): boolean {
  return /Mac|iPhone|iPad/.test(navigator.platform);
}

/**
 * An `aria-keyshortcuts` value for Command (Control off Apple platforms) with
 * a key, such as `Meta+S` or `Control+Shift+S`.
 */
export function commandShortcut(key: string, {shift = false} = {}): string {
  return [
    usesCommandKey() ? 'Meta' : 'Control',
    ...(shift ? ['Shift'] : []),
    key,
  ].join('+');
}
