/**
 * Opening a container column's mapping in a slideout panel.
 *
 * Opened with `openSlideoutWith()` rather than `openSlideout()`: the mapping being
 * edited is unsaved client state the map page already holds, with no URL to GET. The
 * server is asked only for the container's *structure* — which columns exist under it
 * — and the panel edits a copy of the screen's trees, handing them back on Apply.
 *
 * That copy is the whole set of trees rather than just the container's branch, so a
 * nested column's absolute `prefixedHandleAsArray` addresses the same place in the
 * panel as it does on the page, and nesting works to any depth without rebasing paths.
 */
import {actionClient} from '@craftcms/ui';
import type {InertiaPageComponent} from '@/bootstrap/inertia-pages';
import {cloneValues} from './paths';
import type {
    MappingCol,
    MappingGroup,
    MappingValues,
    SourceDataCol,
} from './types';

export interface NestedMappingContext {
    col: MappingCol;
    fieldName: string;
    groups: MappingGroup[];
    sourceDataCols: SourceDataCol[];
    values: MappingValues;
    editable: boolean;
    /** Carried through so a container inside the panel can open a panel of its own. */
    importUid: string;
    colsUrl: string;
    apply(values: MappingValues): void;
}

export interface OpenNestedMappingOptions {
    col: MappingCol;
    importUid: string;
    /** Endpoint returning the container's destination columns. */
    colsUrl: string;
    values: MappingValues;
    editable: boolean;
    opener: HTMLElement | null;
    apply(values: MappingValues): void;
}

// Callbacks can't ride in `ScreenPageProps`, so the panel is handed a key instead and
// claims its context on setup.
const contexts = new Map<string, NestedMappingContext>();
let nextContextId = 0;

export function takeNestedMappingContext(
    contextId: string
): NestedMappingContext {
    const context = contexts.get(contextId);

    if (!context) {
        throw new Error(`Unknown nested mapping context: ${contextId}`);
    }

    contexts.delete(contextId);

    return context;
}

/**
 * Returns false when the panel was not opened — the user declined to discard unsaved
 * changes in a panel this one would have replaced.
 */
export async function openNestedMapping(
    options: OpenNestedMappingOptions
): Promise<boolean> {
    const {col} = options;

    const {data} = await actionClient.get(options.colsUrl, {
        params: {
            importUid: options.importUid,
            fieldUid: col.fieldUid ?? '',
            fieldHandle: col.prefixedHandle,
            fieldIsProperty: col.isProperty ? 1 : 0,
        },
    });

    const [{openSlideoutWith}, {default: NestedMapping}] = await Promise.all([
        import('@/common/slideouts'),
        import('./NestedMapping.vue'),
    ]);

    const contextId = `import-nested-mapping-${++nextContextId}`;
    contexts.set(contextId, {
        col,
        fieldName: data.fieldName,
        groups: data.groups,
        sourceDataCols: data.sourceDataCols ?? [],
        values: cloneValues(options.values),
        editable: options.editable,
        importUid: options.importUid,
        colsUrl: options.colsUrl,
        apply: options.apply,
    });

    // SAFETY: The slideout host renders this imported Vue SFC exactly like its Inertia
    // page components; it does not require an Inertia page module.
    const panel = openSlideoutWith(
        NestedMapping as InertiaPageComponent,
        {contextId, title: data.title},
        {opener: options.opener}
    );

    if (!panel) {
        contexts.delete(contextId);

        return false;
    }

    return true;
}
