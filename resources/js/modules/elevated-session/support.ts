import type {ElevatedSessionForm} from './elevated-session-form';

/**
 * Maps a form element back to its {@link ElevatedSessionForm} instance — the
 * native replacement for a `$form.data('elevatedSessionForm', this)`
 * back-reference, and the double-instantiation guard. Mirrors the
 * `listbox` / `sortable-checkbox-select` `support.ts`.
 */
export const formElevatedSessions = new WeakMap<Element, ElevatedSessionForm>();
