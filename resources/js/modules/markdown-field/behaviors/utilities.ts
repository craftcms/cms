export function escapeMarkdownLabel(label: string): string {
  return label.replace(/([[\]\\])/g, '\\$1');
}
