import type {EntryType} from '@/common/types';
import type {FormChange, FormPayload} from '@/modules/forms/types';
import {createApp, defineComponent, h, nextTick} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import Edit from './Edit.vue';

const state = vi.hoisted(() => ({
    change: undefined as
        | ((change: FormChange, values: FormPayload['values']) => void)
        | undefined,
    setValue: vi.fn(),
    setEntryTypes: vi.fn(),
}));

vi.mock('@/pages/Form.vue', async () => {
    const {defineComponent, h} = await import('vue');

    return {
        default: defineComponent({
            props: ['form'],
            emits: ['change'],
            setup: (props, {emit, expose, slots}) => {
                state.change = (change, values) =>
                    emit('change', change, values);
                expose({setValue: state.setValue});

                return () =>
                    h(
                        'div',
                        slots.entryTypes?.({
                            value: props.form.values.entryTypes,
                            setValue: state.setEntryTypes,
                            editable: true,
                        })
                    );
            },
        }),
    };
});

vi.mock('@/modules/entry-types/components/EntryTypeSelect.vue', () => ({
    default: defineComponent({
        props: ['entryTypes', 'modelValue'],
        emits: ['update:modelValue'],
        setup:
            (props, {emit}) =>
            () =>
                h(
                    'button',
                    {
                        onClick: () =>
                            emit('update:modelValue', [props.entryTypes[0]]),
                    },
                    props.modelValue
                        .map((entryType: EntryType) => entryType.id)
                        .join(',')
                ),
    }),
}));

const entryTypes = [
    {id: 1, name: 'Article'},
    {id: 2, name: 'News'},
] as EntryType[];
const values = {
    sectionId: null,
    name: '',
    type: 'channel',
    entryTypes: [2, 1],
    sites: {
        default: {
            enabled: true,
            siteId: 1,
            name: 'Default',
            singleHomepage: false,
            singleUri: '',
            uriFormat: '',
            template: '',
            enabledByDefault: true,
        },
    },
};
const form: FormPayload = {
    scope: [],
    refreshable: true,
    nodes: [],
    values,
    errors: [],
    globalErrors: [],
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
    state.change = undefined;
    state.setValue.mockReset();
    state.setEntryTypes.mockReset();
    container = document.createElement('div');
    document.body.append(container);
});

afterEach(() => {
    app.unmount();
    container.remove();
});

it('generates new section site settings from the name', async () => {
    await mount(true);

    state.change!({kind: 'typing', path: ['name']}, {...values, name: 'News'});

    expect(state.setValue).toHaveBeenCalledWith(
        ['sites'],
        {
            default: {
                ...values.sites.default,
                singleUri: 'news',
                uriFormat: 'news/{slug}',
                template: 'news/_entry.twig',
            },
        },
        'typing'
    );
});

it('does not generate site settings for an existing section', async () => {
    await mount(false);

    state.change!({kind: 'typing', path: ['name']}, {...values, name: 'News'});

    expect(state.setValue).not.toHaveBeenCalled();
});

it('adapts entry type IDs to the existing selector', async () => {
    await mount(true);

    expect(container.querySelector('button')?.textContent).toBe('2,1');
    container.querySelector('button')?.click();

    expect(state.setEntryTypes).toHaveBeenCalledWith([1], 'discrete');
});

async function mount(brandNew: boolean): Promise<void> {
    app = createApp(Edit, {
        form,
        submit: {method: 'post', url: '/sections'},
        refreshUrl: '/sections/form',
        brandNew,
        entryTypes,
        homepageUri: '__home__',
        templateOptions: [],
        isMultiSite: false,
        headlessMode: false,
    });
    app.mount(container);
    await nextTick();
}
