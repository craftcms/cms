import type {CraftGrid} from '@craftcms/ui';

/**
 * Puts every result grid under `root` back on its first result, so Tab into a
 * new search's results starts at the top rather than where the last search's
 * were left.
 */
export function resetResultGrids(root: ParentNode | null | undefined): void {
  for (const grid of root?.querySelectorAll<CraftGrid>('craft-grid') ?? []) {
    grid.resetTabStop?.();
  }
}
