/**
 * Which Matrix blocks the user has collapsed, remembered per install and per
 * site in `localStorage`.
 *
 * Collapsed state is deliberately not persisted server-side — `collapsed` has
 * no column, it's a per-person view preference. Craft 5 kept it under one key
 * for every site, in this comma-joined format (see
 * `Craft.MatrixInput.getCollapsedEntryIds()`). Each site now gets its own key,
 * like a block's enabled state. A site with no key of its own yet reads the
 * old one, so anyone upgrading keeps their collapsed blocks, and its first
 * write starts from that list.
 *
 * Blocks are remembered by their bare identity. A block the browser just minted
 * is keyed `uid:<uuid>` until the next save adopts it, so the prefix is stripped
 * on the way in and its collapsed state survives being materialized.
 */

import {prefixedKey} from '@/common/composables/useStorage';
import {NESTED_ELEMENT_UID_PREFIX} from '@/modules/ui/types';

const STORAGE_KEY = 'MatrixInput.collapsedEntries';

type SiteId = number | string | null | undefined;

function storageKey(siteId?: SiteId): string {
  return prefixedKey(
    siteId == null || siteId === '' ? STORAGE_KEY : `${siteId}.${STORAGE_KEY}`
  );
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
function read(siteId?: SiteId): string[] {
  try {
    const value =
      localStorage.getItem(storageKey(siteId)) ??
      localStorage.getItem(storageKey()) ??
      '';

    return value.split(',').filter(Boolean);
  } catch {
    return [];
  }
}

function write(ids: string[], siteId?: SiteId): void {
  try {
    localStorage.setItem(storageKey(siteId), ids.join(','));
  } catch {
    // Nothing to remember it with; the session's collapsed state still works.
  }
}

export function collapsedBlockIds(siteId?: SiteId): string[] {
  return read(siteId);
}

export function isBlockCollapsed(
  id: string | number,
  siteId?: SiteId
): boolean {
  return read(siteId).includes(collapsedBlockId(id));
}

export function rememberCollapsedBlock(
  id: string | number,
  siteId?: SiteId
): void {
  const ids = read(siteId);
  const value = collapsedBlockId(id);

  if (!ids.includes(value)) {
    write([...ids, value], siteId);
  }
}

export function forgetCollapsedBlock(
  id: string | number,
  siteId?: SiteId
): void {
  const value = collapsedBlockId(id);
  const ids = read(siteId);

  if (ids.includes(value)) {
    write(
      ids.filter((id) => id !== value),
      siteId
    );
  }
}

export function setBlockCollapsed(
  id: string | number,
  collapsed: boolean,
  siteId?: SiteId
): void {
  if (collapsed) {
    rememberCollapsedBlock(id, siteId);
  } else {
    forgetCollapsedBlock(id, siteId);
  }
}

export function setCollapsedBlockIds(
  ids: Array<string | number>,
  siteId?: SiteId
): void {
  write(ids.map(collapsedBlockId), siteId);
}
