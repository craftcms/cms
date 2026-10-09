export function escapeMarkdownLabel(label: string): string {
  return label.replace(/([[\]\\])/g, '\\$1');
}

export function isModifierKeyPressed(event: KeyboardEvent): boolean {
  return navigator.platform.toLowerCase().includes('mac')
    ? event.metaKey
    : event.ctrlKey;
}
