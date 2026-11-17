/**
 * A random id for linking elements with ARIA attributes. Random rather than
 * counted, so it can't collide with ids in server-rendered markup.
 */
export function uniqueId(prefix = ''): string {
  const id = Math.random().toString(36).slice(2, 12);

  return prefix ? `${prefix}-${id}` : id;
}
