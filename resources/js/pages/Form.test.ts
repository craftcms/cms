import {
    createApp,
    defineComponent,
    h,
    nextTick,
    onMounted,
    reactive,
} from 'vue';
import {afterEach, beforeEach, expect, it, vi} from 'vite-plus/test';
import type {FormChange, FormPayload} from '@/modules/forms/types';
import FormPage from './Form.vue';

const state = vi.hoisted(() => ({
    layout: vi.fn(),
    post: vi.fn(),
    refresh: undefined as
        | ((
              values: FormPayload['values'],
              scope: string[]
          ) => Promise<FormPayload>)
        | undefined,
    submit: vi.fn(),
    setValue: vi.fn(),
    change: undefined as
        | ((change: FormChange, values: FormPayload['values']) => void)
        | undefined,
    currentValues: {siteId: 42, name: 'Changed', live: '1'} as Record<
        string,
        unknown
    >,
    confirmElevation: vi.fn(),
}));

vi.mock('@craftcms/ui', async (importOriginal) => ({
    ...(await importOriginal()),
    actionClient: {post: state.post},
}));

vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({props: {}}),
    useForm: () => {
        let transform = (data: Record<string, unknown>) => data;
        const form: Record<string, any> = {
            errors: {},
            isDirty: false,
            data: () => (form.name === undefined ? {} : {name: form.name}),
            defaults: vi.fn(),
        };
        form.clearErrors = vi.fn(() => form);
        form.transform = vi.fn((callback) => {
            transform = callback;
            return form;
        });
        form.submit = vi.fn((action, options) => {
            state.submit(action, transform(form.data()));
            options.onSuccess?.();
        });

        return form;
    },
}));

vi.mock('@/common/composables/useAppLayout', () => ({
    useAppLayout: state.layout,
}));

vi.mock('@/modules/auth/elevated-session', () => ({
    elevatedSessionManager: {require: state.confirmElevation},
}));

vi.mock('@/modules/forms/FormRenderer.vue', () => ({
    default: defineComponent({
        props: ['refresh'],
        emits: ['change', 'update:mutation'],
        setup: (props, {emit, expose, slots}) => {
            state.refresh = props.refresh;
            state.change = (change, values) => emit('change', change, values);
            expose({
                advanceBaseline: vi.fn(),
                currentValues: () => structuredClone(state.currentValues),
                setValue: state.setValue,
            });
            onMounted(() => emit('update:mutation', {name: 'Changed'}));

            return () => h('div', slots.name?.({value: 'Original'}));
        },
    }),
}));

const payload: FormPayload = {
    scope: [],
    refreshable: false,
    nodes: [],
    values: {name: 'Original', live: '1'},
    errors: [],
    globalErrors: [],
};

let app: ReturnType<typeof createApp>;
let container: HTMLElement;

beforeEach(() => {
    state.layout.mockClear();
    state.post.mockReset();
    state.refresh = undefined;
    state.change = undefined;
    state.setValue.mockReset();
    state.submit.mockClear();
    state.confirmElevation.mockReset();
    state.currentValues = {siteId: 42, name: 'Changed', live: '1'};
    container = document.createElement('div');
    document.body.append(container);
});

afterEach(() => {
    app.unmount();
    container.remove();
});

it('submits complete current values after a partial mutation', async () => {
    app = createApp(FormPage, {
        form: payload,
        submit: {method: 'post', url: '/settings/general'},
    });
    app.mount(container);
    await nextTick();

    expect(container.querySelector('form')).not.toBeNull();

    state.layout.mock.calls[0]![0].onSave({redirect: false});

    expect(state.submit).toHaveBeenCalledWith(
        {method: 'post', url: '/settings/general'},
        expect.objectContaining({siteId: 42, name: 'Changed', live: '1'})
    );
});

it('passes screen layout options through to the app layout', () => {
    app = createApp(FormPage, {
        form: payload,
        submit: {method: 'post', url: '/settings/users'},
        fullWidth: true,
        defaultFormActions: [],
    });
    app.mount(container);

    expect(state.layout).toHaveBeenCalledWith(
        expect.objectContaining({fullWidth: true, defaultFormActions: []})
    );
});

it('accepts reactive Inertia form values', () => {
    app = createApp(FormPage, {
        form: reactive(payload),
        submit: {method: 'post', url: '/settings/users/groups'},
        elevatedFields: ['permissions'],
    });

    expect(() => app.mount(container)).not.toThrow();
});

it('confirms elevated field changes once per saved baseline', async () => {
    state.currentValues = {
        siteId: 42,
        name: 'Changed',
        live: '1',
        permissions: ['accessCp'],
    };
    state.confirmElevation.mockResolvedValue(true);
    app = createApp(FormPage, {
        form: {...payload, values: {...payload.values, permissions: []}},
        submit: {method: 'post', url: '/settings/users/groups'},
        elevatedFields: ['permissions'],
    });
    app.mount(container);
    await nextTick();

    state.layout.mock.calls[0]![0].onSave();
    await vi.waitFor(() => expect(state.submit).toHaveBeenCalledTimes(1));

    state.layout.mock.calls[0]![0].onSave();

    expect(state.confirmElevation).toHaveBeenCalledTimes(1);
    expect(state.submit).toHaveBeenCalledTimes(2);
});

it('refreshes the form through the configured endpoint', async () => {
    const refreshed = {...payload, values: {name: 'Changed', live: '1'}};
    state.post.mockResolvedValueOnce({data: {form: refreshed}});
    app = createApp(FormPage, {
        form: payload,
        submit: {method: 'post', url: '/settings/sites'},
        refreshUrl: '/settings/sites/form',
    });
    app.mount(container);
    await nextTick();

    const result = await state.refresh!({hasUrls: true}, []);

    expect(state.post).toHaveBeenCalledWith('/settings/sites/form', {
        values: {hasUrls: true},
        scope: [],
    });
    expect(result).toBe(refreshed);
});

it('forwards control changes and external value updates', async () => {
    const onChange = vi.fn();
    app = createApp(FormPage, {
        form: payload,
        submit: {method: 'post', url: '/settings/sites'},
        onChange,
    });
    const page = app.mount(container) as unknown as {
        setValue(
            path: string[],
            value: unknown,
            kind?: FormChange['kind']
        ): void;
    };
    await nextTick();

    const change: FormChange = {kind: 'typing', path: ['name']};
    state.change!(change, {name: 'My Site'});
    page.setValue(['baseUrl'], '$MY_SITE_URL', 'typing');

    expect(onChange).toHaveBeenCalledWith(change, {name: 'My Site'});
    expect(state.setValue).toHaveBeenCalledWith(
        ['baseUrl'],
        '$MY_SITE_URL',
        'typing'
    );
});

it('forwards path-keyed control slots to the Form renderer', async () => {
    app = createApp({
        setup: () => () =>
            h(
                FormPage,
                {
                    form: payload,
                    submit: {method: 'post', url: '/settings/sites'},
                },
                {
                    name: ({value}: {value: string}) =>
                        h('span', {'data-name-override': ''}, value),
                }
            ),
    });
    app.mount(container);
    await nextTick();

    expect(container.querySelector('[data-name-override]')?.textContent).toBe(
        'Original'
    );
});
