import {createApp, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {
    MappingCol,
    MappingColEntry,
    MappingValues,
} from '@/modules/import/mapping/types';
import Map from './Map.vue';

const state = vi.hoisted(() => ({
    layout: vi.fn(),
    save: vi.fn(),
    openNested: vi.fn(),
}));

vi.mock('@/common/composables/useAppLayout', () => ({
    useAppLayout: state.layout,
}));

vi.mock('@/modules/settings/composables/useSettingsSave', () => ({
    useSettingsSave: (...args: unknown[]) => {
        state.save(...args);

        return {save: vi.fn()};
    },
}));

vi.mock('@/modules/import/mapping/nested-mapping', () => ({
    openNestedMapping: state.openNested,
}));

function col(
    overrides: Partial<MappingCol> & {prefixedHandle: string}
): MappingCol {
    return {
        handle: overrides.prefixedHandle,
        label: overrides.prefixedHandle,
        prefixedHandleAsArray: [overrides.prefixedHandle],
        prefixedHandleForMap: `map[${overrides.prefixedHandle}]`,
        prefixedHandleForMatchCriteria: `matchCriteria[${overrides.prefixedHandle}]`,
        prefixedHandleForClear: `clearableItems[${overrides.prefixedHandle}]`,
        isContainer: false,
        canBeMatchCriteria: false,
        canBeCleared: false,
        ...overrides,
    };
}

const title = col({prefixedHandle: 'title', canBeMatchCriteria: true});
const outerMatrix = col({
    prefixedHandle: 'outerMatrix',
    isContainer: true,
    fieldUid: 'field-uid',
    canKeepMissingNestedElements: true,
});

function emptyValues(): MappingValues {
    return {
        map: {},
        matchCriteria: {},
        clearableItems: {},
        keepMissingNestedElements: {},
    };
}

function mount(
    destinationCols: MappingColEntry[],
    values: MappingValues = emptyValues()
) {
    app = createApp(Map, {
        config: {
            uid: 'import-uid',
            handle: 'people',
            name: 'People',
            file: 'people.csv',
        },
        destinationCols,
        sourceDataCols: [
            {label: 'Please select', value: ''},
            {label: 'Name', value: 'name'},
        ],
        values,
        submit: {method: 'post', url: '/actions/import/configs/saveMap'},
        nestedColsUrl: '/actions/import/configs/nested-mapping-cols',
        readOnly: false,
        canSave: true,
    });
    app.config.compilerOptions.isCustomElement = (tag) => tag.includes('-');
    app.mount(container);
}

/** What the page would post right now. The page copies its props, so this is the
 * only faithful view of its state. */
function posted(): MappingValues & {importUid: string} {
    return state.save.mock.calls[0]![2].transform();
}

/**
 * Toggles the row's checkbox the way `craft-checkbox` reports one: the host carries
 * the state, and `model-value-changed` announces it.
 */
function toggleCheckbox(checked = true): void {
    const checkbox = container.querySelector<HTMLElement & {checked: boolean}>(
        'craft-checkbox'
    )!;
    checkbox.checked = checked;

    checkbox.dispatchEvent(
        new CustomEvent('model-value-changed', {bubbles: true, detail: {}})
    );
}

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
    state.layout.mockClear();
    state.save.mockClear();
    state.openNested.mockReset();
    container = document.createElement('div');
    document.body.append(container);
});

afterEach(() => {
    app.unmount();
    container.remove();
});

it('writes a chosen source column to the destination column’s path', async () => {
    mount([title]);

    const select = container.querySelector('select')!;
    select.value = 'name';
    select.dispatchEvent(new Event('change'));
    await nextTick();

    expect(posted().map).toEqual({title: 'name'});
});

it('keeps edits to trees the server sent as empty arrays', () => {
    // PHP has one array type, so an empty tree arrives as `[]`, not `{}`. Writing a
    // handle key onto a JS array works in memory but is dropped by JSON.stringify, so
    // the edit would never reach the server.
    mount([title], {
        map: {},
        matchCriteria: [],
        clearableItems: [],
        keepMissingNestedElements: [],
    } as unknown as MappingValues);

    toggleCheckbox();

    expect(posted().matchCriteria).toEqual({title: '1'});
});

it('posts the mapping trees alongside the config UID', () => {
    mount([title], {...emptyValues(), map: {title: 'name'}});

    expect(posted()).toEqual({
        importUid: 'import-uid',
        map: {title: 'name'},
        matchCriteria: {},
        clearableItems: {},
        keepMissingNestedElements: {},
    });
});

it('shows a container column’s nested mapping rather than a source select', () => {
    mount([outerMatrix]);

    expect(container.querySelector('select')).toBeNull();

    container.querySelector('craft-button')!.dispatchEvent(new Event('click'));

    expect(state.openNested).toHaveBeenCalledOnce();
    expect(state.openNested.mock.calls[0]![0]).toMatchObject({
        col: outerMatrix,
        importUid: 'import-uid',
        colsUrl: '/actions/import/configs/nested-mapping-cols',
        editable: true,
    });
});

it('merges a nested panel’s result back into the trees', async () => {
    mount([outerMatrix]);

    container.querySelector('craft-button')!.dispatchEvent(new Event('click'));

    const applied: MappingValues = {
        ...emptyValues(),
        map: {outerMatrix: {outerEt: {title: 'name'}}},
        // A container's own keep decision lives under a reserved `__keep__` leaf.
        keepMissingNestedElements: {outerMatrix: {__keep__: '1'}},
    };
    state.openNested.mock.calls[0]![0].apply(applied);
    await nextTick();

    expect(posted().map).toEqual(applied.map);
    expect(posted().keepMissingNestedElements).toEqual(
        applied.keepMissingNestedElements
    );
});
