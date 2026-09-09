<script setup lang="ts">
    /**
     * A container column's mapping, as a slideout panel.
     *
     * Opened with `openSlideoutWith()` — see `nested-mapping.ts` for why. The panel
     * edits its own copy of the screen's trees and hands them back on Apply, so
     * cancelling leaves the screen behind untouched.
     */
    import '@craftcms/ui/components/checkbox/checkbox';
    import {computed, provide, reactive, watch} from 'vue';
    import {t} from '@craftcms/ui';
    import {useForm} from '@inertiajs/vue3';
    import {useAppLayout} from '@/common/composables/useAppLayout';
    import {useSlideout} from '@/common/slideouts';
    import {ignoreModelValueInitialization} from '@/modules/forms/runtime';
    import MappingTable from './MappingTable.vue';
    import {checkedValue, getAt, isChecked, keepFlagPath, setAt} from './paths';
    import {
        openNestedMapping,
        takeNestedMappingContext,
    } from './nested-mapping';
    import {
        type MappingCol,
        MappingContextKey,
        type MappingValues,
    } from './types';

    const props = defineProps<{
        contextId: string;
        title: string;
    }>();

    const context = takeNestedMappingContext(props.contextId);
    const slideout = useSlideout();
    const values = reactive<MappingValues>(context.values);

    /**
     * Backs the shell's Apply button and gives it an accurate dirty check for the
     * unsaved-changes prompt. Inertia diffs against the value it was created with, so
     * a serialization of the trees stands in for them.
     */
    const form = useForm({state: JSON.stringify(values)});

    watch(
        values,
        () => {
            form.state = JSON.stringify(values);
        },
        {deep: true}
    );

    useAppLayout(() => ({
        title: props.title,
        submitButtonLabel: t('Apply'),
        form,
        onSave: apply,
    }));

    const keepPath = computed(() => keepFlagPath(context.col));

    const keepMissingChecked = computed(() =>
        isChecked(getAt(values.keepMissingNestedElements, keepPath.value))
    );

    const onKeepMissingChanged = ignoreModelValueInitialization((event) => {
        setAt(
            values.keepMissingNestedElements,
            keepPath.value,
            checkedValue(event)
        );
    });

    provide(MappingContextKey, {
        values,
        sourceDataCols: context.sourceDataCols,
        editable: context.editable,
        openNested(col: MappingCol, opener: HTMLElement | null): void {
            void openNestedMapping({
                col,
                importUid: context.importUid,
                colsUrl: context.colsUrl,
                values,
                editable: context.editable,
                opener,
                apply: (applied) => Object.assign(values, applied),
            });
        },
    });

    function apply(): void {
        context.apply(JSON.parse(JSON.stringify(values)) as MappingValues);

        // Before close(): closing drops the panel from the store, and its handler with it.
        slideout?.saved();
        slideout?.close({force: true});
    }
</script>

<template>
    <div>
        <craft-checkbox
            v-if="context.col.canKeepMissingNestedElements"
            :label="
                t(
                    'Keep existing nested elements missing from the imported data.'
                )
            "
            .checked="keepMissingChecked"
            :disabled="!context.editable"
            @model-value-changed="onKeepMissingChanged"
        ></craft-checkbox>

        <section v-for="(group, index) in context.groups" :key="index">
            <h3>{{ group.providerName ?? context.fieldName }}</h3>
            <MappingTable :cols="group.destinationCols" />
        </section>
    </div>
</template>
