import {nextTick} from 'vue';
import {afterEach, expect, it} from 'vite-plus/test';
import {createCpComponentRegistry} from '@/bootstrap/components';
import {defineLayoutComponentSettingsFormHost} from './layout-component-settings-form-host';
import ActionNode from './ActionNode.vue';
import CheckboxControl from './CheckboxControl.vue';
import FieldNode from './FieldNode.vue';
import TextControl from './TextControl.vue';
import type {FormPayload} from './types';

afterEach(() => document.body.replaceChildren());

type Host = HTMLElement & {
    payload: FormPayload | null;
    errors: Record<string, string | string[]>;
    currentValues(): Record<string, unknown>;
};

function payload(): FormPayload {
    return {
        scope: ['settings'],
        refreshable: true,
        nodes: [
            {
                type: 'Field',
                component: 'craft:field',
                props: {label: 'Label', required: false, hasActions: true},
                control: {
                    type: 'Text',
                    component: 'craft:text',
                    props: {},
                    path: ['settings', 'label'],
                    mode: 'editable',
                    deltaGroup: ['settings', 'label'],
                },
                children: [
                    {
                        type: 'Action',
                        component: 'craft:action',
                        props: {},
                        control: {
                            type: 'Checkbox',
                            component: 'craft:checkbox',
                            props: {label: 'Hide'},
                            path: ['settings', 'labelHidden'],
                            mode: 'editable',
                            deltaGroup: ['settings', 'labelHidden'],
                        },
                    },
                ],
            },
        ],
        values: {settings: {label: 'Heading', labelHidden: false}},
        errors: [],
        globalErrors: [],
    } as FormPayload;
}

function mountHost(): Host {
    const components = createCpComponentRegistry();
    components.register('craft:field', FieldNode);
    components.register('craft:action', ActionNode);
    components.register('craft:text', TextControl);
    components.register('craft:checkbox', CheckboxControl);
    defineLayoutComponentSettingsFormHost(components);

    const host = document.createElement(
        'craft-layout-component-settings-form'
    ) as Host;
    host.payload = payload();
    document.body.append(host);

    return host;
}

it('renders action controls into the field’s actions slot', async () => {
    const host = mountHost();
    await nextTick();

    const checkbox = host.querySelector('craft-checkbox');
    expect(checkbox).not.toBeNull();
    expect(checkbox?.closest('[slot="actions"]')).not.toBeNull();
    expect(checkbox?.getAttribute('name')).toBe('settings[labelHidden]');
});

it('returns settings values unwrapped from the form scope', async () => {
    const host = mountHost();
    await nextTick();

    expect(host.currentValues()).toEqual({
        label: 'Heading',
        labelHidden: false,
    });
});

it('prefixes assigned errors with the form scope', async () => {
    const host = mountHost();
    await nextTick();

    host.errors = {handle: 'Handle is taken.', label: ['Too long.']};
    await nextTick();

    expect(host.payload).not.toBeNull();
    expect(host.querySelector('.error-list')?.textContent).toContain(
        'Too long.'
    );
});
