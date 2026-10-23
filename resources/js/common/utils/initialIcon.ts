/** A label's first character, uppercased, or `?` when it's blank. */
export function initialOf(label: string): string {
  const [first] = Array.from(label.trim());

  return first ? first.toLocaleUpperCase() : '?';
}

/**
 * An element showing a label's first letter, sized like an icon (see
 * `.initial-icon`), for things that would otherwise have no icon. It's
 * decorative: whatever it stands in for should be named some other way.
 */
export function createInitialIcon(label: string): HTMLElement {
  const $icon = document.createElement('span');
  $icon.className = 'initial-icon';
  $icon.setAttribute('aria-hidden', 'true');
  $icon.textContent = initialOf(label);

  return $icon;
}
