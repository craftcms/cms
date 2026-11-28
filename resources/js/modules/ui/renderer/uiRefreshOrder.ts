import {isWithin} from './uiTree';

/**
 * Orders a host's refreshes of its UI, so an answer isn't applied once a newer
 * one covers its scope. A later refresh of the same scope, of a scope inside
 * it, or of one it's inside supersedes a refresh. Refreshes of sibling scopes,
 * such as two blocks of a Matrix field, don't supersede each other.
 */
export function createUiRefreshOrder() {
  /** The refreshes nothing has superseded, which is at most one per scope. */
  const standing = new Set<{scope: string[]}>();

  function supersede(matches: (scope: string[]) => boolean): void {
    for (const refresh of standing) {
      if (matches(refresh.scope)) {
        standing.delete(refresh);
      }
    }
  }

  return {
    /**
     * Starts a refresh of `scope`. Returns a check for whether something has
     * superseded it since.
     */
    start(scope: string[]): () => boolean {
      supersede((other) => isWithin(other, scope) || isWithin(scope, other));
      const refresh = {scope};
      standing.add(refresh);

      return () => !standing.has(refresh);
    },

    /**
     * Supersedes every refresh, for a newer payload that reached the host
     * another way, such as from a save. Refreshes of the scopes in `kept`, and
     * of scopes inside them, stand. Pass the nested UIs the payload left as
     * placeholders, as it shows them no newer than those refreshes will.
     */
    invalidate(kept: string[][] = []): void {
      supersede((scope) => !kept.some((nested) => isWithin(scope, nested)));
    },
  };
}
