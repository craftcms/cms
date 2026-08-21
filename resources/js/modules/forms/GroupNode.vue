<script setup lang="ts">
    import '@craftcms/ui/components/field-group/field-group';
    import '@craftcms/ui/components/disclosure/disclosure';
    import FormNodeList from './FormNodeList.vue';
    import type {FormChange, FormNodePayload, FormPayload} from './types';

    type GroupNodeProps = {
        label?: string | null;
        collapsible?: boolean;
    };

    defineProps<{
        node: FormNodePayload<GroupNodeProps>;
        values: FormPayload['values'];
        errors: FormPayload['errors'];
        touchedPaths: Set<string>;
        scope: string[];
        refreshable: boolean;
    }>();
    const emit = defineEmits<{
        (event: 'change', change: FormChange): void;
    }>();
</script>

<template>
    <component
        :is="node.props.collapsible ? 'craft-disclosure' : 'fieldset'"
        :label="node.props.collapsible ? node.props.label : undefined"
        :data-form-node="node.uid"
    >
        <legend v-if="!node.props.collapsible && node.props.label">
            {{ node.props.label }}
        </legend>
        <craft-field-group
            :slot="node.props.collapsible ? 'content' : undefined"
        >
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
    </component>
</template>
