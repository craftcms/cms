import type {
    MappingCol,
    MappingColEntry,
    MappingColSet,
    MappingValues,
} from './types';

/**
 * Reads a column's value out of one of the mapping trees.
 *
 * Replaces the Twig screen's `prefixedHandleAsArray|reduce(...)`: a missing branch
 * reads as `null` rather than throwing, since the trees only hold what has been
 * mapped so far.
 */
export function getAt(
    tree: Record<string, unknown> | null | undefined,
    path: string[]
): unknown {
    return path.reduce<unknown>(
        (value, part) =>
            value !== null && typeof value === 'object'
                ? (value as Record<string, unknown>)[part]
                : undefined,
        tree
    );
}

/** Writes a column's value into one of the mapping trees, creating branches as needed. */
export function setAt(
    tree: Record<string, unknown>,
    path: string[],
    value: unknown
): void {
    const leaf = path[path.length - 1]!;
    let branch = tree;

    for (const part of path.slice(0, -1)) {
        const next = branch[part];

        if (next === null || typeof next !== 'object' || Array.isArray(next)) {
            branch[part] = {};
        }

        branch = branch[part] as Record<string, unknown>;
    }

    branch[leaf] = value;
}

/**
 * A container's own keep-missing decision lives under a reserved `__keep__` leaf,
 * because the container's handle also has to hold its nested containers' decisions.
 */
export function keepFlagPath(col: MappingCol): string[] {
    return [...col.prefixedHandleAsArray, '__keep__'];
}

/**
 * Match criteria and clearable items are stored loosely — `1`, `'1'`, or the mapped
 * column's name once `normalizeMatchCriteriaFromImporterConfig()` has resolved it.
 * The screen only cares whether they are set.
 */
export function isChecked(value: unknown): boolean {
    return (
        value !== undefined && value !== null && value !== '' && value !== false
    );
}

/**
 * The value a `craft-checkbox` should write into one of the trees: `1` and `''`
 * rather than booleans, which is what the Twig checkboxes posted and what
 * `validateMap()` and the importer already read back.
 *
 * Read off `currentTarget` — the `craft-checkbox` the listener is bound to, which
 * mirrors its slotted input's state. `target` is the host for an event Lion raises
 * itself and the input for one that bubbles up from it, so it can't be relied on.
 */
export function checkedValue(event: Event): string {
    const checkbox = event.currentTarget as {checked?: boolean} | null;

    return checkbox?.checked ? '1' : '';
}

/** Whether a `destinationCols` entry is a labelled run of subfields rather than one column. */
export function isColSet(entry: MappingColEntry): entry is MappingColSet {
    return (
        !Array.isArray(entry) && 'multiple' in entry && entry.multiple === true
    );
}

/** Whether a `destinationCols` entry is a column at all — `[]` means "skip me". */
export function isCol(entry: MappingColEntry): entry is MappingCol {
    return !Array.isArray(entry) && !isColSet(entry);
}

/**
 * Rewrites every array in a tree as an object.
 *
 * PHP has one array type, so an empty tree — or any empty branch within one — reaches
 * the client as `[]` rather than `{}`. Writing a handle key onto a JS array succeeds
 * in memory but `JSON.stringify` drops it, so the edit would silently never reach the
 * server. The trees are only ever handle-keyed maps, so nothing here is a real list.
 */
export function toObjectTree<T>(value: T): T {
    if (Array.isArray(value)) {
        return Object.fromEntries(
            value.map((item, index) => [index, toObjectTree(item)])
        ) as T;
    }

    if (value !== null && typeof value === 'object') {
        return Object.fromEntries(
            Object.entries(value).map(([key, item]) => [
                key,
                toObjectTree(item),
            ])
        ) as T;
    }

    return value;
}

/**
 * A structural copy of the trees, so a nested panel can be cancelled without a trace.
 *
 * Round-tripped through JSON rather than `structuredClone()`, which can't clone the
 * reactive proxies the screens hold these in.
 */
export function cloneValues(values: MappingValues): MappingValues {
    return toObjectTree(JSON.parse(JSON.stringify(values)) as MappingValues);
}
