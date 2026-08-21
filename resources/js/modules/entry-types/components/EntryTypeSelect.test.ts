import type {EntryType} from '@/common/types';
import {createApp, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import EntryTypeSelect from './EntryTypeSelect.vue';

const state = vi.hoisted(() => ({
    readOnly: false,
    newEntryType: {id: 2, name: 'News', handle: 'news'},
    open: vi.fn(),
    reload: vi.fn(),
}));

vi.mock('@inertiajs/vue3', () => ({router: {reload: state.reload}}));
vi.mock('@actions/Settings/EntryTypesController', () => ({
    applyOverrideSettings: () => ({url: '/apply'}),
    create: () => ({url: '/create'}),
    renderOverrideSettings: () => ({url: '/render'}),
}));
vi.mock('@/common/composables/useCraftData', () => ({
    default: () => ({readOnly: state.readOnly}),
}));
vi.mock('@/common/composables/useReorderableItems', () => ({
    useReorderableItems: () => ({
        setItemRef: vi.fn(),
        setHandleRef: vi.fn(),
        getDragState: () => ({type: 'idle'}),
        getDropState: () => ({type: 'idle'}),
    }),
}));
vi.mock('@/common/slideouts', () => ({
    useSlideoutOpener: () => ({open: state.open}),
}));

const existingEntryType = {
    id: 1,
    name: 'Article',
    handle: 'article',
    color: null,
    description: null,
} as EntryType;
const newEntryType = state.newEntryType as EntryType;
const container = document.createElement('div');
let app: ReturnType<typeof createApp>;

afterEach(() => {
    app.unmount();
    container.replaceChildren();
    state.open.mockReset();
    state.reload.mockReset();
    state.readOnly = false;
});

it('keeps selected entry types visible without edit actions when read-only', () => {
    state.readOnly = true;
    app = createApp(EntryTypeSelect, {
        modelValue: [existingEntryType],
        entryTypes: [existingEntryType],
    });
    app.mount(container);

    expect(container.textContent).toContain('Article');
    expect(container.textContent).not.toContain('Settings');
    expect(container.textContent).not.toContain('Remove');
    expect(container.textContent).not.toContain('Create');
});

it('selects an entry type created from the picker', async () => {
    const update = vi.fn();
    app = createApp(EntryTypeSelect, {
        modelValue: [existingEntryType],
        entryTypes: [existingEntryType],
        'onUpdate:modelValue': update,
    });
    app.mount(container);

    const createButton = [...container.querySelectorAll('craft-button')].find(
        (button) => button.textContent?.trim() === 'Create'
    )!;
    createButton.click();
    await nextTick();
    state.open.mock.calls[0]![1].onSaved({
        data: {entryType: newEntryType},
    });
    await nextTick();

    expect(update).toHaveBeenCalledWith([existingEntryType, newEntryType]);
});
