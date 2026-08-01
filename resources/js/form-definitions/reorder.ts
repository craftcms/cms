export type ReorderDirection = 'up' | 'down';

export function reorderItems<T>(
  items: readonly T[],
  index: number,
  direction: ReorderDirection
): T[] | null {
  const destination = direction === 'up' ? index - 1 : index + 1;

  if (destination < 0 || destination >= items.length) {
    return null;
  }

  const reordered = [...items];
  [reordered[index], reordered[destination]] = [
    reordered[destination]!,
    reordered[index]!,
  ];

  return reordered;
}

export function reorderPosition(
  index: number,
  length: number
): 'first' | 'middle' | 'last' {
  if (index === 0) {
    return 'first';
  }

  return index === length - 1 ? 'last' : 'middle';
}
