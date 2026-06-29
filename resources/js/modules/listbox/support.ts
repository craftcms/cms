import type {Listbox} from './Listbox';

/**
 * Maps a listbox container element back to its {@link Listbox} instance — the
 * native replacement for the legacy `$container.data('listbox', this)`, and the
 * double-instantiation guard. Mirrors the sortable-checkbox-select `support.ts`.
 */
export const containerListboxes = new WeakMap<Element, Listbox>();
