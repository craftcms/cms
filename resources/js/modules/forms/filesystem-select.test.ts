import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import {createApp, h, nextTick, ref} from 'vue';

const state = vi.hoisted(() => ({
    slideoutCount: 0,
    onSaved: (_event: {
        data: {filesystem: {name: string; handle: string}};
    }) => {},
}));

vi.mock('@/common/slideouts', () => ({
    openSlideout: (
        _createUrl: string,
        settings: {
            onSaved: (event: {
                data: {filesystem: {name: string; handle: string}};
            }) => void;
        }
    ) => {
        state.slideoutCount++;
        state.onSaved = settings.onSaved;

        return new Promise(() => {});
    },
}));

let filesystemSelect: HTMLElementTagNameMap['craft-filesystem-select'];

function inputFor(element: HTMLElement): HTMLInputElement {
    const input = element.querySelector<HTMLInputElement>('input');

    if (!input) {
        throw new Error('Filesystem select input was not rendered.');
    }

    return input;
}

beforeEach(async () => {
    await import('./filesystem-select');
    state.slideoutCount = 0;
    filesystemSelect = document.createElement('craft-filesystem-select');
    filesystemSelect.createUrl = '/settings/filesystems/new';
    filesystemSelect.options = [
        {
            type: 'optgroup',
            label: 'Craft Filesystems',
            options: [{label: 'Create a new filesystem…', value: '__add__'}],
        },
    ];
    document.body.append(filesystemSelect);
    await filesystemSelect.updateComplete;
});

afterEach(() => filesystemSelect.remove());

it('selects a filesystem created in the slideout', async () => {
    const createOption = Array.from(
        filesystemSelect.querySelectorAll<HTMLElement>('craft-option')
    ).find(
        (option) => option.textContent?.trim() === 'Create a new filesystem…'
    );

    createOption?.click();
    await vi.waitFor(() => expect(state.slideoutCount).toBe(1));

    expect(inputFor(filesystemSelect).value).toBe('');
    await new Promise((resolve) => setTimeout(resolve));

    state.onSaved({
        data: {filesystem: {name: 'Uploads', handle: 'uploads'}},
    });

    await vi.waitFor(() =>
        expect(
            Array.from(filesystemSelect.querySelectorAll('craft-option')).map(
                (option) => option.textContent?.trim()
            )
        ).toContain('Uploads')
    );
    await vi.waitFor(() => {
        expect(inputFor(filesystemSelect).value).toBe('Uploads');
    });

    inputFor(filesystemSelect).click();
    await vi.waitFor(() => expect(filesystemSelect.opened).toBe(true));

    expect(
        Array.from(filesystemSelect.querySelectorAll('craft-option')).map(
            (option) => option.textContent?.trim()
        )
    ).toEqual(['Uploads', 'Create a new filesystem…']);
});

it('lets users select a filesystem through the Vue control', async () => {
    const {default: FilesystemSelectControl} =
        await import('./FilesystemSelectControl.vue');
    const selectedValue = ref('uploads');
    const control = {
        type: 'CraftCms\\Cms\\Form\\Controls\\FilesystemSelect',
        component: 'craft:filesystem-select',
        props: {
            options: [
                {label: 'Uploads', value: 'uploads'},
                {label: 'Archives', value: 'archives'},
                {
                    type: 'optgroup' as const,
                    label: 'Craft Filesystems',
                    options: [
                        {label: 'Create a new filesystem…', value: '__add__'},
                    ],
                },
            ],
            createUrl: '/settings/filesystems/new',
            clearable: true,
            requireOptionMatch: true,
            showAllOnEmpty: true,
            showSelectedHint: true,
        },
        path: ['fs'],
        mode: 'editable' as const,
        deltaGroup: ['fs'],
    };
    const container = document.createElement('div');
    document.body.append(container);
    const app = createApp({
        render: () =>
            h(FilesystemSelectControl, {
                control,
                value: selectedValue.value,
                editable: true,
                invalid: false,
                required: false,
                'onUpdate:value': (value: string) => {
                    selectedValue.value = value;
                },
            }),
    });

    app.mount(container);
    await nextTick();
    const select = container.querySelector('craft-filesystem-select')!;
    await select.updateComplete;
    await vi.waitFor(() => expect(inputFor(select).value).toBe('Uploads'));

    const archivesOption = Array.from(
        select.querySelectorAll<HTMLElement>('craft-option')
    ).find((option) => option.textContent?.trim() === 'Archives');

    archivesOption?.click();

    await vi.waitFor(() => expect(inputFor(select).value).toBe('Archives'));
    expect(selectedValue.value).toBe('archives');

    const createOption = Array.from(
        select.querySelectorAll<HTMLElement>('craft-option')
    ).find(
        (option) => option.textContent?.trim() === 'Create a new filesystem…'
    );

    createOption?.click();
    await vi.waitFor(() => expect(selectedValue.value).toBe(''));

    state.onSaved({
        data: {filesystem: {name: 'Private', handle: 'private'}},
    });
    await vi.waitFor(() => expect(selectedValue.value).toBe('private'));

    app.unmount();
    container.remove();
});
