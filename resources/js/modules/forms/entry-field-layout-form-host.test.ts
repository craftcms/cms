import {defineComponent, h, nextTick} from 'vue';
import {afterEach, expect, it, vi} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import FieldNode from './FieldNode.vue';
import TextControl from './TextControl.vue';
import TabNode from './TabNode.vue';
import CraftInput from '@craftcms/ui/components/input/input';
import {actionClient, serializeFormInputsAsObject} from '@craftcms/ui';
import {defineEntryFieldLayoutFormHost} from './entry-field-layout-form-host';
import type {EntryFieldLayoutFormHost} from './entry-field-layout-form-host';
import type {FormPayload} from './types';

afterEach(() => {
    vi.useRealTimers();
    vi.restoreAllMocks();
    vi.unstubAllGlobals();
    document.body.replaceChildren();
});

it('submits Entry Form values and preserves refresh context', async () => {
    vi.useFakeTimers();
    const components = createCpComponentRegistry();
    components.register('craft:field', FieldNode);
    components.register('craft:text', TextControl);
    components.register('craft:tab', TabNode);
    components.register(
        'test:nested-refresh',
        defineComponent({
            emits: ['change'],
            setup:
                (_, {emit}) =>
                () =>
                    h(
                        'button',
                        {
                            type: 'button',
                            'data-nested-refresh': '',
                            onClick: () =>
                                emit('change', {
                                    kind: 'discrete',
                                    path: ['editor', 'matrix'],
                                    scope: [
                                        'editor',
                                        'matrix',
                                        'entries',
                                        'block-a',
                                    ],
                                    refreshable: true,
                                }),
                        },
                        'Refresh nested form'
                    ),
        })
    );
    defineEntryFieldLayoutFormHost(components);

    const form = document.createElement('form');
    const host = document.createElement('craft-entry-field-layout-form');
    const payload = {
        scope: ['editor'],
        refreshable: true,
        nodes: [
            {
                type: 'Tab',
                component: 'craft:tab',
                props: {label: 'Content'},
                uid: 'entry-content',
                children: [
                    {
                        type: 'Field',
                        component: 'craft:field',
                        props: {label: 'Title', required: true},
                        control: {
                            type: 'Text',
                            component: 'craft:text',
                            props: {},
                            path: ['editor', 'title'],
                            mode: 'editable',
                            deltaGroup: ['editor', 'title'],
                        },
                    },
                    {
                        type: 'Nested',
                        component: 'test:nested-refresh',
                        props: {},
                        control: {
                            type: 'Nested',
                            component: 'test:nested-refresh',
                            props: {},
                            path: ['editor', 'matrix'],
                            mode: 'editable',
                            deltaGroup: ['editor', 'matrix'],
                            forms: [
                                {
                                    scope: [
                                        'editor',
                                        'matrix',
                                        'entries',
                                        'block-a',
                                    ],
                                    refreshable: true,
                                    nodes: [],
                                },
                            ],
                        },
                    },
                ],
            },
        ],
        values: {
            editor: {
                title: 'Original',
                matrix: {entries: {'block-a': {}}, sortOrder: ['block-a']},
            },
        },
        errors: [],
        globalErrors: [],
    } satisfies FormPayload;
    const actionRequest = vi.spyOn(actionClient, 'post').mockResolvedValue({
        data: {
            form: {
                scope: ['editor', 'matrix', 'entries', 'block-a'],
                refreshable: true,
                nodes: [],
                values: payload.values,
                errors: [],
                globalErrors: [],
            },
            headHtml: '<style>nested</style>',
            bodyHtml: '<script>nested</script>',
        },
    });
    const appendHeadHtml = vi.fn();
    const appendBodyHtml = vi.fn();
    vi.stubGlobal('Craft', {
        namespaceId: (id: string, namespace?: string) =>
            namespace ? `${namespace}-${id}` : id,
        appendHeadHtml,
        appendBodyHtml,
    });
    (host as EntryFieldLayoutFormHost).requestMetadata = () => ({
        elementType: 'CraftCms\\Cms\\Entry\\Elements\\Entry',
        elementId: null,
        elementUid: 'block-a',
        fieldId: 7,
        ownerId: 42,
        typeId: 9,
        sortOrder: 2,
    });
    host.dataset.payload = JSON.stringify(payload);
    form.append(host);
    document.body.append(form);
    const editor = {
        settings: {
            elementType: 'Entry',
            elementId: 42,
            siteId: 1,
            updateTabs: vi.fn(),
        },
    };
    vi.stubGlobal('$', () => ({
        data: () => editor,
        serialize: () =>
            new URLSearchParams(
                serializeFormInputsAsObject(form) as Record<string, string>
            ).toString(),
    }));
    await nextTick();

    expect(serializeFormInputsAsObject(form)).toEqual({
        'editor[title]': 'Original',
    });

    host.querySelector<HTMLButtonElement>('[data-nested-refresh]')!.click();
    await vi.advanceTimersByTimeAsync(100);

    const input = host.querySelector<HTMLElement>('craft-input')!;
    (input as CraftInput).modelValue = 'Edited';
    input.dispatchEvent(
        new CustomEvent('model-value-changed', {bubbles: true})
    );
    await nextTick();

    expect(serializeFormInputsAsObject(form)).toEqual({
        'editor[title]': 'Edited',
    });
    expect(actionRequest).toHaveBeenCalledOnce();
    const data = actionRequest.mock.calls[0]![1] as string;
    const options = actionRequest.mock.calls[0]![2] as {
        headers: Record<string, string>;
    };
    expect(new URLSearchParams(data).get('editor[selectedTab]')).toBe(
        'editor-form-tab-entry-content'
    );
    expect(Object.fromEntries(new URLSearchParams(data))).toMatchObject({
        'editor[elementType]': 'CraftCms\\Cms\\Entry\\Elements\\Entry',
        'editor[elementUid]': 'block-a',
        'editor[fieldId]': '7',
        'editor[ownerId]': '42',
        'editor[typeId]': '9',
        'editor[sortOrder]': '2',
    });
    expect(new URLSearchParams(data).has('editor[elementId]')).toBe(false);
    expect(options.headers).toMatchObject({
        'X-Craft-Namespace': 'editor',
        'X-Craft-Form-Root-Scope': '["editor"]',
        'X-Craft-Form-Scope': '["editor","matrix","entries","block-a"]',
    });
    expect(appendHeadHtml).toHaveBeenCalledWith('<style>nested</style>');
    expect(appendBodyHtml).toHaveBeenCalledWith('<script>nested</script>');
});
