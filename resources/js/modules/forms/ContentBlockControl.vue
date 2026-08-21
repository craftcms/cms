<script setup lang="ts">
    import '@craftcms/ui/components/button/button';
    import '@craftcms/ui/components/empty/empty';
    import '@craftcms/ui/components/spinner/spinner';
    import {t} from '@craftcms/ui';
    import {useEventListener} from '@vueuse/core';
    import {ref} from 'vue';
    import FormNodeList from './FormNodeList.vue';
    import type {
        FormChange,
        FormControlPayload,
        FormPayload,
        NestedFormPayload,
    } from './types';
    import {inputName} from './runtime';

    type ContentBlockProps = {
        addLabel: string;
        clearLabel?: string;
        emptyLabel?: string;
    };

    const props = defineProps<{
        control: FormControlPayload<ContentBlockProps>;
        value: Record<string, unknown> | null;
        values: FormPayload['values'];
        errors: FormPayload['errors'];
        touchedPaths: Set<string>;
        editable: boolean;
    }>();
    const emit = defineEmits<{
        (
            event: 'update:value',
            value: Record<string, unknown> | null,
            kind: 'discrete'
        ): void;
        (event: 'change', change: FormChange): void;
    }>();
    const host = ref<HTMLElement>();

    useEventListener(host, 'input', (event) => {
        if (event.target === host.value) {
            emit(
                'update:value',
                host.value?.querySelector('[data-content-block]') ? {} : null,
                'discrete'
            );
        }
    });

    function nestedChange(change: FormChange, form: NestedFormPayload): void {
        emit('change', {
            ...change,
            scope: form.scope,
            refreshable: form.refreshable,
        });
    }
</script>

<template>
    <craft-content-block-input
        ref="host"
        :add-label="control.props.addLabel"
        :clear-label="control.props.clearLabel"
        :empty-label="control.props.emptyLabel"
    >
        <input v-if="editable" type="hidden" :name="inputName(control.path)" />
        <craft-empty v-if="value === null" :label="control.props.emptyLabel">
            <craft-button
                v-if="editable"
                type="button"
                icon="plus"
                data-content-block-add
            >
                {{ control.props.addLabel }}
            </craft-button>
        </craft-empty>
        <div v-else class="pane" data-content-block>
            <template v-if="control.forms?.[0]">
                <FormNodeList
                    :nodes="control.forms[0].nodes"
                    :values="values"
                    :errors="errors"
                    :touched-paths="touchedPaths"
                    :scope="control.forms[0].scope"
                    :refreshable="control.forms[0].refreshable"
                    @change="nestedChange($event, control.forms[0])"
                />
            </template>
            <craft-spinner v-else :label="t('Loading')" />
            <craft-button
                v-if="editable"
                type="button"
                icon="trash"
                data-content-block-remove
            >
                {{ control.props.clearLabel }}
            </craft-button>
        </div>
    </craft-content-block-input>
</template>
