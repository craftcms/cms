const sharedContainerTypes = new Set([
  'craft:field',
  'craft:group',
  'craft:tabs',
  'craft:tab',
]);

export function isSharedContainer(type: string): boolean {
  return sharedContainerTypes.has(type);
}
