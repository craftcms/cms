import type {MatrixInput} from './matrix-input';
import type {MatrixEntry} from './matrix-entry';

/**
 * Maps a `[data-matrix-field]` container back to its {@link MatrixInput} instance —
 * the native replacement for the legacy `$container.data('matrix', this)`, and
 * the double-instantiation guard. Mirrors the listbox `support.ts`.
 */
export const containerMatrixInputs = new WeakMap<Element, MatrixInput>();

/**
 * Maps a `[data-matrix-block]` container back to its {@link MatrixEntry} instance —
 * the native replacement for the legacy `$container.data('entry', this)`.
 */
export const containerMatrixEntries = new WeakMap<Element, MatrixEntry>();
