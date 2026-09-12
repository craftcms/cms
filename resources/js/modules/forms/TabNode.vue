<script setup lang="ts">
    import '@craftcms/ui/components/field-group/field-group';
    import FormNodeList from './FormNodeList.vue';
    import {formTabPanelId} from './runtime';
    import type {FormChange, FormNodePayload, FormPayload} from './types';

    const props = defineProps<{
        node: FormNodePayload<{label: string}>;
        values: FormPayload['values'];
        errors: FormPayload['errors'];
        touchedPaths: Set<string>;
        scope: string[];
        refreshable: boolean;
        initiallyHidden?: boolean;
        tabButtonId?: string;
    }>();
    const emit = defineEmits<{
        (event: 'change', change: FormChange): void;
    }>();

    function id(): string {
        return formTabPanelId(props.node.uid!, props.scope);
    }
</script>

<template>
    <section
        :id="id()"
        :class="{hidden: initiallyHidden}"
        :role="tabButtonId ? 'tabpanel' : undefined"
        :aria-label="node.props.label"
        :aria-labelledby="tabButtonId"
        :data-id="id()"
        :data-form-tab="node.uid"
        :data-layout-tab="node.uid"
    >
        <craft-field-group>
            <FormNodeList
                :nodes="node.children ?? []"
                :values="values"
                :errors="errors"
                :touched-paths="touchedPaths"
                :scope="scope"
                :refreshable="refreshable"
                @change="emit('change', $event)"
            />
        </craft-field-group>
    </section>
</template>
