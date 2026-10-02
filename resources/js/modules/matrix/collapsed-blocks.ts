/**
 * Which Matrix blocks the user has collapsed, remembered per install in
 * `localStorage`.
 *
 * Collapsed state is deliberately not persisted server-side — `collapsed` has
 * no column, it's a per-person view preference. Craft 5 kept it here, under this
 * exact key and comma-joined format, so anyone upgrading keeps their collapsed
 * blocks (see `Craft.MatrixInput.getCollapsedEntryIds()`).
 *
 * Blocks are remembered by their bare identity. A block the browser just minted
 * is keyed `uid:<uuid>` until the next save adopts it, so the prefix is stripped
 * on the way in and its collapsed state survives being materialized.
 */

import {NESTED_ELEMENT_UID_PREFIX} from '@/modules/forms/types';

const STORAGE_KEY = 'MatrixInput.collapsedEntries';

function storageKey(): string {
  // Same prefix `useStorage()` applies, spelled out here because this module is
  // used from plain classes as well as components.
  return `Craft-${window.Craft?.systemUid ?? ''}.${STORAGE_KEY}`;
}

/** The identity a block is remembered under, whoever minted it. */
export function collapsedBlockId(id: string | number): string {
  const value = `${id}`;

  return value.startsWith(NESTED_ELEMENT_UID_PREFIX)
    ? value.slice(NESTED_ELEMENT_UID_PREFIX.length)
    : value;
}

/**
 * Storage is unavailable in some contexts (private windows, blocked site data),
 * and reads there throw rather than returning null.
 */
function read(): string[] {
  try {
    return (localStorage.getItem(storageKey()) ?? '')
      .split(',')
      .filter(Boolean);
  } catch {
    return [];
  }
}

function write(ids: string[]): void {
  try {
    localStorage.setItem(storageKey(), ids.join(','));
  } catch {
    // Nothing to remember it with; the session's collapsed state still works.
  }
}

export function collapsedBlockIds(): string[] {
  return read();
}

export function isBlockCollapsed(id: string | number): boolean {
  return read().includes(collapsedBlockId(id));
}

export function rememberCollapsedBlock(id: string | number): void {
  const ids = read();
  const value = collapsedBlockId(id);

  if (!ids.includes(value)) {
    write([...ids, value]);
  }
}

export function forgetCollapsedBlock(id: string | number): void {
  const value = collapsedBlockId(id);
  const ids = read();

  if (ids.includes(value)) {
    write(ids.filter((id) => id !== value));
  }
}

export function setBlockCollapsed(
  id: string | number,
  collapsed: boolean
): void {
  if (collapsed) {
    rememberCollapsedBlock(id);
  } else {
    forgetCollapsedBlock(id);
  }
}

export function setCollapsedBlockIds(ids: Array<string | number>): void {
  write(ids.map(collapsedBlockId));
}
