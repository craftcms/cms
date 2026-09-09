<script setup lang="ts">
    /**
     * Maps an import config's destination columns onto the headings in its source file.
     *
     * The page owns all four mapping trees, so a container column's nested mapping is
     * edited in a panel over the same state rather than relayed through the server —
     * see `modules/import/mapping/nested-mapping.ts`.
     */
    import type {UrlMethodPair} from '@inertiajs/core';
    import {useForm} from '@inertiajs/vue3';
    import {provide, reactive, watch} from 'vue';
    import {t} from '@craftcms/ui';
    import {useAppLayout} from '@/common/composables/useAppLayout';
    import {useSettingsSave} from '@/modules/settings/composables/useSettingsSave';
    import MappingTable from '@/modules/import/mapping/MappingTable.vue';
    import {toObjectTree} from '@/modules/import/mapping/paths';
    import {openNestedMapping} from '@/modules/import/mapping/nested-mapping';
    import {
        type MappingCol,
        type MappingColEntry,
        MappingContextKey,
        type MappingValues,
        type SourceDataCol,
    } from '@/modules/import/mapping/types';

    const props = defineProps<{
        config: {
            uid: string;
            handle: string | null;
            name: string | null;
            file: string | null;
        };
        destinationCols: MappingColEntry[];
        sourceDataCols: SourceDataCol[] | null;
        values: MappingValues;
        submit: UrlMethodPair;
        nestedColsUrl: string;
        readOnly: boolean;
        canSave: boolean;
    }>();

    const editable = !props.readOnly && props.canSave;
    const values = reactive<MappingValues>(toObjectTree(props.values));

    /**
     * Backs the Save button and the unsaved-changes prompt. Inertia diffs against the
     * value the form was created with, so a serialization of the trees stands in for
     * them; `transform` posts the trees themselves.
     */
    const form = useForm({state: JSON.stringify(values)});

    watch(
        values,
        () => {
            form.state = JSON.stringify(values);
        },
        {deep: true}
    );

    const {save} = useSettingsSave(form, () => props.submit, {
        transform: () => ({importUid: props.config.uid, ...toPlain()}),
    });

    useAppLayout({form, onSave: save});

    // Read from the trees, not from `form.state` — that only catches up when the deep
    // watcher flushes, so it can still be a tick behind the edit being saved.
    function toPlain(): MappingValues {
        return JSON.parse(JSON.stringify(values)) as MappingValues;
    }

    provide(MappingContextKey, {
        values,
        sourceDataCols: props.sourceDataCols ?? [],
        editable,
        openNested(col: MappingCol, opener: HTMLElement | null): void {
            void openNestedMapping({
                col,
                importUid: props.config.uid,
                colsUrl: props.nestedColsUrl,
                values,
                editable,
                opener,
                apply: (applied) => Object.assign(values, applied),
            });
        },
    });
</script>

<template>
    <form @submit.prevent="save()">
        <craft-pane appearance="raised">
            <p>{{ t('File: {file}', {file: config.file ?? ''}) }}</p>

            <MappingTable :cols="destinationCols" />
        </craft-pane>
    </form>
</template>
